<?php
namespace ScratchByPHP\User;
use ScratchByPHP\Session; use ScratchByPHP\Cache\CacheInterface; use ScratchByPHP\Config; use ScratchByPHP\Http\HttpClient; use ScratchByPHP\Exceptions\ApiException;
final class User {
    public function __construct(private string $username, private ?Session $session=null,private ?HttpClient $client=null,private ?CacheInterface $cache=null,private ?Config $config=null){}
    private function http():HttpClient{return $this->client??$this->session?->http()??new HttpClient(['Accept'=>'application/json'],null,$this->config);}
    private function requireSession():Session{if(!$this->session)throw new \LogicException('Bu işlem için authenticated Session gerekir.');return $this->session;}
    private function ok($r,string $a):array{if($r->status<200||$r->status>=300)throw new ApiException($a.' başarısız. HTTP '.$r->status.': '.$r->body);return $r->json()?:['success'=>true,'status'=>$r->status];}
    public function get():array{$key='user:'.$this->username;if($this->cache&&$this->cache->has($key))return $this->cache->get($key);$d=$this->http()->get('https://api.scratch.mit.edu/users/'.rawurlencode($this->username))->json();$this->cache?->set($key,$d,(int)($this->config?->get('cache_ttl_user',$this->config?->get('cache_ttl',60)??60)??60));return $d;} public function refresh():array{$this->cache?->delete('user:'.$this->username);return $this->get();}
    public function projects(int $limit=20,int $offset=0):array{return $this->http()->get('https://api.scratch.mit.edu/users/'.rawurlencode($this->username).'/projects?limit='.$limit.'&offset='.$offset)->json();}
    public function followers(int $limit=20,int $offset=0):array{return $this->http()->get('https://api.scratch.mit.edu/users/'.rawurlencode($this->username).'/followers?limit='.$limit.'&offset='.$offset)->json();}
    public function following(int $limit=20,int $offset=0):array{return $this->http()->get('https://api.scratch.mit.edu/users/'.rawurlencode($this->username).'/following?limit='.$limit.'&offset='.$offset)->json();}
    public function favorites(int $limit=20,int $offset=0):array{return $this->http()->get('https://api.scratch.mit.edu/users/'.rawurlencode($this->username).'/favorites?limit='.$limit.'&offset='.$offset)->json();}
    public function studios(int $limit=20,int $offset=0):array{return $this->http()->get('https://api.scratch.mit.edu/users/'.rawurlencode($this->username).'/studios/curate?limit='.$limit.'&offset='.$offset)->json();}
    public function messageCount():int{return (int)($this->http()->get('https://api.scratch.mit.edu/users/'.rawurlencode($this->username).'/messages/count')->json()['count']??0);}
    public function activity(int $limit=100):string{return $this->http()->get('https://scratch.mit.edu/messages/ajax/user-activity/?user='.rawurlencode($this->username).'&max='.$limit,['Accept'=>'text/html'])->body;}
    public function username():string{return $this->username;} public function bio():?string{return $this->get()['profile']['bio']??null;} public function country():?string{return $this->get()['profile']['country']??null;} public function status():?string{return $this->get()['profile']['status']??null;}
    public function follow():array{$s=$this->requireSession();return $this->ok($this->http()->put('https://scratch.mit.edu/site-api/users/followers/'.rawurlencode($this->username).'/add/?usernames='.rawurlencode((string)$s->username())),'Follow');}
    public function unfollow():array{$s=$this->requireSession();return $this->ok($this->http()->put('https://scratch.mit.edu/site-api/users/followers/'.rawurlencode($this->username).'/remove/?usernames='.rawurlencode((string)$s->username())),'Unfollow');}
    public function postComment(string $content,int|string|null $parentId=null,int|string|null $commenteeId=null):array{$this->requireSession();$d=['commentee_id'=>$commenteeId??'','content'=>trim($content),'parent_id'=>$parentId??''];if($d['content']==='')throw new \InvalidArgumentException('Yorum boş olamaz.');return $this->ok($this->http()->post('https://scratch.mit.edu/site-api/comments/user/'.rawurlencode($this->username).'/add/',$d,['Content-Type'=>'application/json']),'Profil yorumu');}
    public function deleteComment(int|string $id):array{$this->requireSession();return $this->ok($this->http()->post('https://scratch.mit.edu/site-api/comments/user/'.rawurlencode($this->username).'/del/',['id'=>(string)$id]),'Profil yorumu silme');}
    public function reportComment(int|string $id):array{$this->requireSession();return $this->ok($this->http()->post('https://scratch.mit.edu/site-api/comments/user/'.rawurlencode($this->username).'/rep/',['id'=>(string)$id]),'Profil yorumu report');}
    public function setBio(string $text):array{$this->requireSession();return $this->ok($this->http()->put('https://scratch.mit.edu/site-api/users/all/'.rawurlencode($this->username).'/', ['bio'=>$text]),'Bio güncelleme');}
    public function setStatus(string $text):array{$this->requireSession();return $this->ok($this->http()->put('https://scratch.mit.edu/site-api/users/all/'.rawurlencode($this->username).'/', ['status'=>$text]),'Status güncelleme');}
    public function setProfilePicture(string $path):array{$this->requireSession();if(!is_file($path))throw new \InvalidArgumentException('Görsel bulunamadı.');return $this->ok($this->http()->multipart('POST','https://scratch.mit.edu/site-api/users/all/'.rawurlencode($this->username).'/', ['file'=>new \CURLFile($path)]),'Profil resmi güncelleme');}

    public function toArray(): array { return $this->get(); }
    public function toJson(int $flags=JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES): string { return (string)json_encode($this->get(),$flags); }
    public function profileDto(): \ScratchByPHP\DTO\UserProfile { return \ScratchByPHP\DTO\UserProfile::fromArray($this->get()); }
    public function projectsPaginator(): \ScratchByPHP\Pagination\Paginator { return new \ScratchByPHP\Pagination\Paginator(fn($l,$o)=>$this->projects($l,$o),fn($r)=>new \ScratchByPHP\Project\Project((string)($r['id']??''),$this->session,$this->client,$this->cache,$this->config)); }
    public function projectsCollection(int $limit=20,int $offset=0): \ScratchByPHP\Collections\Collection { return new \ScratchByPHP\Collections\Collection(array_map(fn($r)=>new \ScratchByPHP\Project\Project((string)($r['id']??''),$this->session,$this->client,$this->cache,$this->config),$this->projects($limit,$offset))); }

}
