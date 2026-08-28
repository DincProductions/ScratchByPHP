<?php
require dirname(__DIR__) . '/autoload.php';

use ScratchByPHP\Scratch;

$scratch = new Scratch();
$session = $scratch->login(
    getenv('SCRATCH_USERNAME'),
    getenv('SCRATCH_PASSWORD')
);

$cloud = $session->cloud((string)getenv('SCRATCH_PROJECT_ID'));
$cloud->connect();

echo 'score = ' . ($cloud->getRemote('score') ?? 'null') . PHP_EOL;

$result = $cloud->setVerified('score', 100);
print_r($result);

$cloud->disconnect();
