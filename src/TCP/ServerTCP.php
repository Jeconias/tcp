<?php declare(strict_types=1);

namespace Sanjos\TCP;
use \Sanjos\Helper\WriteHttpHeader;
use \Sanjos\Exception\{
    ArgumentInvalidException, 
    ErrorSocketException,
    CannotReadException};

final class ServerTCP {

    private $url         = null;
    private $port        = null;
    private $server      = null;

    private $except      = [];
    private $buffers     = [];
    private $teste       = [];
    private $readable    = [];
    private $writeable   = [];
    private $connections = [];

    private const MAGICKEY  = '258EAFA5-E914-47DA-95CA-C5AB0DC85B11';
    private const PROTOCOLS = ['tcp'];
    public const TCP        = 'tcp';


    private function __construct(string $protocol, string $url, int $port)
    {

        $this->url  = $url;
        $this->port = $port;

        if(!in_array($protocol, ServerTCP::PROTOCOLS)) throw ArgumentInvalidException::show('Tipo de protocolo não habilitado.');

        $this->server = stream_socket_server("$protocol://$url:$port");
        stream_set_blocking($this->server, false);

        if($this->server === false) throw ErrorSocketException::show("Erro ao abrir a conexão socket: $protocol://$url:$port");
        
    }

    public static function createSocket(string $protocol, string $url, int $port = 1717) : self
    {
        return new ServerTCP($protocol, $url, $port);
    }

    public function start() : void
    {
        while(true)
        {
            $this->except       = null;
            $this->readable     = $this->connections;
            $this->writeable    = $this->connections;

            $this->readable[]   = $this->server;

            if(stream_select($this->readable, $this->writeable, $this->except, 0, 0) > 0){
                $this->readStreams();
                //$this->checkHandShake();
                $this->writeStreams();
                $this->closed();
            }
        }
    }

    private function acceptConnection($stream) : void
    {
        $coon = stream_socket_accept($stream, 0, $clientAddr);

        if($coon){
            stream_set_blocking($coon, false);
            $this->connections[ (int) $coon ] = $coon;
            $this->teste[(int) $coon] = false;

            echo sprintf("O Cliente [%s] conectado.\n\n", $clientAddr);
        }
    }

    private function readStreams() : void
    {
        foreach($this->readable as $stream)
        {
            if($stream === $this->server)
            {
                $this->acceptConnection($stream);
                continue;
            }

            $key = (int) $stream;

            if (isset($this->buffers[$key])) {
                $msg = fread($stream, 4096);
                if($msg === '') continue;

                $this->buffers[$key] .= $msg;

                echo sprintf("O Cliente [%s] enviou: %s\n\n", stream_socket_get_name($stream, true), $msg);
            } else {
                $this->buffers[$key] = '';
            }
        }
    }

    private function writeStreams() : void
    {
        foreach ($this->writeable as $stream) {

            $key = (int) $stream;
            $buffer = $this->buffers[$key] ?? null;

            if ($buffer != null && $buffer !== '') {
                try{
                    $file = $this->searchImage($buffer);
                    $bytesWritten = fwrite($stream, $file, strlen($file));
                    
                    echo sprintf("O Cliente [%s] recebeu [%s] Byte(s).\n\n", stream_socket_get_name($stream, true), $bytesWritten);
                }catch(CannotReadException $e){
                    $bytesWritten = fwrite($stream, $e->getMessage(), strlen($e->getMessage()));
                }
                unset($this->buffers[$key]);
            }
        }
    }

    private function checkHandShake() : void
    {
        foreach($this->teste as $key => $value)
        {
            if($value === false && isset($this->buffers[$key]) && $this->buffers[$key] != '')
            {
                $buffer = $this->buffers[$key];

                $header = $this->headerHttpRead($buffer);

                if($header !== null)
                {
                    $response = $this->headerHttpResponse($header);

                    $bytesWritten = fwrite($this->connections[$key], $response, strlen($response));
                    $this->teste[$key] = true;
                    unset($this->buffers[$key]);
                    echo sprintf("HandShake -> Bytes[%s].\n\n", $bytesWritten);
                }
            }
        }
    }

    private function closed() : void
    {
        foreach ($this->connections as $key => $coon) {
            if (feof($coon)) {

                echo sprintf("O Cliente [%s] fechou a conexão. \n\n", stream_socket_get_name($coon, true));

                fclose($coon);

                unset($this->connections[$key]);
                unset($this->buffers[$key]);
            }
        }
    }

    private function sendMessage($stream, string $message, int $flags = STREAM_OOB) : void
    {
        fwrite($stream, $message);
    }

    private function searchImage(string $imageName) : string
    {
        $exts = ['png','jpg','bmp','gif'];
        $directory = dirname(__DIR__, 2) . '\\imgs\\';
        $fileNameAndExtension = '';

        foreach($exts as $value)
        {
            if(file_exists( sprintf('%s%s.%s', $directory, $imageName, $value) )){
                $fileNameAndExtension = sprintf('%s.%s', $imageName, $value);
                break;
            }
        }

        if($fileNameAndExtension === '') throw CannotReadException::show('A imagen não existe.');

        $file = fopen("$directory$fileNameAndExtension", "rb");
        if($file === false) throw CannotReadException::show('Erro ao abrir a imagem.');
        return fread($file, filesize("$directory$fileNameAndExtension"));
    }

    private function headerHttpRead(string $headers) : ?array
    {

        $headersArr = [];
        $lines = preg_split("/\r\n/", $headers);
        foreach($lines as $line)
        {
            $line = chop($line);
            if(preg_match('/\A(\S+): (.*)\z/', $line, $matches))
            {
                $headersArr[$matches[1]] = $matches[2];
            }
        }

        return (count($headersArr) > 0) ?  $headersArr : null;
    }

    private function headerHttpResponse(array $headersArr) : string
    {
        $secKey = $headersArr['Sec-WebSocket-Key'];

        $headerW = new WriteHttpHeader();
        $headerW->addLine('HTTP/1.1 101 Web Socket Protocol Handshake')
        ->add('Upgrade', 'websocket')
        ->add('Connection', 'Upgrade')
        ->add('WebSocket-Origin', sprintf("%s:%s", $this->url, $this->port))
        ->add('WebSocket-Location', sprintf("ws://%s:%s/%s", $this->url, $this->port, 'tcp/server.php'))
        ->add('Sec-WebSocket-Accept', base64_encode(pack('H*', sha1($secKey . ServerTCP::MAGICKEY))));

        return $headerW->render();
    }

    private function messageDencode($text) : string {
        
        $length = ord($text[1]) & 127;
        if($length == 126) {
            $messages = substr($text, 4, 4);
            $data = substr($text, 8);
        }
        elseif($length == 127) {
            $messages = substr($text, 10, 4);
            $data = substr($text, 14);
        }
        else {
            $messages = substr($text, 2, 4);
            $data = substr($text, 6);
        }
        $text = "";
        for ($i = 0; $i < strlen($data); ++$i) {
            $text .= $data[$i] ^ $messages[$i%4];
        }
        return $text;
    }

    private function messageEncode(string $text) : string
    {
        $b1 = 0x80 | (0x1 & 0x0f);
        $length = strlen($text);
        
        if($length <= 125)
            $header = pack('CC', $b1, $length);
        elseif($length > 125 && $length < 65536)
            $header = pack('CCn', $b1, 126, $length);
        elseif($length >= 65536)
            $header = pack('CCNN', $b1, 127, $length);
        return $header.$text;
    }

}