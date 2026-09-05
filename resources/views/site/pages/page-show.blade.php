@extends('site.layouts.app')

@section('title', $page->meta_title ?: $page->title)

@section('content')
    <section class="site-section">
        <div class="site-container">
            <article class="mx-auto max-w-3xl">
                <header class="border-b border-gray-200 pb-8">
                    <h1 class="site-section-title">{{ $page->title }}</h1>

                    @if ($page->short_description)
                        <p class="site-section-subtitle">{{ $page->short_description }}</p>
                    @endif
                </header>

                @if ($page->main_image)
                    <img
                        src="{{ \App\Support\Media::url($page->main_image, 800) }}"
                        alt="{{ $page->title }}"
                        class="mt-8 w-full rounded-site object-cover shadow-soft"
                        loading="lazy"
                    >
                @endif

                <div class="site-page-content mt-8">
                    {!! $page->content !!}
                </div>

                @if ($page->activeImages->count())
                    <h2 class="mt-12 text-lg font-bold text-dark">
                        {{ app()->getLocale() === 'ar' ? 'معرض الصور' : 'Gallery' }}
                    </h2>

                    <div class="mt-4 grid grid-cols-2 gap-4 sm:grid-cols-3">
                        @foreach ($page->activeImages as $image)
                            <figure>
                                <img
                                    src="{{ \App\Support\Media::url($image->image, 500) }}"
                                    alt="{{ $image->title }}"
                                    class="h-40 w-full rounded-2xl object-cover"
                                    loading="lazy"
                                >

                                @if ($image->title)
                                    <figcaption class="mt-2 text-center text-xs text-muted">
                                        {{ $image->title }}
                                    </figcaption>
                                @endif
                            </figure>
                        @endforeach
                    </div>
                @endif
            </article>
        </div>
    </section>
@endsection
