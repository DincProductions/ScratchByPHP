<?php
namespace ScratchByPHP\Auth;
use ScratchByPHP\Exceptions\LoginException;
use ScratchByPHP\Http\HttpClient;
final class LoginManager {
    public static function login(string $username, string $password): array {
        $csrf = 'a';
        $cookie = 'scratchcsrftoken=a; scratchlanguage=en';
        $client = new HttpClient([], $cookie);
        $res = $client->post('https://scratch.mit.edu/login/', [
            'username' => $username,
            'password' => $password,
        ], [
            'X-CSRFToken' => 'a',
            'X-Requested-With' => 'XMLHttpRequest',
            'Referer' => 'https://scratch.mit.edu',
            'Content-Type' => 'application/json'
        ]);
        if ($res->status < 200 || $res->status >= 400) throw new LoginException('Scratch girişi başarısız. HTTP ' . $res->status);
        $sessionId = null;
        foreach ($res->headers['set-cookie'] ?? [] as $setCookie) {
            if (preg_match('/scratchsessionsid=([^;]+)/', $setCookie, $m)) { $sessionId = $m[1]; break; }
        }
        if (!$sessionId) throw new LoginException('Scratch session kimliği alınamadı. Endpoint değişmiş olabilir.');
        $sessionId = TokenManager::normalizeSessionId($sessionId);
        return ['sessionId' => $sessionId, 'csrfToken' => $csrf];
    }
}
