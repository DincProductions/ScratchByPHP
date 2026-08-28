<?php
namespace ScratchByPHP\DTO;
final class UserProfile implements \JsonSerializable { public function __construct(public readonly string $username,public readonly ?string $bio=null,public readonly ?string $status=null,public readonly ?string $country=null){} public static function fromArray(array $a):self{return new self((string)($a['username']??''),$a['profile']['bio']??null,$a['profile']['status']??null,$a['profile']['country']??null);} public function toArray():array{return get_object_vars($this);} public function jsonSerialize():mixed{return $this->toArray();} }
