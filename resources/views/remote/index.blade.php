@extends('layouts.app')

@section('title', 'Remote Data')

@section('content')
    <div class="space-y-8">
        <!-- Posts Section -->
        <section class="bg-white shadow-sm rounded-lg overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-200">
                <h2 class="text-xl font-semibold text-gray-800">Recent Posts</h2>
            </div>
            <div class="divide-y divide-gray-200">
                @foreach ($posts as $post)
                    <article class="p-6 hover:bg-gray-50 transition-colors duration-150">
                        <h3 class="text-lg font-medium text-gray-900 mb-2">{{ $post['title'] }}</h3>
                        <p class="text-gray-600 text-sm mb-2">By User ID: {{ $post['userId'] }}</p>
                        <p class="text-gray-700">{{ Str::limit($post['body'], 150) }}</p>
                    </article>
                @endforeach
            </div>
        </section>

        <!-- Users Section -->
        <section class="bg-white shadow-sm rounded-lg overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-200">
                <h2 class="text-xl font-semibold text-gray-800">Users</h2>
            </div>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 p-6">
                @foreach ($users as $user)
                    <div class="bg-gray-50 rounded-lg p-4 hover:shadow-md transition-shadow duration-150">
                        <h3 class="text-lg font-medium text-gray-900 mb-2">{{ $user['name'] }}</h3>
                        <div class="space-y-2 text-sm">
                            <p class="text-gray-600">
                                <span class="font-medium">Username:</span> {{ $user['username'] }}
                            </p>
                            <p class="text-gray-600">
                                <span class="font-medium">Email:</span> {{ $user['email'] }}
                            </p>
                            <p class="text-gray-600">
                                <span class="font-medium">Company:</span> {{ $user['company']['name'] }}
                            </p>
                            <p class="text-gray-600">
                                <span class="font-medium">Location:</span> {{ $user['address']['city'] }}
                            </p>
                        </div>
                    </div>
                @endforeach
            </div>
        </section>

        <!-- Albums Section -->
        <section class="bg-white shadow-sm rounded-lg overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-200">
                <h2 class="text-xl font-semibold text-gray-800">Albums</h2>
            </div>
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4 p-6">
                @foreach ($albums as $album)
                    <div class="bg-gray-50 rounded-lg p-4 hover:shadow-md transition-shadow duration-150">
                        <h3 class="text-lg font-medium text-gray-900 mb-2">{{ $album['title'] }}</h3>
                        <p class="text-gray-600 text-sm">User ID: {{ $album['userId'] }}</p>
                    </div>
                @endforeach
            </div>
        </section>
    </div>
@endsection
