<?php
namespace ScratchByPHP\Cloud;

final class CloudDatabase {
    public function __construct(private CloudConnection $cloud, private string $variable='db') {}
    private function readMap(): array {
        $raw=$this->cloud->getRemote($this->variable,2.0); if (!$raw) return [];
        try { $json=CloudCodec::decode($raw); $map=json_decode($json,true); return is_array($map)?$map:[]; } catch(\Throwable) { return []; }
    }
    private function writeMap(array $map): array {
        $encoded=CloudCodec::encode(json_encode($map,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES));
        if (strlen($encoded)>256) throw new \RuntimeException('CloudDatabase verisi 256 basamak sınırını aşıyor. Daha küçük veri kullan.');
        return $this->cloud->setVerified($this->variable,$encoded,5.0);
    }
    public function all(): array { return $this->readMap(); }
    public function get(string $key,mixed $default=null): mixed { $m=$this->readMap(); return $m[$key]??$default; }
    public function set(string $key,mixed $value): array { $m=$this->readMap(); $m[$key]=$value; return $this->writeMap($m); }
    public function delete(string $key): array { $m=$this->readMap(); unset($m[$key]); return $this->writeMap($m); }
}
