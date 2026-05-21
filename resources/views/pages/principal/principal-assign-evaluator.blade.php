@extends('layouts.principal-layout')

@section('content')
    <x-common.page-breadcrumb pageTitle="Assign Evaluator" />

    <div class="space-y-6">
        <x-principal.assign-evaluator.assign-evaluator-page
            :school-years="$schoolYears"
            :selected-year-id="$selectedYearId"
            :quarters="$quarters"
            :grades="$grades"
            :schedules="$schedules"
            :evaluators="$evaluators"
            :assignments="$assignments"
        />
    </div>
@endsection
