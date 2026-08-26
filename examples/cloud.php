<?php
require dirname(__DIR__) . '/autoload.php';
use ScratchByPHP\Scratch;
$sessionId = getenv('SCRATCH_SESSION_ID');
$projectId = getenv('SCRATCH_PROJECT_ID');
if (!$sessionId || !$projectId) die("SCRATCH_SESSION_ID ve SCRATCH_PROJECT_ID gerekli.\n");
$session=(new Scratch())->loginWithSessionId($sessionId);
$cloud=$session->cloud($projectId)->connect();
$cloud->onVariable('score', function($value){ echo "score => {$value}\n"; });
$cloud->set('server_status',1);
$cloud->listen();
