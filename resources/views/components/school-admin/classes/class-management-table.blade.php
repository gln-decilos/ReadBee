@props([
    'schoolYears' => [],
    'selectedYearId' => null,
    'grades' => [],
])

<div
    x-data='{
        selectedYearId: @json($selectedYearId),
        yearPickerValue: @json($selectedYearId),
        schoolYears: @json($schoolYears),
        grades: @json($grades),

        viewingGradeId: null,
        openAddFormGrade: null,

        sectionForm: {
            grade_level_id: "",
            section_name: "",
            adviser_name: ""
        },

        editModal: false,
        editTarget: null,
        editForm: {
            section_id: "",
            grade_level_id: "",
            section_name: "",
            adviser_name: ""
        },

        formErrors: {},
        successModal: false,
        successMessage: "",

        archiveModal: false,
        archiveTarget: null,

        deleteModal: false,
        deleteTarget: null,

        loadingYear: false,
        saving: false,

        get selectedYearLabel() {
            const y = this.schoolYears.find(item => String(item.year_id) === String(this.selectedYearId));
            if (!y) return "";
            const start = new Date(y.start_date).getFullYear();
            const end = new Date(y.end_date).getFullYear();
            return `${start} - ${end}`;
        },

        get displayedYearLabel() {
            if (this.loadingYear) return "Loading...";

            const y = this.schoolYears.find(item => String(item.year_id) === String(this.yearPickerValue));
            if (!y) return "Select School Year";

            const start = new Date(y.start_date).getFullYear();
            const end = new Date(y.end_date).getFullYear();
            return `${start} - ${end}`;
        },

        get selectedGrade() {
            return this.grades.find(g => String(g.grade_level_id) === String(this.viewingGradeId)) || null;
        },

        async changeYear(yearId) {
            if (!yearId || this.loadingYear) return;
            if (String(yearId) === String(this.selectedYearId)) return;

            this.loadingYear = true;

            try {
                const url = `{{ route('school-admin.classes.index') }}?ajax=1&year_id=${encodeURIComponent(yearId)}&_t=${Date.now()}`;

                const response = await fetch(url, {
                    headers: {
                        "Accept": "application/json",
                        "X-Requested-With": "XMLHttpRequest"
                    },
                    cache: "no-store"
                });

                const text = await response.text();
                let data = null;

                try {
                    data = JSON.parse(text);
                } catch (error) {
                    console.error("Non-JSON response:", text);
                    throw new Error("Server returned HTML instead of JSON.");
                }

                if (!response.ok || !data.success) {
                    alert(data.message || "Failed to load school year data.");
                    this.yearPickerValue = this.selectedYearId;
                    return;
                }

                this.selectedYearId = data.selectedYearId;
                this.yearPickerValue = data.selectedYearId;
                this.schoolYears = Array.isArray(data.schoolYears) ? data.schoolYears : this.schoolYears;
                this.grades = Array.isArray(data.grades) ? data.grades : [];

                this.viewingGradeId = null;
                this.openAddFormGrade = null;
                this.closeEditModal();
                this.formErrors = {};
            } catch (error) {
                console.error("Change year error:", error);
                this.yearPickerValue = this.selectedYearId;
                alert("An error occurred while loading the selected school year.");
            } finally {
                this.loadingYear = false;
            }
        },

        openSections(gradeId) {
            this.viewingGradeId = gradeId;
            this.openAddFormGrade = null;
            this.formErrors = {};
            window.scrollTo({ top: 0, behavior: "smooth" });
        },

        backToGrades() {
            this.viewingGradeId = null;
            this.openAddFormGrade = null;
            this.formErrors = {};
            this.closeEditModal();
        },

        openAddForm(gradeId) {
            this.openAddFormGrade = this.openAddFormGrade === gradeId ? null : gradeId;
            this.sectionForm = {
                grade_level_id: gradeId,
                section_name: "",
                adviser_name: ""
            };
            this.formErrors = {};
            this.closeEditModal();
        },

        getGradeById(gradeId) {
            return this.grades.find(g => String(g.grade_level_id) === String(gradeId));
        },

        getSectionCount(grade) {
            return grade.sections ? grade.sections.length : 0;
        },

        getActiveCount(grade) {
            return (grade.sections || []).filter(section => section.status === "active").length;
        },

        badgeClass(status) {
            if (status === "active") return "bg-green-100 text-green-700 dark:bg-green-500/10 dark:text-green-400";
            if (status === "inactive") return "bg-yellow-100 text-yellow-700 dark:bg-yellow-500/10 dark:text-yellow-400";
            if (status === "archived") return "bg-gray-100 text-gray-700 dark:bg-gray-500/10 dark:text-gray-300";
            return "bg-gray-100 text-gray-700 dark:bg-gray-500/10 dark:text-gray-300";
        },

        async addSection() {
            this.formErrors = {};
            this.saving = true;

            const payload = {
                year_id: this.selectedYearId,
                grade_level_id: this.sectionForm.grade_level_id,
                section_name: this.sectionForm.section_name,
                adviser_name: this.sectionForm.adviser_name || null,
            };

            try {
                const response = await fetch("{{ route('school-admin.classes.store') }}", {
                    method: "POST",
                    headers: {
                        "Content-Type": "application/json",
                        "X-CSRF-TOKEN": "{{ csrf_token() }}",
                        "Accept": "application/json"
                    },
                    body: JSON.stringify(payload)
                });

                const data = await response.json();

                if (!response.ok) {
                    this.formErrors = data.errors || {};
                    alert(data.message || "Failed to create section.");
                    return;
                }

                const grade = this.getGradeById(this.sectionForm.grade_level_id);

                if (grade) {
                    grade.sections.push(data.section);
                    grade.sections.sort((a, b) => String(a.section_name || "").localeCompare(String(b.section_name || "")));
                }

                this.sectionForm = {
                    grade_level_id: this.sectionForm.grade_level_id,
                    section_name: "",
                    adviser_name: ""
                };

                this.openAddFormGrade = null;
                this.successMessage = data.message || "Section created successfully.";
                this.successModal = true;
            } catch (error) {
                console.error("Add section error:", error);
                alert("An error occurred while creating the section.");
            } finally {
                this.saving = false;
            }
        },

        openEditModal(section) {
            this.editTarget = section;
            this.editForm = {
                section_id: section.section_id,
                grade_level_id: section.grade_level_id || this.selectedGrade?.grade_level_id || "",
                section_name: section.section_name || "",
                adviser_name: section.adviser_name || ""
            };
            this.formErrors = {};
            this.openAddFormGrade = null;
            this.editModal = true;
        },

        closeEditModal() {
            this.editModal = false;
            this.editTarget = null;
            this.editForm = {
                section_id: "",
                grade_level_id: "",
                section_name: "",
                adviser_name: ""
            };
            this.formErrors = {};
        },

        async updateSection() {
            if (!this.editTarget) return;

            this.formErrors = {};
            this.saving = true;

            const payload = {
                year_id: this.selectedYearId,
                grade_level_id: this.editForm.grade_level_id,
                section_name: this.editForm.section_name,
                adviser_name: this.editForm.adviser_name || null,
            };

            try {
                const response = await fetch(`/school-admin/classes/${this.editTarget.section_id}`, {
                    method: "PATCH",
                    headers: {
                        "Content-Type": "application/json",
                        "X-CSRF-TOKEN": "{{ csrf_token() }}",
                        "Accept": "application/json"
                    },
                    body: JSON.stringify(payload)
                });

                const data = await response.json();

                if (!response.ok) {
                    this.formErrors = data.errors || {};
                    alert(data.message || "Failed to update section.");
                    return;
                }

                this.upsertSection(data.section);
                this.closeEditModal();
                this.successMessage = data.message || "Section updated successfully.";
                this.successModal = true;
            } catch (error) {
                console.error("Update section error:", error);
                alert("An error occurred while updating the section.");
            } finally {
                this.saving = false;
            }
        },

        upsertSection(updatedSection) {
            if (!updatedSection) return;

            this.grades = this.grades.map(grade => {
                grade.sections = (grade.sections || []).filter(
                    section => String(section.section_id) !== String(updatedSection.section_id)
                );

                if (String(grade.grade_level_id) === String(updatedSection.grade_level_id)) {
                    grade.sections.push(updatedSection);
                    grade.sections.sort((a, b) => String(a.section_name || "").localeCompare(String(b.section_name || "")));
                }

                return grade;
            });
        },

        confirmArchive(section) {
            this.archiveTarget = section;
            this.archiveModal = true;
        },

        async archiveSection() {
            if (!this.archiveTarget) return;

            try {
                const response = await fetch(`/school-admin/classes/${this.archiveTarget.section_id}/archive`, {
                    method: "PATCH",
                    headers: {
                        "Content-Type": "application/json",
                        "X-CSRF-TOKEN": "{{ csrf_token() }}",
                        "Accept": "application/json"
                    },
                    body: JSON.stringify({
                        year_id: this.selectedYearId
                    })
                });

                const data = await response.json();

                if (!response.ok) {
                    alert(data.message || "Failed to archive section.");
                    return;
                }

                this.grades = this.grades.map(grade => {
                    grade.sections = (grade.sections || []).filter(
                        section => String(section.section_id) !== String(this.archiveTarget.section_id)
                    );
                    return grade;
                });

                this.archiveModal = false;
                this.archiveTarget = null;
                this.successMessage = data.message || "Section archived successfully.";
                this.successModal = true;
            } catch (error) {
                console.error("Archive section error:", error);
                alert("An error occurred while archiving the section.");
            }
        },

        confirmDelete(section) {
            this.deleteTarget = section;
            this.deleteModal = true;
        },

        async deleteSection() {
            if (!this.deleteTarget) return;

            try {
                const response = await fetch(`/school-admin/classes/${this.deleteTarget.section_id}`, {
                    method: "DELETE",
                    headers: {
                        "Content-Type": "application/json",
                        "X-CSRF-TOKEN": "{{ csrf_token() }}",
                        "Accept": "application/json"
                    }
                });

                const data = await response.json();

                if (!response.ok) {
                    alert(data.message || "Failed to delete section.");
                    return;
                }

                this.grades = this.grades.map(grade => {
                    grade.sections = (grade.sections || []).filter(
                        section => String(section.section_id) !== String(this.deleteTarget.section_id)
                    );
                    return grade;
                });

                this.deleteModal = false;
                this.deleteTarget = null;
                this.successMessage = data.message || "Section deleted successfully.";
                this.successModal = true;
            } catch (error) {
                console.error("Delete section error:", error);
                alert("An error occurred while deleting the section.");
            }
        }
    }'
    class="space-y-6"
>
    <div class="overflow-hidden rounded-3xl border border-gray-200 bg-white shadow-sm dark:border-white/[0.05] dark:bg-white/[0.03]">
        <div class="flex flex-col gap-5 px-6 py-6 lg:flex-row lg:items-end lg:justify-between">
            <div>
                <div class="inline-flex items-center rounded-full bg-yellow-100 px-3 py-1 text-xs font-medium text-yellow-700 dark:bg-yellow-500/10 dark:text-yellow-400">
                    School Year Management
                </div>
                <h3 class="mt-3 text-xl font-semibold text-gray-800 dark:text-white/90">
                    Class Management
                </h3>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                    Manage sections and adviser names by grade level and school year.
                </p>
            </div>

            <div class="w-full lg:w-auto">
                <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">
                    School Year
                </label>

                <div class="relative min-w-[260px]">
                    <select
                        :value="yearPickerValue"
                        @change="changeYear($event.target.value)"
                        :disabled="loadingYear"
                        class="dark:bg-gray-900 shadow-theme-xs h-11 w-full appearance-none rounded-xl border border-gray-300 bg-transparent px-4 py-2.5 pr-10 text-sm text-gray-800 disabled:cursor-not-allowed disabled:opacity-70 dark:border-gray-700 dark:text-white/90"
                    >
                        <template x-if="loadingYear">
                            <option value="" selected x-text="displayedYearLabel"></option>
                        </template>

                        <template x-if="!loadingYear">
                            <template x-for="year in schoolYears" :key="year.year_id">
                                <option
                                    :value="year.year_id"
                                    x-text="`${new Date(year.start_date).getFullYear()} - ${new Date(year.end_date).getFullYear()}`"
                                ></option>
                            </template>
                        </template>
                    </select>

                    <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center pr-3">
                        <template x-if="loadingYear">
                            <svg class="h-4 w-4 animate-spin text-gray-500 dark:text-gray-400" viewBox="0 0 24 24" fill="none">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"></path>
                            </svg>
                        </template>

                        <template x-if="!loadingYear">
                            <svg class="h-4 w-4 text-gray-500 dark:text-gray-400" fill="none" viewBox="0 0 20 20" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M6 8l4 4 4-4" />
                            </svg>
                        </template>
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

                                        <h4 class="mt-4 text-lg font-semibold text-gray-800 dark:text-white/90">
                                            Grade <span x-text="grade.grade_number"></span>
                                        </h4>
                                        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                                            School Year <span x-text="selectedYearLabel"></span>
                                        </p>
                                    </div>

                                    <button
                                        type="button"
                                        @click="openSections(grade.grade_level_id)"
                                        class="inline-flex h-10 w-10 items-center justify-center rounded-xl border border-gray-300 bg-white text-gray-700 shadow-sm hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-800 dark:text-white dark:hover:bg-gray-700"
                                        title="View Sections"
                                    >
                                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                        </svg>
                                    </button>
                                </div>

                                <div class="mt-5 grid grid-cols-2 gap-3">
                                    <div class="rounded-2xl border border-gray-200 bg-white p-4 dark:border-white/[0.05] dark:bg-white/[0.03]">
                                        <p class="text-xs font-medium uppercase tracking-wide text-gray-500 dark:text-gray-400">Active Sections</p>
                                        <p class="mt-2 text-2xl font-semibold text-gray-800 dark:text-white" x-text="getActiveCount(grade)"></p>
                                    </div>

                                    <div class="rounded-2xl border border-gray-200 bg-white p-4 dark:border-white/[0.05] dark:bg-white/[0.03]">
                                        <p class="text-xs font-medium uppercase tracking-wide text-gray-500 dark:text-gray-400">Total Sections</p>
                                        <p class="mt-2 text-2xl font-semibold text-gray-800 dark:text-white" x-text="getSectionCount(grade)"></p>
                                    </div>
                                </div>

                                <div class="mt-5 flex items-center justify-between rounded-2xl bg-gray-100 px-4 py-3 dark:bg-white/[0.04]">
                                    <span class="text-sm font-medium text-gray-600 dark:text-gray-300">
                                        View section details
                                    </span>
                                    <span class="text-xs text-gray-500 dark:text-gray-400">
                                        Click the eye icon
                                    </span>
                                </div>
                            </div>
                        </div>
                    </template>
                </div>
            </div>
        </template>

        <template x-if="viewingGradeId && selectedGrade">
            <div class="px-6 pb-6">
                <div class="mb-6 flex flex-col gap-4 rounded-3xl border border-gray-200 bg-gradient-to-r from-white to-gray-50 p-5 shadow-sm dark:border-white/[0.05] dark:from-gray-900 dark:to-black sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <div class="inline-flex items-center rounded-full bg-yellow-100 px-3 py-1 text-xs font-medium text-yellow-700 dark:bg-yellow-500/10 dark:text-yellow-400">
                            Grade Detail View
                        </div>
                        <h3 class="mt-3 text-xl font-semibold text-gray-800 dark:text-white/90">
                            Grade <span x-text="selectedGrade.grade_number"></span> Sections
                        </h3>
                        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                            School Year <span x-text="selectedYearLabel"></span>
                        </p>
                    </div>

                    <div class="flex items-center gap-3">
                        <button
                            type="button"
                            @click="backToGrades()"
                            class="inline-flex items-center justify-center rounded-xl border border-gray-300 px-4 py-2.5 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:text-white dark:hover:bg-gray-800"
                        >
                            Back to Grades
                        </button>

                        <button
                            type="button"
                            @click="openAddForm(selectedGrade.grade_level_id)"
                            class="inline-flex items-center justify-center rounded-xl bg-yellow-100 px-4 py-2.5 text-sm font-medium text-yellow-700 hover:bg-yellow-200 dark:bg-yellow-500/10 dark:text-yellow-400 dark:hover:bg-yellow-500/20"
                        >
                            <span x-text="openAddFormGrade === selectedGrade.grade_level_id ? 'Close Form' : 'Add Section'"></span>
                        </button>
                    </div>
                </div>

                <div x-show="openAddFormGrade === selectedGrade.grade_level_id" x-transition class="mb-6">
                    <form @submit.prevent="addSection()">
                        <x-common.component-card title="Add Section">
                            <div>
                                <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">
                                    Section Name
                                </label>
                                <input
                                    type="text"
                                    x-model="sectionForm.section_name"
                                    placeholder="e.g. Rizal"
                                    class="dark:bg-gray-900 shadow-theme-xs h-11 w-full rounded-xl border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 dark:border-gray-700 dark:text-white/90"
                                />
                                <template x-if="formErrors.section_name">
                                    <p class="mt-1.5 text-sm text-red-500" x-text="formErrors.section_name[0]"></p>
                                </template>
                            </div>

                            <div>
                                <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">
                                    Adviser Full Name
                                </label>
                                <input
                                    type="text"
                                    x-model="sectionForm.adviser_name"
                                    placeholder="e.g. Juan Dela Cruz"
                                    class="dark:bg-gray-900 shadow-theme-xs h-11 w-full rounded-xl border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 dark:border-gray-700 dark:text-white/90"
                                />
                                <template x-if="formErrors.adviser_name">
                                    <p class="mt-1.5 text-sm text-red-500" x-text="formErrors.adviser_name[0]"></p>
                                </template>
                            </div>
                        </x-common.component-card>

                        <div class="mt-4 flex justify-end">
                            <button
                                type="submit"
                                :disabled="saving"
                                class="inline-flex items-center justify-center rounded-xl bg-yellow-100 px-4 py-2.5 text-sm font-medium text-yellow-700 hover:bg-yellow-200 disabled:cursor-not-allowed disabled:opacity-60 dark:bg-yellow-500/10 dark:text-yellow-400 dark:hover:bg-yellow-500/20"
                            >
                                <span x-show="!saving">Save Section</span>
                                <span x-show="saving">Saving...</span>
                            </button>
                        </div>
                    </form>
                </div>

                <div class="overflow-hidden rounded-3xl border border-gray-200 bg-white shadow-sm dark:border-white/[0.05] dark:bg-white/[0.03]">
                    <div class="max-w-full overflow-x-auto">
                        <table class="w-full">
                            <thead class="border-y bg-gray-50 dark:border-white/[0.05] dark:bg-gray-900">
                                <tr>
                                    <th class="px-6 py-4 text-start text-xs font-medium uppercase tracking-wide text-gray-500 dark:text-gray-400">Section</th>
                                    <th class="px-6 py-4 text-start text-xs font-medium uppercase tracking-wide text-gray-500 dark:text-gray-400">Adviser</th>
                                    <th class="px-6 py-4 text-start text-xs font-medium uppercase tracking-wide text-gray-500 dark:text-gray-400">Status</th>
                                    <th class="px-6 py-4 text-start text-xs font-medium uppercase tracking-wide text-gray-500 dark:text-gray-400">Actions</th>
                                </tr>
                            </thead>

                            <tbody>
                                <template x-if="(selectedGrade.sections || []).length === 0">
                                    <tr>
                                        <td colspan="4" class="px-6 py-12 text-center">
                                            <div class="flex flex-col items-center justify-center rounded-3xl border border-gray-200 bg-white px-6 py-10 text-center shadow-sm dark:border-white/[0.05] dark:bg-white/[0.03]">
                                                <div class="max-w-md">
                                                    <h4 class="text-lg font-semibold text-gray-800 dark:text-white">
                                                        No sections yet
                                                    </h4>
                                                    <p class="mt-2 text-sm leading-6 text-gray-500 dark:text-gray-400">
                                                        Add a section for this grade in the selected school year.
                                                    </p>
                                                </div>
                                            </div>
                                        </td>
                                    </tr>
                                </template>

                                <template x-for="section in selectedGrade.sections" :key="section.section_id">
                                    <tr class="border-b border-gray-100 dark:border-white/[0.05]">
                                        <td class="px-6 py-4">
                                            <div class="flex items-center gap-3">
                                                <div class="flex h-10 w-10 items-center justify-center rounded-2xl bg-gray-100 text-gray-700 dark:bg-white/[0.04] dark:text-gray-300">
                                                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M8 7h8M8 12h8M8 17h5"/>
                                                    </svg>
                                                </div>
                                                <div>
                                                    <p class="text-sm font-semibold text-gray-800 dark:text-white/90" x-text="section.section_name"></p>
                                                    <p class="text-xs text-gray-500 dark:text-gray-400">Section record</p>
                                                </div>
                                            </div>
                                        </td>

                                        <td class="px-6 py-4">
                                            <p class="text-sm font-medium text-gray-800 dark:text-white/90" x-text="section.adviser_name || 'No adviser set'"></p>
                                        </td>

                                        <td class="px-6 py-4">
                                            <span
                                                class="inline-flex rounded-full px-3 py-1 text-xs font-medium"
                                                :class="badgeClass(section.status)"
                                                x-text="section.status.charAt(0).toUpperCase() + section.status.slice(1)"
                                            ></span>
                                        </td>

                                        <td class="px-6 py-4">
                                            <div class="flex flex-wrap items-center gap-2">
                                                <button
                                                    type="button"
                                                    @click="openEditModal(section)"
                                                    class="inline-flex items-center justify-center rounded-xl border border-gray-300 px-3 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:text-white dark:hover:bg-gray-800"
                                                >
                                                    Edit
                                                </button>

                                                <template x-if="section.status !== 'archived'">
                                                    <button
                                                        type="button"
                                                        @click="confirmArchive(section)"
                                                        class="inline-flex items-center justify-center rounded-xl border border-yellow-300 px-3 py-2 text-sm font-medium text-yellow-700 hover:bg-yellow-50 dark:border-yellow-500/30 dark:text-yellow-400 dark:hover:bg-yellow-500/10"
                                                    >
                                                        Archive
                                                    </button>
                                                </template>

                                                <button
                                                    type="button"
                                                    @click="confirmDelete(section)"
                                                    class="inline-flex items-center justify-center rounded-xl border border-red-200 px-3 py-2 text-sm font-medium text-red-600 hover:bg-red-50 dark:border-red-500/20 dark:hover:bg-red-500/10"
                                                >
                                                    Delete
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                </template>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </template>
    </div>

    <div x-show="editModal" x-cloak class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 px-4">
        <div @click.outside="closeEditModal" class="w-full max-w-2xl rounded-2xl bg-white p-6 shadow-xl dark:bg-gray-900">
            <div class="flex items-start justify-between gap-4">
                <div>
                    <h3 class="text-lg font-semibold text-gray-800 dark:text-white">Edit Section</h3>
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Update the section name and adviser full name.</p>
                </div>
                <button type="button" @click="closeEditModal" class="text-gray-400 hover:text-gray-700 dark:hover:text-gray-200">x</button>
            </div>

            <form @submit.prevent="updateSection" class="mt-5 space-y-4">
                <div>
                    <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">Section Name</label>
                    <input
                        type="text"
                        x-model="editForm.section_name"
                        class="dark:bg-gray-900 shadow-theme-xs h-11 w-full rounded-xl border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 dark:border-gray-700 dark:text-white/90"
                    />
                    <template x-if="formErrors.section_name">
                        <p class="mt-1.5 text-sm text-red-500" x-text="formErrors.section_name[0]"></p>
                    </template>
                </div>

                <div>
                    <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">Adviser Full Name</label>
                    <input
                        type="text"
                        x-model="editForm.adviser_name"
                        placeholder="e.g. Juan Dela Cruz"
                        class="dark:bg-gray-900 shadow-theme-xs h-11 w-full rounded-xl border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 dark:border-gray-700 dark:text-white/90"
                    />
                    <template x-if="formErrors.adviser_name">
                        <p class="mt-1.5 text-sm text-red-500" x-text="formErrors.adviser_name[0]"></p>
                    </template>
                </div>

                <div class="flex justify-end gap-3 border-t border-gray-200 pt-4 dark:border-gray-800">
                    <button type="button" @click="closeEditModal" class="rounded-lg border border-gray-300 px-4 py-2.5 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:text-gray-300 dark:hover:bg-white/5">Cancel</button>
                    <button type="submit" :disabled="saving" class="rounded-lg bg-yellow-100 px-5 py-2.5 text-sm font-semibold text-yellow-700 hover:bg-yellow-200 disabled:cursor-not-allowed disabled:opacity-60 dark:bg-yellow-500/10 dark:text-yellow-400 dark:hover:bg-yellow-500/20">
                        <span x-show="!saving">Save Changes</span>
                        <span x-show="saving">Saving...</span>
                    </button>
                </div>
            </form>
        </div>
    </div>

    <div x-show="archiveModal" x-cloak class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 px-4">
        <div class="w-full max-w-md rounded-2xl bg-white p-6 dark:bg-gray-900">
            <h3 class="text-lg font-semibold text-gray-800 dark:text-white">
                Confirm Archive
            </h3>
            <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">
                Are you sure you want to archive this section?
            </p>
            <div class="mt-6 flex justify-end gap-3">
                <x-ui.button variant="outline" @click="archiveModal = false">Cancel</x-ui.button>
                <x-ui.button variant="primary" @click="archiveSection()">Yes, Archive</x-ui.button>
            </div>
        </div>
    </div>

    <div x-show="deleteModal" x-cloak class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 px-4">
        <div class="w-full max-w-md rounded-2xl bg-white p-6 dark:bg-gray-900">
            <h3 class="text-lg font-semibold text-gray-800 dark:text-white">
                Confirm Delete
            </h3>
            <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">
                Are you sure you want to permanently delete this section?
            </p>
            <div class="mt-6 flex justify-end gap-3">
                <x-ui.button variant="outline" @click="deleteModal = false">Cancel</x-ui.button>
                <x-ui.button variant="primary" @click="deleteSection()">Yes, Delete</x-ui.button>
            </div>
        </div>
    </div>

    <div x-show="successModal" x-cloak class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 px-4">
        <div class="w-full max-w-md rounded-2xl bg-white p-6 text-center dark:bg-gray-900">
            <div class="mb-4 flex justify-center">
                <div class="flex h-14 w-14 items-center justify-center rounded-full bg-green-100 dark:bg-green-500/10">
                    <svg class="h-8 w-8 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/>
                    </svg>
                </div>
            </div>
            <h3 class="text-lg font-semibold text-gray-800 dark:text-white">
                Success
            </h3>
            <p class="mt-2 text-sm text-gray-500 dark:text-gray-400" x-text="successMessage"></p>
            <div class="mt-6">
                <x-ui.button variant="primary" @click="successModal = false">Close</x-ui.button>
            </div>
        </div>
    </div>
</div>

<style>
    [x-cloak] { display: none !important; }
</style>
