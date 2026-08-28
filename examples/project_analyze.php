<?php
require __DIR__.'/../autoload.php';
use ScratchByPHP\Scratch;

$project=(new Scratch())->project($argv[1] ?? '104');
print_r($project->analyze()->summary());
