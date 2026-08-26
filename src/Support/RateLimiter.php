<?php
namespace ScratchByPHP\Support;

final class RateLimiter {
    private static array $last = [];
    public static function wait(string $key, float $minIntervalSeconds): void {
        $now = microtime(true);
        $last = self::$last[$key] ?? 0.0;
        $delta = $now - $last;
        if ($delta < $minIntervalSeconds) usleep((int)(($minIntervalSeconds - $delta) * 1_000_000));
        self::$last[$key] = microtime(true);
    }
}
