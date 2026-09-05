@extends('site.layouts.app')

@section('title', app()->getLocale() === 'ar' ? 'عناويني' : 'My Addresses')

@section('content')
    @livewire('site.customer-addresses')
@endsection