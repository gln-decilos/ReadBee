<style>
    [x-cloak] { display: none !important; }

    .readbee-principal-dashboard {
        --readbee-yellow: #f2c94c;
        --readbee-yellow-soft: #fff7d6;
        --readbee-warm: #fffdf7;
        --readbee-black: #111827;
        --readbee-charcoal: #1f2937;
        --readbee-gray: #667085;
        --readbee-soft: #f8fafc;
        --readbee-line: #e5e7eb;
        --readbee-warm-line: #eee6d2;
        --readbee-brand-hover: #2c3e50;
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

    .dark .readbee-dashboard-hero::after {
        background: rgba(242, 201, 76, .055);
    }

    .readbee-stat-card {
        min-width: 0;
        transition: transform .22s ease, box-shadow .22s ease, border-color .22s ease, background .22s ease;
    }

    .readbee-stat-value {
        font-weight: 600;
        letter-spacing: -0.02em;
    }

    .readbee-dashboard-title {
        font-weight: 600;
        letter-spacing: -0.018em;
    }

    .readbee-dashboard-mini-value {
        font-weight: 600;
        letter-spacing: -0.015em;
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

    .readbee-chart-box .apexcharts-canvas,
    .readbee-chart-box svg {
        max-width: 100% !important;
    }

    .readbee-chart-box .apexcharts-canvas {
        margin-inline: auto;
    }

    .readbee-chart-host {
        min-height: 260px;
        width: 100%;
        min-width: 0;
        overflow: hidden;
    }

    .readbee-chart-host > div {
        width: 100% !important;
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

    .dark .readbee-dashboard-mini-card.is-accent {
        background: rgba(242, 201, 76, .10);
        border-color: rgba(242, 201, 76, .18);
    }

    .readbee-chart-box {
        min-width: 0;
    }

    .readbee-chart-box .apexcharts-legend {
        max-width: 100%;
    }

    .readbee-chart-box .apexcharts-legend-text {
        white-space: normal !important;
    }



    .readbee-stat-icon {
        background: transparent;
        color: var(--readbee-black);
        border: 0 !important;
        box-shadow: none;
        transition: color .22s ease, transform .22s ease;
    }

    .readbee-stat-card:hover .readbee-stat-icon {
        background: transparent;
        color: #ffffff;
        border: 0 !important;
        box-shadow: none;
        transform: translateY(-1px);
    }

    .dark .readbee-stat-icon {
        color: #ffffff;
    }

    .dark .readbee-dashboard-mini-card.is-accent p,
    .dark .readbee-dashboard-mini-card.is-accent span {
        color: #ffffff !important;
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


    .readbee-speed-layout {
        display: grid;
        grid-template-columns: minmax(0, 1fr) minmax(220px, 245px);
        align-items: center;
        gap: 1rem;
    }

    .readbee-speed-figure {
        display: flex;
        align-items: center;
        justify-content: center;
        min-height: 245px;
    }

    .readbee-speed-figure img {
        width: min(100%, 245px);
        height: 245px;
        object-fit: contain;
    }

    .readbee-speed-layout .readbee-chart-host.small-chart {
        min-height: 245px;
    }

    @media (max-width: 768px) {
        .readbee-speed-layout {
            grid-template-columns: 1fr;
        }

        .readbee-speed-figure {
            min-height: 180px;
        }

        .readbee-speed-figure img {
            height: 180px;
        }
    }

    @media (max-width: 640px) {
        .readbee-dashboard-hero::after {
            width: 160px;
            height: 160px;
            right: -92px;
            top: -84px;
        }

        .readbee-chart-host {
            min-height: 235px;
        }

        .readbee-chart-host.small-chart {
            min-height: 215px;
        }
    }

    @media (max-width: 420px) {
        .readbee-chart-host {
            min-height: 220px;
        }

        .readbee-chart-host.small-chart {
            min-height: 200px;
        }
    }
</style>

<script>
    window.principalDashboardPage = function() {
        const palette = {
            yellow: '#f2c94c',
            yellowSoft: '#fff7d6',
            yellowPale: '#fffaf0',
            amber: '#b7791f',
            black: '#111827',
            charcoal: '#1f2937',
            dark: '#374151',
            slate: '#475467',
            gray: '#8a94a6',
            graySoft: '#e5e7eb',
            grayPale: '#f8fafc',
            white: '#ffffff',
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
            filters: {
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
            cards: [
                { label: 'Fast Readers', value: 68, percent: '28%', helper: 'Reads smoothly and accurately.', icon: 'speed' },
                { label: 'Average Readers', value: 94, percent: '39%', helper: 'Reads at the expected pace.', icon: 'book' },
                { label: 'Slow Readers', value: 41, percent: '17%', helper: 'Needs fluency support.', icon: 'time' },
                { label: 'Struggling Readers', value: 25, percent: '10%', helper: 'Needs close monitoring.', icon: 'alert' },
                { label: 'Non-Readers', value: 12, percent: '5%', helper: 'Needs urgent intervention.', icon: 'target' },
            ],
            chartData: {
                readingLevel: {
                    all: [124, 82, 34],
                    male: [61, 46, 20],
                    female: [63, 36, 14],
                },
                comprehensionLevel: {
                    all: [108, 91, 41],
                    male: [53, 51, 23],
                    female: [55, 40, 18],
                },
                readingRate: {
                    all: [61, 68, 74, 81],
                    male: [58, 64, 71, 78],
                    female: [64, 71, 77, 84],
                },
                comprehensionRate: {
                    all: [54, 60, 67, 73],
                    male: [51, 57, 63, 70],
                    female: [57, 63, 71, 76],
                },
                filipinoCompletion: {
                    all: [78, 22],
                    male: [74, 26],
                    female: [82, 18],
                },
                englishCompletion: {
                    all: [72, 28],
                    male: [69, 31],
                    female: [75, 25],
                },
                miscueDistribution: [29, 18, 21, 10, 7, 6, 9],
                speedMale: [30, 42, 23, 15, 7],
                speedFemale: [38, 52, 18, 10, 5],
                comprehensionStatus: [176, 64],
            },
            attentionLists: {
                oral: [
                    { name: 'Miguel Santos', grade: 'Grade 3 - Mabini', sex: 'male', level: 'Non-Reader' },
                    { name: 'Carlo Ramos', grade: 'Grade 4 - Rizal', sex: 'male', level: 'Struggling' },
                    { name: 'Nathan Flores', grade: 'Grade 2 - Bonifacio', sex: 'male', level: 'Slow Reader' },
                    { name: 'Alyssa Reyes', grade: 'Grade 3 - Luna', sex: 'female', level: 'Struggling' },
                    { name: 'Sofia Garcia', grade: 'Grade 5 - Del Pilar', sex: 'female', level: 'Slow Reader' },
                ],
                comprehension: [
                    { name: 'Andrea Torres', grade: 'Grade 4 - Mabini', sex: 'female', level: 'Frustration' },
                    { name: 'Bea Aquino', grade: 'Grade 5 - Luna', sex: 'female', level: 'Instructional' },
                    { name: 'Mark Cruz', grade: 'Grade 3 - Rizal', sex: 'male', level: 'Frustration' },
                    { name: 'Christian Bautista', grade: 'Grade 2 - Bonifacio', sex: 'male', level: 'Instructional' },
                    { name: 'Princess Mendoza', grade: 'Grade 6 - Del Pilar', sex: 'female', level: 'Frustration' },
                ],
            },
            init() {
                this.waitForCharts(() => {
                    this.renderAllCharts();
                    this.queueChartResize();
                    setTimeout(() => this.queueChartResize(), 250);
                    setTimeout(() => this.queueChartResize(), 700);
                });
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
                const selectedSex = this.filters[filterKey];

                return this.attentionLists[type].filter((pupil) => selectedSex === 'all' || pupil.sex === selectedSex);
            },
            sexLabel(value) {
                if (value === 'male') return 'Male';
                if (value === 'female') return 'Female';
                return 'All Sex';
            },
            destroyChart(key) {
                if (this.charts[key]) {
                    try {
                        this.charts[key].destroy();
                    } catch (error) {
                        console.warn('Chart destroy skipped:', key, error);
                    }
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
                            if (chart?.windowResizeHandler) {
                                chart.windowResizeHandler();
                            }
                            chart?.updateOptions?.({ chart: { width: '100%' } }, false, true);
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
            renderAllCharts() {
                this.renderBarChart('readingLevelChart', 'readingLevel', this.chartData.readingLevel[this.filters.readingLevelSex], 'Reading Level');
                this.renderBarChart('comprehensionLevelChart', 'comprehensionLevel', this.chartData.comprehensionLevel[this.filters.comprehensionLevelSex], 'Comprehension Level');
                this.renderLineChart('readingRateChart', 'readingRate', this.chartData.readingRate[this.filters.readingRateSex], 'Reading Rate');
                this.renderLineChart('comprehensionRateChart', 'comprehensionRate', this.chartData.comprehensionRate[this.filters.comprehensionRateSex], 'Comprehension Rate');
                this.renderDonutChart('miscueTypeChart', 'miscueType', this.chartData.miscueDistribution, ['Mispronunciation', 'Omission', 'Substitution', 'Insertion', 'Transposition', 'Reversal', 'Repetition'], 'Miscue Types');
                this.renderProgressDonut('filipinoCompletionChart', 'filipinoCompletion', this.chartData.filipinoCompletion[this.filters.filipinoCompletionSex], 'Filipino');
                this.renderProgressDonut('englishCompletionChart', 'englishCompletion', this.chartData.englishCompletion[this.filters.englishCompletionSex], 'English');
                this.renderPieChart('maleSpeedChart', 'maleSpeed', this.chartData.speedMale, ['Fast', 'Average', 'Slow', 'Struggling', 'Non-Reader']);
                this.renderPieChart('femaleSpeedChart', 'femaleSpeed', this.chartData.speedFemale, ['Fast', 'Average', 'Slow', 'Struggling', 'Non-Reader']);
                this.renderPieChart('comprehensionStatusChart', 'comprehensionStatus', this.chartData.comprehensionStatus, ['With Comprehension', 'Without Comprehension']);
            },
            refreshChart(key) {
                if (!this.charts[key]) return;

                const map = {
                    readingLevel: () => this.charts.readingLevel.updateSeries([{ name: this.sexLabel(this.filters.readingLevelSex), data: this.chartData.readingLevel[this.filters.readingLevelSex] }]),
                    comprehensionLevel: () => this.charts.comprehensionLevel.updateSeries([{ name: this.sexLabel(this.filters.comprehensionLevelSex), data: this.chartData.comprehensionLevel[this.filters.comprehensionLevelSex] }]),
                    readingRate: () => this.charts.readingRate.updateSeries([{ name: this.sexLabel(this.filters.readingRateSex), data: this.chartData.readingRate[this.filters.readingRateSex] }]),
                    comprehensionRate: () => this.charts.comprehensionRate.updateSeries([{ name: this.sexLabel(this.filters.comprehensionRateSex), data: this.chartData.comprehensionRate[this.filters.comprehensionRateSex] }]),
                    filipinoCompletion: () => this.charts.filipinoCompletion.updateSeries(this.chartData.filipinoCompletion[this.filters.filipinoCompletionSex]),
                    englishCompletion: () => this.charts.englishCompletion.updateSeries(this.chartData.englishCompletion[this.filters.englishCompletionSex]),
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
                    animations: { enabled: true, easing: 'easeinout', speed: 550 },
                    redrawOnParentResize: true,
                    redrawOnWindowResize: true,
                };
            },
            renderBarChart(elementId, key, data, title) {
                const element = this.prepareElement(elementId, key);
                if (!element) return;

                const options = {
                    series: [{ name: this.sexLabel(this.filters[key + 'Sex']), data }],
                    colors: this.chartBarColors(),
                    chart: { ...this.chartBase(), type: 'bar', height: 265, width: '100%', redrawOnParentResize: true, redrawOnWindowResize: true },
                    plotOptions: { bar: { borderRadius: 7, columnWidth: '44%', distributed: true } },
                    dataLabels: { enabled: false },
                    legend: { show: false },
                    xaxis: {
                        categories: ['Independent', 'Instructional', 'Frustration'],
                        axisBorder: { show: false },
                        axisTicks: { show: false },
                        labels: { rotate: 0, trim: true, style: { fontSize: '12px', colors: [this.chartMutedTextColor(), this.chartMutedTextColor(), this.chartMutedTextColor()] } },
                    },
                    yaxis: { title: { text: 'Pupils', style: { color: this.chartMutedTextColor() } }, labels: { formatter: (value) => Math.round(value), style: { colors: this.chartMutedTextColor() } } },
                    grid: { borderColor: this.chartGridColor(), strokeDashArray: 4 },
                    tooltip: { y: { formatter: (value) => `${value} pupils` } },
                    title: { text: title, style: { fontSize: '0px' } },
                    responsive: [
                        { breakpoint: 640, options: { chart: { height: 235 }, plotOptions: { bar: { columnWidth: '52%' } }, xaxis: { labels: { style: { fontSize: '10px' } } } } },
                    ],
                };

                this.charts[key] = new ApexCharts(element, options);
                this.charts[key].render().then(() => this.queueChartResize());
            },
            renderLineChart(elementId, key, data, title) {
                const element = this.prepareElement(elementId, key);
                if (!element) return;

                const options = {
                    series: [{ name: this.sexLabel(this.filters[key + 'Sex']), data }],
                    colors: [this.chartLineColor()],
                    chart: { ...this.chartBase(), type: 'line', height: 275, width: '100%', zoom: { enabled: false }, redrawOnParentResize: true, redrawOnWindowResize: true },
                    stroke: { curve: 'smooth', width: 4 },
                    markers: { size: 5, colors: [document.documentElement.classList.contains('dark') ? '#111827' : palette.yellow], strokeColors: this.chartLineColor(), strokeWidth: 3 },
                    fill: { type: 'gradient', gradient: { shadeIntensity: .2, opacityFrom: .22, opacityTo: .02, stops: [0, 95, 100] } },
                    dataLabels: { enabled: false },
                    xaxis: {
                        categories: ['First', 'Second', 'Third', 'Fourth'],
                        axisBorder: { show: false },
                        axisTicks: { show: false },
                        labels: { style: { fontSize: '12px', colors: [this.chartMutedTextColor(), this.chartMutedTextColor(), this.chartMutedTextColor(), this.chartMutedTextColor()] } },
                    },
                    yaxis: { min: 0, max: 100, labels: { formatter: (value) => `${Math.round(value)}%`, style: { colors: this.chartMutedTextColor() } } },
                    grid: { borderColor: this.chartGridColor(), strokeDashArray: 4 },
                    tooltip: { y: { formatter: (value) => `${value}%` }, x: { formatter: (value) => `${value} Quarter` } },
                    title: { text: title, style: { fontSize: '0px' } },
                    responsive: [
                        { breakpoint: 640, options: { chart: { height: 240 }, stroke: { width: 3 }, markers: { size: 4 } } },
                    ],
                };

                this.charts[key] = new ApexCharts(element, options);
                this.charts[key].render().then(() => this.queueChartResize());
            },
            renderDonutChart(elementId, key, data, labels, title) {
                const element = this.prepareElement(elementId, key);
                if (!element) return;

                const options = {
                    series: data,
                    labels,
                    colors: chartColors,
                    chart: { ...this.chartBase(), type: 'donut', height: 320, width: '100%', redrawOnParentResize: true, redrawOnWindowResize: true },
                    legend: { position: 'bottom', fontSize: '12px', labels: { colors: this.chartMutedTextColor() }, markers: { radius: 99 }, itemMargin: { horizontal: 6, vertical: 3 } },
                    stroke: { width: this.chartStrokeWidth(), colors: [this.chartStrokeColor()] },
                    dataLabels: { enabled: false },
                    plotOptions: {
                        pie: {
                            donut: {
                                size: '68%',
                                labels: {
                                    show: true,
                                    name: { fontSize: '12px', color: this.chartMutedTextColor() },
                                    value: { fontSize: '22px', fontWeight: 800, color: this.chartTextColor() },
                                    total: { show: true, label: title, color: this.chartMutedTextColor(), formatter: () => data.reduce((sum, value) => sum + value, 0) },
                                },
                            },
                        },
                    },
                    responsive: [
                        { breakpoint: 640, options: { chart: { height: 275 }, legend: { fontSize: '11px' } } },
                    ],
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
                    chart: { ...this.chartBase(), type: 'donut', height: 245, width: '100%', redrawOnParentResize: true, redrawOnWindowResize: true },
                    legend: { position: 'bottom', fontSize: '12px', labels: { colors: this.chartMutedTextColor() } },
                    stroke: { width: this.chartStrokeWidth(), colors: [this.chartStrokeColor()] },
                    dataLabels: { enabled: false },
                    plotOptions: {
                        pie: {
                            donut: {
                                size: '74%',
                                labels: {
                                    show: true,
                                    name: { color: this.chartMutedTextColor() },
                                    value: { formatter: (value) => `${value}%`, color: this.chartTextColor(), fontWeight: 800 },
                                    total: { show: true, showAlways: true, label, color: this.chartMutedTextColor(), formatter: (w) => `${w.globals.series[0]}%` },
                                },
                            },
                        },
                    },
                    responsive: [
                        { breakpoint: 640, options: { chart: { height: 220 } } },
                    ],
                };

                this.charts[key] = new ApexCharts(element, options);
                this.charts[key].render().then(() => this.queueChartResize());
            },
            renderPieChart(elementId, key, data, labels) {
                const element = this.prepareElement(elementId, key);
                if (!element) return;

                const options = {
                    series: data,
                    labels,
                    colors: chartColors,
                    chart: { ...this.chartBase(), type: 'pie', height: 245, width: '100%', redrawOnParentResize: true, redrawOnWindowResize: true },
                    legend: { position: 'bottom', fontSize: '11px', labels: { colors: this.chartMutedTextColor() }, itemMargin: { horizontal: 5, vertical: 2 } },
                    stroke: { width: this.chartStrokeWidth(), colors: [this.chartStrokeColor()] },
                    dataLabels: { enabled: false },
                    tooltip: { y: { formatter: (value) => `${value} pupils` } },
                    responsive: [
                        { breakpoint: 640, options: { chart: { height: 220 } } },
                    ],
                };

                this.charts[key] = new ApexCharts(element, options);
                this.charts[key].render().then(() => this.queueChartResize());
            },
        };
    };
</script>

<div x-data="principalDashboardPage()" x-cloak class="readbee-principal-dashboard space-y-6">
    <section class="readbee-dashboard-hero p-5 shadow-theme-md sm:p-6 xl:p-7">
        <div class="relative z-10 flex flex-col gap-5 lg:flex-row lg:items-center lg:justify-between">
            <div class="min-w-0">
                <div class="flex items-center gap-3">
                    <div class="flex h-14 w-14 shrink-0 items-center justify-center rounded-2xl bg-gray-50 ring-1 ring-gray-200 dark:bg-white/[0.04] dark:ring-white/10 sm:h-16 sm:w-16">
                        <img src="{{ asset('landing-assets/images/CuteBee3.png') }}" alt="ReadBee dashboard" class="h-11 w-11 object-contain sm:h-12 sm:w-12">
                    </div>
                    <div class="min-w-0">
                        <p class="text-xs font-semibold uppercase tracking-[0.24em] text-gray-500 dark:text-gray-400">Principal Dashboard</p>
                        <h1 class="readbee-dashboard-title mt-1 text-2xl font-semibold leading-tight text-gray-950 dark:text-white sm:text-3xl">Reading Performance Overview</h1>
                    </div>
                </div>
                <p class="mt-4 max-w-3xl text-sm leading-6 text-gray-600 dark:text-gray-300">
                    Monitor reading speed, reading level, comprehension level, assessment completion, miscues, and pupils needing support in one clear view.
                </p>
                <div class="mt-4 flex flex-wrap gap-2 text-xs font-medium">
                    <span class="readbee-dashboard-pill rounded-full px-3 py-1 dark:border-white/10 dark:bg-white/[0.07] dark:text-gray-100">School Year: 2025–2026</span>
                    <span class="readbee-dashboard-pill rounded-full px-3 py-1 dark:border-white/10 dark:bg-white/[0.07] dark:text-gray-100">All Quarters</span>
                    <span class="readbee-dashboard-pill rounded-full px-3 py-1 dark:border-white/10 dark:bg-white/[0.07] dark:text-gray-100">All Grades</span>
                </div>
            </div>

            <div class="grid w-full grid-cols-1 gap-2 min-[420px]:grid-cols-3 sm:max-w-md sm:gap-3">
                <div class="readbee-dashboard-mini-card rounded-2xl p-3 text-center shadow-theme-xs dark:bg-white/10">
                    <p class="truncate text-[11px] font-medium text-gray-500 dark:text-gray-300">Total Pupils</p>
                    <p class="readbee-dashboard-mini-value mt-1 text-xl font-semibold text-gray-950 dark:text-white sm:text-2xl">240</p>
                </div>
                <div class="readbee-dashboard-mini-card is-accent rounded-2xl p-3 text-center shadow-theme-xs">
                    <p class="truncate text-[11px] font-semibold text-gray-950">Assessed</p>
                    <p class="readbee-dashboard-mini-value mt-1 text-xl font-semibold text-gray-950 sm:text-2xl">181</p>
                </div>
                <div class="readbee-dashboard-mini-card rounded-2xl p-3 text-center shadow-theme-xs dark:bg-white/10">
                    <p class="truncate text-[11px] font-medium text-gray-500 dark:text-gray-300">Need Support</p>
                    <p class="readbee-dashboard-mini-value mt-1 text-xl font-semibold text-gray-950 dark:text-white sm:text-2xl">37</p>
                </div>
            </div>
        </div>
    </section>

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
                <div class="min-w-0">
                    <h2 class="text-base font-semibold text-gray-950 dark:text-white sm:text-lg">Reading Level</h2>
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Independent, instructional, and frustration level.</p>
                </div>
                <select x-model="filters.readingLevelSex" @change="refreshChart('readingLevel')" class="h-10 w-full rounded-lg border border-gray-300 bg-white px-3 text-sm text-gray-700 shadow-theme-xs focus:border-[#d6b13f] focus:outline-hidden focus:ring-3 focus:ring-amber-500/10 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-200 sm:w-36">
                    <option value="all">All Sex</option>
                    <option value="male">Male</option>
                    <option value="female">Female</option>
                </select>
            </div>
            <div id="readingLevelChart" class="readbee-chart-host"></div>
        </div>

        <div class="readbee-chart-box rounded-2xl border border-gray-200 bg-white p-4 shadow-theme-xs dark:border-gray-800 dark:bg-white/[0.03] sm:p-5">
            <div class="mb-4 flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                <div class="min-w-0">
                    <h2 class="text-base font-semibold text-gray-950 dark:text-white sm:text-lg">Comprehension Level</h2>
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Shows how pupils understand what they read.</p>
                </div>
                <select x-model="filters.comprehensionLevelSex" @change="refreshChart('comprehensionLevel')" class="h-10 w-full rounded-lg border border-gray-300 bg-white px-3 text-sm text-gray-700 shadow-theme-xs focus:border-[#d6b13f] focus:outline-hidden focus:ring-3 focus:ring-amber-500/10 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-200 sm:w-36">
                    <option value="all">All Sex</option>
                    <option value="male">Male</option>
                    <option value="female">Female</option>
                </select>
            </div>
            <div id="comprehensionLevelChart" class="readbee-chart-host"></div>
        </div>
    </section>

    <section class="grid grid-cols-1 gap-5 lg:grid-cols-2">
        <div class="readbee-chart-box rounded-2xl border border-gray-200 bg-white p-4 shadow-theme-xs dark:border-gray-800 dark:bg-white/[0.03] sm:p-5">
            <div class="mb-4 flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                <div class="min-w-0">
                    <h2 class="text-base font-semibold text-gray-950 dark:text-white sm:text-lg">Reading Rate by Quarter</h2>
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Quarterly reading rate from first to fourth quarter.</p>
                </div>
                <select x-model="filters.readingRateSex" @change="refreshChart('readingRate')" class="h-10 w-full rounded-lg border border-gray-300 bg-white px-3 text-sm text-gray-700 shadow-theme-xs focus:border-[#d6b13f] focus:outline-hidden focus:ring-3 focus:ring-amber-500/10 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-200 sm:w-36">
                    <option value="all">All Sex</option>
                    <option value="male">Male</option>
                    <option value="female">Female</option>
                </select>
            </div>
            <div id="readingRateChart" class="readbee-chart-host"></div>
        </div>

        <div class="readbee-chart-box rounded-2xl border border-gray-200 bg-white p-4 shadow-theme-xs dark:border-gray-800 dark:bg-white/[0.03] sm:p-5">
            <div class="mb-4 flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                <div class="min-w-0">
                    <h2 class="text-base font-semibold text-gray-950 dark:text-white sm:text-lg">Comprehension Rate by Quarter</h2>
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Tracks comprehension growth per quarter.</p>
                </div>
                <select x-model="filters.comprehensionRateSex" @change="refreshChart('comprehensionRate')" class="h-10 w-full rounded-lg border border-gray-300 bg-white px-3 text-sm text-gray-700 shadow-theme-xs focus:border-[#d6b13f] focus:outline-hidden focus:ring-3 focus:ring-amber-500/10 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-200 sm:w-36">
                    <option value="all">All Sex</option>
                    <option value="male">Male</option>
                    <option value="female">Female</option>
                </select>
            </div>
            <div id="comprehensionRateChart" class="readbee-chart-host"></div>
        </div>
    </section>

    <section class="grid grid-cols-1 gap-5 lg:grid-cols-2">
        <div class="readbee-chart-box rounded-2xl border border-gray-200 bg-white p-4 shadow-theme-xs dark:border-gray-800 dark:bg-white/[0.03] sm:p-5">
            <h2 class="text-base font-semibold text-gray-950 dark:text-white sm:text-lg">Miscue Type Distribution</h2>
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Most common oral reading miscues.</p>
            <div id="miscueTypeChart" class="readbee-chart-host mt-2"></div>
        </div>

        <div class="readbee-chart-box rounded-2xl border border-gray-200 bg-white p-4 shadow-theme-xs dark:border-gray-800 dark:bg-white/[0.03] sm:p-5">
            <h2 class="text-base font-semibold text-gray-950 dark:text-white sm:text-lg">With vs Without Comprehension</h2>
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Overall comprehension status.</p>
            <div id="comprehensionStatusChart" class="readbee-chart-host mt-2"></div>
        </div>
    </section>

    <section class="readbee-chart-box rounded-2xl border border-gray-200 bg-white p-4 shadow-theme-xs dark:border-gray-800 dark:bg-white/[0.03] sm:p-5">
        <div class="mb-4 min-w-0">
            <h2 class="text-base font-semibold text-gray-950 dark:text-white sm:text-lg">Assessment Completion Rate</h2>
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Progress for Filipino and English assessments.</p>
        </div>
        <div class="grid grid-cols-1 gap-4 lg:grid-cols-2">
            <div class="rounded-2xl border border-gray-200 bg-gray-50 p-4 dark:border-gray-800 dark:bg-white/[0.03]">
                <div class="mb-3 flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                    <h3 class="font-semibold text-gray-950 dark:text-white">Filipino Completion</h3>
                    <select x-model="filters.filipinoCompletionSex" @change="refreshChart('filipinoCompletion')" class="h-9 w-full rounded-lg border border-gray-300 bg-white px-3 text-xs text-gray-700 shadow-theme-xs focus:border-[#d6b13f] focus:outline-hidden focus:ring-3 focus:ring-amber-500/10 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-200 sm:w-32">
                        <option value="all">All Sex</option>
                        <option value="male">Male</option>
                        <option value="female">Female</option>
                    </select>
                </div>
                <div id="filipinoCompletionChart" class="readbee-chart-host small-chart"></div>
            </div>
            <div class="rounded-2xl border border-gray-200 bg-gray-50 p-4 dark:border-gray-800 dark:bg-white/[0.03]">
                <div class="mb-3 flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                    <h3 class="font-semibold text-gray-950 dark:text-white">English Completion</h3>
                    <select x-model="filters.englishCompletionSex" @change="refreshChart('englishCompletion')" class="h-9 w-full rounded-lg border border-gray-300 bg-white px-3 text-xs text-gray-700 shadow-theme-xs focus:border-[#d6b13f] focus:outline-hidden focus:ring-3 focus:ring-amber-500/10 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-200 sm:w-32">
                        <option value="all">All Sex</option>
                        <option value="male">Male</option>
                        <option value="female">Female</option>
                    </select>
                </div>
                <div id="englishCompletionChart" class="readbee-chart-host small-chart"></div>
            </div>
        </div>
    </section>

    <section class="grid grid-cols-1 gap-5 lg:grid-cols-2">
        <div class="readbee-chart-box rounded-2xl border border-gray-200 bg-white p-4 shadow-theme-xs dark:border-gray-800 dark:bg-white/[0.03] sm:p-5">
            <h2 class="text-base font-semibold text-gray-950 dark:text-white sm:text-lg">Reading Speed Distribution</h2>
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Male pupils by reading speed.</p>
            <div class="readbee-speed-layout mt-3">
                <div id="maleSpeedChart" class="readbee-chart-host small-chart"></div>
                <div class="readbee-speed-figure">
                    <img src="{{ asset('landing-assets/images/maleChild.png') }}" alt="Male pupil reading">
                </div>
            </div>
        </div>
        <div class="readbee-chart-box rounded-2xl border border-gray-200 bg-white p-4 shadow-theme-xs dark:border-gray-800 dark:bg-white/[0.03] sm:p-5">
            <h2 class="text-base font-semibold text-gray-950 dark:text-white sm:text-lg">Reading Speed Distribution</h2>
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Female pupils by reading speed.</p>
            <div class="readbee-speed-layout mt-3">
                <div id="femaleSpeedChart" class="readbee-chart-host small-chart"></div>
                <div class="readbee-speed-figure">
                    <img src="{{ asset('landing-assets/images/femaleChild.png') }}" alt="Female pupil reading">
                </div>
            </div>
        </div>
    </section>

    <section class="grid grid-cols-1 gap-5 lg:grid-cols-2">
        <div class="rounded-2xl border border-gray-200 bg-white p-4 shadow-theme-xs dark:border-gray-800 dark:bg-white/[0.03] sm:p-5">
            <div class="mb-5 flex flex-col gap-3 lg:flex-row lg:items-start lg:justify-between">
                <div class="min-w-0">
                    <h2 class="text-base font-semibold text-gray-950 dark:text-white sm:text-lg">Pupils Needing Attention in Oral Reading</h2>
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Priority pupils for reading fluency support.</p>
                </div>
                <div class="flex shrink-0 flex-col gap-2 sm:flex-row">
                    <select x-model="filters.oralAttentionSex" class="h-10 rounded-lg border border-gray-300 bg-white px-3 text-sm text-gray-700 shadow-theme-xs focus:border-[#d6b13f] focus:outline-hidden focus:ring-3 focus:ring-amber-500/10 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-200">
                        <option value="all">All Sex</option>
                        <option value="male">Male</option>
                        <option value="female">Female</option>
                    </select>
                    <button type="button" class="inline-flex h-10 items-center justify-center gap-2 whitespace-nowrap rounded-lg bg-brand-500 px-3 text-sm font-semibold text-white shadow-theme-xs hover:bg-brand-600">
                        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none"><path d="M12 3V15M12 15L8 11M12 15L16 11M5 21H19" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" /></svg>
                        Download PDF
                    </button>
                </div>
            </div>
            <div class="overflow-hidden rounded-xl border border-gray-200 dark:border-gray-800">
                <template x-for="pupil in filteredAttention('oral')" :key="pupil.name">
                    <div class="flex flex-col gap-2 border-b border-gray-100 px-4 py-3 last:border-b-0 dark:border-gray-800 sm:flex-row sm:items-center sm:justify-between">
                        <div class="min-w-0">
                            <p class="truncate font-medium text-gray-950 dark:text-white" x-text="pupil.name"></p>
                            <p class="text-xs text-gray-500 dark:text-gray-400" x-text="pupil.grade"></p>
                        </div>
                        <span class="w-fit rounded-full bg-gray-100 px-3 py-1 text-xs font-semibold text-gray-700 dark:bg-white/10 dark:text-gray-300" x-text="pupil.level"></span>
                    </div>
                </template>
                <div x-show="filteredAttention('oral').length === 0" class="px-4 py-8 text-center text-sm text-gray-500 dark:text-gray-400">No pupils found for this filter.</div>
            </div>
        </div>

        <div class="rounded-2xl border border-gray-200 bg-white p-4 shadow-theme-xs dark:border-gray-800 dark:bg-white/[0.03] sm:p-5">
            <div class="mb-5 flex flex-col gap-3 lg:flex-row lg:items-start lg:justify-between">
                <div class="min-w-0">
                    <h2 class="text-base font-semibold text-gray-950 dark:text-white sm:text-lg">Pupils Needing Attention in Comprehension</h2>
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Priority pupils for comprehension intervention.</p>
                </div>
                <div class="flex shrink-0 flex-col gap-2 sm:flex-row">
                    <select x-model="filters.comprehensionAttentionSex" class="h-10 rounded-lg border border-gray-300 bg-white px-3 text-sm text-gray-700 shadow-theme-xs focus:border-[#d6b13f] focus:outline-hidden focus:ring-3 focus:ring-amber-500/10 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-200">
                        <option value="all">All Sex</option>
                        <option value="male">Male</option>
                        <option value="female">Female</option>
                    </select>
                    <button type="button" class="inline-flex h-10 items-center justify-center gap-2 whitespace-nowrap rounded-lg bg-brand-500 px-3 text-sm font-semibold text-white shadow-theme-xs hover:bg-brand-600">
                        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none"><path d="M12 3V15M12 15L8 11M12 15L16 11M5 21H19" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" /></svg>
                        Download PDF
                    </button>
                </div>
            </div>
            <div class="overflow-hidden rounded-xl border border-gray-200 dark:border-gray-800">
                <template x-for="pupil in filteredAttention('comprehension')" :key="pupil.name">
                    <div class="flex flex-col gap-2 border-b border-gray-100 px-4 py-3 last:border-b-0 dark:border-gray-800 sm:flex-row sm:items-center sm:justify-between">
                        <div class="min-w-0">
                            <p class="truncate font-medium text-gray-950 dark:text-white" x-text="pupil.name"></p>
                            <p class="text-xs text-gray-500 dark:text-gray-400" x-text="pupil.grade"></p>
                        </div>
                        <span class="w-fit rounded-full bg-gray-100 px-3 py-1 text-xs font-semibold text-gray-700 dark:bg-white/10 dark:text-gray-300" x-text="pupil.level"></span>
                    </div>
                </template>
                <div x-show="filteredAttention('comprehension').length === 0" class="px-4 py-8 text-center text-sm text-gray-500 dark:text-gray-400">No pupils found for this filter.</div>
            </div>
        </div>
    </section>
</div>
