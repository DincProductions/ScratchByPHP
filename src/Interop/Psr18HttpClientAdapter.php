<?php
namespace ScratchByPHP\Interop;
use ScratchByPHP\Http\HttpClient;
final class Psr18HttpClientAdapter { public function __construct(private HttpClient $http){} public function sendRequest(object $request): object { if(!method_exists($request,'getMethod')||!method_exists($request,'getUri'))throw new \InvalidArgumentException('PSR-7 benzeri request gerekir.'); throw new \LogicException('PSR-18 adapter optional bridge olarak gelir; gerçek PSR-7 Response üretmek için projenizde psr/http-message implementation bağlayın. Raw client için HttpClient kullanın.'); } public function raw():HttpClient{return $this->http;} }
