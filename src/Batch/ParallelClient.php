<?php
namespace ScratchByPHP\Batch;
final class ParallelClient
{
    public function run(array $requests,int $timeout=25,int $concurrency=8,int $retries=1,?callable $progress=null):array
    {
        if(!function_exists('curl_multi_init'))throw new \RuntimeException('curl_multi extension gerekli.');$out=[];$chunks=array_chunk($requests,max(1,$concurrency),true);$done=0;$total=count($requests);
        foreach($chunks as $chunk){$mh=curl_multi_init();$handles=[];foreach($chunk as $key=>$r){$url=(string)($r['url']??'');if(!str_starts_with($url,'https://'))throw new \InvalidArgumentException('Parallel request yalnızca HTTPS destekler.');$ch=curl_init($url);$t=(int)($r['timeout']??$timeout);curl_setopt_array($ch,[CURLOPT_RETURNTRANSFER=>true,CURLOPT_FOLLOWLOCATION=>true,CURLOPT_MAXREDIRS=>5,CURLOPT_TIMEOUT=>$t,CURLOPT_CONNECTTIMEOUT=>min(10,$t),CURLOPT_USERAGENT=>'ScratchByPHP/0.8.5']);curl_multi_add_handle($mh,$ch);$handles[(int)$ch]=[$key,$ch,$r];}
            do{$status=curl_multi_exec($mh,$active);if($active)curl_multi_select($mh,.25);}while($active&&$status===CURLM_OK);
            foreach($handles as [$key,$ch,$r]){$body=(string)curl_multi_getcontent($ch);$status=(int)curl_getinfo($ch,CURLINFO_RESPONSE_CODE);$error=curl_error($ch)?:null;$attempt=1;$max=(int)($r['retries']??$retries)+1;while(($error!==null||$status===429||$status>=500)&&$attempt<$max){$attempt++;curl_multi_remove_handle($mh,$ch);curl_close($ch);usleep(150000*$attempt);$ch=curl_init((string)$r['url']);curl_setopt_array($ch,[CURLOPT_RETURNTRANSFER=>true,CURLOPT_FOLLOWLOCATION=>true,CURLOPT_TIMEOUT=>(int)($r['timeout']??$timeout),CURLOPT_USERAGENT=>'ScratchByPHP/0.8.5']);$body=(string)curl_exec($ch);$status=(int)curl_getinfo($ch,CURLINFO_RESPONSE_CODE);$error=curl_error($ch)?:null;}
                $out[$key]=['status'=>$status,'body'=>$body,'json'=>json_decode($body,true)?:[],'error'=>$error,'attempts'=>$attempt,'ok'=>$error===null&&$status>=200&&$status<400];$done++;if($progress)$progress($done,$total,$key,$out[$key]);if(isset($handles[(int)$ch]))curl_multi_remove_handle($mh,$ch);if(is_resource($ch)||$ch instanceof \CurlHandle)curl_close($ch);}
            curl_multi_close($mh);
        }return $out;
    }
}
