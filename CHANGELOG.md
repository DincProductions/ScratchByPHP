# Changelog

## 0.8.5 — CloudDB Pro + Turkish Trending

- Reworked Turkish Trending discovery: it now searches Scratch studios for `türk`, `Türk` and `TÜRK` instead of searching projects/hashtags.
- Only studios whose titles contain `türk` case-insensitively are accepted.
- All projects in accepted studios are paged, collected and de-duplicated before ranking.
- Ranked projects include their Turkish source studio IDs/titles.
- Added `Scratch::searchStudios()` and `Studio::allProjects()`.
- Turkish Trending no longer performs a local description hashtag filter; it ranks the Scratch search candidates directly.
- Turkish Trending weights adjusted to views 35%, loves 15%, favorites 10%, freshness 40%.
- Fixed ProjectDiff Wizard error by using `toArray()` and adding `ProjectDiff::summary()` as a compatibility alias.
- Added `CloudDatabase::getToDB()` / `exportToMySQL()`.
- Added server-side JSON MySQL configuration support.
- Added prepared statements, transactions, strict identifier validation and optional upsert/auto-create.
- Added `CloudDatabase::planToDB()` for dry planning.
- Added `Scratch::turkishTrending()` / `turkishTrendProjects()`.
- Turkish Trending requires `#TürkçeTrend` in project description and ranks by views, loves, favorites and shared-date freshness.
- Added transparent `turkish_trend` rank/score/signals.
- Added Wizard actions for Turkish Trending and server-side CloudDB Pro MySQL profiles.
- MySQL credentials are never sent to the Wizard browser UI.

## 0.8.3 — Wizard Pro / Embedded Control Center

- Rebuilt Scratch API Wizard as a full ScratchByPHP site control center.
- Draggable, resizable and maximizable Tailwind modal.
- ScratchByPHP purple/orange/white visual identity and compact S logo.
- Server-side Scratch login/logout with PHP session persistence.
- Scratch password is not persisted and Scratch session ID is never exposed to browser JSON.
- CSRF protection for Wizard API requests.
- Project, User, Studio and Search tools.
- Authenticated single-account Project/User/Studio write actions.
- Cloud Variables, CloudDatabase and CloudRequests handle-once tools.
- Configurable custom CloudRequests handlers from PHP.
- Watcher baseline/tick tools using PHP session state.
- Project Analyzer / ProjectDiff tools.
- Doctor / Metrics / Circuit Breaker developer tools.
- Contextual PHP code generation for Wizard actions.
- Confirmation prompt for destructive actions.

## 0.8.2 — Reliability & Developer Experience

- Watcher 2.0 with persistent state, event queue, share change events, polling jitter and error backoff.
- Cache 2.0 prefix/model TTL rules and hit/miss metrics.
- Batch 2.0 concurrency limits, per-request timeout/retry and progress callbacks.
- Request metrics, configurable RetryPolicy and CircuitBreaker.
- Doctor 2.0 diagnostics (DNS, temp directory, memory/time limits, API latency).
- SB3 Validator.
- CLI 2.0 with JSON output and new user/studio/sb3 commands.
- Test Panel 2.0 additions.
- Tailwind-based movable Scratch API Wizard popup helper with public API explorer and PHP snippet generator.

## 0.8.1 — Watcher Fix

- Fixed watcher reading the normal 60-second project cache.
- Watcher now uses `Project::refresh()` for every tick.
- Added `views` monitoring and `onView()`.
- Fixed comment monitoring; Scratch project stats do not contain `stats.comments`.
- Comment detection now uses the latest comment ID from the comments endpoint.
- Added `baseline()`, `snapshot()`, `lastState()`, `onChange()` and `diffStates()`.
- Rebuilt the Watcher LIVE panel with 15–300 second monitoring windows.
- Integration panel now redacts `project_token` and similar secret/token fields.

## 0.8.0
- PHP 8.1 compatibility fix: replaced PHP 8.2-only `readonly class` DTO syntax with PHP 8.1-compatible readonly properties.

- Developer experience: Config, Response helpers, health check, debug collector
- Collections, pagination, DTOs and Comment model
- Cache drivers and PSR bridges
- Batch/parallel public requests and rate-limit enhancements
- Cloud 2.0, history, CloudDatabase and CloudRequests improvements
- Watcher, Analyzer 2.0, project diff and SB3 tools
- Fake testing API
- CLI, doctor and Scratch API compatibility checker
- PHPUnit/PHPStan scaffolding and API reference generator
- Bootstrap browser test center
- Optional Laravel integration skeleton



## 0.5.1

- GitHub README redesigned in Turkish and English
- README and documentation references standardized to the final ScratchByPHP brand filenames

- Brand assets added to documentation
- scratchattach / TimMcCool attribution added in THIRD_PARTY_NOTICES.md

Security hardening release:

- Authenticated HTTP requests are restricted to HTTPS `scratch.mit.edu` hosts/subdomains
- Automatic redirects are disabled for requests carrying Scratch credentials
- Logger redacts tokens, session IDs, cookies, authorization data and sensitive query values
- Scratch session IDs now have length/control-character validation
- Compressed session payload decoding is capped at 64 KiB
- Download helper rejects NUL paths, requires a writable target directory and uses `LOCK_EX`
- Plaintext account JSON remains intentional and unchanged


## 0.5.0

- Composer / Packagist metadata refreshed
- GitHub-ready repository structure
- Turkish-first and English documentation
- GitHub Pages documentation website
- Copyable code snippets
- CI workflow for PHP 8.1–8.4
- Contribution, security and issue templates
- Project player helper methods documented

## 0.4.x

- Registration assistant and JSON account profiles
- Cloud remote read/write verification
- Project, studio, user and authenticated write operations
- CloudRequests, CloudDatabase and project analysis
