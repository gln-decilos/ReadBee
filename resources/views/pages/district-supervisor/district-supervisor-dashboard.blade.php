@extends('layouts.district-supervisor-layout')

@section('content')
    <x-common.page-breadcrumb pageTitle="District Supervisor Dashboard" />

    <x-district-supervisor.dashboard.dashboard-page
        :dashboard-data="$dashboardData"
        :dashboard-url="route('district-supervisor.dashboard')"
    />
@endsection
