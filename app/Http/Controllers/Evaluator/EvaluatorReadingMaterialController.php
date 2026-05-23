<?php

namespace App\Http\Controllers\Evaluator;

use App\Helpers\EvaluatorMenuHelper;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class EvaluatorReadingMaterialController extends Controller
{
    public function index(Request $request)
    {
        $menuGroups = EvaluatorMenuHelper::getMenuGroups();
        $schoolId = $this->currentEvaluatorSchoolId();
        $userId = session('supabase_user.id');

        if (! $userId) {
            return redirect()->route('signin')
                ->with('error', 'Please sign in as an evaluator to view reading materials.');
        }

        $gradeLevels = $this->fetchGradeLevels($schoolId);
        $materials = $this->fetchReadingMaterials($schoolId, 'approved');
        $myRequests = $this->fetchMyMaterialRequests($userId, $schoolId);

        return view('pages.evaluator.evaluator-reading-materials', [
            'title' => 'Reading Materials',
            'menuGroups' => $menuGroups,
            'materials' => $materials,
            'myRequests' => $myRequests,
            'gradeLevels' => $gradeLevels,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'cover_image' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:4096',
            'language' => 'required|in:Filipino,English',
            'grade_level_id' => 'nullable|uuid',
            'story_content' => 'required|string|min:20',
            'questions' => 'required|array|min:1',
            'questions.*.question_text' => 'required|string|max:1000',
            'questions.*.choices' => 'required|array|min:2',
            'questions.*.choices.*' => 'nullable|string|max:255',
            'questions.*.correct_answer' => 'required|string|max:10',
            'questions.*.points' => 'nullable|integer|min:1|max:100',
        ]);

        $schoolId = $this->currentEvaluatorSchoolId();
        $userId = session('supabase_user.id');

        if (! $userId) {
            return response()->json([
                'message' => 'Your user session is missing. Please sign in again.',
            ], 401);
        }

        if (! $schoolId) {
            return response()->json([
                'message' => 'No school assigned to your evaluator account. A school is required before submitting reading materials for approval.',
                'errors' => [
                    'school_id' => ['No school assigned to your evaluator account.'],
                ],
            ], 403);
        }

        if (! $this->gradeBelongsToSchool($validated['grade_level_id'] ?? null, $schoolId)) {
            return response()->json([
                'message' => 'The selected grade level is not assigned to your school.',
                'errors' => [
                    'grade_level_id' => ['The selected grade level is not assigned to your school.'],
                ],
            ], 422);
        }

        $questionRows = $this->normalizeQuestions($validated['questions']);

        if (empty($questionRows)) {
            return response()->json([
                'message' => 'Please add at least one valid multiple choice question and select the correct answer letter.',
                'errors' => [
                    'questions' => ['Please add at least one valid multiple choice question and select the correct answer letter.'],
                ],
            ], 422);
        }

        $coverImage = $this->storeCoverImageAsBase64($request);
        $wordCount = str_word_count(strip_tags($validated['story_content']));
        $totalScore = collect($questionRows)->sum('points');

        $story = $this->createSupabaseRow('stories', [
            'title' => trim($validated['title']),
            'content' => $validated['story_content'],
            'word_count' => $wordCount,
            'language' => $validated['language'],
            'grade_level_id' => $validated['grade_level_id'] ?? null,
            'status' => 'active',
            'created_by' => $userId,
        ]);

        if (! $story) {
            return response()->json([
                'message' => 'Failed to save the reading story. Check Laravel logs for the Supabase error.',
            ], 500);
        }

        $quiz = $this->createSupabaseRow('quizzes', [
            'total_score' => $totalScore,
            'created_by' => $userId,
        ]);

        if (! $quiz) {
            return response()->json([
                'message' => 'Story was saved, but the quiz record could not be created. Check Laravel logs for the Supabase error.',
            ], 500);
        }

        $createdQuestions = [];

        foreach ($questionRows as $index => $question) {
            $createdQuestion = $this->createSupabaseRow('quiz_questions', array_merge($question, [
                'quiz_id' => $quiz['quiz_id'],
                'question_order' => $index + 1,
            ]));

            if (! $createdQuestion) {
                return response()->json([
                    'message' => 'Story and quiz were saved, but one or more quiz questions could not be created. Check Laravel logs for the Supabase error.',
                ], 500);
            }

            $createdQuestions[] = $createdQuestion;
        }

        $material = $this->createSupabaseRow('reading_materials', [
            'title' => trim($validated['title']),
            'description' => $validated['description'] ?? null,
            'cover_image' => $coverImage,
            'language' => $validated['language'],
            'word_count' => $wordCount,
            'grade_level_id' => $validated['grade_level_id'] ?? null,
            'school_id' => $schoolId,
            'story_id' => $story['story_id'],
            'quiz_id' => $quiz['quiz_id'],
            'uploaded_by' => $userId,
            'approved_by' => null,
            'status' => 'pending',
        ]);

        if (! $material) {
            return response()->json([
                'message' => 'Story and quiz were saved, but the reading material record could not be created. Check Laravel logs for the Supabase error.',
            ], 500);
        }

        return response()->json([
            'message' => 'Reading material submitted successfully. It is now waiting for principal approval.',
            'material' => $this->formatMaterial($material, $story, $quiz, $createdQuestions),
        ]);
    }

    private function fetchReadingMaterials(?string $schoolId, string $status): array
    {
        $query = [
            'select' => 'material_id,title,description,cover_image,language,word_count,grade_level_id,school_id,story_id,quiz_id,uploaded_by,approved_by,status,created_at,updated_at',
            'status' => 'eq.' . $status,
            'order' => 'created_at.desc',
        ];

        if ($schoolId) {
            $query['or'] = '(school_id.is.null,school_id.eq.' . $schoolId . ')';
        } else {
            $query['school_id'] = 'is.null';
        }

        $response = Http::withHeaders($this->supabaseHeaders())
            ->get($this->supabaseUrl() . '/rest/v1/reading_materials', $query);

        if (! $response->successful()) {
            report('Failed to fetch evaluator reading materials: ' . $response->body());
            return [];
        }

        return $this->hydrateMaterials($response->json());
    }

    private function fetchMyMaterialRequests(string $userId, ?string $schoolId): array
    {
        $query = [
            'select' => 'material_id,title,description,cover_image,language,word_count,grade_level_id,school_id,story_id,quiz_id,uploaded_by,approved_by,status,created_at,updated_at',
            'uploaded_by' => 'eq.' . $userId,
            'status' => 'neq.archived',
            'order' => 'created_at.desc',
        ];

        if ($schoolId) {
            $query['school_id'] = 'eq.' . $schoolId;
        }

        $response = Http::withHeaders($this->supabaseHeaders())
            ->get($this->supabaseUrl() . '/rest/v1/reading_materials', $query);

        if (! $response->successful()) {
            report('Failed to fetch evaluator material requests: ' . $response->body());
            return [];
        }

        return $this->hydrateMaterials($response->json());
    }

    private function hydrateMaterials(array $materials): array
    {
        if (empty($materials)) {
            return [];
        }

        $storyIds = collect($materials)->pluck('story_id')->filter()->unique()->values()->all();
        $quizIds = collect($materials)->pluck('quiz_id')->filter()->unique()->values()->all();
        $gradeIds = collect($materials)->pluck('grade_level_id')->filter()->unique()->values()->all();

        $stories = collect($this->fetchRowsByIds(
            'stories',
            'story_id',
            $storyIds,
            'story_id,title,content,word_count,language,grade_level_id,status,created_at,updated_at'
        ))->keyBy('story_id');

        $quizzes = collect($this->fetchRowsByIds(
            'quizzes',
            'quiz_id',
            $quizIds,
            'quiz_id,total_score,created_by,created_at,updated_at'
        ))->keyBy('quiz_id');

        $grades = collect($this->fetchRowsByIds(
            'grade_levels',
            'grade_level_id',
            $gradeIds,
            'grade_level_id,grade_number,school_id,is_active'
        ))->keyBy('grade_level_id');

        $questionsByQuiz = collect($this->fetchQuizQuestions($quizIds))->groupBy('quiz_id');

        return collect($materials)->map(function ($material) use ($stories, $quizzes, $grades, $questionsByQuiz) {
            $story = ! empty($material['story_id']) ? $stories->get($material['story_id']) : null;
            $quiz = ! empty($material['quiz_id']) ? $quizzes->get($material['quiz_id']) : null;
            $questions = ! empty($material['quiz_id'])
                ? $questionsByQuiz->get($material['quiz_id'], collect())->values()->all()
                : [];
            $grade = ! empty($material['grade_level_id']) ? $grades->get($material['grade_level_id']) : null;

            return [
                'material_id' => $material['material_id'],
                'title' => $material['title'],
                'description' => $material['description'] ?? '',
                'cover_image' => $this->coverImageUrl($material['cover_image'] ?? null),
                'language' => $material['language'] ?? ($story['language'] ?? ''),
                'word_count' => $material['word_count'] ?? ($story['word_count'] ?? 0),
                'grade_level_id' => $material['grade_level_id'] ?? null,
                'school_id' => $material['school_id'] ?? null,
                'scope_label' => empty($material['school_id']) ? 'All Schools' : 'School Material',
                'grade_number' => $grade['grade_number'] ?? null,
                'story_id' => $material['story_id'] ?? null,
                'quiz_id' => $material['quiz_id'] ?? null,
                'uploaded_by' => $material['uploaded_by'] ?? null,
                'approved_by' => $material['approved_by'] ?? null,
                'status' => $material['status'] ?? 'pending',
                'created_at' => $material['created_at'] ?? null,
                'updated_at' => $material['updated_at'] ?? null,
                'story' => $story ?: [
                    'title' => $material['title'],
                    'content' => '',
                    'word_count' => 0,
                    'language' => $material['language'] ?? '',
                ],
                'quiz' => $quiz ?: [
                    'total_score' => collect($questions)->sum('points'),
                ],
                'questions' => $questions,
            ];
        })->values()->all();
    }

    private function fetchGradeLevels(?string $schoolId): array
    {
        $query = [
            'select' => 'grade_level_id,grade_number,school_id,is_active',
            'is_active' => 'eq.true',
            'order' => 'grade_number.asc',
        ];

        if ($schoolId) {
            $query['school_id'] = 'eq.' . $schoolId;
        }

        $response = Http::withHeaders($this->supabaseHeaders())
            ->get($this->supabaseUrl() . '/rest/v1/grade_levels', $query);

        if (! $response->successful()) {
            report('Failed to fetch evaluator grade levels: ' . $response->body());
            return [];
        }

        return $response->json();
    }

    private function fetchRowsByIds(string $table, string $idField, array $ids, string $select): array
    {
        $ids = collect($ids)->filter()->unique()->values()->all();

        if (empty($ids)) {
            return [];
        }

        $response = Http::withHeaders($this->supabaseHeaders())
            ->get($this->supabaseUrl() . '/rest/v1/' . $table, [
                'select' => $select,
                $idField => 'in.(' . $this->postgrestInList($ids) . ')',
            ]);

        if (! $response->successful()) {
            report("Failed to fetch {$table} for evaluator reading materials: " . $response->body());
            return [];
        }

        return $response->json();
    }

    private function fetchQuizQuestions(array $quizIds): array
    {
        $quizIds = collect($quizIds)->filter()->unique()->values()->all();

        if (empty($quizIds)) {
            return [];
        }

        $response = Http::withHeaders($this->supabaseHeaders())
            ->get($this->supabaseUrl() . '/rest/v1/quiz_questions', [
                'quiz_id' => 'in.(' . $this->postgrestInList($quizIds) . ')',
                'select' => 'question_id,quiz_id,question_text,choices,correct_answer,points,question_order,created_at',
                'order' => 'question_order.asc',
            ]);

        if (! $response->successful()) {
            report('Failed to fetch evaluator reading material questions: ' . $response->body());
            return [];
        }

        return $response->json();
    }

    private function normalizeQuestions(array $questions): array
    {
        $letters = range('A', 'Z');

        return collect($questions)->map(function ($question) use ($letters) {
            $choices = collect($question['choices'] ?? [])
                ->map(fn ($choice) => trim((string) $choice))
                ->filter()
                ->values()
                ->map(function ($choice, $index) use ($letters) {
                    return [
                        'letter' => $letters[$index] ?? chr(65 + $index),
                        'choice' => $choice,
                    ];
                })
                ->values()
                ->all();

            $correctAnswer = strtoupper(trim((string) ($question['correct_answer'] ?? '')));

            return [
                'question_text' => trim((string) ($question['question_text'] ?? '')),
                'choices' => $choices,
                'correct_answer' => $correctAnswer,
                'points' => (int) ($question['points'] ?? 1),
            ];
        })->filter(function ($question) {
            if ($question['question_text'] === '') {
                return false;
            }

            if (count($question['choices']) < 2) {
                return false;
            }

            if ($question['correct_answer'] === '') {
                return false;
            }

            return collect($question['choices'])->pluck('letter')->contains($question['correct_answer']);
        })->values()->all();
    }

    private function storeCoverImageAsBase64(Request $request): ?string
    {
        if (! $request->hasFile('cover_image')) {
            return null;
        }

        $file = $request->file('cover_image');
        $mime = $file->getMimeType() ?: 'image/jpeg';
        $contents = file_get_contents($file->getRealPath());

        return 'data:' . $mime . ';base64,' . base64_encode($contents);
    }

    private function gradeBelongsToSchool(?string $gradeLevelId, ?string $schoolId): bool
    {
        if (! $gradeLevelId || ! $schoolId) {
            return true;
        }

        $response = Http::withHeaders($this->supabaseHeaders())
            ->get($this->supabaseUrl() . '/rest/v1/grade_levels', [
                'select' => 'grade_level_id',
                'grade_level_id' => 'eq.' . $gradeLevelId,
                'school_id' => 'eq.' . $schoolId,
                'limit' => 1,
            ]);

        if (! $response->successful()) {
            report('Failed to validate evaluator grade level ownership: ' . $response->body());
            return false;
        }

        return ! empty($response->json());
    }

    private function createSupabaseRow(string $table, array $payload): ?array
    {
        $response = Http::withHeaders(array_merge($this->supabaseHeaders(), [
            'Content-Type' => 'application/json',
            'Prefer' => 'return=representation',
        ]))->post($this->supabaseUrl() . '/rest/v1/' . $table, $payload);

        if (! $response->successful()) {
            report("Failed to create {$table} from evaluator reading materials: " . $response->body());
            return null;
        }

        return $response->json()[0] ?? null;
    }

    private function formatMaterial(array $material, array $story, array $quiz, array $questions): array
    {
        $gradeNumber = null;

        if (! empty($material['grade_level_id'])) {
            $gradeResponse = Http::withHeaders($this->supabaseHeaders())
                ->get($this->supabaseUrl() . '/rest/v1/grade_levels', [
                    'select' => 'grade_level_id,grade_number',
                    'grade_level_id' => 'eq.' . $material['grade_level_id'],
                    'limit' => 1,
                ]);

            if ($gradeResponse->successful()) {
                $gradeNumber = $gradeResponse->json()[0]['grade_number'] ?? null;
            }
        }

        return [
            'material_id' => $material['material_id'],
            'title' => $material['title'],
            'description' => $material['description'] ?? '',
            'cover_image' => $this->coverImageUrl($material['cover_image'] ?? null),
            'language' => $material['language'] ?? ($story['language'] ?? ''),
            'word_count' => $material['word_count'] ?? ($story['word_count'] ?? 0),
            'grade_level_id' => $material['grade_level_id'] ?? null,
            'school_id' => $material['school_id'] ?? null,
            'scope_label' => empty($material['school_id']) ? 'All Schools' : 'School Material',
            'grade_number' => $gradeNumber,
            'story_id' => $material['story_id'] ?? null,
            'quiz_id' => $material['quiz_id'] ?? null,
            'uploaded_by' => $material['uploaded_by'] ?? null,
            'approved_by' => $material['approved_by'] ?? null,
            'status' => $material['status'] ?? 'pending',
            'created_at' => $material['created_at'] ?? null,
            'updated_at' => $material['updated_at'] ?? $material['created_at'] ?? null,
            'story' => $story,
            'quiz' => [
                'quiz_id' => $quiz['quiz_id'] ?? null,
                'total_score' => $quiz['total_score'] ?? collect($questions)->sum('points'),
                'created_by' => $quiz['created_by'] ?? null,
                'created_at' => $quiz['created_at'] ?? null,
                'updated_at' => $quiz['updated_at'] ?? null,
            ],
            'questions' => $questions,
        ];
    }

    private function currentEvaluatorSchoolId(): ?string
    {
        $activeDesignation = session('active_designation', []);
        $activeRole = strtolower($activeDesignation['role_name'] ?? '');

        if (in_array($activeRole, ['evaluator', 'teacher'], true) && ! empty($activeDesignation['school_id'])) {
            return $activeDesignation['school_id'];
        }

        $designation = collect(session('user_designations', []))->first(function ($designation) {
            return in_array(strtolower($designation['role_name'] ?? ''), ['evaluator', 'teacher'], true)
                && ! empty($designation['school_id']);
        });

        return $designation['school_id'] ?? null;
    }

    private function coverImageUrl(?string $path): string
    {
        if (! $path) {
            return '';
        }

        if (str_starts_with($path, 'data:image/')) {
            return $path;
        }

        if (str_starts_with($path, 'http://') || str_starts_with($path, 'https://')) {
            return $path;
        }

        if (str_starts_with($path, '/storage/')) {
            return url($path);
        }

        if (str_starts_with($path, 'storage/')) {
            return url('/' . $path);
        }

        return asset('storage/' . ltrim($path, '/'));
    }

    private function postgrestInList(array $ids): string
    {
        return collect($ids)
            ->filter()
            ->map(fn ($id) => '"' . str_replace('"', '\\"', (string) $id) . '"')
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
