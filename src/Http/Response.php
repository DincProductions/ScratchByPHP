<?php
namespace ScratchByPHP\Http;
final class Response {
    public function __construct(
        public readonly int $status,
        public readonly string $body,
        public readonly array $headers = []
    ) {}
    public function json(bool $assoc = true): array {
        $data = json_decode($this->body, $assoc);
        return is_array($data) ? $data : [];
    }
}
