<?php
namespace ScratchByPHP\Cloud;

use ScratchByPHP\Exceptions\CloudException;

final class WebSocketClient {
    private $socket=null;

    public function connect(string $host,int $port=443,string $path='/',array $headers=[]): void {
        $context=stream_context_create(['ssl'=>[
            'verify_peer'=>true,'verify_peer_name'=>true,'SNI_enabled'=>true,'peer_name'=>$host
        ]]);
        $this->socket=@stream_socket_client("ssl://{$host}:{$port}",$errno,$errstr,15,STREAM_CLIENT_CONNECT,$context);
        if(!$this->socket) throw new CloudException("WebSocket bağlantısı kurulamadı: {$errstr} ({$errno})");
        $this->setReadTimeout(30.0);

        $key=base64_encode(random_bytes(16));
        $request="GET {$path} HTTP/1.1\r\nHost: {$host}\r\nUpgrade: websocket\r\nConnection: Upgrade\r\nSec-WebSocket-Key: {$key}\r\nSec-WebSocket-Version: 13\r\n";
        foreach($headers as $name=>$value) $request.=$name.': '.$value."\r\n";
        $request.="\r\n";
        $this->writeAll($request);

        $response='';
        while(!str_contains($response,"\r\n\r\n")&&!feof($this->socket)){
            $line=fgets($this->socket,4096);
            if($line===false) break;
            $response.=$line;
        }
        if(!preg_match('#HTTP/1\.[01] 101#',$response)){
            $line=trim((string)strtok($response,"\r\n"));
            $this->close();
            throw new CloudException('WebSocket handshake reddedildi: '.$line);
        }
        $accept=base64_encode(sha1($key.'258EAFA5-E914-47DA-95CA-C5AB0DC85B11',true));
        if(!preg_match('/^Sec-WebSocket-Accept:\s*'.preg_quote($accept,'/').'\s*$/mi',$response)){
            $this->close();
            throw new CloudException('WebSocket doğrulaması başarısız.');
        }
    }

    public function setReadTimeout(float $seconds): void {
        if(!$this->socket) return;
        $whole=max(0,(int)floor($seconds));
        $micro=max(0,(int)(($seconds-$whole)*1_000_000));
        stream_set_timeout($this->socket,$whole,$micro);
    }

    private function writeAll(string $data): void {
        if(!$this->socket) throw new CloudException('WebSocket bağlı değil.');
        $offset=0; $len=strlen($data);
        while($offset<$len){
            $written=fwrite($this->socket,substr($data,$offset));
            if($written===false||$written===0) throw new CloudException('WebSocket verisi gönderilemedi.');
            $offset+=$written;
        }
        fflush($this->socket);
    }

    public function sendText(string $payload): void {
        if(!$this->socket) throw new CloudException('WebSocket bağlı değil.');
        $len=strlen($payload); $mask=random_bytes(4); $frame=chr(0x81);
        if($len<=125) $frame.=chr(0x80|$len);
        elseif($len<=65535) $frame.=chr(0x80|126).pack('n',$len);
        else {
            $hi=(int)floor($len/4294967296); $lo=$len%4294967296;
            $frame.=chr(0x80|127).pack('NN',$hi,$lo);
        }
        $masked='';
        for($i=0;$i<$len;$i++) $masked.=$payload[$i]^$mask[$i%4];
        $this->writeAll($frame.$mask.$masked);
    }

    public function receive(): ?string {
        if(!$this->socket) return null;
        $h=$this->readExact(2);
        if(strlen($h)<2) return null;
        $b1=ord($h[0]); $b2=ord($h[1]); $opcode=$b1&0x0f; $masked=(bool)($b2&0x80); $len=$b2&0x7f;
        if($len===126){ $x=$this->readExact(2); if(strlen($x)<2)return null; $len=unpack('n',$x)[1]; }
        elseif($len===127){ $x=$this->readExact(8); if(strlen($x)<8)return null; $u=unpack('N2',$x); $len=(int)($u[1]*4294967296+$u[2]); }
        $mask=$masked?$this->readExact(4):'';
        $payload=$this->readExact((int)$len);
        if(strlen($payload)<$len) return null;
        if($masked){ $out=''; for($i=0;$i<$len;$i++)$out.=$payload[$i]^$mask[$i%4]; $payload=$out; }
        if($opcode===0x8){ $this->close(); return null; }
        if($opcode===0x9){ $this->sendControl(0xA,$payload); return $this->receive(); }
        if($opcode===0xA) return $this->receive();
        if($opcode!==0x1 && $opcode!==0x0) return $this->receive();
        return $payload;
    }

    public function timedOut(): bool {
        if(!$this->socket) return false;
        $meta=stream_get_meta_data($this->socket);
        return (bool)($meta['timed_out']??false);
    }

    private function readExact(int $length): string {
        $data='';
        while(strlen($data)<$length&&!feof($this->socket)){
            $chunk=fread($this->socket,$length-strlen($data));
            if($chunk===false||$chunk==='') break;
            $data.=$chunk;
        }
        return $data;
    }

    private function sendControl(int $opcode,string $payload=''): void {
        $payload=substr($payload,0,125); $mask=random_bytes(4);
        $frame=chr(0x80|$opcode).chr(0x80|strlen($payload)).$mask; $masked='';
        for($i=0;$i<strlen($payload);$i++)$masked.=$payload[$i]^$mask[$i%4];
        $this->writeAll($frame.$masked);
    }

    public function close(): void { if($this->socket){ @fclose($this->socket); $this->socket=null; } }
    public function isConnected(): bool { return is_resource($this->socket); }
    public function __destruct(){ $this->close(); }
}
