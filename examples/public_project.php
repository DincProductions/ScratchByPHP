<?php
require dirname(__DIR__) . '/autoload.php';
use ScratchByPHP\Scratch;
$scratch = new Scratch();
$project = $scratch->project(104);
print_r($project->get());
