<?php
declare(strict_types=1);
require dirname(__DIR__).'/autoload.php';

use ScratchByPHP\Scratch;
use ScratchByPHP\Studio\Studio;

$search=new ReflectionMethod(Scratch::class,'searchStudios');
$all=new ReflectionMethod(Studio::class,'allProjects');
if(!$search->isPublic()||!$all->isPublic())throw new RuntimeException('studio discovery helpers missing');

echo "ScratchByPHP Turkish studio discovery surface test OK\n";
