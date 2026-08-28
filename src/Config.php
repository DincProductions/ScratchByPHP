<?php
namespace ScratchByPHP;

final class Config {
    private array $values;
    public function __construct(array $values = []) {
        $this->values = array_replace([
            'timeout' => 25,
            'connect_timeout' => 10,
            'retries' => 1,
            'retry_delay_ms' => 180,
            'language' => 'en',
            'user_agent' => 'ScratchByPHP/0.8.5 (+https://www.blocklandin.com/scratchbyphp/)',
            'cache_ttl' => 60,
            'cache_ttl_project' => 60,
            'cache_ttl_user' => 120,
            'cache_ttl_studio' => 60,
            'retry_attempts' => 2,
            'circuit_threshold' => 5,
            'circuit_cooldown' => 30,
            'cache_driver' => 'memory',
        ], $values);
    }
    public function get(string $key, mixed $default=null): mixed { return $this->values[$key] ?? $default; }
    public function set(string $key, mixed $value): self { $this->values[$key]=$value; return $this; }
    public function all(): array { return $this->values; }
    public function toArray(): array { return $this->values; }
    public function toJson(int $flags=JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES): string { return (string)json_encode($this->values,$flags); }
}
