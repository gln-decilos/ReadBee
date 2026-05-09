@props([
    'materials' => [],
    'gradeLevels' => [],
    'page' => 1,
    'perPage' => 8,
])

<script>
    window.principalReadingMaterialsPage = function(initialMaterials, gradeLevels, initialPage, initialPerPage) {
        return {
            materials: initialMaterials || [],
            gradeLevels: gradeLevels || [],
            view: 'table',
            step: 1,
            page: initialPage || 1,
            perPage: initialPerPage || 8,
            search: '',
            statusFilter: 'all',
            formErrors: {},
            successModal: false,
            successMessage: '',
            loading: false,
            coverPreview: '',
            showReader: false,
            currentMaterial: null,
            readerPages: [],
            pageIndex: 0,
            questions: [
                {
                    question_text: '',
                    choices: ['', '', '', ''],
                    correct_answer: '',
                    points: 1,
                }
            ],

            get filteredMaterials() {
                const term = this.search.trim().toLowerCase();

                return this.materials.filter((material) => {
                    const matchesStatus = this.statusFilter === 'all' || (this.statusFilter === 'pending' ? material.status === 'pending' && material.school_id : material.status === this.statusFilter);
                    const haystack = [
                        material.title,
                        material.description,
                        material.language,
                        material.grade_number ? `grade ${material.grade_number}` : 'all grades',
                        material.status,
                        material.scope_label || (material.school_id ? 'school material' : 'all schools'),
                    ].join(' ').toLowerCase();

                    return matchesStatus && (!term || haystack.includes(term));
                });
            },

            get total() {
                return this.filteredMaterials.length;
            },

            get lastPage() {
                return Math.max(Math.ceil(this.total / this.perPage), 1);
            },

            get paginatedMaterials() {
                if (this.page > this.lastPage) {
                    this.page = this.lastPage;
                }

                const start = (this.page - 1) * this.perPage;
                return this.filteredMaterials.slice(start, start + this.perPage);
            },

            get startItem() {
                if (this.total === 0) return 0;
                return (this.page - 1) * this.perPage + 1;
            },

            get endItem() {
                return Math.min(this.page * this.perPage, this.total);
            },

            get approvedCount() {
                return this.materials.filter(material => material.status === 'approved').length;
            },

            get pendingCount() {
                return this.materials.filter(material => material.status === 'pending' && material.school_id).length;
            },

            visiblePages() {
                const pages = [];
                const start = Math.max(1, this.page - 2);
                const end = Math.min(this.lastPage, this.page + 2);

                for (let i = start; i <= end; i++) {
                    pages.push(i);
                }

                return pages;
            },

            goToPage(pageNumber) {
                if (pageNumber >= 1 && pageNumber <= this.lastPage) {
                    this.page = pageNumber;
                }
            },

            showApprovalRequests() {
                this.statusFilter = 'pending';
                this.page = 1;
                window.scrollTo({ top: 0, behavior: 'smooth' });
            },

            scopeLabel(material) {
                return material.scope_label || (material.school_id ? 'School Material' : 'All Schools');
            },

            shortText(value, limit = 70) {
                const text = String(value || '').replace(/\s+/g, ' ').trim();

                if (!text) {
                    return 'No description';
                }

                return text.length > limit ? `${text.slice(0, limit).trim()}...` : text;
            },

            openCreateForm() {
                this.resetForm();
                this.view = 'create';
                this.step = 1;
                window.scrollTo({ top: 0, behavior: 'smooth' });
            },

            backToTable() {
                this.view = 'table';
                this.step = 1;
                this.formErrors = {};
                this.coverPreview = '';
                window.scrollTo({ top: 0, behavior: 'smooth' });
            },

            resetForm() {
                this.formErrors = {};
                this.coverPreview = '';
                this.questions = [
                    {
                        question_text: '',
                        choices: ['', '', '', ''],
                        correct_answer: '',
                        points: 1,
                    }
                ];

                this.$nextTick(() => {
                    if (this.$refs.materialForm) {
                        this.$refs.materialForm.reset();
                    }
                });
            },

            handleCoverChange(event) {
                const file = event.target.files && event.target.files[0] ? event.target.files[0] : null;
                this.coverPreview = file ? URL.createObjectURL(file) : '';
            },

            nextStep() {
                this.formErrors = {};
                const form = this.$refs.materialForm;

                if (!form) return;

                const requiredFields = {
                    title: 'Title',
                    language: 'Language',
                    story_content: 'Story content',
                };
                let hasError = false;

                Object.entries(requiredFields).forEach(([field, label]) => {
                    const input = form.querySelector(`[name="${field}"]`);
                    if (!input || !input.value.trim()) {
                        this.formErrors[field] = [`${label} is required.`];
                        hasError = true;
                    }
                });

                if (hasError) return;

                this.step = 2;
                window.scrollTo({ top: 0, behavior: 'smooth' });
            },

            previousStep() {
                this.step = 1;
                window.scrollTo({ top: 0, behavior: 'smooth' });
            },

            addQuestion() {
                this.questions.push({
                    question_text: '',
                    choices: ['', '', '', ''],
                    correct_answer: '',
                    points: 1,
                });
            },

            removeQuestion(index) {
                if (this.questions.length === 1) return;
                this.questions.splice(index, 1);
            },

            addChoice(question) {
                question.choices.push('');
            },

            choiceLetter(index) {
                return String.fromCharCode(65 + index);
            },

            removeChoice(question, index) {
                if (question.choices.length <= 2) return;

                question.choices.splice(index, 1);
                question.correct_answer = '';
            },

            cleanChoices(question) {
                return (question.choices || [])
                    .map((choice, index) => {
                        return {
                            letter: this.choiceLetter(index),
                            choice: String(choice || '').trim(),
                        };
                    })
                    .filter(option => option.choice);
            },

            async submitMaterial() {
                this.formErrors = {};
                this.loading = true;

                const formData = new FormData(this.$refs.materialForm);

                try {
                    const response = await fetch("{{ route('principal.reading-materials.store') }}", {
                        method: 'POST',
                        headers: {
                            'X-CSRF-TOKEN': '{{ csrf_token() }}',
                            'Accept': 'application/json',
                        },
                        body: formData,
                    });

                    const data = await response.json();

                    if (!response.ok) {
                        this.formErrors = data.errors || {};

                        if (this.formErrors.title || this.formErrors.story_content || this.formErrors.cover_image || this.formErrors.grade_level_id || this.formErrors.language || this.formErrors.description) {
                            this.step = 1;
                        }

                        alert(data.message || 'Failed to create reading material.');
                        return;
                    }

                    this.materials.unshift(data.material);
                    this.page = 1;
                    this.view = 'table';
                    this.step = 1;
                    this.resetForm();
                    this.successMessage = data.message || 'Reading material created successfully.';
                    this.successModal = true;
                } catch (error) {
                    console.error('Create reading material error:', error);
                    alert('An error occurred while creating the reading material.');
                } finally {
                    this.loading = false;
                }
            },

            async approveMaterial(material) {
                if (!material || material.status !== 'pending') return;

                try {
                    const response = await fetch(`/principal/reading-materials/${material.material_id}/approve`, {
                        method: 'PATCH',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': '{{ csrf_token() }}',
                            'Accept': 'application/json',
                        },
                    });

                    const data = await response.json();

                    if (!response.ok) {
                        alert(data.message || 'Failed to approve reading material.');
                        return;
                    }

                    this.materials = this.materials.map(item => {
                        if (String(item.material_id) === String(material.material_id)) {
                            return {
                                ...item,
                                status: 'approved',
                                approved_by: data.material?.approved_by || item.approved_by,
                                updated_at: data.material?.updated_at || item.updated_at,
                            };
                        }

                        return item;
                    });

                    this.successMessage = data.message || 'Reading material approved successfully.';
                    this.successModal = true;
                } catch (error) {
                    console.error('Approve reading material error:', error);
                    alert('An error occurred while approving the reading material.');
                }
            },

            async archiveMaterial(material) {
                if (!material) return;
                if (!confirm('Archive this reading material?')) return;

                try {
                    const response = await fetch(`/principal/reading-materials/${material.material_id}/archive`, {
                        method: 'PATCH',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': '{{ csrf_token() }}',
                            'Accept': 'application/json',
                        },
                    });

                    const data = await response.json();

                    if (!response.ok) {
                        alert(data.message || 'Failed to archive reading material.');
                        return;
                    }

                    this.materials = this.materials.filter(item => String(item.material_id) !== String(material.material_id));
                    this.successMessage = data.message || 'Reading material archived successfully.';
                    this.successModal = true;
                } catch (error) {
                    console.error('Archive reading material error:', error);
                    alert('An error occurred while archiving the reading material.');
                }
            },

            openReader(material) {
                this.currentMaterial = material;
                this.readerPages = this.makeBookPages(material);
                this.pageIndex = 0;
                this.showReader = true;
                document.body.classList.add('overflow-hidden');
            },

            closeReader() {
                this.showReader = false;
                this.currentMaterial = null;
                this.readerPages = [];
                this.pageIndex = 0;
                document.body.classList.remove('overflow-hidden');
            },

            readerStep() {
                return window.matchMedia('(min-width: 768px)').matches ? 2 : 1;
            },

            nextReaderPage() {
                const step = this.readerStep();

                if (this.pageIndex + step < this.readerPages.length) {
                    this.pageIndex += step;
                }
            },

            previousReaderPage() {
                const step = this.readerStep();

                if (this.pageIndex - step >= 0) {
                    this.pageIndex -= step;
                }
            },

            get leftPage() {
                return this.readerPages[this.pageIndex] || null;
            },

            get rightPage() {
                return this.readerStep() === 2 ? (this.readerPages[this.pageIndex + 1] || null) : null;
            },

            get readerPageLabel() {
                if (!this.readerPages.length) return '0 / 0';

                const step = this.readerStep();
                const end = Math.min(this.pageIndex + step, this.readerPages.length);

                return step === 1
                    ? `${this.pageIndex + 1} / ${this.readerPages.length}`
                    : `${this.pageIndex + 1}-${end} / ${this.readerPages.length}`;
            },

            makeBookPages(material) {
                const pages = [];
                const story = material.story || {};
                const questions = material.questions || [];
                const totalScore = material.quiz?.total_score || questions.reduce((sum, question) => sum + Number(question.points || 0), 0);

                pages.push({
                    type: 'cover',
                    title: material.title,
                    subtitle: material.description || 'Reading material',
                    image: material.cover_image,
                    meta: [
                        material.grade_number ? `Grade ${material.grade_number}` : 'All Grades',
                        material.language || null,
                        material.word_count ? `${material.word_count} words` : null,
                    ].filter(Boolean).join(' | '),
                });

                this.chunkStory(story.content || '').forEach((chunk, index) => {
                    pages.push({
                        type: 'story',
                        title: index === 0 ? (story.title || material.title) : 'Story continues',
                        body: chunk,
                    });
                });

                pages.push({
                    type: 'quiz_intro',
                    title: 'Comprehension Quiz',
                    subtitle: 'Choose the correct answer based on the story.',
                    meta: `${questions.length} question${questions.length === 1 ? '' : 's'} | ${totalScore} point${Number(totalScore) === 1 ? '' : 's'}`,
                });

                questions.forEach((question, index) => {
                    pages.push({
                        type: 'question',
                        title: `Question ${index + 1}`,
                        question: question,
                    });
                });

                if (pages.length % 2 !== 0) {
                    pages.push({
                        type: 'blank',
                        title: 'End of material',
                        subtitle: 'Story and quiz completed.',
                    });
                }

                return pages;
            },

            chunkStory(content) {
                const cleanContent = String(content || '').replace(/\r\n/g, '\n').trim();

                if (!cleanContent) {
                    return ['No story content has been added yet.'];
                }

                const maxWords = 78;
                const maxChars = 620;
                const pages = [];
                let current = '';
                let currentWords = 0;

                const pushCurrent = () => {
                    if (current.trim()) {
                        pages.push(current.trim());
                    }

                    current = '';
                    currentWords = 0;
                };

                const appendLine = (line) => {
                    const text = String(line || '').trim();

                    if (!text) {
                        return;
                    }

                    const words = text.split(/\s+/).filter(Boolean);

                    words.forEach((word) => {
                        const candidate = current ? `${current} ${word}` : word;

                        if (current && (currentWords + 1 > maxWords || candidate.length > maxChars)) {
                            pushCurrent();
                        }

                        current = current ? `${current} ${word}` : word;
                        currentWords += 1;
                    });
                };

                cleanContent
                    .split(/\n{2,}/)
                    .map((paragraph) => paragraph.trim())
                    .filter(Boolean)
                    .forEach((paragraph) => {
                        const paragraphWords = paragraph.split(/\s+/).filter(Boolean).length;
                        const candidate = current ? `${current}\n\n${paragraph}` : paragraph;

                        if (current && (currentWords + paragraphWords > maxWords || candidate.length > maxChars)) {
                            pushCurrent();
                        }

                        if (paragraphWords > maxWords || paragraph.length > maxChars) {
                            appendLine(paragraph);
                        } else {
                            current = current ? `${current}\n\n${paragraph}` : paragraph;
                            currentWords += paragraphWords;
                        }
                    });

                pushCurrent();

                return pages.length ? pages : ['No story content has been added yet.'];
            },

            formatDate(value) {
                if (!value) return 'Not set';

                return new Date(value).toLocaleDateString('en-PH', {
                    year: 'numeric',
                    month: 'short',
                    day: 'numeric',
                });
            },

            badgeClass(status) {
                if (status === 'approved') return 'bg-green-100 text-green-700 dark:bg-green-500/10 dark:text-green-400';
                if (status === 'pending') return 'bg-yellow-100 text-yellow-700 dark:bg-yellow-500/10 dark:text-yellow-400';
                if (status === 'rejected') return 'bg-red-100 text-red-700 dark:bg-red-500/10 dark:text-red-400';
                return 'bg-gray-100 text-gray-700 dark:bg-gray-500/10 dark:text-gray-300';
            },
        };
    };
</script>

<style>
    [x-cloak] {
        display: none !important;
    }

    .principal-reading-materials-root,
    .principal-reading-materials-root * {
        box-sizing: border-box;
    }

    .principal-reading-materials-root {
        width: 100%;
        max-width: 100%;
        overflow-x: hidden;
    }

    .readbee-book-stage {
        width: 100%;
        max-width: 100%;
        overflow-x: clip;
        perspective: 1800px;
    }

    .readbee-book-spread {
        width: 100%;
        max-width: min(100%, 72rem);
        transform-style: preserve-3d;
        box-shadow: 0 28px 70px rgba(16, 24, 40, 0.22);
    }

    .readbee-page {
        height: calc(100vh - 190px);
        min-height: 430px;
        max-height: 620px;
        overflow: hidden;
        background:
            linear-gradient(90deg, rgba(44, 62, 80, 0.08), transparent 13%, transparent 87%, rgba(44, 62, 80, 0.08)),
            linear-gradient(180deg, #fffdf7 0%, #fff8df 100%);
    }

    .readbee-page-body {
        max-height: calc(100% - 118px);
        overflow: hidden;
    }

    .dark .readbee-page {
        background:
            linear-gradient(90deg, rgba(255, 255, 255, 0.08), transparent 13%, transparent 87%, rgba(255, 255, 255, 0.06)),
            linear-gradient(180deg, #1f2937 0%, #111827 100%);
    }

    @media (max-width: 767px) {
        .readbee-book-stage {
            perspective: none;
        }

        .readbee-book-spread {
            max-width: calc(100vw - 1rem);
            box-shadow: 0 18px 45px rgba(16, 24, 40, 0.26);
        }

        .readbee-page {
            height: calc(100vh - 185px);
            min-height: 420px;
            max-height: 560px;
        }

        .readbee-page-body {
            max-height: calc(100% - 105px);
        }
    }
</style>

<div x-data="principalReadingMaterialsPage(@js($materials), @js($gradeLevels), {{ (int) $page }}, {{ (int) $perPage }})" x-cloak class="principal-reading-materials-root w-full max-w-full overflow-x-hidden">
    <div x-show="successModal" x-transition.opacity class="fixed inset-0 z-99999 flex items-center justify-center bg-gray-900/60 px-4">
        <div @click.outside="successModal = false" class="w-full max-w-md rounded-2xl bg-white p-6 text-center shadow-theme-xl dark:bg-gray-900">
            <div class="mx-auto flex h-14 w-14 items-center justify-center rounded-full bg-green-100 text-green-600 dark:bg-green-500/10 dark:text-green-400">
                <svg class="h-7 w-7" viewBox="0 0 24 24" fill="none">
                    <path d="M20 6L9 17L4 12" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                </svg>
            </div>
            <h3 class="mt-4 text-lg font-semibold text-gray-900 dark:text-white">Success</h3>
            <p class="mt-2 text-sm text-gray-500 dark:text-gray-400" x-text="successMessage"></p>
            <button type="button" @click="successModal = false" class="mt-5 rounded-lg bg-brand-500 px-5 py-2.5 text-sm font-semibold text-gray-900 hover:bg-brand-400">
                OK
            </button>
        </div>
    </div>

    <div x-show="view === 'table'" class="w-full max-w-full space-y-6 overflow-x-hidden">
        <div class="w-full max-w-full overflow-hidden rounded-2xl border border-gray-200 bg-white dark:border-gray-800 dark:bg-white/[0.03]">
            <div class="border-b border-gray-200 p-5 dark:border-gray-800 xl:p-6">
                <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
                    <div>
                        <h2 class="text-xl font-semibold text-gray-900 dark:text-white">Reading Materials Management</h2>
                        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Review submitted materials, approve pending items, and preview stories with quizzes.</p>
                    </div>

                    <div class="flex flex-wrap gap-2">
                        <button type="button" @click="showApprovalRequests" class="inline-flex items-center justify-center rounded-lg border border-yellow-300 px-4 py-2.5 text-sm font-semibold text-yellow-700 hover:bg-yellow-50 dark:border-yellow-500/30 dark:text-yellow-400 dark:hover:bg-yellow-500/10">
                            Approval Requests
                            <span class="ml-2 rounded-full bg-yellow-100 px-2 py-0.5 text-xs text-yellow-700 dark:bg-yellow-500/20 dark:text-yellow-300" x-text="pendingCount"></span>
                        </button>
                        <button type="button" @click="openCreateForm" class="inline-flex items-center justify-center rounded-lg bg-brand-500 px-4 py-2.5 text-sm font-semibold text-gray-900 shadow-theme-xs hover:bg-brand-400">
                            Add Reading Material
                        </button>
                    </div>
                </div>

                <div class="mt-5 grid grid-cols-1 gap-3 lg:grid-cols-[1fr_220px]">
                    <input type="search" x-model="search" @input="page = 1" placeholder="Search title, grade, language, or status" class="h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 shadow-theme-xs placeholder:text-gray-400 focus:border-brand-400 focus:outline-hidden focus:ring-3 focus:ring-brand-500/10 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90" />

                    <select x-model="statusFilter" @change="page = 1" class="h-11 rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 shadow-theme-xs focus:border-brand-400 focus:outline-hidden focus:ring-3 focus:ring-brand-500/10 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
                        <option value="all">All Statuses</option>
                        <option value="pending">For Approval</option>
                        <option value="approved">Approved</option>
                        <option value="draft">Draft</option>
                        <option value="rejected">Rejected</option>
                    </select>
                </div>
            </div>

            <div class="block space-y-3 p-5 md:hidden">
                <template x-for="material in paginatedMaterials" :key="`mobile-${material.material_id}`">
                    <article class="rounded-2xl border border-gray-200 bg-white p-4 shadow-theme-xs dark:border-gray-800 dark:bg-gray-900/40">
                        <div class="flex items-start gap-3">
                            <div class="flex h-16 w-12 shrink-0 items-center justify-center overflow-hidden rounded-lg bg-brand-50 text-brand-600 dark:bg-brand-500/10 dark:text-brand-400">
                                <template x-if="material.cover_image">
                                    <img :src="material.cover_image" :alt="material.title" class="h-full w-full object-cover" />
                                </template>
                                <template x-if="!material.cover_image">
                                    <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none"><path d="M5 5.75C5 4.7835 5.7835 4 6.75 4H18C18.5523 4 19 4.44772 19 5V17.5C19 18.3284 18.3284 19 17.5 19H7.25C6.00736 19 5 17.9926 5 16.75V5.75Z" stroke="currentColor" stroke-width="1.5"/><path d="M7.5 8H15.5M7.5 11.5H15.5M7.5 15H12" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/></svg>
                                </template>
                            </div>

                            <div class="min-w-0 flex-1">
                                <div class="grid grid-cols-2 gap-2 sm:flex sm:flex-wrap sm:items-center">
                                    <span class="rounded-full px-2.5 py-1 text-xs font-medium capitalize" :class="badgeClass(material.status)" x-text="material.status === 'pending' ? 'For Approval' : material.status"></span>
                                    <span class="rounded-full bg-gray-100 px-2.5 py-1 text-xs font-medium text-gray-700 dark:bg-white/10 dark:text-gray-300" x-text="scopeLabel(material)"></span>
                                </div>
                                <h3 class="mt-2 truncate text-base font-semibold text-gray-900 dark:text-white" x-text="material.title" :title="material.title"></h3>
                                <p class="mt-1 truncate text-sm text-gray-500 dark:text-gray-400" x-text="shortText(material.description, 75)" :title="material.description || 'No description'"></p>
                                <div class="mt-3 grid grid-cols-2 gap-2 text-xs text-gray-500 dark:text-gray-400">
                                    <span x-text="material.grade_number ? `Grade ${material.grade_number}` : 'All Grades'"></span>
                                    <span x-text="material.language || 'Not set'"></span>
                                    <span x-text="`${material.word_count || 0} words`"></span>
                                    <span x-text="`${material.questions?.length || 0} questions`"></span>
                                </div>
                            </div>
                        </div>

                        <div class="mt-4 flex flex-wrap justify-end gap-2">
                            <button type="button" @click="openReader(material)" class="inline-flex items-center justify-center rounded-lg border border-gray-300 px-3 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:text-gray-300 dark:hover:bg-white/5">
                                View
                            </button>
                            <button type="button" x-show="material.status === 'pending' && material.school_id" @click="approveMaterial(material)" class="rounded-lg border border-green-200 px-3 py-2 text-sm font-medium text-green-700 hover:bg-green-50 dark:border-green-500/20 dark:text-green-400 dark:hover:bg-green-500/10">
                                Approve
                            </button>
                            <button type="button" x-show="material.school_id" @click="archiveMaterial(material)" class="rounded-lg border border-red-200 px-3 py-2 text-sm font-medium text-red-600 hover:bg-red-50 dark:border-red-500/20 dark:hover:bg-red-500/10">
                                Archive
                            </button>
                        </div>
                    </article>
                </template>

                <div x-show="paginatedMaterials.length === 0" class="rounded-2xl border border-dashed border-gray-300 p-8 text-center dark:border-gray-700">
                    <div class="mx-auto flex h-14 w-14 items-center justify-center rounded-full bg-brand-50 text-brand-500 dark:bg-brand-500/10">
                        <svg class="h-7 w-7" viewBox="0 0 24 24" fill="none"><path d="M5 5.75C5 4.7835 5.7835 4 6.75 4H18C18.5523 4 19 4.44772 19 5V17.5C19 18.3284 18.3284 19 17.5 19H7.25C6.00736 19 5 17.9926 5 16.75V5.75Z" stroke="currentColor" stroke-width="1.5"/><path d="M7.5 8H15.5M7.5 11.5H15.5M7.5 15H12" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/></svg>
                    </div>
                    <h3 class="mt-4 text-lg font-semibold text-gray-900 dark:text-white">No reading materials found</h3>
                    <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">Add a reading material or change the search filter.</p>
                </div>
            </div>

            <div class="hidden w-full max-w-full overflow-hidden md:block">
                <table class="w-full table-fixed divide-y divide-gray-200 dark:divide-gray-800">
                    <colgroup>
                        <col class="w-[42%]">
                        <col class="w-[13%]">
                        <col class="w-[13%]">
                        <col class="w-[12%]">
                        <col class="w-[12%]">
                        <col class="w-[8%]">
                    </colgroup>

                    <thead class="bg-gray-50 dark:bg-gray-900/50">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">
                                Material
                            </th>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">
                                Grade
                            </th>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">
                                Language
                            </th>
                            <th class="px-4 py-3 text-center text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">
                                Questions
                            </th>
                            <th class="px-4 py-3 text-center text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">
                                Status
                            </th>
                            <th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">
                                Action
                            </th>
                        </tr>
                    </thead>

                    <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                        <template x-for="material in paginatedMaterials" :key="material.material_id">
                            <tr class="hover:bg-gray-50 dark:hover:bg-white/[0.03]">
                                <td class="px-4 py-4">
                                    <div class="flex min-w-0 items-center gap-3">
                                        <div class="flex h-14 w-11 shrink-0 items-center justify-center overflow-hidden rounded-lg bg-brand-50 text-brand-600 dark:bg-brand-500/10 dark:text-brand-400">
                                            <template x-if="material.cover_image">
                                                <img :src="material.cover_image" :alt="material.title" class="h-full w-full object-cover" />
                                            </template>

                                            <template x-if="!material.cover_image">
                                                <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none">
                                                    <path d="M5 5.75C5 4.7835 5.7835 4 6.75 4H18C18.5523 4 19 4.44772 19 5V17.5C19 18.3284 18.3284 19 17.5 19H7.25C6.00736 19 5 17.9926 5 16.75V5.75Z" stroke="currentColor" stroke-width="1.5"/>
                                                    <path d="M7.5 8H15.5M7.5 11.5H15.5M7.5 15H12" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/>
                                                </svg>
                                            </template>
                                        </div>

                                        <div class="min-w-0 max-w-full">
                                            <p class="truncate font-medium text-gray-900 dark:text-white" x-text="material.title" :title="material.title"></p>
                                            <p class="mt-1 block max-w-full truncate text-sm text-gray-500 dark:text-gray-400" x-text="shortText(material.description, 70)" :title="material.description || 'No description'"></p>
                                            <p class="mt-1 truncate text-xs text-gray-400 dark:text-gray-500">
                                                <span x-text="scopeLabel(material)"></span>
                                                <span> • </span>
                                                <span x-text="`${material.word_count || 0} words`"></span>
                                                <span> • </span>
                                                <span x-text="formatDate(material.created_at)"></span>
                                            </p>
                                        </div>
                                    </div>
                                </td>

                                <td class="px-4 py-4 text-sm text-gray-700 dark:text-gray-300" x-text="material.grade_number ? `Grade ${material.grade_number}` : 'All Grades'"></td>

                                <td class="px-4 py-4 text-sm text-gray-700 dark:text-gray-300" x-text="material.language || 'Not set'"></td>

                                <td class="px-4 py-4 text-center text-sm text-gray-700 dark:text-gray-300" x-text="material.questions?.length || 0"></td>

                                <td class="px-4 py-4 text-center">
                                    <span class="inline-flex max-w-full justify-center truncate rounded-full px-2.5 py-1 text-xs font-medium capitalize" :class="badgeClass(material.status)" x-text="material.status === 'pending' ? 'For Approval' : material.status"></span>
                                </td>

                                <td class="px-4 py-4">
                                    <div class="flex max-w-full flex-wrap items-center justify-end gap-2">
                                        <button type="button" @click="openReader(material)" title="View flipbook" class="inline-flex h-9 w-9 items-center justify-center rounded-lg border border-gray-300 text-gray-600 hover:bg-gray-50 dark:border-gray-700 dark:text-gray-300 dark:hover:bg-white/5">
                                            <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none">
                                                <path d="M2.25 12C3.75 7.75 7.25 5.25 12 5.25C16.75 5.25 20.25 7.75 21.75 12C20.25 16.25 16.75 18.75 12 18.75C7.25 18.75 3.75 16.25 2.25 12Z" stroke="currentColor" stroke-width="1.5"/>
                                                <path d="M12 15.25C13.7949 15.25 15.25 13.7949 15.25 12C15.25 10.2051 13.7949 8.75 12 8.75C10.2051 8.75 8.75 10.2051 8.75 12C8.75 13.7949 10.2051 15.25 12 15.25Z" stroke="currentColor" stroke-width="1.5"/>
                                            </svg>
                                        </button>

                                        <button type="button" x-show="material.status === 'pending' && material.school_id" @click="approveMaterial(material)" class="rounded-lg border border-green-200 px-3 py-2 text-sm font-medium text-green-700 hover:bg-green-50 dark:border-green-500/20 dark:text-green-400 dark:hover:bg-green-500/10">
                                            Approve
                                        </button>

                                        <button type="button" x-show="material.school_id" @click="archiveMaterial(material)" class="rounded-lg border border-red-200 px-3 py-2 text-sm font-medium text-red-600 hover:bg-red-50 dark:border-red-500/20 dark:hover:bg-red-500/10">
                                            Archive
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        </template>

                        <tr x-show="paginatedMaterials.length === 0">
                            <td colspan="6" class="px-5 py-12 text-center">
                                <h3 class="text-lg font-semibold text-gray-900 dark:text-white">No reading materials found</h3>
                                <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">Add a reading material or change the search filter.</p>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div class="flex flex-col gap-3 border-t border-gray-200 px-5 py-4 dark:border-gray-800 sm:flex-row sm:items-center sm:justify-between">
                <p class="text-sm text-gray-500 dark:text-gray-400">
                    Showing <span x-text="startItem"></span> to <span x-text="endItem"></span> of <span x-text="total"></span> materials
                </p>
                <div class="flex flex-wrap gap-2">
                    <button type="button" @click="goToPage(page - 1)" :disabled="page === 1" class="rounded-lg border border-gray-300 px-3 py-2 text-sm font-medium text-gray-700 disabled:cursor-not-allowed disabled:opacity-50 dark:border-gray-700 dark:text-gray-300">Previous</button>
                    <template x-for="pageNumber in visiblePages()" :key="pageNumber">
                        <button type="button" @click="goToPage(pageNumber)" class="rounded-lg border px-3 py-2 text-sm font-medium" :class="page === pageNumber ? 'border-brand-500 bg-brand-500 text-gray-900' : 'border-gray-300 text-gray-700 dark:border-gray-700 dark:text-gray-300'" x-text="pageNumber"></button>
                    </template>
                    <button type="button" @click="goToPage(page + 1)" :disabled="page === lastPage" class="rounded-lg border border-gray-300 px-3 py-2 text-sm font-medium text-gray-700 disabled:cursor-not-allowed disabled:opacity-50 dark:border-gray-700 dark:text-gray-300">Next</button>
                </div>
            </div>
        </div>
    </div>

    <div x-show="view === 'create'" class="w-full max-w-full overflow-hidden rounded-2xl border border-gray-200 bg-white dark:border-gray-800 dark:bg-white/[0.03]">
        <div class="flex flex-col gap-4 border-b border-gray-200 p-5 dark:border-gray-800 lg:flex-row lg:items-center lg:justify-between xl:p-6">
            <div>
                <button type="button" @click="backToTable" class="mb-3 inline-flex items-center gap-2 text-sm font-medium text-gray-500 hover:text-gray-800 dark:text-gray-400 dark:hover:text-white">
                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none"><path d="M15 18L9 12L15 6" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" /></svg>
                    Back to Reading Materials
                </button>
                <h2 class="text-xl font-semibold text-gray-900 dark:text-white">Add Reading Material</h2>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Step <span x-text="step"></span> of 2</p>
            </div>

            <div class="flex items-center gap-2">
                <div class="h-2 w-20 rounded-full" :class="step >= 1 ? 'bg-brand-500' : 'bg-gray-200 dark:bg-gray-800'"></div>
                <div class="h-2 w-20 rounded-full" :class="step >= 2 ? 'bg-brand-500' : 'bg-gray-200 dark:bg-gray-800'"></div>
            </div>
        </div>

        <form x-ref="materialForm" @submit.prevent="submitMaterial" enctype="multipart/form-data" class="p-5 xl:p-6">
            <div x-show="step === 1" class="space-y-6">
                <div>
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Reading Material Details</h3>
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">The material created by the principal will be saved as approved automatically and assigned to your school.</p>
                </div>

                <div class="grid grid-cols-1 gap-5 lg:grid-cols-[260px_1fr]">
                    <div>
                        <label class="mb-2 block text-sm font-medium text-gray-700 dark:text-gray-300">Cover Image</label>
                        <label class="flex min-h-[300px] cursor-pointer flex-col items-center justify-center overflow-hidden rounded-2xl border border-dashed border-gray-300 bg-gray-50 text-center dark:border-gray-700 dark:bg-gray-900/40">
                            <template x-if="coverPreview">
                                <img :src="coverPreview" alt="Cover preview" class="h-full min-h-[300px] w-full object-cover" />
                            </template>
                            <template x-if="!coverPreview">
                                <div class="p-6">
                                    <div class="mx-auto flex h-14 w-14 items-center justify-center rounded-full bg-brand-50 text-brand-500 dark:bg-brand-500/10">
                                        <svg class="h-7 w-7" viewBox="0 0 24 24" fill="none"><path d="M12 16V4M12 4L7.5 8.5M12 4L16.5 8.5" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/><path d="M20 16.5V18C20 19.1046 19.1046 20 18 20H6C4.89543 20 4 19.1046 4 18V16.5" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/></svg>
                                    </div>
                                    <p class="mt-4 text-sm font-medium text-gray-800 dark:text-white/90">Upload cover image</p>
                                    <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">JPG, PNG, or WEBP up to 4MB</p>
                                </div>
                            </template>
                            <input type="file" name="cover_image" accept="image/jpeg,image/png,image/webp" @change="handleCoverChange" class="hidden" />
                        </label>
                        <template x-if="formErrors.cover_image">
                            <p class="mt-2 text-sm text-red-600" x-text="formErrors.cover_image[0]"></p>
                        </template>
                    </div>

                    <div class="space-y-5">
                        <div class="grid grid-cols-1 gap-5 md:grid-cols-2">
                            <div>
                                <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">Title</label>
                                <input name="title" type="text" class="h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 shadow-theme-xs focus:border-brand-400 focus:outline-hidden focus:ring-3 focus:ring-brand-500/10 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90" />
                                <template x-if="formErrors.title">
                                    <p class="mt-1 text-sm text-red-600" x-text="formErrors.title[0]"></p>
                                </template>
                            </div>

                            <div>
                                <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">Grade Level</label>
                                <select name="grade_level_id" class="h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 shadow-theme-xs focus:border-brand-400 focus:outline-hidden focus:ring-3 focus:ring-brand-500/10 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
                                    <option value="">All Grades</option>
                                    @foreach ($gradeLevels as $grade)
                                        <option value="{{ $grade['grade_level_id'] }}">Grade {{ $grade['grade_number'] }}</option>
                                    @endforeach
                                </select>
                                <template x-if="formErrors.grade_level_id">
                                    <p class="mt-1 text-sm text-red-600" x-text="formErrors.grade_level_id[0]"></p>
                                </template>
                            </div>

                            <div>
                                <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">Language</label>
                                <select name="language" required class="h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 shadow-theme-xs focus:border-brand-400 focus:outline-hidden focus:ring-3 focus:ring-brand-500/10 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
                                    <option value="">Select language</option>
                                    <option value="Filipino">Filipino</option>
                                    <option value="English">English</option>
                                </select>
                                <template x-if="formErrors.language">
                                    <p class="mt-1 text-sm text-red-600" x-text="formErrors.language[0]"></p>
                                </template>
                            </div>
                        </div>

                        <div>
                            <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">Description</label>
                            <textarea name="description" rows="3" class="w-full rounded-lg border border-gray-300 bg-transparent px-4 py-3 text-sm text-gray-800 shadow-theme-xs focus:border-brand-400 focus:outline-hidden focus:ring-3 focus:ring-brand-500/10 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90"></textarea>
                            <template x-if="formErrors.description">
                                <p class="mt-1 text-sm text-red-600" x-text="formErrors.description[0]"></p>
                            </template>
                        </div>

                        <div>
                            <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">Story Content</label>
                            <textarea name="story_content" rows="11" placeholder="Type or paste the complete story here." class="w-full rounded-lg border border-gray-300 bg-transparent px-4 py-3 text-sm text-gray-800 shadow-theme-xs focus:border-brand-400 focus:outline-hidden focus:ring-3 focus:ring-brand-500/10 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90"></textarea>
                            <template x-if="formErrors.story_content">
                                <p class="mt-1 text-sm text-red-600" x-text="formErrors.story_content[0]"></p>
                            </template>
                        </div>
                    </div>
                </div>

                <div class="flex justify-end gap-3 border-t border-gray-200 pt-5 dark:border-gray-800">
                    <button type="button" @click="backToTable" class="rounded-lg border border-gray-300 px-4 py-2.5 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:text-gray-300 dark:hover:bg-white/5">Cancel</button>
                    <button type="button" @click="nextStep" class="rounded-lg bg-brand-500 px-5 py-2.5 text-sm font-semibold text-gray-900 hover:bg-brand-400">Next</button>
                </div>
            </div>

            <div x-show="step === 2" class="space-y-6">
                <div>
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Quiz Questions</h3>
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Only multiple choice questions are used for reading material quizzes.</p>
                </div>

                <template x-if="formErrors.questions">
                    <div class="rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700 dark:border-red-500/20 dark:bg-red-500/10 dark:text-red-400" x-text="formErrors.questions[0]"></div>
                </template>

                <div class="space-y-4">
                    <template x-for="(question, index) in questions" :key="index">
                        <div class="rounded-2xl border border-gray-200 bg-gray-50 p-5 dark:border-gray-800 dark:bg-gray-900/40">
                            <div class="mb-4 flex items-center justify-between gap-3">
                                <h4 class="font-semibold text-gray-900 dark:text-white" x-text="`Question ${index + 1}`"></h4>
                                <button type="button" @click="removeQuestion(index)" x-show="questions.length > 1" class="text-sm font-medium text-red-600">Remove</button>
                            </div>

                            <div class="grid grid-cols-1 gap-4 lg:grid-cols-[1fr_120px]">
                                <div>
                                    <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">Question</label>
                                    <textarea rows="2" x-model="question.question_text" :name="`questions[${index}][question_text]`" class="w-full rounded-lg border border-gray-300 bg-white px-4 py-3 text-sm text-gray-800 shadow-theme-xs focus:border-brand-400 focus:outline-hidden focus:ring-3 focus:ring-brand-500/10 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90"></textarea>
                                    <template x-if="formErrors[`questions.${index}.question_text`]">
                                        <p class="mt-1 text-sm text-red-600" x-text="formErrors[`questions.${index}.question_text`][0]"></p>
                                    </template>
                                </div>
                                <div>
                                    <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">Points</label>
                                    <input type="number" min="1" x-model="question.points" :name="`questions[${index}][points]`" class="h-11 w-full rounded-lg border border-gray-300 bg-white px-4 py-2.5 text-sm text-gray-800 shadow-theme-xs focus:border-brand-400 focus:outline-hidden focus:ring-3 focus:ring-brand-500/10 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90" />
                                </div>
                            </div>

                            <div class="mt-4">
                                <label class="mb-2 block text-sm font-medium text-gray-700 dark:text-gray-300">Choices</label>
                                <div class="grid grid-cols-1 gap-3 md:grid-cols-2">
                                    <template x-for="(choice, choiceIndex) in question.choices" :key="choiceIndex">
                                        <div class="flex gap-2">
                                            <input type="text" x-model="question.choices[choiceIndex]" :name="`questions[${index}][choices][${choiceIndex}]`" :placeholder="`Choice ${choiceLetter(choiceIndex)}`" class="h-11 min-w-0 flex-1 rounded-lg border border-gray-300 bg-white px-4 py-2.5 text-sm text-gray-800 shadow-theme-xs focus:border-brand-400 focus:outline-hidden focus:ring-3 focus:ring-brand-500/10 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90" />
                                            <button type="button" @click="removeChoice(question, choiceIndex)" class="rounded-lg border border-gray-300 px-3 text-sm text-gray-600 hover:bg-gray-50 dark:border-gray-700 dark:text-gray-300 dark:hover:bg-white/5">Remove</button>
                                        </div>
                                    </template>
                                </div>
                                <button type="button" @click="addChoice(question)" class="mt-3 text-sm font-medium text-brand-600 dark:text-brand-400">Add choice</button>
                            </div>

                            <div class="mt-4">
                                <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">Correct Answer</label>
                                <select x-model="question.correct_answer" :name="`questions[${index}][correct_answer]`" class="h-11 w-full rounded-lg border border-gray-300 bg-white px-4 py-2.5 text-sm text-gray-800 shadow-theme-xs focus:border-brand-400 focus:outline-hidden focus:ring-3 focus:ring-brand-500/10 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
                                    <option value="">Select correct answer</option>
                                    <template x-for="option in cleanChoices(question)" :key="option.letter">
                                        <option :value="option.letter" x-text="`${option.letter}. ${option.choice}`"></option>
                                    </template>
                                </select>
                                <template x-if="formErrors[`questions.${index}.correct_answer`]">
                                    <p class="mt-1 text-sm text-red-600" x-text="formErrors[`questions.${index}.correct_answer`][0]"></p>
                                </template>
                            </div>
                        </div>
                    </template>
                </div>

                <button type="button" @click="addQuestion" class="rounded-lg border border-gray-300 px-4 py-2.5 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:text-gray-300 dark:hover:bg-white/5">
                    Add Another Question
                </button>

                <div class="flex flex-col-reverse gap-3 border-t border-gray-200 pt-5 dark:border-gray-800 sm:flex-row sm:justify-end">
                    <button type="button" @click="previousStep" class="rounded-lg border border-gray-300 px-4 py-2.5 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:text-gray-300 dark:hover:bg-white/5">Back</button>
                    <button type="submit" :disabled="loading" class="rounded-lg bg-brand-500 px-5 py-2.5 text-sm font-semibold text-gray-900 hover:bg-brand-400 disabled:cursor-not-allowed disabled:opacity-60">
                        <span x-show="!loading">Save Reading Material</span>
                        <span x-show="loading">Saving...</span>
                    </button>
                </div>
            </div>
        </form>
    </div>

    <div x-show="showReader" x-transition.opacity class="fixed inset-0 z-99999 w-screen max-w-[100vw] overflow-y-auto overflow-x-hidden bg-gray-950/80 px-2 py-3 sm:px-4 sm:py-6">
        <div class="mx-auto flex w-full max-w-full flex-col gap-3 sm:max-w-7xl sm:gap-4">
            <div class="flex flex-col gap-3 rounded-2xl bg-white p-3 shadow-theme-xl dark:bg-gray-900 sm:p-4 lg:flex-row lg:items-center lg:justify-between">
                <div>
                    <p class="text-sm text-gray-500 dark:text-gray-400">Flipbook Preview</p>
                    <h3 class="line-clamp-2 text-lg font-semibold text-gray-900 dark:text-white sm:text-xl" x-text="currentMaterial?.title"></h3>
                </div>
                <div class="flex w-full flex-wrap items-center gap-2 sm:w-auto">
                    <span class="col-span-2 rounded-lg bg-gray-100 px-3 py-2 text-center text-sm font-medium text-gray-700 dark:bg-white/10 dark:text-gray-300 sm:col-span-1" x-text="readerPageLabel"></span>
                    <button type="button" @click="previousReaderPage" :disabled="pageIndex === 0" class="rounded-lg border border-gray-300 px-3 py-2 text-sm font-medium text-gray-700 disabled:cursor-not-allowed disabled:opacity-50 dark:border-gray-700 dark:text-gray-300">Previous</button>
                    <button type="button" @click="nextReaderPage" :disabled="pageIndex + readerStep() >= readerPages.length" class="rounded-lg border border-gray-300 px-3 py-2 text-sm font-medium text-gray-700 disabled:cursor-not-allowed disabled:opacity-50 dark:border-gray-700 dark:text-gray-300">Next</button>
                    <button type="button" @click="closeReader" class="rounded-lg bg-brand-500 px-3 py-2 text-sm font-semibold text-gray-900 hover:bg-brand-400">Close</button>
                </div>
            </div>

            <div class="readbee-book-stage w-full">
                <div class="readbee-book-spread mx-auto grid grid-cols-1 overflow-hidden rounded-[20px] md:grid-cols-2 md:rounded-[28px]">
                    <template x-for="(bookPage, sideIndex) in [leftPage, rightPage]" :key="sideIndex">
                        <section class="readbee-page flex-col p-5 sm:p-6 md:p-10" :class="sideIndex === 1 ? 'hidden border-l border-gray-200 dark:border-gray-700 md:flex' : 'flex'">
                            <template x-if="bookPage">
                                <div class="flex h-full min-h-0 flex-col">
                                    <template x-if="bookPage.type === 'cover'">
                                        <div class="flex h-full flex-col items-center justify-center text-center">
                                            <template x-if="bookPage.image">
                                                <img :src="bookPage.image" :alt="bookPage.title" class="mb-5 h-40 w-32 rounded-2xl object-cover shadow-theme-lg sm:mb-8 sm:h-56 sm:w-44" />
                                            </template>
                                            <template x-if="!bookPage.image">
                                                <div class="mb-6 flex h-20 w-20 items-center justify-center rounded-full bg-brand-500/20 text-brand-600 dark:text-brand-400">
                                                    <svg class="h-10 w-10" viewBox="0 0 24 24" fill="none"><path d="M5 5.75C5 4.7835 5.7835 4 6.75 4H18C18.5523 4 19 4.44772 19 5V17.5C19 18.3284 18.3284 19 17.5 19H7.25C6.00736 19 5 17.9926 5 16.75V5.75Z" stroke="currentColor" stroke-width="1.5"/><path d="M7.5 8H15.5M7.5 11.5H15.5M7.5 15H12" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/></svg>
                                                </div>
                                            </template>
                                            <p class="text-sm font-semibold uppercase tracking-[0.3em] text-brand-600 dark:text-brand-400">ReadBee</p>
                                            <h1 class="mt-4 text-2xl font-bold text-gray-900 dark:text-white sm:text-4xl" x-text="bookPage.title"></h1>
                                            <p class="mt-4 max-w-lg text-sm text-gray-600 dark:text-gray-300 sm:text-base" x-text="bookPage.subtitle"></p>
                                            <p class="mt-6 rounded-full bg-white/70 px-4 py-2 text-sm font-medium text-gray-600 shadow-theme-xs dark:bg-white/10 dark:text-gray-300" x-text="bookPage.meta"></p>
                                        </div>
                                    </template>

                                    <template x-if="bookPage.type === 'story'">
                                        <div>
                                            <p class="text-xs font-semibold uppercase tracking-[0.25em] text-brand-600 dark:text-brand-400">Story</p>
                                            <h2 class="mt-3 text-xl font-semibold text-gray-900 dark:text-white sm:text-2xl" x-text="bookPage.title"></h2>
                                            <div class="readbee-page-body mt-6 whitespace-pre-line text-base leading-7 text-gray-800 dark:text-gray-100" x-text="bookPage.body"></div>
                                        </div>
                                    </template>

                                    <template x-if="bookPage.type === 'quiz_intro'">
                                        <div class="flex h-full flex-col items-center justify-center text-center">
                                            <p class="text-xs font-semibold uppercase tracking-[0.25em] text-brand-600 dark:text-brand-400">Comprehension Check</p>
                                            <h2 class="mt-4 text-2xl font-bold text-gray-900 dark:text-white sm:text-4xl" x-text="bookPage.title"></h2>
                                            <p class="mt-4 max-w-md text-sm text-gray-600 dark:text-gray-300 sm:text-lg" x-text="bookPage.subtitle"></p>
                                            <p class="mt-6 rounded-full bg-white/70 px-4 py-2 text-sm font-medium text-gray-600 shadow-theme-xs dark:bg-white/10 dark:text-gray-300" x-text="bookPage.meta"></p>
                                        </div>
                                    </template>

                                    <template x-if="bookPage.type === 'question'">
                                        <div>
                                            <p class="text-xs font-semibold uppercase tracking-[0.25em] text-brand-600 dark:text-brand-400" x-text="bookPage.title"></p>
                                            <h2 class="mt-4 text-lg font-semibold leading-7 text-gray-900 dark:text-white sm:text-2xl sm:leading-8" x-text="bookPage.question.question_text"></h2>
                                            <div class="mt-6 space-y-3">
                                                <template x-for="choice in bookPage.question.choices || []" :key="choice.letter || choice">
                                                    <div
                                                        class="rounded-xl border border-gray-200 bg-white/70 px-4 py-3 text-gray-800 dark:border-gray-700 dark:bg-white/5 dark:text-gray-100"
                                                        x-text="typeof choice === 'object' ? `${choice.letter}. ${choice.choice}` : choice"
                                                    ></div>
                                                </template>
                                            </div>
                                            <div class="mt-8 rounded-xl border border-green-200 bg-green-50 p-4 text-green-700 dark:border-green-500/20 dark:bg-green-500/10 dark:text-green-400">
                                                <p class="text-xs font-semibold uppercase tracking-[0.18em]">Answer Key</p>
                                                <p class="mt-2 font-medium" x-text="bookPage.question.correct_answer ? `Letter ${bookPage.question.correct_answer}` : 'Not set'"></p>
                                            </div>
                                        </div>
                                    </template>

                                    <template x-if="bookPage.type === 'blank'">
                                        <div class="flex h-full flex-col items-center justify-center text-center text-gray-600 dark:text-gray-300">
                                            <h2 class="text-3xl font-bold text-gray-900 dark:text-white" x-text="bookPage.title"></h2>
                                            <p class="mt-3" x-text="bookPage.subtitle"></p>
                                        </div>
                                    </template>
                                </div>
                            </template>
                        </section>
                    </template>
                </div>
            </div>
        </div>
    </div>
</div>
