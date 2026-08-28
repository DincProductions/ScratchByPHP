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

    public function sprites(): array { return array_values(array_filter($this->targets(),fn($t)=>empty($t['isStage']))); }
    public function blocksByOpcode(): array { $out=[];foreach($this->targets() as $t)foreach($t['blocks']??[] as $b)if(is_array($b)&&isset($b['opcode']))$out[$b['opcode']][]=$b;return $out; }
    public function opcodeCounts(): array { return array_map('count',$this->blocksByOpcode()); }
    public function unusedVariables(): array { $vars=$this->variables();$used=[];foreach($this->targets() as $t)foreach($t['blocks']??[] as $b){if(!is_array($b))continue;foreach($b['fields']??[] as $f)if(is_array($f)&&isset($f[1])&&is_string($f[1]))$used[$f[1]]=true;}return array_filter($vars,fn($v,$id)=>!isset($used[$id]),ARRAY_FILTER_USE_BOTH); }
    public function duplicateScripts(): array { $dups=[];foreach($this->targets() as $t){$sig=[];foreach($t['blocks']??[] as $id=>$b){if(!is_array($b)||empty($b['topLevel']))continue;$key=($b['opcode']??'').'|'.json_encode($b['inputs']??[]).'|'.json_encode($b['fields']??[]);$sig[$key][]=$id;}foreach($sig as $ids)if(count($ids)>1)$dups[]=['target'=>$t['name']??'','blocks'=>$ids];}return $dups; }
    public function broadcastGraph(): array { $graph=[];foreach($this->targets() as $t){$name=$t['name']??'target';foreach($t['blocks']??[] as $b){if(!is_array($b))continue;$op=$b['opcode']??'';if(in_array($op,['event_broadcast','event_broadcastandwait','event_whenbroadcastreceived'],true))$graph[$name][]=['opcode'=>$op,'fields'=>$b['fields']??[],'inputs'=>$b['inputs']??[]];}}return $graph; }
    public function extensionUsage(): array { return $this->extensions(); }
    public function complexityScore(): int { return $this->blockCount()+($this->spriteCount()*10)+(count($this->broadcasts())*3)+(count($this->extensions())*8); }
    public function warnings(): array { $w=[];$u=$this->unusedVariables();if($u)$w[]=count($u).' unused variable(s) detected.';$d=$this->duplicateScripts();if($d)$w[]=count($d).' duplicate top-level script pattern(s) detected.';if($this->blockCount()>5000)$w[]='Large block count: project may be expensive to analyze/run.';return $w; }

}
