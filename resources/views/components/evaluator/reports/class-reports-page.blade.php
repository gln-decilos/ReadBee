@php
    use Illuminate\Support\Str;
@endphp

@props([
    'schoolYears' => [],
    'selectedYearId' => null,
    'assignments' => [],
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
        pendingForm: null,
        pendingTitle: '',
        pendingMessage: '',
        confirmSubmit(event, languageLabel) {
            this.pendingForm = event.target;
            this.pendingTitle = `Submit ${languageLabel} Report?`;
            this.pendingMessage = `Please confirm that the ${languageLabel} class report is complete and ready to submit to the principal.`;
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
        }
    }"
    x-cloak
    class="space-y-6"
>
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
                        $status = strtolower((string) ($languageData['report_status'] ?? 'draft'));
                        $isSubmitted = in_array($status, ['submitted', 'reviewed', 'approved'], true);
                        $isReturned = $status === 'returned';
                        $isReady = (bool) ($languageData['is_ready'] ?? false);
                        $canSubmit = $isReady && ! $isSubmitted;
                        $total = (int) ($languageData['total_pupils'] ?? 0);
                        $assessed = (int) ($languageData['assessed_count'] ?? 0);
                        $missing = (int) ($languageData['missing_count'] ?? 0);
                        $progress = $total > 0 ? round(($assessed / max($total, 1)) * 100) : 0;
                        $statusText = $isSubmitted ? 'Submitted' : ($isReturned ? 'Returned' : ($isReady ? 'Ready' : $missing . ' missing'));
                        $statusClass = $isSubmitted
                            ? 'border border-[#f2c94c]/40 bg-[#fff7d6] text-[#92400e] dark:border-[#f2c94c]/30 dark:bg-[#f2c94c]/15 dark:text-[#f2c94c]'
                            : ($isReturned
                                ? 'border border-orange-200 bg-orange-50 text-orange-700 dark:border-orange-500/20 dark:bg-orange-500/10 dark:text-orange-300'
                                : ($isReady
                                    ? 'border border-green-200 bg-green-50 text-green-700 dark:border-green-500/20 dark:bg-green-500/10 dark:text-green-400'
                                    : 'border border-gray-200 bg-gray-50 text-gray-700 dark:border-gray-700 dark:bg-white/10 dark:text-gray-300'));
                    @endphp

                    <div class="rounded-xl border border-gray-200 p-4 dark:border-gray-800">
                        <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                            <div>
                                <h3 class="text-base font-semibold text-gray-900 dark:text-white">{{ $label }} Report</h3>
                                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                                    {{ $assessed }} of {{ $total }} pupils assessed
                                </p>
                            </div>
                            <span class="inline-flex w-fit items-center rounded-full px-2.5 py-1 text-xs font-semibold {{ $statusClass }}">
                                {{ $statusText }}
                            </span>
                        </div>

                        <div class="mt-4 h-2 overflow-hidden rounded-full bg-gray-100 dark:bg-gray-800">
                            <div class="readbee-report-progress-bar h-full rounded-full" style="width: {{ $progress }}%"></div>
                        </div>

                        <div class="mt-4 flex flex-col gap-2 sm:flex-row">
                            <a href="{{ route('evaluator.reports.show', ['assignmentId' => $assignment['assignment_id'], 'language' => $language]) }}" class="inline-flex h-9 items-center justify-center rounded-lg border border-gray-300 px-3 text-sm font-medium text-gray-700 transition hover:bg-gray-50 dark:border-gray-700 dark:text-gray-300 dark:hover:bg-white/5">
                                Generate / Preview
                            </a>

                            <form method="POST" action="{{ route('evaluator.reports.submit', ['assignmentId' => $assignment['assignment_id'], 'language' => $language]) }}" @submit.prevent="confirmSubmit($event, '{{ $label }}')">
                                @csrf
                                <button
                                    type="submit"
                                    @disabled(! $canSubmit)
                                    class="inline-flex h-9 w-full items-center justify-center rounded-lg border px-3 text-theme-sm font-medium transition sm:w-auto {{ $isSubmitted ? 'cursor-not-allowed border-gray-200 bg-gray-100 text-gray-500 dark:border-gray-800 dark:bg-white/10 dark:text-gray-400' : 'border-brand-500 bg-brand-500 text-white shadow-theme-xs hover:border-brand-600 hover:bg-brand-600 disabled:cursor-not-allowed disabled:border-gray-200 disabled:bg-gray-100 disabled:text-gray-400 dark:border-brand-500 dark:bg-brand-500 dark:text-white dark:hover:border-brand-600 dark:hover:bg-brand-600 dark:disabled:border-gray-800 dark:disabled:bg-white/10 dark:disabled:text-gray-500' }}"
                                >
                                    {{ $isSubmitted ? 'Submitted' : 'Submit to Principal' }}
                                </button>
                            </form>
                        </div>

                        @if (! empty($languageData['submitted_at']))
                            <p class="mt-3 text-xs text-gray-500 dark:text-gray-400">
                                Submitted: {{ \Carbon\Carbon::parse($languageData['submitted_at'])->format('M d, Y h:i A') }}
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
                <button type="button" @click="closeConfirm()" class="inline-flex h-9 items-center justify-center rounded-lg border border-gray-300 px-3 text-theme-sm font-medium text-gray-700 transition hover:bg-gray-50 dark:border-gray-700 dark:text-gray-300 dark:hover:bg-white/5">
                    Cancel
                </button>
                <button type="button" @click="submitConfirmed()" class="inline-flex h-9 items-center justify-center rounded-lg border border-brand-500 bg-brand-500 px-3 text-theme-sm font-medium text-white shadow-theme-xs transition hover:border-brand-600 hover:bg-brand-600">
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
                            {{ $feedbackType === 'error' ? 'Action Needed' : 'Class Report Updated' }}
                        </h3>
                        <p class="mt-1 text-sm leading-6 text-gray-600 dark:text-gray-300">{{ $feedbackMessage }}</p>
                    </div>
                </div>
                <div class="mt-6 flex justify-end">
                    <button type="button" @click="closeFeedback()" class="inline-flex h-9 items-center justify-center rounded-lg border border-gray-300 px-3 text-theme-sm font-medium text-gray-700 transition hover:bg-gray-50 dark:border-gray-700 dark:text-gray-300 dark:hover:bg-white/5">
                        Close
                    </button>
                </div>
            </div>
        </div>
    @endif
</div>
