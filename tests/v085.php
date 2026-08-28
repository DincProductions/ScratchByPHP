<?php
declare(strict_types=1);
require dirname(__DIR__).'/autoload.php';

use ScratchByPHP\Scratch;
use ScratchByPHP\Cloud\CloudDatabase;
use ScratchByPHP\Trending\TurkishTrending;

if(Scratch::version()!=='0.8.5')throw new RuntimeException('version');

$plan=CloudDatabase::planToDB(
    ['level'=>12,'coins'=>500,'profile'=>['rank'=>'gold']],
    ['table'=>'scratch_cloud','key_column'=>'cloud_key','value_column'=>'cloud_value','updated_at_column'=>'updated_at','upsert'=>true]
);

if(($plan['row_count']??0)!==3)throw new RuntimeException('CloudDB row count');
if(!str_contains($plan['sql'],'ON DUPLICATE KEY UPDATE'))throw new RuntimeException('CloudDB SQL');
if(($plan['rows'][2]['value']??'')!=='{"rank":"gold"}')throw new RuntimeException('CloudDB JSON value');

$projects=[
 ['id'=>1,'title'=>'Eski dev proje','description'=>'Deneme #TürkçeTrend','stats'=>['views'=>100000,'loves'=>5000,'favorites'=>2500],'history'=>['shared'=>'2024-01-01T00:00:00Z']],
 ['id'=>2,'title'=>'Yeni proje','description'=>'#TürkçeTrend yeni','stats'=>['views'=>10000,'loves'=>1600,'favorites'=>900],'history'=>['shared'=>gmdate('c',time()-86400)]],
];

$ranked=TurkishTrending::rank($projects);
if(count($ranked)!==2)throw new RuntimeException('trend count');
if(!isset($ranked[0]['turkish_trend']['score']))throw new RuntimeException('trend score');

if(!TurkishTrending::titleHasTurk('Türkiye Scratch Topluluğu'))throw new RuntimeException('studio title turk');
if(TurkishTrending::titleHasTurk('English Studio'))throw new RuntimeException('studio title false');

$collected=TurkishTrending::collectProjects([
 ['id'=>10,'title'=>'Türk Oyunları'],
 ['id'=>11,'title'=>'TÜRK Animasyon'],
 ['id'=>12,'title'=>'English Studio'],
],function(array $studio):array{
 return match((int)$studio['id']){
   10=>[
     ['id'=>101,'title'=>'A','stats'=>['views'=>100,'loves'=>2,'favorites'=>1],'history'=>['shared'=>gmdate('c')]],
     ['id'=>102,'title'=>'B','stats'=>['views'=>50,'loves'=>1,'favorites'=>0],'history'=>['shared'=>gmdate('c')]],
   ],
   11=>[
     ['id'=>101,'title'=>'A','stats'=>['views'=>100,'loves'=>2,'favorites'=>1],'history'=>['shared'=>gmdate('c')]],
   ],
   default=>[['id'=>999,'title'=>'Should not enter']],
 };
});
if(count($collected)!==2)throw new RuntimeException('studio project dedupe');
if(count($collected[0]['_turkish_trend_studios']??[])!==2)throw new RuntimeException('studio source merge');

echo "ScratchByPHP v0.8.5 feature test OK\n";
