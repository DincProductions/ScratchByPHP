<?php declare(strict_types=1); require dirname(__DIR__).'/autoload.php'; ?>
<!doctype html><html lang="tr"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Integration Smoke Tests</title><link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet"><style>body{background:#f6f7fb;color:#172033}.card{border:0;box-shadow:0 .3rem 1rem rgba(0,0,0,.06)}pre{background:#111827;color:#e5e7eb;padding:14px;border-radius:10px;white-space:pre-wrap}.ok{color:#198754}.bad{color:#dc3545}.badge-live{background:#fff3cd;color:#7a5b00}</style></head><body><div class="container py-4"><div class="d-flex justify-content-between align-items-center mb-4"><div><h1 class="h3 mb-1">Integration Smoke Tests</h1><div class="text-secondary">ScratchByPHP v0.8.5 test paneli</div></div><a href="index.php" class="btn btn-outline-secondary">← Paneller</a></div><?php use ScratchByPHP\Scratch;
function redactIntegration(mixed $value): mixed {
    if (is_array($value)) {
        foreach ($value as $k => $v) {
            if (is_string($k) && in_array(strtolower($k), ['project_token','token','x-token','sessionid','session_id','password'], true)) {
                $value[$k] = '[REDACTED]';
            } else {
                $value[$k] = redactIntegration($v);
            }
        }
        return $value;
    }
    if (is_string($value)) {
        return preg_replace('/("project_token"\s*:\s*")[^"]+(")/i', '$1[REDACTED]$2', $value);
    }
    return $value;
}
$out=null;$err=null;if(isset($_GET['run'])){try{$s=new Scratch(['timeout'=>15]);$out=redactIntegration(['health'=>$s->healthCheck(true),'search_count'=>count($s->searchProjects('platformer','trending','en',3,0)),'parallel'=>$s->parallel(['project'=>['url'=>'https://api.scratch.mit.edu/projects/104'],'news'=>['url'=>'https://api.scratch.mit.edu/news?limit=1']])]);}catch(Throwable $e){$err=$e->getMessage();}}?><div class="card"><div class="card-body"><p>Bu test gerçek Scratch API'sine bağlanır; herhangi bir hesaba giriş yapmaz ve yazma işlemi gerçekleştirmez.</p><a class="btn btn-primary" href="?run=1">LIVE Integration Test</a><?php if($err):?><div class="alert alert-danger mt-3"><?=htmlspecialchars($err)?></div><?php elseif($out):?><pre class="mt-3"><?=htmlspecialchars(json_encode($out,JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE))?></pre><?php endif;?></div></div></div></body></html>