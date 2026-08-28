<?php
namespace ScratchByPHP;

use ScratchByPHP\Auth\LoginManager;
use ScratchByPHP\Http\HttpClient;
use ScratchByPHP\Cache\{CacheInterface,MemoryCache,FileCache,Psr16CacheAdapter};
use ScratchByPHP\Debug\DebugCollector;
use ScratchByPHP\Batch\BatchBuilder;
use ScratchByPHP\Watch\Watcher;
use ScratchByPHP\Analysis\ProjectDiff;
use ScratchByPHP\Testing\FakeScratch;
use ScratchByPHP\Observability\Metrics;
use ScratchByPHP\Cache\ManagedCache;
use ScratchByPHP\Http\{RetryPolicy,CircuitBreaker};
use ScratchByPHP\Ui\ScratchApiWizard;
use ScratchByPHP\Trending\TurkishTrending;

class Scratch {
    public const VERSION='0.8.5';
    private Config $config; private HttpClient $http; private CacheInterface $cache; private DebugCollector $debug; private Metrics $metrics; private RetryPolicy $retryPolicy; private CircuitBreaker $circuitBreaker;
    public function __construct(array|Config $config=[]){$this->config=$config instanceof Config?$config:new Config($config);$this->metrics=new Metrics();$this->retryPolicy=(new RetryPolicy())->maxAttempts((int)$this->config->get('retry_attempts',2))->baseDelayMs((int)$this->config->get('retry_delay_ms',180));$this->circuitBreaker=new CircuitBreaker((int)$this->config->get('circuit_threshold',5),(int)$this->config->get('circuit_cooldown',30));$this->http=new HttpClient(['Accept'=>'application/json'],null,$this->config);$this->cache=new ManagedCache(new MemoryCache(),$this->metrics);$this->debug=new DebugCollector();$this->http->setDebugCollector($this->debug)->setMetrics($this->metrics)->setRetryPolicy($this->retryPolicy)->setCircuitBreaker($this->circuitBreaker);}
    public static function version():string{return self::VERSION;} public static function fake():FakeScratch{return new FakeScratch();}
    public function config(?array $values=null):Config{if($values!==null)foreach($values as $k=>$v)$this->config->set($k,$v);return $this->config;}
    public function cache(mixed $config='memory'):self{$store=null;$rules=[];if($config instanceof ManagedCache)$this->cache=$config;elseif($config instanceof CacheInterface)$store=$config;elseif(is_object($config))$store=new Psr16CacheAdapter($config);elseif(is_string($config))$store=$config==='file'?new FileCache(sys_get_temp_dir().'/scratchbyphp-cache'):new MemoryCache();else{$driver=$config['driver']??'memory';$rules=$config['rules']??[];$store=$driver==='file'?new FileCache($config['path']??sys_get_temp_dir().'/scratchbyphp-cache'):new MemoryCache();}if($store)$this->cache=(new ManagedCache($store,$this->metrics))->rules($rules);return $this;}
    public function cacheRules(array $rules):self{if($this->cache instanceof ManagedCache)$this->cache->rules($rules);return $this;} public function metrics():Metrics{return $this->metrics;} public function retry():RetryPolicy{return $this->retryPolicy;} public function circuitBreaker():CircuitBreaker{return $this->circuitBreaker;} public function wizard(array $options=[]):ScratchApiWizard{return new ScratchApiWizard($this,$options);}
    public function cacheStore():CacheInterface{return $this->cache;} public function debug():DebugCollector{return $this->debug;}
    public function loginWithSessionId(string $sid,?string $username=null):Session{return new Session($sid,$username,null,$this->config,$this->debug,$this->metrics,$this->retryPolicy,$this->circuitBreaker);} public function login(string $username,string $password):Session{$d=LoginManager::login($username,$password);return new Session($d['sessionId'],$username,$d['csrfToken']??null,$this->config,$this->debug,$this->metrics,$this->retryPolicy,$this->circuitBreaker);} public function registration():Registration\Registration{return new Registration\Registration();}
    public function user(string $u):User\User{return new User\User($u,null,$this->http,$this->cache,$this->config);} public function project(int|string $id):Project\Project{return new Project\Project((string)$id,null,$this->http,$this->cache,$this->config);} public function studio(int|string $id):Studio\Studio{return new Studio\Studio((string)$id,null,$this->http,$this->cache,$this->config);}
    public function searchProjects(string $q='',string $mode='trending',string $language='en',int $limit=40,int $offset=0):array{return $this->http->get('https://api.scratch.mit.edu/search/projects?limit='.$limit.'&offset='.$offset.'&language='.rawurlencode($language).'&mode='.rawurlencode($mode).'&q='.rawurlencode($q))->json();}
    public function searchStudios(string $q='',string $mode='trending',string $language='tr',int $limit=40,int $offset=0):array{return $this->http->get('https://api.scratch.mit.edu/search/studios?limit='.$limit.'&offset='.$offset.'&language='.rawurlencode($language).'&mode='.rawurlencode($mode).'&q='.rawurlencode($q))->json();}
    public function turkishTrending(int $limit=20,int $scan=120,array $options=[]):array{return (new TurkishTrending($this))->get($limit,$scan,$options);}
    public function turkishTrendProjects(int $limit=20,int $scan=120,array $options=[]):array{return $this->turkishTrending($limit,$scan,$options);}
    public function exploreProjects(string $q='*',string $mode='trending',string $language='en',int $limit=40,int $offset=0):array{return $this->http->get('https://api.scratch.mit.edu/explore/projects?limit='.$limit.'&offset='.$offset.'&language='.rawurlencode($language).'&mode='.rawurlencode($mode).'&q='.rawurlencode($q))->json();}
    public function projects(array $ids):\ScratchByPHP\Collections\Collection{$b=$this->batch();foreach($ids as $id)$b=$b->project($id);$r=$b->run();$out=[];foreach($ids as $id){$key='project:'.$id;$row=$r[$key]['json']??null;if(is_array($row)&&$row)$this->cache->set($key,$row,(int)$this->config->get('cache_ttl',60));$out[]=$this->project($id);}return new \ScratchByPHP\Collections\Collection($out);}
    public function batch():BatchBuilder{return new BatchBuilder($this);} public function parallel(array $requests,int $timeout=25,int $concurrency=8,int $retries=1,?callable $progress=null):array{return (new \ScratchByPHP\Batch\ParallelClient())->run($requests,$timeout,$concurrency,$retries,$progress);} public function watch():Watcher{return new Watcher($this);}
    public function compareProjects(int|string $a,int|string $b):ProjectDiff{return new ProjectDiff($this->project($a)->analyze()->raw(),$this->project($b)->analyze()->raw());}
    public function healthCheck(bool $network=false):array{$tmp=sys_get_temp_dir();$checks=['version'=>self::VERSION,'php'=>PHP_VERSION,'php_ok'=>version_compare(PHP_VERSION,'8.1','>='),'curl'=>extension_loaded('curl'),'openssl'=>extension_loaded('openssl'),'json'=>extension_loaded('json'),'zip'=>extension_loaded('zip'),'dns_function'=>function_exists('gethostbyname'),'temp_dir'=>$tmp,'temp_writable'=>is_dir($tmp)&&is_writable($tmp),'memory_limit'=>ini_get('memory_limit'),'max_execution_time'=>(int)ini_get('max_execution_time')];if($network){$host='api.scratch.mit.edu';$dns=gethostbyname($host);$checks['dns_ok']=$dns!==$host;$start=microtime(true);try{$r=$this->http->get('https://api.scratch.mit.edu/projects/104');$checks['scratch_api']=$r->status;$checks['scratch_api_ms']=round((microtime(true)-$start)*1000,2);}catch(\Throwable $e){$checks['scratch_api']='error: '.$e->getMessage();}}$checks['circuit']=$this->circuitBreaker->state();$checks['metrics']=$this->metrics->summary();$checks['ok']=$checks['php_ok']&&$checks['curl']&&$checks['openssl']&&$checks['json']&&$checks['temp_writable'];return $checks;}
}
