@extends('layouts.district-admin-layout')

@section('content')
    <x-district-admin.dashboard.dashboard-page :dashboard="$dashboard" />
@endsection
