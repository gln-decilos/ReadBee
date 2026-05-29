@extends('layouts.principal-layout')

@section('content')
    <x-common.page-breadcrumb pageTitle="Submitted Reports" />

    <x-principal.reports.reports-page
        :school-years="$schoolYears"
        :selected-year-id="$selectedYearId"
        :report-groups="$reportGroups"
    />
@endsection
