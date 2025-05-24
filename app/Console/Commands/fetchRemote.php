<?php
namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Laravel\Octane\Facades\Octane;

class FetchRemote extends Command
{
    protected $signature = 'gs:remote
                            {--concurrent=10 : Number of concurrent requests}
                            {--cache=300 : Cache duration in seconds}
                            {--use-octane : Enable Octane optimizations}';

    protected $description = "Fetch remote data with Octane optimizations";

    private const BASE_URL = "https://jsonplaceholder.typicode.com";

    public function handle()
    {
        $concurrent = (int) $this->option("concurrent");
        $cacheTtl = (int) $this->option("cache");
        $useOctane = $this->option("use-octane");

        $this->info(
            "Starting fetch (Concurrent: {$concurrent}, Cache: {$cacheTtl}s, Octane: " .
                ($useOctane ? "Yes" : "No")
        );

        $start = microtime(true);

        try {
            $results = $useOctane
                ? $this->fetchWithOctane($concurrent, $cacheTtl)
                : $this->fetchWithPool($concurrent, $cacheTtl);

            $this->displayResults($results, microtime(true) - $start);
        } catch (\Throwable $e) {
            $this->error("Failed: " . $e->getMessage());
            return 1;
        }

        return 0;
    }

    private function fetchWithOctane(int $concurrent, int $cacheTtl): array
    {
        $endpoints = [
            "posts" => "/posts",
            "users" => "/users",
            "albums" => "/albums",
            "todos" => "/todos",
            "comments" => "/comments",
        ];

        $http = Http::withOptions([
            "pool_connections" => true,
            "pool_maxsize" => $concurrent * 2,
            "timeout" => 30,
        ]);

        // Create task list without binding $this
        $tasks = [];
        foreach ($endpoints as $key => $url) {
            $tasks[] = new OctaneTask(
                $http,
                $key,
                self::BASE_URL . $url,
                $cacheTtl
            );
        }

        $results = [];
        $octaneResults = Octane::concurrently(
            array_map(fn($task) => fn() => $task->execute(), $tasks),
            $concurrent
        );

        // Reindex results by endpoint key
        foreach ($tasks as $i => $task) {
            $results[$task->getKey()] = $octaneResults[$i];
        }

        return $results;
    }

    private function fetchWithPool(int $concurrent, int $cacheTtl): array
    {
        $endpoints = [
            "posts" => "/posts",
            "users" => "/users",
            "albums" => "/albums",
            "todos" => "/todos",
            "comments" => "/comments",
        ];

        $responses = Http::pool(function ($pool) use ($endpoints, $concurrent) {
            $pool->withOptions([
                "pool_connections" => true,
                "pool_maxsize" => $concurrent * 2,
            ]);

            foreach ($endpoints as $key => $url) {
                $pool->as($key)->get(self::BASE_URL . $url);
            }
        });

        $results = [];
        foreach ($responses as $key => $response) {
            $results[$key] = $this->processResponse($key, $response, $cacheTtl);
        }

        return $results;
    }

    private function processResponse(
        string $key,
        $response,
        int $cacheTtl
    ): array {
        if ($response->successful()) {
            $data = $response->json();
            Cache::store("octane")->put("remote_{$key}", $data, $cacheTtl);

            return [
                "data" => $data,
                "cached" => false,
                "status" => $response->status(),
                "time" => $response->handlerStats()["total_time"] ?? 0,
            ];
        }

        return [
            "data" => null,
            "cached" => false,
            "status" => $response->status(),
            "error" => $response->body(),
        ];
    }

    private function displayResults(array $results, float $duration): void
    {
        $this->table(
            ["Endpoint", "Status", "Items", "Time", "Cached"],
            array_map(
                function ($k, $v) {
                    return [
                        $k,
                        $v["status"],
                        is_array($v["data"]) ? count($v["data"]) : 0,
                        round($v["time"] ?? 0, 3) . "s",
                        $v["cached"] ? "✅" : "❌",
                    ];
                },
                array_keys($results),
                $results
            )
        );

        $this->line("\nTotal time: " . round($duration, 2) . "s");
    }
}

class OctaneTask
{
    public function __construct(
        private $http,
        private string $key,
        private string $url,
        private int $cacheTtl
    ) {}

    public function execute(): array
    {
        $cacheKey = "remote_{$this->key}";

        if (Cache::store("octane")->has($cacheKey)) {
            return [
                "data" => Cache::store("octane")->get($cacheKey),
                "cached" => true,
                "status" => 200,
            ];
        }

        $response = $this->http->get($this->url);

        if ($response->successful()) {
            $data = $response->json();
            Cache::store("octane")->put($cacheKey, $data, $this->cacheTtl);

            return [
                "data" => $data,
                "cached" => false,
                "status" => $response->status(),
                "time" => $response->handlerStats()["total_time"] ?? 0,
            ];
        }

        return [
            "data" => null,
            "cached" => false,
            "status" => $response->status(),
            "error" => $response->body(),
        ];
    }

    public function getKey(): string
    {
        return $this->key;
    }
}
