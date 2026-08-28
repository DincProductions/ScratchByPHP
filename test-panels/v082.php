<?php
declare(strict_types=1);
require dirname(__DIR__).'/autoload.php';
use ScratchByPHP\Scratch;
use ScratchByPHP\Cache\MemoryCache;
use ScratchByPHP\Cache\ManagedCache;
use ScratchByPHP\Observability\Metrics;
use ScratchByPHP\Watch\ProjectWatch;
use ScratchByPHP\Watch\EventQueue;
use ScratchByPHP\Http\RetryPolicy;
use ScratchByPHP\Http\CircuitBreaker;
use ScratchByPHP\Sb3\Sb3Validator;

function sbpCheck(string $name, callable $fn): array { $t=microtime(true); try{$detail=$fn();return ['name'=>$name,'status'=>'PASS','ms'=>round((microtime(true)-$t)*1000,2),'detail'=>$detail];}catch(Throwable $e){return ['name'=>$name,'status'=>'FAIL','ms'=>round((microtime(true)-$t)*1000,2),'error'=>$e->getMessage()];}}
$scratch=new Scratch();
$checks=[];
$checks[]=sbpCheck('Core / Version',fn()=>['version'=>Scratch::VERSION,'php'=>PHP_VERSION]);
$checks[]=sbpCheck('Watcher 2.0',function(){ $d=ProjectWatch::diffStates(['views'=>1,'shared'=>false,'latest_comment_id'=>'1'],['views'=>2,'shared'=>true,'latest_comment_id'=>'2']); if(!isset($d['views'],$d['shared'],$d['comments']))throw new RuntimeException('diff eksik');$q=new EventQueue();$q->push(['field'=>'views']);return ['changes'=>array_keys($d),'queue'=>count($q)];});
$checks[]=sbpCheck('Cache 2.0',function(){ $m=new Metrics();$c=(new ManagedCache(new MemoryCache(),$m))->rules(['project:'=>10]);$c->set('project:104',['ok'=>1],60);$c->has('project:104');return $m->summary();});
$checks[]=sbpCheck('Batch 2.0 surface',function()use($scratch){$b=$scratch->batch()->project(104)->concurrency(4)->timeout(10)->retries(2)->onProgress(fn()=>null);return ['concurrency'=>4,'timeout'=>10,'retries'=>2,'methods'=>['failures'=>method_exists($b,'failures'),'lastResults'=>method_exists($b,'lastResults')]];});
$checks[]=sbpCheck('Retry / Circuit',function(){ $p=(new RetryPolicy())->maxAttempts(3)->backoff('exponential')->baseDelayMs(100);$c=new CircuitBreaker(2,10);$c->failure();return ['retry'=>$p->toArray(),'circuit'=>$c->state()];});
$checks[]=sbpCheck('Doctor 2.0',fn()=>$scratch->healthCheck(false));
$checks[]=sbpCheck('SB3 Validator',function(){ $f=tempnam(sys_get_temp_dir(),'sbp');file_put_contents($f,json_encode(['targets'=>[['name'=>'Stage','blocks'=>[],'costumes'=>[],'sounds'=>[]]]]));$r=(new Sb3Validator())->validate($f);@unlink($f);return $r;});
$checks[]=sbpCheck('Scratch API Wizard',function()use($scratch){$h=$scratch->wizard()->render(['tailwind_cdn'=>false]);if(!str_contains($h,'sbpw-modal'))throw new RuntimeException('modal yok');return ['render_bytes'=>strlen($h),'movable'=>str_contains($h,'sbpw-drag')];});

$checks[]=sbpCheck('CloudDB Pro / MySQL Plan',function(){
    $plan=\ScratchByPHP\Cloud\CloudDatabase::planToDB(['score'=>500,'level'=>12],[
        'table'=>'scratch_cloud','key_column'=>'cloud_key','value_column'=>'cloud_value','updated_at_column'=>'updated_at','upsert'=>true
    ]);
    if(($plan['row_count']??0)!==2)throw new RuntimeException('CloudDB plan row count');
    return ['rows'=>$plan['row_count'],'sql'=>$plan['sql']];
});
$checks[]=sbpCheck('Türkçe Trend / Turkish Studio Ranking',function(){
    $rows=[
        ['id'=>1,'title'=>'A','stats'=>['views'=>1000,'loves'=>20,'favorites'=>8],'history'=>['shared'=>gmdate('c',time()-86400)]],
        ['id'=>2,'title'=>'B','stats'=>['views'=>5000,'loves'=>50,'favorites'=>10],'history'=>['shared'=>'2024-01-01T00:00:00Z']],
    ];
    $ranked=\ScratchByPHP\Trending\TurkishTrending::rank($rows);
    return ['count'=>count($ranked),'top_score'=>$ranked[0]['turkish_trend']['score']??null];
});
$checks[]=sbpCheck('ProjectDiff compatibility',function(){
    $d=new \ScratchByPHP\Analysis\ProjectDiff(['targets'=>[]],['targets'=>[]]);
    if($d->summary()!==$d->toArray())throw new RuntimeException('summary/toArray mismatch');
    return $d->summary();
});

$out=['version'=>Scratch::VERSION,'summary'=>['pass'=>count(array_filter($checks,fn($c)=>$c['status']==='PASS')),'fail'=>count(array_filter($checks,fn($c)=>$c['status']==='FAIL'))],'checks'=>$checks];
if(($_GET['format']??'')==='json'){header('Content-Type: application/json; charset=utf-8');echo json_encode($out,JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);exit;}
?><!doctype html><html lang="tr"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>v0.8.5 Test Panel 2.0</title><link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet"><style>body{background:#f6f7fb}.card{border:0;box-shadow:0 .25rem 1rem #00000010}.metric{font-size:2rem;font-weight:750}</style></head><body><div class="container py-4"><div class="d-flex justify-content-between align-items-center"><div><a href="index.php" class="text-decoration-none">← Test Merkezi</a><h1 class="h3 mt-2">Test Panel 2.0 <span class="badge text-bg-primary">v0.8.5</span></h1></div><a class="btn btn-outline-dark" href="?format=json">JSON Export</a></div><div class="row g-3 my-2"><div class="col-6 col-md-3"><div class="card p-3"><div class="text-secondary">PASS</div><div class="metric text-success"><?=$out['summary']['pass']?></div></div></div><div class="col-6 col-md-3"><div class="card p-3"><div class="text-secondary">FAIL</div><div class="metric text-danger"><?=$out['summary']['fail']?></div></div></div><div class="col-6 col-md-3"><div class="card p-3"><div class="text-secondary">PHP</div><div class="metric"><?=htmlspecialchars(PHP_VERSION)?></div></div></div><div class="col-6 col-md-3"><div class="card p-3"><div class="text-secondary">Sürüm</div><div class="metric"><?=htmlspecialchars(Scratch::VERSION)?></div></div></div></div><div class="row g-3"><?php foreach($checks as $c):?><div class="col-lg-6"><div class="card h-100"><div class="card-body"><div class="d-flex justify-content-between"><h2 class="h5"><?=htmlspecialchars($c['name'])?></h2><span class="badge <?=$c['status']==='PASS'?'text-bg-success':'text-bg-danger'?>"><?=$c['status']?></span></div><div class="small text-secondary mb-2"><?=$c['ms']?> ms</div><pre class="small bg-dark text-light p-3 rounded overflow-auto" style="max-height:260px"><?=htmlspecialchars(json_encode($c['detail']??['error'=>$c['error']??''],JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES))?></pre></div></div></div><?php endforeach;?></div><div class="alert alert-info mt-4">Bu sayfa LOCAL testleri çalıştırır. Scratch ağına çıkan LIVE testler için Integration ve Watcher panellerini kullan.</div></div></body></html>
