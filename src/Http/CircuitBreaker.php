<?php
namespace ScratchByPHP\Http;

final class CircuitBreaker
{
    private int $failures=0;
    private ?float $openedAt=null;
    public function __construct(private int $threshold=5, private int $cooldownSeconds=30) {}
    public function threshold(int $value): self {$this->threshold=max(1,$value);return $this;}
    public function cooldown(int $seconds): self {$this->cooldownSeconds=max(1,$seconds);return $this;}
    public function allow(): bool { if($this->openedAt===null)return true; if(microtime(true)-$this->openedAt >= $this->cooldownSeconds){$this->reset();return true;} return false; }
    public function success(): void {$this->reset();}
    public function failure(): void { $this->failures++; if($this->failures >= $this->threshold && $this->openedAt===null)$this->openedAt=microtime(true); }
    public function reset(): void {$this->failures=0;$this->openedAt=null;}
    public function state(): array { return ['open'=>$this->openedAt!==null&&!$this->allow(),'failures'=>$this->failures,'threshold'=>$this->threshold,'cooldown_seconds'=>$this->cooldownSeconds]; }
}
