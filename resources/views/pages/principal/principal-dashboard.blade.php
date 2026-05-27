@extends('layouts.principal-layout')

@section('content')
    <x-principal.dashboard.dashboard-page :dashboard-data="$dashboardData ?? []" />
@endsection
