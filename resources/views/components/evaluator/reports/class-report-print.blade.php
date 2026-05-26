<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $report['print_title'] }}</title>
    <link rel="icon" type="image/png" href="{{ asset('landing-assets/images/ReadBeefavicon.png') }}">
    <link rel="shortcut icon" href="{{ asset('landing-assets/images/ReadBeefavicon.png') }}">

    <style>
        @page {
            size: 13in 8.5in;
            margin: 0.35in;
        }

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            background: #f3f4f6;
            color: #000000;
            font-family: "Times New Roman", Times, serif;
        }

        .toolbar {
            display: flex;
            gap: 10px;
            justify-content: center;
            padding: 16px;
            background: #ffffff;
            border-bottom: 1px solid #d1d5db;
            position: sticky;
            top: 0;
            z-index: 10;
            font-family: Arial, Helvetica, sans-serif;
        }

        .toolbar a,
        .toolbar button {
            border: 1px solid #d1d5db;
            border-radius: 8px;
            background: #ffffff;
            color: #111827;
            cursor: pointer;
            font-size: 14px;
            font-weight: 600;
            padding: 10px 14px;
            text-decoration: none;
        }

        .toolbar .primary {
            background: #111827;
            color: #ffffff;
            border-color: #111827;
        }

        .toolbar button:disabled {
            cursor: not-allowed;
            opacity: 0.55;
        }

        .notice {
            max-width: 13in;
            margin: 12px auto 0;
            border: 1px solid #d1d5db;
            border-radius: 10px;
            background: #ffffff;
            color: #374151;
            padding: 10px 14px;
            font-family: Arial, Helvetica, sans-serif;
            font-size: 13px;
        }

        .notice.success {
            border-color: #86efac;
            background: #f0fdf4;
            color: #166534;
        }

        .notice.error {
            border-color: #fecaca;
            background: #fef2f2;
            color: #991b1b;
        }

        .sheet {
            width: 13in;
            min-height: 8.5in;
            margin: 14px auto;
            background: #ffffff;
            padding: 0.22in 0.30in;
            box-shadow: 0 20px 45px rgba(15, 23, 42, 0.15);
            display: flex;
            flex-direction: column;
        }

        .header {
            text-align: center;
            line-height: 1.08;
        }

        .header p {
            margin: 0;
        }

        .header .republic {
            font-size: 11pt;
        }

        .header .department {
            font-size: 14pt;
            font-weight: 700;
        }

        .header .region,
        .header .division,
        .header .district {
            font-size: 10pt;
        }

        .header .office-line {
            width: 100%;
            border: 0;
            border-top: 1pt solid #000000;
            margin: 7pt 0 6pt;
        }

        .report-title {
            text-align: center;
            line-height: 1.18;
            font-size: 12pt;
            font-weight: 700;
        }

        .report-title div {
            margin: 0;
        }

        .report-info {
            margin-top: 9pt;
            text-align: left;
            font-size: 10pt;
            line-height: 1.45;
        }

        .report-info .label {
            font-weight: 700;
        }

        .report-info .value {
            display: inline-block;
            min-width: 2.75in;
            border-bottom: 1pt solid #000000;
            padding: 0 4pt 1pt;
            font-weight: 400;
        }

        table.report-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10pt;
            table-layout: fixed;
            font-size: 8.2pt;
        }

        .report-table th,
        .report-table td {
            border: 1px solid #000000;
            padding: 3pt 2pt;
            text-align: center;
            vertical-align: middle;
            word-wrap: break-word;
        }

        .report-table th {
            font-weight: 700;
        }

        .report-table .pupil-name {
            text-align: left;
            width: 2.85in;
            padding-left: 5pt;
        }

        .report-table .narrow {
            width: 0.45in;
        }

        .report-table .level {
            width: 0.82in;
        }

        .check {
            font-weight: 700;
            font-size: 10pt;
        }

        .summary-row td {
            font-weight: 700;
        }

        .footer {
            margin-top: auto;
            border-top: 1pt solid #000000;
            padding-top: 5pt;
            font-size: 10pt;
            line-height: 1.22;
        }

        .footer .tagline {
            text-align: center;
            font-style: italic;
            font-weight: 700;
            margin-bottom: 4pt;
        }

        .footer .footer-details {
            text-align: left;
            font-size: 10pt;
        }

        @media print {
            body {
                background: #ffffff;
            }

            .toolbar,
            .notice {
                display: none !important;
            }

            .sheet {
                margin: 0;
                box-shadow: none;
                width: auto;
                min-height: calc(8.5in - 0.7in);
                padding: 0;
            }
        }
    </style>
</head>
<body>
    <div class="toolbar">
        <a href="{{ route('evaluator.reports', ['year_id' => $report['year_id']]) }}">Back to Reports</a>
        <button type="button" onclick="window.print()">Print / Save as PDF</button>
        <form method="POST" action="{{ route('evaluator.reports.submit', ['assignmentId' => $report['assignment_id'], 'language' => $report['language']]) }}">
            @csrf
            <button type="submit" class="primary" @disabled(! $report['is_ready'])>
                Submit to Principal
            </button>
        </form>
    </div>

    @if (session('success'))
        <div class="notice success">{{ session('success') }}</div>
    @endif

    @if (session('error'))
        <div class="notice error">{{ session('error') }}</div>
    @endif

    @unless ($report['is_ready'])
        <div class="notice">
            This {{ $report['language_label'] }} report is for preview only. {{ $report['summary']['missing'] }} pupil(s) still need an assessment record before submission.
        </div>
    @endunless

    <main class="sheet">
        <header class="header">
            <p class="republic">Republic of the Philippines</p>
            <p class="department">Department of Education</p>
            <p class="region">{{ $report['region_label'] }}</p>
            <p class="division">{{ $report['division_label'] }}</p>
            <p class="district">{{ $report['district_name'] }}</p>
            <hr class="office-line">
        </header>

        <section class="report-title">
            <div>{{ $report['district_name'] }}</div>
            <div>ORAL READING AND COMPREHENSION ASSESSMENT RESULT IN {{ $report['report_language_title'] }}</div>
            <div>{{ $report['grade_section_report_label'] }}</div>
            <div>{{ $report['quarter_report_label'] }} {{ $report['school_year_report_label'] }}</div>
        </section>

        <section class="report-info">
            <div>
                <span class="label">Evaluator Name:</span>
                <span class="value">{{ $report['evaluator_name'] ?? 'Evaluator' }}</span>
            </div>
            <div>
                <span class="label">Grade and Section:</span>
                <span class="value">{{ $report['grade_section_report_label'] }}</span>
            </div>
        </section>

        <table class="report-table">
            <thead>
                <tr>
                    <th rowspan="2" class="pupil-name">Pupils</th>
                    <th colspan="2">Sex</th>
                    <th colspan="5">Passage Level</th>
                    <th colspan="3">Comprehension Level</th>
                </tr>
                <tr>
                    <th class="narrow">M</th>
                    <th class="narrow">F</th>
                    <th class="level">Non-Reader</th>
                    <th class="level">Struggling</th>
                    <th class="level">Slow</th>
                    <th class="level">Average</th>
                    <th class="level">Fast</th>
                    <th class="level">Independent</th>
                    <th class="level">Instructional</th>
                    <th class="level">Frustrated</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($report['rows'] as $row)
                    <tr>
                        <td class="pupil-name">{{ $row['pupil_name'] }}</td>
                        <td class="check">{{ $row['sex'] === 'M' ? '✓' : '' }}</td>
                        <td class="check">{{ $row['sex'] === 'F' ? '✓' : '' }}</td>
                        <td class="check">{{ $row['passage_category'] === 'Non-Reader' ? '✓' : '' }}</td>
                        <td class="check">{{ $row['passage_category'] === 'Struggling' ? '✓' : '' }}</td>
                        <td class="check">{{ $row['passage_category'] === 'Slow' ? '✓' : '' }}</td>
                        <td class="check">{{ $row['passage_category'] === 'Average' ? '✓' : '' }}</td>
                        <td class="check">{{ $row['passage_category'] === 'Fast' ? '✓' : '' }}</td>
                        <td class="check">{{ $row['comprehension_category'] === 'Independent' ? '✓' : '' }}</td>
                        <td class="check">{{ $row['comprehension_category'] === 'Instructional' ? '✓' : '' }}</td>
                        <td class="check">{{ $row['comprehension_category'] === 'Frustrated' ? '✓' : '' }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="11">No pupils found for this assignment.</td>
                    </tr>
                @endforelse

                <tr class="summary-row">
                    <td class="pupil-name">TOTAL</td>
                    <td>{{ $report['summary']['male'] }}</td>
                    <td>{{ $report['summary']['female'] }}</td>
                    <td>{{ $report['summary']['passage']['Non-Reader'] }}</td>
                    <td>{{ $report['summary']['passage']['Struggling'] }}</td>
                    <td>{{ $report['summary']['passage']['Slow'] }}</td>
                    <td>{{ $report['summary']['passage']['Average'] }}</td>
                    <td>{{ $report['summary']['passage']['Fast'] }}</td>
                    <td>{{ $report['summary']['comprehension']['Independent'] }}</td>
                    <td>{{ $report['summary']['comprehension']['Instructional'] }}</td>
                    <td>{{ $report['summary']['comprehension']['Frustrated'] }}</td>
                </tr>
            </tbody>
        </table>

        <footer class="footer">
            <div class="tagline">&quot;Nurturing with Discipline, Trust, and Respect&quot;</div>
            <div class="footer-details">
                <div>Address: {{ $report['school_address'] ?: 'Not set' }}</div>
                <div>Contact: {{ $report['school_contact'] ?: 'Not set' }}</div>
                <div>Email: {{ $report['school_email'] ?: 'Not set' }}</div>
            </div>
        </footer>
    </main>
</body>
</html>
