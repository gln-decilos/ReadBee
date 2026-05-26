@extends('layouts.evaluator-layout')

@section('content')
    <x-common.page-breadcrumb pageTitle="Progress Monitoring" />

    <div class="space-y-6">
        <x-evaluator.progress-monitoring.progress-monitoring-page
            :school-years="$schoolYears"
            :selected-year-id="$selectedYearId"
            :summary="$summary"
            :assignments="$assignments"
        />
    </div>
@endsection
