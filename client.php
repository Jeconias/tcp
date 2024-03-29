<?php declare(strict_types=1);
require './vendor/autoload.php';

ini_set('display_errors', '1');
ini_set('display_startup_errors', '1');
error_reporting(0);

use \Sanjos\Socket\Client;
use \Sanjos\Exception\ErrorSocketException;

//$value = $_POST['search'] ?? null;
//if($value === null || $value === '') header('Location: interface.php');

try{

    $client = Client::createSocket(AF_INET, SOCK_DGRAM, Client::UDP, '127.0.0.1');
    $client->sendMessage('gato');
    $client->start();

}catch(ErrorSocketException $e){
    echo $e->getMessage();
}