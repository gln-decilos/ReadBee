@extends('layouts.principal-layout')

@section('content')
    <x-common.page-breadcrumb pageTitle="Profile" />

    <div class="space-y-6">
        <x-profile.profile-card />
        <x-profile.personal-info-card />
        <x-profile.address-card />
    </div>
@endsection
