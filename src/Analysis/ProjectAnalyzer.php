<?php
namespace ScratchByPHP\Analysis;

final class ProjectAnalyzer {
    public function __construct(private array $json) {}
    public static function fromJson(string|array $json): self {
        if (is_string($json)) {
            $decoded = json_decode($json, true);
            if (!is_array($decoded)) throw new \InvalidArgumentException('Geçersiz Scratch project JSON.');
            return new self($decoded);
        }
        return new self($json);
    }
    public function raw(): array { return $this->json; }
    public function spriteCount(bool $includeStage = false): int {
        $targets = $this->json['targets'] ?? [];
        if ($includeStage) return count($targets);
        return count(array_filter($targets, fn($t) => empty($t['isStage'])));
    }
    public function blockCount(): int {
        $n = 0; foreach ($this->json['targets'] ?? [] as $t) $n += count($t['blocks'] ?? []); return $n;
    }
    public function costumeCount(): int {
        $n = 0; foreach ($this->json['targets'] ?? [] as $t) $n += count($t['costumes'] ?? []); return $n;
    }
    public function soundCount(): int {
        $n = 0; foreach ($this->json['targets'] ?? [] as $t) $n += count($t['sounds'] ?? []); return $n;
    }
    public function variables(): array {
        $out=[]; foreach ($this->json['targets'] ?? [] as $t) foreach ($t['variables'] ?? [] as $id=>$v) $out[$id]=$v; return $out;
    }
    public function cloudVariables(): array {
        return array_filter($this->variables(), fn($v) => is_array($v) && (($v[2] ?? false) === true));
    }
    public function lists(): array {
        $out=[]; foreach ($this->json['targets'] ?? [] as $t) foreach ($t['lists'] ?? [] as $id=>$v) $out[$id]=$v; return $out;
    }
    public function broadcasts(): array {
        $out=[]; foreach ($this->json['targets'] ?? [] as $t) foreach ($t['broadcasts'] ?? [] as $id=>$v) $out[$id]=$v; return $out;
    }
    public function extensions(): array { return array_values(array_unique($this->json['extensions'] ?? [])); }
    public function targets(): array { return $this->json['targets'] ?? []; }
    public function summary(): array {
        return [
            'sprites'=>$this->spriteCount(), 'targets'=>$this->spriteCount(true), 'blocks'=>$this->blockCount(),
            'costumes'=>$this->costumeCount(), 'sounds'=>$this->soundCount(), 'variables'=>count($this->variables()),
            'cloud_variables'=>count($this->cloudVariables()), 'lists'=>count($this->lists()),
            'broadcasts'=>count($this->broadcasts()), 'extensions'=>$this->extensions(),
        ];
    }
}
