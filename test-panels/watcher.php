<?php
declare(strict_types=1);

require dirname(__DIR__) . '/autoload.php';

use ScratchByPHP\Scratch;

set_time_limit(0);

$projectId = trim((string)($_GET['project'] ?? '104'));
$duration = max(5, min(300, (int)($_GET['duration'] ?? 60)));
$interval = max(2, min(30, (int)($_GET['interval'] ?? 5)));

$out = null;
$err = null;

if (isset($_GET['run'])) {
    try {
        if (!ctype_digit($projectId)) {
            throw new RuntimeException('Geçerli proje ID gir.');
        }

        $scratch = new Scratch([
            'cache_ttl' => 60,
            'timeout' => 15,
        ]);

        $watch = $scratch->watch()
            ->interval($interval)
            ->project($projectId);

        $events = [];

        $watch->onChange(function ($field, $new, $old) use (&$events) {
            $events[] = [
                'field' => $field,
                'before' => $old,
                'after' => $new,
                'detected_at' => gmdate('c'),
            ];
        });

        $baseline = $watch->baseline();

        $ticks = [[
            'tick' => 0,
            'type' => 'baseline',
            'state' => $baseline,
            'changes' => [],
        ]];

        $cycles = max(1, (int)ceil($duration / $interval));

        for ($i = 1; $i <= $cycles; $i++) {
            sleep($interval);

            $changes = $watch->tick();

            $ticks[] = [
                'tick' => $i,
                'state' => $watch->lastState(),
                'changes' => $changes,
            ];
        }

        $out = [
            'project_id' => $projectId,
            'watch_seconds' => $duration,
            'interval_seconds' => $interval,
            'note' => 'Her tick fresh Project::refresh() kullanır; normal 60sn project cache watcher verisini gizlemez.',
            'ticks' => $ticks,
            'events' => $events,
        ];
    } catch (Throwable $e) {
        $err = $e->getMessage();
    }
}
?>
<!doctype html>
<html lang="tr">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Watcher LIVE Test — v0.8.5</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">
<style>
body{background:#f6f7fb}
.card{border:0;box-shadow:0 .3rem 1rem rgba(0,0,0,.06)}
pre{background:#111827;color:#e5e7eb;padding:14px;border-radius:10px;white-space:pre-wrap;max-height:70vh}
</style>
</head>
<body>
<div class="container py-4">
<a href="index.php" class="btn btn-sm btn-outline-secondary mb-3">← Test Merkezi</a>

<h1 class="h3">Watcher LIVE Test <span class="badge text-bg-success">v0.8.5</span></h1>

<div class="alert alert-info">
<strong>Düzeltildi:</strong>
önceki watcher <code>Project::get()</code> üzerinden varsayılan 60 saniyelik cache'i okuyordu.
Ayrıca Scratch proje JSON'unda <code>stats.comments</code> bulunmadığı için yorum değişikliği
algılanamıyordu. Artık proje state'i fresh çekiliyor ve son yorum ID'si comments endpoint'inden izleniyor.
</div>

<div class="card">
<div class="card-body">

<form class="row g-3 align-items-end">
    <div class="col-md-4">
        <label class="form-label">Project ID</label>
        <input name="project" class="form-control" value="<?=htmlspecialchars($projectId)?>">
    </div>

    <div class="col-md-3">
        <label class="form-label">İzleme süresi</label>
        <select name="duration" class="form-select">
            <?php foreach ([15,30,60,120,300] as $v): ?>
            <option value="<?=$v?>" <?=$duration===$v?'selected':''?>><?=$v?> saniye</option>
            <?php endforeach; ?>
        </select>
    </div>

    <div class="col-md-3">
        <label class="form-label">Tick aralığı</label>
        <select name="interval" class="form-select">
            <?php foreach ([2,5,10,15,30] as $v): ?>
            <option value="<?=$v?>" <?=$interval===$v?'selected':''?>><?=$v?> saniye</option>
            <?php endforeach; ?>
        </select>
    </div>

    <div class="col-md-2 d-grid">
        <button name="run" value="1" class="btn btn-primary">LIVE İzle</button>
    </div>
</form>

<p class="text-secondary mt-3 mb-0">
Test çalışırken Scratch'te hedef projeye yeni yorum atabilir veya views/loves/favorites/remixes
değerlerinden birinin doğal olarak değişmesini bekleyebilirsin. Sonuç süre bitince gösterilir.
</p>

<?php if ($err): ?>
<div class="alert alert-danger mt-3"><?=htmlspecialchars($err)?></div>
<?php elseif ($out): ?>
<pre class="mt-3"><?=htmlspecialchars(json_encode($out, JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES))?></pre>
<?php endif; ?>

</div>
</div>
</div>
</body>
</html>
