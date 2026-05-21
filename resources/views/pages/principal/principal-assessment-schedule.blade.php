@extends('layouts.principal-layout')

@section('content')
    <x-common.page-breadcrumb pageTitle="Assessment Schedule" />

    <div class="space-y-6">
        <x-principal.assessment-schedule.assessment-schedule-page
            :school-years="$schoolYears"
            :selected-year-id="$selectedYearId"
            :quarters="$quarters"
            :schedules="$schedules"
        />
    </div>
@endsection
