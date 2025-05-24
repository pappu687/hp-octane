@extends('layouts.app')

@section('title', 'Welcome')

@section('content')
    <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
        <div class="p-6 text-center">
            <h2 class="text-2xl font-semibold text-gray-800 mb-4">
                {{ $title }}
            </h2>
        </div>
        <pre class="p-6 text-left bg-gray-100 rounded-lg overflow-auto">
            {{ $message }}
        </pre>
    </div>
@endsection
