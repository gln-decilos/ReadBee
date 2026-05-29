@extends('layouts.district-supervisor-layout')

@section('content')
    <x-common.page-breadcrumb pageTitle="District Supervisor Reports" />

    <x-district-supervisor.reports.reports-page
        :school-years="$schoolYears"
        :selected-year-id="$selectedYearId"
        :report-groups="$reportGroups"
        :submitted-reports="$submittedReports"
    />
@endsection
