<?php
require dirname(__DIR__) . '/autoload.php';
use ScratchByPHP\Scratch;

$scratch=new Scratch();
$session=$scratch->login('Username','Password');
$cloud=$session->cloud(123456789);
$cloud->connect();

$result=$cloud->database('db')->getToDB(__DIR__.'/mysql-clouddb.json');
print_r($result);

$cloud->disconnect();
