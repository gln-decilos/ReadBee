@props([
    'schoolYears' => [],
    'selectedYearId' => null,
    'assignments' => [],
])

<div
    x-data="evaluatorAssignmentConfirmationPage(@js($schoolYears), @js($selectedYearId), @js($assignments))"
    x-init="init($el)"
    x-cloak
    data-index-url="{{ route('evaluator.assignments') }}"
    data-base-url="{{ url('/evaluator/assignments') }}"
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

    <div class="overflow-hidden rounded-3xl border border-gray-200 bg-white shadow-sm dark:border-white/[0.05] dark:bg-white/[0.03]">
        <div class="flex flex-col gap-5 border-b border-gray-200 px-6 py-6 dark:border-white/[0.05] lg:flex-row lg:items-end lg:justify-between">
            <div>
                <div class="inline-flex items-center rounded-full bg-yellow-100 px-3 py-1 text-xs font-medium text-yellow-700 dark:bg-yellow-500/10 dark:text-yellow-400">
                    Mandatory Confirmation
                </div>
                <h3 class="mt-3 text-xl font-semibold text-gray-800 dark:text-white/90">My Assigned Assessments</h3>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                    These are the assessment assignments assigned to your evaluator account. Pending assignments only have one action: confirm.
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

        <div class="grid grid-cols-1 gap-4 border-b border-gray-200 p-6 dark:border-white/[0.05] sm:grid-cols-3">
            <div class="rounded-2xl border border-gray-200 bg-white p-4 dark:border-gray-800 dark:bg-white/[0.03]">
                <p class="text-xs font-medium uppercase tracking-wide text-gray-500 dark:text-gray-400">Total Assigned</p>
                <p class="mt-2 text-2xl font-semibold text-gray-900 dark:text-white" x-text="assignments.length"></p>
            </div>
            <div class="rounded-2xl border border-yellow-200 bg-yellow-50 p-4 dark:border-yellow-500/20 dark:bg-yellow-500/10">
                <p class="text-xs font-medium uppercase tracking-wide text-yellow-700 dark:text-yellow-400">Need Confirmation</p>
                <p class="mt-2 text-2xl font-semibold text-yellow-700 dark:text-yellow-400" x-text="confirmationCount('pending')"></p>
            </div>
            <div class="rounded-2xl border border-green-200 bg-green-50 p-4 dark:border-green-500/20 dark:bg-green-500/10">
                <p class="text-xs font-medium uppercase tracking-wide text-green-700 dark:text-green-400">Confirmed</p>
                <p class="mt-2 text-2xl font-semibold text-green-700 dark:text-green-400" x-text="confirmationCount('confirmed')"></p>
            </div>
        </div>

        <div class="flex flex-col gap-4 border-b border-gray-200 p-5 dark:border-white/[0.05] lg:flex-row lg:items-center lg:justify-between">
            <div>
                <h4 class="text-lg font-semibold text-gray-900 dark:text-white">Assignment List</h4>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Confirm every pending assignment assigned under your name.</p>
            </div>
            <div class="w-full max-w-sm">
                <input type="search" x-model="search" @input="tablePage = 1" placeholder="Search section, grade, school, quarter, status" class="h-11 w-full rounded-xl border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90" />
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full min-w-[900px] divide-y divide-gray-200 dark:divide-white/[0.05]">
                <thead class="bg-gray-50 dark:bg-gray-900/60">
                    <tr>
                        <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">Assignment</th>
                        <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">Section</th>
                        <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">Assigned By</th>
                        <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">Confirmation</th>
                        <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">Assessment</th>
                        <th class="px-5 py-3 text-right text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-white/[0.05]">
                    <template x-for="assignment in paginatedAssignments" :key="assignment.assignment_id">
                        <tr class="hover:bg-gray-50 dark:hover:bg-white/[0.03]">
                            <td class="px-5 py-4">
                                <p class="font-medium text-gray-900 dark:text-white" x-text="assignment.quarter_label"></p>
                                <p class="mt-1 text-xs text-gray-500 dark:text-gray-400" x-text="formatDate(assignment.assessment_date)"></p>
                                <p class="mt-1 text-xs text-gray-500 dark:text-gray-400" x-text="assignment.school_name"></p>
                            </td>
                            <td class="px-5 py-4">
                                <p class="text-sm font-medium text-gray-800 dark:text-gray-100" x-text="assignment.section_name"></p>
                                <p class="mt-1 text-xs text-gray-500 dark:text-gray-400" x-text="assignment.grade_label"></p>
                                <p class="mt-1 text-xs text-gray-500 dark:text-gray-400" x-text="assignment.adviser_name ? `Adviser: ${assignment.adviser_name}` : 'No adviser set'"></p>
                            </td>
                            <td class="px-5 py-4">
                                <p class="text-sm font-medium text-gray-800 dark:text-gray-100" x-text="assignment.assigned_by_name"></p>
                                <p class="mt-1 text-xs text-gray-500 dark:text-gray-400" x-text="assignment.assigned_by_email || 'No email set'"></p>
                            </td>
                            <td class="px-5 py-4">
                                <span class="rounded-full px-2.5 py-1 text-xs font-medium capitalize" :class="confirmationBadgeClass(assignment.confirmation_status)" x-text="statusText(assignment.confirmation_status)"></span>
                            </td>
                            <td class="px-5 py-4">
                                <span class="rounded-full px-2.5 py-1 text-xs font-medium capitalize" :class="assessmentBadgeClass(assignment.assessment_status)" x-text="statusText(assignment.assessment_status)"></span>
                            </td>
                            <td class="px-5 py-4 text-right">
                                <button
                                    type="button"
                                    x-show="assignment.confirmation_status !== 'confirmed'"
                                    @click="confirmAssignment(assignment)"
                                    :disabled="confirmingId === assignment.assignment_id"
                                    class="rounded-lg bg-brand-500 px-4 py-2.5 text-sm font-semibold text-gray-900 hover:bg-brand-400 disabled:cursor-not-allowed disabled:opacity-60"
                                >
                                    <span x-show="confirmingId !== assignment.assignment_id">Confirm</span>
                                    <span x-show="confirmingId === assignment.assignment_id">Confirming...</span>
                                </button>
                                <span x-show="assignment.confirmation_status === 'confirmed'" class="inline-flex rounded-lg border border-green-200 bg-green-50 px-4 py-2.5 text-sm font-medium text-green-700 dark:border-green-500/20 dark:bg-green-500/10 dark:text-green-400">
                                    Confirmed
                                </span>
                            </td>
                        </tr>
                    </template>

                    <tr x-show="filteredAssignments.length === 0">
                        <td colspan="6" class="px-5 py-12 text-center">
                            <h3 class="text-lg font-semibold text-gray-900 dark:text-white">No assignments found</h3>
                            <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">No assigned assessments match this school year or search filter.</p>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <div class="flex flex-col gap-3 border-t border-gray-200 px-5 py-4 dark:border-white/[0.05] sm:flex-row sm:items-center sm:justify-between">
            <p class="text-sm text-gray-500 dark:text-gray-400">
                Showing <span x-text="tableStartItem"></span> to <span x-text="tableEndItem"></span> of <span x-text="filteredAssignments.length"></span> assignments
            </p>
            <div class="flex items-center gap-2">
                <button type="button" @click="previousPage" :disabled="tablePage === 1" class="rounded-lg border border-gray-300 px-3 py-2 text-sm font-medium text-gray-700 disabled:cursor-not-allowed disabled:opacity-50 dark:border-gray-700 dark:text-gray-300">Previous</button>
                <span class="text-sm text-gray-500 dark:text-gray-400">Page <span x-text="tablePage"></span> of <span x-text="totalPages"></span></span>
                <button type="button" @click="nextPage" :disabled="tablePage >= totalPages" class="rounded-lg border border-gray-300 px-3 py-2 text-sm font-medium text-gray-700 disabled:cursor-not-allowed disabled:opacity-50 dark:border-gray-700 dark:text-gray-300">Next</button>
            </div>
        </div>
    </div>
</div>

@once
    @push('scripts')
        <script>
            function evaluatorAssignmentConfirmationPage(schoolYears, selectedYearId, assignments) {
                return {
                    schoolYears: schoolYears || [],
                    selectedYearId: selectedYearId || '',
                    yearPickerValue: selectedYearId || '',
                    assignments: assignments || [],
                    search: '',
                    tablePage: 1,
                    perPage: 8,
                    loadingYear: false,
                    confirmingId: null,
                    feedbackModal: false,
                    feedbackTitle: '',
                    feedbackMessage: '',
                    feedbackType: 'success',
                    indexUrl: '',
                    baseUrl: '',
                    csrfToken: '',

                    init(root) {
                        this.indexUrl = root.dataset.indexUrl;
                        this.baseUrl = root.dataset.baseUrl;
                        this.csrfToken = root.dataset.csrfToken;

                        if (!this.yearPickerValue && this.schoolYears.length > 0) {
                            this.yearPickerValue = this.schoolYears[0].year_id;
                        }
                    },

                    get filteredAssignments() {
                        const term = this.search.trim().toLowerCase();

                        if (!term) {
                            return this.assignments;
                        }

                        return this.assignments.filter((assignment) => [
                            assignment.school_name,
                            assignment.quarter_label,
                            assignment.section_name,
                            assignment.grade_label,
                            assignment.adviser_name,
                            assignment.assigned_by_name,
                            assignment.confirmation_status,
                            assignment.assessment_status,
                            this.formatDate(assignment.assessment_date),
                        ].filter(Boolean).some(value => String(value).toLowerCase().includes(term)));
                    },

                    get totalPages() {
                        return Math.max(1, Math.ceil(this.filteredAssignments.length / this.perPage));
                    },

                    get paginatedAssignments() {
                        if (this.tablePage > this.totalPages) {
                            this.tablePage = this.totalPages;
                        }

                        const start = (this.tablePage - 1) * this.perPage;
                        return this.filteredAssignments.slice(start, start + this.perPage);
                    },

                    get tableStartItem() {
                        if (this.filteredAssignments.length === 0) {
                            return 0;
                        }

                        return (this.tablePage - 1) * this.perPage + 1;
                    },

                    get tableEndItem() {
                        return Math.min(this.tablePage * this.perPage, this.filteredAssignments.length);
                    },

                    confirmationCount(status) {
                        return this.assignments.filter(assignment => assignment.confirmation_status === status).length;
                    },

                    previousPage() {
                        if (this.tablePage > 1) {
                            this.tablePage -= 1;
                        }
                    },

                    nextPage() {
                        if (this.tablePage < this.totalPages) {
                            this.tablePage += 1;
                        }
                    },

                    async changeYear(yearId) {
                        this.yearPickerValue = yearId;
                        this.loadingYear = true;

                        try {
                            const url = new URL(this.indexUrl, window.location.origin);
                            url.searchParams.set('year_id', yearId);
                            url.searchParams.set('ajax', '1');

                            const response = await fetch(url.toString(), {
                                headers: { 'Accept': 'application/json' },
                            });

                            const data = await response.json();

                            if (!response.ok || data.success === false) {
                                throw new Error(data.message || 'Failed to load assignments.');
                            }

                            this.selectedYearId = data.selectedYearId;
                            this.assignments = data.assignments || [];
                            this.tablePage = 1;
                        } catch (error) {
                            console.error('Change evaluator assignment year error:', error);
                            this.showFeedback('Error', error.message || 'An error occurred while loading assignments.', 'error');
                        } finally {
                            this.loadingYear = false;
                        }
                    },

                    async confirmAssignment(assignment) {
                        this.confirmingId = assignment.assignment_id;

                        try {
                            const response = await fetch(`${this.baseUrl}/${assignment.assignment_id}/confirm`, {
                                method: 'PATCH',
                                headers: {
                                    'Accept': 'application/json',
                                    'Content-Type': 'application/json',
                                    'X-CSRF-TOKEN': this.csrfToken,
                                },
                            });

                            const data = await response.json();

                            if (!response.ok) {
                                throw new Error(data.message || 'Failed to confirm the assignment.');
                            }

                            const updatedAssignment = data.assignment || { ...assignment, confirmation_status: 'confirmed' };
                            const index = this.assignments.findIndex(item => item.assignment_id === assignment.assignment_id);

                            if (index !== -1) {
                                this.assignments.splice(index, 1, updatedAssignment);
                            }

                            this.showFeedback('Assignment Confirmed', data.message || 'Assignment confirmed successfully.', 'success');
                        } catch (error) {
                            console.error('Evaluator assignment confirmation error:', error);
                            this.showFeedback('Error', error.message || 'An error occurred while confirming the assignment.', 'error');
                        } finally {
                            this.confirmingId = null;
                        }
                    },

                    confirmationBadgeClass(status) {
                        if (status === 'confirmed') {
                            return 'bg-green-100 text-green-700 dark:bg-green-500/10 dark:text-green-400';
                        }

                        return 'bg-yellow-100 text-yellow-700 dark:bg-yellow-500/10 dark:text-yellow-400';
                    },

                    assessmentBadgeClass(status) {
                        if (status === 'completed') {
                            return 'bg-green-100 text-green-700 dark:bg-green-500/10 dark:text-green-400';
                        }

                        if (status === 'in_progress') {
                            return 'bg-blue-100 text-blue-700 dark:bg-blue-500/10 dark:text-blue-400';
                        }

                        return 'bg-gray-100 text-gray-700 dark:bg-white/5 dark:text-gray-300';
                    },

                    statusText(status) {
                        return String(status || 'pending').replaceAll('_', ' ');
                    },

                    formatDate(date) {
                        if (!date) {
                            return 'No date';
                        }

                        return new Date(date).toLocaleDateString(undefined, {
                            year: 'numeric',
                            month: 'short',
                            day: 'numeric',
                        });
                    },

                    yearLabel(year) {
                        if (!year || !year.start_date || !year.end_date) {
                            return 'School Year';
                        }

                        return `${new Date(year.start_date).getFullYear()} - ${new Date(year.end_date).getFullYear()}`;
                    },

                    showFeedback(title, message, type = 'success') {
                        this.feedbackTitle = title;
                        this.feedbackMessage = message;
                        this.feedbackType = type;
                        this.feedbackModal = true;
                    },

                    closeFeedback() {
                        this.feedbackModal = false;
                    },
                };
            }
        </script>
    @endpush
@endonce
