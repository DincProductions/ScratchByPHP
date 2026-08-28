<?php
namespace ScratchByPHP\Batch;
use ScratchByPHP\Scratch;
final class BatchBuilder
{
    private array $requests=[];private int $concurrency=8,$timeout=25,$retries=1;private $progress=null;private array $lastResults=[];
    public function __construct(private Scratch $scratch){}
    public function project(int|string $id,?string $key=null):self{return $this->add($key??'project:'.$id,'https://api.scratch.mit.edu/projects/'.rawurlencode((string)$id));}
    public function user(string $u,?string $key=null):self{return $this->add($key??'user:'.$u,'https://api.scratch.mit.edu/users/'.rawurlencode($u));}
    public function studio(int|string $id,?string $key=null):self{return $this->add($key??'studio:'.$id,'https://api.scratch.mit.edu/studios/'.rawurlencode((string)$id));}
    public function raw(string $key,string $url,array $options=[]):self{$c=clone $this;$c->requests[$key]=['url'=>$url]+$options;return $c;}
    private function add(string $key,string $url):self{return $this->raw($key,$url);}
    public function concurrency(int $n):self{$c=clone $this;$c->concurrency=max(1,$n);return $c;} public function timeout(int $s):self{$c=clone $this;$c->timeout=max(1,$s);return $c;} public function retries(int $n):self{$c=clone $this;$c->retries=max(0,$n);return $c;} public function onProgress(callable $cb):self{$c=clone $this;$c->progress=$cb;return $c;}
    public function run():array{$this->lastResults=(new ParallelClient())->run($this->requests,$this->timeout,$this->concurrency,$this->retries,$this->progress);return $this->lastResults;} public function failures():array{return array_filter($this->lastResults,fn($r)=>empty($r['ok']));} public function lastResults():array{return $this->lastResults;}
}
