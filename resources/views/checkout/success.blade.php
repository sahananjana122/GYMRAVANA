@extends('layouts.public')
@section('title', 'Order received')
@section('content')
<main class="mx-auto max-w-3xl px-5 py-24 text-center sm:px-8"><span class="mx-auto grid h-20 w-20 place-items-center rounded-full bg-lime-400 text-3xl font-black text-black">✓</span><p class="section-kicker mt-8">Order received</p><h1 class="page-title mx-auto">Thank you, {{ $order->customer_name }}.</h1><p class="page-lead mx-auto">Your pending order reference is <strong class="text-white">{{ $order->order_number }}</strong>. This MVP does not collect payment; the order is ready for admin review.</p><a href="{{ route('products.index') }}" class="mt-8 inline-flex rounded-full border border-white/20 px-6 py-3 font-bold">Return to store</a></main>
@endsection
