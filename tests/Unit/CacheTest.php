<?php
use PHPUnit\Framework\TestCase; use ScratchByPHP\Cache\MemoryCache;
final class CacheTest extends TestCase { public function testMemoryCache():void{$c=new MemoryCache();$this->assertTrue($c->set('x',42,60));$this->assertSame(42,$c->get('x'));$this->assertTrue($c->has('x'));$c->delete('x');$this->assertFalse($c->has('x'));} }
