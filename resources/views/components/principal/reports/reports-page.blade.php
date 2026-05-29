@php
    use Illuminate\Support\Str;
@endphp

@props([
    'schoolYears' => [],
    'selectedYearId' => null,
    'reportGroups' => [],
])

@php
    $feedbackType = session('success') ? 'success' : (session('error') ? 'error' : (session('info') ? 'info' : null));
    $feedbackMessage = session('success') ?: (session('error') ?: session('info'));
@endphp

<style>
    [x-cloak] { display: none !important; }

    .readbee-report-progress-bar {
        background: linear-gradient(90deg, #ffca03 0%, #f59e0b 100%);
    }

    .dark .readbee-report-progress-bar {
        background: linear-gradient(90deg, #facc15 0%, #f59e0b 100%);
    }
</style>

<div
    x-data="{
        confirmOpen: false,
        feedbackOpen: {{ $feedbackMessage ? 'true' : 'false' }},
        submittedPanel: null,
        pendingForm: null,
        pendingTitle: '',
        pendingMessage: '',
        confirmSubmit(event, languageLabel, gradeLabel, quarterLabel) {
            this.pendingForm = event.target;
            this.pendingTitle = `Submit ${languageLabel} Consolidated Report?`;
            this.pendingMessage = `Please confirm that the ${languageLabel} consolidated report for ${gradeLabel} - ${quarterLabel} is complete and ready to submit to the district supervisor.`;
            this.confirmOpen = true;
        },
        submitConfirmed() {
            if (!this.pendingForm) return;
            this.confirmOpen = false;
            this.pendingForm.submit();
        },
        closeConfirm() {
            this.confirmOpen = false;
            this.pendingForm = null;
            this.pendingTitle = '';
            this.pendingMessage = '';
        },
        closeFeedback() {
            this.feedbackOpen = false;
        },
        openSubmittedReports(panelKey) {
            this.submittedPanel = panelKey;
            this.$nextTick(() => window.scrollTo({ top: 0, behavior: 'smooth' }));
        },
        backToConsolidated() {
            this.submittedPanel = null;
            this.$nextTick(() => window.scrollTo({ top: 0, behavior: 'smooth' }));
        }
    }"
    x-cloak
    class="space-y-6"
>
    <section class="rounded-2xl border border-gray-200 bg-white p-5 shadow-theme-xs dark:border-gray-800 dark:bg-white/[0.03]">
        <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
            <div>
                <h1 class="text-xl font-semibold text-gray-900 dark:text-white">Principal Reports</h1>
                <p class="mt-1 max-w-3xl text-sm leading-6 text-gray-500 dark:text-gray-400">
                    Review evaluator-submitted class reports first, then generate the consolidated grade-level summary for district submission.
                </p>
            </div>

            <form method="GET" action="{{ route('principal.reports') }}" class="flex flex-col gap-2 sm:flex-row sm:items-center">
                <label for="year_id" class="text-sm font-medium text-gray-700 dark:text-gray-300">School Year</label>
                <select id="year_id" name="year_id" onchange="this.form.submit()" class="h-11 rounded-lg border border-gray-300 bg-white px-3 text-sm text-gray-700 shadow-theme-xs focus:border-brand-300 focus:outline-none focus:ring-3 focus:ring-brand-500/10 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-200">
                    @forelse ($schoolYears as $year)
                        <option value="{{ $year['year_id'] }}" @selected($selectedYearId === $year['year_id'])>
                            {{ $year['label'] ?? 'School Year' }}
                        </option>
                    @empty
                        <option value="">No submitted reports yet</option>
                    @endforelse
                </select>
            </form>
        </div>
    </section>


    @foreach ($reportGroups as $group)
        @foreach (['english' => 'English', 'filipino' => 'Filipino'] as $language => $label)
            @php
                $languageData = $group['languages'][$language] ?? null;
                $classReports = $languageData['class_reports'] ?? [];
                $sectionCount = (int) ($languageData['submitted_sections_count'] ?? 0);
                $expectedSectionCount = (int) ($languageData['expected_sections_count'] ?? 0);
                $denominator = max($expectedSectionCount, $sectionCount, 1);
                $progress = min(100, round(($sectionCount / $denominator) * 100));
                $panelKey = 'submitted-' . md5(($group['grade_level_id'] ?? '') . '|' . ($group['quarter_id'] ?? '') . '|' . $language);
            @endphp

            @if ($languageData)
                <section x-show="submittedPanel === @js($panelKey)" x-transition class="rounded-2xl border border-gray-200 bg-white p-5 shadow-theme-xs dark:border-gray-800 dark:bg-white/[0.03]">
                    <div class="flex flex-col gap-4 border-b border-gray-100 pb-4 dark:border-gray-800 lg:flex-row lg:items-start lg:justify-between">
                        <div>
                            <button type="button" @click="backToConsolidated()" class="mb-3 inline-flex h-9 items-center gap-2 rounded-lg border border-gray-300 px-3 text-sm font-semibold text-gray-700 transition hover:bg-gray-50 dark:border-gray-700 dark:text-gray-300 dark:hover:bg-white/5">
                                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M15 6 9 12l6 6" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"/></svg>
                                Back to Consolidated Reports
                            </button>
                            <h2 class="text-lg font-semibold text-gray-900 dark:text-white">{{ $label }} Submitted Class Reports</h2>
                            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                                {{ $group['grade_label'] }} · {{ $group['quarter_label'] }} · {{ $group['school_year_label'] }}
                            </p>
                        </div>
                        <div class="w-full max-w-sm">
                            <div class="mb-2 flex items-center justify-between gap-3 text-xs font-medium text-gray-500 dark:text-gray-400">
                                <span>Section submission progress</span>
                                <span>{{ $progress }}%</span>
                            </div>
                            <div class="h-2 overflow-hidden rounded-full bg-gray-100 dark:bg-gray-800">
                                <div class="readbee-report-progress-bar h-full rounded-full" style="width: {{ $progress }}%"></div>
                            </div>
                            <p class="mt-2 text-right text-xs text-gray-500 dark:text-gray-400">
                                {{ $sectionCount }} of {{ $denominator }} section{{ $denominator === 1 ? '' : 's' }} submitted
                            </p>
                        </div>
                    </div>

                    <div class="mt-5 grid grid-cols-1 gap-3 lg:grid-cols-2">
                        @forelse ($classReports as $classReport)
                            <div class="flex flex-col gap-3 rounded-xl border border-gray-200 bg-gray-50 p-4 dark:border-gray-800 dark:bg-white/[0.03] sm:flex-row sm:items-center sm:justify-between">
                                <div>
                                    <p class="text-sm font-semibold text-gray-900 dark:text-white">{{ $classReport['section_name'] ?? 'Section' }}</p>
                                    <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                                        Submitted {{ ! empty($classReport['submitted_at']) ? \Carbon\Carbon::parse($classReport['submitted_at'])->format('M d, Y h:i A') : '—' }}
                                    </p>
                                </div>
                                @if (! empty($classReport['class_report_id']))
                                    <a href="{{ route('principal.reports.class-report', ['classReportId' => $classReport['class_report_id']]) }}" class="inline-flex h-9 items-center justify-center rounded-lg border border-gray-300 px-3 text-sm font-medium text-gray-700 transition hover:bg-gray-50 dark:border-gray-700 dark:text-gray-300 dark:hover:bg-white/5">
                                        Preview Class Report
                                    </a>
                                @endif
                            </div>
                        @empty
                            <div class="rounded-xl border border-dashed border-gray-300 px-4 py-8 text-center text-sm text-gray-500 dark:border-gray-700 dark:text-gray-400 lg:col-span-2">
                                No submitted class reports are available for this language yet.
                            </div>
                        @endforelse
                    </div>

                    @if (! empty($languageData['missing_section_labels']))
                        <div class="mt-5 rounded-xl border border-orange-200 bg-orange-50 p-3 dark:border-orange-500/20 dark:bg-orange-500/10">
                            <p class="text-xs font-semibold uppercase tracking-wide text-orange-700 dark:text-orange-300">Still Waiting For</p>
                            <div class="mt-2 flex flex-wrap gap-2">
                                @foreach ($languageData['missing_section_labels'] as $sectionLabel)
                                    <span class="rounded-full bg-white px-2.5 py-1 text-xs font-medium text-orange-700 ring-1 ring-orange-100 dark:bg-white/10 dark:text-orange-200 dark:ring-orange-500/20">
                                        {{ $sectionLabel }}
                                    </span>
                                @endforeach
                            </div>
                        </div>
                    @endif
                </section>
            @endif
        @endforeach
    @endforeach

    @forelse ($reportGroups as $group)
        <section x-show="!submittedPanel" x-transition class="overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-theme-xs dark:border-gray-800 dark:bg-white/[0.03]">
            <div class="border-b border-gray-200 px-5 py-4 dark:border-gray-800">
                <div class="flex flex-col gap-3 lg:flex-row lg:items-start lg:justify-between">
                    <div>
                        <h2 class="text-lg font-semibold text-gray-900 dark:text-white">
                            {{ $group['grade_label'] }} Reports
                        </h2>
                        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                            {{ $group['quarter_label'] }} · {{ $group['school_year_label'] }}
                        </p>
                    </div>
                    <span class="inline-flex w-fit rounded-full border border-gray-200 bg-gray-50 px-3 py-1 text-xs font-medium text-gray-600 dark:border-gray-700 dark:bg-white/5 dark:text-gray-300">
                        {{ count($group['languages'] ?? []) }} language report{{ count($group['languages'] ?? []) === 1 ? '' : 's' }} available
                    </span>
                </div>
            </div>

            <div class="grid grid-cols-1 gap-4 p-5 lg:grid-cols-2">
                @foreach (['english' => 'English', 'filipino' => 'Filipino'] as $language => $label)
                    @php
                        $languageData = $group['languages'][$language] ?? null;
                    @endphp

                    @if ($languageData)
                        @php
                            $status = strtolower((string) ($languageData['report_status'] ?? 'draft'));
                            $isSubmitted = (bool) ($languageData['is_submitted'] ?? false);
                            $isReady = (bool) ($languageData['is_ready'] ?? false);
                            $isComplete = (bool) ($languageData['is_complete'] ?? false);
                            $canSubmit = $isReady && $isComplete && ! $isSubmitted;
                            $sectionCount = (int) ($languageData['submitted_sections_count'] ?? 0);
                            $expectedSectionCount = (int) ($languageData['expected_sections_count'] ?? 0);
                            $denominator = max($expectedSectionCount, $sectionCount, 1);
                            $missingSectionCount = (int) ($languageData['missing_sections_count'] ?? 0);
                            $totalPupils = (int) ($languageData['total_pupils'] ?? 0);
                            $progress = min(100, round(($sectionCount / $denominator) * 100));
                            $classReports = $languageData['class_reports'] ?? [];
                            $panelKey = 'submitted-' . md5(($group['grade_level_id'] ?? '') . '|' . ($group['quarter_id'] ?? '') . '|' . $language);
                            $statusText = $isSubmitted
                                ? 'Submitted'
                                : ($isComplete
                                    ? 'Ready'
                                    : 'Incomplete');
                            $statusClass = $isSubmitted
                                ? 'border border-[#f2c94c]/40 bg-[#fff7d6] text-[#92400e] dark:border-[#f2c94c]/30 dark:bg-[#f2c94c]/15 dark:text-[#f2c94c]'
                                : ($isComplete
                                    ? 'border border-green-200 bg-green-50 text-green-700 dark:border-green-500/20 dark:bg-green-500/10 dark:text-green-400'
                                    : 'border border-orange-200 bg-orange-50 text-orange-700 dark:border-orange-500/20 dark:bg-orange-500/10 dark:text-orange-300');
                        @endphp

                        <div class="rounded-xl border border-gray-200 p-4 dark:border-gray-800">
                            <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                                <div>
                                    <h3 class="text-base font-semibold text-gray-900 dark:text-white">{{ $label }} Reports</h3>
                                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                                        {{ $sectionCount }} of {{ $denominator }} section{{ $denominator === 1 ? '' : 's' }} submitted · {{ $totalPupils }} pupil{{ $totalPupils === 1 ? '' : 's' }}
                                    </p>
                                </div>
                                <span class="inline-flex w-fit items-center rounded-full px-2.5 py-1 text-xs font-semibold {{ $statusClass }}">
                                    {{ $statusText }}
                                </span>
                            </div>

                            <div class="mt-4">
                                <div class="mb-2 flex items-center justify-between gap-3 text-xs font-medium text-gray-500 dark:text-gray-400">
                                    <span>Section submission progress</span>
                                    <span>{{ $progress }}%</span>
                                </div>
                                <div class="h-2 overflow-hidden rounded-full bg-gray-100 dark:bg-gray-800">
                                    <div class="readbee-report-progress-bar h-full rounded-full" style="width: {{ $progress }}%"></div>
                                </div>
                            </div>

                            <div class="mt-5 flex flex-col gap-3 rounded-xl border border-gray-200 bg-gray-50 p-3 dark:border-gray-800 dark:bg-white/[0.03] sm:flex-row sm:items-center sm:justify-between">
                                <div>
                                    <p class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Submitted Class Reports</p>
                                    <p class="mt-1 text-sm text-gray-600 dark:text-gray-300">
                                        {{ count($classReports) }} submitted report{{ count($classReports) === 1 ? '' : 's' }} available for preview.
                                    </p>
                                </div>
                                <div class="relative inline-flex w-full justify-end sm:w-auto">
                                    <button
                                        type="button"
                                        @click="openSubmittedReports(@js($panelKey))"
                                        @disabled(count($classReports) === 0)
                                        aria-label="View submitted reports"
                                        title="View submitted reports"
                                        class="peer inline-flex h-9 w-9 items-center justify-center rounded-lg border border-gray-300 text-gray-700 transition hover:bg-gray-50 disabled:cursor-not-allowed disabled:border-gray-200 disabled:bg-gray-100 disabled:!text-gray-400 dark:border-gray-700 dark:text-gray-300 dark:hover:bg-white/5 dark:disabled:border-gray-800 dark:disabled:bg-white/10 dark:disabled:!text-gray-500"
                                    >
                                        <svg class="h-4.5 w-4.5" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                            <path d="M2.75 12s3.5-6.25 9.25-6.25S21.25 12 21.25 12s-3.5 6.25-9.25 6.25S2.75 12 2.75 12Z" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/>
                                            <path d="M12 14.75A2.75 2.75 0 1 0 12 9.25a2.75 2.75 0 0 0 0 5.5Z" stroke="currentColor" stroke-width="1.8"/>
                                        </svg>
                                    </button>
                                    <span class="pointer-events-none absolute right-0 top-full z-20 mt-2 whitespace-nowrap rounded-lg bg-gray-900 px-2.5 py-1.5 text-xs font-medium text-white opacity-0 shadow-lg transition peer-hover:opacity-100 peer-focus:opacity-100 dark:bg-white dark:text-gray-900">
                                        View submitted reports
                                    </span>
                                </div>
                            </div>

                            @if (! empty($languageData['missing_section_labels']))
                                <div class="mt-4 rounded-xl border border-orange-200 bg-orange-50 p-3 dark:border-orange-500/20 dark:bg-orange-500/10">
                                    <p class="text-xs font-semibold uppercase tracking-wide text-orange-700 dark:text-orange-300">Waiting for Section Report{{ $missingSectionCount === 1 ? '' : 's' }}</p>
                                    <div class="mt-2 flex flex-wrap gap-2">
                                        @foreach ($languageData['missing_section_labels'] as $sectionLabel)
                                            <span class="rounded-full bg-white px-2.5 py-1 text-xs font-medium text-orange-700 ring-1 ring-orange-100 dark:bg-white/10 dark:text-orange-200 dark:ring-orange-500/20">
                                                {{ $sectionLabel }}
                                            </span>
                                        @endforeach
                                    </div>
                                </div>
                            @endif

                            <div class="mt-4 flex flex-col gap-2 sm:flex-row">
                                <a href="{{ route('principal.reports.show', ['gradeLevelId' => $group['grade_level_id'], 'yearId' => $selectedYearId, 'quarterId' => $group['quarter_id'], 'language' => $language]) }}" class="inline-flex h-9 items-center justify-center rounded-lg border border-gray-300 px-3 text-sm font-medium text-gray-700 transition hover:bg-gray-50 dark:border-gray-700 dark:text-gray-300 dark:hover:bg-white/5">
                                    Preview Consolidated
                                </a>

                                <form method="POST" action="{{ route('principal.reports.submit', ['gradeLevelId' => $group['grade_level_id'], 'yearId' => $selectedYearId, 'quarterId' => $group['quarter_id'], 'language' => $language]) }}" @submit.prevent="confirmSubmit($event, '{{ $label }}', '{{ $group['grade_label'] }}', '{{ $group['quarter_label'] }}')">
                                    @csrf
                                    <button
                                        type="submit"
                                        @disabled(! $canSubmit)
                                        class="inline-flex h-9 w-full items-center justify-center rounded-lg px-3 text-sm font-semibold text-white transition sm:w-auto {{ $isSubmitted ? 'cursor-not-allowed bg-gray-100 !text-gray-500 dark:bg-white/10 dark:!text-gray-400' : 'bg-brand-500 hover:bg-brand-600 disabled:cursor-not-allowed disabled:bg-gray-100 disabled:!text-gray-400 dark:disabled:bg-white/10 dark:disabled:!text-gray-500' }}"
                                    >
                                        {{ $isSubmitted ? 'Submitted' : 'Submit to District' }}
                                    </button>
                                </form>
                            </div>

                            @unless ($isComplete || $isSubmitted)
                                <p class="mt-3 text-xs text-gray-500 dark:text-gray-400">
                                    Submit will be enabled once all expected sections have submitted their {{ $label }} class report.
                                </p>
                            @endunless

                            @if (! empty($languageData['submitted_at']))
                                <p class="mt-3 text-xs text-gray-500 dark:text-gray-400">
                                    Submitted to district: {{ \Carbon\Carbon::parse($languageData['submitted_at'])->format('M d, Y h:i A') }}
                                </p>
                            @elseif (! empty($languageData['latest_class_report_at']))
                                <p class="mt-3 text-xs text-gray-500 dark:text-gray-400">
                                    Latest evaluator submission: {{ \Carbon\Carbon::parse($languageData['latest_class_report_at'])->format('M d, Y h:i A') }}
                                </p>
                            @endif
                        </div>
                    @endif
                @endforeach
            </div>
        </section>
    @empty
        <section class="rounded-2xl border border-gray-200 bg-white px-5 py-12 text-center shadow-theme-xs dark:border-gray-800 dark:bg-white/[0.03]">
            <div class="mx-auto flex h-14 w-14 items-center justify-center rounded-full bg-gray-100 text-gray-500 dark:bg-white/10 dark:text-gray-300">
                <svg class="h-7 w-7" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                    <path d="M6.75 3.75h7.5L18.75 8.25v12H6.75v-16.5Z" stroke="currentColor" stroke-width="1.7" stroke-linejoin="round"/>
                    <path d="M14.25 3.75v4.5h4.5M9 12h6M9 15h6M9 18h3" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
            </div>
            <h2 class="mt-4 text-lg font-semibold text-gray-900 dark:text-white">No submitted class reports found</h2>
            <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">Evaluator-submitted English and Filipino class reports will appear here for review and consolidation.</p>
        </section>
    @endforelse

    <div x-show="confirmOpen" x-transition.opacity class="fixed inset-0 z-[99998] bg-gray-950/45 backdrop-blur-sm" @click="closeConfirm()"></div>
    <div x-show="confirmOpen" x-transition class="fixed inset-0 z-[99999] flex items-center justify-center p-4" role="dialog" aria-modal="true">
        <div class="w-full max-w-md rounded-2xl border border-gray-200 bg-white p-5 shadow-2xl dark:border-gray-800 dark:bg-gray-950" @click.stop>
            <div class="flex items-start gap-3">
                <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-full bg-[#fff7d6] text-gray-900 dark:bg-[#f2c94c]/15 dark:text-[#f2c94c]">
                    <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                        <path d="M9 12.5 11 14.5 15.5 9.5" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/>
                        <path d="M12 21a9 9 0 1 0 0-18 9 9 0 0 0 0 18Z" stroke="currentColor" stroke-width="1.8"/>
                    </svg>
                </div>
                <div class="min-w-0">
                    <h3 class="text-lg font-semibold text-gray-950 dark:text-white" x-text="pendingTitle"></h3>
                    <p class="mt-1 text-sm leading-6 text-gray-600 dark:text-gray-300" x-text="pendingMessage"></p>
                </div>
            </div>

            <div class="mt-6 flex flex-col-reverse gap-2 sm:flex-row sm:justify-end">
                <button type="button" @click="closeConfirm()" class="inline-flex h-9 items-center justify-center rounded-lg border border-gray-300 px-3 text-sm font-semibold text-gray-700 transition hover:bg-gray-50 dark:border-gray-700 dark:text-gray-300 dark:hover:bg-white/5">
                    Cancel
                </button>
                <button type="button" @click="submitConfirmed()" class="inline-flex h-9 items-center justify-center rounded-lg bg-brand-500 px-3 text-sm font-semibold text-white hover:bg-brand-600">
                    Yes, Submit
                </button>
            </div>
        </div>
    </div>

    @if ($feedbackMessage)
        <div x-show="feedbackOpen" x-transition.opacity class="fixed inset-0 z-[99998] bg-gray-950/45 backdrop-blur-sm" @click="closeFeedback()"></div>
        <div x-show="feedbackOpen" x-transition class="fixed inset-0 z-[99999] flex items-center justify-center p-4" role="dialog" aria-modal="true">
            <div class="w-full max-w-md rounded-2xl border border-gray-200 bg-white p-5 shadow-2xl dark:border-gray-800 dark:bg-gray-950" @click.stop>
                <div class="flex items-start gap-3">
                    <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-full {{ $feedbackType === 'error' ? 'bg-red-50 text-red-600 dark:bg-red-500/10 dark:text-red-300' : 'bg-[#fff7d6] text-gray-900 dark:bg-[#f2c94c]/15 dark:text-[#f2c94c]' }}">
                        @if ($feedbackType === 'error')
                            <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M12 8v5M12 17h.01" stroke="currentColor" stroke-width="2" stroke-linecap="round"/><path d="M10.3 4.2 2.9 17a2 2 0 0 0 1.7 3h14.8a2 2 0 0 0 1.7-3L13.7 4.2a2 2 0 0 0-3.4 0Z" stroke="currentColor" stroke-width="1.8"/></svg>
                        @else
                            <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M9 12.5 11 14.5 15.5 9.5" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/><path d="M12 21a9 9 0 1 0 0-18 9 9 0 0 0 0 18Z" stroke="currentColor" stroke-width="1.8"/></svg>
                        @endif
                    </div>
                    <div class="min-w-0">
                        <h3 class="text-lg font-semibold text-gray-950 dark:text-white">
                            {{ $feedbackType === 'error' ? 'Action Needed' : 'Report Updated' }}
                        </h3>
                        <p class="mt-1 text-sm leading-6 text-gray-600 dark:text-gray-300">{{ $feedbackMessage }}</p>
                    </div>
                </div>
                <div class="mt-6 flex justify-end">
                    <button type="button" @click="closeFeedback()" class="inline-flex h-9 items-center justify-center rounded-lg border border-gray-300 px-3 text-sm font-semibold text-gray-700 transition hover:bg-gray-50 dark:border-gray-700 dark:text-gray-300 dark:hover:bg-white/5">
                        Close
                    </button>
                </div>
            </div>
        </div>
    @endif
</div>
