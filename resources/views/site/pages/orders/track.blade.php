@extends('site.layouts.app')

@section('title', app()->getLocale() === 'ar' ? 'تتبع الطلب' : 'Track Order')

@section('content')
    @livewire('site.track-order')
@endsection