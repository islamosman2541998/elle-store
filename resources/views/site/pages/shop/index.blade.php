@extends('site.layouts.app')

@section('title', app()->getLocale() === 'ar' ? 'المتجر' : 'Shop')

@section('content')
    @livewire('site.shop-page')
@endsection