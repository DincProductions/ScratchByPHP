<?php
declare(strict_types=1);
require dirname(__DIR__) . '/autoload.php';

use ScratchByPHP\Scratch;
use ScratchByPHP\Cache\MemoryCache;
use ScratchByPHP\Cache\ManagedCache;
use ScratchByPHP\Observability\Metrics;
use ScratchByPHP\Watch\EventQueue;
use ScratchByPHP\Watch\ProjectWatch;
use ScratchByPHP\Http\RetryPolicy;
use ScratchByPHP\Http\CircuitBreaker;
use ScratchByPHP\Sb3\Sb3Validator;

$tests=[];
$test=function(string $name, callable $fn) use (&$tests){try{$fn();$tests[$name]=['ok'=>true];}catch(Throwable $e){$tests[$name]=['ok'=>false,'error'=>$e->getMessage()];}};

$test('Watcher 2.0', function(){
    $old=['views'=>1,'loves'=>2,'favorites'=>3,'remixes'=>4,'shared'=>false,'latest_comment_id'=>'10'];
    $new=['views'=>2,'loves'=>2,'favorites'=>3,'remixes'=>4,'shared'=>true,'latest_comment_id'=>'11'];
    $d=ProjectWatch::diffStates($old,$new);
    foreach(['views','shared','comments'] as $k) if(!isset($d[$k])) throw new RuntimeException($k);
    $q=new EventQueue();$q->push(['x'=>1]);if(count($q)!==1||count($q->drain())!==1||count($q)!==0)throw new RuntimeException('queue');
});
$test('Cache 2.0', function(){
    $m=new Metrics();$c=(new ManagedCache(new MemoryCache(),$m))->rules(['project:'=>7]);$c->set('project:1',['ok'=>1],99);if(!$c->has('project:1'))throw new RuntimeException('cache');$c->get('project:1');$s=$m->summary();if($s['cache_hits']<1)throw new RuntimeException('metrics');
});
$test('Batch 2.0 surface', function(){
    $b=(new Scratch())->batch()->project(104)->concurrency(3)->timeout(9)->retries(2)->onProgress(fn()=>null);if(!method_exists($b,'failures')||!method_exists($b,'lastResults'))throw new RuntimeException('surface');
});
$test('Request Metrics', function(){ $m=(new Scratch())->metrics()->summary();foreach(['requests','avg_ms','cache_hits','retries'] as $k)if(!array_key_exists($k,$m))throw new RuntimeException($k); });
$test('Retry Policy / Circuit Breaker', function(){ $p=(new RetryPolicy())->maxAttempts(4)->backoff('exponential')->baseDelayMs(100)->retryOn([429,503]);if($p->delayMs(3)!==400)throw new RuntimeException('backoff');$c=(new CircuitBreaker(2,30));$c->failure();if(!$c->allow())throw new RuntimeException('early');$c->failure();if($c->allow())throw new RuntimeException('not open'); });
$test('Doctor 2.0', function(){ $h=(new Scratch())->healthCheck(false);foreach(['temp_writable','memory_limit','max_execution_time','metrics','circuit'] as $k)if(!array_key_exists($k,$h))throw new RuntimeException($k); });
$test('SB3 Validator', function(){ $f=tempnam(sys_get_temp_dir(),'sbp');file_put_contents($f,json_encode(['targets'=>[['name'=>'Stage','blocks'=>[],'costumes'=>[],'sounds'=>[]]]]));$r=(new Sb3Validator())->validate($f);@unlink($f);if(!$r['ok']||$r['stats']['targets']!==1)throw new RuntimeException('validator'); });
$test('Scratch API Wizard', function(){ $w=(new Scratch())->wizard();$html=$w->render(['tailwind_cdn'=>false]);foreach(['sbpw-modal','sbpw-drag','sbpw-code','ScratchByPHP Control Center','resize:both'] as $needle)if(!str_contains($html,$needle))throw new RuntimeException($needle);$rm=new ReflectionMethod($w,'clientActions');$rm->setAccessible(true);$ids=array_column($rm->invoke($w),'id');foreach(['cloud.requests_once','watcher.baseline','project.love','studio.add_project'] as $id)if(!in_array($id,$ids,true))throw new RuntimeException($id); });

$passed=count(array_filter($tests,fn($x)=>$x['ok']));$out=['ok'=>$passed===count($tests),'passed'=>$passed,'total'=>count($tests),'tests'=>$tests];echo json_encode($out,JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES)."\n";exit($out['ok']?0:1);
