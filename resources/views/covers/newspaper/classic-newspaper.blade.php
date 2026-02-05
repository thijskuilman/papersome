@extends('layout')

@section('content')
    <div class="flex items-center justify-center min-h-screen bg-gray-200 p-4">
        <div id="book-cover" class="bg-white w-[600px] h-[800px] shadow-xl border border-gray-300 p-6 font-serif text-black overflow-hidden">

            <header class="text-center mb-4 relative">
                <hr class="mb-3">
                <h1 class="text-5xl font-extrabold tracking-tight uppercase">{{ $publication->collection->name }}</h1>
                <p class="text-gray-600 text-lg mt-1">{{ now()->format('F j, Y') }}</p>
                <hr class="my-3">
            </header>

            @if($articles->isNotEmpty())
                @php
                    $hero = $articles->shift();
                @endphp
                <section class="mb-4">
                    <h2 class="text-3xl font-extrabold leading-tight mb-2 line-clamp-3">
                        {{ $hero->title }}
                    </h2>
                    <p class="text-sm text-gray-700 mb-2 line-clamp-5">
                        {{ \Illuminate\Support\Str::limit(strip_tags($hero->excerpt), 200, '...') }}
                    </p>
                    @if($hero->author)
                        <p class="text-[10px] text-gray-500 italic mb-2">By {{ $hero->author }}</p>
                    @endif
                    @if($hero->image)
                        <img src="{{ $hero->image }}" alt="{{ $hero->title }}" class="w-full h-auto max-h-24 object-cover mt-2 rounded shadow-sm">
                    @endif
                </section>
            @endif

            <hr class="border-gray-400 mb-2">

            <div class="columns-2 gap-4 space-y-4">
                @foreach($articles as $article)
                    <article class="break-inside-avoid mb-4 p-2">
                        <h3 class="text-lg font-semibold mb-1 line-clamp-3">{{ $article->title }}</h3>
                        <p class="text-xs text-gray-700 mb-1 line-clamp-3">
                           {!! \Illuminate\Support\Str::limit(strip_tags($article->html_content))  !!}
                        </p>
                        @if($article->author)
                            <p class="text-[10px] text-gray-500 italic mb-2">By {{ $article->author }}</p>
                        @endif
                        @if($article->image)
                            <img src="{{ $article->image }}" alt="{{ $article->title }}" class="w-full h-auto mt-2 rounded">
                        @endif
                    </article>
                @endforeach
            </div>
        </div>
    </div>
@endsection
