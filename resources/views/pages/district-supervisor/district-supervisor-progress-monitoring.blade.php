@extends('layouts.district-supervisor-layout')

@section('content')
    <x-common.page-breadcrumb pageTitle="District Supervisor Progress Monitoring" />

    <x-district-supervisor.progress-monitoring.progress-monitoring-page
        :school-years="$schoolYears"
        :selected-year-id="$selectedYearId"
        :summary="$summary"
        :municipalities="$municipalities"
    />
@endsection
