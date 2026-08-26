<?php
namespace ScratchByPHP\Project;
use ScratchByPHP\Session; use ScratchByPHP\Http\HttpClient; use ScratchByPHP\Cloud\CloudConnection; use ScratchByPHP\Exceptions\ApiException; use ScratchByPHP\Analysis\ProjectAnalyzer;
final class Project {
    public function __construct(private string $id,private ?Session $session=null){}
    private function http():HttpClient{return $this->session?->http()??new HttpClient(['Accept'=>'application/json']);} private function rs():Session{if(!$this->session)throw new \LogicException('Authenticated Session gerekir.');return $this->session;}
    private function ok($r,string $a):array{if($r->status<200||$r->status>=300)throw new ApiException($a.' başarısız. HTTP '.$r->status.': '.$r->body);return $r->json()?:['success'=>true,'status'=>$r->status];}
    public function get():array{return $this->http()->get('https://api.scratch.mit.edu/projects/'.rawurlencode($this->id))->json();} public function id():string{return $this->id;} public function title():?string{return $this->get()['title']??null;} public function author():?string{return $this->get()['author']['username']??null;} public function views():int{return(int)($this->get()['stats']['views']??0);} public function loves():int{return(int)($this->get()['stats']['loves']??0);} public function favorites():int{return(int)($this->get()['stats']['favorites']??0);}
    public function comments(int $limit=20,int $offset=0):array{return $this->http()->get('https://api.scratch.mit.edu/users/'.rawurlencode((string)$this->author()).'/projects/'.$this->id.'/comments?limit='.$limit.'&offset='.$offset)->json();}
    public function remixes(int $limit=20,int $offset=0):array{return $this->http()->get('https://api.scratch.mit.edu/projects/'.$this->id.'/remixes?limit='.$limit.'&offset='.$offset)->json();}
    public function remixInfo():array{$g=$this->get();return ['parent'=>$g['remix']['parent']??null,'root'=>$g['remix']['root']??null,'is_remix'=>!empty($g['remix']['parent'])];}
    private function reaction(string $kind,bool $desired,int $max=4):array{$s=$this->rs();$u=(string)$s->username();$key=$kind==='loves'?'userLove':'userFavorite';$url='https://api.scratch.mit.edu/proxy/projects/'.$this->id.'/'.$kind.'/user/'.rawurlencode($u);$last=[];for($i=1;$i<=$max;$i++){$j=$this->ok($desired?$this->http()->post($url):$this->http()->delete($url),'Reaction');$last=$j;if(isset($j[$key])&&(bool)$j[$key]===$desired)return['success'=>true,'state'=>$desired,'attempts'=>$i,'scratch'=>$j];usleep(180000);}throw new ApiException($key.' beklenen duruma geçmedi: '.json_encode($last));}
    public function love():array{return $this->reaction('loves',true);} public function unlove():array{return $this->reaction('loves',false);} public function favorite():array{return $this->reaction('favorites',true);} public function unfavorite():array{return $this->reaction('favorites',false);}
    public function postComment(string $content,int|string|null $parentId=null,int|string|null $commenteeId=null):array{$this->rs();$d=['commentee_id'=>$commenteeId??'','content'=>trim($content),'parent_id'=>$parentId??''];if($d['content']==='')throw new \InvalidArgumentException('Yorum boş olamaz.');return $this->ok($this->http()->post('https://api.scratch.mit.edu/proxy/comments/project/'.$this->id.'/',$d,['Content-Type'=>'application/json','Referer'=>'https://scratch.mit.edu/projects/'.$this->id.'/']),'Yorum gönderme');}
    public function replyComment(string $c,int|string $p,int|string|null $ce=null):array{return $this->postComment($c,$p,$ce);} public function deleteComment(int|string $id):array{$this->rs();return $this->ok($this->http()->delete('https://api.scratch.mit.edu/proxy/comments/project/'.$this->id.'/comment/'.$id.'/'),'Yorum silme');} public function reportComment(int|string $id):array{$this->rs();return $this->ok($this->http()->delete('https://api.scratch.mit.edu/proxy/comments/project/'.$this->id.'/comment/'.$id.'/report'),'Yorum report');}
    public function share():array{$this->rs();return $this->ok($this->http()->put('https://api.scratch.mit.edu/proxy/projects/'.$this->id.'/share/'),'Share');} public function unshare():array{$this->rs();return $this->ok($this->http()->put('https://api.scratch.mit.edu/proxy/projects/'.$this->id.'/unshare/'),'Unshare');}
    public function setThumbnail(string $path):array{$this->rs();if(!is_file($path))throw new \InvalidArgumentException('Dosya yok.');$bytes=file_get_contents($path);return $this->ok($this->http()->post('https://scratch.mit.edu/internalapi/project/thumbnail/'.$this->id.'/set/',$bytes,['Content-Type'=>'application/octet-stream']),'Thumbnail');}
    public function rawJson():string{$g=$this->get();$token=$g['project_token']??null;if(!$token){$g=$this->http()->get('https://api.scratch.mit.edu/projects/'.$this->id)->json();$token=$g['project_token']??null;} $url='https://projects.scratch.mit.edu/'.$this->id.($token?'?token='.rawurlencode($token):'');$r=$this->http()->get($url);if($r->status<200||$r->status>=300)throw new ApiException('Project JSON alınamadı. HTTP '.$r->status);return $r->body;}
    public function downloadSb3(string $path):string{$data=$this->rawJson();if(file_put_contents($path,$data)===false)throw new ApiException('SB3 yazılamadı.');return $path;}
    public function analyze():ProjectAnalyzer{return ProjectAnalyzer::fromJson($this->rawJson());}
    public function cloud():CloudConnection{return new CloudConnection($this->id,$this->rs());}


    public function url(): string
    {
        return 'https://scratch.mit.edu/projects/' . rawurlencode((string)$this->id()) . '/';
    }

    public function embedUrl(): string
    {
        return 'https://scratch.mit.edu/projects/' . rawurlencode((string)$this->id()) . '/embed';
    }

    public function turbowarpUrl(): string
    {
        return 'https://turbowarp.org/' . rawurlencode((string)$this->id());
    }

    public function player(int $width = 485, int $height = 402, bool $allowFullscreen = true): string
    {
        $width = max(240, min(3840, $width));
        $height = max(180, min(2160, $height));
        $src = htmlspecialchars($this->embedUrl(), ENT_QUOTES, 'UTF-8');

        return sprintf(
            '<iframe src="%s" width="%d" height="%d" frameborder="0" scrolling="no"%s loading="lazy" referrerpolicy="strict-origin-when-cross-origin"></iframe>',
            $src,
            $width,
            $height,
            $allowFullscreen ? ' allowfullscreen' : ''
        );
    }

    public function turbowarpPlayer(int $width = 800, int $height = 600, array $options = []): string
    {
        $width = max(240, min(3840, $width));
        $height = max(180, min(2160, $height));

        $query = [];
        if (!empty($options['autoplay'])) $query['autoplay'] = '1';
        if (!empty($options['fullscreen'])) $query['fullscreen'] = '1';
        if (!empty($options['username'])) $query['username'] = (string)$options['username'];

        $url = $this->turbowarpUrl();
        if ($query) $url .= '?' . http_build_query($query);

        return sprintf(
            '<iframe src="%s" width="%d" height="%d" frameborder="0" allow="fullscreen; autoplay" allowfullscreen loading="lazy" referrerpolicy="strict-origin-when-cross-origin"></iframe>',
            htmlspecialchars($url, ENT_QUOTES, 'UTF-8'),
            $width,
            $height
        );
    }

    public function run(array $options = []): string
    {
        $engine = strtolower((string)($options['engine'] ?? 'scratch'));
        $width = (int)($options['width'] ?? ($engine === 'turbowarp' ? 800 : 485));
        $height = (int)($options['height'] ?? ($engine === 'turbowarp' ? 600 : 402));

        return match ($engine) {
            'scratch', 'official' => $this->player(
                $width,
                $height,
                (bool)($options['allow_fullscreen'] ?? true)
            ),
            'turbowarp', 'turbo' => $this->turbowarpPlayer($width, $height, $options),
            default => throw new \InvalidArgumentException('Desteklenmeyen player engine: ' . $engine),
        };
    }

}
