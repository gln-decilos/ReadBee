@php
    $passageColumns = ['Non-Reader', 'Struggling', 'Slow', 'Average', 'Fast'];
    $comprehensionColumns = ['Independent', 'Instructional', 'Frustrated'];
@endphp
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $report['print_title'] }}</title>
    <style>
        @page { size: 13in 8.5in landscape; margin: 0.35in; }
        * { box-sizing: border-box; }
        body {
            margin: 0;
            background: #f3f4f6;
            color: #000000;
            font-family: "Times New Roman", Times, serif;
        }
        .toolbar {
            position: sticky;
            top: 0;
            z-index: 10;
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            justify-content: center;
            padding: 14px;
            background: #ffffff;
            border-bottom: 1px solid #d1d5db;
            font-family: Arial, Helvetica, sans-serif;
        }
        .toolbar a,
        .toolbar button {
            border: 1px solid #d1d5db;
            border-radius: 8px;
            background: #ffffff;
            color: #111827;
            cursor: pointer;
            font-size: 13px;
            font-weight: 600;
            padding: 8px 12px;
            text-decoration: none;
        }
        .toolbar .primary {
            border-color: #f2c94c;
            background: #f2c94c;
            color: #ffffff;
        }
        .toolbar .primary:hover { background: #e5bd42; }
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
        .header { text-align: center; line-height: 1.08; }
        .header p { margin: 0; }
        .header .republic { font-size: 11pt; }
        .header .department { font-size: 14pt; font-weight: 700; }
        .header .region,
        .header .division,
        .header .district { font-size: 10pt; }
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
        .report-info {
            margin-top: 9pt;
            text-align: left;
            font-size: 10pt;
            line-height: 1.45;
        }
        .report-info .label { font-weight: 700; }
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
            border: 1px solid #111827;
            padding: 3pt 2pt;
            text-align: center;
            vertical-align: middle;
            word-wrap: break-word;
        }
        .report-table th { font-weight: 700; }
        .report-table .pupil-name { text-align: left; width: 2.85in; padding-left: 5pt; }
        .check { font-weight: 700; font-size: 10pt; }
        .summary-row td { font-weight: 700; }
        .footer {
            margin-top: auto;
            padding-top: 10pt;
            text-align: center;
            font-size: 9pt;
            line-height: 1.35;
        }
        .footer-rule { border-top: 1pt solid #000000; margin-bottom: 6pt; }
        .footer .tagline { font-style: italic; font-weight: 700; margin-bottom: 4pt; }
        .footer-contact { text-align: left; }
        @media print {
            body { background: #ffffff; }
            .toolbar,
            .notice { display: none !important; }
            .sheet { margin: 0; box-shadow: none; width: auto; min-height: auto; padding: 0; }
        }
    </style>
</head>
<body>
    <div class="toolbar">
        <a href="{{ route('principal.reports', ['year_id' => $report['year_id']]) }}">Back to Reports</a>
        <button type="button" onclick="window.print()" class="primary">Print / Save as PDF</button>
    </div>

    <div class="notice">
        This is the submitted class report preview from {{ $report['evaluator_name'] ?? 'Evaluator' }}. The consolidated report is generated separately for district submission.
    </div>

    <main class="sheet">
        <header class="header">
            <p class="republic">Republic of the Philippines</p>
            <p class="department">Department of Education</p>
            <p class="region">{{ $report['region_label'] }}</p>
            <p class="division">{{ $report['division_label'] }}</p>
            <p class="district">{{ $report['district_name'] }}</p>
            <hr class="office-line">
            <p class="district">{{ $report['district_name'] }}</p>
        </header>

        <section class="report-title">
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
                @forelse ($report['rows'] as $row)
                    <tr>
                        <td class="pupil-name">{{ $row['pupil_name'] }}</td>
                        <td>{!! $row['sex'] === 'M' ? '<span class="check">✓</span>' : '' !!}</td>
                        <td>{!! $row['sex'] === 'F' ? '<span class="check">✓</span>' : '' !!}</td>
                        @foreach ($passageColumns as $column)
                            <td>{!! ($row['passage_category'] ?? null) === $column ? '<span class="check">✓</span>' : '' !!}</td>
                        @endforeach
                        @foreach ($comprehensionColumns as $column)
                            <td>{!! ($row['comprehension_category'] ?? null) === $column ? '<span class="check">✓</span>' : '' !!}</td>
                        @endforeach
                    </tr>
                @empty
                    <tr>
                        <td colspan="11">No pupil rows found in this submitted report.</td>
                    </tr>
                @endforelse
                <tr class="summary-row">
                    <td class="pupil-name">TOTAL</td>
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

        <footer class="footer">
            <div class="footer-rule"></div>
            <div class="tagline">&quot;Nurturing with Discipline, Trust, and Respect&quot;</div>
            <div class="footer-contact">
                <div>Address: {{ $report['school_address'] }}</div>
                <div>Contact: {{ $report['school_contact'] }}</div>
                <div>Email: {{ $report['school_email'] }}</div>
            </div>
        </footer>
    </main>
</body>
</html>
