<?php
namespace ScratchByPHP\DTO;
final class ProjectStats implements \JsonSerializable { public function __construct(public readonly int $views=0,public readonly int $loves=0,public readonly int $favorites=0,public readonly int $remixes=0){} public static function fromArray(array $a):self{return new self((int)($a['views']??0),(int)($a['loves']??0),(int)($a['favorites']??0),(int)($a['remixes']??0));} public function toArray():array{return get_object_vars($this);} public function jsonSerialize():mixed{return $this->toArray();} }
