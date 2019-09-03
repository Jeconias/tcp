<?php declare(strict_types=1);

namespace Sanjos\Socket;
use \Sanjos\Exception\{ArgumentInvalidException, ErrorSocketException};

class Client {

    private $client = null;
    private $errInt = null;
    private $errStr = null;

    private const PROTOCOLS = ['tcp'];
    public const TCP        = 'tcp';


    private function __construct(string $protocol, string $url, int $porta)
    {
        if(!in_array($protocol, Client::PROTOCOLS)) throw ArgumentInvalidException::show('Tipo de protocolo não habilitado.');

        $this->client = stream_socket_client("$protocol://$url:$porta", $this->errInt, $this->errStr, 0);

        if($this->client === false) throw ErrorSocketException::show("Erro ao abrir a conexão cliente socket: $protocol://$url:$porta");
    }

    public static function createSocket(string $protocol, string $url, int $porta = 1717) : self
    {
        return new Client($protocol, $url, $porta);
    }

    public function start() : void
    {
        for($i=0; $i<=100; $i++){
            fwrite($this->client, "1234567890123456789012345678901234567890123456789012345678901234567890123456789012345678901234567890");
            $x = fread($this->client, 1024);
            echo "$i - $x";
        }


        
        //$result = fread($this->client, 4192);
        //echo $result;
        //$base64 = sprintf("data:%s;base64,%s", 'image/jpg', base64_encode($result));
        //echo "<img class='a' src='$base64'/>";
        stream_socket_shutdown($this->client, STREAM_SHUT_RDWR);
    }

    public function sendMessage(string $message) : void
    {
        stream_socket_sendto($this->client, $message);
    }


}