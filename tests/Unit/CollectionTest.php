<?php
use PHPUnit\Framework\TestCase; use ScratchByPHP\Collections\Collection;
final class CollectionTest extends TestCase { public function testMapFilterSort():void{$c=new Collection([3,1,2,4]);$this->assertSame([4,8],$c->filter(fn($v)=>$v%2===0)->map(fn($v)=>$v*2)->all());$this->assertSame([1,2,3,4],$c->sortBy(fn($v)=>$v)->all());} }
