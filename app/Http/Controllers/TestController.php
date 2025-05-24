<?php
namespace App\Http\Controllers;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Swoole\Coroutine;

class TestController extends Controller
{
    public function bootTest()
    {
        return view('message', [ 'title' => 'Boot Test', 'message' => $this->formatBytes(memory_get_usage()) ]);
    }

    public function concurrent()
    {
        [ $users, $posts ] = \Laravel\Octane\Facades\Octane::concurrently([
            fn() => Http::get('https://jsonplaceholder.typicode.com/users')->json(),
            fn() => Http::get('https://jsonplaceholder.typicode.com/posts')->json(),
         ]);

        return view('message', [ 'title' => 'Concurrent', 'message' => json_encode([ 'users' => $users, 'posts' => $posts ], JSON_PRETTY_PRINT) ]);
    }

    public function cacheHit()
    {
        Cache::store('octane')->put('workshop:name', 'Laravel Octane from cache', now()->addMinutes(10));
        return view('message', [ 'title' => 'Cache Hit', 'message' => Cache::store('octane')->get('workshop:name') ]);
    }

    public function hitCounter()
    {
        $count = Cache::store('octane')->get('counter', 0);
        Cache::store('octane')->put('counter', $count + 1);

        return view('message', [ 'title' => 'Hit Counter', 'message' => "Page viewed {$count} times since last restart." ]);
    }

    public function coroutine()
    {

    }

    public function formatBytes(int $bytes, bool $decimalUnits = true): string
    {
        if ($bytes < 0) {
            return "0 B";
        }

        // Choose unit system (SI = 1000, IEC = 1024)
        $unit  = $decimalUnits ? 1000 : 1024;
        $units = $decimalUnits
        ? [ 'B', 'KB', 'MB', 'GB', 'TB', 'PB', 'EB', 'ZB', 'YB' ]
        : [ 'B', 'KiB', 'MiB', 'GiB', 'TiB', 'PiB', 'EiB', 'ZiB', 'YiB' ];

        // Calculate the appropriate unit
        $index = 0;
        while ($bytes >= $unit && $index < count($units) - 1) {
            $bytes /= $unit;
            $index++;
        }

        // Format the number (max 2 decimal places)
        return round($bytes, 2) . ' ' . $units[ $index ];
    }

}
