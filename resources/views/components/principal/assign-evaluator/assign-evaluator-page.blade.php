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
            <button type="button" @click="closeFeedback" class="mt-5 rounded-lg bg-brand-500 px-5 py-2.5 text-sm font-semibold text-gray-900 hover:bg-brand-400">OK</button>
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
                    Assessment Evaluator Assignment
                </div>
                <h3 class="mt-3 text-xl font-semibold text-gray-800 dark:text-white/90">Assign Evaluators</h3>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                    Select a grade, section, scheduled assessment, and evaluator. The evaluator will receive an email confirmation request.
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

        <div class="grid grid-cols-1 gap-6 p-6 xl:grid-cols-[420px_minmax(0,1fr)]">
            <section class="rounded-3xl border border-gray-200 bg-gray-50 p-5 dark:border-white/[0.05] dark:bg-gray-900/40">
                <div>
                    <h4 class="text-lg font-semibold text-gray-900 dark:text-white">New Assignment</h4>
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Current school year: <span class="font-medium text-gray-800 dark:text-gray-200" x-text="selectedYearLabel"></span></p>
                </div>

                <form @submit.prevent="assignEvaluator" class="mt-5 space-y-4">
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

                    <div x-show="selectedSchedule" class="rounded-2xl border border-blue-200 bg-blue-50 p-4 text-sm text-blue-700 dark:border-blue-500/20 dark:bg-blue-500/10 dark:text-blue-300">
                        <p class="font-semibold" x-text="selectedSchedule?.quarter_label || 'Quarter'"></p>
                        <p class="mt-1">Assessment date: <span x-text="formatDate(selectedSchedule?.assessment_date)"></span></p>
                        <p class="mt-1 capitalize">Schedule status: <span x-text="selectedSchedule?.status"></span></p>
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

                    <div x-show="selectedEvaluator && !selectedEvaluator.email" class="rounded-2xl border border-yellow-200 bg-yellow-50 p-4 text-sm text-yellow-700 dark:border-yellow-500/20 dark:bg-yellow-500/10 dark:text-yellow-300">
                        This evaluator has no email in their profile. The assignment can be saved, but no confirmation email can be sent.
                    </div>

                    <div class="flex justify-end gap-3 border-t border-gray-200 pt-4 dark:border-gray-800">
                        <button type="button" @click="resetForm" class="rounded-lg border border-gray-300 px-4 py-2.5 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:text-gray-300 dark:hover:bg-white/5">Clear</button>
                        <button type="submit" :disabled="saving" class="rounded-lg bg-brand-500 px-5 py-2.5 text-sm font-semibold text-gray-900 hover:bg-brand-400 disabled:cursor-not-allowed disabled:opacity-60">
                            <span x-show="!saving">Assign Evaluator</span>
                            <span x-show="saving">Assigning...</span>
                        </button>
                    </div>
                </form>
            </section>

            <section class="min-w-0 rounded-3xl border border-gray-200 bg-white dark:border-white/[0.05] dark:bg-white/[0.03]">
                <div class="flex flex-col gap-4 border-b border-gray-200 p-5 dark:border-white/[0.05] lg:flex-row lg:items-center lg:justify-between">
                    <div>
                        <h4 class="text-lg font-semibold text-gray-900 dark:text-white">Assigned Evaluators</h4>
                        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Manage evaluator assignments for the selected school year.</p>
                    </div>
                    <div class="w-full max-w-sm">
                        <input type="search" x-model="search" @input="tablePage = 1" placeholder="Search evaluator, section, grade, status" class="h-11 w-full rounded-xl border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90" />
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
                            <button type="button" @click="goToTablePage(pageNumber)" class="rounded-lg border px-3 py-2 text-sm font-medium" :class="tablePage === pageNumber ? 'border-brand-500 bg-brand-500 text-gray-900' : 'border-gray-300 text-gray-700 dark:border-gray-700 dark:text-gray-300'" x-text="pageNumber"></button>
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
            form: {
                year_id: selectedYearId || '',
                grade_level_id: '',
                section_id: '',
                schedule_id: '',
                evaluator_user_id: '',
            },
            formErrors: {},
            loadingYear: false,
            saving: false,
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
            baseUrl: '',
            csrfToken: '',

            init(root) {
                this.indexUrl = root.dataset.indexUrl;
                this.storeUrl = root.dataset.storeUrl;
                this.baseUrl = root.dataset.baseUrl;
                this.csrfToken = root.dataset.csrfToken;
                this.form.year_id = this.selectedYearId || '';
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
