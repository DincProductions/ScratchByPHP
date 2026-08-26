<?php
namespace ScratchByPHP\Cloud;

use ScratchByPHP\Session;
use ScratchByPHP\Exceptions\CloudException;

final class CloudConnection {
    private WebSocketClient $ws;
    private CloudEvents $events;
    private array $values=[];
    private array $variableListeners=[];
    private array $lastRemoteMeta=[];

    public function __construct(private string $projectId, private Session $session) {
        $this->ws=new WebSocketClient();
        $this->events=new CloudEvents();
    }

    public function connect(): self {
        $username=$this->session->username();
        if(!$username) throw new CloudException('Cloud için kullanıcı adı çözümlenemedi.');

        $this->ws->connect('clouddata.scratch.mit.edu',443,'/',[
            'Origin'=>'https://scratch.mit.edu',
            'Cookie'=>'scratchsessionsid='.$this->session->sessionId().'; scratchlanguage=en;',
            'User-Agent'=>'ScratchByPHP/0.3.0'
        ]);

        $this->send([
            'method'=>'handshake',
            'user'=>$username,
            'project_id'=>$this->projectId
        ]);
        return $this;
    }

    private function send(array $packet): void {
        $json=json_encode($packet,JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE);
        if($json===false) throw new CloudException('Cloud paketi JSON olarak oluşturulamadı.');
        // Scratch cloud protokolü her JSON mesajını newline ile sonlandırır.
        $this->ws->sendText($json."\n");
    }

    private function normalizeName(string $name): string {
        $name=trim($name);
        return str_starts_with($name,'☁ ') ? $name : '☁ '.$name;
    }

    private function processRaw(string $raw): array {
        $packets=[];
        foreach(preg_split('/\r?\n/',trim($raw)) as $line) {
            if($line==='') continue;
            $data=json_decode($line,true);
            if(!is_array($data)) continue;
            $packets[]=$data;
            if(($data['method']??null)==='set' && isset($data['name'])) {
                $v=new CloudVariable((string)$data['name'],(string)($data['value']??''),$data['user']??null);
                $this->values[$v->name]=$v->value;
                $this->lastRemoteMeta[$v->name]=[
                    'source'=>'websocket',
                    'user'=>$data['user']??null,
                    'timestamp'=>microtime(true)
                ];
                $this->events->emit('set',$v);
                foreach($this->variableListeners[$v->name]??[] as $listener) $listener($v->value,$v);
            }
            $this->events->emit('message',$data);
        }
        return $packets;
    }

    /**
     * Scratch cloud log API'sinden en güncel gerçek değerleri okur.
     * Log endpoint'i kullanılabilir değilse CloudException fırlatır.
     */
    public function fetchRemoteValues(int $limit=100): array {
        $limit=max(1,min(100,$limit));
        $url='https://clouddata.scratch.mit.edu/logs?projectid='.rawurlencode($this->projectId).'&limit='.$limit.'&offset=0&_='.(string)round(microtime(true)*1000);

        $ch=curl_init($url);
        if(!$ch) throw new CloudException('Cloud log isteği başlatılamadı.');
        curl_setopt_array($ch,[
            CURLOPT_RETURNTRANSFER=>true,
            CURLOPT_FOLLOWLOCATION=>true,
            CURLOPT_CONNECTTIMEOUT=>8,
            CURLOPT_TIMEOUT=>12,
            CURLOPT_HTTPHEADER=>['Accept: application/json','Cache-Control: no-cache'],
            CURLOPT_USERAGENT=>'ScratchByPHP/0.3.0'
        ]);
        $body=curl_exec($ch);
        if($body===false){ $err=curl_error($ch); curl_close($ch); throw new CloudException('Cloud log isteği başarısız: '.$err); }
        $status=(int)curl_getinfo($ch,CURLINFO_RESPONSE_CODE);
        curl_close($ch);
        if($status<200||$status>=300) throw new CloudException('Cloud log endpoint HTTP '.$status.' döndürdü.');

        $rows=json_decode((string)$body,true);
        if(!is_array($rows)) throw new CloudException('Cloud log endpoint geçerli JSON döndürmedi.');

        // Loglar en yeniden eskiye gelir. İlk gördüğümüz değer o değişkenin güncel değeridir.
        $remote=[];
        foreach($rows as $row){
            if(!is_array($row)||!isset($row['name'])) continue;
            $name=(string)$row['name'];
            $verb=(string)($row['verb']??'');
            if(isset($remote[$name])) continue;
            if($verb==='del_var' || $verb==='delete_var') continue;
            if(!array_key_exists('value',$row)) continue;
            $remote[$name]=(string)$row['value'];
            $this->values[$name]=(string)$row['value'];
            $this->lastRemoteMeta[$name]=[
                'source'=>'cloud-logs',
                'user'=>$row['user']??null,
                'timestamp'=>$row['timestamp']??null,
                'verb'=>$verb
            ];
        }
        return $remote;
    }

    /** Yalnızca WebSocket'e SET yollar; local cache'i sahte biçimde güncellemez. */
    public function set(string $name, int|float|string $value): self {
        if(!$this->ws->isConnected()) throw new CloudException('Önce connect() çağırılmalı.');
        $name=$this->normalizeName($name);
        $value=(string)$value;
        if(!preg_match('/^-?\d+(?:\.\d+)?$/',$value)) {
            throw new CloudException('Scratch cloud değişkenleri yalnızca sayısal değer kabul eder.');
        }
        $this->send([
            'method'=>'set',
            'name'=>$name,
            'value'=>$value,
            'user'=>$this->session->username(),
            'project_id'=>$this->projectId
        ]);
        return $this;
    }

    /** Son gerçekten alınmış değeri döndürür. */
    public function get(string $name): ?string {
        return $this->values[$this->normalizeName($name)]??null;
    }

    /**
     * Önce cloud logundan gerçek değeri almaya çalışır; log erişilemiyorsa websocket'ten bekler.
     */
    public function getRemote(string $name,float $timeoutSeconds=4.0): ?string {
        $name=$this->normalizeName($name);

        try {
            $remote=$this->fetchRemoteValues(100);
            if(array_key_exists($name,$remote)) return $remote[$name];
        } catch(\Throwable $ignored) {
            // Log servisi zaman zaman erişilemeyebilir; WebSocket fallback kullan.
        }

        if(!$this->ws->isConnected()) throw new CloudException('Cloud logs değeri vermedi ve WebSocket bağlı değil. Önce connect() çağırılmalı.');
        $deadline=microtime(true)+max(.1,$timeoutSeconds);
        while($this->ws->isConnected()&&microtime(true)<$deadline){
            $remaining=max(.05,$deadline-microtime(true));
            $this->ws->setReadTimeout(min(.5,$remaining));
            $raw=$this->ws->receive();
            if($raw===null){ if($this->ws->timedOut()) continue; break; }
            $this->processRaw($raw);
            if(array_key_exists($name,$this->values)) break;
        }
        $this->ws->setReadTimeout(30.0);
        return $this->values[$name]??null;
    }

    /**
     * Değeri gönderir ve Scratch tarafında gerçekten değiştiğini doğrular.
     */
    public function setVerified(string $name,int|float|string $value,float $timeoutSeconds=5.0): array {
        if(!$this->ws->isConnected()) throw new CloudException('Önce connect() çağırılmalı.');
        $normalized=$this->normalizeName($name);
        $value=(string)$value;
        $before=$this->getRemote($normalized,1.0);
        $this->set($normalized,$value);

        $deadline=microtime(true)+max(1.0,$timeoutSeconds);
        $after=null;
        $source=null;
        $attempts=0;
        while(microtime(true)<$deadline){
            $attempts++;

            // Önce sunucunun websocket echo/update paketini dinle.
            $this->ws->setReadTimeout(.35);
            $raw=$this->ws->receive();
            if($raw!==null){
                $this->processRaw($raw);
                $after=$this->values[$normalized]??null;
                if($after===$value){ $source='websocket'; break; }
            }

            // Ardından gerçek cloud logunu kontrol et.
            try {
                $vals=$this->fetchRemoteValues(100);
                $after=$vals[$normalized]??$after;
                if($after===$value){ $source='cloud-logs'; break; }
            } catch(\Throwable $ignored) {}

            usleep(250000);
        }
        $this->ws->setReadTimeout(30.0);

        $verified=($after===$value);
        return [
            'variable'=>$normalized,
            'before'=>$before,
            'sent'=>$value,
            'remote_after'=>$after,
            'verified'=>$verified,
            'verification_source'=>$source,
            'attempts'=>$attempts,
            'same_as_before'=>($before!==null && $before===$value),
            'message'=>$verified
                ? (($before===$value)?'Değer zaten aynıydı; Scratch üzerinde gözle görünür değişiklik olmayabilir.':'Scratch tarafındaki yeni değer doğrulandı.')
                : 'SET paketi gönderildi ancak Scratch tarafında yeni değer doğrulanamadı.'
        ];
    }

    public function sync(float $timeoutSeconds=1.5): array {
        // İlk olarak loglardan gerçek bilinen değerleri yükle.
        try { $this->fetchRemoteValues(100); } catch(\Throwable $ignored) {}
        if(!$this->ws->isConnected()) return $this->all();
        $deadline=microtime(true)+max(.1,$timeoutSeconds);
        while($this->ws->isConnected()&&microtime(true)<$deadline){
            $remaining=max(.05,$deadline-microtime(true));
            $this->ws->setReadTimeout(min(.4,$remaining));
            $raw=$this->ws->receive();
            if($raw===null){ if($this->ws->timedOut()) continue; break; }
            $this->processRaw($raw);
        }
        $this->ws->setReadTimeout(30.0);
        return $this->all();
    }

    public function remoteMeta(string $name): array { return $this->lastRemoteMeta[$this->normalizeName($name)]??[]; }
    public function all(): array { return $this->values; }
    public function on(string $event,callable $listener): self { $this->events->on($event,$listener); return $this; }
    public function onVariable(string $name,callable $listener): self { $this->variableListeners[$this->normalizeName($name)][]=$listener; return $this; }

    public function listen(?int $maxMessages=null): void {
        $count=0;
        while($this->ws->isConnected()){
            $raw=$this->ws->receive();
            if($raw===null) break;
            $packets=$this->processRaw($raw);
            $count+=count($packets);
            if($maxMessages!==null&&$count>=$maxMessages) return;
        }
    }


    public function waitFor(string $name, int|float|string $expected, float $timeoutSeconds=10.0): ?CloudVariable {
        $name=$this->normalizeName($name); $expected=(string)$expected; $deadline=microtime(true)+max(.1,$timeoutSeconds);
        while(microtime(true)<$deadline){
            $this->ws->setReadTimeout(min(.5,max(.05,$deadline-microtime(true))));
            $raw=$this->ws->receive(); if($raw===null){ if($this->ws->timedOut())continue; break; }
            foreach($this->processRaw($raw) as $d){ if(($d['method']??null)==='set' && ($d['name']??'')===$name && (string)($d['value']??'')===$expected){ $this->ws->setReadTimeout(30.0); return new CloudVariable($name,$expected,$d['user']??null); } }
        } $this->ws->setReadTimeout(30.0); return null;
    }
    public function waitForChange(string $name,float $timeoutSeconds=10.0): ?string {
        $name=$this->normalizeName($name); $before=$this->getRemote($name,1.0); $deadline=microtime(true)+max(.1,$timeoutSeconds);
        while(microtime(true)<$deadline){ $this->ws->setReadTimeout(min(.5,max(.05,$deadline-microtime(true)))); $raw=$this->ws->receive(); if($raw===null){if($this->ws->timedOut())continue;break;} $this->processRaw($raw); $now=$this->values[$name]??null; if($now!==null && $now!==$before){$this->ws->setReadTimeout(30.0);return $now;} }
        $this->ws->setReadTimeout(30.0); return null;
    }
    public function watch(string $name,callable $listener): self { return $this->onVariable($name,function($value,$var)use($listener){$listener($value,$var);}); }
    public function requests(string $requestVar='request',string $responseVar='response'): CloudRequests { return new CloudRequests($this,$requestVar,$responseVar); }
    public function database(string $variable='db'): CloudDatabase { return new CloudDatabase($this,$variable); }

    public function disconnect(): void { $this->ws->close(); }
    public function isConnected(): bool { return $this->ws->isConnected(); }
}
