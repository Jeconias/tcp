<?php declare(strict_types=1);

namespace Sanjos\Socket;
use \Sanjos\Helper\WriteHttpHeader;
use \Sanjos\Exception\{
    ArgumentInvalidException, 
    ErrorSocketException,
    CannotReadException};

final class Server {

    private $url         = null;
    private $port        = null;
    private $server      = null;

    private $except      = [];
    private $buffers     = [];
    private $teste       = [];
    private $readable    = [];
    private $writeable   = [];
    private $connections = [];

    private const PROTOCOLS = ['tcp', 'udp'];
    public const TCP        = 'tcp';
    public const UDP        = 'udp';


    private function __construct(string $protocol, string $url, int $port)
    {

        $this->url  = $url;
        $this->port = $port;

        if(!in_array($protocol, Server::PROTOCOLS)) throw ArgumentInvalidException::show('Tipo de protocolo não habilitado.');

        $this->server = stream_socket_server("$protocol://$url:$port");
        stream_set_blocking($this->server, false);

        if($this->server === false) throw ErrorSocketException::show("Erro ao abrir a conexão socket: $protocol://$url:$port");
        
    }

    public static function createSocket(string $protocol, string $url, int $port = 1717) : self
    {
        return new Server($protocol, $url, $port);
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
        foreach ($this->writeable as $stream) 
        {

            $key = (int) $stream;
            $buffer = $this->buffers[$key] ?? null;

            if ($buffer != null && $buffer !== '') {
                try{
                    $file = $this->searchImage($buffer);
                    $bytesWritten = fwrite($stream, $file, strlen($file));
                    
                    //echo sprintf("O Cliente [%s] recebeu [%s] Byte(s).\n\n", stream_socket_get_name($stream, true), $bytesWritten);
                }catch(CannotReadException $e){
                    $bytesWritten = fwrite($stream, $e->getMessage(), strlen($e->getMessage()));
                }
                unset($this->buffers[$key]);
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

}