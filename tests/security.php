<?php
declare(strict_types=1);

require dirname(__DIR__) . '/autoload.php';

use ScratchByPHP\Http\HttpClient;
use ScratchByPHP\Support\Logger;

// Authenticated client must reject non-Scratch hosts before network I/O.
$client = new HttpClient(['X-Token' => 'secret'], 'scratchsessionsid=secret; scratchcsrftoken=a');
try {
    $client->get('https://example.com/');
    throw new RuntimeException('Host guard çalışmadı.');
} catch (\ScratchByPHP\Exceptions\ApiException $e) {
    if (!str_contains($e->getMessage(), 'authenticated HttpClient')) throw $e;
}

// Logger must redact obvious secrets.
$file = tempnam(sys_get_temp_dir(), 'sbp-log-');
$logger = new Logger($file, true);
$logger->log('debug', 'test', [
    'url' => 'https://scratch.mit.edu/?token=abc123&project=1',
    'session_id' => 'super-secret-session',
    'password' => 'super-secret-password',
]);
$log = file_get_contents($file) ?: '';
@unlink($file);
if (str_contains($log, 'abc123') || str_contains($log, 'super-secret-session') || str_contains($log, 'super-secret-password')) {
    throw new RuntimeException('Logger secret redaction başarısız.');
}

echo "ScratchByPHP security smoke test OK\n";
