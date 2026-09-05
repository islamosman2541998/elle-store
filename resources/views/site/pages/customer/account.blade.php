@extends('site.layouts.app')

@section('title', app()->getLocale() === 'ar' ? 'حسابي' : 'My Account')

@section('content')
    @livewire('site.customer-account')
@endsection