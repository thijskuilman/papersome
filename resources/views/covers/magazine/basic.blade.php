@extends('layout')

@section('content')
    <div class="flex items-center justify-center min-h-screen bg-gray-200 p-4">
        <div id="magazine-cover" class="bg-white w-[600px] h-[800px] shadow-xl border border-gray-300 overflow-hidden font-sans relative">

            @if($articles->isNotEmpty())
                @php
                    $hero = $articles->shift();
                @endphp
                <img src="{{ $hero->image ?? 'https://via.placeholder.com/600x800' }}" alt="{{ $hero->title }}" class="absolute inset-0 w-full h-full object-cover">

                <div class="absolute inset-0 bg-gradient-to-t from-black/80 to-black/30"></div>

                <div class="absolute top-6 left-6 right-6 text-center">
                    <h1 class="text-5xl font-bold uppercase text-white tracking-wide drop-shadow-lg">
                        {{ $publication->collection->name }}
                    </h1>

                    <p class="text-gray-200 text-sm mt-1">{{ now()->format('F j, Y') }}</p>
                </div>

                <div class="absolute bottom-40 left-6 right-6 text-center">
                    <h2 class="text-xl font-extrabold text-white leading-tight drop-shadow-md">
                        {{ $hero->title }}
                    </h2>
                    @if($hero->author)
                        <p class="text-gray-200 text-xs italic mt-1">By {{ $hero->author }}</p>
                    @endif
                </div>

                <div class="absolute bottom-6 left-6 grid grid-cols-2 gap-2 w-[90%]">
                    @foreach($articles->take(4) as $article)
                        <div class="bg-black/50 p-1 rounded text-white text-xs hover:bg-black/70 transition">
                            {{ $article->title }}
                        </div>
                    @endforeach
                </div>
            @endif

        </div>
    </div>
@endsection
