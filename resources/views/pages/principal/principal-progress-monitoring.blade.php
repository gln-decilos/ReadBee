@extends('layouts.principal-layout')

@section('content')
    <x-principal.progress-monitoring.progress-monitoring-page
        :school-years="$schoolYears"
        :selected-year-id="$selectedYearId"
        :summary="$summary"
        :grades="$grades"
    />
@endsection
