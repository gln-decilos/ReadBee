@extends('layouts.evaluator-layout')

@section('content')
    <x-common.page-breadcrumb pageTitle="Reading Materials" />

    <div class="space-y-6">
        <x-evaluator.reading-materials.reading-materials-page
            :materials="$materials"
            :my-requests="$myRequests"
            :grade-levels="$gradeLevels"
        />
    </div>
@endsection
