<?php
namespace ScratchByPHP\Http;

final class RetryPolicy
{
    private int $maxAttempts = 2;
    private string $backoff = 'linear';
    private int $baseDelayMs = 180;
    private array $retryStatuses = [429,500,502,503,504];

    public function maxAttempts(int $attempts): self { $this->maxAttempts=max(1,$attempts); return $this; }
    public function backoff(string $mode): self { if(!in_array($mode,['fixed','linear','exponential'],true)) throw new \InvalidArgumentException('backoff: fixed, linear veya exponential olmalı.'); $this->backoff=$mode; return $this; }
    public function baseDelayMs(int $ms): self { $this->baseDelayMs=max(0,$ms); return $this; }
    public function retryOn(array $statuses): self { $this->retryStatuses=array_values(array_unique(array_map('intval',$statuses))); return $this; }
    public function attempts(): int { return $this->maxAttempts; }
    public function shouldRetryStatus(int $status): bool { return in_array($status,$this->retryStatuses,true); }
    public function delayMs(int $attempt, ?int $retryAfterSeconds=null): int
    {
        if($retryAfterSeconds!==null) return max(0,$retryAfterSeconds*1000);
        return match($this->backoff){
            'fixed'=>$this->baseDelayMs,
            'exponential'=>$this->baseDelayMs * (2 ** max(0,$attempt-1)),
            default=>$this->baseDelayMs * max(1,$attempt),
        };
    }
    public function toArray(): array { return ['max_attempts'=>$this->maxAttempts,'backoff'=>$this->backoff,'base_delay_ms'=>$this->baseDelayMs,'retry_statuses'=>$this->retryStatuses]; }
}
