<?php
namespace ScratchByPHP\Cache;
final class Psr16CacheAdapter implements CacheInterface { public function __construct(private object $cache){} public function get(string $k,mixed $d=null):mixed{return $this->cache->get($k,$d);} public function set(string $k,mixed $v,int $ttl=60):bool{return (bool)$this->cache->set($k,$v,$ttl);} public function delete(string $k):bool{return (bool)$this->cache->delete($k);} public function clear():bool{return (bool)$this->cache->clear();} public function has(string $k):bool{return (bool)$this->cache->has($k);} }
