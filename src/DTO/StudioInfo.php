<?php
namespace ScratchByPHP\DTO;
final class StudioInfo implements \JsonSerializable { public function __construct(public readonly string $id,public readonly ?string $title=null,public readonly ?string $description=null){} public static function fromArray(array $a):self{return new self((string)($a['id']??''),$a['title']??null,$a['description']??null);} public function toArray():array{return get_object_vars($this);} public function jsonSerialize():mixed{return $this->toArray();} }
