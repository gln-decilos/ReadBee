@extends('layouts.district-admin-layout')

@section('content')
    <x-common.page-breadcrumb pageTitle="School Year and Quarter Setup" />

    <div class="space-y-6">
        <x-district-admin.school-year.school-years
            :school-years="$schoolYears"
            :page="$page"
            :per-page="$perPage"
        />
    </div>
@endsection
