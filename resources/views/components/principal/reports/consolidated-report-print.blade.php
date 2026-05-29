@php
    $isSubmitted = in_array($report['existing_report_status'] ?? 'draft', ['submitted', 'reviewed', 'approved'], true);
    $isComplete = (bool) ($report['is_complete'] ?? false);
    $canSubmit = ($report['is_ready'] ?? false) && $isComplete && ! $isSubmitted;
    $feedbackType = session('success') ? 'success' : (session('error') ? 'error' : (session('info') ? 'info' : null));
    $feedbackMessage = session('success') ?: (session('error') ?: session('info'));
    $passageColumns = ['Non-Reader', 'Struggling', 'Slow', 'Average', 'Fast'];
    $comprehensionColumns = ['Independent', 'Instructional', 'Frustrated'];
@endphp

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $title ?? 'Consolidated Report' }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>
        [x-cloak] { display: none !important; }

        body {
            margin: 0;
            background: #f3f4f6;
            font-family: "Times New Roman", Times, serif;
            color: #111827;
        }

        .screen-toolbar {
            position: sticky;
            top: 0;
            z-index: 50;
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            justify-content: space-between;
            gap: .75rem;
            padding: .75rem 1rem;
            background: rgba(255, 255, 255, .96);
            border-bottom: 1px solid #e5e7eb;
            font-family: Outfit, system-ui, sans-serif;
            backdrop-filter: blur(10px);
        }

        .paper-shell {
            padding: 18px;
        }

        .report-paper {
            width: 13in;
            min-height: 8.5in;
            margin: 0 auto;
            display: flex;
            flex-direction: column;
            background: #ffffff;
            box-shadow: 0 18px 55px rgba(15, 23, 42, .16);
            padding: .42in .5in;
        }

        .report-header {
            text-align: center;
            line-height: 1.12;
        }

        .republic { font-size: 11pt; }
        .department { font-size: 14pt; font-weight: 700; }
        .region, .division, .district { font-size: 10pt; }

        .header-rule,
        .footer-rule {
            border-top: 1px solid #111827;
            margin: 8px 0 9px;
            width: 100%;
        }

        .report-title {
            text-align: center;
            font-size: 12pt;
            font-weight: 700;
            line-height: 1.18;
            text-transform: uppercase;
        }

        .report-meta {
            margin-top: 10px;
            display: grid;
            grid-template-columns: 1fr;
            gap: 6px;
            font-size: 10pt;
        }

        .meta-line strong { font-weight: 700; }
        .meta-value {
            display: inline-block;
            min-width: 210px;
            border-bottom: 1px solid #111827;
            font-weight: 400;
        }

        .content-area {
            flex: 1;
            display: flex;
            flex-direction: column;
            justify-content: flex-start;
            padding-top: 28px;
        }

        table.report-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 9.5pt;
            table-layout: fixed;
        }

        .report-table th,
        .report-table td {
            border: 1px solid #111827;
            padding: 5px 4px;
            text-align: center;
            vertical-align: middle;
            line-height: 1.15;
        }

        .report-table th {
            font-weight: 700;
        }

        .report-table .section-cell {
            text-align: left;
            font-weight: 700;
            padding-left: 8px;
            width: 17%;
        }

        .total-row td {
            font-weight: 700;
        }

        .submitted-list {
            margin-top: 12px;
            font-size: 9pt;
            color: #374151;
        }

        .footer {
            margin-top: auto;
            font-size: 10pt;
        }

        .tagline {
            text-align: center;
            font-style: italic;
            font-weight: 700;
            margin-bottom: 6px;
        }

        .footer-contact {
            line-height: 1.25;
            text-align: left;
        }

        @page {
            size: 13in 8.5in landscape;
            margin: .35in;
        }

        @media print {
            body { background: #ffffff; }
            .screen-toolbar,
            .no-print { display: none !important; }
            .paper-shell { padding: 0; }
            .report-paper {
                width: auto;
                min-height: auto;
                margin: 0;
                box-shadow: none;
                padding: 0;
            }
        }
    </style>
</head>
<body>
<div
    x-data="{
        confirmOpen: false,
        feedbackOpen: {{ $feedbackMessage ? 'true' : 'false' }},
        confirmSubmit(event) {
            event.preventDefault();
            this.confirmOpen = true;
        },
        submitConfirmed() {
            this.$refs.submitForm.submit();
        },
        closeFeedback() { this.feedbackOpen = false; }
    }"
    x-cloak
>
    <div class="screen-toolbar">
        <div>
            <p class="text-sm font-semibold text-gray-900">{{ $report['grade_label'] }} · {{ $report['quarter_label'] }} · {{ $report['language_label'] }}</p>
            <p class="text-xs text-gray-500">Consolidated report preview</p>
        </div>
        <div class="flex flex-wrap items-center gap-2">
            <a href="{{ route('principal.reports', ['year_id' => $report['year_id']]) }}" class="inline-flex h-9 items-center justify-center rounded-lg border border-gray-300 px-3 text-sm font-semibold text-gray-700 hover:bg-gray-50">
                Back
            </a>
            <button type="button" onclick="window.print()" class="inline-flex h-9 items-center justify-center rounded-lg border border-gray-300 px-3 text-sm font-semibold text-gray-700 hover:bg-gray-50">
                Print
            </button>
            <form x-ref="submitForm" method="POST" action="{{ route('principal.reports.submit', ['gradeLevelId' => $report['grade_level_id'], 'yearId' => $report['year_id'], 'quarterId' => $report['quarter_id'], 'language' => $report['language']]) }}" @submit="confirmSubmit($event)">
                @csrf
                <button type="submit" @disabled(! $canSubmit) class="inline-flex h-9 items-center justify-center rounded-lg px-3 text-sm font-semibold text-white transition {{ $canSubmit ? 'bg-brand-500 hover:bg-brand-600' : 'cursor-not-allowed bg-gray-100 !text-gray-500' }}">
                    {{ $isSubmitted ? 'Submitted' : ($isComplete ? 'Submit to District' : 'Waiting for Sections') }}
                </button>
            </form>
        </div>
    </div>

    <main class="paper-shell">
        <article class="report-paper">
            <header class="report-header">
                <div class="republic">Republic of the Philippines</div>
                <div class="department">Department of Education</div>
                <div class="region">{{ $report['region_label'] }}</div>
                <div class="division">{{ $report['division_label'] }}</div>
                <div class="district">{{ $report['district_name'] }}</div>
                <div class="header-rule"></div>
                <div class="district" style="font-weight:700;">{{ $report['district_name'] }}</div>
                <div class="report-title">ORAL READING AND COMPREHENSION ASSESSMENT RESULT IN {{ $report['report_language_title'] }}</div>
                <div class="report-title">{{ $report['grade_report_label'] }}</div>
                <div class="report-title">{{ $report['quarter_report_label'] }} {{ $report['school_year_report_label'] }}</div>
            </header>

            <section class="report-meta">
                <div class="meta-line"><strong>Principal Name:</strong> <span class="meta-value">{{ $report['principal_name'] }}</span></div>
                <div class="meta-line"><strong>School Name:</strong> <span class="meta-value">{{ $report['school_name'] }}</span></div>
            </section>

            <section class="content-area">
                <table class="report-table">
                    <thead>
                        <tr>
                            <th rowspan="2">Section</th>
                            <th colspan="2">Sex</th>
                            <th colspan="5">Passage Level</th>
                            <th colspan="3">Comprehension Level</th>
                        </tr>
                        <tr>
                            <th>M</th>
                            <th>F</th>
                            @foreach ($passageColumns as $column)
                                <th>{{ $column }}</th>
                            @endforeach
                            @foreach ($comprehensionColumns as $column)
                                <th>{{ $column }}</th>
                            @endforeach
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($report['section_rows'] as $row)
                            @php $summary = $row['summary']; @endphp
                            <tr>
                                <td class="section-cell">{{ $row['section_name'] }}</td>
                                <td>{{ $summary['male'] }}</td>
                                <td>{{ $summary['female'] }}</td>
                                @foreach ($passageColumns as $column)
                                    <td>{{ $summary['passage'][$column] ?? 0 }}</td>
                                @endforeach
                                @foreach ($comprehensionColumns as $column)
                                    <td>{{ $summary['comprehension'][$column] ?? 0 }}</td>
                                @endforeach
                            </tr>
                        @endforeach
                        <tr class="total-row">
                            <td class="section-cell">TOTAL</td>
                            <td>{{ $report['summary']['male'] }}</td>
                            <td>{{ $report['summary']['female'] }}</td>
                            @foreach ($passageColumns as $column)
                                <td>{{ $report['summary']['passage'][$column] ?? 0 }}</td>
                            @endforeach
                            @foreach ($comprehensionColumns as $column)
                                <td>{{ $report['summary']['comprehension'][$column] ?? 0 }}</td>
                            @endforeach
                        </tr>
                    </tbody>
                </table>

                <div class="submitted-list">
                    <strong>Evaluator submitted sections:</strong>
                    {{ collect($report['section_rows'])->filter(fn ($row) => $row['is_submitted'] ?? false)->map(fn ($row) => $row['section_name'] . ' - ' . $row['submitted_by'])->implode('; ') ?: 'None yet' }}
                    @if (! $isComplete && ! empty($report['missing_section_labels']))
                        <br><strong>Waiting for section report{{ count($report['missing_section_labels']) === 1 ? '' : 's' }}:</strong>
                        {{ collect($report['missing_section_labels'])->implode(', ') }}
                    @endif
                </div>
            </section>

            <footer class="footer">
                <div class="footer-rule"></div>
                <div class="tagline">&quot;Nurturing with Discipline, Trust, and Respect&quot;</div>
                <div class="footer-contact">
                    <div>Address: {{ $report['school_address'] }}</div>
                    <div>Contact: {{ $report['school_contact'] }}</div>
                    <div>Email: {{ $report['school_email'] }}</div>
                </div>
            </footer>
        </article>
    </main>

    <div x-show="confirmOpen" x-transition.opacity class="fixed inset-0 z-[99998] bg-gray-950/45 backdrop-blur-sm no-print" @click="confirmOpen = false"></div>
    <div x-show="confirmOpen" x-transition class="fixed inset-0 z-[99999] flex items-center justify-center p-4 no-print" role="dialog" aria-modal="true">
        <div class="w-full max-w-md rounded-2xl border border-gray-200 bg-white p-5 shadow-2xl" @click.stop>
            <div class="flex items-start gap-3">
                <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-full bg-[#fff7d6] text-gray-900">
                    <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M9 12.5 11 14.5 15.5 9.5" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/><path d="M12 21a9 9 0 1 0 0-18 9 9 0 0 0 0 18Z" stroke="currentColor" stroke-width="1.8"/></svg>
                </div>
                <div class="min-w-0 font-sans">
                    <h3 class="text-lg font-semibold text-gray-950">Submit Consolidated Report?</h3>
                    <p class="mt-1 text-sm leading-6 text-gray-600">Please confirm that this {{ $report['language_label'] }} consolidated report is complete and ready to submit to the district supervisor.</p>
                </div>
            </div>
            <div class="mt-6 flex flex-col-reverse gap-2 sm:flex-row sm:justify-end font-sans">
                <button type="button" @click="confirmOpen = false" class="inline-flex h-10 items-center justify-center rounded-lg border border-gray-300 px-4 text-sm font-semibold text-gray-700 hover:bg-gray-50">Cancel</button>
                <button type="button" @click="submitConfirmed()" class="inline-flex h-10 items-center justify-center rounded-lg bg-brand-500 px-4 text-sm font-semibold text-white hover:bg-brand-600">Yes, Submit</button>
            </div>
        </div>
    </div>

    @if ($feedbackMessage)
        <div x-show="feedbackOpen" x-transition.opacity class="fixed inset-0 z-[99998] bg-gray-950/45 backdrop-blur-sm no-print" @click="closeFeedback()"></div>
        <div x-show="feedbackOpen" x-transition class="fixed inset-0 z-[99999] flex items-center justify-center p-4 no-print" role="dialog" aria-modal="true">
            <div class="w-full max-w-md rounded-2xl border border-gray-200 bg-white p-5 shadow-2xl font-sans" @click.stop>
                <div class="flex items-start gap-3">
                    <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-full {{ $feedbackType === 'error' ? 'bg-red-50 text-red-600' : 'bg-[#fff7d6] text-gray-900' }}">
                        @if ($feedbackType === 'error')
                            <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M12 8v5M12 17h.01" stroke="currentColor" stroke-width="2" stroke-linecap="round"/><path d="M10.3 4.2 2.9 17a2 2 0 0 0 1.7 3h14.8a2 2 0 0 0 1.7-3L13.7 4.2a2 2 0 0 0-3.4 0Z" stroke="currentColor" stroke-width="1.8"/></svg>
                        @else
                            <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M9 12.5 11 14.5 15.5 9.5" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/><path d="M12 21a9 9 0 1 0 0-18 9 9 0 0 0 0 18Z" stroke="currentColor" stroke-width="1.8"/></svg>
                        @endif
                    </div>
                    <div class="min-w-0">
                        <h3 class="text-lg font-semibold text-gray-950">{{ $feedbackType === 'error' ? 'Action Needed' : 'Report Updated' }}</h3>
                        <p class="mt-1 text-sm leading-6 text-gray-600">{{ $feedbackMessage }}</p>
                    </div>
                </div>
                <div class="mt-6 flex justify-end">
                    <button type="button" @click="closeFeedback()" class="inline-flex h-10 items-center justify-center rounded-lg border border-gray-300 px-4 text-sm font-semibold text-gray-700 hover:bg-gray-50">Close</button>
                </div>
            </div>
        </div>
    @endif
</div>
</body>
</html>
