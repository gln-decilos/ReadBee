@props([
    'schoolYears' => [],
    'selectedYearId' => null,
    'summary' => [],
    'assignments' => [],
])

<script>
    window.evaluatorProgressMonitoringPage = function(schoolYears, selectedYearId, summary, assignments) {
        return {
            schoolYears: schoolYears || [],
            selectedYearId: selectedYearId || '',
            yearPickerValue: selectedYearId || '',
            summary: summary || {},
            assignments: assignments || [],
            search: '',
            statusFilter: 'all',
            selectedAssignment: null,
            pupilSearch: '',
            selectedPupil: null,
            recordModalOpen: false,
            tablePage: 1,
            perPage: 10,
            loadingYear: false,
            refreshingProgress: false,
            refreshTimer: null,
            autoRefreshInterval: 2000,
            lastUpdatedAt: null,
            indexUrl: '',

            init(root) {
                this.indexUrl = root.dataset.indexUrl;

                if (!this.yearPickerValue && this.schoolYears.length > 0) {
                    this.yearPickerValue = this.schoolYears[0].year_id;
                    this.selectedYearId = this.yearPickerValue;
                }

                this.lastUpdatedAt = new Date();
                this.startAutoRefresh();
            },

            destroy() {
                this.stopAutoRefresh();
                document.body.style.overflow = '';
            },

            startAutoRefresh() {
                this.stopAutoRefresh();

                this.refreshTimer = window.setInterval(() => {
                    if (document.hidden) return;
                    this.refreshProgressData();
                }, this.autoRefreshInterval);
            },

            stopAutoRefresh() {
                if (!this.refreshTimer) return;

                window.clearInterval(this.refreshTimer);
                this.refreshTimer = null;
            },

            async changeYear() {
                if (!this.yearPickerValue || this.yearPickerValue === this.selectedYearId) return;

                this.loadingYear = true;
                const url = new URL(this.indexUrl, window.location.origin);
                url.searchParams.set('year_id', this.yearPickerValue);
                url.searchParams.set('ajax', '1');
                url.searchParams.set('_', Date.now());

                try {
                    const response = await fetch(url.toString(), {
                        headers: { 'Accept': 'application/json' }
                    });
                    const data = await response.json();

                    if (!response.ok || !data.success) {
                        alert(data.message || 'Unable to load progress data for the selected school year.');
                        return;
                    }

                    this.selectedYearId = data.selectedYearId;
                    this.yearPickerValue = data.selectedYearId;
                    this.schoolYears = data.schoolYears || this.schoolYears;
                    this.summary = data.summary || {};
                    this.assignments = data.assignments || [];
                    this.search = '';
                    this.statusFilter = 'all';
                    this.lastUpdatedAt = new Date();
                    this.closeDetail();
                    window.history.replaceState({}, '', `${this.indexUrl}?year_id=${this.selectedYearId}`);
                } catch (error) {
                    console.error('Progress monitoring year load error:', error);
                    alert('Unable to load progress data. Please try again.');
                } finally {
                    this.loadingYear = false;
                }
            },

            async refreshProgressData() {
                if (!this.indexUrl || !this.selectedYearId || this.loadingYear || this.refreshingProgress) return;

                this.refreshingProgress = true;
                const currentAssignmentId = this.selectedAssignment?.assignment_id || null;
                const currentPupilId = this.selectedPupil?.pupil_id || null;
                const url = new URL(this.indexUrl, window.location.origin);
                url.searchParams.set('year_id', this.selectedYearId);
                url.searchParams.set('ajax', '1');
                url.searchParams.set('_', Date.now());

                try {
                    const response = await fetch(url.toString(), {
                        headers: { 'Accept': 'application/json' }
                    });
                    const data = await response.json();

                    if (!response.ok || !data.success) return;

                    this.selectedYearId = data.selectedYearId || this.selectedYearId;
                    this.yearPickerValue = this.selectedYearId;
                    this.schoolYears = data.schoolYears || this.schoolYears;
                    this.summary = data.summary || {};
                    this.assignments = data.assignments || [];
                    this.lastUpdatedAt = new Date();

                    if (currentAssignmentId) {
                        const updatedAssignment = this.assignments.find((assignment) => assignment.assignment_id === currentAssignmentId);

                        if (!updatedAssignment) {
                            this.closeDetail();
                            return;
                        }

                        this.selectedAssignment = updatedAssignment;
                    }

                    if (currentPupilId && this.selectedAssignment) {
                        const updatedPupil = (this.selectedAssignment.pupils || []).find((pupil) => pupil.pupil_id === currentPupilId);

                        if (updatedPupil) {
                            this.selectedPupil = updatedPupil;
                        } else {
                            this.closePupilRecords();
                            this.selectedPupil = null;
                        }
                    }

                    if (this.tablePage > this.totalPages) {
                        this.tablePage = this.totalPages;
                    }
                } catch (error) {
                    console.error('Progress monitoring auto-refresh error:', error);
                } finally {
                    this.refreshingProgress = false;
                }
            },

            get filteredAssignments() {
                const term = this.search.trim().toLowerCase();

                return this.assignments.filter((assignment) => {
                    const matchesSearch = !term || [
                        assignment.grade_label,
                        assignment.section_name,
                        assignment.quarter_label,
                        assignment.school_year_label,
                        assignment.school_name,
                        assignment.adviser_name,
                    ].join(' ').toLowerCase().includes(term);

                    const matchesStatus = this.statusFilter === 'all'
                        || (this.statusFilter === 'complete' && Number(assignment.overall_percent || 0) >= 100)
                        || (this.statusFilter === 'incomplete' && Number(assignment.overall_percent || 0) < 100);

                    return matchesSearch && matchesStatus;
                });
            },

            get filteredPupils() {
                if (!this.selectedAssignment) return [];

                const term = this.pupilSearch.trim().toLowerCase();
                return (this.selectedAssignment.pupils || []).filter((pupil) => {
                    if (!term) return true;

                    return [
                        pupil.full_name,
                        pupil.lrn,
                        pupil.sex,
                        pupil.english?.status,
                        pupil.filipino?.status,
                        pupil.english?.record?.reading_level,
                        pupil.filipino?.record?.reading_level,
                        pupil.english?.record?.comprehension_level,
                        pupil.filipino?.record?.comprehension_level,
                    ].join(' ').toLowerCase().includes(term);
                });
            },

            get paginatedPupils() {
                const start = (this.tablePage - 1) * this.perPage;
                return this.filteredPupils.slice(start, start + this.perPage);
            },

            get totalPages() {
                return Math.max(Math.ceil(this.filteredPupils.length / this.perPage), 1);
            },

            get tableStartItem() {
                if (this.filteredPupils.length === 0) return 0;
                return ((this.tablePage - 1) * this.perPage) + 1;
            },

            get tableEndItem() {
                return Math.min(this.tablePage * this.perPage, this.filteredPupils.length);
            },

            get lastUpdatedText() {
                if (!this.lastUpdatedAt) return 'Progress updates automatically';

                return `Last updated ${this.lastUpdatedAt.toLocaleTimeString('en-PH', {
                    hour: 'numeric',
                    minute: '2-digit',
                    second: '2-digit',
                })}`;
            },

            selectAssignment(assignment) {
                this.selectedAssignment = assignment;
                this.pupilSearch = '';
                this.tablePage = 1;
                this.selectedPupil = null;
                this.recordModalOpen = false;
                this.$nextTick(() => {
                    this.$refs.detailPanel?.scrollIntoView({ behavior: 'smooth', block: 'start' });
                });
            },

            closeDetail() {
                this.selectedAssignment = null;
                this.pupilSearch = '';
                this.tablePage = 1;
                this.selectedPupil = null;
                this.recordModalOpen = false;
                document.body.style.overflow = '';
            },

            openPupilRecords(pupil) {
                if (!pupil?.has_any_record) return;
                this.selectedPupil = pupil;
                this.recordModalOpen = true;
                document.body.style.overflow = 'hidden';
            },

            closePupilRecords() {
                this.recordModalOpen = false;
                document.body.style.overflow = '';
            },

            nextPage() {
                if (this.tablePage < this.totalPages) this.tablePage++;
            },

            previousPage() {
                if (this.tablePage > 1) this.tablePage--;
            },

            percentage(value) {
                const number = Number(value || 0);
                return `${Number.isInteger(number) ? number : number.toFixed(1)}%`;
            },

            statusBadgeClass(done) {
                return done
                    ? 'bg-green-100 text-green-700 dark:bg-green-500/10 dark:text-green-400'
                    : 'bg-gray-100 text-gray-700 dark:bg-white/10 dark:text-gray-300';
            },

            completionBadgeClass(value) {
                const percent = Number(value || 0);
                if (percent >= 100) return 'bg-green-100 text-green-700 dark:bg-green-500/10 dark:text-green-400';
                if (percent > 0) return 'bg-brand-100 text-brand-700 dark:bg-brand-500/10 dark:text-brand-400';
                return 'bg-gray-100 text-gray-700 dark:bg-white/10 dark:text-gray-300';
            },

            formatDate(value) {
                if (!value) return 'Not set';

                return new Date(value).toLocaleDateString('en-PH', {
                    year: 'numeric',
                    month: 'short',
                    day: 'numeric',
                });
            },

            recordDate(record) {
                if (!record) return 'No record';
                return this.formatDate(record.updated_at || record.created_at);
            },

            recordValue(value, fallback = 'Not recorded') {
                if (value === null || value === undefined || value === '') return fallback;
                return value;
            },

            levelText(record, type) {
                if (!record) return 'Not assessed';
                if (type === 'reading') return this.recordValue(record.reading_level, 'No reading level');
                return this.recordValue(record.comprehension_level, 'No comprehension level');
            },

            rateText(record) {
                if (!record || record.comprehension_rate === null || record.comprehension_rate === undefined) return 'No rate';
                return `${record.comprehension_rate}%`;
            },

            wpmText(record) {
                if (!record || record.word_per_minute === null || record.word_per_minute === undefined) return 'No WPM';
                return `${record.word_per_minute} wpm`;
            },

            languageLabel(language) {
                return language === 'filipino' ? 'Filipino' : 'English';
            },

            recordHighlightMetrics(record) {
                if (!record) return [];

                return [
                    { label: 'Reading Level', value: this.levelText(record, 'reading') },
                    { label: 'Comprehension', value: this.levelText(record, 'comprehension') },
                    { label: 'Reading Speed', value: this.recordValue(record.reading_speed, 'No speed') },
                    { label: 'Words / Minute', value: this.wpmText(record) },
                ];
            },

            recordMetrics(record) {
                if (!record) return [];

                return [
                    { label: 'Reading Material', value: this.recordValue(record.material_title) },
                    { label: 'Assessment Type', value: this.recordValue(record.assessment_type, 'Not set') },
                    { label: 'Assessment Method', value: this.recordValue(record.assessment_method, 'Not set') },
                    { label: 'Reading Level', value: this.levelText(record, 'reading') },
                    { label: 'Reading Speed', value: this.recordValue(record.reading_speed, 'No speed') },
                    { label: 'Word Per Minute', value: this.wpmText(record) },
                    { label: 'Total Score', value: this.recordValue(record.total_score, 'No total score') },
                    { label: 'Correct Words', value: this.recordValue(record.correct_words, 'No data') },
                    { label: 'Total Miscues', value: this.recordValue(record.total_miscues, 'No data') },
                    { label: 'Comprehension Level', value: this.levelText(record, 'comprehension') },
                    { label: 'Comprehension Rate', value: this.rateText(record) },
                    { label: 'Record Status', value: `${this.recordValue(record.status_label, 'Recorded')} | Updated ${this.recordDate(record)}` },
                ];
            },
        };
    };
</script>

<style>
    [x-cloak] { display: none !important; }

    .readbee-progress-bar {
        background: linear-gradient(90deg, #ffca03 0%, #f59e0b 100%);
    }

    .dark .readbee-progress-bar {
        background: linear-gradient(90deg, #facc15 0%, #f59e0b 100%);
    }

    .readbee-modal-scrollbar {
        scrollbar-width: thin;
        scrollbar-color: rgba(148, 163, 184, 0.65) transparent;
    }

    .readbee-modal-scrollbar::-webkit-scrollbar {
        width: 8px;
    }

    .readbee-modal-scrollbar::-webkit-scrollbar-thumb {
        background: rgba(148, 163, 184, 0.65);
        border-radius: 999px;
    }
</style>

<div
    x-data="evaluatorProgressMonitoringPage(@js($schoolYears), @js($selectedYearId), @js($summary), @js($assignments))"
    x-init="init($el)"
    x-cloak
    data-index-url="{{ route('evaluator.progress-monitoring') }}"
    class="space-y-6"
>
    <section class="rounded-2xl border border-gray-200 bg-white p-5 shadow-theme-xs dark:border-gray-800 dark:bg-white/[0.03] sm:p-6">
        <div class="flex flex-col gap-5 lg:flex-row lg:items-center lg:justify-between">
            <div class="min-w-0">
                <div class="flex items-center gap-3">
                    <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl bg-brand-50 text-brand-600 dark:bg-brand-500/10 dark:text-brand-400">
                        <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none"><path d="M4.75 19.25H19.25" stroke="currentColor" stroke-width="1.7" stroke-linecap="round"/><path d="M7.25 16.25V11.75" stroke="currentColor" stroke-width="1.7" stroke-linecap="round"/><path d="M12 16.25V7.75" stroke="currentColor" stroke-width="1.7" stroke-linecap="round"/><path d="M16.75 16.25V9.75" stroke="currentColor" stroke-width="1.7" stroke-linecap="round"/></svg>
                    </div>
                    <div class="min-w-0">
                        <h2 class="text-xl font-semibold text-gray-900 dark:text-white">Evaluator Progress Monitoring</h2>
                        <p class="mt-1 text-sm leading-6 text-gray-500 dark:text-gray-400">Monitor assigned sections and review English and Filipino assessment records per pupil.</p>
                    </div>
                </div>
            </div>

            <div class="flex w-full flex-col gap-2 sm:w-auto sm:min-w-[260px]">
                <label class="text-sm font-medium text-gray-700 dark:text-gray-300">School Year</label>
                <select x-model="yearPickerValue" @change="changeYear" :disabled="loadingYear" class="h-11 rounded-xl border border-gray-300 bg-white px-4 text-sm text-gray-700 shadow-theme-xs focus:border-brand-400 focus:outline-hidden focus:ring-3 focus:ring-brand-500/10 disabled:cursor-not-allowed disabled:opacity-60 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-200">
                    <template x-for="year in schoolYears" :key="year.year_id">
                        <option :value="year.year_id" x-text="year.label"></option>
                    </template>
                </select>
            </div>
        </div>
    </section>

    <section x-show="!selectedAssignment" x-transition class="rounded-2xl border border-gray-200 bg-white shadow-theme-xs dark:border-gray-800 dark:bg-white/[0.03]">
        <div class="flex flex-col gap-4 border-b border-gray-200 p-5 dark:border-white/[0.05] lg:flex-row lg:items-center lg:justify-between">
            <div>
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Assigned Grade and Section Progress</h3>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Each pupil needs one English record and one Filipino record.</p>
            </div>
            <div class="grid w-full grid-cols-1 gap-3 sm:grid-cols-[1fr_180px] lg:max-w-xl">
                <input type="search" x-model="search" placeholder="Search grade, section, quarter..." class="h-11 rounded-xl border border-gray-300 bg-white px-4 text-sm text-gray-700 shadow-theme-xs focus:border-brand-400 focus:outline-hidden focus:ring-3 focus:ring-brand-500/10 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-200" />
                <select x-model="statusFilter" class="h-11 rounded-xl border border-gray-300 bg-white px-4 text-sm text-gray-700 shadow-theme-xs focus:border-brand-400 focus:outline-hidden focus:ring-3 focus:ring-brand-500/10 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-200">
                    <option value="all">All Progress</option>
                    <option value="complete">Complete</option>
                    <option value="incomplete">Incomplete</option>
                </select>
            </div>
        </div>

        <div class="p-5">
            <div class="grid grid-cols-1 gap-4 xl:grid-cols-2">
                <template x-for="assignment in filteredAssignments" :key="assignment.assignment_id">
                    <article class="rounded-2xl border border-gray-200 bg-white p-5 shadow-theme-xs transition hover:border-brand-200 hover:shadow-theme-sm dark:border-gray-800 dark:bg-white/[0.03] dark:hover:border-brand-500/30">
                        <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                            <div class="min-w-0">
                                <div class="flex flex-wrap items-center gap-2">
                                    <span class="rounded-full bg-gray-100 px-2.5 py-1 text-xs font-medium text-gray-700 dark:bg-white/10 dark:text-gray-300" x-text="assignment.quarter_label"></span>
                                    <span class="rounded-full px-2.5 py-1 text-xs font-medium" :class="completionBadgeClass(assignment.overall_percent)" x-text="percentage(assignment.overall_percent)"></span>
                                </div>
                                <h4 class="mt-3 text-lg font-semibold text-gray-900 dark:text-white">
                                    <span x-text="assignment.grade_label"></span>
                                    <span> - </span>
                                    <span x-text="assignment.section_name"></span>
                                </h4>
                                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400" x-text="assignment.adviser_name ? `Adviser: ${assignment.adviser_name}` : 'No adviser set'"></p>
                            </div>
                            <button type="button" @click="selectAssignment(assignment)" class="inline-flex h-10 shrink-0 items-center justify-center rounded-lg bg-brand-500 px-4 text-sm font-semibold text-white hover:bg-brand-600">
                                View Pupils
                            </button>
                        </div>

                        <div class="mt-5 space-y-4">
                            <div>
                                <div class="mb-2 flex items-center justify-between gap-3 text-sm">
                                    <span class="font-medium text-gray-700 dark:text-gray-300">Overall completion</span>
                                    <span class="text-right text-gray-500 dark:text-gray-400" x-text="`${assignment.total_completed}/${assignment.total_required} required assessments`"></span>
                                </div>
                                <div class="h-2.5 overflow-hidden rounded-full bg-gray-100 dark:bg-gray-800">
                                    <div class="readbee-progress-bar h-full rounded-full" :style="`width: ${Math.min(assignment.overall_percent || 0, 100)}%`"></div>
                                </div>
                            </div>

                            <div class="grid grid-cols-2 gap-3">
                                <div class="rounded-xl bg-gray-50 p-3 dark:bg-white/[0.04]">
                                    <p class="text-xs font-medium text-gray-500 dark:text-gray-400">English</p>
                                    <p class="mt-1 text-lg font-semibold text-gray-900 dark:text-white" x-text="`${assignment.english_assessed}/${assignment.total_pupils}`"></p>
                                    <p class="text-xs text-gray-500 dark:text-gray-400" x-text="percentage(assignment.english_percent)"></p>
                                </div>
                                <div class="rounded-xl bg-gray-50 p-3 dark:bg-white/[0.04]">
                                    <p class="text-xs font-medium text-gray-500 dark:text-gray-400">Filipino</p>
                                    <p class="mt-1 text-lg font-semibold text-gray-900 dark:text-white" x-text="`${assignment.filipino_assessed}/${assignment.total_pupils}`"></p>
                                    <p class="text-xs text-gray-500 dark:text-gray-400" x-text="percentage(assignment.filipino_percent)"></p>
                                </div>
                            </div>
                        </div>
                    </article>
                </template>
            </div>

            <div x-show="filteredAssignments.length === 0" class="rounded-2xl border border-dashed border-gray-300 p-10 text-center dark:border-gray-700">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white">No progress records found</h3>
                <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">No confirmed assignment matched the selected school year or filters.</p>
            </div>
        </div>
    </section>

    <section x-show="selectedAssignment" x-ref="detailPanel" x-transition class="rounded-2xl border border-gray-200 bg-white shadow-theme-xs dark:border-gray-800 dark:bg-white/[0.03]">
        <div class="flex flex-col gap-4 border-b border-gray-200 p-5 dark:border-gray-800 lg:flex-row lg:items-start lg:justify-between">
            <div class="min-w-0">
                <p class="text-xs font-semibold uppercase tracking-[0.2em] text-brand-600 dark:text-brand-400">Pupil Assessment Status</p>
                <h3 class="mt-2 text-xl font-semibold text-gray-900 dark:text-white">
                    <span x-text="selectedAssignment?.grade_label"></span>
                    <span> - </span>
                    <span x-text="selectedAssignment?.section_name"></span>
                </h3>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                    <span x-text="selectedAssignment?.quarter_label"></span>
                    <span> | </span>
                    <span x-text="selectedAssignment?.school_year_label"></span>
                    <span> | Assessment Date: </span>
                    <span x-text="formatDate(selectedAssignment?.assessment_date)"></span>
                </p>
            </div>
            <button type="button" @click="closeDetail" class="inline-flex h-10 items-center justify-center rounded-lg border border-gray-300 px-4 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:text-gray-300 dark:hover:bg-white/5">
                Close Details
            </button>
        </div>

        <div class="border-b border-gray-200 p-5 dark:border-gray-800">
            <div class="space-y-3">
                <div class="flex flex-col gap-2 text-sm sm:flex-row sm:items-center sm:justify-between">
                    <div class="flex flex-wrap gap-x-4 gap-y-1 text-gray-600 dark:text-gray-300">
                        <span>Total pupils: <strong class="font-semibold text-gray-900 dark:text-white" x-text="selectedAssignment?.total_pupils || 0"></strong></span>
                        <span>English: <strong class="font-semibold text-gray-900 dark:text-white" x-text="`${selectedAssignment?.english_assessed || 0}/${selectedAssignment?.total_pupils || 0}`"></strong></span>
                        <span>Filipino: <strong class="font-semibold text-gray-900 dark:text-white" x-text="`${selectedAssignment?.filipino_assessed || 0}/${selectedAssignment?.total_pupils || 0}`"></strong></span>
                    </div>
                    <span class="w-fit rounded-full px-3 py-1 text-xs font-medium" :class="completionBadgeClass(selectedAssignment?.overall_percent)" x-text="`Overall ${percentage(selectedAssignment?.overall_percent)}`"></span>
                </div>
                <div class="h-2 overflow-hidden rounded-full bg-gray-100 dark:bg-gray-800">
                    <div class="readbee-progress-bar h-full rounded-full" :style="`width: ${Math.min(selectedAssignment?.overall_percent || 0, 100)}%`"></div>
                </div>
            </div>
        </div>

        <div class="border-b border-gray-200 p-5 dark:border-gray-800">
            <input type="search" x-model="pupilSearch" @input="tablePage = 1" placeholder="Search pupil name, LRN, status, or level..." class="h-11 w-full rounded-xl border border-gray-300 bg-white px-4 text-sm text-gray-700 shadow-theme-xs focus:border-brand-400 focus:outline-hidden focus:ring-3 focus:ring-brand-500/10 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-200" />
        </div>

        <div class="overflow-x-auto">
            <table class="w-full min-w-[920px] divide-y divide-gray-200 text-left dark:divide-gray-800">
                <thead class="bg-gray-50 dark:bg-white/[0.03]">
                    <tr>
                        <th class="px-5 py-3 text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Pupil</th>
                        <th class="px-5 py-3 text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">English</th>
                        <th class="px-5 py-3 text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Filipino</th>
                        <th class="px-5 py-3 text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Completion</th>
                        <th class="px-5 py-3 text-right text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Records</th>
                    </tr>
                </thead>
                <template x-for="pupil in paginatedPupils" :key="pupil.pupil_id">
                    <tbody class="divide-y divide-gray-100 bg-white dark:divide-gray-800 dark:bg-transparent">
                        <tr class="hover:bg-gray-50/80 dark:hover:bg-white/[0.03]">
                            <td class="px-5 py-4 align-top">
                                <p class="font-medium text-gray-900 dark:text-white" x-text="pupil.full_name"></p>
                                <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                                    <span x-text="pupil.lrn || 'No LRN'"></span>
                                    <span x-show="pupil.sex"> | <span x-text="pupil.sex"></span></span>
                                    <span x-show="pupil.age"> | Age <span x-text="pupil.age"></span></span>
                                </p>
                            </td>
                            <td class="px-5 py-4 align-top">
                                <span class="inline-flex rounded-full px-2.5 py-1 text-xs font-medium" :class="statusBadgeClass(pupil.english.assessed)" x-text="pupil.english.status"></span>
                                <p class="mt-2 text-xs text-gray-500 dark:text-gray-400" x-text="pupil.english.assessed ? recordDate(pupil.english.record) : 'No record yet'"></p>
                            </td>
                            <td class="px-5 py-4 align-top">
                                <span class="inline-flex rounded-full px-2.5 py-1 text-xs font-medium" :class="statusBadgeClass(pupil.filipino.assessed)" x-text="pupil.filipino.status"></span>
                                <p class="mt-2 text-xs text-gray-500 dark:text-gray-400" x-text="pupil.filipino.assessed ? recordDate(pupil.filipino.record) : 'No record yet'"></p>
                            </td>
                            <td class="px-5 py-4 align-top">
                                <span class="inline-flex rounded-full px-2.5 py-1 text-xs font-medium" :class="pupil.is_complete ? 'bg-green-100 text-green-700 dark:bg-green-500/10 dark:text-green-400' : 'bg-gray-100 text-gray-700 dark:bg-white/10 dark:text-gray-300'" x-text="pupil.is_complete ? 'Complete' : 'Incomplete'"></span>
                            </td>
                            <td class="px-5 py-4 text-right align-top">
                                <button type="button" @click="openPupilRecords(pupil)" :disabled="!pupil.has_any_record" class="inline-flex h-9 items-center justify-center rounded-lg border border-gray-300 px-3 text-sm font-medium text-gray-700 shadow-theme-xs transition hover:border-brand-300 hover:bg-brand-50 hover:text-brand-700 disabled:cursor-not-allowed disabled:border-gray-200 disabled:bg-gray-50 disabled:text-gray-400 disabled:opacity-70 dark:border-gray-700 dark:text-gray-300 dark:hover:border-brand-500/40 dark:hover:bg-brand-500/10 dark:hover:text-brand-300 dark:disabled:border-gray-800 dark:disabled:bg-white/[0.03] dark:disabled:text-gray-600">
                                    View Record
                                </button>
                            </td>
                        </tr>
                    </tbody>
                </template>
            </table>
        </div>

        <div x-show="filteredPupils.length === 0" class="px-4 py-10 text-center text-sm text-gray-500 dark:text-gray-400">No pupils found for this search.</div>
        <div class="flex flex-col gap-3 border-t border-gray-200 px-5 py-4 dark:border-gray-800 sm:flex-row sm:items-center sm:justify-between">
            <p class="text-sm text-gray-500 dark:text-gray-400">Showing <span x-text="tableStartItem"></span> to <span x-text="tableEndItem"></span> of <span x-text="filteredPupils.length"></span> pupils</p>
            <div class="flex flex-wrap items-center gap-2">
                <button type="button" @click="previousPage" :disabled="tablePage === 1" class="rounded-lg border border-gray-300 px-3 py-2 text-sm font-medium text-gray-700 disabled:cursor-not-allowed disabled:opacity-50 dark:border-gray-700 dark:text-gray-300">Previous</button>
                <span class="text-sm text-gray-500 dark:text-gray-400">Page <span x-text="tablePage"></span> of <span x-text="totalPages"></span></span>
                <button type="button" @click="nextPage" :disabled="tablePage >= totalPages" class="rounded-lg border border-gray-300 px-3 py-2 text-sm font-medium text-gray-700 disabled:cursor-not-allowed disabled:opacity-50 dark:border-gray-700 dark:text-gray-300">Next</button>
            </div>
        </div>
    </section>

    <div
        x-show="recordModalOpen && selectedPupil"
        x-transition.opacity
        x-cloak
        class="fixed inset-0 z-[99999] flex items-center justify-center p-4 sm:p-6"
        role="dialog"
        aria-modal="true"
        aria-labelledby="assessment-record-modal-title"
        @keydown.escape.window="closePupilRecords()"
    >
        <div class="absolute inset-0 bg-gray-950/70 backdrop-blur-sm" @click="closePupilRecords()"></div>

        <div
            x-show="recordModalOpen && selectedPupil"
            x-transition:enter="transition ease-out duration-200"
            x-transition:enter-start="translate-y-4 scale-95 opacity-0"
            x-transition:enter-end="translate-y-0 scale-100 opacity-100"
            x-transition:leave="transition ease-in duration-150"
            x-transition:leave-start="translate-y-0 scale-100 opacity-100"
            x-transition:leave-end="translate-y-4 scale-95 opacity-0"
            @click.stop
            class="relative max-h-[92vh] w-full max-w-6xl overflow-hidden rounded-3xl border border-white/20 bg-white shadow-2xl dark:border-gray-800 dark:bg-gray-950"
        >
            <div class="border-b border-gray-200 bg-white px-6 py-5 dark:border-gray-800 dark:bg-gray-950 sm:px-8">
                <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                    <div class="flex items-start gap-4">
                        <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-xl border border-gray-200 bg-gray-50 text-gray-700 dark:border-gray-800 dark:bg-white/[0.03] dark:text-gray-200">
                            <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                <path d="M7.75 4.75H16.25C17.3546 4.75 18.25 5.64543 18.25 6.75V17.25C18.25 18.3546 17.3546 19.25 16.25 19.25H7.75C6.64543 19.25 5.75 18.3546 5.75 17.25V6.75C5.75 5.64543 6.64543 4.75 7.75 4.75Z" stroke="currentColor" stroke-width="1.7"/>
                                <path d="M8.75 9.25H15.25" stroke="currentColor" stroke-width="1.7" stroke-linecap="round"/>
                                <path d="M8.75 12.25H15.25" stroke="currentColor" stroke-width="1.7" stroke-linecap="round"/>
                                <path d="M8.75 15.25H12.25" stroke="currentColor" stroke-width="1.7" stroke-linecap="round"/>
                            </svg>
                        </div>
                        <div class="min-w-0">
                            <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Assessment Record</p>
                            <h3 id="assessment-record-modal-title" class="mt-1 text-2xl font-semibold leading-tight text-gray-900 dark:text-white" x-text="selectedPupil?.full_name || 'Pupil Record'"></h3>
                            <div class="mt-3 flex flex-wrap gap-2 text-sm text-gray-600 dark:text-gray-300">
                                <span class="rounded-lg border border-gray-200 bg-gray-50 px-3 py-1 dark:border-gray-800 dark:bg-white/[0.03]" x-text="selectedPupil?.lrn ? `LRN: ${selectedPupil.lrn}` : 'No LRN'"></span>
                                <span x-show="selectedPupil?.sex" class="rounded-lg border border-gray-200 bg-gray-50 px-3 py-1 dark:border-gray-800 dark:bg-white/[0.03]" x-text="`Sex: ${selectedPupil?.sex}`"></span>
                                <span x-show="selectedPupil?.age" class="rounded-lg border border-gray-200 bg-gray-50 px-3 py-1 dark:border-gray-800 dark:bg-white/[0.03]" x-text="`Age: ${selectedPupil?.age}`"></span>
                                <span class="rounded-lg border border-gray-200 bg-gray-50 px-3 py-1 dark:border-gray-800 dark:bg-white/[0.03]" x-text="`${selectedAssignment?.grade_label || ''} - ${selectedAssignment?.section_name || ''}`"></span>
                            </div>
                        </div>
                    </div>

                    <div class="flex items-center gap-2 lg:justify-end">
                        <span class="rounded-lg border border-gray-200 bg-gray-50 px-3 py-1.5 text-sm font-medium text-gray-700 dark:border-gray-800 dark:bg-white/[0.03] dark:text-gray-200" x-text="selectedPupil?.is_complete ? 'Complete' : 'Incomplete'"></span>
                        <button type="button" @click="closePupilRecords()" class="inline-flex h-11 items-center justify-center gap-2 rounded-lg border border-gray-300 bg-white px-4 text-sm font-medium text-gray-700 shadow-theme-xs transition hover:bg-gray-50 focus:outline-hidden focus:ring-3 focus:ring-gray-300/50 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-200 dark:hover:bg-white/[0.04]" aria-label="Close modal">
                            <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                <path d="M6.75 6.75L17.25 17.25" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/>
                                <path d="M17.25 6.75L6.75 17.25" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/>
                            </svg>
                            <span>Close</span>
                        </button>
                    </div>
                </div>
            </div>

            <div class="max-h-[calc(92vh-176px)] overflow-y-auto bg-gray-50 p-4 dark:bg-gray-950 sm:p-6 readbee-modal-scrollbar">
                <div class="mb-5 grid grid-cols-1 gap-3 sm:grid-cols-3">
                    <div class="rounded-2xl border border-gray-200 bg-white p-4 shadow-theme-xs dark:border-gray-800 dark:bg-white/[0.03]">
                        <p class="text-xs font-medium uppercase tracking-wide text-gray-500 dark:text-gray-400">English Status</p>
                        <div class="mt-3 flex items-center justify-between gap-3">
                            <span class="rounded-full px-2.5 py-1 text-xs font-semibold" :class="statusBadgeClass(selectedPupil?.english?.assessed)" x-text="selectedPupil?.english?.status || 'Not assessed'"></span>
                            <span class="text-xs text-gray-500 dark:text-gray-400" x-text="selectedPupil?.english?.assessed ? recordDate(selectedPupil?.english?.record) : 'No record'"></span>
                        </div>
                    </div>
                    <div class="rounded-2xl border border-gray-200 bg-white p-4 shadow-theme-xs dark:border-gray-800 dark:bg-white/[0.03]">
                        <p class="text-xs font-medium uppercase tracking-wide text-gray-500 dark:text-gray-400">Filipino Status</p>
                        <div class="mt-3 flex items-center justify-between gap-3">
                            <span class="rounded-full px-2.5 py-1 text-xs font-semibold" :class="statusBadgeClass(selectedPupil?.filipino?.assessed)" x-text="selectedPupil?.filipino?.status || 'Not assessed'"></span>
                            <span class="text-xs text-gray-500 dark:text-gray-400" x-text="selectedPupil?.filipino?.assessed ? recordDate(selectedPupil?.filipino?.record) : 'No record'"></span>
                        </div>
                    </div>
                    <div class="rounded-2xl border border-gray-200 bg-white p-4 shadow-theme-xs dark:border-gray-800 dark:bg-white/[0.03]">
                        <p class="text-xs font-medium uppercase tracking-wide text-gray-500 dark:text-gray-400">Assessment Context</p>
                        <p class="mt-3 text-sm font-semibold text-gray-900 dark:text-white" x-text="selectedAssignment?.quarter_label || 'Quarter not set'"></p>
                        <p class="mt-1 text-xs text-gray-500 dark:text-gray-400" x-text="`Date: ${formatDate(selectedAssignment?.assessment_date)}`"></p>
                    </div>
                </div>

                <div class="grid grid-cols-1 gap-5 xl:grid-cols-2">
                    <template x-for="language in ['english', 'filipino']" :key="`modal-${selectedPupil?.pupil_id}-${language}`">
                        <section class="overflow-hidden rounded-3xl border border-gray-200 bg-white shadow-theme-xs dark:border-gray-800 dark:bg-white/[0.03]">
                            <div class="flex flex-col gap-3 border-b border-gray-200 px-5 py-4 dark:border-gray-800 sm:flex-row sm:items-start sm:justify-between">
                                <div>
                                    <p class="text-xs font-semibold uppercase tracking-[0.18em] text-brand-600 dark:text-brand-400" x-text="languageLabel(language)"></p>
                                    <h4 class="mt-1 text-lg font-semibold text-gray-900 dark:text-white">Assessment Details</h4>
                                    <p class="mt-1 text-xs text-gray-500 dark:text-gray-400" x-text="selectedPupil?.[language]?.assessed ? recordDate(selectedPupil?.[language]?.record) : 'No assessment record yet'"></p>
                                </div>
                                <span class="w-fit rounded-full px-3 py-1 text-xs font-semibold" :class="statusBadgeClass(selectedPupil?.[language]?.assessed)" x-text="selectedPupil?.[language]?.status || 'Not assessed'"></span>
                            </div>

                            <template x-if="selectedPupil?.[language]?.assessed">
                                <div>
                                    <div class="grid grid-cols-1 gap-3 p-5 sm:grid-cols-2">
                                        <template x-for="item in recordHighlightMetrics(selectedPupil?.[language]?.record)" :key="`${language}-highlight-${item.label}`">
                                            <div class="rounded-2xl bg-gray-50 p-4 dark:bg-white/[0.04]">
                                                <p class="text-xs font-medium text-gray-500 dark:text-gray-400" x-text="item.label"></p>
                                                <p class="mt-1 text-base font-semibold text-gray-900 dark:text-white" x-text="item.value"></p>
                                            </div>
                                        </template>
                                    </div>

                                    <div class="border-t border-gray-200 px-5 py-4 dark:border-gray-800">
                                        <h5 class="text-sm font-semibold text-gray-900 dark:text-white">Record Information</h5>
                                        <div class="mt-3 divide-y divide-gray-100 overflow-hidden rounded-2xl border border-gray-200 dark:divide-gray-800 dark:border-gray-800">
                                            <template x-for="metric in recordMetrics(selectedPupil?.[language]?.record)" :key="`${language}-${metric.label}`">
                                                <div class="grid grid-cols-1 gap-1 bg-white px-4 py-3 text-sm dark:bg-gray-950/40 sm:grid-cols-[180px_1fr]">
                                                    <span class="text-gray-500 dark:text-gray-400" x-text="metric.label"></span>
                                                    <span class="font-medium text-gray-900 dark:text-white" x-text="metric.value"></span>
                                                </div>
                                            </template>
                                        </div>
                                    </div>

                                    <div class="grid grid-cols-1 gap-4 border-t border-gray-200 p-5 dark:border-gray-800 md:grid-cols-2">
                                        <div>
                                            <div class="mb-2 flex items-center justify-between gap-3">
                                                <p class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Miscue Summary</p>
                                                <span class="text-xs text-gray-400" x-text="`${selectedPupil?.[language]?.record?.miscue_summary?.length || 0} items`"></span>
                                            </div>
                                            <div class="overflow-hidden rounded-2xl border border-gray-200 bg-white dark:border-gray-800 dark:bg-gray-950/40">
                                                <template x-for="item in selectedPupil?.[language]?.record?.miscue_summary || []" :key="`${language}-modal-miscue-${item.type}`">
                                                    <div class="flex justify-between gap-3 border-b border-gray-100 px-4 py-3 text-xs last:border-b-0 dark:border-gray-800">
                                                        <span class="text-gray-500 dark:text-gray-400" x-text="item.type"></span>
                                                        <span class="font-semibold text-gray-900 dark:text-white" x-text="item.count"></span>
                                                    </div>
                                                </template>
                                                <div x-show="!(selectedPupil?.[language]?.record?.miscue_summary || []).length" class="px-4 py-5 text-center text-xs text-gray-500 dark:text-gray-400">No miscues recorded.</div>
                                            </div>
                                        </div>
                                        <div>
                                            <div class="mb-2 flex items-center justify-between gap-3">
                                                <p class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Comprehension Summary</p>
                                                <span class="text-xs text-gray-400" x-text="`${selectedPupil?.[language]?.record?.comprehension_summary?.length || 0} items`"></span>
                                            </div>
                                            <div class="overflow-hidden rounded-2xl border border-gray-200 bg-white dark:border-gray-800 dark:bg-gray-950/40">
                                                <template x-for="item in selectedPupil?.[language]?.record?.comprehension_summary || []" :key="`${language}-modal-comp-${item.type}`">
                                                    <div class="flex justify-between gap-3 border-b border-gray-100 px-4 py-3 text-xs last:border-b-0 dark:border-gray-800">
                                                        <span class="text-gray-500 dark:text-gray-400" x-text="item.type"></span>
                                                        <span class="font-semibold text-gray-900 dark:text-white" x-text="item.count"></span>
                                                    </div>
                                                </template>
                                                <div x-show="!(selectedPupil?.[language]?.record?.comprehension_summary || []).length" class="px-4 py-5 text-center text-xs text-gray-500 dark:text-gray-400">No comprehension items recorded.</div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </template>

                            <template x-if="!selectedPupil?.[language]?.assessed">
                                <div class="px-6 py-12 text-center">
                                    <div class="mx-auto flex h-14 w-14 items-center justify-center rounded-2xl bg-gray-100 text-gray-400 dark:bg-white/[0.05] dark:text-gray-500">
                                        <svg class="h-7 w-7" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                            <path d="M12 8.75V12.25" stroke="currentColor" stroke-width="1.7" stroke-linecap="round"/>
                                            <path d="M12 15.25H12.01" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                                            <path d="M10.3 4.75H13.7L20.25 16.25L18.55 19.25H5.45L3.75 16.25L10.3 4.75Z" stroke="currentColor" stroke-width="1.7" stroke-linejoin="round"/>
                                        </svg>
                                    </div>
                                    <h5 class="mt-4 text-base font-semibold text-gray-900 dark:text-white">No record available</h5>
                                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400" x-text="`${languageLabel(language)} assessment has not been recorded for this pupil yet.`"></p>
                                </div>
                            </template>
                        </section>
                    </template>
                </div>
            </div>
        </div>
    </div>
</div>
