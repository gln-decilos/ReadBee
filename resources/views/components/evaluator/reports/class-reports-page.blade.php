@php
    use Illuminate\Support\Str;
@endphp

@props([
    'schoolYears' => [],
    'selectedYearId' => null,
    'assignments' => [],
])

<div class="space-y-6">
    @if (session('success'))
        <div class="rounded-xl border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-800 dark:border-green-900/50 dark:bg-green-900/20 dark:text-green-300">
            {{ session('success') }}
        </div>
    @endif

    @if (session('error'))
        <div class="rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800 dark:border-red-900/50 dark:bg-red-900/20 dark:text-red-300">
            {{ session('error') }}
        </div>
    @endif

    <section class="rounded-2xl border border-gray-200 bg-white p-5 dark:border-gray-800 dark:bg-white/[0.03]">
        <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
            <div>
                <h1 class="text-xl font-semibold text-gray-900 dark:text-white">Class Report Generation</h1>
                <p class="mt-1 max-w-3xl text-sm text-gray-500 dark:text-gray-400">
                    Generate separate English and Filipino reports once all pupils in the assigned grade level and section have assessment records.
                </p>
            </div>

            <form method="GET" action="{{ route('evaluator.reports') }}" class="flex flex-col gap-2 sm:flex-row sm:items-center">
                <label for="year_id" class="text-sm font-medium text-gray-700 dark:text-gray-300">School Year</label>
                <select id="year_id" name="year_id" onchange="this.form.submit()" class="h-11 rounded-lg border border-gray-300 bg-white px-3 text-sm text-gray-700 shadow-sm focus:border-brand-300 focus:outline-none focus:ring-3 focus:ring-brand-500/10 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-200">
                    @forelse ($schoolYears as $year)
                        <option value="{{ $year['year_id'] }}" @selected($selectedYearId === $year['year_id'])>
                            {{ $year['label'] ?? 'School Year' }}
                        </option>
                    @empty
                        <option value="">No school year found</option>
                    @endforelse
                </select>
            </form>
        </div>
    </section>

    @forelse ($assignments as $assignment)
        <section class="overflow-hidden rounded-2xl border border-gray-200 bg-white dark:border-gray-800 dark:bg-white/[0.03]">
            <div class="border-b border-gray-200 px-5 py-4 dark:border-gray-800">
                <div class="flex flex-col gap-3 lg:flex-row lg:items-start lg:justify-between">
                    <div>
                        <h2 class="text-lg font-semibold text-gray-900 dark:text-white">
                            {{ $assignment['grade_label'] }} - {{ $assignment['section_name'] }}
                        </h2>
                        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                            {{ $assignment['school_name'] }} · {{ $assignment['quarter_label'] }} · {{ $assignment['school_year_label'] }}
                        </p>
                    </div>
                    <span class="inline-flex w-fit rounded-full border border-gray-200 px-3 py-1 text-xs font-medium text-gray-600 dark:border-gray-700 dark:text-gray-300">
                        Report Status: {{ Str::headline($assignment['report_status'] ?? 'not_submitted') }}
                    </span>
                </div>
            </div>

            <div class="grid grid-cols-1 gap-4 p-5 lg:grid-cols-2">
                @foreach (['english' => 'English', 'filipino' => 'Filipino'] as $language => $label)
                    @php
                        $languageData = $assignment['languages'][$language] ?? [];
                        $isReady = (bool) ($languageData['is_ready'] ?? false);
                        $total = (int) ($languageData['total_pupils'] ?? 0);
                        $assessed = (int) ($languageData['assessed_count'] ?? 0);
                        $missing = (int) ($languageData['missing_count'] ?? 0);
                    @endphp

                    <div class="rounded-xl border border-gray-200 p-4 dark:border-gray-800">
                        <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                            <div>
                                <h3 class="text-base font-semibold text-gray-900 dark:text-white">{{ $label }} Report</h3>
                                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                                    {{ $assessed }} of {{ $total }} pupils assessed
                                </p>
                            </div>
                            <span class="inline-flex w-fit rounded-full px-2.5 py-1 text-xs font-medium {{ $isReady ? 'bg-green-100 text-green-700 dark:bg-green-500/10 dark:text-green-400' : 'bg-gray-100 text-gray-700 dark:bg-white/10 dark:text-gray-300' }}">
                                {{ $isReady ? 'Ready' : $missing . ' missing' }}
                            </span>
                        </div>

                        <div class="mt-4 h-2 overflow-hidden rounded-full bg-gray-100 dark:bg-gray-800">
                            <div class="h-full rounded-full bg-gray-900 dark:bg-gray-200" style="width: {{ $total > 0 ? round(($assessed / max($total, 1)) * 100) : 0 }}%"></div>
                        </div>

                        <div class="mt-4 flex flex-col gap-2 sm:flex-row">
                            <a href="{{ route('evaluator.reports.show', ['assignmentId' => $assignment['assignment_id'], 'language' => $language]) }}" class="inline-flex h-10 items-center justify-center rounded-lg border border-gray-300 px-4 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:text-gray-300 dark:hover:bg-white/5">
                                Generate / Preview
                            </a>

                            <form method="POST" action="{{ route('evaluator.reports.submit', ['assignmentId' => $assignment['assignment_id'], 'language' => $language]) }}" onsubmit="return confirm('Submit the {{ $label }} report to the principal?')">
                                @csrf
                                <button type="submit" @disabled(! $isReady) class="inline-flex h-10 w-full items-center justify-center rounded-lg bg-gray-900 px-4 text-sm font-medium text-white hover:bg-gray-800 disabled:cursor-not-allowed disabled:opacity-50 dark:bg-white dark:text-gray-900 dark:hover:bg-gray-200 sm:w-auto">
                                    Submit to Principal
                                </button>
                            </form>
                        </div>

                        @if (! empty($languageData['submitted_at']))
                            <p class="mt-3 text-xs text-gray-500 dark:text-gray-400">
                                Last submitted: {{ \Carbon\Carbon::parse($languageData['submitted_at'])->format('M d, Y h:i A') }}
                            </p>
                        @endif
                    </div>
                @endforeach
            </div>
        </section>
    @empty
        <section class="rounded-2xl border border-gray-200 bg-white px-5 py-12 text-center dark:border-gray-800 dark:bg-white/[0.03]">
            <h2 class="text-lg font-semibold text-gray-900 dark:text-white">No confirmed assignments found</h2>
            <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">Reports will appear here after a grade level and section are assigned to you.</p>
        </section>
    @endforelse
</div>
