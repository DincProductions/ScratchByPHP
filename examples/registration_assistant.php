<?php
require dirname(__DIR__) . '/autoload.php';

use ScratchByPHP\Scratch;

$scratch = new Scratch();
$registration = $scratch->registration();

$result = $registration->generateAvailableCredentials('ScratchUser');

print_r($result);
echo PHP_EOL . 'Open this page and finish CAPTCHA manually: ' . $registration->joinUrl() . PHP_EOL;
