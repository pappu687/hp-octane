<?php
namespace App\Services;

use Illuminate\Http\Client\Pool;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

class RemoteDataService
{
    private string $baseUrl;
    private int $timeout;
    private int $maxConnections;

    public function __construct(
        string $baseUrl = 'https://jsonplaceholder.typicode.com',
        int $timeout = 500,
        int $maxConnections = 20
    ) {
        $this->baseUrl        = $baseUrl;
        $this->timeout        = $timeout;
        $this->maxConnections = $maxConnections;
    }

    public function fetchMultiple(array $endpoints, int $cacheSeconds = 300): array
    {
        $results = [  ];

        $uncachedEndpoints = [  ];
        foreach ($endpoints as $key => $endpoint) {
            $cacheKey = $this->getCacheKey($key);
            if (Cache::has($cacheKey)) {
                $results[ $key ] = Cache::get($cacheKey);
            } else {
                $uncachedEndpoints[ $key ] = $endpoint;
            }
        }

        if (! empty($uncachedEndpoints)) {
            $freshResults = $this->fetchConcurrently($uncachedEndpoints);

            foreach ($freshResults as $key => $data) {
                if ($data !== null) {
                    Cache::put($this->getCacheKey($key), $data, $cacheSeconds);
                    $results[ $key ] = $data;
                }
            }
        }

        return $results;
    }

    private function fetchConcurrently(array $endpoints): array
    {
        $responses = Http::pool(function (Pool $pool) use ($endpoints) {
            $requests = [  ];

            foreach ($endpoints as $key => $endpoint) {
                $requests[ $key ] = $pool
                    ->withOptions([
                        'pool_connections' => true,
                        'pool_maxsize'     => $this->maxConnections,
                        'timeout'          => $this->timeout,
                        'connect_timeout'  => 10,
                     ])
                    ->withHeaders([
                        'Accept'     => 'application/json',
                        'User-Agent' => 'Laravel-Octane-Client/1.0',
                        'Connection' => 'keep-alive',
                     ])
                    ->get($this->baseUrl . $endpoint);
            }

            return $requests;
        });

        $results = [  ];
        foreach ($responses as $key => $response) {
            $results[ $key ] = $response->successful() ? $response->json() : null;
        }

        return $results;
    }

    private function getCacheKey(string $key): string
    {
        return "remote_data_service_{$key}";
    }
}
