<?php
require dirname(__DIR__) . '/autoload.php';

use ScratchByPHP\Scratch;

$scratch = new Scratch();

$username = getenv('SCRATCH_USERNAME');
$password = getenv('SCRATCH_PASSWORD');

$session = $scratch->login($username, $password);

echo 'Giriş yapıldı: ' . $session->username() . PHP_EOL;
