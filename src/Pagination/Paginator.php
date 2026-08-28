<?php
namespace ScratchByPHP\Pagination;
use ScratchByPHP\Collections\Collection;

final class Paginator implements \IteratorAggregate {
    private int $limit=20; private int $page=1; private ?int $maxPages=null;
    public function __construct(private \Closure $fetcher, private ?\Closure $mapper=null) {}
    public function limit(int $limit): self { $c=clone $this;$c->limit=max(1,min(100,$limit));return $c; }
    public function page(int $page): self { $c=clone $this;$c->page=max(1,$page);return $c; }
    public function maxPages(?int $n): self { $c=clone $this;$c->maxPages=$n===null?null:max(1,$n);return $c; }
    public function get(): Collection { $rows=($this->fetcher)($this->limit,($this->page-1)*$this->limit); return new Collection($this->map($rows)); }
    public function all(?int $maxPages=null): Collection { $out=[];$page=1;$cap=$maxPages??$this->maxPages;while(true){$rows=($this->fetcher)($this->limit,($page-1)*$this->limit);if(!$rows)break;array_push($out,...$this->map($rows));if(count($rows)<$this->limit)break;$page++;if($cap!==null&&$page>$cap)break;}return new Collection($out); }
    private function map(array $rows): array { return $this->mapper?array_map($this->mapper,$rows):$rows; }
    public function getIterator(): \Traversable { yield from $this->all()->all(); }
}
