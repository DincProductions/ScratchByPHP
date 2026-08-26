<?php
namespace ScratchByPHP\Cloud;
final class CloudEvents {
    private array $listeners = [];
    public function on(string $event, callable $listener): void { $this->listeners[$event][] = $listener; }
    public function emit(string $event, mixed $payload): void {
        foreach ($this->listeners[$event] ?? [] as $listener) $listener($payload);
    }
}
