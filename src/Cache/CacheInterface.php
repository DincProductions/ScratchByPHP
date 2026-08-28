<?php
namespace ScratchByPHP\Cache;
interface CacheInterface { public function get(string $key,mixed $default=null):mixed; public function set(string $key,mixed $value,int $ttl=60):bool; public function delete(string $key):bool; public function clear():bool; public function has(string $key):bool; }
