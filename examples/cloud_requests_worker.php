<?php
require __DIR__.'/../autoload.php';
use ScratchByPHP\Scratch;

$scratch=new Scratch();
$session=$scratch->loginWithSessionId(getenv('SCRATCH_SESSION_ID') ?: '', getenv('SCRATCH_USERNAME') ?: null);
$cloud=$session->cloud(getenv('SCRATCH_PROJECT_ID') ?: '0')->connect();

$rpc=$cloud->requests('request','response');
$rpc->on('ping', fn(array $params) => ['pong'=>true,'params'=>$params,'time'=>time()]);
$rpc->on('sum', fn(array $params) => array_sum($params));
$rpc->run();
