@extends('layouts.evaluator-layout')

@section('content')
    <x-common.page-breadcrumb pageTitle="My Assignments" />

    <div class="space-y-6">
        <x-evaluator.assignments.assignment-confirmation-page
            :school-years="$schoolYears"
            :selected-year-id="$selectedYearId"
            :assignments="$assignments"
        />
    </div>
@endsection
