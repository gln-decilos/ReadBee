@props([
    'dashboardData' => [],
    'dashboardUrl' => route('principal.dashboard'),
])

<style>
    [x-cloak] { display: none !important; }

    .readbee-principal-dashboard {
        --readbee-yellow: #f2c94c;
        --readbee-black: #111827;
        --readbee-charcoal: #1f2937;
        --readbee-gray: #667085;
        --readbee-line: #e5e7eb;
        --readbee-brand-hover: #2c3e50;
    }


    .readbee-principal-dashboard,
    .readbee-principal-dashboard .readbee-dashboard-hero,
    .readbee-principal-dashboard .readbee-chart-box,
    .readbee-principal-dashboard .readbee-stat-card,
    .readbee-principal-dashboard .readbee-dashboard-mini-card,
    .readbee-principal-dashboard .readbee-dashboard-pill,
    .readbee-principal-dashboard select,
    .readbee-principal-dashboard button,
    .readbee-filter-popover {
        transition-property: background, background-color, border-color, color, box-shadow, opacity, transform;
        transition-duration: 280ms;
        transition-timing-function: cubic-bezier(.4, 0, .2, 1);
    }

    .readbee-principal-dashboard .apexcharts-canvas,
    .readbee-principal-dashboard .apexcharts-canvas svg,
    .readbee-principal-dashboard .apexcharts-canvas svg * {
        transition-property: fill, stroke, color, opacity;
        transition-duration: 280ms;
        transition-timing-function: cubic-bezier(.4, 0, .2, 1);
    }

    @media (prefers-reduced-motion: reduce) {
        .readbee-principal-dashboard,
        .readbee-principal-dashboard *,
        .readbee-filter-popover,
        .readbee-filter-popover * {
            transition-duration: 1ms !important;
            animation-duration: 1ms !important;
        }
    }

    .readbee-dashboard-hero {
        position: relative;
        overflow: hidden;
        border-radius: 18px;
        background:
            radial-gradient(circle at 94% 0%, rgba(242, 201, 76, .12), transparent 34%),
            linear-gradient(135deg, #ffffff 0%, #fffdf7 48%, #f8fafc 100%);
        border: 1px solid rgba(229, 231, 235, .95);
    }

    .readbee-dashboard-hero::after {
        content: '';
        position: absolute;
        right: -72px;
        top: -86px;
        width: 210px;
        height: 210px;
        border-radius: 999px;
        background: rgba(242, 201, 76, .08);
        filter: blur(5px);
    }

    .dark .readbee-dashboard-hero {
        background:
            radial-gradient(circle at 92% 0%, rgba(242, 201, 76, .08), transparent 34%),
            linear-gradient(135deg, rgba(255, 255, 255, .055) 0%, rgba(255, 255, 255, .03) 58%, rgba(148, 163, 184, .07) 100%);
        border-color: rgba(255, 255, 255, .09);
    }

    .readbee-stat-card {
        min-width: 0;
        transition: transform .22s ease, box-shadow .22s ease, border-color .22s ease, background .22s ease;
    }

    .readbee-stat-card:hover {
        transform: translateY(-4px);
        background: var(--readbee-brand-hover);
        border-color: var(--readbee-brand-hover);
        box-shadow: 0 16px 35px rgba(44, 62, 80, .24);
    }

    .readbee-stat-card:hover .readbee-stat-title,
    .readbee-stat-card:hover .readbee-stat-value,
    .readbee-stat-card:hover .readbee-stat-helper {
        color: #ffffff !important;
    }

    .readbee-stat-card:hover .readbee-stat-percent {
        background: rgba(255, 255, 255, .14) !important;
        color: #ffffff !important;
    }

    .readbee-stat-icon {
        background: transparent;
        color: var(--readbee-black);
        border: 0 !important;
        box-shadow: none;
        transition: color .22s ease, transform .22s ease;
    }

    .readbee-stat-card:hover .readbee-stat-icon {
        color: #ffffff;
        transform: translateY(-1px);
    }

    .dark .readbee-stat-icon {
        color: #ffffff;
    }

    .readbee-chart-host {
        min-height: 260px;
        width: 100%;
        min-width: 0;
        overflow: hidden;
    }

    .readbee-chart-host > div,
    .readbee-chart-box .apexcharts-canvas,
    .readbee-chart-box svg {
        width: 100% !important;
        max-width: 100% !important;
    }

    .readbee-chart-host.small-chart {
        min-height: 230px;
    }

    .readbee-dashboard-pill {
        border: 1px solid rgba(229, 231, 235, .95);
        background: rgba(248, 250, 252, .86);
        color: #475467;
    }

    .dark .readbee-dashboard-pill {
        border-color: rgba(255, 255, 255, .10) !important;
        background: rgba(255, 255, 255, .07) !important;
        color: #f9fafb !important;
    }

    .readbee-dashboard-mini-card {
        background: rgba(255, 255, 255, .82);
        border: 1px solid rgba(17, 24, 39, .06);
        backdrop-filter: blur(10px);
    }

    .dark .readbee-dashboard-mini-card {
        background: rgba(255, 255, 255, .07);
        border-color: rgba(255, 255, 255, .08);
    }

    .readbee-dashboard-mini-card.is-accent {
        background: rgba(242, 201, 76, .12);
        border-color: rgba(242, 201, 76, .25);
    }


    .readbee-filter-popover {
        position: fixed;
        z-index: 99999;
        max-height: calc(100vh - 2rem);
        overflow-y: auto;
    }

    .readbee-filter-popover-overlay {
        position: fixed;
        inset: 0;
        z-index: 99998;
        background: transparent;
    }

    .readbee-speed-layout {
        display: grid;
        grid-template-columns: minmax(0, 1fr) minmax(180px, 230px);
        align-items: center;
        gap: 1rem;
    }

    .readbee-speed-figure {
        display: flex;
        align-items: center;
        justify-content: center;
        min-height: 220px;
    }

    .readbee-speed-figure img {
        width: min(100%, 220px);
        height: 220px;
        object-fit: contain;
    }

    .dark .readbee-chart-box .apexcharts-text,
    .dark .readbee-chart-box .apexcharts-title-text,
    .dark .readbee-chart-box .apexcharts-xaxis-label,
    .dark .readbee-chart-box .apexcharts-yaxis-label,
    .dark .readbee-chart-box .apexcharts-yaxis-title-text,
    .dark .readbee-chart-box .apexcharts-xaxis-title-text,
    .dark .readbee-chart-box .apexcharts-datalabel-label,
    .dark .readbee-chart-box .apexcharts-datalabel-value,
    .dark .readbee-chart-box .apexcharts-datalabel-total-label,
    .dark .readbee-chart-box .apexcharts-datalabel-total-value {
        fill: #e5e7eb !important;
        color: #e5e7eb !important;
    }

    .dark .readbee-chart-box .apexcharts-legend-text {
        color: #d1d5db !important;
    }


    .readbee-chart-box .apexcharts-pie-series path,
    .readbee-chart-box .apexcharts-radialbar-track path,
    .readbee-chart-box .apexcharts-radialbar-area path {
        stroke: #ffffff !important;
    }

    .dark .readbee-chart-box .apexcharts-gridline {
        stroke: rgba(255, 255, 255, .10) !important;
    }

    .dark .readbee-chart-box .apexcharts-pie-series path,
    .dark .readbee-chart-box .apexcharts-radialbar-track path,
    .dark .readbee-chart-box .apexcharts-radialbar-area path {
        stroke: #101828 !important;
    }

    .readbee-chart-box .apexcharts-nodata,
    .readbee-chart-box .apexcharts-nodata text,
    .readbee-chart-box .apexcharts-no-data-text {
        color: #475467 !important;
        fill: #475467 !important;
    }

    .dark .readbee-chart-box .apexcharts-nodata,
    .dark .readbee-chart-box .apexcharts-nodata text,
    .dark .readbee-chart-box .apexcharts-no-data-text {
        color: #d1d5db !important;
        fill: #d1d5db !important;
    }

    .readbee-principal-dashboard #principalReadingRateChart .apexcharts-series path[stroke],
    .readbee-principal-dashboard #principalReadingRateChart .apexcharts-line-series path,
    .readbee-principal-dashboard #principalReadingRateChart path.apexcharts-line,
    .readbee-principal-dashboard #principalComprehensionRateChart .apexcharts-series path[stroke],
    .readbee-principal-dashboard #principalComprehensionRateChart .apexcharts-line-series path,
    .readbee-principal-dashboard #principalComprehensionRateChart path.apexcharts-line {
        stroke: #1f2937 !important;
        opacity: 1 !important;
    }

    .dark .readbee-principal-dashboard #principalReadingRateChart .apexcharts-series path[stroke],
    .dark .readbee-principal-dashboard #principalReadingRateChart .apexcharts-line-series path,
    .dark .readbee-principal-dashboard #principalReadingRateChart path.apexcharts-line,
    .dark .readbee-principal-dashboard #principalComprehensionRateChart .apexcharts-series path[stroke],
    .dark .readbee-principal-dashboard #principalComprehensionRateChart .apexcharts-line-series path,
    .dark .readbee-principal-dashboard #principalComprehensionRateChart path.apexcharts-line {
        stroke: #f2c94c !important;
        opacity: 1 !important;
    }

    @media (max-width: 768px) {
        .readbee-speed-layout { grid-template-columns: 1fr; }
        .readbee-speed-figure { min-height: 165px; }
        .readbee-speed-figure img { height: 165px; }
    }
</style>

<script>
    window.principalDashboardPage = function(initialState, dashboardUrl) {
        const palette = {
            yellow: '#f2c94c',
            black: '#111827',
            charcoal: '#1f2937',
            slate: '#475467',
            gray: '#8a94a6',
            graySoft: '#e5e7eb',
            mutedBlue: '#8fa3b8',
            mutedTeal: '#8aa6a3',
            mutedRose: '#c9a7a7',
            mutedOlive: '#a9aa7d',
        };

        const chartColors = [
            palette.yellow,
            palette.mutedBlue,
            palette.gray,
            palette.mutedTeal,
            '#d8b26e',
            palette.mutedRose,
            palette.charcoal,
        ];

        return {
            dashboardUrl,
            loading: false,
            loadError: '',
            filterPanelOpen: false,
            filterPopoverStyle: '',
            filterRequestToken: 0,
            filterAbortController: null,
            filters: initialState.filters || {},
            options: initialState.options || {},
            filterCatalog: initialState.filterCatalog || {},
            activeLabels: initialState.activeLabels || {},
            summary: initialState.summary || {},
            cards: initialState.cards || [],
            chartData: initialState.chartData || {},
            attentionLists: initialState.attentionLists || { oral: [], comprehension: [] },
            updatedAt: initialState.updatedAt || '',
            chartFilters: {
                readingLevelSex: 'all',
                comprehensionLevelSex: 'all',
                readingRateSex: 'all',
                comprehensionRateSex: 'all',
                filipinoCompletionSex: 'all',
                englishCompletionSex: 'all',
                oralAttentionSex: 'all',
                comprehensionAttentionSex: 'all',
            },
            charts: {},
            chartResizeTimer: null,
            init() {
                this.ensureFilterSelections();
                this.onDashboardViewportChange = () => {
                    if (this.filterPanelOpen) {
                        this.positionFilterPopover();
                    }
                };
                window.addEventListener('resize', this.onDashboardViewportChange);
                window.addEventListener('scroll', this.onDashboardViewportChange, true);

                this.waitForCharts(() => {
                    this.renderAllCharts();
                    this.queueChartResize();
                });
            },
            destroy() {
                window.removeEventListener('resize', this.onDashboardViewportChange);
                window.removeEventListener('scroll', this.onDashboardViewportChange, true);
            },
            toggleFilterPanel() {
                this.filterPanelOpen = !this.filterPanelOpen;

                if (this.filterPanelOpen) {
                    this.$nextTick(() => this.positionFilterPopover());
                }
            },
            closeFilterPanel() {
                this.filterPanelOpen = false;
            },
            positionFilterPopover() {
                const button = this.$refs.filterButton;
                if (!button) return;

                const rect = button.getBoundingClientRect();
                const viewportWidth = window.innerWidth || document.documentElement.clientWidth;
                const viewportHeight = window.innerHeight || document.documentElement.clientHeight;
                const gap = 10;
                const sideMargin = 16;
                const popoverWidth = Math.min(544, Math.max(280, viewportWidth - (sideMargin * 2)));
                const left = Math.min(
                    Math.max(sideMargin, rect.right - popoverWidth),
                    viewportWidth - popoverWidth - sideMargin
                );
                const top = Math.min(rect.bottom + gap, viewportHeight - sideMargin);
                const maxHeight = Math.max(260, viewportHeight - top - sideMargin);

                this.filterPopoverStyle = `top: ${top}px; left: ${left}px; width: ${popoverWidth}px; max-height: ${maxHeight}px;`;
            },
            catalogList(key, fallbackKey = null) {
                const list = this.filterCatalog?.[key] || [];
                if (list.length) return list;

                return this.options?.[fallbackKey || key] || [];
            },
            activeAssignmentLinks() {
                return this.filterCatalog?.assignments || [];
            },
            availableQuarters() {
                const selectedYear = this.filters?.school_year_id;
                const ids = new Set(this.activeAssignmentLinks()
                    .filter((assignment) => !selectedYear || assignment.year_id === selectedYear)
                    .map((assignment) => assignment.quarter_id)
                    .filter(Boolean));

                return this.catalogList('quarters')
                    .filter((quarter) => ids.has(quarter.quarter_id));
            },
            availableGradeLevels() {
                const selectedYear = this.filters?.school_year_id;
                const selectedQuarter = this.filters?.quarter_id || 'all';
                const ids = new Set(this.activeAssignmentLinks()
                    .filter((assignment) => !selectedYear || assignment.year_id === selectedYear)
                    .filter((assignment) => selectedQuarter === 'all' || assignment.quarter_id === selectedQuarter)
                    .map((assignment) => assignment.grade_level_id)
                    .filter(Boolean));

                return this.catalogList('gradeLevels')
                    .filter((grade) => ids.has(grade.grade_level_id));
            },
            availableSections() {
                const selectedYear = this.filters?.school_year_id;
                const selectedQuarter = this.filters?.quarter_id || 'all';
                const selectedGrade = this.filters?.grade_level_id || 'all';
                const ids = new Set(this.activeAssignmentLinks()
                    .filter((assignment) => !selectedYear || assignment.year_id === selectedYear)
                    .filter((assignment) => selectedQuarter === 'all' || assignment.quarter_id === selectedQuarter)
                    .filter((assignment) => selectedGrade === 'all' || assignment.grade_level_id === selectedGrade)
                    .map((assignment) => assignment.section_id)
                    .filter(Boolean));

                return this.catalogList('sections')
                    .filter((section) => ids.has(section.section_id));
            },
            languageOptions() {
                return this.catalogList('languages').length
                    ? this.catalogList('languages')
                    : [
                        { value: 'all', label: 'All Languages' },
                        { value: 'english', label: 'English' },
                        { value: 'filipino', label: 'Filipino' },
                    ];
            },
            findOption(list, key, value) {
                return (list || []).find((item) => item?.[key] === value) || null;
            },
            selectedSchoolYearLabel() {
                return this.findOption(this.catalogList('schoolYears'), 'year_id', this.filters?.school_year_id)?.label || 'School Year';
            },
            selectedQuarterLabel() {
                if ((this.filters?.quarter_id || 'all') === 'all') return 'All Quarters';
                return this.findOption(this.availableQuarters(), 'quarter_id', this.filters?.quarter_id)?.label || 'Quarter';
            },
            selectedGradeLevelLabel() {
                if ((this.filters?.grade_level_id || 'all') === 'all') return 'All Grades';
                const grade = this.findOption(this.availableGradeLevels(), 'grade_level_id', this.filters?.grade_level_id);
                return grade ? `Grade ${grade.grade_number}` : 'Grade Level';
            },
            selectedSectionLabel() {
                if ((this.filters?.section_id || 'all') === 'all') return 'All Sections';
                return this.findOption(this.availableSections(), 'section_id', this.filters?.section_id)?.label || 'Section';
            },
            selectedLanguageLabel() {
                return this.findOption(this.languageOptions(), 'value', this.filters?.language || 'all')?.label || 'Language';
            },
            ensureFilterSelections() {
                const quarterIds = this.availableQuarters().map((quarter) => quarter.quarter_id);
                if ((this.filters.quarter_id || 'all') !== 'all' && !quarterIds.includes(this.filters.quarter_id)) {
                    this.filters.quarter_id = 'all';
                }

                const gradeIds = this.availableGradeLevels().map((grade) => grade.grade_level_id);
                if ((this.filters.grade_level_id || 'all') !== 'all' && !gradeIds.includes(this.filters.grade_level_id)) {
                    this.filters.grade_level_id = 'all';
                }

                const sectionIds = this.availableSections().map((section) => section.section_id);
                if ((this.filters.section_id || 'all') !== 'all' && !sectionIds.includes(this.filters.section_id)) {
                    this.filters.section_id = 'all';
                }
            },
            onSchoolYearChange() {
                this.filters.quarter_id = 'all';
                this.filters.grade_level_id = 'all';
                this.filters.section_id = 'all';
                this.applyFilters();
            },
            onQuarterChange() {
                this.filters.grade_level_id = 'all';
                this.filters.section_id = 'all';
                this.applyFilters();
            },
            onGradeLevelChange() {
                this.filters.section_id = 'all';
                this.applyFilters();
            },
            onSectionChange() {
                this.applyFilters();
            },
            onLanguageChange() {
                this.applyFilters();
            },
            async applyFilters() {
                this.ensureFilterSelections();
                this.loadError = '';
                const requestToken = ++this.filterRequestToken;

                if (this.filterAbortController) {
                    this.filterAbortController.abort();
                }

                this.filterAbortController = new AbortController();

                const params = new URLSearchParams({ ajax: '1' });
                Object.entries(this.filters || {}).forEach(([key, value]) => params.set(key, value ?? 'all'));

                try {
                    const response = await fetch(`${this.dashboardUrl}?${params.toString()}`, {
                        headers: { 'Accept': 'application/json' },
                        signal: this.filterAbortController.signal,
                    });
                    const payload = await response.json();

                    if (requestToken !== this.filterRequestToken) {
                        return;
                    }

                    if (!response.ok || !payload.success) {
                        throw new Error(payload.message || 'Unable to load dashboard data.');
                    }

                    const data = payload.dashboardData || {};
                    this.filters = data.filters || this.filters;
                    this.options = data.options || this.options;
                    this.filterCatalog = data.filterCatalog || this.filterCatalog;
                    this.activeLabels = data.activeLabels || {};
                    this.summary = data.summary || {};
                    this.cards = data.cards || [];
                    this.chartData = data.chartData || {};
                    this.attentionLists = data.attentionLists || { oral: [], comprehension: [] };
                    this.updatedAt = data.updatedAt || '';

                    this.$nextTick(() => {
                        this.updateDashboardCharts();
                    });
                } catch (error) {
                    if (error.name === 'AbortError') {
                        return;
                    }

                    if (requestToken === this.filterRequestToken) {
                        this.loadError = error.message || 'Unable to load dashboard data.';
                    }
                }
            },
            waitForCharts(callback) {
                if (window.ApexCharts) {
                    this.$nextTick(callback);
                    return;
                }

                setTimeout(() => this.waitForCharts(callback), 80);
            },
            filteredAttention(type) {
                const filterKey = type === 'oral' ? 'oralAttentionSex' : 'comprehensionAttentionSex';
                const selectedSex = this.chartFilters[filterKey];
                const source = this.attentionLists[type] || [];

                return source.filter((pupil) => selectedSex === 'all' || pupil.sex === selectedSex);
            },
            sexLabel(value) {
                if (value === 'male') return 'Male';
                if (value === 'female') return 'Female';
                return 'All Sex';
            },
            destroyChart(key) {
                if (this.charts[key]) {
                    try { this.charts[key].destroy(); } catch (error) { console.warn('Chart destroy skipped:', key, error); }
                    delete this.charts[key];
                }
            },
            prepareElement(elementId, key) {
                const element = document.getElementById(elementId);
                if (!element) return null;

                this.destroyChart(key);
                element.innerHTML = '';
                return element;
            },
            queueChartResize() {
                clearTimeout(this.chartResizeTimer);
                this.chartResizeTimer = setTimeout(() => {
                    Object.values(this.charts).forEach((chart) => {
                        try {
                            chart?.updateOptions?.({ chart: { width: '100%' } }, false, true);
                            chart?.windowResizeHandler?.();
                        } catch (error) {
                            console.warn('Chart resize skipped:', error);
                        }
                    });
                }, 90);
            },
            chartTextColor() {
                return document.documentElement.classList.contains('dark') ? '#f9fafb' : palette.black;
            },
            chartMutedTextColor() {
                return document.documentElement.classList.contains('dark') ? '#d1d5db' : palette.slate;
            },
            chartGridColor() {
                return document.documentElement.classList.contains('dark') ? 'rgba(255,255,255,.10)' : palette.graySoft;
            },
            chartBarColors() {
                return document.documentElement.classList.contains('dark')
                    ? [palette.yellow, '#9ca3af', '#6b7280']
                    : [palette.yellow, palette.mutedBlue, palette.gray];
            },
            chartLineColor() {
                return document.documentElement.classList.contains('dark') ? palette.yellow : palette.charcoal;
            },
            chartStrokeColor() {
                return document.documentElement.classList.contains('dark') ? '#101828' : '#ffffff';
            },
            chartStrokeWidth() {
                return document.documentElement.classList.contains('dark') ? 2 : 3;
            },
            chartMarkerFillColor() {
                return document.documentElement.classList.contains('dark') ? '#111827' : palette.yellow;
            },
            chartNoDataColor() {
                return document.documentElement.classList.contains('dark') ? '#d1d5db' : palette.slate;
            },
            noDataOptions() {
                return {
                    text: 'No data yet',
                    style: {
                        color: this.chartNoDataColor(),
                        fontSize: '13px',
                        fontFamily: 'Outfit, sans-serif',
                        fontWeight: 600,
                    },
                };
            },
            integerAxisMax(data) {
                const values = Array.isArray(data) ? data.map((value) => Number(value) || 0) : [0];
                const maxValue = Math.max(...values, 0);

                return Math.max(1, Math.ceil(maxValue));
            },
            integerTickAmount(data) {
                const maxValue = this.integerAxisMax(data);

                return Math.max(1, Math.min(maxValue, 5));
            },
            formatWholeNumber(value) {
                const number = Number(value);

                if (!Number.isFinite(number)) {
                    return '0';
                }

                return `${Math.round(number)}`;
            },
            hasChartData(data) {
                return Array.isArray(data) && data.some((value) => Number(value) > 0);
            },
            emptyPieSeries(data, labels) {
                if (this.hasChartData(data)) {
                    return { series: data, labels, colors: chartColors, isEmpty: false };
                }

                return {
                    series: [1],
                    labels: ['No data yet'],
                    colors: [document.documentElement.classList.contains('dark') ? '#374151' : '#e5e7eb'],
                    isEmpty: true,
                };
            },
            renderAllCharts() {
                this.renderBarChart('principalReadingLevelChart', 'readingLevel', this.chartData?.readingLevel?.[this.chartFilters.readingLevelSex] || [0, 0, 0], 'Reading Level');
                this.renderBarChart('principalComprehensionLevelChart', 'comprehensionLevel', this.chartData?.comprehensionLevel?.[this.chartFilters.comprehensionLevelSex] || [0, 0, 0], 'Comprehension Level');
                this.renderLineChart('principalReadingRateChart', 'readingRate', this.chartData?.readingRate?.[this.chartFilters.readingRateSex] || [0, 0, 0, 0], 'Reading Rate');
                this.renderLineChart('principalComprehensionRateChart', 'comprehensionRate', this.chartData?.comprehensionRate?.[this.chartFilters.comprehensionRateSex] || [0, 0, 0, 0], 'Comprehension Rate');
                this.renderDonutChart('principalMiscueTypeChart', 'miscueType', this.chartData?.miscueDistribution || [0, 0, 0, 0, 0, 0, 0], ['Mispronunciation', 'Omission', 'Substitution', 'Insertion', 'Transposition', 'Reversal', 'Repetition'], 'Miscues');
                this.renderProgressDonut('principalFilipinoCompletionChart', 'filipinoCompletion', this.chartData?.filipinoCompletion?.[this.chartFilters.filipinoCompletionSex] || [0, 100], 'Filipino');
                this.renderProgressDonut('principalEnglishCompletionChart', 'englishCompletion', this.chartData?.englishCompletion?.[this.chartFilters.englishCompletionSex] || [0, 100], 'English');
                this.renderPieChart('principalMaleSpeedChart', 'maleSpeed', this.chartData?.speedMale || [0, 0, 0, 0, 0], ['Fast', 'Average', 'Slow', 'Struggling', 'Non-Reader']);
                this.renderPieChart('principalFemaleSpeedChart', 'femaleSpeed', this.chartData?.speedFemale || [0, 0, 0, 0, 0], ['Fast', 'Average', 'Slow', 'Struggling', 'Non-Reader']);
                this.renderPieChart('principalComprehensionStatusChart', 'comprehensionStatus', this.chartData?.comprehensionStatus || [0, 0], ['With Comprehension', 'Without Comprehension']);
            },
            updateDashboardCharts() {
                if (!Object.keys(this.charts || {}).length) {
                    this.renderAllCharts();
                    return;
                }

                try {
                    this.refreshChart('readingLevel');
                    this.refreshChart('comprehensionLevel');
                    this.refreshChart('readingRate');
                    this.refreshChart('comprehensionRate');
                    this.refreshChart('filipinoCompletion');
                    this.refreshChart('englishCompletion');
                    this.updatePieLikeChart('miscueType', this.chartData?.miscueDistribution || [0, 0, 0, 0, 0, 0, 0], ['Mispronunciation', 'Omission', 'Substitution', 'Insertion', 'Transposition', 'Reversal', 'Repetition']);
                    this.updatePieLikeChart('maleSpeed', this.chartData?.speedMale || [0, 0, 0, 0, 0], ['Fast', 'Average', 'Slow', 'Struggling', 'Non-Reader']);
                    this.updatePieLikeChart('femaleSpeed', this.chartData?.speedFemale || [0, 0, 0, 0, 0], ['Fast', 'Average', 'Slow', 'Struggling', 'Non-Reader']);
                    this.updatePieLikeChart('comprehensionStatus', this.chartData?.comprehensionStatus || [0, 0], ['With Comprehension', 'Without Comprehension']);
                    this.queueChartResize();
                } catch (error) {
                    console.warn('Dashboard chart update skipped, re-rendering charts instead.', error);
                    this.renderAllCharts();
                }
            },
            updatePieLikeChart(key, data, labels) {
                if (!this.charts[key]) return;

                const display = this.emptyPieSeries(data, labels);
                const leftLegendKeys = ['maleSpeed', 'femaleSpeed'];
                const tooltipUnit = leftLegendKeys.includes(key) ? 'pupil(s)' : 'record(s)';

                this.charts[key].updateOptions({
                    labels: display.labels,
                    colors: display.colors,
                    stroke: { width: this.chartStrokeWidth(), colors: [this.chartStrokeColor()] },
                    noData: this.noDataOptions(),
                    tooltip: {
                        enabled: !display.isEmpty,
                        y: { formatter: (value) => `${value} ${tooltipUnit}` },
                    },
                }, false, false);
                this.charts[key].updateSeries(display.series);
            },
            refreshChart(key) {
                if (!this.charts[key]) return;

                const map = {
                    readingLevel: () => {
                        const data = this.chartData?.readingLevel?.[this.chartFilters.readingLevelSex] || [0, 0, 0];
                        this.charts.readingLevel.updateOptions({
                            yaxis: {
                                min: 0,
                                max: this.integerAxisMax(data),
                                tickAmount: this.integerTickAmount(data),
                                forceNiceScale: false,
                                title: { text: 'Number of Pupils', style: { color: this.chartMutedTextColor(), fontSize: '12px', fontWeight: 700 } },
                                labels: { formatter: (value) => this.formatWholeNumber(value), style: { colors: this.chartMutedTextColor() } },
                            },
                        }, false, false);
                        this.charts.readingLevel.updateSeries([{ name: this.sexLabel(this.chartFilters.readingLevelSex), data }]);
                    },
                    comprehensionLevel: () => {
                        const data = this.chartData?.comprehensionLevel?.[this.chartFilters.comprehensionLevelSex] || [0, 0, 0];
                        this.charts.comprehensionLevel.updateOptions({
                            yaxis: {
                                min: 0,
                                max: this.integerAxisMax(data),
                                tickAmount: this.integerTickAmount(data),
                                forceNiceScale: false,
                                title: { text: 'Number of Pupils', style: { color: this.chartMutedTextColor(), fontSize: '12px', fontWeight: 700 } },
                                labels: { formatter: (value) => this.formatWholeNumber(value), style: { colors: this.chartMutedTextColor() } },
                            },
                        }, false, false);
                        this.charts.comprehensionLevel.updateSeries([{ name: this.sexLabel(this.chartFilters.comprehensionLevelSex), data }]);
                    },
                    readingRate: () => this.charts.readingRate.updateSeries([{ name: this.sexLabel(this.chartFilters.readingRateSex), data: this.chartData?.readingRate?.[this.chartFilters.readingRateSex] || [0, 0, 0, 0] }]),
                    comprehensionRate: () => this.charts.comprehensionRate.updateSeries([{ name: this.sexLabel(this.chartFilters.comprehensionRateSex), data: this.chartData?.comprehensionRate?.[this.chartFilters.comprehensionRateSex] || [0, 0, 0, 0] }]),
                    filipinoCompletion: () => this.charts.filipinoCompletion.updateSeries(this.chartData?.filipinoCompletion?.[this.chartFilters.filipinoCompletionSex] || [0, 100]),
                    englishCompletion: () => this.charts.englishCompletion.updateSeries(this.chartData?.englishCompletion?.[this.chartFilters.englishCompletionSex] || [0, 100]),
                };

                if (map[key]) {
                    map[key]();
                    this.queueChartResize();
                }
            },
            chartBase() {
                return {
                    fontFamily: 'Outfit, sans-serif',
                    toolbar: { show: false },
                    foreColor: this.chartMutedTextColor(),
                    animations: { enabled: true, easing: 'easeinout', speed: 450 },
                    redrawOnParentResize: true,
                    redrawOnWindowResize: true,
                };
            },
            renderBarChart(elementId, key, data, title) {
                const element = this.prepareElement(elementId, key);
                if (!element) return;

                const levelAxisTitle = title === 'Comprehension Level' ? 'Comprehension Levels' : 'Reading Levels';

                const options = {
                    series: [{ name: this.sexLabel(this.chartFilters[key + 'Sex']), data }],
                    colors: this.chartBarColors(),
                    chart: { ...this.chartBase(), type: 'bar', height: 285, width: '100%' },
                    plotOptions: { bar: { borderRadius: 7, columnWidth: '44%', distributed: true } },
                    dataLabels: { enabled: false },
                    legend: { show: false },
                    xaxis: {
                        categories: ['Independent', 'Instructional', 'Frustration'],
                        title: { text: levelAxisTitle, style: { color: this.chartMutedTextColor(), fontSize: '12px', fontWeight: 700 } },
                        axisBorder: { show: false },
                        axisTicks: { show: false },
                        labels: { rotate: 0, trim: true, style: { fontSize: '12px', colors: [this.chartMutedTextColor(), this.chartMutedTextColor(), this.chartMutedTextColor()] } },
                    },
                    yaxis: {
                        min: 0,
                        max: this.integerAxisMax(data),
                        tickAmount: this.integerTickAmount(data),
                        forceNiceScale: false,
                        title: { text: 'Number of Pupils', style: { color: this.chartMutedTextColor(), fontSize: '12px', fontWeight: 700 } },
                        labels: { formatter: (value) => this.formatWholeNumber(value), style: { colors: this.chartMutedTextColor() } },
                    },
                    grid: { borderColor: this.chartGridColor(), strokeDashArray: 4 },
                    tooltip: { y: { formatter: (value) => `${this.formatWholeNumber(value)} pupil(s)` } },
                    title: { text: title, style: { fontSize: '0px' } },
                    noData: this.noDataOptions(),
                };

                this.charts[key] = new ApexCharts(element, options);
                this.charts[key].render().then(() => this.queueChartResize());
            },
            renderLineChart(elementId, key, data, title) {
                const element = this.prepareElement(elementId, key);
                if (!element) return;

                const options = {
                    series: [{ name: this.sexLabel(this.chartFilters[key + 'Sex']), data }],
                    colors: [this.chartLineColor()],
                    chart: { ...this.chartBase(), type: 'line', height: 285, width: '100%', zoom: { enabled: false } },
                    stroke: { curve: 'smooth', width: 4 },
                    markers: { size: 5, colors: [this.chartMarkerFillColor()], strokeColors: this.chartLineColor(), strokeWidth: 3 },
                    dataLabels: { enabled: false },
                    xaxis: {
                        categories: ['First', 'Second', 'Third', 'Fourth'],
                        title: { text: 'Quarter', style: { color: this.chartMutedTextColor(), fontSize: '12px', fontWeight: 700 } },
                        axisBorder: { show: false },
                        axisTicks: { show: false },
                        labels: { style: { fontSize: '12px', colors: [this.chartMutedTextColor(), this.chartMutedTextColor(), this.chartMutedTextColor(), this.chartMutedTextColor()] } },
                    },
                    yaxis: {
                        min: 0,
                        max: 100,
                        tickAmount: 5,
                        title: { text: 'Rate %', style: { color: this.chartMutedTextColor(), fontSize: '12px', fontWeight: 700 } },
                        labels: { formatter: (value) => `${Math.round(value)}%`, style: { colors: this.chartMutedTextColor() } },
                    },
                    grid: { borderColor: this.chartGridColor(), strokeDashArray: 4 },
                    tooltip: { y: { formatter: (value) => `${value}%` }, x: { formatter: (value) => `${value} Quarter` } },
                    title: { text: title, style: { fontSize: '0px' } },
                    noData: this.noDataOptions(),
                };

                this.charts[key] = new ApexCharts(element, options);
                this.charts[key].render().then(() => this.queueChartResize());
            },
            renderDonutChart(elementId, key, data, labels, title) {
                const element = this.prepareElement(elementId, key);
                if (!element) return;

                const display = this.emptyPieSeries(data, labels);
                const options = {
                    series: display.series,
                    labels: display.labels,
                    colors: display.colors,
                    chart: { ...this.chartBase(), type: 'donut', height: 285, width: '100%' },
                    legend: {
                        position: 'left',
                        horizontalAlign: 'center',
                        floating: false,
                        fontSize: '12px',
                        labels: { colors: this.chartMutedTextColor() },
                        itemMargin: { horizontal: 0, vertical: 5 },
                        offsetX: 0,
                        offsetY: 0,
                    },
                    stroke: { width: this.chartStrokeWidth(), colors: [this.chartStrokeColor()] },
                    dataLabels: { enabled: false },
                    plotOptions: { pie: { customScale: 0.76, donut: { size: '60%', labels: { show: true, name: { fontSize: '12px', color: this.chartMutedTextColor() }, value: { fontSize: '22px', fontWeight: 800, color: this.chartTextColor() }, total: { show: true, label: title, color: this.chartMutedTextColor(), formatter: () => display.isEmpty ? '0' : data.reduce((sum, value) => sum + value, 0) } } } } },
                    noData: this.noDataOptions(),
                };

                this.charts[key] = new ApexCharts(element, options);
                this.charts[key].render().then(() => this.queueChartResize());
            },
            renderProgressDonut(elementId, key, data, label) {
                const element = this.prepareElement(elementId, key);
                if (!element) return;

                const options = {
                    series: data,
                    labels: ['Assessed', 'Not Yet Assessed'],
                    colors: [palette.yellow, document.documentElement.classList.contains('dark') ? '#374151' : '#e5e7eb'],
                    chart: { ...this.chartBase(), type: 'donut', height: 245, width: '100%' },
                    legend: { position: 'bottom', fontSize: '12px', labels: { colors: this.chartMutedTextColor() } },
                    stroke: { width: this.chartStrokeWidth(), colors: [this.chartStrokeColor()] },
                    dataLabels: { enabled: false },
                    plotOptions: { pie: { donut: { size: '74%', labels: { show: true, name: { color: this.chartMutedTextColor() }, value: { formatter: (value) => `${value}%`, color: this.chartTextColor(), fontWeight: 800 }, total: { show: true, showAlways: true, label, color: this.chartMutedTextColor(), formatter: (w) => `${w.globals.series[0]}%` } } } } },
                    noData: this.noDataOptions(),
                };

                this.charts[key] = new ApexCharts(element, options);
                this.charts[key].render().then(() => this.queueChartResize());
            },
            renderPieChart(elementId, key, data, labels) {
                const element = this.prepareElement(elementId, key);
                if (!element) return;

                const display = this.emptyPieSeries(data, labels);
                const leftLegendKeys = ['maleSpeed', 'femaleSpeed'];
                const legendPosition = leftLegendKeys.includes(key) ? 'left' : 'bottom';
                const legendItemMargin = leftLegendKeys.includes(key)
                    ? { horizontal: 0, vertical: 4 }
                    : { horizontal: 5, vertical: 2 };
                const tooltipUnit = leftLegendKeys.includes(key) ? 'pupil(s)' : 'record(s)';

                const options = {
                    series: display.series,
                    labels: display.labels,
                    colors: display.colors,
                    chart: { ...this.chartBase(), type: 'pie', height: 245, width: '100%' },
                    legend: { position: legendPosition, horizontalAlign: leftLegendKeys.includes(key) ? 'left' : 'center', fontSize: '11px', labels: { colors: this.chartMutedTextColor() }, itemMargin: legendItemMargin },
                    stroke: { width: this.chartStrokeWidth(), colors: [this.chartStrokeColor()] },
                    dataLabels: { enabled: false },
                    tooltip: { enabled: !display.isEmpty, y: { formatter: (value) => `${value} ${tooltipUnit}` } },
                    noData: this.noDataOptions(),
                };

                this.charts[key] = new ApexCharts(element, options);
                this.charts[key].render().then(() => this.queueChartResize());
            },
        };
    };
</script>

<div
    x-data="principalDashboardPage(@js($dashboardData), @js($dashboardUrl))"
    x-cloak
    class="readbee-principal-dashboard space-y-6"
>
    <section class="readbee-dashboard-hero p-5 shadow-theme-md sm:p-6 xl:p-7">
        <div class="relative z-10 flex flex-col gap-5 lg:flex-row lg:items-center lg:justify-between">
            <div class="min-w-0">
                <div class="flex items-center gap-3">
                    <div class="flex h-14 w-14 shrink-0 items-center justify-center rounded-2xl bg-gray-50 ring-1 ring-gray-200 dark:bg-white/[0.04] dark:ring-white/10 sm:h-16 sm:w-16">
                        <img src="{{ asset('landing-assets/images/CuteBee3.png') }}" alt="ReadBee dashboard" class="h-11 w-11 object-contain sm:h-12 sm:w-12">
                    </div>
                    <div class="min-w-0">
                        <p class="text-xs font-semibold uppercase tracking-[0.24em] text-gray-500 dark:text-gray-400">Principal Dashboard</p>
                        <h1 class="mt-1 text-2xl font-semibold leading-tight text-gray-950 dark:text-white sm:text-3xl">School Reading Performance Overview</h1>
                    </div>
                </div>
                <p class="mt-4 max-w-3xl text-sm leading-6 text-gray-600 dark:text-gray-300">
                    Monitor reading speed, reading level, comprehension, assessment completion, miscues, and pupils needing support across your school.
                </p>
                <div class="mt-4 flex flex-wrap gap-2 text-xs font-medium">
                    <span class="readbee-dashboard-pill rounded-full px-3 py-1" x-text="selectedSchoolYearLabel()"></span>
                    <span class="readbee-dashboard-pill rounded-full px-3 py-1" x-text="selectedQuarterLabel()"></span>
                    <span class="readbee-dashboard-pill rounded-full px-3 py-1" x-text="selectedGradeLevelLabel()"></span>
                    <span class="readbee-dashboard-pill rounded-full px-3 py-1" x-text="selectedSectionLabel()"></span>
                    <span class="readbee-dashboard-pill rounded-full px-3 py-1" x-text="selectedLanguageLabel()"></span>
                </div>
                <p class="mt-3 text-xs text-gray-500 dark:text-gray-400">Last updated: <span x-text="updatedAt || '—'"></span></p>
            </div>

            <div class="relative z-30 flex w-full flex-col gap-3 lg:w-[34rem]">
                <div class="flex justify-end">
                    <button type="button"
                        x-ref="filterButton"
                        @click.stop="toggleFilterPanel()"
                        class="inline-flex h-11 items-center gap-2 rounded-xl border border-gray-200 bg-white px-4 text-sm font-semibold text-gray-700 shadow-theme-xs transition hover:bg-gray-50 focus:outline-hidden focus:ring-3 focus:ring-amber-500/10 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-200 dark:hover:bg-white/[0.06]"
                        :aria-expanded="filterPanelOpen.toString()">
                        <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                            <path d="M4 6h16M7 12h10M10 18h4" stroke="currentColor" stroke-width="1.9" stroke-linecap="round"/>
                        </svg>
                        <span>Filters</span>
                    </button>
                </div>

                <template x-teleport="body">
                    <div x-cloak x-show="filterPanelOpen" class="readbee-filter-popover-overlay" @click="closeFilterPanel()"></div>
                </template>

                <template x-teleport="body">
                    <div x-cloak x-show="filterPanelOpen" x-transition.opacity.scale.95.origin.top.right
                        @click.outside="closeFilterPanel()"
                        @keydown.escape.window="closeFilterPanel()"
                        :style="filterPopoverStyle"
                        class="readbee-filter-popover rounded-2xl border border-gray-200 bg-white p-4 shadow-xl dark:border-gray-700 dark:bg-gray-950 sm:p-5">
                    <div class="mb-4 flex items-start justify-between gap-3">
                        <div>
                            <h2 class="text-base font-semibold text-gray-950 dark:text-white">Dashboard Filters</h2>
                            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Options are limited to your school data.</p>
                        </div>
                        <button type="button" @click="closeFilterPanel()" class="rounded-lg px-3 py-1.5 text-sm font-semibold text-gray-600 hover:bg-gray-100 dark:text-gray-300 dark:hover:bg-white/10">Close</button>
                    </div>

                    <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                        <label class="block">
                            <span class="mb-1 block text-xs font-semibold text-gray-600 dark:text-gray-300">School Year</span>
                            <select x-model="filters.school_year_id" @change="onSchoolYearChange()" class="h-11 w-full rounded-xl border border-gray-300 bg-white px-3 text-sm text-gray-700 shadow-theme-xs focus:border-[#d6b13f] focus:outline-hidden focus:ring-3 focus:ring-amber-500/10 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-200">
                                <template x-for="year in catalogList('schoolYears')" :key="year.year_id">
                                    <option :value="year.year_id" x-text="year.label"></option>
                                </template>
                            </select>
                        </label>

                        <label class="block">
                            <span class="mb-1 block text-xs font-semibold text-gray-600 dark:text-gray-300">Quarter</span>
                            <select x-model="filters.quarter_id" @change="onQuarterChange()" class="h-11 w-full rounded-xl border border-gray-300 bg-white px-3 text-sm text-gray-700 shadow-theme-xs focus:border-[#d6b13f] focus:outline-hidden focus:ring-3 focus:ring-amber-500/10 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-200">
                                <option value="all">All Quarters</option>
                                <template x-for="quarter in availableQuarters()" :key="quarter.quarter_id">
                                    <option :value="quarter.quarter_id" x-text="quarter.label"></option>
                                </template>
                            </select>
                        </label>

                        <label class="block">
                            <span class="mb-1 block text-xs font-semibold text-gray-600 dark:text-gray-300">Grade Level</span>
                            <select x-model="filters.grade_level_id" @change="onGradeLevelChange()" class="h-11 w-full rounded-xl border border-gray-300 bg-white px-3 text-sm text-gray-700 shadow-theme-xs focus:border-[#d6b13f] focus:outline-hidden focus:ring-3 focus:ring-amber-500/10 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-200">
                                <option value="all">All Grades</option>
                                <template x-for="grade in availableGradeLevels()" :key="grade.grade_level_id">
                                    <option :value="grade.grade_level_id" x-text="`Grade ${grade.grade_number}`"></option>
                                </template>
                            </select>
                        </label>

                        <label class="block">
                            <span class="mb-1 block text-xs font-semibold text-gray-600 dark:text-gray-300">Section</span>
                            <select x-model="filters.section_id" @change="onSectionChange()" class="h-11 w-full rounded-xl border border-gray-300 bg-white px-3 text-sm text-gray-700 shadow-theme-xs focus:border-[#d6b13f] focus:outline-hidden focus:ring-3 focus:ring-amber-500/10 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-200">
                                <option value="all">All Sections</option>
                                <template x-for="section in availableSections()" :key="section.section_id">
                                    <option :value="section.section_id" x-text="section.label"></option>
                                </template>
                            </select>
                        </label>

                        <label class="block sm:col-span-2">
                            <span class="mb-1 block text-xs font-semibold text-gray-600 dark:text-gray-300">Language</span>
                            <select x-model="filters.language" @change="onLanguageChange()" class="h-11 w-full rounded-xl border border-gray-300 bg-white px-3 text-sm text-gray-700 shadow-theme-xs focus:border-[#d6b13f] focus:outline-hidden focus:ring-3 focus:ring-amber-500/10 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-200">
                                <template x-for="language in languageOptions()" :key="language.value">
                                    <option :value="language.value" x-text="language.label"></option>
                                </template>
                            </select>
                        </label>
                    </div>
                    </div>
                </template>

                <div class="grid w-full grid-cols-1 gap-2 min-[420px]:grid-cols-3 sm:gap-3">
                    <div class="readbee-dashboard-mini-card rounded-2xl p-3 text-center shadow-theme-xs">
                        <p class="truncate text-[11px] font-medium text-gray-500 dark:text-gray-300">Total Pupils</p>
                        <p class="mt-1 text-xl font-semibold text-gray-950 dark:text-white sm:text-2xl" x-text="summary.total_pupils || 0"></p>
                    </div>
                    <div class="readbee-dashboard-mini-card is-accent rounded-2xl p-3 text-center shadow-theme-xs">
                        <p class="truncate text-[11px] font-semibold text-gray-950 dark:text-white">Assessed</p>
                        <p class="mt-1 text-xl font-semibold text-gray-950 dark:text-white sm:text-2xl" x-text="summary.assessed_count || 0"></p>
                    </div>
                    <div class="readbee-dashboard-mini-card rounded-2xl p-3 text-center shadow-theme-xs">
                        <p class="truncate text-[11px] font-medium text-gray-500 dark:text-gray-300">Need Support</p>
                        <p class="mt-1 text-xl font-semibold text-gray-950 dark:text-white sm:text-2xl" x-text="summary.need_support_count || 0"></p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <div x-show="loadError" x-cloak class="rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700" x-text="loadError"></div>

    <section class="grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-5 xl:gap-4">
        <template x-for="card in cards" :key="card.label">
            <div class="readbee-stat-card rounded-2xl border border-gray-200 bg-white p-4 shadow-theme-xs dark:border-gray-800 dark:bg-white/[0.03] lg:p-3 xl:p-4">
                <div class="flex items-start justify-between gap-3">
                    <div class="min-w-0 flex-1">
                        <p class="readbee-stat-title truncate text-sm font-semibold text-gray-600 dark:text-gray-300" x-text="card.label"></p>
                        <div class="mt-2 flex flex-wrap items-baseline gap-x-2 gap-y-1">
                            <p class="readbee-stat-value text-2xl font-semibold leading-none text-gray-950 dark:text-white xl:text-3xl" x-text="card.value"></p>
                            <span class="readbee-stat-percent rounded-full bg-gray-100 px-2 py-0.5 text-xs font-semibold text-gray-700 dark:bg-white/10 dark:text-gray-200" x-text="card.percent"></span>
                        </div>
                    </div>
                    <div class="readbee-stat-icon flex h-10 w-10 shrink-0 items-center justify-center rounded-2xl xl:h-11 xl:w-11">
                        <template x-if="card.icon === 'speed'"><svg class="h-5 w-5" viewBox="0 0 24 24" fill="none"><path d="M12 19a7 7 0 1 0-7-7" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/><path d="M12 12l4-4" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/><path d="M4 19h16" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/></svg></template>
                        <template x-if="card.icon === 'book'"><svg class="h-5 w-5" viewBox="0 0 24 24" fill="none"><path d="M5 5.75A2.75 2.75 0 0 1 7.75 3H19v15.25A2.75 2.75 0 0 1 16.25 21H7.5A2.5 2.5 0 0 1 5 18.5V5.75Z" stroke="currentColor" stroke-width="1.8"/><path d="M8 8h7M8 11h6" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/></svg></template>
                        <template x-if="card.icon === 'time'"><svg class="h-5 w-5" viewBox="0 0 24 24" fill="none"><path d="M12 21a9 9 0 1 0 0-18a9 9 0 0 0 0 18Z" stroke="currentColor" stroke-width="1.8"/><path d="M12 7v5l3 2" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/></svg></template>
                        <template x-if="card.icon === 'alert'"><svg class="h-5 w-5" viewBox="0 0 24 24" fill="none"><path d="M12 8v5" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/><path d="M12 17h.01" stroke="currentColor" stroke-width="2.4" stroke-linecap="round"/><path d="M10.3 4.2 2.9 17a2 2 0 0 0 1.7 3h14.8a2 2 0 0 0 1.7-3L13.7 4.2a2 2 0 0 0-3.4 0Z" stroke="currentColor" stroke-width="1.8"/></svg></template>
                        <template x-if="card.icon === 'target'"><svg class="h-5 w-5" viewBox="0 0 24 24" fill="none"><path d="M12 21a9 9 0 1 0 0-18a9 9 0 0 0 0 18Z" stroke="currentColor" stroke-width="1.8"/><path d="M12 17a5 5 0 1 0 0-10a5 5 0 0 0 0 10Z" stroke="currentColor" stroke-width="1.8"/><path d="M12 13.5a1.5 1.5 0 1 0 0-3a1.5 1.5 0 0 0 0 3Z" fill="currentColor"/></svg></template>
                    </div>
                </div>
                <p class="readbee-stat-helper mt-3 line-clamp-2 min-h-[2.5rem] text-xs leading-5 text-gray-500 dark:text-gray-400 xl:mt-4" x-text="card.helper"></p>
            </div>
        </template>
    </section>

    <section class="grid grid-cols-1 gap-5 lg:grid-cols-2">
        <div class="readbee-chart-box rounded-2xl border border-gray-200 bg-white p-4 shadow-theme-xs dark:border-gray-800 dark:bg-white/[0.03] sm:p-5">
            <div class="mb-4 flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                <div><h2 class="text-base font-semibold text-gray-950 dark:text-white sm:text-lg">Reading Level</h2><p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Independent, instructional, and frustration levels.</p></div>
                <select x-model="chartFilters.readingLevelSex" @change="refreshChart('readingLevel')" class="h-10 w-full rounded-lg border border-gray-300 bg-white px-3 text-sm text-gray-700 shadow-theme-xs dark:border-gray-700 dark:bg-gray-900 dark:text-gray-200 sm:w-36"><option value="all">All Sex</option><option value="male">Male</option><option value="female">Female</option></select>
            </div>
            <div id="principalReadingLevelChart" class="readbee-chart-host"></div>
        </div>

        <div class="readbee-chart-box rounded-2xl border border-gray-200 bg-white p-4 shadow-theme-xs dark:border-gray-800 dark:bg-white/[0.03] sm:p-5">
            <div class="mb-4 flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                <div><h2 class="text-base font-semibold text-gray-950 dark:text-white sm:text-lg">Comprehension Level</h2><p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Comprehension assessment level results.</p></div>
                <select x-model="chartFilters.comprehensionLevelSex" @change="refreshChart('comprehensionLevel')" class="h-10 w-full rounded-lg border border-gray-300 bg-white px-3 text-sm text-gray-700 shadow-theme-xs dark:border-gray-700 dark:bg-gray-900 dark:text-gray-200 sm:w-36"><option value="all">All Sex</option><option value="male">Male</option><option value="female">Female</option></select>
            </div>
            <div id="principalComprehensionLevelChart" class="readbee-chart-host"></div>
        </div>
    </section>

    <section class="grid grid-cols-1 gap-5 lg:grid-cols-2">
        <div class="readbee-chart-box rounded-2xl border border-gray-200 bg-white p-4 shadow-theme-xs dark:border-gray-800 dark:bg-white/[0.03] sm:p-5">
            <div class="mb-4 flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                <div><h2 class="text-base font-semibold text-gray-950 dark:text-white sm:text-lg">Reading Rate</h2><p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Percentage of pupils reading at average or fast speed.</p></div>
                <select x-model="chartFilters.readingRateSex" @change="refreshChart('readingRate')" class="h-10 w-full rounded-lg border border-gray-300 bg-white px-3 text-sm text-gray-700 shadow-theme-xs dark:border-gray-700 dark:bg-gray-900 dark:text-gray-200 sm:w-36"><option value="all">All Sex</option><option value="male">Male</option><option value="female">Female</option></select>
            </div>
            <div id="principalReadingRateChart" class="readbee-chart-host"></div>
        </div>

        <div class="readbee-chart-box rounded-2xl border border-gray-200 bg-white p-4 shadow-theme-xs dark:border-gray-800 dark:bg-white/[0.03] sm:p-5">
            <div class="mb-4 flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                <div><h2 class="text-base font-semibold text-gray-950 dark:text-white sm:text-lg">Comprehension Rate</h2><p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Percentage of pupils at instructional or independent comprehension.</p></div>
                <select x-model="chartFilters.comprehensionRateSex" @change="refreshChart('comprehensionRate')" class="h-10 w-full rounded-lg border border-gray-300 bg-white px-3 text-sm text-gray-700 shadow-theme-xs dark:border-gray-700 dark:bg-gray-900 dark:text-gray-200 sm:w-36"><option value="all">All Sex</option><option value="male">Male</option><option value="female">Female</option></select>
            </div>
            <div id="principalComprehensionRateChart" class="readbee-chart-host"></div>
        </div>
    </section>

    <section class="grid grid-cols-1 gap-5 lg:grid-cols-2">
        <div class="readbee-chart-box rounded-2xl border border-gray-200 bg-white p-4 shadow-theme-xs dark:border-gray-800 dark:bg-white/[0.03] sm:p-5">
            <h2 class="text-base font-semibold text-gray-950 dark:text-white sm:text-lg">Miscue Type Distribution</h2>
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Summary of miscues found in school assessment records.</p>
            <div id="principalMiscueTypeChart" class="readbee-chart-host mt-2"></div>
        </div>
        <div class="readbee-chart-box rounded-2xl border border-gray-200 bg-white p-4 shadow-theme-xs dark:border-gray-800 dark:bg-white/[0.03] sm:p-5">
            <h2 class="text-base font-semibold text-gray-950 dark:text-white sm:text-lg">Comprehension Status</h2>
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Records with and without comprehension scores.</p>
            <div id="principalComprehensionStatusChart" class="readbee-chart-host mt-2"></div>
        </div>
    </section>

    <section class="readbee-chart-box rounded-2xl border border-gray-200 bg-white p-4 shadow-theme-xs dark:border-gray-800 dark:bg-white/[0.03] sm:p-5">
        <div class="mb-4 min-w-0">
            <h2 class="text-base font-semibold text-gray-950 dark:text-white sm:text-lg">Assessment Completion Rate</h2>
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Completion progress for Filipino and English assessments in your school.</p>
        </div>
        <div class="grid grid-cols-1 gap-4 lg:grid-cols-2">
            <div class="rounded-2xl border border-gray-200 bg-gray-50 p-4 dark:border-gray-800 dark:bg-white/[0.03]">
                <div class="mb-3 flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                    <h3 class="font-semibold text-gray-950 dark:text-white">Filipino Completion</h3>
                    <select x-model="chartFilters.filipinoCompletionSex" @change="refreshChart('filipinoCompletion')" class="h-9 w-full rounded-lg border border-gray-300 bg-white px-3 text-xs text-gray-700 shadow-theme-xs dark:border-gray-700 dark:bg-gray-900 dark:text-gray-200 sm:w-32"><option value="all">All Sex</option><option value="male">Male</option><option value="female">Female</option></select>
                </div>
                <div id="principalFilipinoCompletionChart" class="readbee-chart-host small-chart"></div>
            </div>
            <div class="rounded-2xl border border-gray-200 bg-gray-50 p-4 dark:border-gray-800 dark:bg-white/[0.03]">
                <div class="mb-3 flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                    <h3 class="font-semibold text-gray-950 dark:text-white">English Completion</h3>
                    <select x-model="chartFilters.englishCompletionSex" @change="refreshChart('englishCompletion')" class="h-9 w-full rounded-lg border border-gray-300 bg-white px-3 text-xs text-gray-700 shadow-theme-xs dark:border-gray-700 dark:bg-gray-900 dark:text-gray-200 sm:w-32"><option value="all">All Sex</option><option value="male">Male</option><option value="female">Female</option></select>
                </div>
                <div id="principalEnglishCompletionChart" class="readbee-chart-host small-chart"></div>
            </div>
        </div>
    </section>

    <section class="grid grid-cols-1 gap-5 lg:grid-cols-2">
        <div class="readbee-chart-box rounded-2xl border border-gray-200 bg-white p-4 shadow-theme-xs dark:border-gray-800 dark:bg-white/[0.03] sm:p-5">
            <h2 class="text-base font-semibold text-gray-950 dark:text-white sm:text-lg">Reading Speed Distribution</h2>
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Male pupils by reading speed.</p>
            <div class="readbee-speed-layout mt-3">
                <div id="principalMaleSpeedChart" class="readbee-chart-host small-chart"></div>
                <div class="readbee-speed-figure"><img src="{{ asset('landing-assets/images/maleChild.png') }}" alt="Male pupil reading"></div>
            </div>
        </div>
        <div class="readbee-chart-box rounded-2xl border border-gray-200 bg-white p-4 shadow-theme-xs dark:border-gray-800 dark:bg-white/[0.03] sm:p-5">
            <h2 class="text-base font-semibold text-gray-950 dark:text-white sm:text-lg">Reading Speed Distribution</h2>
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Female pupils by reading speed.</p>
            <div class="readbee-speed-layout mt-3">
                <div id="principalFemaleSpeedChart" class="readbee-chart-host small-chart"></div>
                <div class="readbee-speed-figure"><img src="{{ asset('landing-assets/images/femaleChild.png') }}" alt="Female pupil reading"></div>
            </div>
        </div>
    </section>

    <section class="grid grid-cols-1 gap-5 lg:grid-cols-2">
        <div class="rounded-2xl border border-gray-200 bg-white p-4 shadow-theme-xs dark:border-gray-800 dark:bg-white/[0.03] sm:p-5">
            <div class="mb-5 flex flex-col gap-3 lg:flex-row lg:items-start lg:justify-between">
                <div><h2 class="text-base font-semibold text-gray-950 dark:text-white sm:text-lg">Pupils Needing Attention in Oral Reading</h2><p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Priority pupils for reading fluency support.</p></div>
                <select x-model="chartFilters.oralAttentionSex" class="h-10 rounded-lg border border-gray-300 bg-white px-3 text-sm text-gray-700 shadow-theme-xs dark:border-gray-700 dark:bg-gray-900 dark:text-gray-200"><option value="all">All Sex</option><option value="male">Male</option><option value="female">Female</option></select>
            </div>
            <div class="overflow-hidden rounded-xl border border-gray-200 dark:border-gray-800">
                <template x-for="pupil in filteredAttention('oral')" :key="pupil.name">
                    <div class="flex flex-col gap-2 border-b border-gray-100 px-4 py-3 last:border-b-0 dark:border-gray-800 sm:flex-row sm:items-center sm:justify-between">
                        <div><p class="font-medium text-gray-950 dark:text-white" x-text="pupil.name"></p><p class="text-xs text-gray-500 dark:text-gray-400" x-text="pupil.grade"></p></div>
                        <span class="w-fit rounded-full bg-gray-100 px-3 py-1 text-xs font-semibold text-gray-700 dark:bg-white/10 dark:text-gray-300" x-text="pupil.level"></span>
                    </div>
                </template>
                <div x-show="filteredAttention('oral').length === 0" class="px-4 py-8 text-center text-sm text-gray-500 dark:text-gray-400">No pupils found for this filter.</div>
            </div>
        </div>

        <div class="rounded-2xl border border-gray-200 bg-white p-4 shadow-theme-xs dark:border-gray-800 dark:bg-white/[0.03] sm:p-5">
            <div class="mb-5 flex flex-col gap-3 lg:flex-row lg:items-start lg:justify-between">
                <div><h2 class="text-base font-semibold text-gray-950 dark:text-white sm:text-lg">Pupils Needing Attention in Comprehension</h2><p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Priority pupils for comprehension intervention.</p></div>
                <select x-model="chartFilters.comprehensionAttentionSex" class="h-10 rounded-lg border border-gray-300 bg-white px-3 text-sm text-gray-700 shadow-theme-xs dark:border-gray-700 dark:bg-gray-900 dark:text-gray-200"><option value="all">All Sex</option><option value="male">Male</option><option value="female">Female</option></select>
            </div>
            <div class="overflow-hidden rounded-xl border border-gray-200 dark:border-gray-800">
                <template x-for="pupil in filteredAttention('comprehension')" :key="pupil.name">
                    <div class="flex flex-col gap-2 border-b border-gray-100 px-4 py-3 last:border-b-0 dark:border-gray-800 sm:flex-row sm:items-center sm:justify-between">
                        <div><p class="font-medium text-gray-950 dark:text-white" x-text="pupil.name"></p><p class="text-xs text-gray-500 dark:text-gray-400" x-text="pupil.grade"></p></div>
                        <span class="w-fit rounded-full bg-gray-100 px-3 py-1 text-xs font-semibold text-gray-700 dark:bg-white/10 dark:text-gray-300" x-text="pupil.level"></span>
                    </div>
                </template>
                <div x-show="filteredAttention('comprehension').length === 0" class="px-4 py-8 text-center text-sm text-gray-500 dark:text-gray-400">No pupils found for this filter.</div>
            </div>
        </div>
    </section>
</div>
