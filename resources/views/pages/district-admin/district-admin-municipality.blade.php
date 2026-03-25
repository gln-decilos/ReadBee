@extends('layouts.district-admin-layout')

@section('content')
    <x-common.page-breadcrumb pageTitle="Municipality Management" />

    <div class="space-y-6">
        <x-district-admin.municipality.municipalities-table
            :municipalities="$municipalities"
            :district-name="$districtName"
            :page="$page"
            :per-page="$perPage"
        />
    </div>
@endsection
