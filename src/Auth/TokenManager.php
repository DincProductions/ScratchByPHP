<?php
namespace ScratchByPHP\Auth;

use ScratchByPHP\Http\HttpClient;

final class TokenManager {
    public static function fromSessionId(string $sessionId, ?string $knownCsrf = null): array {
        $sessionId = self::normalizeSessionId($sessionId);
        $decoded = self::decodeSessionId($sessionId);

        $username = $decoded['username'] ?? null;
        $xToken = $decoded['token'] ?? null;
        $tokenSource = $xToken ? 'session-id' : null;

        $csrf = $knownCsrf ?: 'a';
        $cookie = 'scratchsessionsid=' . $sessionId . '; scratchcsrftoken=' . $csrf . '; scratchlanguage=en';
        $client = new HttpClient([
            'X-CSRFToken' => $csrf,
            'X-Requested-With' => 'XMLHttpRequest',
            'Referer' => 'https://scratch.mit.edu/',
            'Origin' => 'https://scratch.mit.edu',
            'Accept' => 'application/json',
        ], $cookie);

        // Scratchattach currently POSTs /session. Some deployments/proxies can return 405,
        // so diagnostics also try GET. Authentication must not depend on this request because
        // the X-Token is encoded inside the Scratch session id itself.
        $post = $client->post('https://scratch.mit.edu/session', null, ['Content-Length' => '0']);
        $probe = $post;
        $probeMethod = 'POST';
        $getStatus = null;

        if ($post->status === 405 || $post->status === 404) {
            $get = $client->get('https://scratch.mit.edu/session/');
            $getStatus = $get->status;
            if ($get->status >= 200 && $get->status < 300) {
                $probe = $get;
                $probeMethod = 'GET';
            }
        }

        $json = $probe->json();
        if (isset($json['user']['username'])) $username = (string)$json['user']['username'];
        if (!$xToken && isset($json['user']['token'])) {
            $xToken = (string)$json['user']['token'];
            $tokenSource = 'session-endpoint';
        }

        return [
            'username' => $username,
            'xToken' => $xToken,
            'csrfToken' => $csrf,
            'sessionStatus' => $probe->status,
            'sessionPostStatus' => $post->status,
            'sessionGetStatus' => $getStatus,
            'sessionProbeMethod' => $probeMethod,
            'tokenSource' => $tokenSource,
            'decodedSession' => !empty($decoded),
        ];
    }

    public static function normalizeSessionId(string $sessionId): string {
        $sessionId = trim($sessionId);
        $sessionId = rawurldecode($sessionId);
        if (strlen($sessionId) >= 2 && $sessionId[0] === '"' && $sessionId[strlen($sessionId)-1] === '"') {
            $sessionId = substr($sessionId, 1, -1);
        }
        return trim($sessionId);
    }

    private static function base64UrlDecode(string $value): string|false {
        $value = strtr($value, '-_', '+/');
        $value .= str_repeat('=', (4 - strlen($value) % 4) % 4);
        return base64_decode($value, true);
    }

    private static function decodeSessionId(string $sessionId): array {
        try {
            $parts = explode(':', $sessionId);
            if (count($parts) < 3) return [];

            $p1 = $parts[0];
            $compressed = str_starts_with($p1, '.');
            if ($compressed) $p1 = substr($p1, 1);

            $raw = self::base64UrlDecode($p1);
            if ($raw === false) return [];

            if ($compressed) {
                $inflated = @gzuncompress($raw);
                if ($inflated === false) $inflated = @gzinflate($raw);
                if ($inflated === false && function_exists('zlib_decode')) $inflated = @zlib_decode($raw);
                if ($inflated === false) return [];
                $raw = $inflated;
            }

            $json = json_decode($raw, true);
            return is_array($json) ? $json : [];
        } catch (\Throwable) {
            return [];
        }
    }
}
