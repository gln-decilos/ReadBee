@extends('layouts.evaluator-layout')

@section('content')
    <x-common.page-breadcrumb pageTitle="Pupil Management" />

    <div class="space-y-6">
        <x-evaluator.pupils.pupil-management-page
            :school-years="$schoolYears"
            :selected-year-id="$selectedYearId"
            :grades="$grades"
            :page="$page"
            :per-page="$perPage"
        />
    </div>
@endsection
