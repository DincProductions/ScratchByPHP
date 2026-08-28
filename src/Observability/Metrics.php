<?php
namespace ScratchByPHP\Observability;

final class Metrics
{
    private int $requests = 0;
    private int $success = 0;
    private int $failed = 0;
    private float $totalMs = 0.0;
    private array $statuses = [];
    private int $cacheHits = 0;
    private int $cacheMisses = 0;
    private int $retries = 0;

    public function recordRequest(int $status, float $ms): void
    {
        $this->requests++;
        $this->totalMs += max(0, $ms);
        $this->statuses[$status] = ($this->statuses[$status] ?? 0) + 1;
        if ($status >= 200 && $status < 400) $this->success++; else $this->failed++;
    }
    public function recordFailure(float $ms = 0): void { $this->requests++; $this->failed++; $this->totalMs += max(0,$ms); }
    public function cacheHit(): void { $this->cacheHits++; }
    public function cacheMiss(): void { $this->cacheMisses++; }
    public function retry(): void { $this->retries++; }
    public function reset(): self { $this->requests=$this->success=$this->failed=$this->cacheHits=$this->cacheMisses=$this->retries=0; $this->totalMs=0; $this->statuses=[]; return $this; }
    public function summary(): array
    {
        return [
            'requests'=>$this->requests,
            'success'=>$this->success,
            'failed'=>$this->failed,
            'avg_ms'=>$this->requests ? round($this->totalMs/$this->requests,2) : 0.0,
            'statuses'=>$this->statuses,
            'cache_hits'=>$this->cacheHits,
            'cache_misses'=>$this->cacheMisses,
            'retries'=>$this->retries,
        ];
    }
}
