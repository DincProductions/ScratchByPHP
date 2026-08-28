<?php
namespace ScratchByPHP\Http;
final class Response implements \JsonSerializable {
    public function __construct(public readonly int $status,public readonly string $body,public readonly array $headers=[]){ }
    public function statusCode():int{return $this->status;} public function ok():bool{return $this->status>=200&&$this->status<300;} public function failed():bool{return !$this->ok();} public function header(string $name,mixed $default=null):mixed{$v=$this->headers[strtolower($name)]??null;return is_array($v)?($v[0]??$default):($v??$default);} public function headers():array{return $this->headers;} public function text():string{return $this->body;} public function json(bool $assoc=true):array{$d=json_decode($this->body,$assoc);return is_array($d)?$d:[];} public function toArray():array{return ['status'=>$this->status,'headers'=>$this->headers,'body'=>$this->body];} public function toJson(int $flags=0):string{return (string)json_encode($this->toArray(),$flags);} public function jsonSerialize():mixed{return $this->toArray();}
}
