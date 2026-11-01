<?php

declare(strict_types=1);

/*
 * Profiles VoltProcure pages the way Octane serves them: one worker boots the
 * application once, then handles every request in a fresh sandbox.
 *
 *   php scripts/perf/profile.php [requests-per-page] [output-dir]
 *
 * Needs a migrated and seeded database (the demo seeder). Prints a Markdown
 * table of time and queries per page. When the excimer extension is loaded it
 * also samples CPU time and writes, per page, a speedscope profile and a
 * collapsed-stack file for flame graphs.
 */

use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Http\Request;
use Laravel\Octane\ApplicationFactory;
use Laravel\Octane\RequestContext;
use Laravel\Octane\Testing\Fakes\FakeClient;
use Laravel\Octane\Worker;
use Symfony\Component\HttpFoundation\Response;

$basePath = dirname(__DIR__, 2);
require $basePath.'/vendor/autoload.php';

$perPage = max(1, (int) ($argv[1] ?? 200));
$outDir = $argv[2] ?? $basePath.'/storage/perf';
is_dir($outDir) || mkdir($outDir, 0777, true);

// Boot as a web worker, not as the console: Laravolt registers its icon sets
// and UI overrides only outside the console.
putenv('APP_RUNNING_IN_CONSOLE=false');
$_ENV['APP_RUNNING_IN_CONSOLE'] = $_SERVER['APP_RUNNING_IN_CONSOLE'] = 'false';

$email = getenv('PERF_USER') ?: 'andi@voltprocure.test';
$pages = ['/up', '/home', '/purchase-requests', '/purchase-orders', '/items', '/suppliers'];

$client = new FakeClient([]);
$worker = new Worker(new ApplicationFactory($basePath), $client);
$worker->boot();

$queries = 0;
$queryMs = 0.0;
$worker->application()->make('events')->listen(
    QueryExecuted::class,
    function (QueryExecuted $event) use (&$queries, &$queryMs): void {
        $queries++;
        $queryMs += $event->time;
    },
);

/** @var array<string, string> $jar */
$jar = [];

$send = function (string $method, string $uri, array $params = []) use ($worker, $client, &$jar): Response {
    $request = Request::create('http://127.0.0.1'.$uri, $method, $params, $jar, [], [
        'HTTP_ACCEPT' => 'text/html',
        'REMOTE_ADDR' => '127.0.0.1',
    ]);

    $client->responses = [];
    $client->errors = [];
    $worker->handle($request, new RequestContext(['request' => $request]));

    if ($client->errors !== []) {
        throw new RuntimeException("{$method} {$uri} failed: ".$client->errors[0]);
    }

    $response = $client->responses[0] ?? throw new RuntimeException("{$method} {$uri} sent no response");
    $client->responses = [];

    foreach ($response->headers->getCookies() as $cookie) {
        if ($cookie->isCleared()) {
            unset($jar[$cookie->getName()]);
        } else {
            $jar[$cookie->getName()] = (string) $cookie->getValue();
        }
    }

    return $response;
};

// Log in the way a browser does: fetch the form for its CSRF token, then post it.
$form = $send('GET', '/auth/login');
preg_match('/<input[^>]*name="_token"[^>]*>/', (string) $form->getContent(), $input);
preg_match('/value="([^"]*)"/', $input[0] ?? '', $value);
$token = $value[1] ?? throw new RuntimeException('No CSRF token on the login page');

$login = $send('POST', '/auth/login', ['_token' => $token, 'email' => $email, 'password' => 'password']);
$target = (string) $login->headers->get('Location');
if (! $login->isRedirect() || str_contains($target, '/auth/login')) {
    throw new RuntimeException("Login as {$email} failed (status {$login->getStatusCode()}, Location {$target})");
}

$percentile = function (array $sorted, float $p): float {
    $index = (int) ceil($p / 100 * count($sorted)) - 1;

    return $sorted[max(0, min($index, count($sorted) - 1))];
};

$excimer = extension_loaded('excimer');
$rows = [];
$hot = [];

foreach ($pages as $page) {
    // Warm up: OPcache, the JIT and the first-request caches.
    for ($i = 0; $i < 20; $i++) {
        $status = $send('GET', $page)->getStatusCode();
    }
    if ($status !== 200) {
        throw new RuntimeException("GET {$page} returned {$status}");
    }

    $times = [];
    $queries = 0;
    $queryMs = 0.0;
    $bytes = 0;
    for ($i = 0; $i < $perPage; $i++) {
        $start = hrtime(true);
        $response = $send('GET', $page);
        $times[] = (hrtime(true) - $start) / 1e6;
        $bytes += mb_strlen((string) $response->getContent());
    }
    sort($times);

    $rows[] = sprintf(
        '| `%s` | %.1f | %.1f | %.1f | %.0f | %.1f | %.1f | %d |',
        $page,
        array_sum($times) / count($times),
        $percentile($times, 50),
        $percentile($times, 95),
        1000 / (array_sum($times) / count($times)),
        $queries / $perPage,
        $queryMs / $perPage,
        (int) round($bytes / $perPage / 1024),
    );

    if ($excimer) {
        $profiler = new ExcimerProfiler;
        $profiler->setEventType(EXCIMER_CPU);
        $profiler->setPeriod(0.0005);
        $profiler->setMaxDepth(300);
        $profiler->start();
        for ($i = 0; $i < $perPage; $i++) {
            $send('GET', $page);
        }
        $profiler->stop();

        $log = $profiler->getLog();
        $name = mb_trim(str_replace('/', '-', $page), '-');
        file_put_contents("{$outDir}/{$name}.collapsed.txt", $log->formatCollapsed());
        file_put_contents("{$outDir}/{$name}.speedscope.json", json_encode($log->getSpeedscopeData()));

        $total = max(1, count($log));
        $functions = $log->aggregateByFunction();
        uasort($functions, fn (array $a, array $b): int => $b['self'] <=> $a['self']);
        $hot[$page] = array_map(
            fn (array $f): string => sprintf('%.1f%%', 100 * $f['self'] / $total),
            array_slice($functions, 0, 12, true),
        );
    }
}

$out = [
    "### Octane worker profile ({$perPage} requests per page, user `{$email}`)",
    '',
    'PHP '.PHP_VERSION.', OPcache JIT '.(ini_get('opcache.jit') ?: 'off').'. One worker, so req/s is per CPU core.',
    '',
    '| Page | Mean ms | p50 ms | p95 ms | req/s per worker | Queries/req | Query ms/req | KB |',
    '|---|---|---|---|---|---|---|---|',
    ...$rows,
    '',
];

foreach ($hot as $page => $functions) {
    $out[] = "<details><summary>Top self time: <code>{$page}</code></summary>";
    $out[] = '';
    $out[] = '| Self | Function |';
    $out[] = '|---|---|';
    foreach ($functions as $function => $share) {
        $out[] = sprintf('| %s | `%s` |', $share, str_replace('|', '\|', (string) $function));
    }
    $out[] = '';
    $out[] = '</details>';
    $out[] = '';
}

$markdown = implode("\n", $out)."\n";
echo $markdown;

if ($summary = getenv('GITHUB_STEP_SUMMARY')) {
    file_put_contents($summary, $markdown, FILE_APPEND);
}

$worker->terminate();
