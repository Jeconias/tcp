<?php declare(strict_types=1);
require './vendor/autoload.php';

ini_set('display_errors', '1');
ini_set('display_startup_errors', '1');
error_reporting(0);

use \Sanjos\Socket\ClientUDP;
use \Sanjos\Exception\ErrorSocketException;

//$value = $_POST['search'] ?? null;
//if($value === null || $value === '') header('Location: interface.php');

try{

    $client = ClientUDP::createSocket(AF_INET, SOCK_DGRAM, ClientUDP::UDP, '127.0.0.1');
    $client->sendMessage('gato');
    $client->start();

}catch(ErrorSocketException $e){
    echo $e->getMessage();
}