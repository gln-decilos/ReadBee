@extends('layouts.evaluator-layout')

@section('content')
    <x-common.page-breadcrumb pageTitle="Evaluator Profile" />

    <div class="grid grid-cols-12 gap-4 md:gap-6">
        <div class="col-span-12 xl:col-span-5">
            <x-profile.profile-card />
        </div>
        <div class="col-span-12 xl:col-span-7">
            <x-profile.personal-info-card />
        </div>
        <div class="col-span-12">
            <x-profile.address-card />
        </div>
    </div>
@endsection
