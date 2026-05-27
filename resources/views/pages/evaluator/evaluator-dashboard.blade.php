@extends('layouts.evaluator-layout')

@section('content')
    <x-common.page-breadcrumb pageTitle="Evaluator Dashboard" />

    <x-evaluator.dashboard.dashboard-page
        :dashboard-data="$dashboardData"
        :dashboard-url="route('evaluator.dashboard')"
    />
@endsection
