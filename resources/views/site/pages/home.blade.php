@extends('site.layouts.app')
@section('transparent_header', true)
@section('title', $storeSettings->store_name)

@section('content')
    @include('site.partials.home.hero-slider')

    {{-- Benefits first: it answers the questions a new visitor has straight away. --}}
    @include('site.partials.home.features-strip')

    @include('site.partials.home.featured-categories')

    {{-- Time-limited offers sit high on the page while they are running. --}}
    @include('site.partials.home.flash-sales')

    @include('site.partials.home.featured-products')

    @include('site.partials.home.category-banner')

    {{-- Ranked from real order history. --}}
    @include('site.partials.home.best-sellers')

    @include('site.partials.home.new-products')

    @include('site.partials.home.brands')

    @include('site.partials.home.social-strip')
@endsection
