@props([
    'schoolYears' => [],
    'selectedYearId' => null,
    'summary' => [],
    'grades' => [],
])

<style>
    [x-cloak] { display: none !important; }

    .readbee-progress-bar {
        background: linear-gradient(90deg, #f2c94c 0%, #e0b83d 100%);
    }

    .readbee-monitoring-card {
        transition: border-color .2s ease, box-shadow .2s ease, transform .2s ease, background-color .2s ease;
    }

    .readbee-monitoring-card:hover {
        transform: translateY(-2px);
    }
</style>

<script>
    window.principalProgressMonitoringPage = function(schoolYears, selectedYearId, summary, grades) {
        return {
            schoolYears: schoolYears || [],
            selectedYearId: selectedYearId || '',
            yearPickerValue: selectedYearId || '',
            summary: summary || {},
            grades: grades || [],
            gradeSearch: '',
            sectionSearch: '',
            pupilSearch: '',
            statusFilter: 'all',
            selectedGrade: null,
            selectedSection: null,
            selectedPupil: null,
            selectedRecord: null,
            selectedLanguage: '',
            recordModalOpen: false,
            loadingYear: false,
            indexUrl: '',
            lastUpdatedAt: null,

            init(root) {
                this.indexUrl = root.dataset.indexUrl;

                if (!this.yearPickerValue && this.schoolYears.length > 0) {
                    this.yearPickerValue = this.schoolYears[0].year_id;
                    this.selectedYearId = this.yearPickerValue;
                }

                this.lastUpdatedAt = new Date();
            },

            destroy() {
                document.body.style.overflow = '';
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
                        alert(data.message || 'Unable to load progress monitoring data.');
                        return;
                    }

                    this.selectedYearId = data.selectedYearId;
                    this.yearPickerValue = data.selectedYearId;
                    this.schoolYears = data.schoolYears || this.schoolYears;
                    this.summary = data.summary || {};
                    this.grades = data.grades || [];
                    this.gradeSearch = '';
                    this.sectionSearch = '';
                    this.pupilSearch = '';
                    this.statusFilter = 'all';
                    this.selectedGrade = null;
                    this.selectedSection = null;
                    this.closeRecordModal();
                    this.lastUpdatedAt = new Date();
                    window.history.replaceState({}, '', `${this.indexUrl}?year_id=${this.selectedYearId}`);
                } catch (error) {
                    console.error('Principal progress monitoring year load error:', error);
                    alert('Unable to load progress monitoring data. Please try again.');
                } finally {
                    this.loadingYear = false;
                }
            },

            get filteredGrades() {
                const term = this.gradeSearch.trim().toLowerCase();

                return (this.grades || []).filter((grade) => {
                    const matchesSearch = !term || [
                        grade.grade_label,
                        grade.school_year_label,
                        grade.sections_count,
                    ].join(' ').toLowerCase().includes(term);

                    return matchesSearch && this.matchesStatus(grade.overall_percent);
                });
            },

            get filteredSections() {
                if (!this.selectedGrade) return [];

                const term = this.sectionSearch.trim().toLowerCase();

                return (this.selectedGrade.sections || []).filter((section) => {
                    const matchesSearch = !term || [
                        section.section_name,
                        section.adviser_name,
                        section.grade_label,
                    ].join(' ').toLowerCase().includes(term);

                    return matchesSearch && this.matchesStatus(section.overall_percent);
                });
            },

            get filteredPupils() {
                if (!this.selectedSection) return [];

                const term = this.pupilSearch.trim().toLowerCase();

                return (this.selectedSection.pupils || []).filter((pupil) => {
                    if (!term) return true;

                    return [
                        pupil.full_name,
                        pupil.lrn,
                        pupil.sex_label,
                        pupil.english?.status,
                        pupil.filipino?.status,
                        pupil.english?.record?.reading_level,
                        pupil.filipino?.record?.reading_level,
                        pupil.english?.record?.comprehension_level,
                        pupil.filipino?.record?.comprehension_level,
                    ].join(' ').toLowerCase().includes(term);
                });
            },

            get lastUpdatedText() {
                if (!this.lastUpdatedAt) return 'Progress data loaded';

                return `Last updated ${this.lastUpdatedAt.toLocaleTimeString('en-PH', {
                    hour: 'numeric',
                    minute: '2-digit',
                    second: '2-digit',
                })}`;
            },

            matchesStatus(percent) {
                if (this.statusFilter === 'complete') return Number(percent || 0) >= 100;
                if (this.statusFilter === 'incomplete') return Number(percent || 0) < 100;
                return true;
            },

            selectGrade(grade) {
                this.selectedGrade = grade;
                this.selectedSection = null;
                this.sectionSearch = '';
                this.pupilSearch = '';
                this.closeRecordModal();
                this.$nextTick(() => this.$refs.sectionPanel?.scrollIntoView({ behavior: 'smooth', block: 'start' }));
            },

            selectSection(section) {
                this.selectedSection = section;
                this.pupilSearch = '';
                this.closeRecordModal();
                this.$nextTick(() => this.$refs.pupilPanel?.scrollIntoView({ behavior: 'smooth', block: 'start' }));
            },

            backToGrades() {
                this.selectedGrade = null;
                this.selectedSection = null;
                this.sectionSearch = '';
                this.pupilSearch = '';
                this.closeRecordModal();
            },

            backToSections() {
                this.selectedSection = null;
                this.pupilSearch = '';
                this.closeRecordModal();
            },

            openRecordModal(pupil, language) {
                const languageData = pupil?.[language] || {};
                this.closeRecordModal(false);

                this.$nextTick(() => {
                    this.selectedPupil = pupil;
                    this.selectedLanguage = language === 'english' ? 'English' : 'Filipino';
                    this.selectedRecord = languageData.record || null;
                    this.recordModalOpen = true;
                    document.body.style.overflow = 'hidden';
                });
            },

            closeRecordModal(reset = true) {
                this.recordModalOpen = false;
                document.body.style.overflow = '';

                if (reset) {
                    this.selectedPupil = null;
                    this.selectedRecord = null;
                    this.selectedLanguage = '';
                }
            },

            percentage(value) {
                const numeric = Number(value || 0);
                return `${numeric % 1 === 0 ? numeric.toFixed(0) : numeric.toFixed(1)}%`;
            },

            formatDate(date) {
                if (!date) return '—';

                try {
                    return new Date(date).toLocaleDateString('en-PH', {
                        year: 'numeric',
                        month: 'short',
                        day: 'numeric',
                    });
                } catch (error) {
                    return date;
                }
            },

            completionBadgeClass(percent) {
                const value = Number(percent || 0);

                if (value >= 100) return 'bg-green-50 text-green-700 ring-1 ring-green-200 dark:bg-green-500/10 dark:text-green-300 dark:ring-green-500/20';
                if (value >= 50) return 'bg-amber-50 text-amber-700 ring-1 ring-amber-200 dark:bg-amber-500/10 dark:text-amber-300 dark:ring-amber-500/20';
                return 'bg-gray-100 text-gray-700 ring-1 ring-gray-200 dark:bg-white/10 dark:text-gray-300 dark:ring-white/10';
            },

            statusBadgeClass(assessed) {
                return assessed
                    ? 'bg-green-50 text-green-700 ring-1 ring-green-200 dark:bg-green-500/10 dark:text-green-300 dark:ring-green-500/20'
                    : 'bg-gray-100 text-gray-600 ring-1 ring-gray-200 dark:bg-white/10 dark:text-gray-300 dark:ring-white/10';
            },

            recordMetric(label, value) {
                return { label, value: value || '—' };
            },

            get recordMetrics() {
                if (!this.selectedRecord) return [];

                return [
                    this.recordMetric('Material', this.selectedRecord.material_title),
                    this.recordMetric('Quarter', this.selectedRecord.quarter_label),
                    this.recordMetric('Reading Level', this.selectedRecord.reading_level),
                    this.recordMetric('Reading Speed', this.selectedRecord.reading_speed),
                    this.recordMetric('Word per Minute', this.selectedRecord.word_per_minute),
                    this.recordMetric('Comprehension Level', this.selectedRecord.comprehension_level),
                    this.recordMetric('Comprehension Rate', this.selectedRecord.comprehension_rate),
                    this.recordMetric('Recorded', this.formatDate(this.selectedRecord.updated_at || this.selectedRecord.created_at)),
                ];
            },

            summaryRows(rows) {
                return Array.isArray(rows) ? rows : [];
            },
        };
    };
</script>

<div
    x-data="principalProgressMonitoringPage(@js($schoolYears), @js($selectedYearId), @js($summary), @js($grades))"
    x-init="init($el)"
    x-on:keydown.escape.window="recordModalOpen && closeRecordModal()"
    x-cloak
    data-index-url="{{ route('principal.progress-monitoring') }}"
    class="space-y-6"
>
    <section class="rounded-2xl border border-gray-200 bg-white p-5 shadow-theme-xs dark:border-gray-800 dark:bg-white/[0.03] sm:p-6">
        <div class="flex flex-col gap-5 xl:flex-row xl:items-center xl:justify-between">
            <div class="flex min-w-0 items-start gap-4">
                <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl bg-gray-100 text-gray-800 dark:bg-white/10 dark:text-white">
                    <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                        <path d="M4.75 19.25H19.25" stroke="currentColor" stroke-width="1.7" stroke-linecap="round"/>
                        <path d="M7.25 16.25V11.75" stroke="currentColor" stroke-width="1.7" stroke-linecap="round"/>
                        <path d="M12 16.25V7.75" stroke="currentColor" stroke-width="1.7" stroke-linecap="round"/>
                        <path d="M16.75 16.25V9.75" stroke="currentColor" stroke-width="1.7" stroke-linecap="round"/>
                    </svg>
                </div>
                <div class="min-w-0">
                    <p class="text-xs font-semibold uppercase tracking-[0.22em] text-gray-500 dark:text-gray-400">Principal Progress Monitoring</p>
                    <h1 class="mt-1 text-xl font-semibold text-gray-900 dark:text-white sm:text-2xl">School Assessment Progress</h1>
                    <p class="mt-1 max-w-3xl text-sm leading-6 text-gray-500 dark:text-gray-400">Monitor assessment completion by grade level, section, and pupil inside your school only.</p>
                </div>
            </div>

            <div class="flex w-full flex-col gap-2 sm:w-auto sm:min-w-[280px]">
                <label class="text-sm font-medium text-gray-700 dark:text-gray-300">School Year</label>
                <select x-model="yearPickerValue" @change="changeYear" :disabled="loadingYear" class="h-11 rounded-xl border border-gray-300 bg-white px-4 text-sm text-gray-700 shadow-theme-xs focus:border-brand-400 focus:outline-hidden focus:ring-3 focus:ring-brand-500/10 disabled:cursor-not-allowed disabled:opacity-60 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-200">
                    <template x-for="year in schoolYears" :key="year.year_id">
                        <option :value="year.year_id" x-text="year.label"></option>
                    </template>
                </select>
                <p class="text-xs text-gray-500 dark:text-gray-400" x-text="loadingYear ? 'Loading progress...' : lastUpdatedText"></p>
            </div>
        </div>
    </section>

    <section class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-5">
        <div class="rounded-2xl border border-gray-200 bg-white p-4 shadow-theme-xs dark:border-gray-800 dark:bg-white/[0.03]">
            <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Grade Levels</p>
            <p class="mt-2 text-2xl font-semibold text-gray-900 dark:text-white" x-text="summary.grades_count || 0"></p>
        </div>
        <div class="rounded-2xl border border-gray-200 bg-white p-4 shadow-theme-xs dark:border-gray-800 dark:bg-white/[0.03]">
            <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Sections</p>
            <p class="mt-2 text-2xl font-semibold text-gray-900 dark:text-white" x-text="summary.sections_count || 0"></p>
        </div>
        <div class="rounded-2xl border border-gray-200 bg-white p-4 shadow-theme-xs dark:border-gray-800 dark:bg-white/[0.03]">
            <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Pupils</p>
            <p class="mt-2 text-2xl font-semibold text-gray-900 dark:text-white" x-text="summary.total_pupils || 0"></p>
        </div>
        <div class="rounded-2xl border border-gray-200 bg-white p-4 shadow-theme-xs dark:border-gray-800 dark:bg-white/[0.03]">
            <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Completed Records</p>
            <p class="mt-2 text-2xl font-semibold text-gray-900 dark:text-white" x-text="`${summary.total_completed || 0}/${summary.total_required || 0}`"></p>
        </div>
        <div class="rounded-2xl border border-gray-200 bg-white p-4 shadow-theme-xs dark:border-gray-800 dark:bg-white/[0.03]">
            <div class="flex items-center justify-between gap-3">
                <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Overall</p>
                <span class="rounded-full px-2.5 py-1 text-xs font-semibold" :class="completionBadgeClass(summary.overall_percent)" x-text="percentage(summary.overall_percent)"></span>
            </div>
            <div class="mt-4 h-2.5 overflow-hidden rounded-full bg-gray-100 dark:bg-gray-800">
                <div class="readbee-progress-bar h-full rounded-full" :style="`width: ${Math.min(summary.overall_percent || 0, 100)}%`"></div>
            </div>
        </div>
    </section>

    <section x-show="!selectedGrade" x-transition class="rounded-2xl border border-gray-200 bg-white shadow-theme-xs dark:border-gray-800 dark:bg-white/[0.03]">
        <div class="flex flex-col gap-4 border-b border-gray-200 p-5 dark:border-gray-800 lg:flex-row lg:items-center lg:justify-between">
            <div>
                <h2 class="text-lg font-semibold text-gray-900 dark:text-white">Grade Level Progress</h2>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Start here. Select a grade level to view its sections and pupils.</p>
            </div>
            <div class="grid w-full grid-cols-1 gap-3 sm:grid-cols-[1fr_180px] lg:max-w-xl">
                <input type="search" x-model="gradeSearch" placeholder="Search grade level..." class="h-11 rounded-xl border border-gray-300 bg-white px-4 text-sm text-gray-700 shadow-theme-xs focus:border-brand-400 focus:outline-hidden focus:ring-3 focus:ring-brand-500/10 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-200" />
                <select x-model="statusFilter" class="h-11 rounded-xl border border-gray-300 bg-white px-4 text-sm text-gray-700 shadow-theme-xs focus:border-brand-400 focus:outline-hidden focus:ring-3 focus:ring-brand-500/10 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-200">
                    <option value="all">All Progress</option>
                    <option value="complete">Complete</option>
                    <option value="incomplete">Incomplete</option>
                </select>
            </div>
        </div>

        <div class="p-5">
            <div class="grid grid-cols-1 gap-4 xl:grid-cols-2">
                <template x-for="grade in filteredGrades" :key="grade.grade_level_id">
                    <article class="readbee-monitoring-card rounded-2xl border border-gray-200 bg-white p-5 shadow-theme-xs hover:border-brand-200 hover:shadow-theme-sm dark:border-gray-800 dark:bg-white/[0.03] dark:hover:border-brand-500/30">
                        <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                            <div class="min-w-0">
                                <div class="flex flex-wrap items-center gap-2">
                                    <span class="rounded-full bg-gray-100 px-2.5 py-1 text-xs font-medium text-gray-700 dark:bg-white/10 dark:text-gray-300" x-text="`${grade.sections_count || 0} section(s)`"></span>
                                    <span class="rounded-full px-2.5 py-1 text-xs font-semibold" :class="completionBadgeClass(grade.overall_percent)" x-text="percentage(grade.overall_percent)"></span>
                                </div>
                                <h3 class="mt-3 text-lg font-semibold text-gray-900 dark:text-white" x-text="grade.grade_label"></h3>
                                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400" x-text="`${grade.total_pupils || 0} enrolled pupil(s)`"></p>
                            </div>
                            <button type="button" @click="selectGrade(grade)" class="inline-flex h-10 shrink-0 items-center justify-center rounded-lg bg-brand-500 px-4 text-sm font-semibold text-white hover:bg-brand-600">
                                View Sections
                            </button>
                        </div>

                        <div class="mt-5 space-y-4">
                            <div>
                                <div class="mb-2 flex items-center justify-between gap-3 text-sm">
                                    <span class="font-medium text-gray-700 dark:text-gray-300">Overall completion</span>
                                    <span class="text-right text-gray-500 dark:text-gray-400" x-text="`${grade.total_completed}/${grade.total_required} required assessments`"></span>
                                </div>
                                <div class="h-2.5 overflow-hidden rounded-full bg-gray-100 dark:bg-gray-800">
                                    <div class="readbee-progress-bar h-full rounded-full" :style="`width: ${Math.min(grade.overall_percent || 0, 100)}%`"></div>
                                </div>
                            </div>

                            <div class="grid grid-cols-2 gap-3">
                                <div class="rounded-xl bg-gray-50 p-3 dark:bg-white/[0.04]">
                                    <p class="text-xs font-medium text-gray-500 dark:text-gray-400">English</p>
                                    <p class="mt-1 text-lg font-semibold text-gray-900 dark:text-white" x-text="`${grade.english_assessed}/${grade.total_pupils}`"></p>
                                    <p class="text-xs text-gray-500 dark:text-gray-400" x-text="percentage(grade.english_percent)"></p>
                                </div>
                                <div class="rounded-xl bg-gray-50 p-3 dark:bg-white/[0.04]">
                                    <p class="text-xs font-medium text-gray-500 dark:text-gray-400">Filipino</p>
                                    <p class="mt-1 text-lg font-semibold text-gray-900 dark:text-white" x-text="`${grade.filipino_assessed}/${grade.total_pupils}`"></p>
                                    <p class="text-xs text-gray-500 dark:text-gray-400" x-text="percentage(grade.filipino_percent)"></p>
                                </div>
                            </div>
                        </div>
                    </article>
                </template>
            </div>

            <div x-show="filteredGrades.length === 0" class="rounded-2xl border border-dashed border-gray-300 p-10 text-center dark:border-gray-700">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white">No grade level progress found</h3>
                <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">No grade level matched the selected school year or filter.</p>
            </div>
        </div>
    </section>

    <section x-show="selectedGrade && !selectedSection" x-ref="sectionPanel" x-transition class="rounded-2xl border border-gray-200 bg-white shadow-theme-xs dark:border-gray-800 dark:bg-white/[0.03]">
        <div class="flex flex-col gap-4 border-b border-gray-200 p-5 dark:border-gray-800 lg:flex-row lg:items-start lg:justify-between">
            <div>
                <p class="text-xs font-semibold uppercase tracking-[0.2em] text-brand-600 dark:text-brand-400">Sections</p>
                <h2 class="mt-2 text-xl font-semibold text-gray-900 dark:text-white" x-text="selectedGrade?.grade_label"></h2>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400" x-text="`${selectedGrade?.sections_count || 0} section(s) | ${selectedGrade?.total_pupils || 0} pupil(s)`"></p>
            </div>
            <button type="button" @click="backToGrades" class="inline-flex h-10 items-center justify-center rounded-lg border border-gray-300 px-4 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:text-gray-300 dark:hover:bg-white/5">
                Back to Grade Levels
            </button>
        </div>

        <div class="border-b border-gray-200 p-5 dark:border-gray-800">
            <input type="search" x-model="sectionSearch" placeholder="Search section or adviser..." class="h-11 w-full rounded-xl border border-gray-300 bg-white px-4 text-sm text-gray-700 shadow-theme-xs focus:border-brand-400 focus:outline-hidden focus:ring-3 focus:ring-brand-500/10 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-200" />
        </div>

        <div class="p-5">
            <div class="grid grid-cols-1 gap-4 xl:grid-cols-2">
                <template x-for="section in filteredSections" :key="section.section_id">
                    <article class="readbee-monitoring-card rounded-2xl border border-gray-200 bg-white p-5 shadow-theme-xs hover:border-brand-200 hover:shadow-theme-sm dark:border-gray-800 dark:bg-white/[0.03] dark:hover:border-brand-500/30">
                        <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                            <div class="min-w-0">
                                <div class="flex flex-wrap items-center gap-2">
                                    <span class="rounded-full bg-gray-100 px-2.5 py-1 text-xs font-medium text-gray-700 dark:bg-white/10 dark:text-gray-300" x-text="section.adviser_name ? `Adviser: ${section.adviser_name}` : 'No adviser set'"></span>
                                    <span class="rounded-full px-2.5 py-1 text-xs font-semibold" :class="completionBadgeClass(section.overall_percent)" x-text="percentage(section.overall_percent)"></span>
                                </div>
                                <h3 class="mt-3 text-lg font-semibold text-gray-900 dark:text-white" x-text="section.section_name"></h3>
                                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400" x-text="`${section.total_pupils || 0} enrolled pupil(s)`"></p>
                            </div>
                            <button type="button" @click="selectSection(section)" class="inline-flex h-10 shrink-0 items-center justify-center rounded-lg bg-brand-500 px-4 text-sm font-semibold text-white hover:bg-brand-600">
                                View Pupils
                            </button>
                        </div>

                        <div class="mt-5 space-y-4">
                            <div>
                                <div class="mb-2 flex items-center justify-between gap-3 text-sm">
                                    <span class="font-medium text-gray-700 dark:text-gray-300">Overall completion</span>
                                    <span class="text-right text-gray-500 dark:text-gray-400" x-text="`${section.total_completed}/${section.total_required} required assessments`"></span>
                                </div>
                                <div class="h-2.5 overflow-hidden rounded-full bg-gray-100 dark:bg-gray-800">
                                    <div class="readbee-progress-bar h-full rounded-full" :style="`width: ${Math.min(section.overall_percent || 0, 100)}%`"></div>
                                </div>
                            </div>

                            <div class="grid grid-cols-2 gap-3">
                                <div class="rounded-xl bg-gray-50 p-3 dark:bg-white/[0.04]">
                                    <p class="text-xs font-medium text-gray-500 dark:text-gray-400">English</p>
                                    <p class="mt-1 text-lg font-semibold text-gray-900 dark:text-white" x-text="`${section.english_assessed}/${section.total_pupils}`"></p>
                                    <p class="text-xs text-gray-500 dark:text-gray-400" x-text="percentage(section.english_percent)"></p>
                                </div>
                                <div class="rounded-xl bg-gray-50 p-3 dark:bg-white/[0.04]">
                                    <p class="text-xs font-medium text-gray-500 dark:text-gray-400">Filipino</p>
                                    <p class="mt-1 text-lg font-semibold text-gray-900 dark:text-white" x-text="`${section.filipino_assessed}/${section.total_pupils}`"></p>
                                    <p class="text-xs text-gray-500 dark:text-gray-400" x-text="percentage(section.filipino_percent)"></p>
                                </div>
                            </div>
                        </div>
                    </article>
                </template>
            </div>

            <div x-show="filteredSections.length === 0" class="rounded-2xl border border-dashed border-gray-300 p-10 text-center dark:border-gray-700">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white">No sections found</h3>
                <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">No section matched your search.</p>
            </div>
        </div>
    </section>

    <section x-show="selectedSection" x-ref="pupilPanel" x-transition class="rounded-2xl border border-gray-200 bg-white shadow-theme-xs dark:border-gray-800 dark:bg-white/[0.03]">
        <div class="flex flex-col gap-4 border-b border-gray-200 p-5 dark:border-gray-800 lg:flex-row lg:items-start lg:justify-between">
            <div>
                <p class="text-xs font-semibold uppercase tracking-[0.2em] text-brand-600 dark:text-brand-400">Pupil Assessment Status</p>
                <h2 class="mt-2 text-xl font-semibold text-gray-900 dark:text-white">
                    <span x-text="selectedGrade?.grade_label"></span>
                    <span> - </span>
                    <span x-text="selectedSection?.section_name"></span>
                </h2>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400" x-text="selectedSection?.adviser_name ? `Adviser: ${selectedSection.adviser_name}` : 'No adviser set'"></p>
            </div>
            <button type="button" @click="backToSections" class="inline-flex h-10 items-center justify-center rounded-lg border border-gray-300 px-4 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:text-gray-300 dark:hover:bg-white/5">
                Back to Sections
            </button>
        </div>

        <div class="border-b border-gray-200 p-5 dark:border-gray-800">
            <input type="search" x-model="pupilSearch" placeholder="Search pupil, LRN, status, or level..." class="h-11 w-full rounded-xl border border-gray-300 bg-white px-4 text-sm text-gray-700 shadow-theme-xs focus:border-brand-400 focus:outline-hidden focus:ring-3 focus:ring-brand-500/10 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-200" />
        </div>

        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-800">
                <thead class="bg-gray-50 dark:bg-white/[0.03]">
                    <tr>
                        <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">Pupil</th>
                        <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">Sex</th>
                        <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">English</th>
                        <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">Filipino</th>
                        <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">Overall</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                    <template x-for="pupil in filteredPupils" :key="pupil.pupil_id">
                        <tr>
                            <td class="px-5 py-4 align-top">
                                <p class="font-medium text-gray-900 dark:text-white" x-text="pupil.full_name"></p>
                                <p class="mt-0.5 text-xs text-gray-500 dark:text-gray-400" x-text="pupil.lrn ? `LRN: ${pupil.lrn}` : 'No LRN' "></p>
                            </td>
                            <td class="px-5 py-4 align-top text-sm text-gray-600 dark:text-gray-300" x-text="pupil.sex_label"></td>
                            <td class="px-5 py-4 align-top">
                                <div class="space-y-2">
                                    <span class="inline-flex rounded-full px-2.5 py-1 text-xs font-semibold" :class="statusBadgeClass(pupil.english?.assessed)" x-text="pupil.english?.status"></span>
                                    <div x-show="pupil.english?.assessed">
                                        <p class="text-xs text-gray-500 dark:text-gray-400" x-text="pupil.english?.record?.reading_level || 'No reading level'"></p>
                                        <button type="button" @click="openRecordModal(pupil, 'english')" class="mt-1 text-xs font-semibold text-brand-600 hover:text-brand-700 dark:text-brand-400">View record</button>
                                    </div>
                                </div>
                            </td>
                            <td class="px-5 py-4 align-top">
                                <div class="space-y-2">
                                    <span class="inline-flex rounded-full px-2.5 py-1 text-xs font-semibold" :class="statusBadgeClass(pupil.filipino?.assessed)" x-text="pupil.filipino?.status"></span>
                                    <div x-show="pupil.filipino?.assessed">
                                        <p class="text-xs text-gray-500 dark:text-gray-400" x-text="pupil.filipino?.record?.reading_level || 'No reading level'"></p>
                                        <button type="button" @click="openRecordModal(pupil, 'filipino')" class="mt-1 text-xs font-semibold text-brand-600 hover:text-brand-700 dark:text-brand-400">View record</button>
                                    </div>
                                </div>
                            </td>
                            <td class="px-5 py-4 align-top">
                                <span class="inline-flex rounded-full px-2.5 py-1 text-xs font-semibold" :class="pupil.is_complete ? completionBadgeClass(100) : completionBadgeClass(50)" x-text="pupil.is_complete ? 'Complete' : (pupil.has_any_record ? 'Partial' : 'Not started')"></span>
                            </td>
                        </tr>
                    </template>
                </tbody>
            </table>
        </div>

        <div x-show="filteredPupils.length === 0" class="p-10 text-center">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white">No pupils found</h3>
            <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">No pupil matched your search.</p>
        </div>
    </section>

    <div x-show="recordModalOpen" x-cloak class="fixed inset-0 z-[99999] flex items-center justify-center p-4" role="dialog" aria-modal="true">
        <div class="absolute inset-0 bg-gray-900/45" @click="closeRecordModal()"></div>
        <div class="relative z-10 flex max-h-[92vh] w-full max-w-4xl flex-col overflow-hidden rounded-2xl bg-white shadow-2xl ring-1 ring-gray-200 dark:bg-gray-950 dark:ring-gray-800">
            <div class="flex items-start justify-between gap-4 border-b border-gray-200 px-5 py-4 dark:border-gray-800">
                <div class="min-w-0">
                    <p class="text-xs font-semibold uppercase tracking-[0.2em] text-gray-500 dark:text-gray-400" x-text="selectedLanguage + ' Assessment Record'"></p>
                    <h2 class="mt-1 text-lg font-semibold text-gray-900 dark:text-white" x-text="selectedPupil?.full_name || 'Pupil Record'"></h2>
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                        <span x-text="selectedGrade?.grade_label"></span>
                        <span> - </span>
                        <span x-text="selectedSection?.section_name"></span>
                    </p>
                </div>
                <button type="button" @click="closeRecordModal()" class="rounded-lg border border-gray-300 px-3 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:text-gray-300 dark:hover:bg-white/5">
                    Close
                </button>
            </div>

            <div class="overflow-y-auto px-5 py-5">
                <template x-if="selectedRecord">
                    <div class="space-y-5">
                        <div class="grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-4">
                            <template x-for="metric in recordMetrics" :key="metric.label">
                                <div class="rounded-xl border border-gray-200 bg-gray-50 p-3 dark:border-gray-800 dark:bg-white/[0.03]">
                                    <p class="text-xs font-medium text-gray-500 dark:text-gray-400" x-text="metric.label"></p>
                                    <p class="mt-1 text-sm font-semibold text-gray-900 dark:text-white" x-text="metric.value"></p>
                                </div>
                            </template>
                        </div>

                        <div class="grid grid-cols-1 gap-4 lg:grid-cols-2">
                            <div class="rounded-xl border border-gray-200 p-4 dark:border-gray-800">
                                <h3 class="font-semibold text-gray-900 dark:text-white">Reading Summary</h3>
                                <div class="mt-3 space-y-2">
                                    <template x-for="row in summaryRows(selectedRecord.reading_overall_summary)" :key="row.type">
                                        <div class="flex items-center justify-between gap-4 text-sm">
                                            <span class="text-gray-500 dark:text-gray-400" x-text="row.type"></span>
                                            <span class="font-medium text-gray-900 dark:text-white" x-text="row.count || '—'"></span>
                                        </div>
                                    </template>
                                    <p x-show="summaryRows(selectedRecord.reading_overall_summary).length === 0" class="text-sm text-gray-500 dark:text-gray-400">No reading summary available.</p>
                                </div>
                            </div>

                            <div class="rounded-xl border border-gray-200 p-4 dark:border-gray-800">
                                <h3 class="font-semibold text-gray-900 dark:text-white">Comprehension Summary</h3>
                                <div class="mt-3 space-y-2">
                                    <template x-for="row in summaryRows(selectedRecord.comprehension_summary)" :key="row.type">
                                        <div class="flex items-center justify-between gap-4 text-sm">
                                            <span class="text-gray-500 dark:text-gray-400" x-text="row.type"></span>
                                            <span class="font-medium text-gray-900 dark:text-white" x-text="row.count || '—'"></span>
                                        </div>
                                    </template>
                                    <p x-show="summaryRows(selectedRecord.comprehension_summary).length === 0" class="text-sm text-gray-500 dark:text-gray-400">No comprehension summary available.</p>
                                </div>
                            </div>
                        </div>

                        <div class="rounded-xl border border-gray-200 p-4 dark:border-gray-800">
                            <h3 class="font-semibold text-gray-900 dark:text-white">Miscue Details</h3>
                            <div class="mt-3 grid grid-cols-1 gap-2 sm:grid-cols-2">
                                <template x-for="row in summaryRows(selectedRecord.miscue_summary)" :key="row.type">
                                    <div class="flex items-center justify-between gap-4 rounded-lg bg-gray-50 px-3 py-2 text-sm dark:bg-white/[0.03]">
                                        <span class="text-gray-500 dark:text-gray-400" x-text="row.type"></span>
                                        <span class="font-medium text-gray-900 dark:text-white" x-text="row.count || 0"></span>
                                    </div>
                                </template>
                            </div>
                            <p x-show="summaryRows(selectedRecord.miscue_summary).length === 0" class="mt-3 text-sm text-gray-500 dark:text-gray-400">No miscues recorded.</p>
                        </div>
                    </div>
                </template>

                <template x-if="!selectedRecord">
                    <div class="rounded-xl border border-dashed border-gray-300 p-8 text-center dark:border-gray-700">
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white">No record available</h3>
                        <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">This pupil has no assessment record for the selected language.</p>
                    </div>
                </template>
            </div>
        </div>
    </div>
</div>
