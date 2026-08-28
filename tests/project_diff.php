<?php
declare(strict_types=1);
require dirname(__DIR__) . '/autoload.php';

use ScratchByPHP\Analysis\ProjectDiff;

$a = [
    'targets' => [
        ['isStage'=>false,'name'=>'Sprite1','blocks'=>[
            'a'=>['opcode'=>'motion_movesteps']
        ]]
    ]
];

$b = [
    'targets' => [
        ['isStage'=>false,'name'=>'Sprite1','blocks'=>[
            'a'=>['opcode'=>'motion_movesteps'],
            'b'=>['opcode'=>'looks_say']
        ]],
        ['isStage'=>false,'name'=>'Sprite2','blocks'=>[]]
    ]
];

$diff = new ProjectDiff($a,$b);

$summary = $diff->summary();
$array = $diff->toArray();

if ($summary !== $array) throw new RuntimeException('summary alias mismatch');
if (($summary['added_sprites'][0] ?? null) !== 'Sprite2') throw new RuntimeException('added sprite missing');
if (($summary['block_delta']['looks_say'] ?? 0) !== 1) throw new RuntimeException('block delta missing');

echo "ScratchByPHP ProjectDiff regression test OK\n";
