# ScratchByPHP API Reference

Generated from public PHP methods.

## `Support/Logger.php`

- `__construct(private ?string $file = null, private bool $enabled = false)`
- `enable(?string $file = null)`
- `disable()`
- `log(string $level, string $message, array $context = [])`

## `Support/RateLimiter.php`

- `maxRequests(int $max)`
- `perSeconds(int $seconds)`
- `acquire(string $key='default')`

## `Interop/Psr18HttpClientAdapter.php`

- `__construct(private HttpClient $http)`
- `sendRequest(object $request)`
- `raw()`

## `Interop/Psr3LoggerAdapter.php`

- `__construct(private object $logger)`
- `log(string $level,string $message,array $context=[])`

## `Debug/DebugCollector.php`

- `enable()`
- `disable()`
- `enabled()`
- `record(string $type,array $data=[])`
- `events()`
- `requests()`
- `clear()`

## `Batch/ParallelClient.php`

- `run(array $requests,int $timeout=25,int $concurrency=8,int $retries=1,?callable $progress=null)`

## `Batch/BatchBuilder.php`

- `__construct(private Scratch $scratch)`
- `project(int|string $id,?string $key=null)`
- `user(string $u,?string $key=null)`
- `studio(int|string $id,?string $key=null)`
- `raw(string $key,string $url,array $options=[])`
- `concurrency(int $n)`
- `timeout(int $s)`
- `retries(int $n)`
- `onProgress(callable $cb)`
- `run()`
- `failures()`
- `lastResults()`

## `Watch/Watcher.php`

- `__construct(private Scratch $scratch)`
- `interval(float $seconds)`
- `project(int|string $id)`

## `Watch/ProjectWatch.php`

- `__construct(private Scratch $scratch,private string $id,private float $interval=15.0)`
- `onView(callable $c)`
- `onLove(callable $c)`
- `onFavorite(callable $c)`
- `onRemix(callable $c)`
- `onComment(callable $c)`
- `onShare(callable $c)`
- `onChange(callable $c)`
- `persistTo(string $file)`
- `jitter(float $fraction=0.15)`
- `backoff(float $factor=1.5)`
- `queue()`
- `snapshot()`
- `baseline()`
- `lastState()`
- `tick()`
- `run(?int $cycles=null)`

## `Watch/EventQueue.php`

- `push(array $event)`
- `all()`
- `drain()`
- `count()`
- `getIterator()`

## `Config.php`

- `__construct(array $values = [])`
- `get(string $key, mixed $default=null)`
- `set(string $key, mixed $value)`
- `all()`
- `toArray()`
- `toJson(int $flags=JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES)`

## `Collections/Collection.php`

- `__construct(private array $items = [])`
- `all()`
- `first(mixed $default=null)`
- `last(mixed $default=null)`
- `map(callable $fn)`
- `filter(?callable $fn=null)`
- `each(callable $fn)`
- `take(int $n)`
- `skip(int $n)`
- `pluck(string $key)`
- `sortBy(callable|string $by)`
- `sortByDesc(callable|string $by)`
- `where(callable $fn)`
- `count()`
- `isEmpty()`
- `toArray()`
- `toJson(int $flags=JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES)`
- `getIterator()`
- `jsonSerialize()`
- `offsetExists(mixed $o)`
- `offsetGet(mixed $o)`
- `offsetSet(mixed $o,mixed $v)`
- `offsetUnset(mixed $o)`

## `Http/Response.php`

- `__construct(public readonly int $status,public readonly string $body,public readonly array $headers=[])`
- `statusCode()`
- `ok()`
- `failed()`
- `header(string $name,mixed $default=null)`
- `headers()`
- `text()`
- `json(bool $assoc=true)`
- `toArray()`
- `toJson(int $flags=0)`
- `jsonSerialize()`

## `Http/RetryPolicy.php`

- `maxAttempts(int $attempts)`
- `backoff(string $mode)`
- `baseDelayMs(int $ms)`
- `retryOn(array $statuses)`
- `attempts()`
- `shouldRetryStatus(int $status)`
- `delayMs(int $attempt, ?int $retryAfterSeconds=null)`
- `toArray()`

## `Http/CircuitBreaker.php`

- `__construct(private int $threshold=5, private int $cooldownSeconds=30)`
- `threshold(int $value)`
- `cooldown(int $seconds)`
- `allow()`
- `success()`
- `failure()`
- `reset()`
- `state()`

## `Http/HttpClient.php`

- `__construct(private array $defaultHeaders = [], private ?string $cookie = null, ?Config $config = null)`
- `setDebugCollector(?DebugCollector $debug)`
- `setMetrics(?Metrics $metrics)`
- `setRetryPolicy(?RetryPolicy $policy)`
- `setCircuitBreaker(?CircuitBreaker $breaker)`
- `setCookie(?string $cookie)`
- `setDefaultHeader(string $name, string $value)`
- `setProxy(?string $proxy)`
- `setRetries(int $retries, int $delayMs=180)`
- `setLogger(?Logger $logger)`
- `debugProfile()`
- `request(string $method, string $url, array|string|null $data = null, array $headers = [])`
- `multipart(string $method,string $url,array $fields,array $headers=[])`
- `download(string $url,string $path,array $headers=[])`
- `get(string $url,array $headers=[])`
- `post(string $url,array|string|null $data=null,array $headers=[])`
- `put(string $url,array|string|null $data=null,array $headers=[])`
- `delete(string $url,array|string|null $data=null,array $headers=[])`

## `Studio/Studio.php`

- `__construct(private string $id,private ?Session $session=null,private ?HttpClient $client=null,private ?CacheInterface $cache=null,private ?Config $config=null)`
- `get()`
- `refresh()`
- `id()`
- `title()`
- `projects(int $l=20,int $o=0)`
- `curators(int $l=20,int $o=0)`
- `managers(int $l=20,int $o=0)`
- `comments(int $l=20,int $o=0)`
- `commentReplies(int|string $id,int $l=20,int $o=0)`
- `addProject(int|string $p)`
- `removeProject(int|string $p)`
- `acceptInvite()`
- `yourRole()`
- `inviteCurator(string $u)`
- `promoteCurator(string $u)`
- `removeCurator(string $u)`
- `leave()`
- `transferOwnership(string $u,string $password)`
- `follow()`
- `unfollow()`
- `setFields(array $f)`
- `setTitle(string $t)`
- `setDescription(string $d)`
- `openProjects()`
- `closeProjects()`
- `setThumbnail(string $path)`
- `postComment(string $c,int|string|null $p=null,int|string|null $ce=null)`
- `replyComment(string $c,int|string $p,int|string|null $ce=null)`
- `deleteComment(int|string $id)`
- `reportComment(int|string $id)`
- `toArray()`
- `toJson(int $flags=JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES)`
- `infoDto()`
- `projectsPaginator()`
- `curatorsCollection(int $limit=20,int $offset=0)`
- `commentsCollection(int $limit=20,int $offset=0)`

## `Cli/Application.php`

- `run(array $argv)`

## `Scratch.php`

- `__construct(array|Config $config=[])`
- `config(?array $values=null)`
- `cache(mixed $config='memory')`
- `cacheRules(array $rules)`
- `metrics()`
- `retry()`
- `circuitBreaker()`
- `wizard()`
- `cacheStore()`
- `debug()`
- `loginWithSessionId(string $sid,?string $username=null)`
- `login(string $username,string $password)`
- `registration()`
- `user(string $u)`
- `project(int|string $id)`
- `studio(int|string $id)`
- `searchProjects(string $q='',string $mode='trending',string $language='en',int $limit=40,int $offset=0)`
- `exploreProjects(string $q='*',string $mode='trending',string $language='en',int $limit=40,int $offset=0)`
- `projects(array $ids)`
- `batch()`
- `parallel(array $requests,int $timeout=25,int $concurrency=8,int $retries=1,?callable $progress=null)`
- `watch()`
- `compareProjects(int|string $a,int|string $b)`
- `healthCheck(bool $network=false)`

## `Testing/FakeScratch.php`

- `fakeProject(int|string $id,array $data)`
- `fakeUser(string $u,array $data)`
- `fakeStudio(int|string $id,array $data)`
- `project(int|string $id)`
- `user(string $u)`
- `studio(int|string $id)`
- `__construct(private array $data)`
- `get()`
- `__call(string $name,array $args)`
- `toArray()`
- `jsonSerialize()`

## `Pagination/Paginator.php`

- `__construct(private \Closure $fetcher, private ?\Closure $mapper=null)`
- `limit(int $limit)`
- `page(int $page)`
- `maxPages(?int $n)`
- `get()`
- `all(?int $maxPages=null)`
- `getIterator()`

## `DTO/UserProfile.php`

- `__construct(public readonly string $username,public readonly ?string $bio=null,public readonly ?string $status=null,public readonly ?string $country=null)`
- `toArray()`
- `jsonSerialize()`

## `DTO/StudioInfo.php`

- `__construct(public readonly string $id,public readonly ?string $title=null,public readonly ?string $description=null)`
- `toArray()`
- `jsonSerialize()`

## `DTO/ProjectStats.php`

- `__construct(public readonly int $views=0,public readonly int $loves=0,public readonly int $favorites=0,public readonly int $remixes=0)`
- `toArray()`
- `jsonSerialize()`

## `DTO/CloudChange.php`

- `__construct(public readonly string $name,public readonly string $value,public readonly ?string $user=null,public readonly int|float|string|null $timestamp=null,public readonly ?string $verb=null)`
- `toArray()`
- `jsonSerialize()`

## `Observability/Metrics.php`

- `recordRequest(int $status, float $ms)`
- `recordFailure(float $ms = 0)`
- `cacheHit()`
- `cacheMiss()`
- `retry()`
- `reset()`
- `summary()`

## `Cloud/WebSocketClient.php`

- `connect(string $host,int $port=443,string $path='/',array $headers=[])`
- `setReadTimeout(float $seconds)`
- `sendText(string $payload)`
- `receive()`
- `timedOut()`
- `close()`
- `isConnected()`
- `__destruct()`

## `Cloud/CloudConnection.php`

- `__construct(private string $projectId, private Session $session)`
- `connect()`
- `fetchRemoteValues(int $limit=100)`
- `set(string $name, int|float|string $value)`
- `get(string $name)`
- `getRemote(string $name,float $timeoutSeconds=4.0)`
- `setVerified(string $name,int|float|string $value,float $timeoutSeconds=5.0)`
- `sync(float $timeoutSeconds=1.5)`
- `remoteMeta(string $name)`
- `all()`
- `on(string $event,callable $listener)`
- `onVariable(string $name,callable $listener)`
- `listen(?int $maxMessages=null)`
- `waitFor(string $name, int|float|string $expected, float $timeoutSeconds=10.0)`
- `waitForChange(string $name,float $timeoutSeconds=10.0)`
- `watch(string $name,callable $listener)`
- `requests(string $requestVar='request',string $responseVar='response')`
- `database(string $variable='db')`
- `disconnect()`
- `isConnected()`
- `variables(bool $refresh=true)`
- `setMany(array $values,bool $verify=false)`
- `waitUntil(string $name,callable $predicate,float $timeoutSeconds=10.0)`
- `history(?string $name=null,int $limit=100,int $offset=0)`

## `Cloud/CloudDatabase.php`

- `__construct(private CloudConnection $cloud, private string $variable='db')`
- `all()`
- `get(string $key,mixed $default=null)`
- `set(string $key,mixed $value)`
- `delete(string $key)`
- `has(string $key)`
- `increment(string $key,int|float $by=1)`
- `decrement(string $key,int|float $by=1)`
- `clear()`

## `Cloud/CloudRequests.php`

- `__construct(private CloudConnection $cloud, private string $requestVar='request', private string $responseVar='response')`
- `on(string $method, callable $handler)`
- `route(string $method, callable $handler)`
- `middleware(callable $middleware)`
- `handleOnce(float $timeout=10.0)`
- `run(?int $maxRequests=null, float $timeoutPerRequest=30.0)`

## `Cloud/CloudVariable.php`

- `__construct(public readonly string $name, public readonly string $value, public readonly ?string $user = null)`

## `Cloud/CloudEvents.php`

- `on(string $event, callable $listener)`
- `emit(string $event, mixed $payload)`

## `Session.php`

- `__construct(private string $sessionId, private ?string $username = null, ?string $csrfToken = null, private ?Config $config=null, private ?DebugCollector $debugCollector=null, private ?Metrics $metrics=null, private ?RetryPolicy $retryPolicy=null, private ?CircuitBreaker $circuitBreaker=null)`
- `username()`
- `sessionId()`
- `xToken()`
- `csrfToken()`
- `http()`
- `user(string $username)`
- `project(int|string $id)`
- `studio(int|string $id)`
- `cloud(int|string $projectId)`
- `setProxy(?string $proxy)`
- `setRetries(int $retries, int $delayMs=180)`
- `enableLogger(?string $file=null)`
- `disableLogger()`
- `messages(int $limit=40,int $offset=0,?string $filter=null)`
- `adminMessages(int $limit=40,int $offset=0)`
- `searchProjects(string $query='',string $mode='trending',string $language='en',int $limit=40,int $offset=0)`
- `exploreProjects(string $query='*',string $mode='trending',string $language='en',int $limit=40,int $offset=0)`
- `searchStudios(string $query='',string $mode='trending',string $language='en',int $limit=40,int $offset=0)`
- `exploreStudios(string $query='',string $mode='trending',string $language='en',int $limit=40,int $offset=0)`
- `news(int $limit=10,int $offset=0)`
- `debug()`
- `authDiagnostics(?int $projectId = null)`

## `User/User.php`

- `__construct(private string $username, private ?Session $session=null,private ?HttpClient $client=null,private ?CacheInterface $cache=null,private ?Config $config=null)`
- `get()`
- `refresh()`
- `projects(int $limit=20,int $offset=0)`
- `followers(int $limit=20,int $offset=0)`
- `following(int $limit=20,int $offset=0)`
- `favorites(int $limit=20,int $offset=0)`
- `studios(int $limit=20,int $offset=0)`
- `messageCount()`
- `activity(int $limit=100)`
- `username()`
- `bio()`
- `country()`
- `status()`
- `follow()`
- `unfollow()`
- `postComment(string $content,int|string|null $parentId=null,int|string|null $commenteeId=null)`
- `deleteComment(int|string $id)`
- `reportComment(int|string $id)`
- `setBio(string $text)`
- `setStatus(string $text)`
- `setProfilePicture(string $path)`
- `toArray()`
- `toJson(int $flags=JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES)`
- `profileDto()`
- `projectsPaginator()`
- `projectsCollection(int $limit=20,int $offset=0)`

## `Registration/Registration.php`

- `__construct(private ?HttpClient $http = null)`
- `generateCredentials(
        string $prefix = 'ScratchUser',
        int $suffixLength = 7,
        int $passwordLength = 18
    )`
- `generateAvailableCredentials(
        string $prefix = 'ScratchUser',
        int $maxAttempts = 6
    )`
- `checkUsername(string $username)`
- `validatePassword(string $password)`
- `joinUrl()`
- `credentialsText(
        string $username,
        string $password,
        ?string $email = null
    )`
- `credentialsJson(
        string $username,
        string $password,
        ?string $email = null
    )`
- `parseCredentialsJson(string $json)`

## `Analysis/ProjectDiff.php`

- `__construct(private array $a,private array $b)`
- `addedSprites()`
- `removedSprites()`
- `blockDelta()`
- `toArray()`
- `jsonSerialize()`

## `Analysis/ProjectAnalyzer.php`

- `__construct(private array $json)`
- `raw()`
- `spriteCount(bool $includeStage = false)`
- `blockCount()`
- `costumeCount()`
- `soundCount()`
- `variables()`
- `cloudVariables()`
- `lists()`
- `broadcasts()`
- `extensions()`
- `targets()`
- `summary()`
- `sprites()`
- `blocksByOpcode()`
- `opcodeCounts()`
- `unusedVariables()`
- `duplicateScripts()`
- `broadcastGraph()`
- `extensionUsage()`
- `complexityScore()`
- `warnings()`

## `Sb3/Sb3Archive.php`

- `__construct(private string $path)`
- `projectJson()`
- `analyze()`
- `assets()`
- `extract(string $dir)`

## `Sb3/Sb3Validator.php`

- `validate(string $path)`

## `Project/Project.php`

- `__construct(private string $id,private ?Session $session=null,private ?HttpClient $client=null,private ?CacheInterface $cache=null,private ?Config $config=null)`
- `get()`
- `refresh()`
- `clearCache()`
- `id()`
- `title()`
- `author()`
- `views()`
- `loves()`
- `favorites()`
- `comments(int $limit=20,int $offset=0)`
- `remixes(int $limit=20,int $offset=0)`
- `remixInfo()`
- `love()`
- `unlove()`
- `favorite()`
- `unfavorite()`
- `postComment(string $content,int|string|null $parentId=null,int|string|null $commenteeId=null)`
- `replyComment(string $c,int|string $p,int|string|null $ce=null)`
- `deleteComment(int|string $id)`
- `reportComment(int|string $id)`
- `share()`
- `unshare()`
- `setThumbnail(string $path)`
- `rawJson()`
- `downloadProjectJson(string $path)`
- `downloadSb3(string $path)`
- `analyze()`
- `cloud()`
- `url()`
- `embedUrl()`
- `turbowarpUrl()`
- `player(int $width = 485, int $height = 402, bool $allowFullscreen = true)`
- `turbowarpPlayer(int $width = 800, int $height = 600, array $options = [])`
- `run(array $options = [])`
- `toArray()`
- `toJson(int $flags=JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES)`
- `statsDto()`
- `commentsCollection(int $limit=20,int $offset=0)`
- `commentsPaginator()`
- `sb3(string $path)`

## `Ui/ScratchApiWizard.php`

- `__construct(private Scratch $scratch)`
- `handle()`
- `render(array $options=[])`

## `Comment/Comment.php`

- `__construct(private array $data,private ?Session $session=null,private ?string $type=null,private ?string $resourceId=null)`
- `id()`
- `author()`
- `content()`
- `createdAt()`
- `parentId()`
- `toArray()`
- `toJson()`
- `jsonSerialize()`
- `reply(string $text)`
- `delete()`
- `report()`

## `Cache/MemoryCache.php`

- `get(string $k,mixed $d=null)`
- `set(string $k,mixed $v,int $ttl=60)`
- `delete(string $k)`
- `clear()`
- `has(string $k)`

## `Cache/Psr16CacheAdapter.php`

- `__construct(private object $cache)`
- `get(string $k,mixed $d=null)`
- `set(string $k,mixed $v,int $ttl=60)`
- `delete(string $k)`
- `clear()`
- `has(string $k)`

## `Cache/ManagedCache.php`

- `__construct(private CacheInterface $store, private ?Metrics $metrics=null)`
- `rules(array $rules)`
- `ttlFor(string $key,int $fallback=60)`
- `get(string $key,mixed $default=null)`
- `set(string $key,mixed $value,int $ttl=60)`
- `delete(string $key)`
- `clear()`
- `has(string $key)`
- `inner()`

## `Cache/FileCache.php`

- `__construct(private string $dir)`
- `get(string $k,mixed $d=null)`
- `set(string $k,mixed $v,int $ttl=60)`
- `delete(string $k)`
- `clear()`
- `has(string $k)`

## `Cache/CacheInterface.php`

- `get(string $key,mixed $default=null)`
- `set(string $key,mixed $value,int $ttl=60)`
- `delete(string $key)`
- `clear()`
- `has(string $key)`
