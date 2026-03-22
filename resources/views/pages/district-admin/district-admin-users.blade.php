@extends('layouts.district-admin-layout')

@section('content')
    <x-common.page-breadcrumb pageTitle="User Management" />

    <div class="space-y-6">
        <x-district-admin.users.users-table
            :users="$users"
            :page="$page"
            :per-page="$perPage"
        />
    </div>
@endsection
