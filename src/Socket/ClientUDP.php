<?php declare(strict_types=1);

namespace Sanjos\Socket;
use \Sanjos\Exception\{ArgumentInvalidException, ErrorSocketException};

class ClientUDP {

    private $clientSocket   = null;
    private $messageToSend  = [];

    private const PROTOCOLS = [SOL_UDP];
    public const UDP = SOL_UDP;


    private function __construct(int $domain, int $type, int $protocol, string $ip, int $port)
    {
        $this->ip   = $ip;
        $this->port = $port;

        if(!in_array($protocol, ClientUDP::PROTOCOLS)) throw ArgumentInvalidException::show('Tipo de protocolo não habilitado.');
        
        $this->clientSocket = socket_create($domain, $type, $protocol);

        if($this->clientSocket === false) throw ErrorSocketException::show("Erro ao criar o socket do cliente: " . socket_strerror(socket_last_error()));
        if(socket_connect($this->clientSocket, $ip, $port) === false) throw ErrorSocketException::show("Erro ao se conectar ao socket do servidor: " . socket_strerror(socket_last_error()));
    }

    public static function createSocket(int $domain, int $type, int $protocol, string $ip, int $port = 1717) : self
    {
        return new ClientUDP($domain, $type, $protocol, $ip, $port);
    }

    public function start() : void
    {   
        //$this->messageToSend[] = "CLOSE";
        $this->dispatchMessage();
        while(true)
        {
            $recv = socket_recvfrom($this->clientSocket, $buffer, 1024, 0, $ip, $port);
            if(!$recv) break;
            echo sprintf("\n\nRecebido [%s] bytes de [%s:%s]: %s\n", $recv, $ip, $port, $buffer);
        }
    }

    private function dispatchMessage() : void
    {
        foreach($this->messageToSend as $key => $message)
        {
            $result = socket_sendto($this->clientSocket, $message, strlen($message), 0, $this->ip, $this->port);
            if($result === false) continue;
            if($message !== "CLOSE") echo sprintf("\nEnviado [%s] bytes para [%s:%s]: %s", $result, $this->ip, $this->port, $message);
            unset($this->messageToSend[$key]);
        }
    }

    public function sendMessage(string $message) : void 
    {
        $this->messageToSend[] = $message;
    }
}