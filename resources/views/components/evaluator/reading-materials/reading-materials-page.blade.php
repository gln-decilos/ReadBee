@props([
    'materials' => [],
    'myRequests' => [],
    'gradeLevels' => [],
])

<script>
    window.evaluatorReadingMaterialsPage = function(initialMaterials, initialRequests, gradeLevels) {
        return {
            materials: initialMaterials || [],
            myRequests: initialRequests || [],
            gradeLevels: gradeLevels || [],
            view: 'books',
            activeTab: 'bookshelf',
            search: '',
            gradeFilter: 'all',
            page: 1,
            perPage: 18,
            requestPage: 1,
            requestsPerPage: 6,
            showReader: false,
            currentMaterial: null,
            isFullscreen: false,
            formErrors: {},
            loading: false,
            successModal: false,
            successMessage: '',
            coverPreview: '',
            step: 1,
            questions: [
                {
                    question_text: '',
                    choices: ['', '', '', ''],
                    correct_answer: '',
                    points: 1,
                }
            ],

            init() {
                this.$watch('search', () => this.page = 1);
                this.$watch('gradeFilter', () => this.page = 1);

                document.addEventListener('fullscreenchange', () => {
                    if (!document.fullscreenElement) {
                        this.isFullscreen = false;
                    }
                });
            },

            get filteredMaterials() {
                const term = this.search.trim().toLowerCase();

                return this.materials.filter((material) => {
                    const matchesGrade = this.gradeFilter === 'all' || String(material.grade_level_id || '') === String(this.gradeFilter);
                    const haystack = [
                        material.title,
                        material.description,
                        material.language,
                        material.grade_number ? `grade ${material.grade_number}` : 'all grades',
                        material.scope_label,
                    ].join(' ').toLowerCase();

                    return matchesGrade && (!term || haystack.includes(term));
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

            get requestTotal() {
                return this.myRequests.length;
            },

            get requestLastPage() {
                return Math.max(Math.ceil(this.requestTotal / this.requestsPerPage), 1);
            },

            get paginatedRequests() {
                if (this.requestPage > this.requestLastPage) {
                    this.requestPage = this.requestLastPage;
                }

                const start = (this.requestPage - 1) * this.requestsPerPage;
                return this.myRequests.slice(start, start + this.requestsPerPage);
            },

            get requestStartItem() {
                if (this.requestTotal === 0) return 0;
                return (this.requestPage - 1) * this.requestsPerPage + 1;
            },

            get requestEndItem() {
                return Math.min(this.requestPage * this.requestsPerPage, this.requestTotal);
            },

            get pendingRequests() {
                return this.myRequests.filter((material) => material.status === 'pending').length;
            },

            get approvedRequests() {
                return this.myRequests.filter((material) => material.status === 'approved').length;
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

            visibleRequestPages() {
                const pages = [];
                const start = Math.max(1, this.requestPage - 2);
                const end = Math.min(this.requestLastPage, this.requestPage + 2);

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

            goToRequestPage(pageNumber) {
                if (pageNumber >= 1 && pageNumber <= this.requestLastPage) {
                    this.requestPage = pageNumber;
                }
            },

            setTab(tab) {
                this.activeTab = tab;
                window.scrollTo({ top: 0, behavior: 'smooth' });
            },

            openCreateForm() {
                this.resetForm();
                this.view = 'create';
                this.step = 1;
                window.scrollTo({ top: 0, behavior: 'smooth' });
            },

            backToBooks() {
                this.view = 'books';
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

                    if (!input || !String(input.value || '').trim()) {
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

            removeChoice(question, index) {
                if (question.choices.length <= 2) return;
                question.choices.splice(index, 1);
                question.correct_answer = '';
            },

            choiceLetter(index) {
                return String.fromCharCode(65 + index);
            },

            cleanChoices(question) {
                return (question.choices || [])
                    .map((choice, index) => ({
                        letter: this.choiceLetter(index),
                        choice: String(choice || '').trim(),
                    }))
                    .filter((choice) => choice.choice);
            },

            async submitMaterial() {
                this.formErrors = {};
                this.loading = true;

                const formData = new FormData(this.$refs.materialForm);

                try {
                    const response = await fetch("{{ route('evaluator.reading-materials.store') }}", {
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

                        alert(data.message || 'Failed to submit the reading material.');
                        return;
                    }

                    this.myRequests.unshift(data.material);
                    this.view = 'books';
                    this.activeTab = 'requests';
                    this.requestPage = 1;
                    this.step = 1;
                    this.resetForm();
                    this.successMessage = data.message || 'Reading material submitted for principal approval.';
                    this.successModal = true;
                } catch (error) {
                    console.error('Submit evaluator reading material error:', error);
                    alert('An error occurred while submitting the reading material.');
                } finally {
                    this.loading = false;
                }
            },

            openReader(material) {
                this.currentMaterial = material;
                this.showReader = true;
                this.isFullscreen = false;
                document.body.classList.add('overflow-hidden');
            },

            async closeReader() {
                if (document.fullscreenElement) {
                    try {
                        await document.exitFullscreen();
                    } catch (error) {
                        console.warn('Could not exit fullscreen mode.', error);
                    }
                }

                this.showReader = false;
                this.currentMaterial = null;
                this.isFullscreen = false;
                document.body.classList.remove('overflow-hidden');
            },

            async toggleFullscreen() {
                this.isFullscreen = !this.isFullscreen;

                if (this.isFullscreen && this.$refs.readerOverlay?.requestFullscreen && !document.fullscreenElement) {
                    try {
                        await this.$refs.readerOverlay.requestFullscreen();
                    } catch (error) {
                        console.warn('Browser fullscreen was not allowed, using distraction-free layout only.', error);
                    }
                }

                if (!this.isFullscreen && document.fullscreenElement) {
                    try {
                        await document.exitFullscreen();
                    } catch (error) {
                        console.warn('Could not exit fullscreen mode.', error);
                    }
                }
            },

            storyParagraphs(material = null) {
                const source = material || this.currentMaterial || {};
                const content = String(source.story?.content || 'No reading content has been added yet.').replace(/\r\n/g, '\n').trim();

                if (!content) {
                    return ['No reading content has been added yet.'];
                }

                return content
                    .split(/\n\s*\n/)
                    .map((paragraph) => paragraph.trim())
                    .filter(Boolean);
            },

            requestStatusLabel(status) {
                if (status === 'pending') return 'Waiting for Principal Approval';
                if (status === 'approved') return 'Approved';
                if (status === 'rejected') return 'Rejected';
                return status || 'Pending';
            },

            statusBadgeClass(status) {
                if (status === 'approved') return 'bg-green-100 text-green-700 dark:bg-green-500/10 dark:text-green-400';
                if (status === 'pending') return 'bg-yellow-100 text-yellow-700 dark:bg-yellow-500/10 dark:text-yellow-400';
                if (status === 'rejected') return 'bg-red-100 text-red-700 dark:bg-red-500/10 dark:text-red-400';
                return 'bg-gray-100 text-gray-700 dark:bg-white/10 dark:text-gray-300';
            },

            shortText(value, limit = 90) {
                const text = String(value || '').replace(/\s+/g, ' ').trim();

                if (!text) {
                    return 'No description';
                }

                return text.length > limit ? `${text.slice(0, limit).trim()}...` : text;
            },

            formatDate(value) {
                if (!value) return 'Not set';

                return new Date(value).toLocaleDateString('en-PH', {
                    year: 'numeric',
                    month: 'short',
                    day: 'numeric',
                });
            },
        };
    };
</script>

<style>
    [x-cloak] {
        display: none !important;
    }

    .evaluator-reading-materials-root,
    .evaluator-reading-materials-root * {
        box-sizing: border-box;
    }

    .evaluator-shelf-panel {
        background:
            linear-gradient(180deg, rgba(255,255,255,0.98), rgba(255,255,255,0.94)),
            repeating-linear-gradient(180deg, transparent 0 296px, rgba(120, 72, 18, 0.14) 296px 302px, rgba(120, 72, 18, 0.08) 302px 314px);
    }

    .dark .evaluator-shelf-panel {
        background:
            linear-gradient(180deg, rgba(17,24,39,0.98), rgba(17,24,39,0.94)),
            repeating-linear-gradient(180deg, transparent 0 296px, rgba(255,255,255,0.10) 296px 302px, rgba(255,255,255,0.06) 302px 314px);
    }

    .evaluator-book-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(175px, 1fr));
        column-gap: clamp(1.75rem, 2.25vw, 2.5rem);
        row-gap: 2.75rem;
        align-items: end;
    }

    .evaluator-book-item {
        min-width: 0;
        padding-inline: 0.35rem;
    }

    .evaluator-book-container {
        z-index: 1;
        perspective: 3000px;
    }

    .evaluator-book-container .readbee-material-book {
        position: relative;
        display: block;
        width: 150px;
        height: 240px;
        margin: 14px auto 8px;
        border-radius: 2px 4px 4px 2px;
        background: linear-gradient(45deg, #DAD5DC 0%, #f2ebf4 100%);
        box-shadow: 12px 14px 18px rgba(17, 24, 39, 0.18), 0 2px 0 rgba(255,255,255,0.45) inset;
        color: #2b2b2b;
        cursor: pointer;
        transform-style: preserve-3d;
        transition: transform .45s ease, box-shadow .45s ease;
    }

    .evaluator-book-container .readbee-material-book::before {
        content: '';
        position: absolute;
        top: 3px;
        left: 100%;
        width: 20px;
        height: 234px;
        border-radius: 0 4px 4px 0;
        background: linear-gradient(90deg, #f8f4df 0%, #d9d0b7 100%);
        transform: rotateY(90deg);
        transform-origin: left;
    }

    .evaluator-book-container .readbee-material-book:hover,
    .evaluator-book-container .readbee-material-book:focus {
        transform: rotate3d(0, 1, 0, 34deg) translateY(-3px);
        box-shadow: 18px 20px 28px rgba(17, 24, 39, 0.22);
        outline: none;
    }

    .evaluator-book-container .readbee-material-book > div,
    .evaluator-book-container .readbee-material-front > div {
        display: block;
        position: absolute;
    }

    .evaluator-book-container .readbee-material-front {
        transform-style: preserve-3d;
        transform-origin: 0% 50%;
        transition: transform .45s ease;
        transform: translate3d(0, 0, 20px);
        z-index: 10;
    }

    .evaluator-book-container .readbee-material-front > div {
        width: 150px;
        height: 240px;
        border-radius: 0 3px 3px 0;
        box-shadow: inset 4px 0 10px rgba(0, 0, 0, 0.12);
        overflow: hidden;
    }

    .evaluator-book-container .readbee-material-cover {
        background: linear-gradient(45deg, #DAD5DC 0%, #f2ebf4 100%);
    }

    .evaluator-book-container .readbee-material-cover::after {
        content: '';
        position: absolute;
        top: 0;
        left: 10px;
        bottom: 0;
        width: 3px;
        background: rgba(0, 0, 0, 0.12);
        box-shadow: 1px 0 3px rgba(255, 255, 255, 0.2);
    }

    .evaluator-book-container .readbee-default-cover {
        width: 100%;
        height: 100%;
        background:
            radial-gradient(circle at top right, rgba(255, 255, 255, 0.75), transparent 30%),
            linear-gradient(135deg, #fff7c2 0%, #ffd95a 45%, #f2a900 100%);
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 16px;
        text-align: center;
    }

    .evaluator-book-container .readbee-default-cover-title {
        color: #31230b;
        font-size: 16px;
        font-weight: 800;
        line-height: 1.25;
        word-break: break-word;
    }

    .evaluator-reader-shell {
        min-height: calc(100vh - 2rem);
    }

    .evaluator-reader-content {
        background: #fffdf5;
        min-height: clamp(460px, calc(100vh - 150px), 820px);
        display: flex;
        align-items: flex-start;
    }

    .evaluator-reader-content > div {
        width: 100%;
    }

    .dark .evaluator-reader-content {
        background: #0b1220;
    }

    .evaluator-reader-fullscreen {
        background: #fffdf5 !important;
        padding: 0 !important;
    }

    .dark .evaluator-reader-fullscreen {
        background: #030712 !important;
    }

    .evaluator-reader-fullscreen .evaluator-reader-shell {
        max-width: none !important;
        min-height: 100vh;
    }

    .evaluator-reader-fullscreen .evaluator-reader-content {
        border-radius: 0 !important;
        min-height: calc(100vh - 72px);
        border: 0 !important;
    }

    .evaluator-reader-fullscreen .evaluator-reader-text {
        max-width: 62rem;
        font-size: clamp(1.2rem, 1.45vw, 1.6rem);
        line-height: 2;
    }
</style>

<div
    x-data="evaluatorReadingMaterialsPage(@js($materials), @js($myRequests), @js($gradeLevels))"
    x-cloak
    @keydown.escape.window="showReader ? closeReader() : null"
    class="evaluator-reading-materials-root w-full max-w-full overflow-x-hidden"
>
    <div x-show="successModal" x-transition.opacity class="fixed inset-0 z-99999 flex items-center justify-center bg-gray-900/60 px-4">
        <div @click.outside="successModal = false" class="w-full max-w-md rounded-2xl bg-white p-6 text-center shadow-theme-xl dark:bg-gray-900">
            <div class="mx-auto flex h-14 w-14 items-center justify-center rounded-full bg-green-100 text-green-600 dark:bg-green-500/10 dark:text-green-400">
                <svg class="h-7 w-7" viewBox="0 0 24 24" fill="none"><path d="M20 6L9 17L4 12" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" /></svg>
            </div>
            <h3 class="mt-4 text-lg font-semibold text-gray-900 dark:text-white">Submitted</h3>
            <p class="mt-2 text-sm text-gray-500 dark:text-gray-400" x-text="successMessage"></p>
            <button type="button" @click="successModal = false" class="mt-5 rounded-lg bg-brand-500 px-5 py-2.5 text-sm font-semibold text-gray-900 hover:bg-brand-400">OK</button>
        </div>
    </div>

    <div x-show="view === 'books'" class="space-y-6">
        <div class="rounded-2xl border border-gray-200 bg-white p-5 dark:border-gray-800 dark:bg-white/[0.03] xl:p-6">
            <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
                <div class="flex items-center gap-3">
                    <div class="flex h-12 w-12 items-center justify-center rounded-2xl bg-brand-500/15 text-brand-600 dark:text-brand-400">
                        <svg class="h-7 w-7" viewBox="0 0 24 24" fill="none"><path d="M4.75 5.75C4.75 4.64543 5.64543 3.75 6.75 3.75H18C18.6904 3.75 19.25 4.30964 19.25 5V17.25C19.25 18.3546 18.3546 19.25 17.25 19.25H7C5.75736 19.25 4.75 18.2426 4.75 17V5.75Z" stroke="currentColor" stroke-width="1.5"/><path d="M7.75 8.25H15.75M7.75 11.75H15.75M7.75 15.25H12.75" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/></svg>
                    </div>
                    <div>
                        <h2 class="text-xl font-semibold text-gray-900 dark:text-white">Reading Materials</h2>
                        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Approved books are ready for pupil reading. Customized materials are sent to the principal for approval.</p>
                    </div>
                </div>

                <button type="button" @click="openCreateForm" class="inline-flex items-center justify-center rounded-lg bg-brand-500 px-4 py-2.5 text-sm font-semibold text-gray-900 shadow-theme-xs hover:bg-brand-400">
                    Customize Reading Material
                </button>
            </div>

            <div class="mt-5 flex flex-col gap-3 border-t border-gray-200 pt-4 dark:border-gray-800 xl:flex-row xl:items-center xl:justify-between">
                <div class="inline-flex w-max max-w-full rounded-lg bg-gray-100 p-0.5 dark:bg-gray-900">
                    <button type="button" @click="setTab('bookshelf')" class="rounded-md px-3 py-1.5 text-xs font-semibold transition" :class="activeTab === 'bookshelf' ? 'bg-white text-gray-900 shadow-theme-xs dark:bg-white/10 dark:text-white' : 'text-gray-500 hover:text-gray-800 dark:text-gray-400 dark:hover:text-white'">
                        Book Shelf
                    </button>
                    <button type="button" @click="setTab('requests')" class="rounded-md px-3 py-1.5 text-xs font-semibold transition" :class="activeTab === 'requests' ? 'bg-white text-gray-900 shadow-theme-xs dark:bg-white/10 dark:text-white' : 'text-gray-500 hover:text-gray-800 dark:text-gray-400 dark:hover:text-white'">
                        My Requests
                        <span class="ml-1 rounded-full bg-yellow-100 px-1.5 py-0.5 text-[10px] text-yellow-700 dark:bg-yellow-500/10 dark:text-yellow-400" x-text="pendingRequests"></span>
                    </button>
                </div>

                <div x-show="activeTab === 'bookshelf'" class="flex w-full min-w-0 flex-nowrap items-center gap-2 xl:max-w-xl">
                    <input type="search" x-model="search" placeholder="Search reading materials..." class="h-10 min-w-0 flex-1 rounded-lg border border-gray-300 bg-transparent px-3 py-2 text-sm text-gray-800 shadow-theme-xs placeholder:text-gray-400 focus:border-brand-400 focus:outline-hidden focus:ring-3 focus:ring-brand-500/10 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90" />

                    <select x-model="gradeFilter" class="h-10 w-36 shrink-0 rounded-lg border border-gray-300 bg-transparent px-3 py-2 text-sm text-gray-800 shadow-theme-xs focus:border-brand-400 focus:outline-hidden focus:ring-3 focus:ring-brand-500/10 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90 sm:w-44">
                        <option value="all">All Grades</option>
                        <template x-for="grade in gradeLevels" :key="grade.grade_level_id">
                            <option :value="grade.grade_level_id" x-text="`Grade ${grade.grade_number}`"></option>
                        </template>
                    </select>
                </div>
            </div>
        </div>

        <div x-show="activeTab === 'bookshelf'" class="overflow-hidden rounded-2xl border border-gray-200 bg-white dark:border-gray-800 dark:bg-white/[0.03]">
            <div class="flex flex-col gap-2 border-b border-gray-200 p-5 dark:border-gray-800 sm:flex-row sm:items-end sm:justify-between xl:p-6">
                <div>
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Book Shelf</h3>
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Hover a book to preview the animation. Click a book to open the reading content.</p>
                </div>
                <p class="text-sm text-gray-500 dark:text-gray-400">Showing <span x-text="startItem"></span> to <span x-text="endItem"></span> of <span x-text="total"></span> book(s)</p>
            </div>

            <div class="evaluator-shelf-panel p-4 sm:p-5 xl:p-6">
                <div class="evaluator-book-grid">
                    <template x-for="material in paginatedMaterials" :key="material.material_id">
                        <article class="evaluator-book-item flex flex-col items-center text-center">
                            <div class="evaluator-book-container" @click="openReader(material)" :title="`View reading content: ${material.title}`">
                                <div class="readbee-material-book" role="button" tabindex="0" @keydown.enter="openReader(material)" :aria-label="`View reading content: ${material.title}`">
                                    <div class="readbee-material-front">
                                        <div class="readbee-material-cover">
                                            <template x-if="material.cover_image">
                                                <img :src="material.cover_image" :alt="material.title" class="h-full w-full object-cover" />
                                            </template>
                                            <template x-if="!material.cover_image">
                                                <div class="readbee-default-cover">
                                                    <div class="readbee-default-cover-title" x-text="material.title"></div>
                                                </div>
                                            </template>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <h4 class="mt-2 line-clamp-2 max-w-[165px] text-sm font-semibold leading-snug text-gray-900 dark:text-white" x-text="material.title"></h4>
                        </article>
                    </template>
                </div>

                <div x-show="filteredMaterials.length === 0" class="rounded-2xl border border-dashed border-gray-300 bg-white/70 p-10 text-center dark:border-gray-700 dark:bg-gray-900/50">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white">No approved reading materials found</h3>
                    <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">Try a different search or submit a customized reading material for principal approval.</p>
                </div>
            </div>

            <div class="flex flex-col gap-3 border-t border-gray-200 px-5 py-4 dark:border-gray-800 sm:flex-row sm:items-center sm:justify-between">
                <p class="text-sm text-gray-500 dark:text-gray-400">
                    Page <span x-text="page"></span> of <span x-text="lastPage"></span>
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

        <div x-show="activeTab === 'requests'" class="overflow-hidden rounded-2xl border border-gray-200 bg-white dark:border-gray-800 dark:bg-white/[0.03]">
            <div class="flex flex-col gap-3 border-b border-gray-200 p-5 dark:border-gray-800 sm:flex-row sm:items-center sm:justify-between xl:p-6">
                <div>
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white">My Customized Material Requests</h3>
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Pending materials must be approved by the principal before pupils can use them.</p>
                </div>
                <div class="flex flex-wrap gap-2 text-xs font-medium">
                    <span class="rounded-full bg-yellow-100 px-3 py-1 text-yellow-700 dark:bg-yellow-500/10 dark:text-yellow-400">Pending: <span x-text="pendingRequests"></span></span>
                    <span class="rounded-full bg-green-100 px-3 py-1 text-green-700 dark:bg-green-500/10 dark:text-green-400">Approved: <span x-text="approvedRequests"></span></span>
                </div>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full min-w-[760px] divide-y divide-gray-200 dark:divide-gray-800">
                    <thead class="bg-gray-50 dark:bg-gray-900/50">
                        <tr>
                            <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">Material</th>
                            <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">Grade</th>
                            <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">Date Submitted</th>
                            <th class="px-5 py-3 text-right text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">Status</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                        <template x-for="request in paginatedRequests" :key="`request-${request.material_id}`">
                            <tr class="transition hover:bg-gray-50 dark:hover:bg-white/[0.03]">
                                <td class="px-5 py-4">
                                    <p class="font-medium text-gray-900 dark:text-white" x-text="request.title"></p>
                                    <p class="mt-1 max-w-xl text-sm text-gray-500 dark:text-gray-400" x-text="shortText(request.description)"></p>
                                </td>
                                <td class="px-5 py-4 text-sm text-gray-700 dark:text-gray-300" x-text="request.grade_number ? `Grade ${request.grade_number}` : 'All Grades'"></td>
                                <td class="px-5 py-4 text-sm text-gray-700 dark:text-gray-300" x-text="formatDate(request.created_at)"></td>
                                <td class="px-5 py-4 text-right">
                                    <span class="inline-flex rounded-full px-2.5 py-1 text-xs font-medium" :class="statusBadgeClass(request.status)" x-text="requestStatusLabel(request.status)"></span>
                                </td>
                            </tr>
                        </template>
                        <tr x-show="myRequests.length === 0">
                            <td colspan="4" class="px-5 py-12 text-center text-sm text-gray-500 dark:text-gray-400">No customized material requests yet.</td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div class="flex flex-col gap-3 border-t border-gray-200 px-5 py-4 dark:border-gray-800 sm:flex-row sm:items-center sm:justify-between">
                <p class="text-sm text-gray-500 dark:text-gray-400">
                    Showing <span x-text="requestStartItem"></span> to <span x-text="requestEndItem"></span> of <span x-text="requestTotal"></span> request(s)
                </p>
                <div class="flex flex-wrap gap-2">
                    <button type="button" @click="goToRequestPage(requestPage - 1)" :disabled="requestPage === 1" class="rounded-lg border border-gray-300 px-3 py-2 text-sm font-medium text-gray-700 disabled:cursor-not-allowed disabled:opacity-50 dark:border-gray-700 dark:text-gray-300">Previous</button>
                    <template x-for="pageNumber in visibleRequestPages()" :key="`request-page-${pageNumber}`">
                        <button type="button" @click="goToRequestPage(pageNumber)" class="rounded-lg border px-3 py-2 text-sm font-medium" :class="requestPage === pageNumber ? 'border-brand-500 bg-brand-500 text-gray-900' : 'border-gray-300 text-gray-700 dark:border-gray-700 dark:text-gray-300'" x-text="pageNumber"></button>
                    </template>
                    <button type="button" @click="goToRequestPage(requestPage + 1)" :disabled="requestPage === requestLastPage" class="rounded-lg border border-gray-300 px-3 py-2 text-sm font-medium text-gray-700 disabled:cursor-not-allowed disabled:opacity-50 dark:border-gray-700 dark:text-gray-300">Next</button>
                </div>
            </div>
        </div>
    </div>

    <div x-show="view === 'create'" class="w-full max-w-full overflow-hidden rounded-2xl border border-gray-200 bg-white dark:border-gray-800 dark:bg-white/[0.03]">
        <div class="flex flex-col gap-4 border-b border-gray-200 p-5 dark:border-gray-800 lg:flex-row lg:items-center lg:justify-between xl:p-6">
            <div>
                <button type="button" @click="backToBooks" class="mb-3 inline-flex items-center gap-2 text-sm font-medium text-gray-500 hover:text-gray-800 dark:text-gray-400 dark:hover:text-white">
                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none"><path d="M15 18L9 12L15 6" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" /></svg>
                    Back to Reading Materials
                </button>
                <h2 class="text-xl font-semibold text-gray-900 dark:text-white">Customize Reading Material</h2>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Step <span x-text="step"></span> of 2. The material will be sent to the principal for approval.</p>
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
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Use the same structure as the principal reading-material form. This evaluator submission will remain pending until approved.</p>
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
                    <button type="button" @click="backToBooks" class="rounded-lg border border-gray-300 px-4 py-2.5 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:text-gray-300 dark:hover:bg-white/5">Cancel</button>
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
                        <span x-show="!loading">Submit for Principal Approval</span>
                        <span x-show="loading">Submitting...</span>
                    </button>
                </div>
            </div>
        </form>
    </div>

    <div
        x-show="showReader"
        x-transition.opacity
        x-ref="readerOverlay"
        :class="isFullscreen ? 'evaluator-reader-fullscreen' : ''"
        class="fixed inset-0 z-99999 w-screen max-w-[100vw] overflow-y-auto overflow-x-hidden bg-gray-950/80 px-3 py-4 sm:px-5 sm:py-6"
    >
        <div class="evaluator-reader-shell mx-auto flex w-full max-w-6xl flex-col gap-3">
            <div class="flex flex-wrap items-center justify-end gap-2 rounded-2xl bg-white p-3 shadow-theme-xl dark:bg-gray-900 sm:p-4">
                <button type="button" @click="toggleFullscreen" class="rounded-lg border border-gray-300 px-3 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:text-gray-300 dark:hover:bg-white/5">
                    <span x-show="!isFullscreen">Full Screen</span>
                    <span x-show="isFullscreen">Exit Full Screen</span>
                </button>
                <button type="button" @click="closeReader" class="rounded-lg bg-brand-500 px-3 py-2 text-sm font-semibold text-gray-900 hover:bg-brand-400">Close</button>
            </div>

            <article class="evaluator-reader-content rounded-[28px] border border-white/70 p-6 shadow-theme-xl dark:border-white/10 sm:p-10 lg:p-14">
                <div class="mx-auto max-w-4xl">
                    <h1 class="text-center text-3xl font-bold leading-tight text-gray-900 dark:text-white sm:text-4xl lg:text-5xl" x-text="currentMaterial?.title"></h1>

                    <div class="evaluator-reader-text mx-auto mt-10 max-w-3xl space-y-6 text-lg leading-9 text-gray-800 dark:text-gray-100 sm:text-xl sm:leading-10">
                        <template x-for="(paragraph, index) in storyParagraphs()" :key="`paragraph-${index}`">
                            <p class="whitespace-pre-line" x-text="paragraph"></p>
                        </template>
                    </div>
                </div>
            </article>
        </div>
    </div>
</div>
