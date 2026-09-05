@extends('site.layouts.app')

@section('title', app()->getLocale() === 'ar' ? 'تفاصيل المنتج' : 'Product Details')

@section('content')
    @livewire('site.product-show', ['slug' => $slug])
@endsection