<?php
namespace ScratchByPHP\Http;

use ScratchByPHP\Exceptions\ApiException;
use ScratchByPHP\Support\Logger;

final class HttpClient {
    private ?string $proxy = null;
    private int $retries = 1;
    private int $retryDelayMs = 180;
    private ?Logger $logger = null;

    public function __construct(private array $defaultHeaders = [], private ?string $cookie = null) {}
    public function setCookie(?string $cookie): void { $this->cookie = $cookie; }
    public function setDefaultHeader(string $name, string $value): void { $this->defaultHeaders[$name] = $value; }
    public function setProxy(?string $proxy): self { $this->proxy=$proxy; return $this; }
    public function setRetries(int $retries, int $delayMs=180): self { $this->retries=max(0,$retries); $this->retryDelayMs=max(0,$delayMs); return $this; }
    public function setLogger(?Logger $logger): self { $this->logger=$logger; return $this; }

    public function debugProfile(): array {
        $names=[]; foreach ($this->defaultHeaders as $k=>$v) $names[] = is_int($k) ? '[raw-header]' : $k;
        return [
            'cookie_session_id' => $this->cookie !== null && str_contains($this->cookie, 'scratchsessionsid=') ? '[present]' : '[missing]',
            'cookie_csrf' => $this->cookie !== null && str_contains($this->cookie, 'scratchcsrftoken=') ? '[present]' : '[missing]',
            'cookie_language' => $this->cookie !== null && str_contains($this->cookie, 'scratchlanguage=') ? '[present]' : '[missing]',
            'header_names' => $names,
            'x_token' => isset($this->defaultHeaders['X-Token']) ? '[present]' : '[missing]',
            'x_csrf' => isset($this->defaultHeaders['X-CSRFToken']) ? '[present]' : '[missing]',
            'proxy' => $this->proxy ? '[configured]' : '[none]',
            'retries' => $this->retries,
        ];
    }

    public function request(string $method, string $url, array|string|null $data = null, array $headers = []): Response {
        $attempt=0; $lastError=null;
        do {
            $attempt++;
            try {
                $response=$this->requestOnce($method,$url,$data,$headers);
                $this->logger?->log('debug','HTTP request',['method'=>$method,'url'=>$url,'status'=>$response->status,'attempt'=>$attempt]);
                if (($response->status===429 || $response->status>=500) && $attempt <= $this->retries) {
                    usleep($this->retryDelayMs*1000*$attempt); continue;
                }
                return $response;
            } catch (\Throwable $e) {
                $lastError=$e; $this->logger?->log('error','HTTP exception',['method'=>$method,'url'=>$url,'message'=>$e->getMessage(),'attempt'=>$attempt]);
                if ($attempt>$this->retries) throw $e;
                usleep($this->retryDelayMs*1000*$attempt);
            }
        } while ($attempt <= $this->retries+1);
        throw $lastError ?? new ApiException('HTTP isteği başarısız.');
    }

    private function requestOnce(string $method,string $url,array|string|null $data,array $headers): Response {
        $ch = curl_init($url); if (!$ch) throw new ApiException('cURL başlatılamadı.');
        $responseHeaders = []; $merged = array_merge($this->defaultHeaders, $headers); $headerLines=[];
        foreach ($merged as $k=>$v) $headerLines[] = is_int($k) ? $v : "$k: $v";
        curl_setopt_array($ch,[
            CURLOPT_RETURNTRANSFER=>true,CURLOPT_CUSTOMREQUEST=>strtoupper($method),CURLOPT_FOLLOWLOCATION=>true,
            CURLOPT_TIMEOUT=>25,CURLOPT_CONNECTTIMEOUT=>10,CURLOPT_USERAGENT=>'ScratchByPHP/0.4 (+https://scratch.mit.edu)',
            CURLOPT_HTTPHEADER=>$headerLines,
            CURLOPT_HEADERFUNCTION=>function($ch,$line)use(&$responseHeaders){$len=strlen($line);if(str_contains($line,':')){[$n,$v]=explode(':',$line,2);$responseHeaders[strtolower(trim($n))][]=trim($v);}return $len;},
        ]);
        if ($this->proxy) curl_setopt($ch,CURLOPT_PROXY,$this->proxy);
        if ($this->cookie) curl_setopt($ch,CURLOPT_COOKIE,$this->cookie);
        if ($data!==null) {
            if (is_array($data)) {
                curl_setopt($ch,CURLOPT_POSTFIELDS,json_encode($data,JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE));
                if(!array_key_exists('Content-Type',$merged)&&!array_key_exists('content-type',$merged)){$headerLines[]='Content-Type: application/json';curl_setopt($ch,CURLOPT_HTTPHEADER,$headerLines);}
            } else curl_setopt($ch,CURLOPT_POSTFIELDS,$data);
        }
        $body=curl_exec($ch); if($body===false){$err=curl_error($ch);curl_close($ch);throw new ApiException('HTTP isteği başarısız: '.$err);} $status=(int)curl_getinfo($ch,CURLINFO_RESPONSE_CODE);curl_close($ch);
        return new Response($status,(string)$body,$responseHeaders);
    }

    public function multipart(string $method,string $url,array $fields,array $headers=[]): Response {
        $ch=curl_init($url); if(!$ch) throw new ApiException('cURL başlatılamadı.');
        $merged=array_merge($this->defaultHeaders,$headers); unset($merged['Content-Type'],$merged['content-type']); $lines=[]; foreach($merged as $k=>$v)$lines[]=is_int($k)?$v:"$k: $v";
        curl_setopt_array($ch,[CURLOPT_RETURNTRANSFER=>true,CURLOPT_CUSTOMREQUEST=>strtoupper($method),CURLOPT_FOLLOWLOCATION=>true,CURLOPT_TIMEOUT=>30,CURLOPT_CONNECTTIMEOUT=>10,CURLOPT_POSTFIELDS=>$fields,CURLOPT_HTTPHEADER=>$lines,CURLOPT_USERAGENT=>'ScratchByPHP/0.4']);
        if($this->cookie)curl_setopt($ch,CURLOPT_COOKIE,$this->cookie); if($this->proxy)curl_setopt($ch,CURLOPT_PROXY,$this->proxy);
        $body=curl_exec($ch);if($body===false){$e=curl_error($ch);curl_close($ch);throw new ApiException('Multipart isteği başarısız: '.$e);} $status=(int)curl_getinfo($ch,CURLINFO_RESPONSE_CODE);curl_close($ch);return new Response($status,(string)$body,[]);
    }
    public function download(string $url,string $path,array $headers=[]): string {
        $r=$this->get($url,$headers); if($r->status<200||$r->status>=300)throw new ApiException('Dosya indirme başarısız. HTTP '.$r->status); if(file_put_contents($path,$r->body)===false)throw new ApiException('Dosya yazılamadı: '.$path); return $path;
    }
    public function get(string $url,array $headers=[]):Response{return $this->request('GET',$url,null,$headers);}    
    public function post(string $url,array|string|null $data=null,array $headers=[]):Response{return $this->request('POST',$url,$data,$headers);}    
    public function put(string $url,array|string|null $data=null,array $headers=[]):Response{return $this->request('PUT',$url,$data,$headers);}    
    public function delete(string $url,array|string|null $data=null,array $headers=[]):Response{return $this->request('DELETE',$url,$data,$headers);}    
}
