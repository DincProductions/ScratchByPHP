<?php
require dirname(__DIR__) . '/autoload.php';
use ScratchByPHP\Scratch;
$sessionId = getenv('SCRATCH_SESSION_ID');
if (!$sessionId) die("SCRATCH_SESSION_ID ortam değişkenini ayarlayın.\n");
$session = (new Scratch())->loginWithSessionId($sessionId);
print_r($session->debug());
