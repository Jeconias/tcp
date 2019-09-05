<?php declare(strict_types=1);
require './vendor/autoload.php';

ini_set('display_errors', '1');
ini_set('display_startup_errors', '1');
error_reporting(E_ALL);

use \Sanjos\Socket\ServerUDP;
use \Sanjos\Exception\{ErrorSocketException, ArgumentInvalidException};

try{

    $server = ServerUDP::createSocket(AF_INET, SOCK_DGRAM, ServerUDP::UDP, '127.0.0.1');
    $server->start();

}catch(ErrorSocketException | ArgumentInvalidException $e){
    echo $e->getMessage();
}