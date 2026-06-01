@extends('layouts.school-admin-layout')

@section('content')
    <x-school-admin.dashboard.dashboard-page :dashboard="$dashboard" />
@endsection
