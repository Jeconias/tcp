<?php declare(strict_types=1);

namespace Sanjos\Socket;
use \Sanjos\Helper\WriteHttpHeader;
use \Sanjos\Exception\{
    ArgumentInvalidException, 
    ErrorSocketException,
    CannotReadException};

final class ServerUDP {

    private $ip             = null;
    private $port           = null;
    private $serverSocket   = null;

    private $buffers     = [];
    private $readable    = [];
    private $writeable   = [];
    private $connections = [];

    private const PROTOCOL = [SOL_UDP];
    public const UDP        = SOL_UDP;


    private function __construct(int $domain, int $type, int $protocol, string $ip, int $port)
    {
        $this->ip   = $ip;
        $this->port = $port;

        if(!in_array($protocol, ServerUDP::PROTOCOL)) throw ArgumentInvalidException::show('Tipo de protocolo não habilitado.');

        $this->serverSocket = socket_create($domain, $type, $protocol);
        if($this->serverSocket === false) throw ErrorSocketException::show("Erro ao abrir a conexão socket: " . socket_strerror(socket_last_error()));

        socket_set_option($this->serverSocket, SOL_SOCKET, SO_BROADCAST, 1); 
        socket_set_nonblock($this->serverSocket);

        if(socket_bind($this->serverSocket, $ip, $port) === false) throw ErrorSocketException::show("Erro ao passar o nome para o socket: " . socket_strerror(socket_last_error()));
        //if(socket_listen($this->serverSocket, 10) === false) throw ErrorSocketException::show("Erro ao abrir a esculta para o socket: " . socket_strerror(socket_last_error()));
    }

    public static function createSocket(int $domain, int $type, int $protocol, string $ip, int $port = 1717) : self
    {
        return new ServerUDP($domain, $type, $protocol, $ip, $port);
    }

    public function start() : void
    {
        while(true)
        {
            $recv = socket_recvfrom($this->serverSocket, $buffer, 1024, 0, $ip, $port);
            if($recv)
            {
                if($port == $this->port) continue;
                echo sprintf("O cliente [%s:%s] enviou: %s\n", $ip, $port, $buffer);
                socket_sendto($this->serverSocket, "Welcome!", 1024, 0, $ip, $port);
                $this->broadcast();
            }
        }
    }

    private function broadcast() : void
    {
        socket_sendto($this->serverSocket, "alguém entrou.", 1024, 0, '255.255.255.255', $this->port);
    }
}