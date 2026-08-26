<?php
namespace ScratchByPHP\Support;

final class Logger {
    private const SECRET_KEYS = [
        'password','passwd','secret','token','x-token','x_token','x-csrftoken','x_csrftoken',
        'csrf','csrftoken','session','sessionid','session_id','scratchsessionsid','cookie','authorization',
        'project_token','projecttoken'
    ];

    public function __construct(private ?string $file = null, private bool $enabled = false) {}
    public function enable(?string $file = null): self { $this->enabled = true; if ($file !== null) $this->file = $file; return $this; }
    public function disable(): self { $this->enabled = false; return $this; }

    public function log(string $level, string $message, array $context = []): void {
        if (!$this->enabled) return;

        $line = sprintf("[%s] %s %s", date('c'), strtoupper($level), $message);
        if ($context) {
            $line .= ' ' . json_encode($this->redact($context), JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);
        }
        $line .= PHP_EOL;

        if ($this->file) @file_put_contents($this->file, $line, FILE_APPEND|LOCK_EX);
        else error_log(trim($line));
    }

    private function redact(mixed $value, ?string $key = null): mixed {
        if ($key !== null && $this->isSecretKey($key)) return '[REDACTED]';

        if (is_array($value)) {
            $out = [];
            foreach ($value as $k => $v) $out[$k] = $this->redact($v, is_string($k) ? $k : null);
            return $out;
        }

        if (!is_string($value)) return $value;

        // Redact secrets embedded in URLs, cookies or diagnostic strings.
        $value = preg_replace_callback(
            '/([?&;\\s](?:password|passwd|secret|token|x-token|x_token|x-csrftoken|x_csrftoken|csrf|csrftoken|session|sessionid|session_id|scratchsessionsid|project_token|projecttoken)=)([^&;\\s]+)/i',
            static fn(array $m): string => $m[1] . '[REDACTED]',
            $value
        ) ?? $value;

        $value = preg_replace('/(Authorization:\\s*(?:Bearer|Basic)\\s+)[^\\s]+/i', '$1[REDACTED]', $value) ?? $value;
        return $value;
    }

    private function isSecretKey(string $key): bool {
        $key = strtolower(trim($key));
        return in_array($key, self::SECRET_KEYS, true)
            || str_contains($key, 'password')
            || str_contains($key, 'token')
            || str_contains($key, 'session')
            || str_contains($key, 'cookie')
            || str_contains($key, 'authorization');
    }
}
