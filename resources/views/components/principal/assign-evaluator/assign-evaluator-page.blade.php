@props([
    'schoolYears' => [],
    'selectedYearId' => null,
    'quarters' => [],
    'grades' => [],
    'schedules' => [],
    'evaluators' => [],
    'assignments' => [],
])

<div
    x-data="principalAssignEvaluatorPage(@js($schoolYears), @js($selectedYearId), @js($quarters), @js($grades), @js($schedules), @js($evaluators), @js($assignments))"
    x-init="init($el)"
    x-cloak
    data-index-url="{{ route('principal.assign-evaluator') }}"
    data-store-url="{{ route('principal.assign-evaluator.store') }}"
    data-bulk-store-url="{{ route('principal.assign-evaluator.bulk-store') }}"
    data-base-url="{{ url('/principal/assign-evaluator') }}"
    data-csrf-token="{{ csrf_token() }}"
    class="space-y-6"
>
    <div x-show="feedbackModal" x-transition.opacity class="fixed inset-0 z-99999 flex items-center justify-center bg-gray-900/60 px-4">
        <div @click.outside="closeFeedback" class="w-full max-w-md rounded-2xl bg-white p-6 text-center shadow-theme-xl dark:bg-gray-900">
            <div class="mx-auto flex h-14 w-14 items-center justify-center rounded-full" :class="feedbackType === 'success' ? 'bg-green-100 text-green-600 dark:bg-green-500/10 dark:text-green-400' : 'bg-red-100 text-red-600 dark:bg-red-500/10 dark:text-red-400'">
                <template x-if="feedbackType === 'success'">
                    <svg class="h-7 w-7" viewBox="0 0 24 24" fill="none"><path d="M20 6L9 17L4 12" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" /></svg>
                </template>
                <template x-if="feedbackType !== 'success'">
                    <svg class="h-7 w-7" viewBox="0 0 24 24" fill="none"><path d="M18 6L6 18M6 6L18 18" stroke="currentColor" stroke-width="2" stroke-linecap="round" /></svg>
                </template>
            </div>
            <h3 class="mt-4 text-lg font-semibold text-gray-900 dark:text-white" x-text="feedbackTitle"></h3>
            <p class="mt-2 text-sm text-gray-500 dark:text-gray-400" x-text="feedbackMessage"></p>
            <button type="button" @click="closeFeedback" class="mt-5 rounded-lg bg-brand-500 px-5 py-2.5 text-sm font-semibold text-white hover:bg-brand-400">OK</button>
        </div>
    </div>

    <div x-show="confirmModal" x-transition.opacity class="fixed inset-0 z-99999 flex items-center justify-center bg-gray-900/60 px-4">
        <div @click.outside="closeConfirm" class="w-full max-w-md rounded-2xl bg-white p-6 shadow-theme-xl dark:bg-gray-900">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white" x-text="confirmTitle"></h3>
            <p class="mt-2 text-sm text-gray-500 dark:text-gray-400" x-text="confirmMessage"></p>
            <div class="mt-6 flex justify-end gap-3">
                <button type="button" @click="closeConfirm" class="rounded-lg border border-gray-300 px-4 py-2.5 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:text-gray-300 dark:hover:bg-white/5">Cancel</button>
                <button type="button" @click="runConfirmAction" class="rounded-lg bg-red-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-red-700">Continue</button>
            </div>
        </div>
    </div>

    <div class="overflow-hidden rounded-3xl border border-gray-200 bg-white shadow-sm dark:border-white/[0.05] dark:bg-white/[0.03]">
        <div class="flex flex-col gap-5 border-b border-gray-200 px-6 py-6 dark:border-white/[0.05] lg:flex-row lg:items-end lg:justify-between">
            <div>
                <div class="inline-flex items-center rounded-full bg-yellow-100 px-3 py-1 text-xs font-medium text-yellow-700 dark:bg-yellow-500/10 dark:text-yellow-400">
                    Evaluator Assignment
                </div>
                <h3 class="mt-3 text-xl font-semibold text-gray-800 dark:text-white/90">Assign Evaluators</h3>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                    Assign evaluators by section. Evaluators will receive a confirmation request after assignment.
                </p>
            </div>

            <div class="w-full lg:w-auto">
                <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">School Year</label>
                <div class="relative min-w-[260px]">
                    <select
                        :value="yearPickerValue"
                        @change="changeYear($event.target.value)"
                        :disabled="loadingYear"
                        class="dark:bg-gray-900 shadow-theme-xs h-11 w-full appearance-none rounded-xl border border-gray-300 bg-transparent px-4 py-2.5 pr-10 text-sm text-gray-800 disabled:cursor-not-allowed disabled:opacity-70 dark:border-gray-700 dark:text-white/90"
                    >
                        <template x-for="year in schoolYears" :key="year.year_id">
                            <option :value="year.year_id" x-text="year.label || yearLabel(year)"></option>
                        </template>
                    </select>
                    <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center pr-3">
                        <svg x-show="loadingYear" class="h-4 w-4 animate-spin text-gray-500 dark:text-gray-400" viewBox="0 0 24 24" fill="none">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"></path>
                        </svg>
                        <svg x-show="!loadingYear" class="h-4 w-4 text-gray-500 dark:text-gray-400" fill="none" viewBox="0 0 20 20" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M6 8l4 4 4-4" />
                        </svg>
                    </div>
                </div>
            </div>
        </div>

        <div class="space-y-6 p-6">
            <section x-show="activeMode === 'single'" x-transition.opacity class="rounded-3xl border border-gray-200 bg-white shadow-sm dark:border-white/[0.05] dark:bg-white/[0.03]">
                <div class="border-b border-gray-200 p-5 dark:border-white/[0.05]">
                    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                        <div>
                            <button type="button" @click="activeMode = ''; resetForm()" class="mb-2 inline-flex items-center gap-2 text-sm font-medium text-gray-500 hover:text-gray-800 dark:text-gray-400 dark:hover:text-white">
                                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none"><path d="M15 18L9 12L15 6" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" /></svg>
                                Back to assigned evaluators
                            </button>
                            <h4 class="text-lg font-semibold text-gray-900 dark:text-white">Assign One Evaluator</h4>
                            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Current school year: <span class="font-medium text-gray-800 dark:text-gray-200" x-text="selectedYearLabel"></span></p>
                        </div>
                        <span class="inline-flex w-fit rounded-full bg-gray-100 px-3 py-1 text-xs font-medium text-gray-600 dark:bg-white/10 dark:text-gray-300">One section only</span>
                    </div>
                </div>

                <form @submit.prevent="assignEvaluator" class="grid grid-cols-1 gap-5 p-5 lg:grid-cols-2 xl:grid-cols-4">
                    <div>
                        <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">Grade Level</label>
                        <select x-model="form.grade_level_id" @change="onGradeChange" class="h-11 w-full rounded-xl border border-gray-300 bg-white px-4 py-2.5 text-sm text-gray-800 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
                            <option value="">Select grade</option>
                            <template x-for="grade in grades" :key="grade.grade_level_id">
                                <option :value="grade.grade_level_id" x-text="`Grade ${grade.grade_number}`"></option>
                            </template>
                        </select>
                        <template x-if="formErrors.grade_level_id"><p class="mt-1 text-sm text-red-600" x-text="formErrors.grade_level_id[0]"></p></template>
                    </div>

                    <div>
                        <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">Section</label>
                        <select x-model="form.section_id" class="h-11 w-full rounded-xl border border-gray-300 bg-white px-4 py-2.5 text-sm text-gray-800 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
                            <option value="">Select section</option>
                            <template x-for="section in selectedGradeSections" :key="section.section_id">
                                <option :value="section.section_id" x-text="section.adviser_name ? `${section.section_name} - Adviser: ${section.adviser_name}` : section.section_name"></option>
                            </template>
                        </select>
                        <template x-if="formErrors.section_id"><p class="mt-1 text-sm text-red-600" x-text="formErrors.section_id[0]"></p></template>
                    </div>

                    <div>
                        <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">Scheduled Assessment</label>
                        <select x-model="form.schedule_id" class="h-11 w-full rounded-xl border border-gray-300 bg-white px-4 py-2.5 text-sm text-gray-800 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
                            <option value="">Select schedule</option>
                            <template x-for="schedule in schedules" :key="schedule.schedule_id">
                                <option :value="schedule.schedule_id" x-text="schedule.label"></option>
                            </template>
                        </select>
                        <template x-if="formErrors.schedule_id"><p class="mt-1 text-sm text-red-600" x-text="formErrors.schedule_id[0]"></p></template>
                    </div>

                    <div>
                        <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">Evaluator</label>
                        <select x-model="form.evaluator_user_id" class="h-11 w-full rounded-xl border border-gray-300 bg-white px-4 py-2.5 text-sm text-gray-800 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
                            <option value="">Select evaluator</option>
                            <template x-for="evaluator in evaluators" :key="evaluator.user_id">
                                <option :value="evaluator.user_id" x-text="evaluator.label"></option>
                            </template>
                        </select>
                        <template x-if="formErrors.evaluator_user_id"><p class="mt-1 text-sm text-red-600" x-text="formErrors.evaluator_user_id[0]"></p></template>
                    </div>

                    <div x-show="selectedSchedule" class="rounded-2xl border border-blue-200 bg-blue-50 p-4 text-sm text-blue-700 dark:border-blue-500/20 dark:bg-blue-500/10 dark:text-blue-300 lg:col-span-2 xl:col-span-4">
                        <p class="font-semibold" x-text="selectedSchedule?.quarter_label || 'Quarter'"></p>
                        <p class="mt-1">Assessment date: <span x-text="formatDate(selectedSchedule?.assessment_date)"></span></p>
                        <p class="mt-1 capitalize">Schedule status: <span x-text="selectedSchedule?.status"></span></p>
                    </div>

                    <div x-show="selectedEvaluator && !selectedEvaluator.email" class="rounded-2xl border border-yellow-200 bg-yellow-50 p-4 text-sm text-yellow-700 dark:border-yellow-500/20 dark:bg-yellow-500/10 dark:text-yellow-300 lg:col-span-2 xl:col-span-4">
                        This evaluator has no email in their profile. The assignment can be saved, but no confirmation email can be sent.
                    </div>

                    <div class="flex flex-col gap-3 border-t border-gray-200 pt-5 dark:border-gray-800 sm:flex-row sm:justify-end lg:col-span-2 xl:col-span-4">
                        <button type="button" @click="resetForm" class="rounded-lg border border-gray-300 px-4 py-2.5 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:text-gray-300 dark:hover:bg-white/5">Clear</button>
                        <button type="submit" :disabled="saving" class="rounded-lg bg-brand-500 px-5 py-2.5 text-sm font-semibold text-white hover:bg-brand-400 disabled:cursor-not-allowed disabled:opacity-60">
                            <span x-show="!saving">Assign Evaluator</span>
                            <span x-show="saving">Assigning...</span>
                        </button>
                    </div>
                </form>
            </section>

            <section x-show="activeMode === 'bulk'" x-transition.opacity class="rounded-3xl border border-gray-200 bg-white shadow-sm dark:border-white/[0.05] dark:bg-white/[0.03]">
                <div class="border-b border-gray-200 p-5 dark:border-white/[0.05]">
                    <div class="flex flex-col gap-4 xl:flex-row xl:items-start xl:justify-between">
                        <div class="max-w-2xl">
                            <button type="button" @click="activeMode = ''; resetBulkForm()" class="mb-2 inline-flex items-center gap-2 text-sm font-medium text-gray-500 hover:text-gray-800 dark:text-gray-400 dark:hover:text-white">
                                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none"><path d="M15 18L9 12L15 6" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" /></svg>
                                Back to assigned evaluators
                            </button>
                            <div class="inline-flex items-center rounded-full bg-brand-100 px-3 py-1 text-xs font-medium text-brand-700 dark:bg-brand-500/10 dark:text-brand-400">
                                Recommended for many sections
                            </div>
                            <h4 class="mt-3 text-lg font-semibold text-gray-900 dark:text-white">Assign Multiple Evaluators</h4>
                            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Follow the steps below. Confirmation requests will be sent after you submit.</p>
                        </div>

                        <div class="grid grid-cols-1 gap-2 text-xs text-gray-600 dark:text-gray-300 sm:grid-cols-3 xl:min-w-[520px]">
                            <div class="rounded-2xl border border-gray-200 bg-gray-50 p-3 dark:border-gray-800 dark:bg-gray-900/50"><span class="font-semibold text-gray-900 dark:text-white">1.</span> Choose schedule</div>
                            <div class="rounded-2xl border border-gray-200 bg-gray-50 p-3 dark:border-gray-800 dark:bg-gray-900/50"><span class="font-semibold text-gray-900 dark:text-white">2.</span> Load sections</div>
                            <div class="rounded-2xl border border-gray-200 bg-gray-50 p-3 dark:border-gray-800 dark:bg-gray-900/50"><span class="font-semibold text-gray-900 dark:text-white">3.</span> Pick evaluators</div>
                        </div>
                    </div>
                </div>

                <div class="space-y-5 p-5">
                    <div class="grid grid-cols-1 gap-4 xl:grid-cols-[minmax(0,1fr)_auto] xl:items-end">
                        <div>
                            <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">Step 1: Scheduled Assessment</label>
                            <select x-model="bulkForm.schedule_id" class="h-11 w-full rounded-xl border border-gray-300 bg-white px-4 py-2.5 text-sm text-gray-800 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
                                <option value="">Select the assessment schedule</option>
                                <template x-for="schedule in schedules" :key="`bulk-schedule-${schedule.schedule_id}`">
                                    <option :value="schedule.schedule_id" x-text="schedule.label"></option>
                                </template>
                            </select>
                            <template x-if="bulkErrors.schedule_id"><p class="mt-1 text-sm text-red-600" x-text="bulkErrors.schedule_id[0]"></p></template>
                        </div>

                        <div class="flex flex-col gap-2 sm:flex-row xl:justify-end">
                            <button type="button" @click="loadAllSectionsToBulk" class="inline-flex items-center justify-center rounded-lg bg-brand-500 px-4 py-2.5 text-sm  text-white hover:bg-brand-400">
                                Load Available Sections
                            </button>
                            <button type="button" @click="addBulkRow" class="inline-flex items-center justify-center rounded-lg border border-gray-300 px-4 py-2.5 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:text-gray-300 dark:hover:bg-white/5">
                                Add One Section
                            </button>
                        </div>
                    </div>

                    <div x-show="bulkSelectedSchedule" class="rounded-2xl border border-blue-200 bg-blue-50 p-4 text-sm text-blue-700 dark:border-blue-500/20 dark:bg-blue-500/10 dark:text-blue-300">
                        <p class="font-semibold" x-text="bulkSelectedSchedule?.quarter_label || 'Selected assessment'"></p>
                        <p class="mt-1">Assessment date: <span x-text="formatDate(bulkSelectedSchedule?.assessment_date)"></span></p>
                    </div>

                    <div class="rounded-2xl border border-gray-200 bg-gray-50/70 p-4 dark:border-gray-800 dark:bg-gray-900/40">
                        <div class="flex flex-col gap-2 border-b border-gray-200 pb-4 dark:border-gray-800 sm:flex-row sm:items-center sm:justify-between">
                            <div>
                                <h5 class="font-semibold text-gray-900 dark:text-white">Step 2 and 3: Sections and Evaluators</h5>
                                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Each card is one section assignment. Choose the evaluator for each card.</p>
                            </div>
                            <span class="inline-flex w-fit rounded-full bg-white px-3 py-1 text-xs font-medium text-gray-600 shadow-sm dark:bg-white/10 dark:text-gray-300">
                                <span x-text="bulkForm.assignments.length"></span>&nbsp;section(s)
                            </span>
                        </div>

                        <div class="mt-4 space-y-3">
                            <template x-for="(row, index) in bulkForm.assignments" :key="row.key">
                                <div class="rounded-2xl border border-gray-200 bg-white p-4 shadow-sm dark:border-gray-800 dark:bg-white/[0.03]">
                                    <div class="flex flex-col gap-4 xl:flex-row xl:items-start">
                                        <div class="flex items-center gap-3 xl:w-48 xl:pt-2">
                                            <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-brand-100 text-sm font-semibold text-brand-700 dark:bg-brand-500/10 dark:text-brand-400" x-text="index + 1"></span>
                                            <div>
                                                <p class="text-sm font-semibold text-gray-900 dark:text-white">Section Assignment</p>
                                                <p class="text-xs text-gray-500 dark:text-gray-400">One evaluator per section</p>
                                            </div>
                                        </div>

                                        <div class="grid min-w-0 flex-1 grid-cols-1 gap-3 md:grid-cols-3">
                                            <div>
                                                <label class="mb-1.5 block text-xs font-medium uppercase tracking-wide text-gray-500 dark:text-gray-400">Grade</label>
                                                <select x-model="row.grade_level_id" @change="onBulkGradeChange(row)" class="h-10 w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-gray-800 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
                                                    <option value="">Select grade</option>
                                                    <template x-for="grade in grades" :key="`bulk-grade-${row.key}-${grade.grade_level_id}`">
                                                        <option :value="grade.grade_level_id" x-text="`Grade ${grade.grade_number}`"></option>
                                                    </template>
                                                </select>
                                                <template x-if="bulkErrors[`assignments.${index}.grade_level_id`]"><p class="mt-1 text-xs text-red-600" x-text="bulkErrors[`assignments.${index}.grade_level_id`][0]"></p></template>
                                            </div>

                                            <div>
                                                <label class="mb-1.5 block text-xs font-medium uppercase tracking-wide text-gray-500 dark:text-gray-400">Section</label>
                                                <select x-model="row.section_id" class="h-10 w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-gray-800 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
                                                    <option value="">Select section</option>
                                                    <template x-for="section in bulkRowSections(row)" :key="`bulk-section-${row.key}-${section.section_id}`">
                                                        <option :value="section.section_id" x-text="section.section_name"></option>
                                                    </template>
                                                </select>
                                                <template x-if="bulkErrors[`assignments.${index}.section_id`]"><p class="mt-1 text-xs text-red-600" x-text="bulkErrors[`assignments.${index}.section_id`][0]"></p></template>
                                            </div>

                                            <div>
                                                <label class="mb-1.5 block text-xs font-medium uppercase tracking-wide text-gray-500 dark:text-gray-400">Evaluator</label>
                                                <select x-model="row.evaluator_user_id" class="h-10 w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-gray-800 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
                                                    <option value="">Select evaluator</option>
                                                    <template x-for="evaluator in evaluators" :key="`bulk-evaluator-${row.key}-${evaluator.user_id}`">
                                                        <option :value="evaluator.user_id" x-text="evaluator.label"></option>
                                                    </template>
                                                </select>
                                                <template x-if="bulkErrors[`assignments.${index}.evaluator_user_id`]"><p class="mt-1 text-xs text-red-600" x-text="bulkErrors[`assignments.${index}.evaluator_user_id`][0]"></p></template>
                                            </div>
                                        </div>

                                        <div class="xl:pt-6">
                                            <button type="button" @click="removeBulkRow(index)" :disabled="bulkForm.assignments.length === 1" title="Remove section" aria-label="Remove section" class="inline-flex h-10 w-10 items-center justify-center rounded-lg border border-red-200 text-red-600 hover:bg-red-50 disabled:cursor-not-allowed disabled:opacity-40 dark:border-red-500/20 dark:hover:bg-red-500/10">
                                                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none"><path d="M6 7H18M10 11V17M14 11V17M9 7L9.5 5.5C9.7 4.9 10.2 4.5 10.8 4.5H13.2C13.8 4.5 14.3 4.9 14.5 5.5L15 7M8 7L8.6 19C8.65 19.85 9.35 20.5 10.2 20.5H13.8C14.65 20.5 15.35 19.85 15.4 19L16 7" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" /></svg>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </template>
                        </div>
                    </div>

                    <div x-show="bulkSkipped.length" class="rounded-2xl border border-yellow-200 bg-yellow-50 p-4 text-sm text-yellow-800 dark:border-yellow-500/20 dark:bg-yellow-500/10 dark:text-yellow-300">
                        <p class="font-semibold">Some rows were skipped</p>
                        <p class="mt-1 text-xs">These rows were not saved because they already exist or need checking.</p>
                        <ul class="mt-2 list-disc space-y-1 pl-5">
                            <template x-for="item in bulkSkipped" :key="`${item.row}-${item.reason}`">
                                <li><span x-text="`Row ${item.row}: ${item.reason}`"></span></li>
                            </template>
                        </ul>
                    </div>

                    <div class="flex flex-col gap-3 rounded-2xl border border-gray-200 bg-white p-4 dark:border-gray-800 dark:bg-white/[0.03] sm:flex-row sm:items-center sm:justify-between">
                        <p class="text-sm text-gray-500 dark:text-gray-400">
                            Ready to assign <span class="font-semibold text-gray-900 dark:text-white" x-text="bulkForm.assignments.length"></span> section(s).
                        </p>
                        <div class="flex flex-col gap-2 sm:flex-row sm:justify-end">
                            <button type="button" @click="resetBulkForm" class="rounded-lg border border-gray-300 px-4 py-2.5 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:text-gray-300 dark:hover:bg-white/5">Clear</button>
                            <button type="button" @click="submitBulkAssignments" :disabled="bulkSaving" class="rounded-lg bg-brand-500 px-5 py-2.5 text-sm  text-white hover:bg-brand-400 disabled:cursor-not-allowed disabled:opacity-60">
                                <span x-show="!bulkSaving">Submit Assignments</span>
                                <span x-show="bulkSaving">Submitting...</span>
                            </button>
                        </div>
                    </div>
                </div>
            </section>

            <section x-show="!activeMode" x-transition.opacity class="min-w-0 rounded-3xl border border-gray-200 bg-white shadow-sm dark:border-white/[0.05] dark:bg-white/[0.03]">
                <div class="flex flex-col gap-4 border-b border-gray-200 p-5 dark:border-white/[0.05] lg:flex-row lg:items-center lg:justify-between">
                    <div>
                        <h4 class="text-lg font-semibold text-gray-900 dark:text-white">Assigned Evaluators</h4>
                        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Review existing assignments for the selected school year.</p>
                    </div>
                    <div class="flex w-full flex-col gap-3 sm:flex-row sm:items-center sm:justify-end lg:w-auto">
                        <div class="w-full sm:w-72 lg:w-80">
                            <input type="search" x-model="search" @input="tablePage = 1" placeholder="Search assignments..." class="h-11 w-full rounded-xl border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90" />
                        </div>
                        <div class="flex shrink-0 flex-nowrap items-center gap-2">
                            <button type="button" @click="activeMode = 'single'" class="inline-flex shrink-0 items-center justify-center whitespace-nowrap rounded-lg border border-gray-300 px-3.5 py-2.5 text-sm font-medium leading-none text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:text-gray-300 dark:hover:bg-white/5">Assign One</button>
                            <button type="button" @click="activeMode = 'bulk'" class="inline-flex shrink-0 items-center justify-center whitespace-nowrap rounded-lg bg-brand-500 px-3.5 py-2.5 text-sm  leading-none text-white hover:bg-brand-400">Assign Multiple</button>
                        </div>
                    </div>
                </div>

                <div class="flex flex-wrap items-center gap-2 border-b border-gray-200 p-5 dark:border-white/[0.05]">
                    <div class="inline-flex items-center gap-2 rounded-xl border border-gray-200 bg-white px-3 py-2 dark:border-gray-800 dark:bg-white/[0.03]">
                        <span class="text-xs font-medium uppercase tracking-wide text-gray-500 dark:text-gray-400">Assignments</span>
                        <span class="text-sm font-semibold text-gray-900 dark:text-white" x-text="assignments.length"></span>
                    </div>

                    <div class="inline-flex items-center gap-2 rounded-xl border border-yellow-200 bg-yellow-50 px-3 py-2 dark:border-yellow-500/20 dark:bg-yellow-500/10">
                        <span class="text-xs font-medium uppercase tracking-wide text-yellow-700 dark:text-yellow-400">Pending</span>
                        <span class="text-sm font-semibold text-yellow-700 dark:text-yellow-400" x-text="confirmationCount('pending')"></span>
                    </div>

                    <div class="inline-flex items-center gap-2 rounded-xl border border-green-200 bg-green-50 px-3 py-2 dark:border-green-500/20 dark:bg-green-500/10">
                        <span class="text-xs font-medium uppercase tracking-wide text-green-700 dark:text-green-400">Confirmed</span>
                        <span class="text-sm font-semibold text-green-700 dark:text-green-400" x-text="confirmationCount('confirmed')"></span>
                    </div>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full min-w-[900px] divide-y divide-gray-200 dark:divide-white/[0.05]">
                        <thead class="bg-gray-50 dark:bg-gray-900/60">
                            <tr>
                                <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">Evaluator</th>
                                <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">Section</th>
                                <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">Schedule</th>
                                <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">Confirmation</th>
                                <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">Assessment</th>
                                <th class="px-5 py-3 text-right text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">Action</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 dark:divide-white/[0.05]">
                            <template x-for="assignment in paginatedAssignments" :key="assignment.assignment_id">
                                <tr class="hover:bg-gray-50 dark:hover:bg-white/[0.03]">
                                    <td class="px-5 py-4">
                                        <p class="font-medium text-gray-900 dark:text-white" x-text="assignment.evaluator_name"></p>
                                        <p class="mt-1 text-xs text-gray-500 dark:text-gray-400" x-text="assignment.evaluator_email || 'No email set'"></p>
                                    </td>
                                    <td class="px-5 py-4">
                                        <p class="text-sm font-medium text-gray-800 dark:text-gray-100" x-text="assignment.section_name"></p>
                                        <p class="mt-1 text-xs text-gray-500 dark:text-gray-400" x-text="assignment.grade_label"></p>
                                    </td>
                                    <td class="px-5 py-4">
                                        <p class="text-sm text-gray-800 dark:text-gray-100" x-text="assignment.quarter_label"></p>
                                        <p class="mt-1 text-xs text-gray-500 dark:text-gray-400" x-text="formatDate(assignment.assessment_date)"></p>
                                    </td>
                                    <td class="px-5 py-4">
                                        <span class="rounded-full px-2.5 py-1 text-xs font-medium capitalize" :class="confirmationBadgeClass(assignment.confirmation_status)" x-text="assignment.confirmation_status"></span>
                                    </td>
                                    <td class="px-5 py-4">
                                        <span class="rounded-full px-2.5 py-1 text-xs font-medium capitalize" :class="assessmentBadgeClass(assignment.assessment_status)" x-text="statusText(assignment.assessment_status)"></span>
                                    </td>
                                    <td class="px-5 py-4 text-right">
                                        <div class="flex justify-end gap-2">
                                            <button
                                                type="button"
                                                x-show="assignment.confirmation_status === 'pending'"
                                                @click="followUpEmail(assignment)"
                                                :disabled="followUpId === assignment.assignment_id"
                                                class="rounded-lg border border-gray-300 px-3 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 disabled:cursor-not-allowed disabled:opacity-60 dark:border-gray-700 dark:text-gray-300 dark:hover:bg-white/5"
                                            >
                                                <span x-show="followUpId !== assignment.assignment_id">Follow Up</span>
                                                <span x-show="followUpId === assignment.assignment_id">Sending...</span>
                                            </button>
                                            <button type="button" @click="confirmDelete(assignment)" class="rounded-lg border border-red-200 px-3 py-2 text-sm font-medium text-red-600 hover:bg-red-50 dark:border-red-500/20 dark:hover:bg-red-500/10">Delete</button>
                                        </div>
                                    </td>
                                </tr>
                            </template>

                            <tr x-show="filteredAssignments.length === 0">
                                <td colspan="6" class="px-5 py-12 text-center">
                                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white">No evaluator assignments found</h3>
                                    <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">Assign an evaluator to a section or change your search filter.</p>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div class="flex flex-col gap-3 border-t border-gray-200 px-5 py-4 dark:border-white/[0.05] sm:flex-row sm:items-center sm:justify-between">
                    <p class="text-sm text-gray-500 dark:text-gray-400">
                        Showing <span x-text="tableStartItem"></span> to <span x-text="tableEndItem"></span> of <span x-text="filteredAssignments.length"></span> assignments
                    </p>

                    <div class="flex flex-wrap gap-2">
                        <button type="button" @click="goToTablePage(tablePage - 1)" :disabled="tablePage === 1" class="rounded-lg border border-gray-300 px-3 py-2 text-sm font-medium text-gray-700 disabled:cursor-not-allowed disabled:opacity-50 dark:border-gray-700 dark:text-gray-300">
                            Previous
                        </button>

                        <template x-for="pageNumber in visibleTablePages()" :key="pageNumber">
                            <button type="button" @click="goToTablePage(pageNumber)" class="rounded-lg border px-3 py-2 text-sm font-medium" :class="tablePage === pageNumber ? 'border-brand-500 bg-brand-500 text-white' : 'border-gray-300 text-gray-700 dark:border-gray-700 dark:text-gray-300'" x-text="pageNumber"></button>
                        </template>

                        <button type="button" @click="goToTablePage(tablePage + 1)" :disabled="tablePage === tableLastPage" class="rounded-lg border border-gray-300 px-3 py-2 text-sm font-medium text-gray-700 disabled:cursor-not-allowed disabled:opacity-50 dark:border-gray-700 dark:text-gray-300">
                            Next
                        </button>
                    </div>
                </div>
            </section>
        </div>
    </div>
</div>

<script>
    function principalAssignEvaluatorPage(schoolYears, selectedYearId, quarters, grades, schedules, evaluators, assignments) {
        return {
            schoolYears: schoolYears || [],
            selectedYearId: selectedYearId,
            yearPickerValue: selectedYearId,
            quarters: quarters || [],
            grades: grades || [],
            schedules: schedules || [],
            evaluators: evaluators || [],
            assignments: assignments || [],
            activeMode: '',
            form: {
                year_id: selectedYearId || '',
                grade_level_id: '',
                section_id: '',
                schedule_id: '',
                evaluator_user_id: '',
            },
            bulkForm: {
                year_id: selectedYearId || '',
                schedule_id: '',
                assignments: [
                    {
                        key: Date.now(),
                        grade_level_id: '',
                        section_id: '',
                        evaluator_user_id: '',
                    },
                ],
            },
            formErrors: {},
            bulkErrors: {},
            bulkSkipped: [],
            loadingYear: false,
            saving: false,
            bulkSaving: false,
            followUpId: null,
            search: '',
            tablePage: 1,
            tablePerPage: 10,
            feedbackModal: false,
            feedbackType: 'success',
            feedbackTitle: '',
            feedbackMessage: '',
            confirmModal: false,
            confirmTitle: '',
            confirmMessage: '',
            confirmAction: null,
            indexUrl: '',
            storeUrl: '',
            bulkStoreUrl: '',
            baseUrl: '',
            csrfToken: '',

            init(root) {
                this.indexUrl = root.dataset.indexUrl;
                this.storeUrl = root.dataset.storeUrl;
                this.bulkStoreUrl = root.dataset.bulkStoreUrl;
                this.baseUrl = root.dataset.baseUrl;
                this.csrfToken = root.dataset.csrfToken;
                this.form.year_id = this.selectedYearId || '';
                this.bulkForm.year_id = this.selectedYearId || '';
            },

            get selectedYearLabel() {
                const year = this.schoolYears.find(item => String(item.year_id) === String(this.selectedYearId));
                return year?.label || this.yearLabel(year);
            },

            get selectedGrade() {
                return this.grades.find(grade => String(grade.grade_level_id) === String(this.form.grade_level_id)) || null;
            },

            get selectedGradeSections() {
                return this.selectedGrade ? (this.selectedGrade.sections || []) : [];
            },

            get selectedSchedule() {
                return this.schedules.find(schedule => String(schedule.schedule_id) === String(this.form.schedule_id)) || null;
            },

            get bulkSelectedSchedule() {
                return this.schedules.find(schedule => String(schedule.schedule_id) === String(this.bulkForm.schedule_id)) || null;
            },

            get selectedEvaluator() {
                return this.evaluators.find(evaluator => String(evaluator.user_id) === String(this.form.evaluator_user_id)) || null;
            },

            get filteredAssignments() {
                const term = this.search.trim().toLowerCase();

                const rows = !term
                    ? this.assignments
                    : this.assignments.filter(assignment => {
                        const haystack = [
                            assignment.evaluator_name,
                            assignment.evaluator_email,
                            assignment.section_name,
                            assignment.grade_label,
                            assignment.quarter_label,
                            assignment.school_year_label,
                            assignment.confirmation_status,
                            assignment.assessment_status,
                            assignment.report_status,
                        ].join(' ').toLowerCase();

                        return haystack.includes(term);
                    });

                return rows;
            },

            get tableLastPage() {
                return Math.max(Math.ceil(this.filteredAssignments.length / this.tablePerPage), 1);
            },

            get paginatedAssignments() {
                if (this.tablePage > this.tableLastPage) {
                    this.tablePage = this.tableLastPage;
                }

                const start = (this.tablePage - 1) * this.tablePerPage;
                return this.filteredAssignments.slice(start, start + this.tablePerPage);
            },

            get tableStartItem() {
                if (this.filteredAssignments.length === 0) return 0;
                return (this.tablePage - 1) * this.tablePerPage + 1;
            },

            get tableEndItem() {
                return Math.min(this.tablePage * this.tablePerPage, this.filteredAssignments.length);
            },

            visibleTablePages() {
                const pages = [];
                const start = Math.max(1, this.tablePage - 2);
                const end = Math.min(this.tableLastPage, this.tablePage + 2);

                for (let i = start; i <= end; i++) {
                    pages.push(i);
                }

                return pages;
            },

            goToTablePage(pageNumber) {
                if (pageNumber >= 1 && pageNumber <= this.tableLastPage) {
                    this.tablePage = pageNumber;
                }
            },

            yearLabel(year) {
                if (!year) return 'School Year';
                if (year.label) return year.label;

                const start = year.start_date ? new Date(year.start_date).getFullYear() : '';
                const end = year.end_date ? new Date(year.end_date).getFullYear() : '';
                return start && end ? `${start} - ${end}` : 'School Year';
            },

            formatDate(value) {
                if (!value) return 'No date';
                return new Date(`${value}T00:00:00`).toLocaleDateString('en-PH', {
                    year: 'numeric',
                    month: 'short',
                    day: 'numeric',
                });
            },

            statusText(value) {
                return String(value || '').replaceAll('_', ' ');
            },

            onGradeChange() {
                this.form.section_id = this.selectedGradeSections[0]?.section_id || '';
            },

            resetForm() {
                this.formErrors = {};
                this.form = {
                    year_id: this.selectedYearId || '',
                    grade_level_id: '',
                    section_id: '',
                    schedule_id: '',
                    evaluator_user_id: '',
                };
            },

            makeBulkRow() {
                return {
                    key: `${Date.now()}-${Math.random().toString(36).slice(2)}`,
                    grade_level_id: '',
                    section_id: '',
                    evaluator_user_id: '',
                };
            },

            resetBulkForm() {
                this.bulkErrors = {};
                this.bulkSkipped = [];
                this.bulkForm = {
                    year_id: this.selectedYearId || '',
                    schedule_id: '',
                    assignments: [this.makeBulkRow()],
                };
            },

            addBulkRow() {
                this.bulkForm.assignments.push(this.makeBulkRow());
            },

            removeBulkRow(index) {
                if (this.bulkForm.assignments.length === 1) return;
                this.bulkForm.assignments.splice(index, 1);
            },

            bulkRowSections(row) {
                const grade = this.grades.find(item => String(item.grade_level_id) === String(row.grade_level_id));
                return grade ? (grade.sections || []) : [];
            },

            onBulkGradeChange(row) {
                row.section_id = this.bulkRowSections(row)[0]?.section_id || '';
            },

            loadAllSectionsToBulk() {
                if (!this.bulkForm.schedule_id) {
                    this.showFeedback('Select Schedule', 'Please select a scheduled assessment first before loading all sections.', 'error');
                    return;
                }

                const assignedSectionIds = new Set(
                    this.assignments
                        .filter(item => String(item.schedule_id) === String(this.bulkForm.schedule_id))
                        .map(item => String(item.section_id))
                );

                const rows = [];

                this.grades.forEach((grade) => {
                    (grade.sections || []).forEach((section) => {
                        if (!assignedSectionIds.has(String(section.section_id))) {
                            rows.push({
                                ...this.makeBulkRow(),
                                grade_level_id: grade.grade_level_id,
                                section_id: section.section_id,
                                evaluator_user_id: '',
                            });
                        }
                    });
                });

                if (!rows.length) {
                    this.showFeedback('No Sections Available', 'All sections already have evaluator assignments for the selected schedule.', 'error');
                    return;
                }

                this.bulkErrors = {};
                this.bulkSkipped = [];
                this.bulkForm.assignments = rows;
            },

            validateBulkForm() {
                this.bulkErrors = {};
                let hasError = false;

                if (!this.bulkForm.schedule_id) {
                    this.bulkErrors.schedule_id = ['Scheduled assessment is required.'];
                    hasError = true;
                }

                this.bulkForm.assignments.forEach((row, index) => {
                    if (!row.grade_level_id) {
                        this.bulkErrors[`assignments.${index}.grade_level_id`] = ['Grade is required.'];
                        hasError = true;
                    }

                    if (!row.section_id) {
                        this.bulkErrors[`assignments.${index}.section_id`] = ['Section is required.'];
                        hasError = true;
                    }

                    if (!row.evaluator_user_id) {
                        this.bulkErrors[`assignments.${index}.evaluator_user_id`] = ['Evaluator is required.'];
                        hasError = true;
                    }
                });

                return !hasError;
            },

            async submitBulkAssignments() {
                this.bulkSkipped = [];

                if (!this.validateBulkForm()) {
                    this.showFeedback('Incomplete Rows', 'Please complete the schedule, grade, section, and evaluator for each row.', 'error');
                    return;
                }

                this.bulkSaving = true;

                const payload = {
                    year_id: this.selectedYearId || this.bulkForm.year_id,
                    schedule_id: this.bulkForm.schedule_id,
                    assignments: this.bulkForm.assignments.map((row) => ({
                        grade_level_id: row.grade_level_id,
                        section_id: row.section_id,
                        evaluator_user_id: row.evaluator_user_id,
                    })),
                };

                try {
                    const response = await fetch(this.bulkStoreUrl, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': this.csrfToken,
                            'Accept': 'application/json',
                        },
                        body: JSON.stringify(payload),
                    });

                    const data = await response.json();
                    this.bulkSkipped = data.skipped || [];

                    if (!response.ok) {
                        this.bulkErrors = data.errors || {};
                        this.showFeedback('Error', data.message || 'Failed to create bulk evaluator assignments.', 'error');
                        return;
                    }

                    const newAssignments = data.assignments || [];
                    const existing = this.assignments.filter((item) => !newAssignments.some((created) => String(created.assignment_id) === String(item.assignment_id)));
                    this.assignments = [...newAssignments, ...existing];
                    this.tablePage = 1;
                    this.showFeedback('Success', data.message || 'Bulk evaluator assignments created successfully.', 'success');
                    this.activeMode = '';

                    if (!this.bulkSkipped.length) {
                        this.resetBulkForm();
                    }
                } catch (error) {
                    console.error('Bulk assign evaluator error:', error);
                    this.showFeedback('Error', 'An error occurred while creating bulk evaluator assignments.', 'error');
                } finally {
                    this.bulkSaving = false;
                }
            },

            async changeYear(yearId) {
                if (!yearId || this.loadingYear) return;

                this.loadingYear = true;

                try {
                    const response = await fetch(`${this.indexUrl}?ajax=1&year_id=${encodeURIComponent(yearId)}&_t=${Date.now()}`, {
                        headers: {
                            'Accept': 'application/json',
                            'X-Requested-With': 'XMLHttpRequest',
                        },
                        cache: 'no-store',
                    });

                    const data = await response.json();

                    if (!response.ok || !data.success) {
                        this.showFeedback('Error', data.message || 'Failed to load school year data.', 'error');
                        return;
                    }

                    this.selectedYearId = data.selectedYearId;
                    this.yearPickerValue = data.selectedYearId;
                    this.quarters = data.quarters || [];
                    this.grades = data.grades || [];
                    this.schedules = data.schedules || [];
                    this.evaluators = data.evaluators || [];
                    this.assignments = data.assignments || [];
                    this.search = '';
                    this.tablePage = 1;
                    this.resetForm();
                    this.resetBulkForm();
                    this.activeMode = '';
                } catch (error) {
                    console.error('Change evaluator assignment year error:', error);
                    this.showFeedback('Error', 'An error occurred while loading evaluator assignment data.', 'error');
                } finally {
                    this.loadingYear = false;
                }
            },

            async assignEvaluator() {
                this.formErrors = {};
                this.saving = true;

                try {
                    const response = await fetch(this.storeUrl, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': this.csrfToken,
                            'Accept': 'application/json',
                        },
                        body: JSON.stringify(this.form),
                    });

                    const data = await response.json();

                    if (!response.ok) {
                        this.formErrors = data.errors || {};
                        this.showFeedback('Error', data.message || 'Failed to assign evaluator.', 'error');
                        return;
                    }

                    this.assignments = [data.assignment, ...this.assignments.filter(item => String(item.assignment_id) !== String(data.assignment.assignment_id))];
                    this.tablePage = 1;
                    this.showFeedback('Success', data.message || 'Evaluator assigned successfully.', 'success');
                    this.resetForm();
                    this.activeMode = '';
                } catch (error) {
                    console.error('Assign evaluator error:', error);
                    this.showFeedback('Error', 'An error occurred while assigning the evaluator.', 'error');
                } finally {
                    this.saving = false;
                }
            },

            async followUpEmail(assignment) {
                if (!assignment) return;

                if (assignment.confirmation_status === 'confirmed') {
                    this.showFeedback(
                        'Already Confirmed',
                        'This evaluator has already confirmed the assignment. No follow-up is needed.',
                        'success'
                    );
                    return;
                }

                this.followUpId = assignment.assignment_id;

                try {
                    const response = await fetch(`${this.baseUrl}/${assignment.assignment_id}/resend`, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': this.csrfToken,
                            'Accept': 'application/json',
                        },
                    });

                    const data = await response.json();

                    if (!response.ok) {
                        this.showFeedback('Error', data.message || 'Failed to send follow-up email.', 'error');
                        return;
                    }

                    this.showFeedback('Success', data.message || 'Follow-up email sent successfully.', 'success');
                } catch (error) {
                    console.error('Follow Up evaluator confirmation error:', error);
                    this.showFeedback('Error', 'An error occurred while sending the follow-up email.', 'error');
                } finally {
                    this.followUpId = null;
                }
            },

            confirmDelete(assignment) {
                this.confirmTitle = 'Delete evaluator assignment?';
                this.confirmMessage = `${assignment.evaluator_name} will be removed from ${assignment.section_name}. This is allowed only if no assessment records reference the assignment.`;
                this.confirmAction = () => this.deleteAssignment(assignment);
                this.confirmModal = true;
            },

            closeConfirm() {
                this.confirmModal = false;
                this.confirmTitle = '';
                this.confirmMessage = '';
                this.confirmAction = null;
            },

            runConfirmAction() {
                if (typeof this.confirmAction === 'function') this.confirmAction();
            },

            async deleteAssignment(assignment) {
                try {
                    const response = await fetch(`${this.baseUrl}/${assignment.assignment_id}`, {
                        method: 'DELETE',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': this.csrfToken,
                            'Accept': 'application/json',
                        },
                    });

                    const data = await response.json();

                    if (!response.ok) {
                        this.showFeedback('Error', data.message || 'Failed to delete evaluator assignment.', 'error');
                        return;
                    }

                    this.assignments = this.assignments.filter(item => String(item.assignment_id) !== String(assignment.assignment_id));
                    this.closeConfirm();
                    this.showFeedback('Success', data.message || 'Evaluator assignment deleted successfully.', 'success');
                } catch (error) {
                    console.error('Delete evaluator assignment error:', error);
                    this.showFeedback('Error', 'An error occurred while deleting the evaluator assignment.', 'error');
                }
            },

            showFeedback(title, message, type = 'success') {
                this.feedbackTitle = title;
                this.feedbackMessage = message;
                this.feedbackType = type;
                this.feedbackModal = true;
            },

            closeFeedback() {
                this.feedbackModal = false;
                this.feedbackTitle = '';
                this.feedbackMessage = '';
                this.feedbackType = 'success';
            },

            confirmationCount(status) {
                return this.assignments.filter(assignment => assignment.confirmation_status === status).length;
            },

            confirmationBadgeClass(status) {
                if (status === 'confirmed') return 'bg-green-100 text-green-700 dark:bg-green-500/10 dark:text-green-400';
                return 'bg-yellow-100 text-yellow-700 dark:bg-yellow-500/10 dark:text-yellow-400';
            },

            assessmentBadgeClass(status) {
                if (status === 'completed') return 'bg-green-100 text-green-700 dark:bg-green-500/10 dark:text-green-400';
                if (status === 'ongoing') return 'bg-blue-100 text-blue-700 dark:bg-blue-500/10 dark:text-blue-400';
                return 'bg-gray-100 text-gray-700 dark:bg-gray-500/10 dark:text-gray-300';
            },
        };
    }
</script>

<style>
    [x-cloak] { display: none !important; }
</style>
