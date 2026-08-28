<?php
namespace ScratchByPHP\Collections;

use ArrayAccess; use Countable; use IteratorAggregate; use JsonSerializable; use Traversable;

final class Collection implements ArrayAccess, Countable, IteratorAggregate, JsonSerializable {
    public function __construct(private array $items = []) { $this->items=array_values($items); }
    public static function make(iterable $items=[]): self { return new self(is_array($items)?$items:iterator_to_array($items,false)); }
    public function all(): array { return $this->items; }
    public function first(mixed $default=null): mixed { return $this->items[0] ?? $default; }
    public function last(mixed $default=null): mixed { return $this->items ? $this->items[array_key_last($this->items)] : $default; }
    public function map(callable $fn): self { return new self(array_map($fn,$this->items)); }
    public function filter(?callable $fn=null): self { return new self(array_values(array_filter($this->items,$fn))); }
    public function each(callable $fn): self { foreach($this->items as $k=>$v)$fn($v,$k); return $this; }
    public function take(int $n): self { return new self(array_slice($this->items,0,max(0,$n))); }
    public function skip(int $n): self { return new self(array_slice($this->items,max(0,$n))); }
    public function pluck(string $key): self { return new self(array_map(fn($v)=>is_array($v)?($v[$key]??null):(is_object($v)&&isset($v->$key)?$v->$key:null),$this->items)); }
    public function sortBy(callable|string $by): self { $a=$this->items; usort($a,function($x,$y)use($by){$vx=is_callable($by)?$by($x):(is_array($x)?($x[$by]??null):($x->$by??null));$vy=is_callable($by)?$by($y):(is_array($y)?($y[$by]??null):($y->$by??null));return $vx<=>$vy;}); return new self($a); }
    public function sortByDesc(callable|string $by): self { $a=$this->sortBy($by)->all(); return new self(array_reverse($a)); }
    public function where(callable $fn): self { return $this->filter($fn); }
    public function count(): int { return count($this->items); }
    public function isEmpty(): bool { return !$this->items; }
    public function toArray(): array { return array_map(function($v){if(is_object($v)&&method_exists($v,'toArray'))return $v->toArray();return $v;},$this->items); }
    public function toJson(int $flags=JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES): string { return (string)json_encode($this->toArray(),$flags); }
    public function getIterator(): Traversable { yield from $this->items; }
    public function jsonSerialize(): mixed { return $this->toArray(); }
    public function offsetExists(mixed $o): bool { return isset($this->items[$o]); }
    public function offsetGet(mixed $o): mixed { return $this->items[$o]??null; }
    public function offsetSet(mixed $o,mixed $v): void { $o===null?$this->items[]=$v:$this->items[$o]=$v; }
    public function offsetUnset(mixed $o): void { unset($this->items[$o]);$this->items=array_values($this->items); }
}
