<?php
declare(strict_types=1);
require dirname(__DIR__).'/autoload.php';
use ScratchByPHP\Scratch;

if(session_status()!==PHP_SESSION_ACTIVE)session_start();

$w=(new Scratch())->wizard(['clouddb_profiles'=>['main'=>['table'=>'scratch_cloud']]]);
$rm=new ReflectionMethod($w,'clientActions');
$rm->setAccessible(true);
$ids=array_column($rm->invoke($w),'id');

foreach(['search.turkish_trending','cloud.db_to_mysql'] as $id){
    if(!in_array($id,$ids,true))throw new RuntimeException('Wizard action missing: '.$id);
}

echo "ScratchByPHP v0.8.5 Wizard feature test OK\n";
