@extends('layouts.app')

@section('title', 'Remote Data')

@section('content')
    <div class="container mx-auto px-4 py-8">
        <h1 class="text-3xl font-bold text-gray-800 mb-6">Remote Data Fetcher</h1>

        <!-- Stats Bar -->
        <div class="bg-white rounded-lg shadow p-6 mb-8">
            <div class="grid grid-cols-4 gap-4">
                <div class="bg-blue-50 p-4 rounded">
                    <p class="text-sm text-blue-600">Concurrent</p>
                    <p class="text-2xl font-bold">{{ $concurrent }}</p>
                </div>
                <div class="bg-green-50 p-4 rounded">
                    <p class="text-sm text-green-600">Cache TTL</p>
                    <p class="text-2xl font-bold">{{ $cacheTtl }}s</p>
                </div>
                <div class="bg-purple-50 p-4 rounded">
                    <p class="text-sm text-purple-600">Octane</p>
                    <p class="text-2xl font-bold">{{ $useOctane ? 'Enabled' : 'Disabled' }}</p>
                </div>
                <div class="bg-yellow-50 p-4 rounded">
                    <p class="text-sm text-yellow-600">Total Time</p>
                    <p class="text-2xl font-bold">{{ $duration }}s</p>
                </div>
            </div>
        </div>

        @if ($noCache)
            <div class="bg-yellow-50 border-l-4 border-yellow-400 p-4 mb-6">
                <div class="flex">
                    <div class="flex-shrink-0">
                        ⚡
                    </div>
                    <div class="ml-3">
                        <h3 class="text-sm font-medium text-yellow-800">
                            Cache Bypass Active - Measuring Pure Octane Performance
                        </h3>
                        @if ($benchmark)
                            <div class="mt-2 text-sm text-yellow-700">
                                Previous benchmark: {{ $benchmark['duration'] }}s
                                ({{ $benchmark['concurrent'] }} concurrent)
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        @endif
        <!-- Results Table -->
        <div class="bg-white rounded-lg shadow overflow-hidden">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Endpoint</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Items</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Time</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Cached</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @foreach ($results as $key => $result)
                        <tr class="hover:bg-gray-50">
                            <td class="px-6 py-4 whitespace-nowrap font-medium text-gray-900">
                                {{ isset($result['url']) ? $result['url'] : $key }}</td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span
                                    class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full 
                                {{ $result['status'] == 200 ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                                    {{ $result['status'] }}
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-gray-500">
                                {{ is_array($result['data']) ? count($result['data']) : 0 }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-gray-500">
                                {{ round($result['time'] ?? 0, 3) }}s
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                @if ($result['cached'])
                                    <span class="text-green-600">✓ Cached</span>
                                @else
                                    <span class="text-orange-500">✗ Live</span>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
@endsection
