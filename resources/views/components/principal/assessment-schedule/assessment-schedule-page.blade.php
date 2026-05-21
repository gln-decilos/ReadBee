@props([
    'schoolYears' => [],
    'selectedYearId' => null,
    'quarters' => [],
    'schedules' => [],
])

<div
    x-data="principalAssessmentSchedulePage(@js($schoolYears), @js($selectedYearId), @js($quarters), @js($schedules))"
    x-init="init()"
    x-cloak
    data-schedule-base-url="{{ url('/principal/assessment-schedule') }}"
    data-schedule-index-url="{{ route('principal.assessment-schedule') }}"
    data-schedule-store-url="{{ route('principal.assessment-schedule.store') }}"
    data-csrf-token="{{ csrf_token() }}"
    class="space-y-6"
>
    <div x-show="successModal" x-transition.opacity class="fixed inset-0 z-[100020] flex items-center justify-center bg-gray-900/60 px-4">
        <div @click.outside="successModal = false" class="w-full max-w-md rounded-2xl bg-white p-6 text-center shadow-theme-xl dark:bg-gray-900">
            <div class="mx-auto flex h-14 w-14 items-center justify-center rounded-full bg-green-100 text-green-600 dark:bg-green-500/10 dark:text-green-400">
                <svg class="h-7 w-7" viewBox="0 0 24 24" fill="none">
                    <path d="M20 6L9 17L4 12" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                </svg>
            </div>
            <h3 class="mt-4 text-lg font-semibold text-gray-900 dark:text-white">Success</h3>
            <p class="mt-2 text-sm text-gray-500 dark:text-gray-400" x-text="successMessage"></p>
            <button type="button" @click="successModal = false" class="mt-5 rounded-lg bg-brand-500 px-5 py-2.5 text-sm font-semibold text-gray-900 hover:bg-brand-400">
                OK
            </button>
        </div>
    </div>

    <div x-show="confirmModal" x-transition.opacity class="fixed inset-0 z-[100020] flex items-center justify-center bg-gray-900/60 px-4">
        <div @click.outside="confirmModal = false" class="w-full max-w-md rounded-2xl bg-white p-6 shadow-theme-xl dark:bg-gray-900">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Delete assessment schedule?</h3>
            <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">
                This will permanently remove the selected schedule from the school calendar if it has no linked assessment records or evaluator assignments.
            </p>
            <div class="mt-6 flex justify-end gap-3">
                <button type="button" @click="confirmModal = false" class="rounded-lg border border-gray-300 px-4 py-2.5 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:text-gray-300 dark:hover:bg-white/5">Cancel</button>
                <button type="button" @click="deleteSelectedSchedule" :disabled="loading" class="rounded-lg bg-red-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-red-700 disabled:cursor-not-allowed disabled:opacity-60">
                    <span x-show="!loading">Delete</span>
                    <span x-show="loading">Deleting...</span>
                </button>
            </div>
        </div>
    </div>

    <div x-show="scheduleModal" x-transition.opacity class="fixed inset-0 z-[100000] flex items-center justify-center overflow-y-auto bg-gray-900/60 px-4 py-6">
        <div @click.outside="closeScheduleModal" class="w-full max-w-2xl rounded-3xl bg-white p-6 shadow-theme-xl dark:bg-gray-900">
            <div class="flex items-start justify-between gap-4">
                <div>
                    <p class="text-sm font-medium text-yellow-700 dark:text-yellow-400">Assessment Schedule</p>
                    <h3 class="mt-1 text-xl font-semibold text-gray-900 dark:text-white" x-text="selectedSchedule?.title || 'Schedule Details'"></h3>
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400" x-text="selectedSchedule ? formatLongDate(selectedSchedule.assessment_date) : ''"></p>
                    <p class="mt-1 text-sm font-medium text-gray-600 dark:text-gray-300">School Year: <span x-text="selectedSchedule?.year_label || selectedYearLabel"></span></p>
                </div>
                <button type="button" @click="closeScheduleModal" class="inline-flex h-10 w-10 items-center justify-center rounded-xl border border-gray-300 text-gray-500 hover:bg-gray-50 dark:border-gray-700 dark:text-gray-300 dark:hover:bg-white/5">
                    <span class="text-lg leading-none">&times;</span>
                </button>
            </div>

            <template x-if="selectedSchedule">
                <div class="mt-6 space-y-5">
                    <div class="rounded-2xl border border-gray-200 bg-gray-50 p-4 dark:border-white/[0.05] dark:bg-gray-900/60">
                        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                            <div>
                                <p class="text-sm font-semibold text-gray-900 dark:text-white" x-text="selectedSchedule.title"></p>
                                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400" x-text="formatLongDate(selectedSchedule.assessment_date)"></p>
                                <p class="mt-1 text-xs font-medium uppercase tracking-wide text-gray-500 dark:text-gray-400">School Year: <span x-text="selectedSchedule.year_label || selectedYearLabel"></span></p>
                            </div>
                            <span class="inline-flex w-fit rounded-full px-2.5 py-1 text-xs font-medium" :class="statusPillClass(selectedSchedule.status)" x-text="statusLabel(selectedSchedule.status)"></span>
                        </div>
                    </div>

                    <form @submit.prevent="updateSelectedSchedule" class="grid grid-cols-1 gap-4 md:grid-cols-2">
                        <div>
                            <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">Quarter</label>
                            <select x-model="editForm.quarter_id" @change="clampEditDate" class="h-11 w-full rounded-xl border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 shadow-theme-xs dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
                                <template x-for="quarter in quarters" :key="`modal-edit-${quarter.quarter_id}`">
                                    <option :value="quarter.quarter_id" x-text="quarter.quarter_name"></option>
                                </template>
                            </select>
                            <template x-if="formErrors.quarter_id"><p class="mt-1 text-sm text-red-600" x-text="formErrors.quarter_id[0]"></p></template>
                        </div>

                        <div>
                            <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">Assessment Date</label>
                            <input type="date" x-model="editForm.assessment_date" :min="editDateMin" :max="editDateMax" @change="clampEditDate" @click="$event.target.showPicker && $event.target.showPicker()" class="h-11 w-full cursor-pointer rounded-xl border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 shadow-theme-xs dark:border-gray-700 dark:bg-gray-900 dark:text-white/90" />
                            <p class="mt-1 text-xs text-gray-500 dark:text-gray-400" x-text="editDateRangeLabel"></p>
                            <template x-if="formErrors.assessment_date"><p class="mt-1 text-sm text-red-600" x-text="formErrors.assessment_date[0]"></p></template>
                        </div>

                        <div class="md:col-span-2">
                            <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">Status</label>
                            <select x-model="editForm.status" class="h-11 w-full rounded-xl border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 shadow-theme-xs dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
                                <option value="scheduled">Scheduled</option>
                                <option value="ongoing">Ongoing</option>
                                <option value="completed">Completed</option>
                                <option value="cancelled">Cancelled</option>
                            </select>
                        </div>

                        <div class="flex flex-col-reverse gap-3 border-t border-gray-200 pt-5 dark:border-gray-800 md:col-span-2 sm:flex-row sm:justify-end">
                            <button type="button" @click="confirmDeleteSelected" class="rounded-xl border border-red-200 px-4 py-2.5 text-sm font-semibold text-red-600 hover:bg-red-50 dark:border-red-500/20 dark:hover:bg-red-500/10">Delete</button>
                            <button type="button" @click="closeScheduleModal" class="rounded-xl border border-gray-300 px-4 py-2.5 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:text-gray-300 dark:hover:bg-white/5">Close</button>
                            <button type="submit" :disabled="loading" class="rounded-xl bg-brand-500 px-5 py-2.5 text-sm font-semibold text-gray-900 hover:bg-brand-400 disabled:cursor-not-allowed disabled:opacity-60">
                                <span x-show="!loading">Update Schedule</span>
                                <span x-show="loading">Updating...</span>
                            </button>
                        </div>
                    </form>
                </div>
            </template>
        </div>
    </div>

    <div class="overflow-hidden rounded-3xl border border-gray-200 bg-white shadow-sm dark:border-white/[0.05] dark:bg-white/[0.03]">
        <div class="flex flex-col gap-5 px-6 py-6 lg:flex-row lg:items-end lg:justify-between">
            <div>
                <div class="inline-flex items-center rounded-full bg-yellow-100 px-3 py-1 text-xs font-medium text-yellow-700 dark:bg-yellow-500/10 dark:text-yellow-400">
                    School Assessment Calendar
                </div>
                <h3 class="mt-3 text-xl font-semibold text-gray-800 dark:text-white/90">Assessment Schedule</h3>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                    Manage school assessment dates by school year and quarter. Click a calendar item to view, update, or delete it.
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

        <div class="grid grid-cols-1 gap-6 border-t border-gray-200 p-6 dark:border-white/[0.05] xl:grid-cols-[minmax(0,1fr)_360px]">
            <section class="min-w-0 rounded-3xl border border-gray-200 bg-white dark:border-white/[0.05] dark:bg-white/[0.03]">
                <div class="flex flex-col gap-4 border-b border-gray-200 p-5 dark:border-white/[0.05] sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <h4 class="text-lg font-semibold text-gray-800 dark:text-white" x-text="calendarTitle"></h4>
                        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400" x-text="selectedYearLabel"></p>
                    </div>

                    <div class="flex items-center gap-2">
                        <button type="button" @click="previousMonth" :disabled="!canGoPreviousMonth()" :class="!canGoPreviousMonth() ? 'cursor-not-allowed opacity-50' : ''" class="inline-flex h-10 w-10 items-center justify-center rounded-xl border border-gray-300 text-gray-600 hover:bg-gray-50 dark:border-gray-700 dark:text-gray-300 dark:hover:bg-white/5" title="Previous month">
                            <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none"><path d="M15 18L9 12L15 6" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" /></svg>
                        </button>
                        <button type="button" @click="goToday" class="rounded-xl border border-gray-300 px-4 py-2.5 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:text-gray-300 dark:hover:bg-white/5">Today</button>
                        <button type="button" @click="nextMonth" :disabled="!canGoNextMonth()" :class="!canGoNextMonth() ? 'cursor-not-allowed opacity-50' : ''" class="inline-flex h-10 w-10 items-center justify-center rounded-xl border border-gray-300 text-gray-600 hover:bg-gray-50 dark:border-gray-700 dark:text-gray-300 dark:hover:bg-white/5" title="Next month">
                            <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none"><path d="M9 18L15 12L9 6" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" /></svg>
                        </button>
                    </div>
                </div>

                <div class="grid grid-cols-7 border-b border-gray-200 bg-gray-50 text-center text-xs font-semibold uppercase tracking-wide text-gray-500 dark:border-white/[0.05] dark:bg-gray-900/70 dark:text-gray-400">
                    <div class="px-2 py-3">Sun</div>
                    <div class="px-2 py-3">Mon</div>
                    <div class="px-2 py-3">Tue</div>
                    <div class="px-2 py-3">Wed</div>
                    <div class="px-2 py-3">Thu</div>
                    <div class="px-2 py-3">Fri</div>
                    <div class="px-2 py-3">Sat</div>
                </div>

                <div class="grid grid-cols-7">
                    <template x-for="day in calendarDays" :key="day.date">
                        <div
                            @click="selectDateForCreate(day.date)"
                            class="min-h-[112px] cursor-pointer border-b border-r border-gray-100 p-2 transition hover:bg-yellow-50/70 dark:border-white/[0.05] dark:hover:bg-yellow-500/5"
                            :class="calendarDayClass(day)"
                        >
                            <div class="flex items-center justify-between">
                                <span class="inline-flex h-7 w-7 items-center justify-center rounded-full text-sm font-medium" :class="day.isToday ? 'bg-brand-500 text-gray-900' : 'text-gray-700 dark:text-gray-300'" x-text="day.dayNumber"></span>
                                <span x-show="day.schedules.length" class="rounded-full bg-gray-100 px-2 py-0.5 text-[11px] font-medium text-gray-600 dark:bg-white/10 dark:text-gray-300" x-text="day.schedules.length"></span>
                            </div>

                            <div class="mt-2 space-y-1">
                                <template x-for="schedule in day.schedules" :key="schedule.schedule_id">
                                    <button
                                        type="button"
                                        @click.stop="selectSchedule(schedule)"
                                        class="block w-full truncate rounded-lg px-2 py-1.5 text-left text-xs font-medium shadow-sm"
                                        :class="scheduleBadgeClass(schedule.status)"
                                        :title="`${schedule.quarter_name} - ${statusLabel(schedule.status)}`"
                                    >
                                        <span x-text="schedule.quarter_name"></span>
                                    </button>
                                </template>
                            </div>
                        </div>
                    </template>
                </div>
            </section>

            <aside class="space-y-5">
                <section class="rounded-3xl border border-gray-200 bg-white p-5 dark:border-white/[0.05] dark:bg-white/[0.03]">
                    <div class="flex items-start justify-between gap-3">
                        <div>
                            <h4 class="text-base font-semibold text-gray-800 dark:text-white">Add Schedule</h4>
                            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Create a school assessment date for the selected school year and quarter.</p>
                        </div>
                        <button type="button" @click="resetForm" class="text-sm font-medium text-brand-600 hover:text-brand-500 dark:text-brand-400">Clear</button>
                    </div>

                    <form @submit.prevent="createSchedule" class="mt-5 space-y-4">
                        <div class="rounded-2xl border border-yellow-200 bg-yellow-50 px-4 py-3 dark:border-yellow-500/20 dark:bg-yellow-500/10">
                            <p class="text-xs font-medium uppercase tracking-wide text-yellow-700 dark:text-yellow-400">Selected School Year</p>
                            <p class="mt-1 text-sm font-semibold text-gray-900 dark:text-white" x-text="selectedYearLabel"></p>
                            <p class="mt-1 text-xs text-yellow-700/80 dark:text-yellow-300/80">The quarter choices and allowed dates below are based on this selected school year.</p>
                        </div>

                        <div>
                            <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">Quarter</label>
                            <select x-model="form.quarter_id" @change="clampFormDate" class="h-11 w-full rounded-xl border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 shadow-theme-xs focus:border-brand-400 focus:outline-hidden focus:ring-3 focus:ring-brand-500/10 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
                                <option value="">Select quarter</option>
                                <template x-for="quarter in quarters" :key="quarter.quarter_id">
                                    <option :value="quarter.quarter_id" x-text="quarter.quarter_name"></option>
                                </template>
                            </select>
                            <template x-if="formErrors.quarter_id"><p class="mt-1 text-sm text-red-600" x-text="formErrors.quarter_id[0]"></p></template>
                        </div>

                        <div>
                            <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">Assessment Date</label>
                            <input type="date" x-model="form.assessment_date" :min="createDateMin" :max="createDateMax" @change="clampFormDate" @click="$event.target.showPicker && $event.target.showPicker()" class="h-11 w-full cursor-pointer rounded-xl border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 shadow-theme-xs focus:border-brand-400 focus:outline-hidden focus:ring-3 focus:ring-brand-500/10 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90" />
                            <p class="mt-1 text-xs text-gray-500 dark:text-gray-400" x-text="createDateRangeLabel"></p>
                            <template x-if="formErrors.assessment_date"><p class="mt-1 text-sm text-red-600" x-text="formErrors.assessment_date[0]"></p></template>
                        </div>

                        <div>
                            <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">Status</label>
                            <select x-model="form.status" class="h-11 w-full rounded-xl border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 shadow-theme-xs focus:border-brand-400 focus:outline-hidden focus:ring-3 focus:ring-brand-500/10 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
                                <option value="scheduled">Scheduled</option>
                                <option value="ongoing">Ongoing</option>
                                <option value="completed">Completed</option>
                                <option value="cancelled">Cancelled</option>
                            </select>
                        </div>

                        <button type="submit" :disabled="loading || !quarters.length || !form.assessment_date" class="w-full rounded-xl bg-brand-500 px-5 py-2.5 text-sm font-semibold text-gray-900 hover:bg-brand-400 disabled:cursor-not-allowed disabled:opacity-60">
                            <span x-show="!loading">Save Schedule</span>
                            <span x-show="loading">Saving...</span>
                        </button>
                    </form>

                    <div x-show="!quarters.length" class="mt-4 rounded-2xl border border-yellow-200 bg-yellow-50 p-4 text-sm text-yellow-700 dark:border-yellow-500/20 dark:bg-yellow-500/10 dark:text-yellow-300">
                        No quarters found for this school year. Create quarters first in School Year Management.
                    </div>
                </section>

                <section class="rounded-3xl border border-gray-200 bg-white p-5 dark:border-white/[0.05] dark:bg-white/[0.03]">
                    <h4 class="text-base font-semibold text-gray-800 dark:text-white">Legend</h4>
                    <div class="mt-4 grid grid-cols-2 gap-2 text-sm">
                        <div class="flex items-center gap-2"><span class="h-3 w-3 rounded-full bg-blue-500"></span><span class="text-gray-600 dark:text-gray-300">Scheduled</span></div>
                        <div class="flex items-center gap-2"><span class="h-3 w-3 rounded-full bg-yellow-500"></span><span class="text-gray-600 dark:text-gray-300">Ongoing</span></div>
                        <div class="flex items-center gap-2"><span class="h-3 w-3 rounded-full bg-green-500"></span><span class="text-gray-600 dark:text-gray-300">Completed</span></div>
                        <div class="flex items-center gap-2"><span class="h-3 w-3 rounded-full bg-red-500"></span><span class="text-gray-600 dark:text-gray-300">Cancelled</span></div>
                    </div>
                </section>
            </aside>
        </div>
    </div>
</div>

<script>
    function principalAssessmentSchedulePage(schoolYears, selectedYearId, quarters, schedules) {
        return {
            schoolYears: schoolYears || [],
            selectedYearId: selectedYearId,
            yearPickerValue: selectedYearId,
            quarters: quarters || [],
            schedules: schedules || [],
            monthCursor: null,
            selectedSchedule: null,
            form: {
                quarter_id: '',
                assessment_date: '',
                status: 'scheduled',
            },
            editForm: {
                quarter_id: '',
                assessment_date: '',
                status: 'scheduled',
            },
            formErrors: {},
            loading: false,
            loadingYear: false,
            successModal: false,
            successMessage: '',
            confirmModal: false,
            scheduleModal: false,
            scheduleBaseUrl: '',
            scheduleIndexUrl: '',
            scheduleStoreUrl: '',
            csrfToken: '', 

            init() {
                this.scheduleBaseUrl = this.$root.dataset.scheduleBaseUrl || '/principal/assessment-schedule';
                this.scheduleIndexUrl = this.$root.dataset.scheduleIndexUrl || this.scheduleBaseUrl;
                this.scheduleStoreUrl = this.$root.dataset.scheduleStoreUrl || this.scheduleBaseUrl;
                this.csrfToken = this.$root.dataset.csrfToken || '';

                if (!this.form.quarter_id && this.quarters.length) {
                    this.form.quarter_id = this.quarters[0].quarter_id;
                }

                this.form.assessment_date = '';
                this.setMonthCursor(this.defaultCalendarDate());
            },

            get selectedYearLabel() {
                const year = this.schoolYears.find(item => String(item.year_id) === String(this.selectedYearId));
                return year?.label || this.yearLabel(year);
            },
            get selectedYear() {
                return this.schoolYears.find(item => String(item.year_id) === String(this.selectedYearId)) || null;
            },

            get selectedYearStart() {
                return this.selectedYear?.start_date || '';
            },

            get selectedYearEnd() {
                return this.selectedYear?.end_date || '';
            },

            get selectedQuarterForForm() {
                return this.quarterById(this.form.quarter_id);
            },

            get selectedQuarterForEdit() {
                return this.quarterById(this.editForm.quarter_id);
            },

            get createDateMin() {
                return this.dateRangeStart(this.selectedQuarterForForm);
            },

            get createDateMax() {
                return this.dateRangeEnd(this.selectedQuarterForForm);
            },

            get editDateMin() {
                return this.dateRangeStart(this.selectedQuarterForEdit);
            },

            get editDateMax() {
                return this.dateRangeEnd(this.selectedQuarterForEdit);
            },

            get createDateRangeLabel() {
                return this.dateRangeLabel(this.createDateMin, this.createDateMax);
            },

            get editDateRangeLabel() {
                return this.dateRangeLabel(this.editDateMin, this.editDateMax);
            },


            get calendarTitle() {
                if (!this.monthCursor) return '';

                return this.monthCursor.toLocaleDateString('en-PH', {
                    month: 'long',
                    year: 'numeric',
                });
            },

            get calendarDays() {
                if (!this.monthCursor) return [];

                const year = this.monthCursor.getFullYear();
                const month = this.monthCursor.getMonth();
                const firstDay = new Date(year, month, 1);
                const start = new Date(year, month, 1 - firstDay.getDay());
                const today = this.todayString();
                const days = [];

                for (let index = 0; index < 42; index++) {
                    const date = new Date(start);
                    date.setDate(start.getDate() + index);
                    const dateString = this.toDateString(date);

                    days.push({
                        date: dateString,
                        dayNumber: date.getDate(),
                        isCurrentMonth: date.getMonth() === month,
                        isToday: dateString === today,
                        schedules: this.schedulesForDate(dateString),
                    });
                }

                return days;
            },

            yearLabel(year) {
                if (!year) return 'School Year';
                const start = year.start_date ? new Date(year.start_date).getFullYear() : '';
                const end = year.end_date ? new Date(year.end_date).getFullYear() : '';
                return start && end ? `${start} - ${end}` : 'School Year';
            },

            parseDate(value) {
                const [year, month, day] = String(value || this.todayString()).split('-').map(Number);
                return new Date(year, (month || 1) - 1, day || 1);
            },

            toDateString(date) {
                const year = date.getFullYear();
                const month = String(date.getMonth() + 1).padStart(2, '0');
                const day = String(date.getDate()).padStart(2, '0');
                return `${year}-${month}-${day}`;
            },

            todayString() {
                return this.toDateString(new Date());
            },

            quarterById(quarterId) {
                return this.quarters.find(quarter => String(quarter.quarter_id) === String(quarterId)) || null;
            },

            dateRangeStart(quarter) {
                return quarter?.start_date || this.selectedYearStart || '';
            },

            dateRangeEnd(quarter) {
                return quarter?.end_date || this.selectedYearEnd || '';
            },

            dateRangeLabel(min, max) {
                if (min && max) return `Allowed dates: ${this.formatShortDate(min)} to ${this.formatShortDate(max)}`;
                if (min) return `Allowed from: ${this.formatShortDate(min)}`;
                if (max) return `Allowed until: ${this.formatShortDate(max)}`;
                return 'Choose a date for the selected school year and quarter.';
            },

            firstValidDateForQuarter(quarter) {
                const min = this.dateRangeStart(quarter);
                const max = this.dateRangeEnd(quarter);
                const today = this.todayString();

                if (this.isDateBetween(today, min, max)) {
                    return today;
                }

                return min || max || today;
            },

            defaultCalendarDate() {
                const firstSchedule = this.schedules[0] || null;

                if (firstSchedule?.assessment_date && this.isDateInSelectedYear(firstSchedule.assessment_date)) {
                    return firstSchedule.assessment_date;
                }

                return this.firstValidDateForQuarter(this.quarters[0] || null);
            },

            setMonthCursor(dateString) {
                const parsed = this.parseDate(dateString || this.todayString());
                this.monthCursor = new Date(parsed.getFullYear(), parsed.getMonth(), 1);
            },

            clampDateValue(value, min, max) {
                if (!value) return '';

                let date = value;

                if (min && date < min) date = min;
                if (max && date > max) date = max;

                return date;
            },

            clampFormDate(moveCalendar = true) {
                this.form.assessment_date = this.clampDateValue(this.form.assessment_date, this.createDateMin, this.createDateMax);

                if (moveCalendar) {
                    this.setMonthCursor(this.form.assessment_date);
                }
            },

            clampEditDate(moveCalendar = true) {
                this.editForm.assessment_date = this.clampDateValue(this.editForm.assessment_date, this.editDateMin, this.editDateMax);

                if (moveCalendar) {
                    this.setMonthCursor(this.editForm.assessment_date);
                }
            },

            isDateBetween(date, min, max) {
                if (!date) return false;
                if (min && date < min) return false;
                if (max && date > max) return false;
                return true;
            },

            isDateInSelectedYear(dateString) {
                return this.isDateBetween(dateString, this.selectedYearStart, this.selectedYearEnd);
            },

            canGoPreviousMonth() {
                if (!this.monthCursor || !this.selectedYearStart) return true;

                const previous = new Date(this.monthCursor.getFullYear(), this.monthCursor.getMonth() - 1, 1);
                const start = this.parseDate(this.selectedYearStart);
                const firstAllowed = new Date(start.getFullYear(), start.getMonth(), 1);

                return previous >= firstAllowed;
            },

            canGoNextMonth() {
                if (!this.monthCursor || !this.selectedYearEnd) return true;

                const next = new Date(this.monthCursor.getFullYear(), this.monthCursor.getMonth() + 1, 1);
                const end = this.parseDate(this.selectedYearEnd);
                const lastAllowed = new Date(end.getFullYear(), end.getMonth(), 1);

                return next <= lastAllowed;
            },

            calendarDayClass(day) {
                const base = day.isCurrentMonth
                    ? 'bg-white dark:bg-white/[0.02]'
                    : 'bg-gray-50 text-gray-400 dark:bg-gray-900/40 dark:text-gray-600';

                if (!this.isDateInSelectedYear(day.date)) {
                    return `${base} cursor-not-allowed opacity-40`;
                }

                return base;
            },

            schedulesForDate(dateString) {
                return this.schedules.filter(schedule => schedule.assessment_date === dateString);
            },

            selectDateForCreate(dateString) {
                if (!this.isDateInSelectedYear(dateString)) return;

                const matchingQuarter = this.quarters.find(quarter => {
                    return this.isDateBetween(dateString, this.dateRangeStart(quarter), this.dateRangeEnd(quarter));
                });

                if (matchingQuarter) {
                    this.form.quarter_id = matchingQuarter.quarter_id;
                }

                this.form.assessment_date = dateString;
                this.clampFormDate(false);
                this.formErrors = {};
            },

            selectSchedule(schedule) {
                this.selectedSchedule = schedule;
                this.editForm = {
                    quarter_id: schedule.quarter_id,
                    assessment_date: schedule.assessment_date,
                    status: schedule.status,
                };
                this.clampEditDate(false);
                this.formErrors = {};
                this.scheduleModal = true;
            },

            closeScheduleModal() {
                this.scheduleModal = false;
                this.formErrors = {};
            },

            resetForm() {
                this.form = {
                    quarter_id: this.quarters[0]?.quarter_id || '',
                    assessment_date: '',
                    status: 'scheduled',
                };
                this.formErrors = {};
            },

            previousMonth() {
                if (!this.canGoPreviousMonth()) return;
                this.monthCursor = new Date(this.monthCursor.getFullYear(), this.monthCursor.getMonth() - 1, 1);
            },

            nextMonth() {
                if (!this.canGoNextMonth()) return;
                this.monthCursor = new Date(this.monthCursor.getFullYear(), this.monthCursor.getMonth() + 1, 1);
            },

            goToday() {
                const today = this.todayString();
                const target = this.isDateInSelectedYear(today) ? today : this.defaultCalendarDate();
                this.setMonthCursor(target);
                this.form.assessment_date = this.clampDateValue(target, this.createDateMin, this.createDateMax);
            },

            async changeYear(yearId) {
                if (!yearId || this.loadingYear) return;

                this.loadingYear = true;

                try {
                    const url = `${this.scheduleIndexUrl}?ajax=1&year_id=${encodeURIComponent(yearId)}&_t=${Date.now()}`;
                    const response = await fetch(url, {
                        headers: {
                            'Accept': 'application/json',
                            'X-Requested-With': 'XMLHttpRequest',
                        },
                        cache: 'no-store',
                    });

                    const data = await response.json();

                    if (!response.ok || !data.success) {
                        alert(data.message || 'Failed to load assessment schedules.');
                        return;
                    }

                    this.selectedYearId = data.selectedYearId;
                    this.yearPickerValue = data.selectedYearId;
                    this.quarters = data.quarters || [];
                    this.schedules = data.schedules || [];
                    this.selectedSchedule = null;
                    this.resetForm();

                    this.setMonthCursor(this.defaultCalendarDate());
                } catch (error) {
                    console.error('Change assessment schedule year error:', error);
                    alert('An error occurred while loading assessment schedules.');
                } finally {
                    this.loadingYear = false;
                }
            },

            async createSchedule() {
                this.formErrors = {};
                this.clampFormDate(false);
                this.loading = true;

                try {
                    const response = await fetch(this.scheduleStoreUrl, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': this.csrfToken,
                            'Accept': 'application/json',
                        },
                        body: JSON.stringify({
                            year_id: this.selectedYearId,
                            quarter_id: this.form.quarter_id,
                            assessment_date: this.form.assessment_date,
                            status: this.form.status,
                        }),
                    });

                    const data = await response.json();

                    if (!response.ok) {
                        this.formErrors = data.errors || {};
                        alert(data.message || 'Failed to create assessment schedule.');
                        return;
                    }

                    this.upsertSchedule(data.schedule);

                    this.selectedSchedule = null;
                    this.scheduleModal = false;
                    this.confirmModal = false;

                    const parsed = this.parseDate(data.schedule.assessment_date);
                    this.monthCursor = new Date(parsed.getFullYear(), parsed.getMonth(), 1);

                    this.resetForm();
                    this.successMessage = data.message || 'Assessment schedule created successfully.';
                    this.successModal = true;
                } catch (error) {
                    console.error('Create assessment schedule error:', error);
                    alert('An error occurred while creating the assessment schedule.');
                } finally {
                    this.loading = false;
                }
            },

            async updateSelectedSchedule() {
                if (!this.selectedSchedule) return;

                this.formErrors = {};
                this.clampEditDate(false);
                this.loading = true;

                try {
                    const response = await fetch(`${this.scheduleBaseUrl}/${this.selectedSchedule.schedule_id}`, {
                        method: 'PATCH',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': this.csrfToken,
                            'Accept': 'application/json',
                        },
                        body: JSON.stringify({
                            quarter_id: this.editForm.quarter_id,
                            assessment_date: this.editForm.assessment_date,
                            status: this.editForm.status,
                        }),
                    });

                    const data = await response.json();

                    if (!response.ok) {
                        this.formErrors = data.errors || {};
                        alert(data.message || 'Failed to update assessment schedule.');
                        return;
                    }

                    this.upsertSchedule(data.schedule);

                    this.selectedSchedule = data.schedule;
                    this.editForm = {
                        quarter_id: data.schedule.quarter_id,
                        assessment_date: data.schedule.assessment_date,
                        status: data.schedule.status,
                    };
                    this.scheduleModal = false;
                    this.confirmModal = false;

                    const parsed = this.parseDate(data.schedule.assessment_date);
                    this.monthCursor = new Date(parsed.getFullYear(), parsed.getMonth(), 1);

                    this.successMessage = data.message || 'Assessment schedule updated successfully.';
                    this.successModal = true;
                } catch (error) {
                    console.error('Update assessment schedule error:', error);
                    alert('An error occurred while updating the assessment schedule.');
                } finally {
                    this.loading = false;
                }
            },

            confirmDeleteSelected() {
                if (!this.selectedSchedule) return;
                this.confirmModal = true;
            },

            async deleteSelectedSchedule() {
                if (!this.selectedSchedule) return;

                this.loading = true;

                try {
                    const scheduleId = this.selectedSchedule.schedule_id;
                    const response = await fetch(`${this.scheduleBaseUrl}/${scheduleId}`, {
                        method: 'DELETE',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': this.csrfToken,
                            'Accept': 'application/json',
                        },
                    });

                    const data = await response.json();

                    if (!response.ok) {
                        alert(data.message || 'Failed to delete assessment schedule.');
                        return;
                    }

                    this.schedules = this.schedules.filter(schedule => String(schedule.schedule_id) !== String(scheduleId));
                    this.selectedSchedule = null;
                    this.confirmModal = false;
                    this.scheduleModal = false;
                    this.successMessage = data.message || 'Assessment schedule deleted successfully.';
                    this.successModal = true;
                } catch (error) {
                    console.error('Delete assessment schedule error:', error);
                    alert('An error occurred while deleting the assessment schedule.');
                } finally {
                    this.loading = false;
                }
            },

            upsertSchedule(schedule) {
                if (!schedule) return;

                const index = this.schedules.findIndex(item => String(item.schedule_id) === String(schedule.schedule_id));

                if (index >= 0) {
                    this.schedules.splice(index, 1, schedule);
                } else {
                    this.schedules.push(schedule);
                }

                this.schedules.sort((a, b) => String(a.assessment_date).localeCompare(String(b.assessment_date)));
            },

            formatLongDate(value) {
                if (!value) return 'No date set';

                return this.parseDate(value).toLocaleDateString('en-PH', {
                    year: 'numeric',
                    month: 'long',
                    day: 'numeric',
                    weekday: 'long',
                });
            },

            formatShortDate(value) {
                if (!value) return 'Not set';

                return this.parseDate(value).toLocaleDateString('en-PH', {
                    year: 'numeric',
                    month: 'short',
                    day: 'numeric',
                });
            },

            statusLabel(status) {
                if (status === 'scheduled') return 'Scheduled';
                if (status === 'ongoing') return 'Ongoing';
                if (status === 'completed') return 'Completed';
                if (status === 'cancelled') return 'Cancelled';
                return status || 'Unknown';
            },

            scheduleBadgeClass(status) {
                if (status === 'scheduled') return 'bg-blue-50 text-blue-700 ring-1 ring-blue-200 dark:bg-blue-500/10 dark:text-blue-300 dark:ring-blue-500/20';
                if (status === 'ongoing') return 'bg-yellow-50 text-yellow-700 ring-1 ring-yellow-200 dark:bg-yellow-500/10 dark:text-yellow-300 dark:ring-yellow-500/20';
                if (status === 'completed') return 'bg-green-50 text-green-700 ring-1 ring-green-200 dark:bg-green-500/10 dark:text-green-300 dark:ring-green-500/20';
                if (status === 'cancelled') return 'bg-red-50 text-red-700 ring-1 ring-red-200 dark:bg-red-500/10 dark:text-red-300 dark:ring-red-500/20';
                return 'bg-gray-50 text-gray-700 ring-1 ring-gray-200 dark:bg-white/10 dark:text-gray-300 dark:ring-white/10';
            },

            statusPillClass(status) {
                if (status === 'scheduled') return 'bg-blue-100 text-blue-700 dark:bg-blue-500/10 dark:text-blue-300';
                if (status === 'ongoing') return 'bg-yellow-100 text-yellow-700 dark:bg-yellow-500/10 dark:text-yellow-300';
                if (status === 'completed') return 'bg-green-100 text-green-700 dark:bg-green-500/10 dark:text-green-300';
                if (status === 'cancelled') return 'bg-red-100 text-red-700 dark:bg-red-500/10 dark:text-red-300';
                return 'bg-gray-100 text-gray-700 dark:bg-white/10 dark:text-gray-300';
            },
        };
    }
</script>

<style>
    [x-cloak] { display: none !important; }
</style>
