@extends('layouts.school-admin-layout')

@section('content')
    <x-common.page-breadcrumb pageTitle="User Management" />

    <div class="space-y-6">
        <x-school-admin.users.users-table
            :users="$users"
            :page="$page"
            :per-page="$perPage"
        />
    </div>
@endsection
