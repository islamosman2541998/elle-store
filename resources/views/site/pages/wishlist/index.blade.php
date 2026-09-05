@extends('site.layouts.app')

@section('title', app()->getLocale() === 'ar' ? 'قائمة الرغبات' : 'Wishlist')

@section('content')
    @livewire('site.wishlist-page')
@endsection