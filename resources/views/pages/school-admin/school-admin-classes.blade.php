@extends('layouts.school-admin-layout')

@section('content')
    <x-common.page-breadcrumb pageTitle="Class Management" />

    <div class="space-y-6">
        <x-school-admin.classes.class-management-table
            :school-years="$schoolYears"
            :selected-year-id="$selectedYearId"
            :grades="$grades"
            
        />
    </div>
@endsection
