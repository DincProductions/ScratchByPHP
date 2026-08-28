<?php
namespace ScratchByPHP\Http;

use ScratchByPHP\Exceptions\ApiException;
use ScratchByPHP\Support\Logger;
use ScratchByPHP\Config;
use ScratchByPHP\Debug\DebugCollector;
use ScratchByPHP\Support\RateLimiter;
use ScratchByPHP\Observability\Metrics;

final class HttpClient {
    private ?string $proxy = null;
    private int $retries = 1;
    private int $retryDelayMs = 180;
    private ?Logger $logger = null;
    private ?Config $config = null;
    private ?DebugCollector $debug = null;
    private ?Metrics $metrics = null;
    private ?RetryPolicy $retryPolicy = null;
    private ?CircuitBreaker $circuitBreaker = null;

    public function __construct(private array $defaultHeaders = [], private ?string $cookie = null, ?Config $config = null) { $this->config=$config; if($config){$this->retries=(int)$config->get('retries',1);$this->retryDelayMs=(int)$config->get('retry_delay_ms',180);} }
    public function setDebugCollector(?DebugCollector $debug): self { $this->debug=$debug; return $this; }
    public function setMetrics(?Metrics $metrics): self {$this->metrics=$metrics;return $this;} public function setRetryPolicy(?RetryPolicy $policy):self{$this->retryPolicy=$policy;return $this;} public function setCircuitBreaker(?CircuitBreaker $breaker):self{$this->circuitBreaker=$breaker;return $this;}

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
            'credential_host_guard' => $this->hasSensitiveAuth() ? '[enabled]' : '[not-needed]',
            'authenticated_redirects' => $this->hasSensitiveAuth() ? '[disabled]' : '[normal]',
        ];
    }

    public function request(string $method, string $url, array|string|null $data = null, array $headers = []): Response {
        $this->assertSafeTarget($url, $headers);
        if($this->circuitBreaker && !$this->circuitBreaker->allow()) throw new ApiException('Circuit breaker açık; Scratch isteği geçici olarak durduruldu.');
        $policy=$this->retryPolicy; $maxAttempts=$policy?->attempts() ?? ($this->retries+1); $attempt=0; $lastError=null;
        while($attempt<$maxAttempts){$attempt++;$started=microtime(true);try{$response=$this->requestOnce($method,$url,$data,$headers);$ms=(microtime(true)-$started)*1000;$this->metrics?->recordRequest($response->status,$ms);$this->logger?->log('debug','HTTP request',['method'=>$method,'url'=>$url,'status'=>$response->status,'attempt'=>$attempt]);$this->debug?->record('http',['method'=>$method,'url'=>$url,'status'=>$response->status,'attempt'=>$attempt,'ms'=>round($ms,2)]);$retry=$policy?$policy->shouldRetryStatus($response->status):($response->status===429||$response->status>=500);if($retry&&$attempt<$maxAttempts){$this->circuitBreaker?->failure();$this->metrics?->retry();$ra=RateLimiter::retryAfter($response->headers);$delay=$policy?$policy->delayMs($attempt,$ra):($ra!==null?$ra*1000:$this->retryDelayMs*$attempt);usleep($delay*1000);continue;}$response->status>=500?$this->circuitBreaker?->failure():$this->circuitBreaker?->success();return $response;}catch(\Throwable $e){$ms=(microtime(true)-$started)*1000;$this->metrics?->recordFailure($ms);$this->circuitBreaker?->failure();$lastError=$e;$this->logger?->log('error','HTTP exception',['method'=>$method,'url'=>$url,'message'=>$e->getMessage(),'attempt'=>$attempt]);if($attempt>=$maxAttempts)throw $e;$this->metrics?->retry();$delay=$policy?$policy->delayMs($attempt):$this->retryDelayMs*$attempt;usleep($delay*1000);}}
        throw $lastError ?? new ApiException('HTTP isteği başarısız.');
    }

    private function requestOnce(string $method,string $url,array|string|null $data,array $headers): Response {
        $ch = curl_init($url);
        if (!$ch) throw new ApiException('cURL başlatılamadı.');

        $responseHeaders = [];
        $merged = array_merge($this->defaultHeaders, $headers);
        $headerLines=[];
        foreach ($merged as $k=>$v) $headerLines[] = is_int($k) ? $v : "$k: $v";

        $sensitive = $this->hasSensitiveAuth($headers);

        curl_setopt_array($ch,[
            CURLOPT_RETURNTRANSFER=>true,
            CURLOPT_CUSTOMREQUEST=>strtoupper($method),
            // Never automatically redirect a request carrying Scratch credentials.
            // Public clients retain ordinary redirect behaviour.
            CURLOPT_FOLLOWLOCATION=>!$sensitive,
            CURLOPT_MAXREDIRS=>$sensitive ? 0 : 5,
            CURLOPT_TIMEOUT=>(int)($this->config?->get('timeout',25)??25),
            CURLOPT_CONNECTTIMEOUT=>(int)($this->config?->get('connect_timeout',10)??10),
            CURLOPT_USERAGENT=>(string)($this->config?->get('user_agent','ScratchByPHP/0.8.5')??'ScratchByPHP/0.8.5'),
            CURLOPT_HTTPHEADER=>$headerLines,
            CURLOPT_HEADERFUNCTION=>function($ch,$line)use(&$responseHeaders){
                $len=strlen($line);
                if(str_contains($line,':')){
                    [$n,$v]=explode(':',$line,2);
                    $responseHeaders[strtolower(trim($n))][]=trim($v);
                }
                return $len;
            },
        ]);

        if (defined('CURLOPT_PROTOCOLS') && defined('CURLPROTO_HTTPS')) {
            curl_setopt($ch, CURLOPT_PROTOCOLS, CURLPROTO_HTTPS);
        }
        if (defined('CURLOPT_REDIR_PROTOCOLS') && defined('CURLPROTO_HTTPS')) {
            curl_setopt($ch, CURLOPT_REDIR_PROTOCOLS, CURLPROTO_HTTPS);
        }

        if ($this->proxy) curl_setopt($ch,CURLOPT_PROXY,$this->proxy);
        if ($this->cookie) curl_setopt($ch,CURLOPT_COOKIE,$this->cookie);

        if ($data!==null) {
            if (is_array($data)) {
                curl_setopt($ch,CURLOPT_POSTFIELDS,json_encode($data,JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE));
                if(!array_key_exists('Content-Type',$merged)&&!array_key_exists('content-type',$merged)){
                    $headerLines[]='Content-Type: application/json';
                    curl_setopt($ch,CURLOPT_HTTPHEADER,$headerLines);
                }
            } else {
                curl_setopt($ch,CURLOPT_POSTFIELDS,$data);
            }
        }

        $body=curl_exec($ch);
        if($body===false){
            $err=curl_error($ch);
            curl_close($ch);
            throw new ApiException('HTTP isteği başarısız: '.$err);
        }
        $status=(int)curl_getinfo($ch,CURLINFO_RESPONSE_CODE);
        curl_close($ch);

        return new Response($status,(string)$body,$responseHeaders);
    }

    public function multipart(string $method,string $url,array $fields,array $headers=[]): Response {
        $this->assertSafeTarget($url, $headers);

        $ch=curl_init($url);
        if(!$ch) throw new ApiException('cURL başlatılamadı.');

        $merged=array_merge($this->defaultHeaders,$headers);
        unset($merged['Content-Type'],$merged['content-type']);
        $lines=[];
        foreach($merged as $k=>$v)$lines[]=is_int($k)?$v:"$k: $v";

        $sensitive = $this->hasSensitiveAuth($headers);
        curl_setopt_array($ch,[
            CURLOPT_RETURNTRANSFER=>true,
            CURLOPT_CUSTOMREQUEST=>strtoupper($method),
            CURLOPT_FOLLOWLOCATION=>!$sensitive,
            CURLOPT_MAXREDIRS=>$sensitive ? 0 : 5,
            CURLOPT_TIMEOUT=>30,
            CURLOPT_CONNECTTIMEOUT=>(int)($this->config?->get('connect_timeout',10)??10),
            CURLOPT_POSTFIELDS=>$fields,
            CURLOPT_HTTPHEADER=>$lines,
            CURLOPT_USERAGENT=>(string)($this->config?->get('user_agent','ScratchByPHP/0.8.5')??'ScratchByPHP/0.8.5'),
        ]);

        if (defined('CURLOPT_PROTOCOLS') && defined('CURLPROTO_HTTPS')) curl_setopt($ch, CURLOPT_PROTOCOLS, CURLPROTO_HTTPS);
        if (defined('CURLOPT_REDIR_PROTOCOLS') && defined('CURLPROTO_HTTPS')) curl_setopt($ch, CURLOPT_REDIR_PROTOCOLS, CURLPROTO_HTTPS);
        if($this->cookie)curl_setopt($ch,CURLOPT_COOKIE,$this->cookie);
        if($this->proxy)curl_setopt($ch,CURLOPT_PROXY,$this->proxy);

        $body=curl_exec($ch);
        if($body===false){
            $e=curl_error($ch);
            curl_close($ch);
            throw new ApiException('Multipart isteği başarısız: '.$e);
        }
        $status=(int)curl_getinfo($ch,CURLINFO_RESPONSE_CODE);
        curl_close($ch);
        return new Response($status,(string)$body,[]);
    }

    public function download(string $url,string $path,array $headers=[]): string {
        if (str_contains($path, "\0")) throw new ApiException('Geçersiz dosya yolu.');
        $dir = dirname($path);
        if (!is_dir($dir) || !is_writable($dir)) throw new ApiException('Hedef klasör yok veya yazılabilir değil: '.$dir);

        $r=$this->get($url,$headers);
        if($r->status<200||$r->status>=300)throw new ApiException('Dosya indirme başarısız. HTTP '.$r->status);
        if(file_put_contents($path,$r->body,LOCK_EX)===false)throw new ApiException('Dosya yazılamadı: '.$path);
        return $path;
    }

    public function get(string $url,array $headers=[]):Response{return $this->request('GET',$url,null,$headers);}
    public function post(string $url,array|string|null $data=null,array $headers=[]):Response{return $this->request('POST',$url,$data,$headers);}
    public function put(string $url,array|string|null $data=null,array $headers=[]):Response{return $this->request('PUT',$url,$data,$headers);}
    public function delete(string $url,array|string|null $data=null,array $headers=[]):Response{return $this->request('DELETE',$url,$data,$headers);}

    private function hasSensitiveAuth(array $extraHeaders = []): bool {
        if ($this->cookie !== null && trim($this->cookie) !== '') return true;

        $headers = array_merge($this->defaultHeaders, $extraHeaders);
        foreach ($headers as $key => $value) {
            if (is_int($key)) {
                $name = strtolower(trim((string)strtok((string)$value, ':')));
            } else {
                $name = strtolower(trim((string)$key));
            }
            if (in_array($name, ['x-token','x-csrftoken','authorization','cookie'], true)) return true;
        }
        return false;
    }

    private function assertSafeTarget(string $url, array $extraHeaders = []): void {
        if (!$this->hasSensitiveAuth($extraHeaders)) return;

        $parts = parse_url($url);
        $scheme = strtolower((string)($parts['scheme'] ?? ''));
        $host = strtolower(rtrim((string)($parts['host'] ?? ''), '.'));

        if ($scheme !== 'https' || $host === '') {
            throw new ApiException('Credential taşıyan Scratch isteği yalnızca HTTPS Scratch adreslerine gönderilebilir.');
        }

        $allowed = $host === 'scratch.mit.edu' || str_ends_with($host, '.scratch.mit.edu');
        if (!$allowed) {
            throw new ApiException('Güvenlik nedeniyle authenticated HttpClient bu domaine istek gönderemez: '.$host);
        }
    }
}
