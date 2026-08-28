<?php
declare(strict_types=1);
require dirname(__DIR__).'/autoload.php';

use ScratchByPHP\Cloud\CloudDatabase;
use ScratchByPHP\Trending\TurkishTrending;
use ScratchByPHP\Scratch;

$plan=CloudDatabase::planToDB(
    ['score'=>500,'level'=>12,'profile'=>['rank'=>'gold']],
    [
        'table'=>'scratch_cloud',
        'key_column'=>'cloud_key',
        'value_column'=>'cloud_value',
        'updated_at_column'=>'updated_at',
        'upsert'=>true,
    ]
);

$demo=[
    [
        'id'=>1001,
        'title'=>'Büyük ama eski proje',
        'description'=>'Demo #TürkçeTrend',
        'stats'=>['views'=>120000,'loves'=>6000,'favorites'=>3000],
        'history'=>['shared'=>'2024-01-01T00:00:00Z'],
    ],
    [
        'id'=>1002,
        'title'=>'Yeni yükselen proje',
        'description'=>'#TürkçeTrend yeni demo',
        'stats'=>['views'=>15000,'loves'=>2200,'favorites'=>1200],
        'history'=>['shared'=>gmdate('c',time()-86400)],
    ],
];

$ranked=TurkishTrending::rank($demo);
?>
<!doctype html>
<html lang="tr">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>ScratchByPHP v0.8.5 Test</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">
<style>body{background:#f7f5fb}.card{border:0;box-shadow:0 .4rem 1.3rem #6f42c114}pre{background:#17131d;color:#f8f5ff;border-radius:12px;padding:15px;white-space:pre-wrap}</style>
</head>
<body>
<div class="container py-4">
<h1 class="h3 mb-1">ScratchByPHP v0.8.5</h1>
<p class="text-secondary">CloudDB Pro + Türkçe Trend local test</p>

<div class="row g-4">
<div class="col-lg-6"><div class="card"><div class="card-body">
<h2 class="h5">CloudDB Pro SQL Plan</h2>
<p class="small text-secondary">DB bağlantısı açmadan prepared statement planını test eder.</p>
<pre><?=htmlspecialchars(json_encode($plan,JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES))?></pre>
</div></div></div>

<div class="col-lg-6"><div class="card"><div class="card-body">
<h2 class="h5">Türkçe Trend Algorithm</h2>
<p class="small text-secondary">views + loves + favorites + paylaşım tarihi puanlaması.</p>
<pre><?=htmlspecialchars(json_encode($ranked,JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES))?></pre>
</div></div></div>
</div>

<div class="alert alert-info mt-4">
Canlı Türkçe Trend için <code>$scratch->turkishTrending()</code>; canlı MySQL aktarımı için
<code>$cloud->database('db')->getToDB(...)</code> kullan.
</div>
</div>
</body>
</html>
