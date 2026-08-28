<?php
namespace ScratchByPHP;

use ScratchByPHP\Auth\TokenManager;
use ScratchByPHP\Http\HttpClient;
use ScratchByPHP\User\User;
use ScratchByPHP\Project\Project;
use ScratchByPHP\Studio\Studio;
use ScratchByPHP\Cloud\CloudConnection;
use ScratchByPHP\Support\Logger;
use ScratchByPHP\Config;
use ScratchByPHP\Debug\DebugCollector;
use ScratchByPHP\Observability\Metrics;
use ScratchByPHP\Http\{RetryPolicy,CircuitBreaker};

final class Session {
    private ?Logger $logger = null;
    private HttpClient $http;
    private ?string $xToken = null;
    private ?string $csrfToken = null;
    private ?string $resolvedUsername = null;
    private ?int $sessionProbeStatus = null;
    private ?int $sessionPostStatus = null;
    private ?int $sessionGetStatus = null;
    private ?string $sessionProbeMethod = null;
    private ?string $tokenSource = null;
    private bool $decodedSession = false;

    public function __construct(private string $sessionId, private ?string $username = null, ?string $csrfToken = null, private ?Config $config=null, private ?DebugCollector $debugCollector=null, private ?Metrics $metrics=null, private ?RetryPolicy $retryPolicy=null, private ?CircuitBreaker $circuitBreaker=null) {
        $tokens = TokenManager::fromSessionId($sessionId, $csrfToken);
        $this->resolvedUsername = $tokens['username'] ?? $username;
        $this->xToken = $tokens['xToken'] ?? null;
        $this->csrfToken = $tokens['csrfToken'] ?? 'a';
        $this->sessionProbeStatus = $tokens['sessionStatus'] ?? null;
        $this->sessionPostStatus = $tokens['sessionPostStatus'] ?? null;
        $this->sessionGetStatus = $tokens['sessionGetStatus'] ?? null;
        $this->sessionProbeMethod = $tokens['sessionProbeMethod'] ?? null;
        $this->tokenSource = $tokens['tokenSource'] ?? null;
        $this->decodedSession = (bool)($tokens['decodedSession'] ?? false);

        $cookie = 'scratchsessionsid=' . $sessionId . '; scratchcsrftoken=' . $this->csrfToken . '; scratchlanguage=en';
        $headers = [
            'Accept' => 'application/json',
            'Referer' => 'https://scratch.mit.edu',
            'X-Requested-With' => 'XMLHttpRequest',
            'X-CSRFToken' => $this->csrfToken,
        ];
        if ($this->xToken) $headers['X-Token'] = $this->xToken;
        $this->http = new HttpClient($headers, $cookie, $this->config); $this->http->setDebugCollector($this->debugCollector)->setMetrics($this->metrics)->setRetryPolicy($this->retryPolicy)->setCircuitBreaker($this->circuitBreaker);
    }

    public function username(): ?string { return $this->resolvedUsername; }
    public function sessionId(): string { return $this->sessionId; }
    public function xToken(): ?string { return $this->xToken; }
    public function csrfToken(): ?string { return $this->csrfToken; }
    public function http(): HttpClient { return $this->http; }
    public function user(string $username): User { return new User($username, $this, $this->http, null, $this->config); }
    public function project(int|string $id): Project { return new Project((string)$id, $this, $this->http, null, $this->config); }
    public function studio(int|string $id): Studio { return new Studio((string)$id, $this, $this->http, null, $this->config); }
    public function cloud(int|string $projectId): CloudConnection { return new CloudConnection((string)$projectId, $this); }

    public function setProxy(?string $proxy): self { $this->http->setProxy($proxy); return $this; }
    public function setRetries(int $retries, int $delayMs=180): self { $this->http->setRetries($retries,$delayMs); return $this; }
    public function enableLogger(?string $file=null): self { $this->logger = new Logger($file, true); $this->http->setLogger($this->logger); return $this; }
    public function disableLogger(): self { $this->logger?->disable(); return $this; }

    public function messages(int $limit=40,int $offset=0,?string $filter=null): array {
        $url='https://api.scratch.mit.edu/users/'.rawurlencode((string)$this->username()).'/messages?limit='.$limit.'&offset='.$offset;
        if($filter!==null&&$filter!=='')$url.='&filter='.rawurlencode($filter);
        return $this->http->get($url)->json();
    }
    public function adminMessages(int $limit=40,int $offset=0): array { return $this->http->get('https://api.scratch.mit.edu/users/'.rawurlencode((string)$this->username()).'/messages/admin?limit='.$limit.'&offset='.$offset)->json(); }
    public function searchProjects(string $query='',string $mode='trending',string $language='en',int $limit=40,int $offset=0): array { return $this->http->get('https://api.scratch.mit.edu/search/projects?limit='.$limit.'&offset='.$offset.'&language='.rawurlencode($language).'&mode='.rawurlencode($mode).'&q='.rawurlencode($query))->json(); }
    public function exploreProjects(string $query='*',string $mode='trending',string $language='en',int $limit=40,int $offset=0): array { return $this->http->get('https://api.scratch.mit.edu/explore/projects?limit='.$limit.'&offset='.$offset.'&language='.rawurlencode($language).'&mode='.rawurlencode($mode).'&q='.rawurlencode($query))->json(); }
    public function searchStudios(string $query='',string $mode='trending',string $language='en',int $limit=40,int $offset=0): array { return $this->http->get('https://api.scratch.mit.edu/search/studios?limit='.$limit.'&offset='.$offset.'&language='.rawurlencode($language).'&mode='.rawurlencode($mode).'&q='.rawurlencode($query))->json(); }
    public function exploreStudios(string $query='',string $mode='trending',string $language='en',int $limit=40,int $offset=0): array { return $this->http->get('https://api.scratch.mit.edu/explore/studios?limit='.$limit.'&offset='.$offset.'&language='.rawurlencode($language).'&mode='.rawurlencode($mode).'&q='.rawurlencode($query))->json(); }
    public function news(int $limit=10,int $offset=0): array { return $this->http->get('https://api.scratch.mit.edu/news?limit='.$limit.'&offset='.$offset)->json(); }

    public function debug(): array {
        return [
            'username' => $this->username(),
            'authenticated' => (bool)$this->username(),
            'session_probe_http' => $this->sessionProbeStatus,
            'sessionId' => $this->sessionId !== '' ? '[available]' : '[missing]',
            'csrfToken' => $this->csrfToken ? '[available]' : '[missing]',
            'csrf_mode' => $this->csrfToken === 'a' ? 'Scratch-compatible static token' : 'custom',
            'xToken' => $this->xToken ? '[available]' : '[missing]',
            'xToken_source' => $this->tokenSource,
            'session_id_decoded' => $this->decodedSession,
            'session_probe_method' => $this->sessionProbeMethod,
            'session_post_http' => $this->sessionPostStatus,
            'session_get_http' => $this->sessionGetStatus,
        ];
    }

    public function authDiagnostics(?int $projectId = null): array {
        $out = [
            'session' => $this->debug(),
            'request_profile' => $this->http->debugProfile(),
            'explanation' => [
                'x_token_required_for_writes' => true,
                'x_token_source' => $this->tokenSource,
                'session_endpoint_is_not_required_when_session_id_decode_succeeds' => $this->decodedSession,
            ],
        ];

        $post = $this->http->post('https://scratch.mit.edu/session', null, ['Content-Length' => '0']);
        $postJson = $post->json();
        $out['session_endpoint_post'] = [
            'method' => 'POST',
            'http' => $post->status,
            'returned_username' => $postJson['user']['username'] ?? null,
            'returned_x_token' => isset($postJson['user']['token']) ? '[available]' : '[missing]',
            'body_preview' => substr($post->body, 0, 300),
        ];

        if ($post->status === 405 || $post->status === 404) {
            $get = $this->http->get('https://scratch.mit.edu/session/');
            $getJson = $get->json();
            $out['session_endpoint_get_fallback'] = [
                'method' => 'GET',
                'http' => $get->status,
                'returned_username' => $getJson['user']['username'] ?? null,
                'returned_x_token' => isset($getJson['user']['token']) ? '[available]' : '[missing]',
                'body_preview' => substr($get->body, 0, 300),
            ];
        }

        if ($projectId !== null && $this->username()) {
            $r = $this->http->get('https://api.scratch.mit.edu/users/' . rawurlencode($this->username()) . '/projects/' . $projectId . '/visibility');
            $out['authenticated_visibility_probe'] = [
                'http' => $r->status,
                'body_preview' => substr($r->body, 0, 300),
            ];
        }
        return $out;
    }
}
