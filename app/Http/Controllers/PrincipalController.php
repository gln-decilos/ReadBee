<?php

namespace App\Http\Controllers;

use App\Helpers\PrincipalMenuHelper;
use Illuminate\Support\Facades\Http;

class PrincipalController extends Controller
{
    public function dashboard()
    {
        $menuGroups = PrincipalMenuHelper::getMenuGroups();

        return view(
            'pages.principal.principal-dashboard',
            compact('menuGroups')
        );
    }

    public function profile()
    {
        $menuGroups = PrincipalMenuHelper::getMenuGroups();

        return view(
            'pages.principal.principal-profile',
            compact('menuGroups')
        );
    }

    public function readingMaterials()
    {
        $menuGroups = PrincipalMenuHelper::getMenuGroups();
        $schoolId = $this->principalSchoolId();

        $gradeLevels = $this->fetchGradeLevels($schoolId);
        $materials = $this->fetchReadingMaterials($schoolId);

        return view('pages.principal.principal-reading-materials', [
            'title' => 'Reading Materials',
            'menuGroups' => $menuGroups,
            'materials' => $materials,
            'gradeLevels' => $gradeLevels,
            'page' => 1,
            'perPage' => 8,
        ]);
    }

    public function pupils()
    {
        $menuGroups = PrincipalMenuHelper::getMenuGroups();

        return view(
            'pages.principal.principal-pupils',
            compact('menuGroups')
        );
    }

    private function fetchReadingMaterials(?string $schoolId): array
    {
        $query = [
            'select' => 'material_id,title,description,cover_image,language,word_count,grade_level_id,school_id,story_id,quiz_id,uploaded_by,approved_by,status,created_at,updated_at',
            'status' => 'neq.archived',
            'order' => 'created_at.desc',
        ];

        if ($schoolId) {
            $query['or'] = '(school_id.is.null,school_id.eq.' . $schoolId . ')';
        } else {
            $query['school_id'] = 'is.null';
        }

        $materialsResponse = Http::withHeaders($this->supabaseHeaders())
            ->get($this->supabaseUrl() . '/rest/v1/reading_materials', $query);

        if (! $materialsResponse->successful()) {
            report('Failed to fetch principal reading materials: ' . $materialsResponse->body());
            return [];
        }

        $materials = $materialsResponse->json();

        if (empty($materials)) {
            return [];
        }

        $storyIds = collect($materials)->pluck('story_id')->filter()->unique()->values()->all();
        $quizIds = collect($materials)->pluck('quiz_id')->filter()->unique()->values()->all();
        $materialGradeIds = collect($materials)->pluck('grade_level_id')->filter()->unique()->values()->all();

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
            $materialGradeIds,
            'grade_level_id,grade_number,school_id'
        ))->keyBy('grade_level_id');

        $questionsByQuiz = collect($this->fetchQuizQuestions($quizIds))
            ->groupBy('quiz_id');

        return collect($materials)->map(function ($material) use ($stories, $quizzes, $grades, $questionsByQuiz) {
            $story = ! empty($material['story_id']) ? $stories->get($material['story_id']) : null;
            $quiz = ! empty($material['quiz_id']) ? $quizzes->get($material['quiz_id']) : null;

            $questions = ! empty($material['quiz_id'])
                ? $questionsByQuiz->get($material['quiz_id'], collect())->values()->all()
                : [];

            $grade = ! empty($material['grade_level_id'])
                ? $grades->get($material['grade_level_id'])
                : null;

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
                'status' => $material['status'],
                'created_at' => $material['created_at'],
                'updated_at' => $material['updated_at'],
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
            report('Failed to fetch principal grade levels: ' . $response->body());
            return [];
        }

        return $response->json();
    }

    private function fetchRowsByIds(string $table, string $idField, array $ids, string $select): array
    {
        if (empty($ids)) {
            return [];
        }

        $response = Http::withHeaders($this->supabaseHeaders())
            ->get($this->supabaseUrl() . '/rest/v1/' . $table, [
                $idField => 'in.(' . $this->postgrestInList($ids) . ')',
                'select' => $select,
            ]);

        if (! $response->successful()) {
            report("Failed to fetch {$table}: " . $response->body());
            return [];
        }

        return $response->json();
    }

    private function fetchQuizQuestions(array $quizIds): array
    {
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
            report('Failed to fetch quiz questions: ' . $response->body());
            return [];
        }

        return $response->json();
    }

    private function principalSchoolId(): ?string
    {
        $activeDesignation = session('active_designation', []);

        if (
            strtolower($activeDesignation['role_name'] ?? '') === 'principal'
            && ! empty($activeDesignation['school_id'])
        ) {
            return $activeDesignation['school_id'];
        }

        $principalDesignation = collect(session('user_designations', []))
            ->first(function ($designation) {
                return strtolower($designation['role_name'] ?? '') === 'principal'
                    && ! empty($designation['school_id']);
            });

        return $principalDesignation['school_id'] ?? null;
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
