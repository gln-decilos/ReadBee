<?php

namespace App\Http\Controllers\Evaluator;

use App\Helpers\EvaluatorMenuHelper;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class EvaluatorPupilsController extends Controller
{
    public function index(Request $request)
    {
        $menuGroups = EvaluatorMenuHelper::getMenuGroups();
        $schoolId = $this->principalSchoolId();

        if (! $schoolId) {
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'No confirmed grade/section assignment found for your evaluator account.',
                ], 403);
            }

            return redirect()
                ->route('evaluator.dashboard')
                ->with('error', 'No confirmed grade/section assignment found for your evaluator account.');
        }

        $schoolYears = $this->fetchSchoolYears();
        $selectedYearId = $request->query('year_id') ?: ($schoolYears[0]['year_id'] ?? null);
        $grades = $this->buildGradeSectionPupils($schoolId, $selectedYearId);

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json([
                'success' => true,
                'schoolYears' => $schoolYears,
                'selectedYearId' => $selectedYearId,
                'grades' => $grades,
            ]);
        }

        return view('pages.evaluator.evaluator-pupils', [
            'title' => 'Pupil Management',
            'menuGroups' => $menuGroups,
            'schoolYears' => $schoolYears,
            'selectedYearId' => $selectedYearId,
            'grades' => $grades,
            'page' => 1,
            'perPage' => 10,
        ]);
    }

    public function store(Request $request)
    {
        $schoolId = $this->principalSchoolId();

        if (! $schoolId) {
            return response()->json([
                'message' => 'No confirmed grade/section assignment found for your evaluator account.',
            ], 403);
        }

        $validated = $request->validate([
            'year_id' => 'required|uuid',
            'grade_level_id' => 'required|uuid',
            'section_id' => 'required|uuid',
            'lrn' => 'required|string|max:50',
            'full_name' => 'required|string|max:255',
            'sex' => 'nullable|in:M,F',
            'age' => 'nullable|integer|min:0|max:30',
            'guardian_name' => 'nullable|string|max:255',
            'guardian_contact' => 'nullable|string|max:100',
        ]);

        $section = $this->findValidSection(
            $schoolId,
            $validated['year_id'],
            $validated['grade_level_id'],
            $validated['section_id']
        );

        if (! $section) {
            return response()->json([
                'message' => 'The selected section is not assigned to your evaluator account for this school year.',
                'errors' => [
                    'section_id' => ['The selected section is not assigned to your evaluator account for this school year.'],
                ],
            ], 422);
        }

        if ($this->lrnExists($validated['lrn'])) {
            return response()->json([
                'message' => 'The LRN is already assigned to another pupil.',
                'errors' => [
                    'lrn' => ['The LRN is already assigned to another pupil.'],
                ],
            ], 422);
        }

        $payload = [
            'lrn' => trim($validated['lrn']),
            'full_name' => trim($validated['full_name']),
            'sex' => $validated['sex'] ?? null,
            'age' => $validated['age'] ?? null,
            'guardian_name' => $validated['guardian_name'] ?? null,
            'guardian_contact' => $validated['guardian_contact'] ?? null,
            'school_id' => $section['school_id'],
            'grade_level_id' => $validated['grade_level_id'],
            'section_id' => $validated['section_id'],
            'status' => 'enrolled',
        ];

        $response = Http::withHeaders(array_merge($this->supabaseHeaders(), [
            'Content-Type' => 'application/json',
            'Prefer' => 'return=representation',
        ]))->post($this->supabaseUrl() . '/rest/v1/pupils', $payload);

        if (! $response->successful()) {
            return response()->json([
                'message' => 'Failed to create pupil.',
                'error' => $response->json() ?: $response->body(),
            ], 500);
        }

        $pupil = $response->json()[0] ?? null;

        return response()->json([
            'message' => 'Pupil added successfully.',
            'pupil' => $this->formatPupil($pupil, $section),
        ]);
    }


    public function downloadImportTemplate()
    {
        $content = implode("\n", [
            'lrn,full_name,sex,age,guardian_name,guardian_contact',
            '123456789012,Juan Dela Cruz,M,9,Maria Dela Cruz,09123456789',
            '123456789013,Ana Santos,F,8,Jose Santos,09987654321',
        ]);

        return response($content, 200, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="pupils-import-template.csv"',
        ]);
    }

    public function previewImport(Request $request)
    {
        $schoolId = $this->principalSchoolId();

        if (! $schoolId) {
            return response()->json([
                'message' => 'No confirmed grade/section assignment found for your evaluator account.',
            ], 403);
        }

        $validated = $request->validate([
            'year_id' => 'required|uuid',
            'grade_level_id' => 'required|uuid',
            'section_id' => 'required|uuid',
            'csv_file' => 'required|file|mimes:csv,txt|max:10240',
        ]);

        $section = $this->findValidSection(
            $schoolId,
            $validated['year_id'],
            $validated['grade_level_id'],
            $validated['section_id']
        );

        if (! $section) {
            return response()->json([
                'message' => 'The selected section is not assigned to your evaluator account for this school year.',
                'errors' => [
                    'section_id' => ['The selected section is not assigned to your evaluator account for this school year.'],
                ],
            ], 422);
        }

        $parsed = $this->parseCsvFile($request->file('csv_file'));

        if (! empty($parsed['missing_headers'])) {
            return response()->json([
                'message' => 'The CSV file is missing required columns.',
                'errors' => [
                    'csv_file' => ['Missing columns: ' . implode(', ', $parsed['missing_headers'])],
                ],
            ], 422);
        }

        if (empty($parsed['rows'])) {
            return response()->json([
                'message' => 'The CSV file has no pupil rows.',
                'errors' => [
                    'csv_file' => ['The CSV file has no pupil rows.'],
                ],
            ], 422);
        }

        $validation = $this->validateImportRows($parsed['rows'], $schoolId);

        return response()->json([
            'success' => true,
            'message' => 'CSV file validated successfully.',
            'rows' => $validation['rows'],
            'summary' => $validation['summary'],
        ]);
    }

    public function commitImport(Request $request)
    {
        $schoolId = $this->principalSchoolId();

        if (! $schoolId) {
            return response()->json([
                'message' => 'No confirmed grade/section assignment found for your evaluator account.',
            ], 403);
        }

        $validated = $request->validate([
            'year_id' => 'required|uuid',
            'grade_level_id' => 'required|uuid',
            'section_id' => 'required|uuid',
            'rows' => 'required|array|min:1',
            'rows.*.lrn' => 'required|string|max:50',
            'rows.*.full_name' => 'required|string|max:255',
            'rows.*.sex' => 'nullable|in:M,F',
            'rows.*.age' => 'nullable|integer|min:0|max:30',
            'rows.*.guardian_name' => 'nullable|string|max:255',
            'rows.*.guardian_contact' => 'nullable|string|max:100',
        ]);

        $section = $this->findValidSection(
            $schoolId,
            $validated['year_id'],
            $validated['grade_level_id'],
            $validated['section_id']
        );

        if (! $section) {
            return response()->json([
                'message' => 'The selected section is not assigned to your evaluator account for this school year.',
                'errors' => [
                    'section_id' => ['The selected section is not assigned to your evaluator account for this school year.'],
                ],
            ], 422);
        }

        $validation = $this->validateImportRows($validated['rows'], $schoolId);
        $invalidRows = collect($validation['rows'])->where('valid', false)->values()->all();

        if (! empty($invalidRows)) {
            return response()->json([
                'message' => 'Some rows are no longer valid. Review the import preview again.',
                'rows' => $validation['rows'],
                'summary' => $validation['summary'],
            ], 422);
        }

        $payloads = collect($validation['rows'])
            ->map(function ($row) use ($section, $validated) {
                return [
                    'lrn' => $row['data']['lrn'],
                    'full_name' => $row['data']['full_name'],
                    'sex' => $row['data']['sex'] ?: null,
                    'age' => $row['data']['age'] !== null && $row['data']['age'] !== '' ? (int) $row['data']['age'] : null,
                    'guardian_name' => $row['data']['guardian_name'] ?: null,
                    'guardian_contact' => $row['data']['guardian_contact'] ?: null,
                    'school_id' => $section['school_id'],
                    'grade_level_id' => $validated['grade_level_id'],
                    'section_id' => $validated['section_id'],
                    'status' => 'enrolled',
                ];
            })
            ->values()
            ->all();

        $response = Http::withHeaders(array_merge($this->supabaseHeaders(), [
            'Content-Type' => 'application/json',
            'Prefer' => 'return=representation',
        ]))->post($this->supabaseUrl() . '/rest/v1/pupils', $payloads);

        if (! $response->successful()) {
            return response()->json([
                'message' => 'Failed to import pupils.',
                'error' => $response->json() ?: $response->body(),
            ], 500);
        }

        $pupils = collect($response->json())
            ->map(fn ($pupil) => $this->formatPupil($pupil, $section))
            ->values()
            ->all();

        return response()->json([
            'success' => true,
            'message' => count($pupils) . ' pupil(s) imported successfully.',
            'pupils' => $pupils,
        ]);
    }

    public function update(Request $request, string $pupilId)
    {
        $schoolId = $this->principalSchoolId();

        if (! $schoolId) {
            return response()->json([
                'message' => 'No confirmed grade/section assignment found for your evaluator account.',
            ], 403);
        }

        $existing = $this->findPupil($schoolId, $pupilId);

        if (! $existing) {
            return response()->json([
                'message' => 'Pupil not found in your assigned grade/section.',
            ], 404);
        }

        $validated = $request->validate([
            'year_id' => 'required|uuid',
            'grade_level_id' => 'required|uuid',
            'section_id' => 'required|uuid',
            'lrn' => 'required|string|max:50',
            'full_name' => 'required|string|max:255',
            'sex' => 'nullable|in:M,F',
            'age' => 'nullable|integer|min:0|max:30',
            'guardian_name' => 'nullable|string|max:255',
            'guardian_contact' => 'nullable|string|max:100',
        ]);

        $section = $this->findValidSection(
            $schoolId,
            $validated['year_id'],
            $validated['grade_level_id'],
            $validated['section_id']
        );

        if (! $section) {
            return response()->json([
                'message' => 'The selected section is not assigned to your evaluator account for this school year.',
                'errors' => [
                    'section_id' => ['The selected section is not assigned to your evaluator account for this school year.'],
                ],
            ], 422);
        }

        if ($this->lrnExists($validated['lrn'], $pupilId)) {
            return response()->json([
                'message' => 'The LRN is already assigned to another pupil.',
                'errors' => [
                    'lrn' => ['The LRN is already assigned to another pupil.'],
                ],
            ], 422);
        }

        $payload = [
            'lrn' => trim($validated['lrn']),
            'full_name' => trim($validated['full_name']),
            'sex' => $validated['sex'] ?? null,
            'age' => $validated['age'] ?? null,
            'guardian_name' => $validated['guardian_name'] ?? null,
            'guardian_contact' => $validated['guardian_contact'] ?? null,
            'grade_level_id' => $validated['grade_level_id'],
            'section_id' => $validated['section_id'],
        ];

        $response = Http::withHeaders(array_merge($this->supabaseHeaders(), [
            'Content-Type' => 'application/json',
            'Prefer' => 'return=representation',
        ]))->patch($this->supabaseUrl() . '/rest/v1/pupils?pupil_id=eq.' . $pupilId . '&school_id=eq.' . ($existing['school_id'] ?? $schoolId), $payload);

        if (! $response->successful()) {
            return response()->json([
                'message' => 'Failed to update pupil.',
                'error' => $response->json() ?: $response->body(),
            ], 500);
        }

        $pupil = $response->json()[0] ?? null;

        return response()->json([
            'message' => 'Pupil updated successfully.',
            'pupil' => $this->formatPupil($pupil, $section),
        ]);
    }

    public function markDropped(string $pupilId)
    {
        return $this->updatePupilStatus($pupilId, 'inactive', 'Pupil marked as dropped successfully.');
    }

    public function restore(string $pupilId)
    {
        return $this->updatePupilStatus($pupilId, 'enrolled', 'Pupil restored as enrolled successfully.');
    }

    public function transferSection(Request $request, string $pupilId)
    {
        $schoolId = $this->principalSchoolId();

        if (! $schoolId) {
            return response()->json([
                'message' => 'No confirmed grade/section assignment found for your evaluator account.',
            ], 403);
        }

        $existing = $this->findPupil($schoolId, $pupilId);

        if (! $existing) {
            return response()->json([
                'message' => 'Pupil not found in your assigned grade/section.',
            ], 404);
        }

        $validated = $request->validate([
            'year_id' => 'required|uuid',
            'grade_level_id' => 'required|uuid',
            'section_id' => 'required|uuid',
        ]);

        $section = $this->findValidSection(
            $schoolId,
            $validated['year_id'],
            $validated['grade_level_id'],
            $validated['section_id']
        );

        if (! $section) {
            return response()->json([
                'message' => 'The selected section is not assigned to your evaluator account for this school year.',
            ], 422);
        }

        $response = Http::withHeaders(array_merge($this->supabaseHeaders(), [
            'Content-Type' => 'application/json',
            'Prefer' => 'return=representation',
        ]))->patch($this->supabaseUrl() . '/rest/v1/pupils?pupil_id=eq.' . $pupilId . '&school_id=eq.' . ($existing['school_id'] ?? $schoolId), [
            'grade_level_id' => $validated['grade_level_id'],
            'section_id' => $validated['section_id'],
            'status' => 'enrolled',
        ]);

        if (! $response->successful()) {
            return response()->json([
                'message' => 'Failed to transfer pupil section.',
                'error' => $response->json() ?: $response->body(),
            ], 500);
        }

        $pupil = $response->json()[0] ?? null;

        return response()->json([
            'message' => 'Pupil moved to the selected section successfully.',
            'pupil' => $this->formatPupil($pupil, $section),
        ]);
    }

    public function delete(string $pupilId)
    {
        $schoolId = $this->principalSchoolId();

        if (! $schoolId) {
            return response()->json([
                'message' => 'No confirmed grade/section assignment found for your evaluator account.',
            ], 403);
        }

        $existing = $this->findPupil($schoolId, $pupilId, true);

        if (! $existing) {
            return response()->json([
                'message' => 'Pupil not found in your assigned grade/section.',
            ], 404);
        }

        $response = Http::withHeaders(array_merge($this->supabaseHeaders(), [
            'Prefer' => 'return=minimal',
        ]))->delete($this->supabaseUrl() . '/rest/v1/pupils?pupil_id=eq.' . $pupilId . '&school_id=eq.' . ($existing['school_id'] ?? $schoolId));

        if (! $response->successful()) {
            return response()->json([
                'message' => 'Failed to delete pupil. This pupil may already be referenced by other records. Use Archive instead if deletion is blocked.',
                'error' => $response->json() ?: $response->body(),
            ], 500);
        }

        return response()->json([
            'message' => 'Pupil deleted successfully.',
            'pupil_id' => $pupilId,
            'deleted_id' => $pupilId,
        ]);
    }

    public function bulkDelete(Request $request)
    {
        $schoolId = $this->principalSchoolId();

        if (! $schoolId) {
            return response()->json([
                'message' => 'No confirmed grade/section assignment found for your evaluator account.',
            ], 403);
        }

        $validated = $request->validate([
            'ids' => 'required|array|min:1',
            'ids.*' => 'required|uuid',
        ]);

        $ids = collect($validated['ids'])->filter()->unique()->values()->all();

        if (empty($ids)) {
            return response()->json([
                'message' => 'No pupils selected for deletion.',
            ], 422);
        }

        $sectionIds = $this->assignedSectionIds();

        if (empty($sectionIds)) {
            return response()->json([
                'message' => 'No confirmed grade/section assignment found for your evaluator account.',
            ], 403);
        }

        $existingResponse = Http::withHeaders($this->supabaseHeaders())
            ->get($this->supabaseUrl() . '/rest/v1/pupils', [
                'select' => 'pupil_id',
                'section_id' => 'in.(' . $this->postgrestInList($sectionIds) . ')',
                'pupil_id' => 'in.(' . $this->postgrestInList($ids) . ')',
            ]);

        if (! $existingResponse->successful()) {
            return response()->json([
                'message' => 'Failed to validate selected pupils before deletion.',
                'error' => $existingResponse->json() ?: $existingResponse->body(),
            ], 500);
        }

        $existingIds = collect($existingResponse->json())
            ->pluck('pupil_id')
            ->map(fn ($id) => (string) $id)
            ->values()
            ->all();

        if (count($existingIds) !== count($ids)) {
            return response()->json([
                'message' => 'One or more selected pupils were not found in your assigned grade/section.',
            ], 404);
        }

        $response = Http::withHeaders(array_merge($this->supabaseHeaders(), [
            'Prefer' => 'return=minimal',
        ]))->delete($this->supabaseUrl() . '/rest/v1/pupils?section_id=in.(' . $this->postgrestInList($sectionIds) . ')&pupil_id=in.(' . $this->postgrestInList($ids) . ')');

        if (! $response->successful()) {
            return response()->json([
                'message' => 'Failed to delete selected pupils. One or more pupils may already be referenced by other records. Use Archive instead if deletion is blocked.',
                'error' => $response->json() ?: $response->body(),
            ], 500);
        }

        return response()->json([
            'message' => 'Selected pupils deleted successfully.',
            'deleted_ids' => $ids,
        ]);
    }


    public function archive(string $pupilId)
    {
        return $this->updatePupilStatus($pupilId, 'archived', 'Pupil archived successfully.', true);
    }


    private function parseCsvFile($file): array
    {
        $requiredHeaders = ['lrn', 'full_name', 'sex', 'age', 'guardian_name', 'guardian_contact'];
        $rows = [];

        $handle = fopen($file->getRealPath(), 'r');

        if (! $handle) {
            return [
                'headers' => [],
                'missing_headers' => $requiredHeaders,
                'rows' => [],
            ];
        }

        $rawHeaders = fgetcsv($handle);

        if (! $rawHeaders) {
            fclose($handle);

            return [
                'headers' => [],
                'missing_headers' => $requiredHeaders,
                'rows' => [],
            ];
        }

        $headers = collect($rawHeaders)
            ->map(fn ($header) => $this->normalizeCsvHeader((string) $header))
            ->values()
            ->all();

        $missingHeaders = collect($requiredHeaders)
            ->reject(fn ($header) => in_array($header, $headers, true))
            ->values()
            ->all();

        $rowNumber = 1;

        while (($line = fgetcsv($handle)) !== false) {
            $rowNumber++;

            if (collect($line)->every(fn ($value) => trim((string) $value) === '')) {
                continue;
            }

            $row = [
                '_row_number' => $rowNumber,
            ];

            foreach ($headers as $index => $header) {
                if (! $header) {
                    continue;
                }

                $row[$header] = trim((string) ($line[$index] ?? ''));
            }

            $rows[] = $row;
        }

        fclose($handle);

        return [
            'headers' => $headers,
            'missing_headers' => $missingHeaders,
            'rows' => $rows,
        ];
    }

    private function normalizeCsvHeader(string $header): string
    {
        $header = strtolower(trim($header));
        $header = preg_replace('/[^a-z0-9]+/', '_', $header);

        return trim($header, '_');
    }

    private function validateImportRows(array $rows, string $schoolId): array
    {
        $normalizedRows = collect($rows)
            ->map(function ($row, $index) {
                return [
                    'row_number' => (int) ($row['_row_number'] ?? $row['row_number'] ?? ($index + 2)),
                    'lrn' => trim((string) ($row['lrn'] ?? '')),
                    'full_name' => trim((string) ($row['full_name'] ?? '')),
                    'sex' => strtoupper(trim((string) ($row['sex'] ?? ''))),
                    'age' => trim((string) ($row['age'] ?? '')),
                    'guardian_name' => trim((string) ($row['guardian_name'] ?? '')),
                    'guardian_contact' => trim((string) ($row['guardian_contact'] ?? '')),
                ];
            })
            ->values();

        $lrnCounts = $normalizedRows
            ->pluck('lrn')
            ->filter()
            ->countBy();

        $existingLrns = $this->existingLrns($normalizedRows->pluck('lrn')->filter()->unique()->values()->all(), $schoolId);

        $validatedRows = $normalizedRows->map(function ($row) use ($lrnCounts, $existingLrns) {
            $errors = [];

            if ($row['lrn'] === '') {
                $errors[] = 'LRN is required.';
            }

            if ($row['full_name'] === '') {
                $errors[] = 'Full name is required.';
            }

            if ($row['sex'] !== '' && ! in_array($row['sex'], ['M', 'F'], true)) {
                $errors[] = 'Sex must be M or F.';
            }

            if ($row['age'] !== '' && (! ctype_digit($row['age']) || (int) $row['age'] < 0 || (int) $row['age'] > 30)) {
                $errors[] = 'Age must be a number from 0 to 30.';
            }

            if ($row['lrn'] !== '' && (int) ($lrnCounts[$row['lrn']] ?? 0) > 1) {
                $errors[] = 'Duplicate LRN in this CSV file.';
            }

            if ($row['lrn'] !== '' && in_array($row['lrn'], $existingLrns, true)) {
                $errors[] = 'LRN already exists in the pupil records.';
            }

            $data = [
                'lrn' => $row['lrn'],
                'full_name' => $row['full_name'],
                'sex' => $row['sex'] ?: null,
                'age' => $row['age'] !== '' ? (int) $row['age'] : null,
                'guardian_name' => $row['guardian_name'] ?: null,
                'guardian_contact' => $row['guardian_contact'] ?: null,
            ];

            return [
                'row_number' => $row['row_number'],
                'lrn' => $row['lrn'],
                'full_name' => $row['full_name'],
                'sex' => $row['sex'],
                'age' => $row['age'],
                'guardian_name' => $row['guardian_name'],
                'guardian_contact' => $row['guardian_contact'],
                'valid' => empty($errors),
                'errors' => $errors,
                'data' => $data,
            ];
        })->values()->all();

        return [
            'rows' => $validatedRows,
            'summary' => [
                'total' => count($validatedRows),
                'valid' => collect($validatedRows)->where('valid', true)->count(),
                'invalid' => collect($validatedRows)->where('valid', false)->count(),
            ],
        ];
    }

    private function existingLrns(array $lrns, string $schoolId): array
    {
        $lrns = collect($lrns)->filter()->unique()->values()->all();

        if (empty($lrns)) {
            return [];
        }

        $response = Http::withHeaders($this->supabaseHeaders())
            ->get($this->supabaseUrl() . '/rest/v1/pupils', [
                'select' => 'lrn',
                'lrn' => 'in.(' . $this->postgrestInList($lrns) . ')',
            ]);

        if (! $response->successful()) {
            report('Failed to validate existing pupil LRNs: ' . $response->body());
            return $lrns;
        }

        return collect($response->json())
            ->pluck('lrn')
            ->map(fn ($lrn) => trim((string) $lrn))
            ->filter()
            ->values()
            ->all();
    }

    private function updatePupilStatus(string $pupilId, string $status, string $successMessage, bool $removeFromList = false)
    {
        $schoolId = $this->principalSchoolId();

        if (! $schoolId) {
            return response()->json([
                'message' => 'No confirmed grade/section assignment found for your evaluator account.',
            ], 403);
        }

        $existing = $this->findPupil($schoolId, $pupilId, true);

        if (! $existing) {
            return response()->json([
                'message' => 'Pupil not found in your assigned grade/section.',
            ], 404);
        }

        $response = Http::withHeaders(array_merge($this->supabaseHeaders(), [
            'Content-Type' => 'application/json',
            'Prefer' => 'return=representation',
        ]))->patch($this->supabaseUrl() . '/rest/v1/pupils?pupil_id=eq.' . $pupilId . '&school_id=eq.' . ($existing['school_id'] ?? $schoolId), [
            'status' => $status,
        ]);

        if (! $response->successful()) {
            return response()->json([
                'message' => 'Failed to update pupil status.',
                'error' => $response->json() ?: $response->body(),
            ], 500);
        }

        $pupil = $response->json()[0] ?? null;
        $section = null;

        if (! empty($pupil['section_id'])) {
            $section = $this->findSectionById($schoolId, $pupil['section_id']);
        }

        return response()->json([
            'message' => $successMessage,
            'remove' => $removeFromList,
            'pupil' => $this->formatPupil($pupil, $section),
        ]);
    }

    private function buildGradeSectionPupils(string $schoolId, ?string $selectedYearId): array
    {
        $sections = $selectedYearId ? $this->fetchSections($schoolId, $selectedYearId) : [];
        $sectionIds = collect($sections)->pluck('section_id')->filter()->values()->all();
        $pupils = $this->fetchPupils($schoolId, $sectionIds);

        $gradeIds = collect($sections)->pluck('grade_level_id')->filter()->unique()->values()->all();
        $grades = $this->fetchGradeLevelsByIds($gradeIds);

        $sectionsByGrade = collect($sections)->groupBy('grade_level_id');
        $pupilsBySection = collect($pupils)->groupBy('section_id');

        return collect($grades)->map(function ($grade) use ($sectionsByGrade, $pupilsBySection) {
            $gradeSections = collect($sectionsByGrade->get($grade['grade_level_id'], []))
                ->map(function ($section) use ($pupilsBySection, $grade) {
                    $sectionPupils = collect($pupilsBySection->get($section['section_id'], []))
                        ->map(fn ($pupil) => $this->formatPupil($pupil, $section, $grade))
                        ->values()
                        ->all();

                    return [
                        'section_id' => $section['section_id'],
                        'section_name' => $section['section_name'],
                        'grade_level_id' => $section['grade_level_id'],
                        'grade_number' => $grade['grade_number'],
                        'year_id' => $section['year_id'],
                        'status' => $section['status'],
                        'adviser_name' => $section['adviser_name'] ?? null,
                        'pupils' => $sectionPupils,
                        'pupil_count' => count($sectionPupils),
                        'enrolled_count' => collect($sectionPupils)->where('status', 'enrolled')->count(),
                        'dropped_count' => collect($sectionPupils)->where('status', 'inactive')->count(),
                    ];
                })
                ->sortBy('section_name')
                ->values()
                ->all();

            return [
                'grade_level_id' => $grade['grade_level_id'],
                'grade_number' => $grade['grade_number'],
                'school_id' => $grade['school_id'],
                'sections' => $gradeSections,
                'section_count' => count($gradeSections),
                'enrolled_pupils_count' => collect($gradeSections)->sum('enrolled_count'),
                'dropped_pupils_count' => collect($gradeSections)->sum('dropped_count'),
                'total_pupils_count' => collect($gradeSections)->sum('pupil_count'),
            ];
        })->values()->all();
    }


    private function fetchSchoolYears(): array
    {
        $evaluatorId = $this->currentEvaluatorId();

        if (! $evaluatorId) {
            return [];
        }

        $assignmentResponse = Http::withHeaders($this->supabaseHeaders())
            ->get($this->supabaseUrl() . '/rest/v1/assigned_evaluators', [
                'select' => 'year_id',
                'evaluator_user_id' => 'eq.' . $evaluatorId,
                'confirmation_status' => 'eq.confirmed',
                'order' => 'assessment_date.desc',
            ]);

        if (! $assignmentResponse->successful()) {
            report('Failed to fetch evaluator assigned school years: ' . $assignmentResponse->body());
            return [];
        }

        $yearIds = collect($assignmentResponse->json())
            ->pluck('year_id')
            ->filter()
            ->unique()
            ->values()
            ->all();

        if (empty($yearIds)) {
            return [];
        }

        $response = Http::withHeaders($this->supabaseHeaders())
            ->get($this->supabaseUrl() . '/rest/v1/school_year', [
                'select' => 'year_id,start_date,end_date,created_at',
                'year_id' => 'in.(' . $this->postgrestInList($yearIds) . ')',
                'order' => 'start_date.desc',
            ]);

        if (! $response->successful()) {
            report('Failed to fetch evaluator school years: ' . $response->body());
            return [];
        }

        return $response->json();
    }


    private function fetchGradeLevels(string $schoolId): array
    {
        $response = Http::withHeaders($this->supabaseHeaders())
            ->get($this->supabaseUrl() . '/rest/v1/grade_levels', [
                'select' => 'grade_level_id,grade_number,school_id,is_active',
                'school_id' => 'eq.' . $schoolId,
                'is_active' => 'eq.true',
                'order' => 'grade_number.asc',
            ]);

        if (! $response->successful()) {
            report('Failed to fetch principal grade levels: ' . $response->body());
            return [];
        }

        return $response->json();
    }

    private function fetchSections(string $schoolId, string $yearId): array
    {
        $sectionIds = $this->assignedSectionIds($yearId);

        if (empty($sectionIds)) {
            return [];
        }

        $response = Http::withHeaders($this->supabaseHeaders())
            ->get($this->supabaseUrl() . '/rest/v1/class_sections', [
                'select' => 'section_id,section_name,school_id,year_id,grade_level_id,status,adviser_name,created_at,updated_at',
                'section_id' => 'in.(' . $this->postgrestInList($sectionIds) . ')',
                'year_id' => 'eq.' . $yearId,
                'status' => 'neq.archived',
                'order' => 'section_name.asc',
            ]);

        if (! $response->successful()) {
            report('Failed to fetch evaluator assigned sections for pupils: ' . $response->body());
            return [];
        }

        return $response->json();
    }


    private function fetchPupils(string $schoolId, array $sectionIds): array
    {
        $sectionIds = collect($sectionIds)
            ->intersect($this->assignedSectionIds())
            ->values()
            ->all();

        if (empty($sectionIds)) {
            return [];
        }

        $response = Http::withHeaders($this->supabaseHeaders())
            ->get($this->supabaseUrl() . '/rest/v1/pupils', [
                'select' => 'pupil_id,created_at,updated_at,lrn,full_name,sex,age,guardian_name,guardian_contact,school_id,section_id,grade_level_id,status',
                'section_id' => 'in.(' . $this->postgrestInList($sectionIds) . ')',
                'status' => 'neq.archived',
                'order' => 'full_name.asc',
            ]);

        if (! $response->successful()) {
            report('Failed to fetch evaluator assigned pupils: ' . $response->body());
            return [];
        }

        return $response->json();
    }


    private function findValidSection(string $schoolId, string $yearId, string $gradeLevelId, string $sectionId): ?array
    {
        if (! in_array($sectionId, $this->assignedSectionIds($yearId), true)) {
            return null;
        }

        $response = Http::withHeaders($this->supabaseHeaders())
            ->get($this->supabaseUrl() . '/rest/v1/class_sections', [
                'select' => 'section_id,section_name,school_id,year_id,grade_level_id,status,adviser_name',
                'year_id' => 'eq.' . $yearId,
                'grade_level_id' => 'eq.' . $gradeLevelId,
                'section_id' => 'eq.' . $sectionId,
                'status' => 'neq.archived',
                'limit' => 1,
            ]);

        if (! $response->successful()) {
            report('Failed to validate evaluator assigned section: ' . $response->body());
            return null;
        }

        return $response->json()[0] ?? null;
    }


    private function findSectionById(string $schoolId, string $sectionId): ?array
    {
        if (! in_array($sectionId, $this->assignedSectionIds(), true)) {
            return null;
        }

        $response = Http::withHeaders($this->supabaseHeaders())
            ->get($this->supabaseUrl() . '/rest/v1/class_sections', [
                'select' => 'section_id,section_name,school_id,year_id,grade_level_id,status,adviser_name',
                'section_id' => 'eq.' . $sectionId,
                'limit' => 1,
            ]);

        if (! $response->successful()) {
            report('Failed to fetch evaluator assigned section by id: ' . $response->body());
            return null;
        }

        return $response->json()[0] ?? null;
    }


    private function findPupil(string $schoolId, string $pupilId, bool $includeArchived = false): ?array
    {
        $sectionIds = $this->assignedSectionIds();

        if (empty($sectionIds)) {
            return null;
        }

        $query = [
            'select' => 'pupil_id,created_at,updated_at,lrn,full_name,sex,age,guardian_name,guardian_contact,school_id,section_id,grade_level_id,status',
            'section_id' => 'in.(' . $this->postgrestInList($sectionIds) . ')',
            'pupil_id' => 'eq.' . $pupilId,
            'limit' => 1,
        ];

        if (! $includeArchived) {
            $query['status'] = 'neq.archived';
        }

        $response = Http::withHeaders($this->supabaseHeaders())
            ->get($this->supabaseUrl() . '/rest/v1/pupils', $query);

        if (! $response->successful()) {
            report('Failed to fetch evaluator assigned pupil: ' . $response->body());
            return null;
        }

        return $response->json()[0] ?? null;
    }


    private function lrnExists(string $lrn, ?string $exceptPupilId = null): bool
    {
        $query = [
            'select' => 'pupil_id',
            'lrn' => 'eq.' . trim($lrn),
            'limit' => 1,
        ];

        $response = Http::withHeaders($this->supabaseHeaders())
            ->get($this->supabaseUrl() . '/rest/v1/pupils', $query);

        if (! $response->successful()) {
            report('Failed to validate pupil LRN: ' . $response->body());
            return true;
        }

        $existing = $response->json()[0] ?? null;

        if (! $existing) {
            return false;
        }

        if ($exceptPupilId && (string) $existing['pupil_id'] === (string) $exceptPupilId) {
            return false;
        }

        return true;
    }

    private function formatPupil(?array $pupil, ?array $section = null, ?array $grade = null): ?array
    {
        if (! $pupil) {
            return null;
        }

        return [
            'pupil_id' => $pupil['pupil_id'],
            'lrn' => $pupil['lrn'],
            'full_name' => $pupil['full_name'],
            'sex' => $pupil['sex'] ?? null,
            'age' => $pupil['age'] ?? null,
            'guardian_name' => $pupil['guardian_name'] ?? null,
            'guardian_contact' => $pupil['guardian_contact'] ?? null,
            'school_id' => $pupil['school_id'],
            'grade_level_id' => $pupil['grade_level_id'] ?? ($grade['grade_level_id'] ?? null),
            'grade_number' => $grade['grade_number'] ?? null,
            'section_id' => $pupil['section_id'] ?? ($section['section_id'] ?? null),
            'section_name' => $section['section_name'] ?? null,
            'status' => $pupil['status'],
            'status_label' => $this->statusLabel($pupil['status']),
            'created_at' => $pupil['created_at'] ?? null,
            'updated_at' => $pupil['updated_at'] ?? null,
        ];
    }

    private function statusLabel(string $status): string
    {
        return match ($status) {
            'enrolled' => 'Enrolled',
            'inactive' => 'Dropped',
            'transferred' => 'Transferred',
            'archived' => 'Archived',
            default => ucfirst($status),
        };
    }

    private function principalSchoolId(): ?string
    {
        return $this->evaluatorAssignedSchoolId();
    }


    private function evaluatorAssignedSchoolId(): ?string
    {
        $sections = $this->fetchAssignedSections();

        return collect($sections)->pluck('school_id')->filter()->first();
    }

    private function currentEvaluatorId(): ?string
    {
        return session('supabase_user.id');
    }

    private function assignedSectionIds(?string $yearId = null): array
    {
        return collect($this->fetchAssignedSections($yearId))
            ->pluck('section_id')
            ->filter()
            ->unique()
            ->values()
            ->all();
    }

    private function fetchAssignedSections(?string $yearId = null): array
    {
        $evaluatorId = $this->currentEvaluatorId();

        if (! $evaluatorId) {
            return [];
        }

        $query = [
            'select' => 'assignment_id,section_id,year_id,confirmation_status,assessment_status,report_status',
            'evaluator_user_id' => 'eq.' . $evaluatorId,
            'confirmation_status' => 'eq.confirmed',
            'order' => 'assessment_date.asc',
        ];

        if ($yearId) {
            $query['year_id'] = 'eq.' . $yearId;
        }

        $assignmentResponse = Http::withHeaders($this->supabaseHeaders())
            ->get($this->supabaseUrl() . '/rest/v1/assigned_evaluators', $query);

        if (! $assignmentResponse->successful()) {
            report('Failed to fetch evaluator pupil assignment scope: ' . $assignmentResponse->body());
            return [];
        }

        $sectionIds = collect($assignmentResponse->json())
            ->pluck('section_id')
            ->filter()
            ->unique()
            ->values()
            ->all();

        if (empty($sectionIds)) {
            return [];
        }

        $sectionQuery = [
            'select' => 'section_id,section_name,school_id,year_id,grade_level_id,status,adviser_name,created_at,updated_at',
            'section_id' => 'in.(' . $this->postgrestInList($sectionIds) . ')',
            'status' => 'neq.archived',
            'order' => 'section_name.asc',
        ];

        if ($yearId) {
            $sectionQuery['year_id'] = 'eq.' . $yearId;
        }

        $sectionResponse = Http::withHeaders($this->supabaseHeaders())
            ->get($this->supabaseUrl() . '/rest/v1/class_sections', $sectionQuery);

        if (! $sectionResponse->successful()) {
            report('Failed to fetch evaluator assigned class sections: ' . $sectionResponse->body());
            return [];
        }

        return $sectionResponse->json();
    }

    private function fetchGradeLevelsByIds(array $gradeLevelIds): array
    {
        $gradeLevelIds = collect($gradeLevelIds)->filter()->unique()->values()->all();

        if (empty($gradeLevelIds)) {
            return [];
        }

        $response = Http::withHeaders($this->supabaseHeaders())
            ->get($this->supabaseUrl() . '/rest/v1/grade_levels', [
                'select' => 'grade_level_id,grade_number,school_id,is_active',
                'grade_level_id' => 'in.(' . $this->postgrestInList($gradeLevelIds) . ')',
                'is_active' => 'eq.true',
                'order' => 'grade_number.asc',
            ]);

        if (! $response->successful()) {
            report('Failed to fetch evaluator assigned grade levels: ' . $response->body());
            return [];
        }

        return $response->json();
    }

    private function postgrestInList(array $ids): string
    {
        return collect($ids)
            ->filter()
            ->map(function ($id) {
                return '"' . str_replace('"', '\\"', (string) $id) . '"';
            })
            ->implode(',');
    }

    private function supabaseUrl(): string
    {
        return rtrim(env('SUPABASE_URL'), '/');
    }

    private function supabaseHeaders(): array
    {
        return [
            'apikey' => env('SUPABASE_SERVICE_ROLE_KEY'),
            'Authorization' => 'Bearer ' . env('SUPABASE_SERVICE_ROLE_KEY'),
            'Accept' => 'application/json',
        ];
    }
}
