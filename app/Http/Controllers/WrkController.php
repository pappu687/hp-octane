<?php
namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class WrkController extends Controller
{
    public function index()
    {
        return view('wrk.parser');
    }

    public function parse(Request $request): JsonResponse
    {
        $request->validate([
            'wrk_output' => 'required|string',
         ]);

        $output = $request->input('wrk_output');
        $parsed = $this->parseWrkOutput($output);

        return response()->json($parsed);
    }

    private function parseWrkOutput(string $output): array
    {
        $lines  = explode("\n", trim($output));
        $result = [
            'test_info'    => [  ],
            'thread_stats' => [  ],
            'summary'      => [  ],
            'errors'       => [  ],
            'rates'        => [  ],
         ];

        foreach ($lines as $line) {
            $line = trim($line);

            // Parse test configuration
            if (preg_match('/Running (\d+[a-z]) test @ (.+)/', $line, $matches)) {
                $result[ 'test_info' ][ 'duration' ] = $matches[ 1 ];
                $result[ 'test_info' ][ 'url' ]      = $matches[ 2 ];
            }

            if (preg_match('/(\d+) threads and (\d+) connections/', $line, $matches)) {
                $result[ 'test_info' ][ 'threads' ]     = (int) $matches[ 1 ];
                $result[ 'test_info' ][ 'connections' ] = (int) $matches[ 2 ];
            }

            // Parse latency stats
            if (preg_match('/Latency\s+([0-9.]+)([a-z]+)\s+([0-9.]+)([a-z]+)\s+([0-9.]+)([a-z]+)\s+([0-9.]+)%/', $line, $matches)) {
                $result[ 'thread_stats' ][ 'latency' ] = [
                    'avg'              => $this->convertToMs($matches[ 1 ], $matches[ 2 ]),
                    'stdev'            => $this->convertToMs($matches[ 3 ], $matches[ 4 ]),
                    'max'              => $this->convertToMs($matches[ 5 ], $matches[ 6 ]),
                    'stdev_percentage' => (float) $matches[ 7 ],
                 ];
            }

            // Parse req/sec stats
            if (preg_match('/Req\/Sec\s+([0-9.]+)\s+([0-9.]+)\s+([0-9.]+)\s+([0-9.]+)%/', $line, $matches)) {
                $result[ 'thread_stats' ][ 'req_per_sec' ] = [
                    'avg'              => (float) $matches[ 1 ],
                    'stdev'            => (float) $matches[ 2 ],
                    'max'              => (float) $matches[ 3 ],
                    'stdev_percentage' => (float) $matches[ 4 ],
                 ];
            }

            // Parse summary
            if (preg_match('/(\d+) requests in ([0-9.]+)s, ([0-9.]+)([A-Z]+) read/', $line, $matches)) {
                $result[ 'summary' ][ 'total_requests' ] = (int) $matches[ 1 ];
                $result[ 'summary' ][ 'duration' ]       = (float) $matches[ 2 ];
                $result[ 'summary' ][ 'data_read' ]      = [
                    'amount' => (float) $matches[ 3 ],
                    'unit'   => $matches[ 4 ],
                 ];
            }

            // Parse socket errors
            if (preg_match('/Socket errors: connect (\d+), read (\d+), write (\d+), timeout (\d+)/', $line, $matches)) {
                $result[ 'errors' ] = [
                    'connect' => (int) $matches[ 1 ],
                    'read'    => (int) $matches[ 2 ],
                    'write'   => (int) $matches[ 3 ],
                    'timeout' => (int) $matches[ 4 ],
                    'total'   => (int) $matches[ 1 ] + (int) $matches[ 2 ] + (int) $matches[ 3 ] + (int) $matches[ 4 ],
                 ];
            }

            // Parse non-2xx responses
            if (preg_match('/Non-2xx or 3xx responses: (\d+)/', $line, $matches)) {
                $result[ 'errors' ][ 'non_success_responses' ] = (int) $matches[ 1 ];
            }

            // Parse rates
            if (preg_match('/Requests\/sec:\s+([0-9.]+)/', $line, $matches)) {
                $result[ 'rates' ][ 'requests_per_sec' ] = (float) $matches[ 1 ];
            }

            if (preg_match('/Transfer\/sec:\s+([0-9.]+)([A-Z]+)/', $line, $matches)) {
                $result[ 'rates' ][ 'transfer_per_sec' ] = [
                    'amount' => (float) $matches[ 1 ],
                    'unit'   => $matches[ 2 ],
                 ];
            }
        }

        // Calculate additional metrics
        if (! empty($result[ 'summary' ][ 'total_requests' ]) && ! empty($result[ 'summary' ][ 'duration' ])) {
            $result[ 'calculated' ] = [
                'success_rate'      => $this->calculateSuccessRate($result),
                'error_rate'        => $this->calculateErrorRate($result),
                'avg_response_time' => $result[ 'thread_stats' ][ 'latency' ][ 'avg' ] ?? 0,
             ];
        }

        return $result;
    }

    private function convertToMs(string $value, string $unit): float
    {
        $val = (float) $value;
        switch (strtolower($unit)) {
            case 's':
                return $val * 1000;
            case 'ms':
                return $val;
            case 'us':
                return $val / 1000;
            default:
                return $val;
        }
    }

    private function calculateSuccessRate(array $result): float
    {
        $total  = $result[ 'summary' ][ 'total_requests' ] ?? 0;
        $errors = ($result[ 'errors' ][ 'non_success_responses' ] ?? 0) + ($result[ 'errors' ][ 'total' ] ?? 0);

        if ($total === 0) {
            return 0;
        }

        return round((($total - $errors) / $total) * 100, 2);
    }

    private function calculateErrorRate(array $result): float
    {
        return round(100 - $this->calculateSuccessRate($result), 2);
    }
}
