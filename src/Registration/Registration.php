<?php
declare(strict_types=1);

namespace ScratchByPHP\Registration;

use ScratchByPHP\Http\HttpClient;

final class Registration
{
    public function __construct(private ?HttpClient $http = null)
    {
        $this->http ??= new HttpClient([
            'Accept' => 'application/json',
            'Referer' => 'https://scratch.mit.edu/join',
        ]);
    }

    /**
     * Generates one candidate username and strong password.
     * This method does NOT create an account.
     */
    public function generateCredentials(
        string $prefix = 'ScratchUser',
        int $suffixLength = 7,
        int $passwordLength = 18
    ): array {
        $prefix = preg_replace('/[^A-Za-z0-9_-]/', '', $prefix) ?: 'ScratchUser';
        $prefix = substr($prefix, 0, 12);
        $suffixLength = max(4, min(12, $suffixLength));
        $passwordLength = max(12, min(64, $passwordLength));

        return [
            'username' => $prefix . '_' . $this->randomAlphaNumeric($suffixLength),
            'password' => $this->generatePassword($passwordLength),
            'generated_at' => gmdate('c'),
        ];
    }

    /**
     * Generates an available + allowed Scratch username.
     * Uses Scratch's username-check endpoint and stops after a small number
     * of attempts to avoid hammering the service.
     */
    public function generateAvailableCredentials(
        string $prefix = 'ScratchUser',
        int $maxAttempts = 6
    ): array {
        $maxAttempts = max(1, min(10, $maxAttempts));
        $last = null;

        for ($i = 1; $i <= $maxAttempts; $i++) {
            $credentials = $this->generateCredentials($prefix);
            $check = $this->checkUsername($credentials['username']);
            $last = ['credentials' => $credentials, 'check' => $check, 'attempt' => $i];

            if ($check['available'] === true) {
                return $last;
            }

            if ($i < $maxAttempts) {
                usleep(350000);
            }
        }

        return $last ?? [
            'credentials' => $this->generateCredentials($prefix),
            'check' => ['available' => null, 'allowed' => null, 'message' => 'Kontrol yapılamadı.'],
            'attempt' => 0,
        ];
    }

    /**
     * Checks whether the username is both unused and accepted by Scratch.
     */
    public function checkUsername(string $username): array
    {
        $username = trim($username);
        if ($username === '') {
            return [
                'username' => $username,
                'available' => false,
                'allowed' => false,
                'http' => 0,
                'message' => 'Kullanıcı adı boş.',
                'raw' => null,
            ];
        }

        $url = 'https://api.scratch.mit.edu/accounts/checkusername/' . rawurlencode($username);
        $response = $this->http->get($url);
        $json = $response->json();

        $message = strtolower((string)($json['msg'] ?? ''));
        $available = $message === 'valid username';
        $allowed = !in_array($message, ['bad username', 'invalid username'], true);

        return [
            'username' => $username,
            'available' => $available,
            'allowed' => $allowed,
            'http' => $response->status,
            'message' => (string)($json['msg'] ?? ('HTTP ' . $response->status)),
            'raw' => $json,
        ];
    }

    public function validatePassword(string $password): array
    {
        $issues = [];
        if (strlen($password) < 6) $issues[] = 'En az 6 karakter olmalı.';
        if (!preg_match('/[A-Z]/', $password)) $issues[] = 'En az bir büyük harf önerilir.';
        if (!preg_match('/[a-z]/', $password)) $issues[] = 'En az bir küçük harf önerilir.';
        if (!preg_match('/\d/', $password)) $issues[] = 'En az bir rakam önerilir.';

        return [
            'valid_for_scratch_minimum' => strlen($password) >= 6,
            'strong' => strlen($password) >= 12 && count($issues) <= 1,
            'issues' => $issues,
        ];
    }

    public function joinUrl(): string
    {
        return 'https://scratch.mit.edu/join';
    }

    /**
     * Creates the contents of a credentials TXT for ONE user-created account.
     * Nothing is persisted by this method.
     */
    public function credentialsText(
        string $username,
        string $password,
        ?string $email = null
    ): string {
        $lines = [
            'ScratchByPHP Account Credentials',
            '================================',
            '',
            'Username: ' . $username,
            'Password: ' . $password,
        ];

        if ($email !== null && trim($email) !== '') {
            $lines[] = 'Email: ' . trim($email);
        }

        $lines[] = 'Saved: ' . date('Y-m-d H:i:s');
        $lines[] = '';
        $lines[] = 'Security note: Keep this file private. Delete it when you no longer need it.';

        return implode(PHP_EOL, $lines) . PHP_EOL;
    }


    /**
     * Creates a portable JSON credentials profile for ONE account.
     * Warning: password is stored in plaintext inside the JSON.
     */
    public function credentialsJson(
        string $username,
        string $password,
        ?string $email = null
    ): string {
        $data = [
            'format' => 'scratchbyphp-account',
            'version' => 1,
            'username' => $username,
            'password' => $password,
            'email' => ($email !== null && trim($email) !== '') ? trim($email) : null,
            'saved_at' => date(DATE_ATOM),
        ];

        return json_encode(
            $data,
            JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
        ) . PHP_EOL;
    }

    public function parseCredentialsJson(string $json): array
    {
        $data = json_decode($json, true);

        if (!is_array($data)) {
            throw new \InvalidArgumentException('Geçersiz JSON.');
        }

        if (($data['format'] ?? null) !== 'scratchbyphp-account') {
            throw new \InvalidArgumentException('Bu dosya ScratchByPHP hesap JSON formatında değil.');
        }

        $username = trim((string)($data['username'] ?? ''));
        $password = (string)($data['password'] ?? '');

        if ($username === '' || $password === '') {
            throw new \InvalidArgumentException('JSON içinde username veya password eksik.');
        }

        return [
            'username' => $username,
            'password' => $password,
            'email' => isset($data['email']) ? (string)$data['email'] : null,
            'saved_at' => isset($data['saved_at']) ? (string)$data['saved_at'] : null,
            'format' => 'scratchbyphp-account',
            'version' => (int)($data['version'] ?? 1),
        ];
    }

    private function randomAlphaNumeric(int $length): string
    {
        $alphabet = 'ABCDEFGHJKLMNPQRSTUVWXYZabcdefghijkmnopqrstuvwxyz23456789';
        $out = '';
        $max = strlen($alphabet) - 1;

        for ($i = 0; $i < $length; $i++) {
            $out .= $alphabet[random_int(0, $max)];
        }
        return $out;
    }

    private function generatePassword(int $length): string
    {
        // Ensure useful character variety, then shuffle securely enough for
        // generated credentials.
        $upper = 'ABCDEFGHJKLMNPQRSTUVWXYZ';
        $lower = 'abcdefghijkmnopqrstuvwxyz';
        $digits = '23456789';
        $symbols = '!@#$%*-_=+';
        $all = $upper . $lower . $digits . $symbols;

        $chars = [
            $upper[random_int(0, strlen($upper) - 1)],
            $lower[random_int(0, strlen($lower) - 1)],
            $digits[random_int(0, strlen($digits) - 1)],
            $symbols[random_int(0, strlen($symbols) - 1)],
        ];

        while (count($chars) < $length) {
            $chars[] = $all[random_int(0, strlen($all) - 1)];
        }

        for ($i = count($chars) - 1; $i > 0; $i--) {
            $j = random_int(0, $i);
            [$chars[$i], $chars[$j]] = [$chars[$j], $chars[$i]];
        }

        return implode('', $chars);
    }
}
