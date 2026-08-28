<?php
require dirname(__DIR__).'/autoload.php';
use ScratchByPHP\Scratch; use ScratchByPHP\Collections\Collection; use ScratchByPHP\Cache\MemoryCache; use ScratchByPHP\Analysis\ProjectAnalyzer;
$checks=[];$check=function($name,$ok)use(&$checks){$checks[$name]=(bool)$ok;if(!$ok)throw new RuntimeException('FAIL: '.$name);};
$check('version',Scratch::VERSION==='0.8.5');$check('collection',(new Collection([1,2,3]))->filter(fn($x)=>$x>1)->count()===2);$cache=new MemoryCache();$cache->set('a',1);$check('cache',$cache->get('a')===1);$fake=Scratch::fake()->fakeProject(1,['title'=>'X','stats'=>['views'=>3]]);$check('fake',$fake->project(1)->views()===3);$an=ProjectAnalyzer::fromJson(['targets'=>[['isStage'=>true,'blocks'=>[]],['isStage'=>false,'name'=>'S','blocks'=>['a'=>['opcode'=>'motion_movesteps','topLevel'=>true,'inputs'=>[],'fields'=>[]]]]]]);$check('analyzer',$an->blockCount()===1&&$an->complexityScore()>0);echo json_encode(['ok'=>true,'checks'=>$checks],JSON_PRETTY_PRINT)."\n";
