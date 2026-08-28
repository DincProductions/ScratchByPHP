<?php
namespace ScratchByPHP\Watch;

use ScratchByPHP\Scratch;

final class Watcher
{
    private array $projects = [];
    private float $interval = 15.0;

    public function __construct(private Scratch $scratch) {}

    public function interval(float $seconds): self
    {
        $this->interval = max(1.0, $seconds);
        return $this;
    }

    public function project(int|string $id): ProjectWatch
    {
        $watch = new ProjectWatch($this->scratch, (string)$id, $this->interval);
        $this->projects[] = $watch;
        return $watch;
    }
}
