@props([
    'title' => 'Submitted School Report',
    'report' => [],
])

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $title }}</title>
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
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-height: 36px;
            border: 1px solid #d1d5db;
            border-radius: 8px;
            background: #ffffff;
            color: #111827;
            cursor: pointer;
            font-size: 13px;
            font-weight: 600;
            padding: 0 12px;
            text-decoration: none;
            transition: background-color .18s ease, border-color .18s ease, color .18s ease;
        }

        .toolbar a:hover,
        .toolbar button:hover {
            background: #f9fafb;
        }

        .toolbar .primary {
            background: #ffca03;
            color: #ffffff;
            border-color: #ffca03;
        }

        .toolbar .primary:hover {
            background: #2c3e50;
            border-color: #2c3e50;
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

        .report-table .name {
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

            .toolbar {
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
        <a href="{{ route('district-supervisor.reports') }}">Back to Reports</a>
        <button type="button" class="primary" onclick="window.print()">Print / Save as PDF</button>
    </div>

    <main class="sheet">
        <header class="header">
            <p class="republic">Republic of the Philippines</p>
            <p class="department">Department of Education</p>
            <p class="region">{{ $report['region_label'] ?? 'Region IV-CALABARZON' }}</p>
            <p class="division">{{ $report['division_label'] ?? 'Schools Division of Batangas' }}</p>
            <p class="district">{{ $report['district_name'] ?? 'DISTRICT' }}</p>
            <hr class="office-line">
        </header>

        <section class="report-title">
            <div>{{ $report['district_name'] ?? 'DISTRICT' }}</div>
            <div>ORAL READING AND COMPREHENSION ASSESSMENT RESULT IN {{ $report['report_language_title'] ?? 'ENGLISH' }}</div>
            <div>{{ $report['grade_report_label'] ?? 'GRADE' }}</div>
            <div>{{ $report['quarter_report_label'] ?? 'QUARTER' }} {{ $report['school_year_report_label'] ?? '' }}</div>
        </section>

        <section class="report-info">
            <div>
                <span class="label">Principal Name:</span>
                <span class="value">{{ $report['principal_name'] ?? 'Principal' }}</span>
            </div>
            <div>
                <span class="label">School:</span>
                <span class="value">{{ $report['school_name'] ?? 'School' }}</span>
            </div>
        </section>

        <table class="report-table">
            <thead>
                <tr>
                    <th rowspan="2" class="name">Section</th>
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
                @forelse ($report['section_rows'] ?? [] as $row)
                    @php($summary = $row['summary'] ?? [])
                    <tr>
                        <td class="name">{{ $row['section_name'] ?? 'Section' }}</td>
                        <td>{{ $summary['male'] ?? 0 }}</td>
                        <td>{{ $summary['female'] ?? 0 }}</td>
                        <td>{{ $summary['passage']['Non-Reader'] ?? 0 }}</td>
                        <td>{{ $summary['passage']['Struggling'] ?? 0 }}</td>
                        <td>{{ $summary['passage']['Slow'] ?? 0 }}</td>
                        <td>{{ $summary['passage']['Average'] ?? 0 }}</td>
                        <td>{{ $summary['passage']['Fast'] ?? 0 }}</td>
                        <td>{{ $summary['comprehension']['Independent'] ?? 0 }}</td>
                        <td>{{ $summary['comprehension']['Instructional'] ?? 0 }}</td>
                        <td>{{ $summary['comprehension']['Frustrated'] ?? 0 }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="11">No section reports found for this submitted school report.</td>
                    </tr>
                @endforelse

                @php($total = $report['summary'] ?? [])
                <tr class="summary-row">
                    <td class="name">TOTAL</td>
                    <td>{{ $total['male'] ?? 0 }}</td>
                    <td>{{ $total['female'] ?? 0 }}</td>
                    <td>{{ $total['passage']['Non-Reader'] ?? 0 }}</td>
                    <td>{{ $total['passage']['Struggling'] ?? 0 }}</td>
                    <td>{{ $total['passage']['Slow'] ?? 0 }}</td>
                    <td>{{ $total['passage']['Average'] ?? 0 }}</td>
                    <td>{{ $total['passage']['Fast'] ?? 0 }}</td>
                    <td>{{ $total['comprehension']['Independent'] ?? 0 }}</td>
                    <td>{{ $total['comprehension']['Instructional'] ?? 0 }}</td>
                    <td>{{ $total['comprehension']['Frustrated'] ?? 0 }}</td>
                </tr>
            </tbody>
        </table>

        <footer class="footer">
            <div class="tagline">&quot;Nurturing with Discipline, Trust, and Respect&quot;</div>
            <div class="footer-details">
                <div>Address: {{ $report['school_address'] ?? 'Not set' }}</div>
                <div>Contact: {{ $report['school_contact'] ?? 'Not set' }}</div>
                <div>Email: {{ $report['school_email'] ?? 'Not set' }}</div>
            </div>
        </footer>
    </main>
</body>
</html>
