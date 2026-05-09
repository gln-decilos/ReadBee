@props([
    'schoolYears' => [],
    'selectedYearId' => null,
    'grades' => [],
    'page' => 1,
    'perPage' => 10,
])

<div
    x-data="principalPupilManagementPage(@js($schoolYears), @js($selectedYearId), @js($grades), {{ (int) $page }}, {{ (int) $perPage }})"
    x-init="init()"
    x-cloak
    @click="closeActionMenu"
    class="space-y-6"
>
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

    <div x-show="confirmModal" x-transition.opacity class="fixed inset-0 z-99999 flex items-center justify-center bg-gray-900/60 px-4">
        <div @click.outside="closeConfirm" class="w-full max-w-md rounded-2xl bg-white p-6 shadow-theme-xl dark:bg-gray-900">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white" x-text="confirmTitle"></h3>
            <p class="mt-2 text-sm text-gray-500 dark:text-gray-400" x-text="confirmMessage"></p>
            <div class="mt-6 flex justify-end gap-3">
                <button type="button" @click="closeConfirm" class="rounded-lg border border-gray-300 px-4 py-2.5 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:text-gray-300 dark:hover:bg-white/5">Cancel</button>
                <button type="button" @click="runConfirmAction" class="rounded-lg bg-red-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-red-700">Continue</button>
            </div>
        </div>
    </div>

    <div x-show="editModal" x-transition.opacity class="fixed inset-0 z-99999 flex items-center justify-center overflow-y-auto bg-gray-900/60 px-4 py-6">
        <div @click.outside="closeEdit" class="w-full max-w-3xl rounded-2xl bg-white p-6 shadow-theme-xl dark:bg-gray-900">
            <div class="flex items-start justify-between gap-4">
                <div>
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Edit Pupil</h3>
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Update pupil details or move the pupil to another section in this school year.</p>
                </div>
                <button type="button" @click="closeEdit" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-200">x</button>
            </div>

            <form @submit.prevent="updatePupil" class="mt-5 grid grid-cols-1 gap-4 md:grid-cols-2">
                <div>
                    <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">LRN</label>
                    <input type="text" x-model="editForm.lrn" class="h-11 w-full rounded-xl border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90" />
                    <template x-if="formErrors.lrn"><p class="mt-1 text-sm text-red-600" x-text="formErrors.lrn[0]"></p></template>
                </div>
                <div>
                    <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">Full Name</label>
                    <input type="text" x-model="editForm.full_name" class="h-11 w-full rounded-xl border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90" />
                    <template x-if="formErrors.full_name"><p class="mt-1 text-sm text-red-600" x-text="formErrors.full_name[0]"></p></template>
                </div>
                <div>
                    <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">Section</label>
                    <select x-model="editForm.section_id" @change="syncEditGrade" class="h-11 w-full rounded-xl border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
                        <template x-for="section in selectedGradeSections" :key="section.section_id">
                            <option :value="section.section_id" x-text="section.section_name"></option>
                        </template>
                    </select>
                    <template x-if="formErrors.section_id"><p class="mt-1 text-sm text-red-600" x-text="formErrors.section_id[0]"></p></template>
                </div>
                <div>
                    <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">Sex</label>
                    <select x-model="editForm.sex" class="h-11 w-full rounded-xl border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
                        <option value="">Select sex</option>
                        <option value="M">Male</option>
                        <option value="F">Female</option>
                    </select>
                </div>
                <div>
                    <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">Age</label>
                    <input type="number" min="0" x-model="editForm.age" class="h-11 w-full rounded-xl border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90" />
                </div>
                <div>
                    <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">Guardian Contact</label>
                    <input type="text" x-model="editForm.guardian_contact" class="h-11 w-full rounded-xl border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90" />
                </div>
                <div class="md:col-span-2">
                    <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">Guardian Name</label>
                    <input type="text" x-model="editForm.guardian_name" class="h-11 w-full rounded-xl border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90" />
                </div>
                <div class="flex justify-end gap-3 border-t border-gray-200 pt-4 dark:border-gray-800 md:col-span-2">
                    <button type="button" @click="closeEdit" class="rounded-lg border border-gray-300 px-4 py-2.5 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:text-gray-300 dark:hover:bg-white/5">Cancel</button>
                    <button type="submit" :disabled="saving" class="rounded-lg bg-brand-500 px-5 py-2.5 text-sm font-semibold text-gray-900 hover:bg-brand-400 disabled:opacity-60">
                        <span x-show="!saving">Save Changes</span>
                        <span x-show="saving">Saving...</span>
                    </button>
                </div>
            </form>
        </div>
    </div>



    <div class="overflow-hidden rounded-3xl border border-gray-200 bg-white shadow-sm dark:border-white/[0.05] dark:bg-white/[0.03]">
        <div class="flex flex-col gap-5 px-6 py-6 lg:flex-row lg:items-end lg:justify-between">
            <div>
                <div class="inline-flex items-center rounded-full bg-yellow-100 px-3 py-1 text-xs font-medium text-yellow-700 dark:bg-yellow-500/10 dark:text-yellow-400">
                    Section Based Pupil Management
                </div>
                <h3 class="mt-3 text-xl font-semibold text-gray-800 dark:text-white/90">Pupil Management</h3>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                    Add pupils to sections, move pupils when sections change, and mark dropped pupils without deleting assessment history.
                </p>
            </div>

            <div class="w-full lg:w-auto">
                <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">School Year</label>
                <div class="relative min-w-[260px]">
                    <select
                        :value="yearPickerValue"
                        @change="changeYear($event.target.value)"
                        :disabled="loadingYear"
                        class="dark:bg-gray-900 shadow-theme-xs h-11 w-full appearance-none rounded-xl border border-gray-300 bg-transparent px-4 py-2.5 pr-10 text-sm text-gray-800 disabled:cursor-not-allowed disabled:opacity-70 dark:border-gray-700 dark:text-white/90"
                    >
                        <template x-for="year in schoolYears" :key="year.year_id">
                            <option :value="year.year_id" x-text="yearLabel(year)"></option>
                        </template>
                    </select>
                    <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center pr-3">
                        <svg x-show="loadingYear" class="h-4 w-4 animate-spin text-gray-500 dark:text-gray-400" viewBox="0 0 24 24" fill="none">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"></path>
                        </svg>
                        <svg x-show="!loadingYear" class="h-4 w-4 text-gray-500 dark:text-gray-400" fill="none" viewBox="0 0 20 20" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M6 8l4 4 4-4" />
                        </svg>
                    </div>
                </div>
            </div>
        </div>

        <template x-if="!viewingGradeId">
            <div class="px-6 pb-6">
                <div class="grid grid-cols-1 gap-5 md:grid-cols-2 xl:grid-cols-3">
                    <template x-for="grade in grades" :key="grade.grade_level_id">
                        <div class="group relative overflow-hidden rounded-3xl border border-gray-200 bg-gradient-to-br from-white to-gray-50 p-5 shadow-sm transition hover:-translate-y-1 hover:shadow-md dark:border-white/[0.05] dark:from-gray-900 dark:to-black">
                            <div class="absolute right-0 top-0 h-24 w-24 rounded-full bg-yellow-200/40 blur-2xl dark:bg-yellow-500/10"></div>
                            <div class="relative">
                                <div class="flex items-start justify-between gap-4">
                                    <div>
                                        <div class="flex h-12 w-12 items-center justify-center rounded-2xl bg-yellow-100 text-yellow-700 dark:bg-yellow-500/10 dark:text-yellow-400">
                                            <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M12 14l9-5-9-5-9 5 9 5z"/>
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M12 14l6.16-3.422A12.083 12.083 0 0112 21a12.083 12.083 0 01-6.16-10.422L12 14z"/>
                                            </svg>
                                        </div>
                                        <h4 class="mt-4 text-lg font-semibold text-gray-800 dark:text-white/90">Grade <span x-text="grade.grade_number"></span></h4>
                                        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">School Year <span x-text="selectedYearLabel"></span></p>
                                    </div>
                                    <button type="button" @click="openGrade(grade.grade_level_id)" class="inline-flex h-10 w-10 items-center justify-center rounded-xl border border-gray-300 bg-white text-gray-700 shadow-sm hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-800 dark:text-white dark:hover:bg-gray-700" title="View sections and pupils">
                                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                        </svg>
                                    </button>
                                </div>
                                <div class="mt-5 grid grid-cols-3 gap-3">
                                    <div class="rounded-2xl border border-gray-200 bg-white p-4 dark:border-white/[0.05] dark:bg-white/[0.03]">
                                        <p class="text-xs font-medium uppercase tracking-wide text-gray-500 dark:text-gray-400">Sections</p>
                                        <p class="mt-2 text-2xl font-semibold text-gray-800 dark:text-white" x-text="grade.section_count || 0"></p>
                                    </div>
                                    <div class="rounded-2xl border border-gray-200 bg-white p-4 dark:border-white/[0.05] dark:bg-white/[0.03]">
                                        <p class="text-xs font-medium uppercase tracking-wide text-gray-500 dark:text-gray-400">Enrolled</p>
                                        <p class="mt-2 text-2xl font-semibold text-gray-800 dark:text-white" x-text="grade.enrolled_pupils_count || 0"></p>
                                    </div>
                                    <div class="rounded-2xl border border-gray-200 bg-white p-4 dark:border-white/[0.05] dark:bg-white/[0.03]">
                                        <p class="text-xs font-medium uppercase tracking-wide text-gray-500 dark:text-gray-400">Dropped</p>
                                        <p class="mt-2 text-2xl font-semibold text-gray-800 dark:text-white" x-text="grade.dropped_pupils_count || 0"></p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </template>
                </div>
            </div>
        </template>

        <template x-if="viewingGradeId && selectedGrade">
            <div class="px-6 pb-6">
                <div class="mb-6 flex flex-col gap-4 rounded-3xl border border-gray-200 bg-gradient-to-r from-white to-gray-50 p-5 shadow-sm dark:border-white/[0.05] dark:from-gray-900 dark:to-black lg:flex-row lg:items-center lg:justify-between">
                    <div>
                        <div class="inline-flex items-center rounded-full bg-yellow-100 px-3 py-1 text-xs font-medium text-yellow-700 dark:bg-yellow-500/10 dark:text-yellow-400">Grade Detail View</div>
                        <h3 class="mt-3 text-xl font-semibold text-gray-800 dark:text-white/90">Grade <span x-text="selectedGrade.grade_number"></span> Sections and Pupils</h3>
                        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">School Year <span x-text="selectedYearLabel"></span></p>
                    </div>
                    <button type="button" @click="backToGrades" class="inline-flex items-center justify-center rounded-xl border border-gray-300 px-4 py-2.5 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:text-white dark:hover:bg-gray-800">Back to Grades</button>
                </div>

                <div class="grid grid-cols-1 items-start gap-5 transition-all duration-300 xl:grid-cols-[var(--sections-width)_minmax(0,1fr)]" :style="sectionsCollapsed ? '--sections-width: 76px' : '--sections-width: 320px'">
                    <aside class="overflow-hidden rounded-3xl border border-gray-200 bg-white dark:border-white/[0.05] dark:bg-white/[0.03]">
                        <div class="flex items-center justify-between gap-3 border-b border-gray-200 px-4 py-4 dark:border-white/[0.05]">
                            <div x-show="!sectionsCollapsed" x-transition.opacity>
                                <h4 class="text-base font-semibold text-gray-800 dark:text-white">Sections</h4>
                                <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Scrollable list for this grade</p>
                            </div>
                            <button type="button" @click="sectionsCollapsed = !sectionsCollapsed" class="inline-flex h-10 w-10 shrink-0 items-center justify-center rounded-xl border border-gray-300 text-gray-600 hover:bg-gray-50 dark:border-gray-700 dark:text-gray-300 dark:hover:bg-white/5" :title="sectionsCollapsed ? 'Expand sections' : 'Collapse sections'">
                                <svg class="h-5 w-5 transition-transform" :class="sectionsCollapsed ? 'rotate-180' : ''" viewBox="0 0 24 24" fill="none">
                                    <path d="M15 18L9 12L15 6" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" />
                                </svg>
                            </button>
                        </div>

                        <div x-show="sectionsCollapsed" x-transition.opacity class="max-h-[620px] overflow-y-auto p-3">
                            <div class="space-y-2">
                                <template x-for="section in selectedGrade.sections" :key="`rail-${section.section_id}`">
                                    <button type="button" @click="openSection(section.section_id)" class="flex h-12 w-full items-center justify-center rounded-xl border text-xs font-semibold transition" :class="String(viewingSectionId) === String(section.section_id) ? 'border-brand-400 bg-brand-50 text-brand-700 dark:border-brand-500/40 dark:bg-brand-500/10 dark:text-brand-300' : 'border-gray-200 bg-white text-gray-700 hover:bg-gray-50 dark:border-white/[0.05] dark:bg-white/[0.03] dark:text-gray-300 dark:hover:bg-white/[0.06]'" :title="section.section_name">
                                        <span x-text="sectionInitial(section.section_name)"></span>
                                    </button>
                                </template>
                            </div>
                        </div>

                        <div x-show="!sectionsCollapsed" x-transition class="max-h-[620px] space-y-3 overflow-y-auto p-4">
                            <template x-for="section in selectedGrade.sections" :key="section.section_id">
                                <button type="button" @click="openSection(section.section_id)" class="w-full rounded-2xl border p-4 text-left transition" :class="String(viewingSectionId) === String(section.section_id) ? 'border-brand-400 bg-brand-50 dark:border-brand-500/40 dark:bg-brand-500/10' : 'border-gray-200 bg-white hover:bg-gray-50 dark:border-white/[0.05] dark:bg-white/[0.03] dark:hover:bg-white/[0.06]'">
                                    <div class="flex items-start justify-between gap-3">
                                        <div class="min-w-0">
                                            <p class="truncate font-semibold text-gray-800 dark:text-white" x-text="section.section_name"></p>
                                            <p class="mt-1 truncate text-xs text-gray-500 dark:text-gray-400" x-text="section.adviser_name ? `Adviser: ${section.adviser_name}` : 'No adviser set'"></p>
                                        </div>
                                        <span class="shrink-0 rounded-full bg-gray-100 px-2.5 py-1 text-xs font-medium text-gray-700 dark:bg-white/10 dark:text-gray-300" x-text="`${section.pupil_count || 0}`"></span>
                                    </div>
                                    <div class="mt-3 grid grid-cols-2 gap-2 text-xs text-gray-500 dark:text-gray-400">
                                        <span>Enrolled: <strong x-text="section.enrolled_count || 0"></strong></span>
                                        <span>Dropped: <strong x-text="section.dropped_count || 0"></strong></span>
                                    </div>
                                </button>
                            </template>

                            <div x-show="selectedGrade.sections.length === 0" class="rounded-2xl border border-dashed border-gray-300 p-6 text-center dark:border-gray-700">
                                <p class="text-sm font-medium text-gray-800 dark:text-white">No sections for this grade.</p>
                                <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Create sections first in Class Management.</p>
                            </div>
                        </div>
                    </aside>

                    <main class="min-w-0 rounded-3xl border border-gray-200 bg-white dark:border-white/[0.05] dark:bg-white/[0.03]">
                        <template x-if="selectedSection">
                            <div>
                                <div class="flex flex-col gap-4 border-b border-gray-200 p-5 dark:border-white/[0.05] lg:flex-row lg:items-center lg:justify-between">
                                    <div>
                                        <h4 class="text-lg font-semibold text-gray-800 dark:text-white">Section <span x-text="selectedSection.section_name"></span></h4>
                                        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Manage pupils currently assigned to this section.</p>
                                    </div>

                                    <div class="flex flex-wrap gap-2">
                                        <button x-show="!importMode && !hasSelection" type="button" @click="toggleAddForm" class="inline-flex items-center justify-center rounded-xl bg-brand-500 px-4 py-2.5 text-sm font-semibold text-gray-900 hover:bg-brand-400">
                                            <span x-text="showAddForm ? 'Close Form' : 'Add Pupil'"></span>
                                        </button>

                                        <button x-show="!importMode && !hasSelection" type="button" @click="openImportModal" class="inline-flex items-center justify-center rounded-xl border border-gray-300 px-4 py-2.5 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:text-gray-300 dark:hover:bg-white/5">
                                            Import CSV
                                        </button>

                                        <button x-show="!importMode && hasSelection" type="button" @click="confirmBulkDelete" class="inline-flex items-center justify-center rounded-xl bg-red-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-red-700">
                                            Delete Selected (<span x-text="selectedPupilIds.length"></span>)
                                        </button>

                                        <button x-show="importMode" type="button" @click="closeImportModal" class="inline-flex items-center justify-center rounded-xl border border-gray-300 px-4 py-2.5 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:text-gray-300 dark:hover:bg-white/5">
                                            Back to Pupils
                                        </button>
                                    </div>
                                </div>


                                <div x-show="importMode" x-transition class="border-b border-gray-200 p-5 dark:border-white/[0.05]">
                                    <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                                        <div>
                                            <h5 class="text-base font-semibold text-gray-800 dark:text-white">Import Pupils from CSV</h5>
                                            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Upload a CSV file for this section. Valid rows will be saved as enrolled pupils.</p>
                                        </div>
                                        <a href="{{ route('principal.pupils.import.template') }}" class="inline-flex items-center justify-center rounded-xl border border-gray-300 px-4 py-2.5 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:text-gray-300 dark:hover:bg-white/5">
                                            Download Template
                                        </a>
                                    </div>

                                    <div class="mt-5 rounded-2xl border border-blue-200 bg-blue-50 p-4 text-sm text-blue-700 dark:border-blue-500/20 dark:bg-blue-500/10 dark:text-blue-300">
                                        CSV columns required:
                                        <span class="font-semibold">lrn</span>,
                                        <span class="font-semibold">full_name</span>,
                                        <span class="font-semibold">sex</span>,
                                        <span class="font-semibold">age</span>,
                                        <span class="font-semibold">guardian_name</span>,
                                        <span class="font-semibold">guardian_contact</span>.
                                        Sex must be <span class="font-semibold">M</span> or <span class="font-semibold">F</span>.
                                    </div>

                                    <form x-ref="importForm" x-show="importStage === 'upload'" @submit.prevent="previewImport" enctype="multipart/form-data" class="mt-5">
                                        <div class="rounded-2xl border-2 border-dashed border-gray-300 bg-gray-50 p-6 text-center dark:border-gray-700 dark:bg-gray-900">
                                            <label for="pupil_csv_file" class="block cursor-pointer">
                                                <div class="mx-auto mb-4 flex h-16 w-16 items-center justify-center rounded-2xl bg-brand-50 dark:bg-brand-500/10">
                                                    <svg class="h-8 w-8 text-brand-500" fill="none" viewBox="0 0 24 24">
                                                        <path d="M12 16V4M12 4L8 8M12 4L16 8M4 15V18C4 19.1046 4.89543 20 6 20H18C19.1046 20 20 19.1046 20 18V15" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" />
                                                    </svg>
                                                </div>
                                                <h4 class="text-base font-semibold text-gray-800 dark:text-white">Click to upload CSV file</h4>
                                                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Only CSV files are supported. Maximum file size is 10 MB.</p>
                                            </label>

                                            <input id="pupil_csv_file" name="csv_file" type="file" accept=".csv,text/csv" class="mt-4 block w-full rounded-lg border border-gray-300 bg-white px-4 py-2.5 text-sm text-gray-800 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90" required />

                                            <template x-if="importErrors.csv_file">
                                                <p class="mt-2 text-sm text-red-600" x-text="importErrors.csv_file[0]"></p>
                                            </template>
                                        </div>

                                        <div class="mt-5 flex justify-end gap-3">
                                            <button type="button" @click="closeImportModal" class="rounded-lg border border-gray-300 px-4 py-2.5 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:text-gray-300 dark:hover:bg-white/5">Cancel</button>
                                            <button type="submit" :disabled="importLoading" class="rounded-lg bg-brand-500 px-5 py-2.5 text-sm font-semibold text-gray-900 hover:bg-brand-400 disabled:cursor-not-allowed disabled:opacity-60">
                                                Import and Validate
                                            </button>
                                        </div>
                                    </form>

                                    <div x-show="importStage === 'validating'" x-transition class="mt-5 rounded-2xl border border-gray-200 bg-gray-50 p-8 text-center dark:border-gray-800 dark:bg-gray-900">
                                        <div class="mx-auto mb-4 h-10 w-10 animate-spin rounded-full border-4 border-brand-200 border-t-brand-500"></div>
                                        <h4 class="text-base font-semibold text-gray-800 dark:text-white">Validating CSV file</h4>
                                        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Please wait while the uploaded file is checked.</p>
                                    </div>

                                    <div x-show="importStage === 'preview' && importRows.length > 0" x-transition class="mt-6">
                                        <div class="mb-4 flex flex-wrap items-center gap-2">
                                          <div class="inline-flex items-center gap-2 rounded-xl border border-gray-200 bg-white px-3 py-2 dark:border-gray-800 dark:bg-white/[0.03]">
                                              <span class="text-xs font-medium uppercase tracking-wide text-gray-500 dark:text-gray-400">Total</span>
                                              <span class="text-sm font-semibold text-gray-900 dark:text-white" x-text="importSummary.total || 0"></span>
                                          </div>

                                          <div class="inline-flex items-center gap-2 rounded-xl border border-green-200 bg-green-50 px-3 py-2 dark:border-green-500/20 dark:bg-green-500/10">
                                              <span class="text-xs font-medium uppercase tracking-wide text-green-700 dark:text-green-400">Valid</span>
                                              <span class="text-sm font-semibold text-green-700 dark:text-green-400" x-text="importSummary.valid || 0"></span>
                                          </div>

                                          <div class="inline-flex items-center gap-2 rounded-xl border border-red-200 bg-red-50 px-3 py-2 dark:border-red-500/20 dark:bg-red-500/10">
                                              <span class="text-xs font-medium uppercase tracking-wide text-red-700 dark:text-red-400">Invalid</span>
                                              <span class="text-sm font-semibold text-red-700 dark:text-red-400" x-text="importSummary.invalid || 0"></span>
                                          </div>
                                      </div>

                                        <div class="max-h-[360px] overflow-y-auto rounded-2xl border border-gray-200 dark:border-gray-800">
                                            <table class="w-full divide-y divide-gray-200 text-sm dark:divide-gray-800">
                                                <thead class="sticky top-0 bg-gray-50 dark:bg-gray-900">
                                                    <tr>
                                                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">Row</th>
                                                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">LRN</th>
                                                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">Full Name</th>
                                                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">Sex</th>
                                                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">Age</th>
                                                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">Status</th>
                                                    </tr>
                                                </thead>
                                                <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                                                    <template x-for="row in importRows" :key="row.row_number">
                                                        <tr>
                                                            <td class="px-4 py-3 text-gray-700 dark:text-gray-300" x-text="row.row_number"></td>
                                                            <td class="px-4 py-3 text-gray-700 dark:text-gray-300" x-text="row.lrn || 'N/A'"></td>
                                                            <td class="px-4 py-3 text-gray-700 dark:text-gray-300" x-text="row.full_name || 'N/A'"></td>
                                                            <td class="px-4 py-3 text-gray-700 dark:text-gray-300" x-text="row.sex || 'N/A'"></td>
                                                            <td class="px-4 py-3 text-gray-700 dark:text-gray-300" x-text="row.age || 'N/A'"></td>
                                                            <td class="px-4 py-3">
                                                                <span class="rounded-full px-2.5 py-1 text-xs font-medium" :class="row.valid ? 'bg-green-100 text-green-700 dark:bg-green-500/10 dark:text-green-400' : 'bg-red-100 text-red-700 dark:bg-red-500/10 dark:text-red-400'" x-text="row.valid ? 'Valid' : row.errors.join(' ')"></span>
                                                            </td>
                                                        </tr>
                                                    </template>
                                                </tbody>
                                            </table>
                                        </div>

                                        <div class="mt-5 flex flex-col-reverse gap-3 sm:flex-row sm:justify-end">
                                            <button type="button" @click="resetImportPreview" class="rounded-lg border border-gray-300 px-4 py-2.5 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:text-gray-300 dark:hover:bg-white/5">Upload Another CSV</button>
                                            <button type="button" @click="commitImport" :disabled="importCommitting || !importSummary.valid" class="rounded-lg bg-brand-500 px-5 py-2.5 text-sm font-semibold text-gray-900 hover:bg-brand-400 disabled:cursor-not-allowed disabled:opacity-60">
                                                <span x-show="!importCommitting">Import Valid Pupils</span>
                                                <span x-show="importCommitting">Importing...</span>
                                            </button>
                                        </div>
                                    </div>
                                </div>

                                <div x-show="showAddForm && !hasSelection && !importMode" x-transition class="border-b border-gray-200 p-5 dark:border-white/[0.05]">
                                    <h5 class="text-base font-semibold text-gray-800 dark:text-white">Add Pupil</h5>
                                    <form @submit.prevent="addPupil" class="mt-5 grid grid-cols-1 gap-4 lg:grid-cols-2">
                                        <div>
                                            <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">LRN</label>
                                            <input type="text" x-model="pupilForm.lrn" class="h-11 w-full rounded-xl border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90" />
                                            <template x-if="formErrors.lrn"><p class="mt-1 text-sm text-red-600" x-text="formErrors.lrn[0]"></p></template>
                                        </div>
                                        <div>
                                            <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">Full Name</label>
                                            <input type="text" x-model="pupilForm.full_name" class="h-11 w-full rounded-xl border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90" />
                                            <template x-if="formErrors.full_name"><p class="mt-1 text-sm text-red-600" x-text="formErrors.full_name[0]"></p></template>
                                        </div>
                                        <div>
                                            <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">Sex</label>
                                            <select x-model="pupilForm.sex" class="h-11 w-full rounded-xl border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
                                                <option value="">Select sex</option>
                                                <option value="M">Male</option>
                                                <option value="F">Female</option>
                                            </select>
                                        </div>
                                        <div>
                                            <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">Age</label>
                                            <input type="number" min="0" x-model="pupilForm.age" class="h-11 w-full rounded-xl border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90" />
                                        </div>
                                        <div>
                                            <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">Guardian Name</label>
                                            <input type="text" x-model="pupilForm.guardian_name" class="h-11 w-full rounded-xl border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90" />
                                        </div>
                                        <div>
                                            <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">Guardian Contact</label>
                                            <input type="text" x-model="pupilForm.guardian_contact" class="h-11 w-full rounded-xl border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90" />
                                        </div>
                                        <div class="flex justify-end gap-3 border-t border-gray-200 pt-4 dark:border-gray-800 lg:col-span-2">
                                            <button type="button" @click="toggleAddForm" class="rounded-lg border border-gray-300 px-4 py-2.5 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:text-gray-300 dark:hover:bg-white/5">Cancel</button>
                                            <button type="submit" :disabled="saving" class="rounded-lg bg-brand-500 px-5 py-2.5 text-sm font-semibold text-gray-900 hover:bg-brand-400 disabled:opacity-60">
                                                <span x-show="!saving">Save Pupil</span>
                                                <span x-show="saving">Saving...</span>
                                            </button>
                                        </div>
                                    </form>
                                </div>

                                <div x-show="!importMode && !showAddForm" class="p-5">
                                    <div class="mb-4 flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
                                        <div class="max-w-sm">
                                            <input type="search" x-model="search" @input="tablePage = 1; clearSelection()" placeholder="Search pupils in this section" class="h-11 w-full rounded-xl border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90" />
                                        </div>
                                        <p class="text-sm text-gray-500 dark:text-gray-400">
                                            Showing <span x-text="tableStartItem"></span> to <span x-text="tableEndItem"></span> of <span x-text="filteredSectionPupils.length"></span>
                                        </p>
                                    </div>

                                    <div class="overflow-visible rounded-2xl border border-gray-200 dark:border-white/[0.05]">
                                        <table class="w-full table-fixed divide-y divide-gray-200 dark:divide-white/[0.05]">
                                            <colgroup>
                                                <col class="w-[48px]">
                                                <col class="w-[24%]">
                                                <col class="w-[34%]">
                                                <col class="w-[12%]">
                                                <col class="w-[16%]">
                                                <col class="w-[14%]">
                                            </colgroup>
                                            <thead class="bg-gray-50 dark:bg-gray-900/60">
                                                <tr>
                                                    <th class="px-4 py-3 text-left">
                                                        <input type="checkbox" :checked="allVisibleSelected" @change="toggleSelectAllVisible" class="h-4 w-4 rounded border-gray-300 text-brand-500 focus:ring-brand-500" />
                                                    </th>
                                                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">LRN</th>
                                                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">Pupil</th>
                                                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">Sex/Age</th>
                                                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">Status</th>
                                                    <th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">Action</th>
                                                </tr>
                                            </thead>
                                            <tbody class="divide-y divide-gray-100 dark:divide-white/[0.05]">
                                                <template x-for="pupil in paginatedSectionPupils" :key="pupil.pupil_id">
                                                    <tr class="hover:bg-gray-50 dark:hover:bg-white/[0.03]">
                                                        <td class="px-4 py-4">
                                                            <input type="checkbox" :checked="isSelected(pupil.pupil_id)" @change="togglePupilSelection(pupil.pupil_id)" class="h-4 w-4 rounded border-gray-300 text-brand-500 focus:ring-brand-500" />
                                                        </td>
                                                        <td class="px-4 py-4 text-sm text-gray-700 dark:text-gray-300" x-text="pupil.lrn"></td>
                                                        <td class="px-4 py-4">
                                                            <p class="truncate font-medium text-gray-900 dark:text-white" x-text="pupil.full_name"></p>
                                                            <p class="mt-1 truncate text-xs text-gray-500 dark:text-gray-400" x-text="pupil.guardian_name ? `Guardian: ${pupil.guardian_name}` : 'No guardian set'"></p>
                                                        </td>
                                                        <td class="px-4 py-4 text-sm text-gray-700 dark:text-gray-300">
                                                            <span x-text="pupil.sex || 'N/A'"></span>
                                                            <span x-show="pupil.age"> / </span>
                                                            <span x-show="pupil.age" x-text="pupil.age"></span>
                                                        </td>
                                                        <td class="px-4 py-4">
                                                            <span class="rounded-full px-2.5 py-1 text-xs font-medium" :class="pupilBadgeClass(pupil.status)" x-text="pupil.status_label || statusLabel(pupil.status)"></span>
                                                        </td>
                                                        <td class="px-4 py-4 text-right">
                                                            <div class="relative inline-flex" @click.stop>
                                                                <button type="button" @click.stop="toggleActionMenu(pupil.pupil_id)" class="inline-flex h-9 w-9 items-center justify-center rounded-lg border border-gray-300 text-gray-600 hover:bg-gray-50 dark:border-gray-700 dark:text-gray-300 dark:hover:bg-white/5" title="Actions">
                                                                    <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none">
                                                                        <path d="M12 13C12.5523 13 13 12.5523 13 12C13 11.4477 12.5523 11 12 11C11.4477 11 11 11.4477 11 12C11 12.5523 11.4477 13 12 13Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                                                                        <path d="M19 13C19.5523 13 20 12.5523 20 12C20 11.4477 19.5523 11 19 11C18.4477 11 18 11.4477 18 12C18 12.5523 18.4477 13 19 13Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                                                                        <path d="M5 13C5.55228 13 6 12.5523 6 12C6 11.4477 5.55228 11 5 11C4.44772 11 4 11.4477 4 12C4 12.5523 4.44772 13 5 13Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                                                                    </svg>
                                                                </button>

                                                                <div x-show="isActionMenuOpen(pupil.pupil_id)" x-transition @click.stop class="absolute right-0 top-10 z-30 w-44 overflow-hidden rounded-xl border border-gray-200 bg-white py-1 text-left shadow-lg dark:border-gray-700 dark:bg-gray-900">
                                                                    <button type="button" @click="openEdit(pupil); closeActionMenu()" class="block w-full px-4 py-2 text-left text-sm text-gray-700 hover:bg-gray-50 dark:text-gray-300 dark:hover:bg-white/5">Edit</button>
                                                                    <button type="button" x-show="pupil.status === 'enrolled'" @click="confirmDrop(pupil); closeActionMenu()" class="block w-full px-4 py-2 text-left text-sm text-yellow-700 hover:bg-yellow-50 dark:text-yellow-400 dark:hover:bg-yellow-500/10">Drop</button>
                                                                    <button type="button" x-show="pupil.status === 'inactive'" @click="restorePupil(pupil); closeActionMenu()" class="block w-full px-4 py-2 text-left text-sm text-green-700 hover:bg-green-50 dark:text-green-400 dark:hover:bg-green-500/10">Restore</button>
                                                                    <button type="button" @click="confirmArchive(pupil); closeActionMenu()" class="block w-full px-4 py-2 text-left text-sm text-gray-700 hover:bg-gray-50 dark:text-gray-300 dark:hover:bg-white/5">Archive</button>
                                                                    <button type="button" @click="confirmDelete(pupil); closeActionMenu()" class="block w-full px-4 py-2 text-left text-sm text-red-600 hover:bg-red-50 dark:hover:bg-red-500/10">Delete</button>
                                                                </div>
                                                            </div>
                                                        </td>
                                                    </tr>
                                                </template>
                                                <tr x-show="filteredSectionPupils.length === 0">
                                                    <td colspan="6" class="px-5 py-12 text-center">
                                                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white">No pupils found</h3>
                                                        <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">Add a pupil or adjust your search.</p>
                                                    </td>
                                                </tr>
                                            </tbody>
                                        </table>
                                    </div>

                                    <div class="mt-4 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                                        <p class="text-sm text-gray-500 dark:text-gray-400">
                                            Page <span x-text="tablePage"></span> of <span x-text="tableLastPage"></span>
                                        </p>
                                        <div class="flex flex-wrap gap-2">
                                            <button type="button" @click="goToTablePage(tablePage - 1)" :disabled="tablePage === 1" class="rounded-lg border border-gray-300 px-3 py-2 text-sm font-medium text-gray-700 disabled:cursor-not-allowed disabled:opacity-50 dark:border-gray-700 dark:text-gray-300">Previous</button>
                                            <template x-for="pageNumber in visibleTablePages()" :key="pageNumber">
                                                <button type="button" @click="goToTablePage(pageNumber)" class="rounded-lg border px-3 py-2 text-sm font-medium" :class="tablePage === pageNumber ? 'border-brand-500 bg-brand-500 text-gray-900' : 'border-gray-300 text-gray-700 dark:border-gray-700 dark:text-gray-300'" x-text="pageNumber"></button>
                                            </template>
                                            <button type="button" @click="goToTablePage(tablePage + 1)" :disabled="tablePage === tableLastPage" class="rounded-lg border border-gray-300 px-3 py-2 text-sm font-medium text-gray-700 disabled:cursor-not-allowed disabled:opacity-50 dark:border-gray-700 dark:text-gray-300">Next</button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </template>

                        <div x-show="!selectedSection" class="p-8 text-center">
                            <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Select a section</h3>
                            <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">Choose a section on the left to view and manage its pupils.</p>
                        </div>
                    </main>
                </div>
            </div>
        </template>
    </div>
</div>

<script>
    function principalPupilManagementPage(schoolYears, selectedYearId, grades, page, perPage) {
        return {
            schoolYears: schoolYears || [],
            selectedYearId: selectedYearId,
            yearPickerValue: selectedYearId,
            grades: grades || [],
            page: page || 1,
            perPage: 10,
            tablePage: 1,
            viewingGradeId: null,
            viewingSectionId: null,
            sectionsCollapsed: false,
            showAddForm: false,
            selectedPupilIds: [],
            openActionMenuId: null,
            search: '',
            formErrors: {},
            loadingYear: false,
            saving: false,
            importMode: false,
            importStage: 'upload',
            importLoading: false,
            importCommitting: false,
            importRows: [],
            importSummary: { total: 0, valid: 0, invalid: 0 },
            importErrors: {},
            successModal: false,
            successMessage: '',
            confirmModal: false,
            confirmTitle: '',
            confirmMessage: '',
            confirmAction: null,
            editModal: false,
            editTarget: null,
            pupilForm: {},
            editForm: {},

            init() {
                this.pupilForm = this.emptyPupilForm();
            },

            emptyPupilForm() {
                return {
                    lrn: '',
                    full_name: '',
                    sex: '',
                    age: '',
                    guardian_name: '',
                    guardian_contact: '',
                };
            },

            yearLabel(year) {
                if (!year) return 'Select School Year';
                const start = year.start_date ? new Date(year.start_date).getFullYear() : '';
                const end = year.end_date ? new Date(year.end_date).getFullYear() : '';
                return start && end ? `${start} - ${end}` : 'School Year';
            },

            get selectedYearLabel() {
                const year = this.schoolYears.find(item => String(item.year_id) === String(this.selectedYearId));
                return this.yearLabel(year);
            },

            get selectedGrade() {
                return this.grades.find(grade => String(grade.grade_level_id) === String(this.viewingGradeId)) || null;
            },

            get selectedGradeSections() {
                return this.selectedGrade ? (this.selectedGrade.sections || []) : [];
            },

            get selectedSection() {
                if (!this.selectedGrade) return null;
                return (this.selectedGrade.sections || []).find(section => String(section.section_id) === String(this.viewingSectionId)) || null;
            },

            get filteredSectionPupils() {
                const pupils = this.selectedSection ? (this.selectedSection.pupils || []) : [];
                const term = this.search.trim().toLowerCase();

                if (!term) return pupils;

                return pupils.filter(pupil => {
                    const haystack = [
                        pupil.lrn,
                        pupil.full_name,
                        pupil.sex,
                        pupil.age,
                        pupil.guardian_name,
                        pupil.guardian_contact,
                        pupil.status,
                        pupil.status_label || this.statusLabel(pupil.status),
                    ].join(' ').toLowerCase();

                    return haystack.includes(term);
                });
            },

            get tableLastPage() {
                return Math.max(Math.ceil(this.filteredSectionPupils.length / this.perPage), 1);
            },

            get paginatedSectionPupils() {
                if (this.tablePage > this.tableLastPage) this.tablePage = this.tableLastPage;
                const start = (this.tablePage - 1) * this.perPage;
                return this.filteredSectionPupils.slice(start, start + this.perPage);
            },

            get tableStartItem() {
                if (this.filteredSectionPupils.length === 0) return 0;
                return (this.tablePage - 1) * this.perPage + 1;
            },

            get tableEndItem() {
                return Math.min(this.tablePage * this.perPage, this.filteredSectionPupils.length);
            },

            get hasSelection() {
                return this.selectedPupilIds.length > 0;
            },

            get allVisibleSelected() {
                if (!this.paginatedSectionPupils.length) return false;
                return this.paginatedSectionPupils.every(pupil => this.isSelected(pupil.pupil_id));
            },

            sectionInitial(sectionName) {
                return String(sectionName || '?').trim().charAt(0).toUpperCase() || '?';
            },

            toggleActionMenu(pupilId) {
                this.openActionMenuId = String(this.openActionMenuId) === String(pupilId) ? null : pupilId;
            },

            closeActionMenu() {
                this.openActionMenuId = null;
            },

            isActionMenuOpen(pupilId) {
                return String(this.openActionMenuId) === String(pupilId);
            },

            visibleTablePages() {
                const pages = [];
                const start = Math.max(1, this.tablePage - 2);
                const end = Math.min(this.tableLastPage, this.tablePage + 2);

                for (let i = start; i <= end; i++) pages.push(i);
                return pages;
            },

            goToTablePage(pageNumber) {
                if (pageNumber >= 1 && pageNumber <= this.tableLastPage) {
                    this.tablePage = pageNumber;
                    this.clearSelection();
                    this.closeActionMenu();
                }
            },

            isSelected(pupilId) {
                return this.selectedPupilIds.map(String).includes(String(pupilId));
            },

            togglePupilSelection(pupilId) {
                if (this.isSelected(pupilId)) {
                    this.selectedPupilIds = this.selectedPupilIds.filter(id => String(id) !== String(pupilId));
                } else {
                    this.selectedPupilIds.push(pupilId);
                    this.showAddForm = false;
                }
                this.closeActionMenu();
            },

            toggleSelectAllVisible() {
                if (this.allVisibleSelected) {
                    const visibleIds = this.paginatedSectionPupils.map(pupil => String(pupil.pupil_id));
                    this.selectedPupilIds = this.selectedPupilIds.filter(id => !visibleIds.includes(String(id)));
                    return;
                }

                const next = new Set(this.selectedPupilIds.map(String));
                this.paginatedSectionPupils.forEach(pupil => next.add(String(pupil.pupil_id)));
                this.selectedPupilIds = Array.from(next);
                this.showAddForm = false;
                this.closeActionMenu();
            },

            clearSelection() {
                this.selectedPupilIds = [];
            },

            async changeYear(yearId) {
                if (!yearId || this.loadingYear) return;

                this.loadingYear = true;

                try {
                    const url = `{{ route('principal.pupils') }}?ajax=1&year_id=${encodeURIComponent(yearId)}&_t=${Date.now()}`;
                    const response = await fetch(url, {
                        headers: {
                            'Accept': 'application/json',
                            'X-Requested-With': 'XMLHttpRequest',
                        },
                        cache: 'no-store',
                    });

                    const data = await response.json();

                    if (!response.ok || !data.success) {
                        alert(data.message || 'Failed to load school year data.');
                        return;
                    }

                    this.selectedYearId = data.selectedYearId;
                    this.yearPickerValue = data.selectedYearId;
                    this.grades = data.grades || [];
                    this.viewingGradeId = null;
                    this.viewingSectionId = null;
                    this.sectionsCollapsed = false;
                    this.tablePage = 1;
                    this.search = '';
                    this.showAddForm = false;
                    this.importMode = false;
                    this.resetImportPreview();
                    this.clearSelection();
                    this.closeActionMenu();
                    this.pupilForm = this.emptyPupilForm();
                } catch (error) {
                    console.error('Change year error:', error);
                    alert('An error occurred while loading school year data.');
                } finally {
                    this.loadingYear = false;
                }
            },

            openGrade(gradeId) {
                this.viewingGradeId = gradeId;
                this.viewingSectionId = this.selectedGradeSections[0]?.section_id || null;
                this.sectionsCollapsed = false;
                this.tablePage = 1;
                this.search = '';
                this.showAddForm = false;
                this.importMode = false;
                this.resetImportPreview();
                this.clearSelection();
                this.closeActionMenu();
                this.pupilForm = this.emptyPupilForm();
            },

            backToGrades() {
                this.viewingGradeId = null;
                this.viewingSectionId = null;
                this.sectionsCollapsed = false;
                this.showAddForm = false;
                this.importMode = false;
                this.resetImportPreview();
                this.search = '';
                this.tablePage = 1;
                this.clearSelection();
                this.closeActionMenu();
            },

            openSection(sectionId) {
                this.viewingSectionId = sectionId;
                this.tablePage = 1;
                this.search = '';
                this.showAddForm = false;
                this.importMode = false;
                this.resetImportPreview();
                this.clearSelection();
                this.closeActionMenu();
                this.pupilForm = this.emptyPupilForm();
            },

            openImportModal() {
                if (!this.selectedSection || !this.selectedGrade) return;
                this.showAddForm = false;
                this.clearSelection();
                this.closeActionMenu();
                this.resetImportPreview();
                this.importMode = true;
                this.importStage = 'upload';
            },

            closeImportModal() {
                this.importMode = false;
                this.resetImportPreview();
            },

            resetImportPreview() {
                this.importRows = [];
                this.importSummary = { total: 0, valid: 0, invalid: 0 };
                this.importErrors = {};
                this.importLoading = false;
                this.importCommitting = false;
                this.importStage = 'upload';

                this.$nextTick(() => {
                    if (this.$refs.importForm) {
                        this.$refs.importForm.reset();
                    }
                });
            },

            async previewImport() {
                if (!this.selectedSection || !this.selectedGrade) return;

                this.importErrors = {};
                this.importLoading = true;
                this.importStage = 'validating';

                const formData = new FormData(this.$refs.importForm);
                formData.append('year_id', this.selectedYearId);
                formData.append('grade_level_id', this.selectedGrade.grade_level_id);
                formData.append('section_id', this.selectedSection.section_id);

                try {
                    const response = await fetch(`{{ route('principal.pupils.import.preview') }}`, {
                        method: 'POST',
                        headers: {
                            'X-CSRF-TOKEN': '{{ csrf_token() }}',
                            'Accept': 'application/json',
                        },
                        body: formData,
                    });

                    const data = await response.json();

                    if (!response.ok) {
                        this.importErrors = data.errors || {};
                        this.importStage = 'upload';
                        alert(data.message || 'Failed to validate CSV file.');
                        return;
                    }

                    this.importRows = data.rows || [];
                    this.importSummary = data.summary || { total: 0, valid: 0, invalid: 0 };
                    this.importStage = 'preview';
                } catch (error) {
                    console.error('Preview pupil import error:', error);
                    this.importStage = 'upload';
                    alert('An error occurred while validating the CSV file.');
                } finally {
                    this.importLoading = false;
                }
            },

            async commitImport() {
                if (!this.selectedSection || !this.selectedGrade) return;

                const validRows = this.importRows
                    .filter(row => row.valid)
                    .map(row => row.data);

                if (!validRows.length) {
                    alert('There are no valid rows to import.');
                    return;
                }

                this.importCommitting = true;

                try {
                    const response = await fetch(`{{ route('principal.pupils.import.commit') }}`, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': '{{ csrf_token() }}',
                            'Accept': 'application/json',
                        },
                        body: JSON.stringify({
                            year_id: this.selectedYearId,
                            grade_level_id: this.selectedGrade.grade_level_id,
                            section_id: this.selectedSection.section_id,
                            rows: validRows,
                        }),
                    });

                    const data = await response.json();

                    if (!response.ok) {
                        if (data.rows) {
                            this.importRows = data.rows;
                            this.importSummary = data.summary || this.importSummary;
                        }
                        alert(data.message || 'Failed to import pupils.');
                        return;
                    }

                    (data.pupils || []).forEach(pupil => this.moveOrReplacePupil(pupil));
                    this.refreshCounts();
                    this.closeImportModal();
                    this.successMessage = data.message || 'Pupils imported successfully.';
                    this.successModal = true;
                } catch (error) {
                    console.error('Commit pupil import error:', error);
                    alert('An error occurred while importing pupils.');
                } finally {
                    this.importCommitting = false;
                }
            },

            toggleAddForm() {
                this.importMode = false;
                this.resetImportPreview();
                this.showAddForm = !this.showAddForm;
                this.formErrors = {};
                this.clearSelection();
                this.closeActionMenu();
                if (this.showAddForm) this.pupilForm = this.emptyPupilForm();
            },

            payloadFromForm(form) {
                return {
                    year_id: this.selectedYearId,
                    grade_level_id: form.grade_level_id || this.selectedGrade?.grade_level_id,
                    section_id: form.section_id || this.selectedSection?.section_id,
                    lrn: form.lrn,
                    full_name: form.full_name,
                    sex: form.sex || null,
                    age: form.age || null,
                    guardian_name: form.guardian_name || null,
                    guardian_contact: form.guardian_contact || null,
                };
            },

            async addPupil() {
                if (!this.selectedSection || !this.selectedGrade) return;

                this.formErrors = {};
                this.saving = true;

                try {
                    const response = await fetch(`{{ route('principal.pupils.store') }}`, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': '{{ csrf_token() }}',
                            'Accept': 'application/json',
                        },
                        body: JSON.stringify(this.payloadFromForm(this.pupilForm)),
                    });

                    const data = await response.json();

                    if (!response.ok) {
                        this.formErrors = data.errors || {};
                        alert(data.message || 'Failed to add pupil.');
                        return;
                    }

                    this.moveOrReplacePupil(data.pupil);
                    this.refreshCounts();
                    this.pupilForm = this.emptyPupilForm();
                    this.showAddForm = false;
                    this.successMessage = data.message || 'Pupil added successfully.';
                    this.successModal = true;
                } catch (error) {
                    console.error('Add pupil error:', error);
                    alert('An error occurred while adding the pupil.');
                } finally {
                    this.saving = false;
                }
            },

            openEdit(pupil) {
                this.clearSelection();
                this.closeActionMenu();
                this.editTarget = pupil;
                this.editForm = {
                    pupil_id: pupil.pupil_id,
                    lrn: pupil.lrn || '',
                    full_name: pupil.full_name || '',
                    sex: pupil.sex || '',
                    age: pupil.age || '',
                    guardian_name: pupil.guardian_name || '',
                    guardian_contact: pupil.guardian_contact || '',
                    grade_level_id: this.selectedGrade?.grade_level_id,
                    section_id: pupil.section_id || this.selectedSection?.section_id,
                };
                this.formErrors = {};
                this.editModal = true;
            },

            closeEdit() {
                this.editModal = false;
                this.editTarget = null;
                this.editForm = {};
                this.formErrors = {};
            },

            syncEditGrade() {
                const section = this.selectedGradeSections.find(item => String(item.section_id) === String(this.editForm.section_id));
                if (section) this.editForm.grade_level_id = section.grade_level_id;
            },

            async updatePupil() {
                if (!this.editTarget) return;

                this.formErrors = {};
                this.saving = true;
                this.syncEditGrade();

                try {
                    const response = await fetch(`/principal/pupils/${this.editTarget.pupil_id}`, {
                        method: 'PATCH',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': '{{ csrf_token() }}',
                            'Accept': 'application/json',
                        },
                        body: JSON.stringify(this.payloadFromForm(this.editForm)),
                    });

                    const data = await response.json();

                    if (!response.ok) {
                        this.formErrors = data.errors || {};
                        alert(data.message || 'Failed to update pupil.');
                        return;
                    }

                    this.moveOrReplacePupil(data.pupil);
                    this.refreshCounts();
                    this.closeEdit();
                    this.successMessage = data.message || 'Pupil updated successfully.';
                    this.successModal = true;
                } catch (error) {
                    console.error('Update pupil error:', error);
                    alert('An error occurred while updating the pupil.');
                } finally {
                    this.saving = false;
                }
            },

            confirmDrop(pupil) {
                this.openConfirm(
                    'Mark pupil as dropped?',
                    `${pupil.full_name} will remain in records but will be shown as Dropped.`,
                    () => this.dropPupil(pupil)
                );
            },

            confirmArchive(pupil) {
                this.openConfirm(
                    'Archive pupil?',
                    `${pupil.full_name} will be archived and hidden from the current pupil list.`,
                    () => this.archivePupil(pupil)
                );
            },

            confirmDelete(pupil) {
                this.openConfirm(
                    'Delete pupil?',
                    `${pupil.full_name} will be permanently deleted from the pupil records.`,
                    () => this.deletePupil(pupil)
                );
            },

            confirmBulkDelete() {
                this.openConfirm(
                    'Delete selected pupils?',
                    `${this.selectedPupilIds.length} selected pupil(s) will be permanently deleted from the pupil records.`,
                    () => this.bulkDeletePupils()
                );
            },

            openConfirm(title, message, action) {
                this.confirmTitle = title;
                this.confirmMessage = message;
                this.confirmAction = action;
                this.confirmModal = true;
            },

            closeConfirm() {
                this.confirmModal = false;
                this.confirmTitle = '';
                this.confirmMessage = '';
                this.confirmAction = null;
            },

            runConfirmAction() {
                if (typeof this.confirmAction === 'function') this.confirmAction();
            },

            async dropPupil(pupil) {
                await this.patchStatus(`/principal/pupils/${pupil.pupil_id}/drop`, 'Pupil marked as dropped successfully.');
            },

            async restorePupil(pupil) {
                await this.patchStatus(`/principal/pupils/${pupil.pupil_id}/restore`, 'Pupil restored successfully.');
            },

            async archivePupil(pupil) {
                await this.patchStatus(`/principal/pupils/${pupil.pupil_id}/archive`, 'Pupil archived successfully.', true);
            },

            async deletePupil(pupil) {
                await this.patchStatus(`/principal/pupils/${pupil.pupil_id}`, 'Pupil deleted successfully.', true, 'DELETE');
            },

            async patchStatus(url, fallbackMessage, remove = false, method = 'PATCH') {
                try {
                    const response = await fetch(url, {
                        method: method,
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': '{{ csrf_token() }}',
                            'Accept': 'application/json',
                        },
                    });

                    const data = await response.json();

                    if (!response.ok) {
                        alert(data.message || 'Failed to update pupil status.');
                        return;
                    }

                    if (remove) this.removePupil(data.pupil?.pupil_id || data.pupil_id || data.id || url.split('/').pop());
                    else this.moveOrReplacePupil(data.pupil);

                    this.refreshCounts();
                    this.clearSelection();
                    this.closeConfirm();
                    this.closeActionMenu();
                    this.successMessage = data.message || fallbackMessage;
                    this.successModal = true;
                } catch (error) {
                    console.error('Patch pupil status error:', error);
                    alert('An error occurred while updating the pupil status.');
                }
            },

            async bulkDeletePupils() {
                if (!this.selectedPupilIds.length) return;

                try {
                    const response = await fetch(`/principal/pupils`, {
                        method: 'DELETE',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': '{{ csrf_token() }}',
                            'Accept': 'application/json',
                        },
                        body: JSON.stringify({ ids: this.selectedPupilIds }),
                    });

                    const data = await response.json();

                    if (!response.ok) {
                        alert(data.message || 'Failed to delete selected pupils.');
                        return;
                    }

                    (data.deleted_ids || this.selectedPupilIds).forEach(id => this.removePupil(id));
                    this.refreshCounts();
                    this.clearSelection();
                    this.closeConfirm();
                    this.closeActionMenu();
                    this.successMessage = data.message || 'Selected pupils deleted successfully.';
                    this.successModal = true;
                } catch (error) {
                    console.error('Bulk delete pupils error:', error);
                    alert('An error occurred while deleting selected pupils.');
                }
            },

            moveOrReplacePupil(pupil) {
                if (!pupil) return;

                this.grades.forEach(grade => {
                    (grade.sections || []).forEach(section => {
                        section.pupils = (section.pupils || []).filter(item => String(item.pupil_id) !== String(pupil.pupil_id));
                    });
                });

                const targetGrade = this.grades.find(grade => String(grade.grade_level_id) === String(pupil.grade_level_id));
                const targetSection = targetGrade?.sections?.find(section => String(section.section_id) === String(pupil.section_id));

                if (targetSection && pupil.status !== 'archived') {
                    targetSection.pupils.unshift(pupil);
                    targetSection.pupils.sort((a, b) => String(a.full_name || '').localeCompare(String(b.full_name || '')));
                }
            },

            removePupil(pupilId) {
                if (!pupilId) return;

                this.grades.forEach(grade => {
                    (grade.sections || []).forEach(section => {
                        section.pupils = (section.pupils || []).filter(item => String(item.pupil_id) !== String(pupilId));
                    });
                });
            },

            refreshCounts() {
                this.grades = this.grades.map(grade => {
                    grade.sections = (grade.sections || []).map(section => {
                        const pupils = section.pupils || [];
                        return {
                            ...section,
                            pupil_count: pupils.length,
                            enrolled_count: pupils.filter(pupil => pupil.status === 'enrolled').length,
                            dropped_count: pupils.filter(pupil => pupil.status === 'inactive').length,
                        };
                    });

                    return {
                        ...grade,
                        section_count: (grade.sections || []).length,
                        total_pupils_count: (grade.sections || []).reduce((sum, section) => sum + Number(section.pupil_count || 0), 0),
                        enrolled_pupils_count: (grade.sections || []).reduce((sum, section) => sum + Number(section.enrolled_count || 0), 0),
                        dropped_pupils_count: (grade.sections || []).reduce((sum, section) => sum + Number(section.dropped_count || 0), 0),
                    };
                });
            },

            statusLabel(status) {
                if (status === 'enrolled') return 'Enrolled';
                if (status === 'inactive') return 'Dropped';
                if (status === 'transferred') return 'Transferred';
                if (status === 'archived') return 'Archived';
                return status || 'Unknown';
            },

            pupilBadgeClass(status) {
                if (status === 'enrolled') return 'bg-green-100 text-green-700 dark:bg-green-500/10 dark:text-green-400';
                if (status === 'inactive') return 'bg-yellow-100 text-yellow-700 dark:bg-yellow-500/10 dark:text-yellow-400';
                if (status === 'transferred') return 'bg-blue-100 text-blue-700 dark:bg-blue-500/10 dark:text-blue-400';
                if (status === 'archived') return 'bg-gray-100 text-gray-700 dark:bg-gray-500/10 dark:text-gray-300';
                return 'bg-gray-100 text-gray-700 dark:bg-gray-500/10 dark:text-gray-300';
            },
        };
    }
</script>

<style>
    [x-cloak] { display: none !important; }
</style>
