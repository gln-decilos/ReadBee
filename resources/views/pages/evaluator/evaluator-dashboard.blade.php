@extends('layouts.evaluator-layout')

@section('content')
    <x-common.page-breadcrumb pageTitle="Evaluator Dashboard" />

    <div class="grid grid-cols-12 gap-4 md:gap-6">
        <div class="col-span-12 rounded-3xl border border-gray-200 bg-white p-6 shadow-sm dark:border-white/[0.05] dark:bg-white/[0.03]">
            <div class="inline-flex items-center rounded-full bg-yellow-100 px-3 py-1 text-xs font-medium text-yellow-700 dark:bg-yellow-500/10 dark:text-yellow-400">
                Evaluator Workspace
            </div>
            <h2 class="mt-3 text-2xl font-semibold text-gray-900 dark:text-white">Welcome, evaluator</h2>
            <p class="mt-2 max-w-2xl text-sm text-gray-500 dark:text-gray-400">
                Review the assessment assignments given to you and confirm each assignment before proceeding with the evaluation work.
            </p>
            <div class="mt-5">
                <a href="{{ route('evaluator.assignments') }}" class="inline-flex rounded-lg bg-brand-500 px-5 py-2.5 text-sm font-semibold text-gray-900 hover:bg-brand-400">
                    View My Assignments
                </a>
            </div>
        </div>
    </div>
@endsection
