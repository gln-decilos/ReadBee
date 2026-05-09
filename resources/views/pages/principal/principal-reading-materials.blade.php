@extends('layouts.principal-layout')

@section('content')
    <x-common.page-breadcrumb pageTitle="Reading Materials" />

    <div class="space-y-6">
        <x-principal.reading-materials.reading-materials-page
            :materials="$materials"
            :grade-levels="$gradeLevels"
            :page="$page"
            :per-page="$perPage"
        />
    </div>
@endsection
