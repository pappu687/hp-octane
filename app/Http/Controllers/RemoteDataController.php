<?php
namespace App\Http\Controllers;

use App\Services\RemoteDataService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\View\View;
use Laravel\Octane\Facades\Octane;

class RemoteDataController extends Controller
{
    private const BASE_URL = 'https://jsonplaceholder.typicode.com';

    public function index(RemoteDataService $remoteDataService): View
    {
        $endpoints = [
            'posts'  => '/posts?_limit=10',
            'users'  => '/users?_limit=5',
            'albums' => '/albums?_limit=5',
         ];

        $data = $remoteDataService->fetchMultiple($endpoints, 600);

        return view('remote.index', [
            'posts'  => $data[ 0 ],
            'users'  => $data[ 1 ],
            'albums' => $data[ 2 ],
         ]);
    }

    public function fetch(Request $request, $noCache = false)
    {
        $request->validate([
            'concurrent' => 'sometimes|integer|min:1|max:20',
            'cache'      => 'sometimes|integer|min:0',
            'no_cache'   => 'sometimes|boolean',
         ]);

        $concurrent = (int) $request->input('concurrent', 2000);
        $cacheTtl   = (int) $request->input('cache', 300);
        $useOctane  = $request->boolean('use_octane', true);

        // Clear cache if bypass requested
        if ($noCache) {
            Cache::forget('remote_benchmark');
            collect([ 'posts', 'users', 'albums', 'todos', 'comments' ])
                ->each(fn($key) => Cache::forget("remote_$key"));
        }

        $start = microtime(true);

        $endpoints = $this->getEndpoints();

        try {
            $results = $useOctane
            ? $this->fetchWithOctane($concurrent, $noCache ? 0 : $cacheTtl)
            : $this->fetchWithPool($concurrent, $noCache ? 0 : $cacheTtl);

            $duration = microtime(true) - $start;

            // Store benchmark results
            if ($noCache) {
                Cache::remember('remote_benchmark', 600, function () use ($duration, $concurrent) {
                    return [
                        'last_run'   => now()->toDateTimeString(),
                        'duration'   => $duration,
                        'concurrent' => $concurrent,
                        'cache'      => 'disabled',
                     ];
                });
            }

            return view('remote.fetch', [
                'results'    => $results,
                'endpoints'  => $endpoints,
                'duration'   => round($duration, 3),
                'concurrent' => $concurrent,
                'cacheTtl'   => $noCache ? 0 : $cacheTtl,
                'useOctane'  => $useOctane,
                'noCache'    => $noCache,
                'benchmark'  => Cache::get('remote_benchmark'),
             ]);

        } catch (\Throwable $e) {

            return view('error', [
                'message' => "Error: " . $e->getMessage(),
             ]);
        }
    }

    private function fetchWithOctane(int $concurrent, int $cacheTtl): array
    {
        $endpoints = $this->getEndpoints();

        // Pre-configure HTTP client outside closures
        $httpConfig = [
            'base_uri'         => self::BASE_URL,
            'timeout'          => 30,
            'pool_connections' => true,
            'pool_maxsize'     => $concurrent * 2,
         ];

        $tasks = [  ];
        foreach ($endpoints as $key => $url) {
            $tasks[  ] = function () use ($httpConfig, $key, $url, $cacheTtl) {
                $http = Http::withOptions($httpConfig);
                return $this->fetchSingleEndpoint($http, $key, $url, $cacheTtl);
            };
        }

        return Octane::concurrently($tasks, $concurrent);
    }

    private function fetchWithPool(int $concurrent, int $cacheTtl): array
    {
        $endpoints = $this->getEndpoints();

        $responses = Http::pool(function ($pool) use ($concurrent, $endpoints) {
            $pool->withOptions([
                'pool_connections' => true,
                'pool_maxsize'     => $concurrent * 2,
             ]);

            $requests = [  ];

            foreach ($endpoints as $key => $url) {
                $requests[ $key ] = $pool->as($key)->get(self::BASE_URL . $url);
            }

            return $requests;
        });

        $results = [  ];
        foreach ($responses as $key => $response) {
            $results[ $key ] = $this->processResponse($key, $response, $cacheTtl);
        }

        return $results;
    }

    private function fetchSingleEndpoint($http, string $key, string $url, int $cacheTtl): array
    {
        $cacheKey = "remote_{$key}";

        if (Cache::has($cacheKey)) {
            return [
                'url'    => $url,
                'data'   => Cache::get($cacheKey),
                'cached' => true,
                'status' => 200,
             ];
        }

        $response = $http->get($url);
        return $this->processResponse($key, $response, $cacheTtl);
    }

    private function getEndpoints(): array
    {
        return [
            'posts'    => '/posts',
            'users'    => '/users',
            'albums'   => '/albums',
            'todos'    => '/todos',
            'comments' => '/comments',
         ];
    }

    private function processResponse(string $key, $response, int $cacheTtl): array
    {
        $data = $response->successful() ? $response->json() : null;

        if ($response->successful() && $cacheTtl > 0) {
            Cache::put("remote_$key", $data, $cacheTtl);
        }

        return [
            'data'       => $data,
            'cached'     => false,
            'status'     => $response->status(),
            'time'       => $response->handlerStats()[ 'total_time' ] ?? 0,
            'from_cache' => false,
         ];
    }
}
