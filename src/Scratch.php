<?php
namespace ScratchByPHP;

use ScratchByPHP\Auth\LoginManager;

final class Scratch {
    public const VERSION = '0.5.0';

    public function loginWithSessionId(string $sessionId, ?string $username = null): Session {
        return new Session($sessionId, $username);
    }

    public function login(string $username, string $password): Session {
        $data = LoginManager::login($username, $password);
        return new Session($data['sessionId'], $username, $data['csrfToken'] ?? null);
    }


    public function registration(): Registration\Registration {
        return new Registration\Registration();
    }

    public function user(string $username): User\User { return new User\User($username, null); }
    public function project(int|string $id): Project\Project { return new Project\Project((string)$id, null); }
    public function studio(int|string $id): Studio\Studio { return new Studio\Studio((string)$id, null); }
    public function searchProjects(string $query='',string $mode='trending',string $language='en',int $limit=40,int $offset=0): array { return (new Http\HttpClient())->get('https://api.scratch.mit.edu/search/projects?limit='.$limit.'&offset='.$offset.'&language='.rawurlencode($language).'&mode='.rawurlencode($mode).'&q='.rawurlencode($query))->json(); }
    public function exploreProjects(string $query='*',string $mode='trending',string $language='en',int $limit=40,int $offset=0): array { return (new Http\HttpClient())->get('https://api.scratch.mit.edu/explore/projects?limit='.$limit.'&offset='.$offset.'&language='.rawurlencode($language).'&mode='.rawurlencode($mode).'&q='.rawurlencode($query))->json(); }
}
