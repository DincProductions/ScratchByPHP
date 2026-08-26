<?php
require dirname(__DIR__) . '/autoload.php';

use ScratchByPHP\Scratch;

$scratch = new Scratch();
$project = $scratch->project(104);

echo $project->title() . PHP_EOL;
echo 'Views: ' . $project->views() . PHP_EOL;
echo 'Loves: ' . $project->loves() . PHP_EOL;
