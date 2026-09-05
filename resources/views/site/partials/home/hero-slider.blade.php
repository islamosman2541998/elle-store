@php
    $settings = $homepage['settings'];
    $sliders = $homepage['sliders'] ?? collect();
@endphp

@if ($settings->slider_enabled && $sliders->count())
    <section class="relative w-full overflow-hidden bg-white" style="height: 100svh;">
        <div class="relative h-full w-full overflow-hidden bg-dark shadow-soft">
            <div class="hero-slider" data-hero-slider>
                @foreach ($sliders as $index => $slider)
                    @php
                        $image = \App\Support\Media::url($slider->image, 800);
                        $mobileImage = \App\Support\Media::url($slider->mobile_image, 500) ?? $image;
                    @endphp

                    <div class="hero-slide {{ $index === 0 ? 'is-active' : '' }}">
                        @if ($image)
                            <picture>
                                @if ($mobileImage)
                                    <source media="(max-width: 768px)" srcset="{{ $mobileImage }}">
                                @endif

                                <img
                                    src="{{ $image }}"
                                    alt="{{ $slider->title ?? $storeSettings->store_name }}"
                                    class="absolute inset-0 h-full w-full object-cover"
                                    draggable="false"
                                >
                            </picture>
                        @endif

                        <div class="absolute inset-0 bg-gradient-to-r from-black/75 via-black/35 to-transparent"></div>

                        <div class="site-container relative z-10 flex items-center py-16" style="height: 100svh;">
                            <div class="max-w-xl text-white">
                                @if ($slider->title)
                                    <h1 class="text-4xl font-black leading-tight md:text-6xl">
                                        {{ $slider->title }}
                                    </h1>
                                @endif

                                @if ($slider->description)
                                    <p class="mt-5 max-w-lg text-base leading-8 text-white/80 md:text-lg">
                                        {{ $slider->description }}
                                    </p>
                                @endif

                                @if (!empty($slider->button_url) && $slider->button_text)
                                    <a
                                        href="{{ $slider->button_url }}"
                                        @if ($slider->open_in_new_tab) target="_blank" rel="noopener" @endif
                                        class="site-btn site-btn-primary mt-8"
                                    >
                                        {{ $slider->button_text }}
                                    </a>
                                @endif
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>

            @if ($sliders->count() > 1)
                <button type="button" class="hero-slider-btn hero-slider-prev" data-hero-prev>
                    ‹
                </button>

                <button type="button" class="hero-slider-btn hero-slider-next" data-hero-next>
                    ›
                </button>

                <div class="absolute bottom-7 left-1/2 z-20 flex -translate-x-1/2 gap-2">
                    @foreach ($sliders as $index => $slider)
                        <button
                            type="button"
                            class="hero-slider-dot {{ $index === 0 ? 'is-active' : '' }}"
                            data-hero-dot="{{ $index }}"
                            aria-label="Slide {{ $index + 1 }}"
                        ></button>
                    @endforeach
                </div>
            @endif
        </div>
    </section>
@endif