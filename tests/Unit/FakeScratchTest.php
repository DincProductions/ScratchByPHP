<?php
use PHPUnit\Framework\TestCase; use ScratchByPHP\Scratch;
final class FakeScratchTest extends TestCase { public function testFakeProject():void{$s=Scratch::fake()->fakeProject(1,['title'=>'Demo','stats'=>['views'=>9]]);$this->assertSame('Demo',$s->project(1)->title());$this->assertSame(9,$s->project(1)->views());} }
