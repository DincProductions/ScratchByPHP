<?php
namespace ScratchByPHP\Watch;
final class EventQueue implements \Countable, \IteratorAggregate
{
    private array $events=[];
    public function push(array $event):self{$this->events[]=$event;return $this;}
    public function all():array{return $this->events;}
    public function drain():array{$out=$this->events;$this->events=[];return $out;}
    public function count():int{return count($this->events);} public function getIterator():\Traversable{return new \ArrayIterator($this->events);}
}
