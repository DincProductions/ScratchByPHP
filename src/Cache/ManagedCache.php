<?php
namespace ScratchByPHP\Cache;
use ScratchByPHP\Observability\Metrics;
final class ManagedCache implements CacheInterface
{
    private array $rules=[];
    public function __construct(private CacheInterface $store, private ?Metrics $metrics=null){}
    public function rules(array $rules):self{$this->rules=array_replace($this->rules,$rules);return $this;}
    public function ttlFor(string $key,int $fallback=60):int{foreach($this->rules as $prefix=>$ttl)if(str_starts_with($key,rtrim((string)$prefix,'*')))return max(1,(int)$ttl);return max(1,$fallback);}
    public function get(string $key,mixed $default=null):mixed{$exists=$this->store->has($key);$exists?$this->metrics?->cacheHit():$this->metrics?->cacheMiss();return $exists?$this->store->get($key,$default):$default;}
    public function set(string $key,mixed $value,int $ttl=60):bool{return $this->store->set($key,$value,$this->ttlFor($key,$ttl));}
    public function delete(string $key):bool{return $this->store->delete($key);} public function clear():bool{return $this->store->clear();} public function has(string $key):bool{$ok=$this->store->has($key);$ok?$this->metrics?->cacheHit():$this->metrics?->cacheMiss();return $ok;}
    public function inner():CacheInterface{return $this->store;}
}
