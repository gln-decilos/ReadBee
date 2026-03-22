@extends('layouts.district-admin-layout')

@section('content')
    <x-common.page-breadcrumb pageTitle="School Management" />

    <div class="space-y-6">
        <x-district-admin.school.schools-table
            :schools="$schools"
            :districts="$districts"
            :municipalities="$municipalities"
            :page="$page"
            :per-page="$perPage"
        />
    </div>
@endsection
