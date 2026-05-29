@extends('layouts.district-supervisor-layout')

@section('content')
    <x-common.page-breadcrumb :pageTitle="$title ?? 'District Supervisor'" />

    <div class="rounded-2xl border border-gray-200 bg-white p-6 shadow-theme-xs dark:border-gray-800 dark:bg-white/[0.03]">
        <h1 class="text-xl font-semibold text-gray-950 dark:text-white">{{ $title ?? 'District Supervisor' }}</h1>
        <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">
            This page is prepared for the District Supervisor role. The dashboard is already available; this module can be completed next.
        </p>
    </div>
@endsection
