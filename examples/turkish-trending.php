<?php
require dirname(__DIR__) . '/autoload.php';
use ScratchByPHP\Scratch;

$scratch=new Scratch();
$projects=$scratch->turkishTrending(limit:20,scan:120);

foreach($projects as $project){
    echo '#'.$project['turkish_trend']['rank'].' '.$project['title'].' — '.$project['turkish_trend']['score'].PHP_EOL;
}
