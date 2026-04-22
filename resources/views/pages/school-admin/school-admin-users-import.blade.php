@extends('layouts.school-admin-layout')

@section('content')
    <x-common.page-breadcrumb pageTitle="Import User Accounts" />

    <div class="space-y-6">
        <div class="overflow-hidden rounded-2xl border border-gray-200 bg-white pt-4 dark:border-white/[0.05] dark:bg-white/[0.03]">

            <div class="mb-4 px-6">
                <h3 class="text-lg font-semibold text-gray-800 dark:text-white/90">
                    Upload CSV File
                </h3>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                    Download the template, fill in the user details, upload the CSV file, then continue to validation.
                </p>
            </div>

            <div class="px-6 pb-6">

                <!-- INFO CARD -->
                <div class="mb-6">
                    <div class="rounded-2xl border border-blue-200 bg-blue-50 p-4 dark:border-blue-500/20 dark:bg-blue-500/10">
                        <h4 class="text-sm font-semibold text-blue-700 dark:text-blue-400">
                            Information
                        </h4>

                        <p class="mt-2 text-sm text-blue-700 dark:text-blue-300">
                            CSV must have columns:
                            <span class="font-semibold">full_name</span>,
                            <span class="font-semibold">email</span>, and
                            <span class="font-semibold">role</span>.
                        </p>

                        <p class="mt-1 text-sm text-blue-700 dark:text-blue-300">
                            Maximum file size:
                            <span class="font-semibold">10 MB</span>.
                        </p>
                    </div>
                </div>

                <!-- ACTION BUTTONS -->
                <div class="mb-6 flex flex-wrap gap-3">
                    <a href="{{ route('school-admin.users.import.template') }}">
                        <x-ui.button variant="outline">
                            Download Template
                        </x-ui.button>
                    </a>

                    <a href="{{ route('school-admin.users.index') }}">
                        <x-ui.button variant="outline">
                            Back to Users
                        </x-ui.button>
                    </a>
                </div>

                <!-- UPLOAD FORM -->
                <form action="{{ route('school-admin.users.import.preview') }}" method="POST" enctype="multipart/form-data">
                    @csrf

                    <div class="rounded-2xl border-2 border-dashed border-gray-300 bg-gray-50 p-8 text-center dark:border-gray-700 dark:bg-gray-900">

                        <label for="csv_file" class="block cursor-pointer">
                            <div class="mx-auto mb-4 flex h-16 w-16 items-center justify-center rounded-2xl bg-brand-50 dark:bg-brand-500/10">
                                <svg class="h-8 w-8 text-brand-500" fill="none" viewBox="0 0 24 24">
                                    <path
                                        d="M12 16V4M12 4L8 8M12 4L16 8M4 15V18C4 19.1046 4.89543 20 6 20H18C19.1046 20 20 19.1046 20 18V15"
                                        stroke="currentColor"
                                        stroke-width="1.5"
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                    />
                                </svg>
                            </div>

                            <h4 class="text-base font-semibold text-gray-800 dark:text-white">
                                Click to upload CSV file
                            </h4>

                            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                                Only CSV files are supported. Maximum file size is 10 MB.
                            </p>
                        </label>

                        <input
                            id="csv_file"
                            name="csv_file"
                            type="file"
                            accept=".csv,text/csv"
                            class="mt-4 block w-full rounded-lg border border-gray-300 bg-white px-4 py-2.5 text-sm text-gray-800 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90"
                            required
                        />

                        @error('csv_file')
                            <p class="mt-2 text-sm text-red-500">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="mt-6 flex justify-end">
                        <x-ui.button variant="primary" type="submit">
                            Import and Validate
                        </x-ui.button>
                    </div>
                </form>

            </div>
        </div>
    </div>
@endsection
