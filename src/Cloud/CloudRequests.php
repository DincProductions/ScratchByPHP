<?php
namespace ScratchByPHP\Cloud;

final class CloudRequests {
    private array $handlers=[]; private array $middleware=[];
    public function __construct(private CloudConnection $cloud, private string $requestVar='request', private string $responseVar='response') {}
    public function on(string $method, callable $handler): self { $this->handlers[$method]=$handler; return $this; } public function route(string $method, callable $handler): self { return $this->on($method,$handler); } public function middleware(callable $middleware): self { $this->middleware[]=$middleware; return $this; }
    public function handleOnce(float $timeout=10.0): ?array {
        $value=$this->cloud->waitForChange($this->requestVar,$timeout);
        if ($value===null) return null;
        try { $payload=json_decode(CloudCodec::decode($value),true,512,JSON_THROW_ON_ERROR); }
        catch(\Throwable $e){ return ['ok'=>false,'error'=>'decode_failed','message'=>$e->getMessage()]; }
        $method=(string)($payload['method']??''); $id=$payload['id']??null; $params=$payload['params']??[];
        if (!isset($this->handlers[$method])) $response=['id'=>$id,'ok'=>false,'error'=>'method_not_found'];
        else {
            try { $handler=$this->handlers[$method]; $next=fn($p,$pl)=>$handler($p,$pl); foreach(array_reverse($this->middleware) as $mw){$prev=$next;$next=fn($p,$pl)=>$mw($p,$pl,$prev);} $data=$next($params,$payload); $response=['id'=>$id,'ok'=>true,'data'=>$data]; }
            catch(\Throwable $e){ $response=['id'=>$id,'ok'=>false,'error'=>'handler_error','message'=>$e->getMessage()]; }
        }
        $encoded=CloudCodec::encode(json_encode($response,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES));
        if (strlen($encoded)>256) throw new \RuntimeException('CloudRequests yanıtı 256 basamak sınırını aşıyor.');
        $this->cloud->setVerified($this->responseVar,$encoded,5.0);
        return $response;
    }
    public function run(?int $maxRequests=null, float $timeoutPerRequest=30.0): void {
        $n=0; while ($maxRequests===null || $n<$maxRequests) { $r=$this->handleOnce($timeoutPerRequest); if($r!==null)$n++; }
    }
}
