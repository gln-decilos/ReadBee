@extends('layouts.evaluator-layout')

@section('content')
    <x-common.page-breadcrumb pageTitle="Class Reports" />

    <div class="space-y-6">
        <x-evaluator.reports.class-reports-page
            :school-years="$schoolYears"
            :selected-year-id="$selectedYearId"
            :assignments="$assignments"
        />
    </div>
@endsection
