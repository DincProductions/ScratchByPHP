<?php
namespace ScratchByPHP\Support;

final class Logger {
    public function __construct(private ?string $file = null, private bool $enabled = false) {}
    public function enable(?string $file = null): self { $this->enabled = true; if ($file !== null) $this->file = $file; return $this; }
    public function disable(): self { $this->enabled = false; return $this; }
    public function log(string $level, string $message, array $context = []): void {
        if (!$this->enabled) return;
        $line = sprintf("[%s] %s %s", date('c'), strtoupper($level), $message);
        if ($context) $line .= ' ' . json_encode($context, JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);
        $line .= PHP_EOL;
        if ($this->file) @file_put_contents($this->file, $line, FILE_APPEND|LOCK_EX);
        else error_log(trim($line));
    }
}
