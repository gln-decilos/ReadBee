@props([
    'schoolYears' => [],
    'page' => 1,
    'perPage' => 6,
])

<div x-data='{
    allSchoolYears: @json($schoolYears),

    page: {{ (int) $page }},
    perPage: {{ (int) $perPage }},

    selectedRows: [],
    selectAll: false,

    showAddForm: false,
    showEditForm: false,

    confirmModal: false,
    successModal: false,
    bulkDeleteModal: false,
    deleteSuccessModal: false,

    successMessage: "",
    deleteMessage: "",
    deleteMode: null,
    deleteId: null,

    formErrors: {},
    editFormErrors: {},

    createSchoolYear: {
        start_date: "",
        end_date: ""
    },

    quarters: [],

    editSchoolYear: {
        year_id: "",
        start_date: "",
        end_date: "",
        quarters: []
    },

    ordinalQuarterName(number) {
        const names = {
            1: "First Quarter",
            2: "Second Quarter",
            3: "Third Quarter",
            4: "Fourth Quarter",
            5: "Fifth Quarter",
            6: "Sixth Quarter",
            7: "Seventh Quarter",
            8: "Eighth Quarter",
            9: "Ninth Quarter",
            10: "Tenth Quarter"
        };

        return names[number] || `Quarter ${number}`;
    },

    initQuarterPickers(scope = "create") {
        this.$nextTick(() => {
            const container =
                scope === "create"
                    ? this.$refs.createQuarterContainer
                    : this.$refs.editQuarterContainer;

            if (!container) return;

            const startInputs = container.querySelectorAll(".quarter-start-picker");
            const endInputs = container.querySelectorAll(".quarter-end-picker");
            const sourceList = scope === "create" ? this.quarters : this.editSchoolYear.quarters;

            startInputs.forEach((input, index) => {
                if (input._flatpickr) {
                    input._flatpickr.destroy();
                }

                flatpickr(input, {
                    dateFormat: "Y-m-d",
                    allowInput: false,
                    defaultDate: sourceList[index]?.start_date || null,
                    onChange: (selectedDates, dateStr) => {
                        sourceList[index].start_date = dateStr;

                        const endInput = endInputs[index];
                        if (endInput?._flatpickr) {
                            endInput._flatpickr.set("minDate", dateStr || null);
                        }
                    }
                });
            });

            endInputs.forEach((input, index) => {
                if (input._flatpickr) {
                    input._flatpickr.destroy();
                }

                flatpickr(input, {
                    dateFormat: "Y-m-d",
                    allowInput: false,
                    defaultDate: sourceList[index]?.end_date || null,
                    onChange: (selectedDates, dateStr) => {
                        sourceList[index].end_date = dateStr;

                        const startInput = startInputs[index];
                        if (startInput?._flatpickr) {
                            startInput._flatpickr.set("maxDate", dateStr || null);
                        }
                    }
                });
            });
        });
    },

    init() {
        this.quarters = this.defaultQuarters(3);

        this.$nextTick(() => {
            flatpickr(this.$refs.createStartDate, {
                dateFormat: "Y-m-d",
                allowInput: false,
                onChange: (selectedDates, dateStr) => {
                    this.createSchoolYear.start_date = dateStr;
                    if (this.$refs.createEndDate?._flatpickr) {
                        this.$refs.createEndDate._flatpickr.set("minDate", dateStr || null);
                    }
                }
            });

            flatpickr(this.$refs.createEndDate, {
                dateFormat: "Y-m-d",
                allowInput: false,
                onChange: (selectedDates, dateStr) => {
                    this.createSchoolYear.end_date = dateStr;
                    if (this.$refs.createStartDate?._flatpickr) {
                        this.$refs.createStartDate._flatpickr.set("maxDate", dateStr || null);
                    }
                }
            });

            flatpickr(this.$refs.editStartDate, {
                dateFormat: "Y-m-d",
                allowInput: false,
                onChange: (selectedDates, dateStr) => {
                    this.editSchoolYear.start_date = dateStr;
                    if (this.$refs.editEndDate?._flatpickr) {
                        this.$refs.editEndDate._flatpickr.set("minDate", dateStr || null);
                    }
                }
            });

            flatpickr(this.$refs.editEndDate, {
                dateFormat: "Y-m-d",
                allowInput: false,
                onChange: (selectedDates, dateStr) => {
                    this.editSchoolYear.end_date = dateStr;
                    if (this.$refs.editStartDate?._flatpickr) {
                        this.$refs.editStartDate._flatpickr.set("maxDate", dateStr || null);
                    }
                }
            });

            this.$watch("editSchoolYear.start_date", value => {
                if (this.$refs.editStartDate?._flatpickr) {
                    this.$refs.editStartDate._flatpickr.setDate(value || null, false);
                }
                if (this.$refs.editEndDate?._flatpickr) {
                    this.$refs.editEndDate._flatpickr.set("minDate", value || null);
                }
            });

            this.$watch("editSchoolYear.end_date", value => {
                if (this.$refs.editEndDate?._flatpickr) {
                    this.$refs.editEndDate._flatpickr.setDate(value || null, false);
                }
                if (this.$refs.editStartDate?._flatpickr) {
                    this.$refs.editStartDate._flatpickr.set("maxDate", value || null);
                }
            });

            this.$watch("createSchoolYear.start_date", value => {
                if (this.$refs.createStartDate?._flatpickr) {
                    this.$refs.createStartDate._flatpickr.setDate(value || null, false);
                }
                if (this.$refs.createEndDate?._flatpickr) {
                    this.$refs.createEndDate._flatpickr.set("minDate", value || null);
                }
            });

            this.$watch("createSchoolYear.end_date", value => {
                if (this.$refs.createEndDate?._flatpickr) {
                    this.$refs.createEndDate._flatpickr.setDate(value || null, false);
                }
                if (this.$refs.createStartDate?._flatpickr) {
                    this.$refs.createStartDate._flatpickr.set("maxDate", value || null);
                }
            });

            this.initQuarterPickers("create");
        });
    },

    get total() {
        return this.allSchoolYears.length;
    },

    get lastPage() {
        return Math.max(Math.ceil(this.total / this.perPage), 1);
    },

    get paginatedSchoolYears() {
        const start = (this.page - 1) * this.perPage;
        const end = start + this.perPage;
        return this.allSchoolYears.slice(start, end);
    },

    get startItem() {
        if (this.total === 0) return 0;
        return (this.page - 1) * this.perPage + 1;
    },

    get endItem() {
        return Math.min(this.page * this.perPage, this.total);
    },

    visiblePages() {
        let start = Math.max(1, this.page - 2);
        let end = Math.min(this.lastPage, this.page + 2);

        const pages = [];
        for (let i = start; i <= end; i++) {
            pages.push(i);
        }
        return pages;
    },

    defaultQuarters(count = 3) {
        return Array.from({ length: count }, (_, index) => ({
            quarter_id: "",
            quarter_number: index + 1,
            quarter_name: this.ordinalQuarterName(index + 1),
            start_date: "",
            end_date: ""
        }));
    },

    renumberQuarters(list) {
        return list.map((quarter, index) => ({
            ...quarter,
            quarter_number: index + 1,
            quarter_name: this.ordinalQuarterName(index + 1)
        }));
    },

    addQuarter() {
        const nextNumber = this.quarters.length + 1;
        this.quarters.push({
            quarter_id: "",
            quarter_number: nextNumber,
            quarter_name: this.ordinalQuarterName(nextNumber),
            start_date: "",
            end_date: ""
        });
        this.quarters = this.renumberQuarters(this.quarters);
        this.initQuarterPickers("create");
    },

    removeQuarter(index) {
        if (this.quarters.length <= 1) return;
        this.quarters.splice(index, 1);
        this.quarters = this.renumberQuarters(this.quarters);
        this.initQuarterPickers("create");
    },

    addEditQuarter() {
        const nextNumber = this.editSchoolYear.quarters.length + 1;
        this.editSchoolYear.quarters.push({
            quarter_id: "",
            quarter_number: nextNumber,
            quarter_name: this.ordinalQuarterName(nextNumber),
            start_date: "",
            end_date: ""
        });
        this.editSchoolYear.quarters = this.renumberQuarters(this.editSchoolYear.quarters);
        this.initQuarterPickers("edit");
    },

    removeEditQuarter(index) {
        if (this.editSchoolYear.quarters.length <= 1) return;
        this.editSchoolYear.quarters.splice(index, 1);
        this.editSchoolYear.quarters = this.renumberQuarters(this.editSchoolYear.quarters);
        this.initQuarterPickers("edit");
    },

    goToPage(pageNumber) {
        if (pageNumber >= 1 && pageNumber <= this.lastPage) {
            this.page = pageNumber;
            this.selectedRows = [];
            this.selectAll = false;
        }
    },

    formatSchoolYear(startDate, endDate) {
        if (!startDate || !endDate) return "N/A";

        const startYear = new Date(startDate).getFullYear();
        const endYear = new Date(endDate).getFullYear();

        return `${startYear} - ${endYear}`;
    },

    formatDate(date) {
        if (!date) return "-";
        return new Date(date).toLocaleDateString("en-US", {
            year: "numeric",
            month: "long",
            day: "numeric"
        });
    },

    sortedQuarters(quarters) {
        if (!Array.isArray(quarters)) return [];
        return [...quarters].sort((a, b) => a.quarter_number - b.quarter_number);
    },

    handleSelectAll() {
        this.selectAll = !this.selectAll;
        this.selectedRows = this.selectAll
            ? this.paginatedSchoolYears.map(item => String(item.year_id))
            : [];
    },

    handleRowSelect(id) {
        id = String(id);

        if (this.selectedRows.includes(id)) {
            this.selectedRows = this.selectedRows.filter(rowId => rowId !== id);
        } else {
            this.selectedRows.push(id);
        }

        this.selectAll =
            this.paginatedSchoolYears.length > 0 &&
            this.selectedRows.length === this.paginatedSchoolYears.length;
    },

    deleteRow(id) {
        this.deleteMode = "single";
        this.deleteId = String(id);
        this.bulkDeleteModal = true;
    },

    triggerBulkDelete() {
        if (this.selectedRows.length > 0) {
            this.deleteMode = "bulk";
            this.bulkDeleteModal = true;
        }
    },

    submitForm() {
        this.confirmModal = false;
        this.addSchoolYear();
    },

    async addSchoolYear() {
        this.formErrors = {};
        this.quarters = this.renumberQuarters(this.quarters);

        const formData = new FormData(this.$refs.createForm);

        try {
            const response = await fetch("{{ route('district-admin.school-year.store') }}", {
                method: "POST",
                headers: {
                    "X-CSRF-TOKEN": "{{ csrf_token() }}",
                    "Accept": "application/json"
                },
                body: formData
            });

            const data = await response.json();

            if (!response.ok) {
                this.formErrors = data.errors || {};
                alert(data.message || "Failed to create school year.");
                return;
            }

            this.allSchoolYears.unshift(data.schoolYear);
            this.page = 1;
            this.showAddForm = false;
            this.successMessage = data.message || "School year created successfully.";
            this.successModal = true;
            this.$refs.createForm.reset();
            this.createSchoolYear = {
                start_date: "",
                end_date: ""
            };
            this.quarters = this.defaultQuarters(3);

            if (this.$refs.createStartDate?._flatpickr) {
                this.$refs.createStartDate._flatpickr.clear();
                this.$refs.createStartDate._flatpickr.set("maxDate", null);
            }

            if (this.$refs.createEndDate?._flatpickr) {
                this.$refs.createEndDate._flatpickr.clear();
                this.$refs.createEndDate._flatpickr.set("minDate", null);
            }

            this.$nextTick(() => this.initQuarterPickers("create"));

            this.selectedRows = [];
            this.selectAll = false;
        } catch (error) {
            console.error("Create error:", error);
            alert("An error occurred while creating school year.");
        }
    },

    openEditForm(schoolYear) {
        this.editFormErrors = {};
        this.editSchoolYear = {
            year_id: schoolYear.year_id,
            start_date: schoolYear.start_date || "",
            end_date: schoolYear.end_date || "",
            quarters: (schoolYear.quarter || []).length
                ? this.renumberQuarters(this.sortedQuarters(schoolYear.quarter))
                : this.defaultQuarters(3)
        };

        this.showEditForm = true;
        this.showAddForm = false;

        this.$nextTick(() => {
            if (this.$refs.editStartDate?._flatpickr) {
                this.$refs.editStartDate._flatpickr.setDate(this.editSchoolYear.start_date || null, false);
                this.$refs.editStartDate._flatpickr.set("maxDate", this.editSchoolYear.end_date || null);
            }

            if (this.$refs.editEndDate?._flatpickr) {
                this.$refs.editEndDate._flatpickr.setDate(this.editSchoolYear.end_date || null, false);
                this.$refs.editEndDate._flatpickr.set("minDate", this.editSchoolYear.start_date || null);
            }

            this.initQuarterPickers("edit");
        });
    },

    async updateSchoolYear() {
      this.editFormErrors = {};
      this.editSchoolYear.quarters = this.renumberQuarters(this.editSchoolYear.quarters);

      const formData = new FormData(this.$refs.editForm);
      formData.append("_method", "PATCH");

      try {
          const response = await fetch(`/district-admin/school-year/${this.editSchoolYear.year_id}`, {
              method: "POST",
              headers: {
                  "X-CSRF-TOKEN": "{{ csrf_token() }}",
                  "Accept": "application/json"
              },
              body: formData
          });

          const data = await response.json();

          if (!response.ok) {
              this.editFormErrors = data.errors || {};
              alert(data.message || "Failed to update school year.");
              return;
          }

          const index = this.allSchoolYears.findIndex(
              item => String(item.year_id) === String(data.schoolYear.year_id)
          );

          if (index !== -1) {
              this.allSchoolYears[index] = {
                  ...this.allSchoolYears[index],
                  ...data.schoolYear
              };
          }

          this.showEditForm = false;
          this.successMessage = data.message || "School year updated successfully.";
          this.successModal = true;
      } catch (error) {
          console.error("Update error:", error);
          alert("An error occurred while updating school year.");
      }
  },

    async confirmBulkDelete() {
        const idsToDelete = this.deleteMode === "single" ? [this.deleteId] : this.selectedRows;

        this.bulkDeleteModal = false;

        try {
            const response = await fetch("{{ route('district-admin.school-year.destroy') }}", {
                method: "DELETE",
                headers: {
                    "Content-Type": "application/json",
                    "X-CSRF-TOKEN": "{{ csrf_token() }}",
                    "Accept": "application/json"
                },
                body: JSON.stringify({ ids: idsToDelete })
            });

            const data = await response.json();

            if (data.success) {
                this.allSchoolYears = this.allSchoolYears.filter(
                    item => !idsToDelete.includes(String(item.year_id))
                );

                if (this.page > this.lastPage) {
                    this.page = this.lastPage;
                }

                this.selectedRows = [];
                this.selectAll = false;
                this.deleteMessage = data.message || "School year(s) deleted successfully.";
                this.deleteSuccessModal = true;
                this.deleteMode = null;
                this.deleteId = null;
            } else {
                alert(data.message || "Failed to delete school year(s).");
            }
        } catch (error) {
            console.error("Delete error:", error);
            alert("An error occurred while deleting school year(s).");
        }
    }
}'>
    <div class="overflow-hidden rounded-2xl border border-gray-200 bg-white pt-4 dark:border-white/[0.05] dark:bg-white/[0.03]">

        <div class="mb-4 flex flex-col gap-4 px-6 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h3 class="text-lg font-semibold text-gray-800 dark:text-white/90">
                    School Years
                </h3>
            </div>

            <div class="flex items-center gap-3">
                <template x-if="selectedRows.length > 0">
                    <x-ui.button
                        variant="outline"
                        className="text-red-600 ring-red-300 hover:bg-red-50"
                        @click="triggerBulkDelete()"
                    >
                        Delete Selected
                        (<span x-text="selectedRows.length"></span>)
                    </x-ui.button>
                </template>

                <template x-if="selectedRows.length === 0">
                    <x-ui.button
                        variant="primary"
                        size="md"
                        @click="showAddForm = !showAddForm; showEditForm = false"
                    >
                        <span x-text="showAddForm ? 'Close Form' : 'Add School Year'"></span>
                    </x-ui.button>
                </template>
            </div>
        </div>

        <div x-show="showAddForm" x-transition class="mb-6 px-6">
            <form x-ref="createForm" @submit.prevent="confirmModal = true">
                @csrf

                <x-common.component-card title="Add New School Year">
                    <div>
                        <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">
                            Start Date
                        </label>
                        <input
                            type="text"
                            name="start_date"
                            x-model="createSchoolYear.start_date"
                            x-ref="createStartDate"
                            placeholder="Select start date"
                            required
                            class="dark:bg-dark-900 shadow-theme-xs focus:border-brand-300 focus:ring-brand-500/10 dark:focus:border-brand-800 h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 placeholder:text-gray-400 focus:ring-3 focus:outline-hidden dark:border-gray-700 dark:bg-gray-900 dark:text-white/90 dark:placeholder:text-white/30"
                        />
                        <template x-if="formErrors.start_date">
                            <p class="mt-1 text-sm text-red-600" x-text="formErrors.start_date[0]"></p>
                        </template>
                    </div>

                    <div>
                        <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">
                            End Date
                        </label>
                        <input
                            type="text"
                            name="end_date"
                            x-model="createSchoolYear.end_date"
                            x-ref="createEndDate"
                            placeholder="Select end date"
                            required
                            class="dark:bg-dark-900 shadow-theme-xs focus:border-brand-300 focus:ring-brand-500/10 dark:focus:border-brand-800 h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 placeholder:text-gray-400 focus:ring-3 focus:outline-hidden dark:border-gray-700 dark:bg-gray-900 dark:text-white/90 dark:placeholder:text-white/30"
                        />
                        <template x-if="formErrors.end_date">
                            <p class="mt-1 text-sm text-red-600" x-text="formErrors.end_date[0]"></p>
                        </template>
                    </div>

                    <div class="col-span-full">
                        <div class="mb-3 mt-4 flex items-center justify-between">
                            <h4 class="text-md font-semibold text-gray-800 dark:text-white">
                                Quarters
                            </h4>

                            <x-ui.button type="button" variant="outline" @click="addQuarter()">
                                Add Quarter
                            </x-ui.button>
                        </div>

                        <div class="grid grid-cols-1 gap-4 md:grid-cols-2" x-ref="createQuarterContainer">
                            <template x-for="(quarter, index) in quarters" :key="`${quarter.quarter_number}-${index}`">
                                <div class="rounded-xl border border-gray-200 p-4 dark:border-gray-700">
                                    <div class="mb-3 flex items-center justify-between gap-3">
                                        <h5 class="text-sm font-semibold text-gray-800 dark:text-white" x-text="quarter.quarter_name"></h5>

                                        <button
                                            type="button"
                                            @click="removeQuarter(index)"
                                            :disabled="quarters.length <= 1"
                                            class="text-sm text-red-500 hover:text-red-600 disabled:cursor-not-allowed disabled:opacity-50"
                                        >
                                            Remove
                                        </button>
                                    </div>

                                    <input type="hidden" :name="`quarters[${index}][quarter_id]`" x-model="quarter.quarter_id">
                                    <input type="hidden" :name="`quarters[${index}][quarter_number]`" x-model="quarter.quarter_number">
                                    <input type="hidden" :name="`quarters[${index}][quarter_name]`" x-model="quarter.quarter_name">

                                    <div class="mb-3">
                                        <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">
                                            Quarter Name
                                        </label>
                                        <input
                                            type="text"
                                            :value="quarter.quarter_name"
                                            readonly
                                            class="dark:bg-dark-900 h-11 w-full rounded-lg border border-gray-300 bg-gray-50 px-4 py-2.5 text-sm text-gray-800 dark:border-gray-700 dark:bg-gray-800 dark:text-white/90"
                                        />
                                    </div>

                                    <div class="mb-3">
                                        <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">
                                            Start Date
                                        </label>
                                        <input
                                            type="text"
                                            :name="`quarters[${index}][start_date]`"
                                            x-model="quarter.start_date"
                                            placeholder="Select quarter start date"
                                            class="quarter-start-picker dark:bg-dark-900 h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 focus:outline-hidden dark:border-gray-700 dark:bg-gray-900 dark:text-white/90"
                                        />
                                    </div>

                                    <div>
                                        <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">
                                            End Date
                                        </label>
                                        <input
                                            type="text"
                                            :name="`quarters[${index}][end_date]`"
                                            x-model="quarter.end_date"
                                            placeholder="Select quarter end date"
                                            class="quarter-end-picker dark:bg-dark-900 h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 focus:outline-hidden dark:border-gray-700 dark:bg-gray-900 dark:text-white/90"
                                        />
                                    </div>
                                </div>
                            </template>
                        </div>
                    </div>
                </x-common.component-card>

                <div class="mt-4 flex justify-end">
                    <x-ui.button type="submit" variant="outline">
                        Create School Year
                    </x-ui.button>
                </div>
            </form>
        </div>

        <div x-show="showEditForm" x-transition class="mb-6 px-6">
            <form x-ref="editForm" @submit.prevent="updateSchoolYear()">
                @csrf
                <input type="hidden" name="year_id" x-model="editSchoolYear.year_id">

                <x-common.component-card title="Edit School Year">
                    <div>
                        <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">
                            Start Date
                        </label>
                        <input
                            type="text"
                            name="start_date"
                            x-model="editSchoolYear.start_date"
                            x-ref="editStartDate"
                            placeholder="Select start date"
                            required
                            class="dark:bg-dark-900 shadow-theme-xs focus:border-brand-300 focus:ring-brand-500/10 dark:focus:border-brand-800 h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 placeholder:text-gray-400 focus:ring-3 focus:outline-hidden dark:border-gray-700 dark:bg-gray-900 dark:text-white/90 dark:placeholder:text-white/30"
                        />
                        <template x-if="editFormErrors.start_date">
                            <p class="mt-1 text-sm text-red-600" x-text="editFormErrors.start_date[0]"></p>
                        </template>
                    </div>

                    <div>
                        <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">
                            End Date
                        </label>
                        <input
                            type="text"
                            name="end_date"
                            x-model="editSchoolYear.end_date"
                            x-ref="editEndDate"
                            placeholder="Select end date"
                            required
                            class="dark:bg-dark-900 shadow-theme-xs focus:border-brand-300 focus:ring-brand-500/10 dark:focus:border-brand-800 h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 placeholder:text-gray-400 focus:ring-3 focus:outline-hidden dark:border-gray-700 dark:bg-gray-900 dark:text-white/90 dark:placeholder:text-white/30"
                        />
                        <template x-if="editFormErrors.end_date">
                            <p class="mt-1 text-sm text-red-600" x-text="editFormErrors.end_date[0]"></p>
                        </template>
                    </div>

                    <div class="col-span-full">
                        <div class="mb-3 mt-4 flex items-center justify-between">
                            <h4 class="text-md font-semibold text-gray-800 dark:text-white">
                                Quarters
                            </h4>

                            <x-ui.button type="button" variant="outline" @click="addEditQuarter()">
                                Add Quarter
                            </x-ui.button>
                        </div>

                        <div class="grid grid-cols-1 gap-4 md:grid-cols-2" x-ref="editQuarterContainer">
                            <template x-for="(quarter, index) in editSchoolYear.quarters" :key="`${quarter.quarter_number}-${index}`">
                                <div class="rounded-xl border border-gray-200 p-4 dark:border-gray-700">
                                    <div class="mb-3 flex items-center justify-between gap-3">
                                        <h5 class="text-sm font-semibold text-gray-800 dark:text-white" x-text="quarter.quarter_name"></h5>

                                        <button
                                            type="button"
                                            @click="removeEditQuarter(index)"
                                            :disabled="editSchoolYear.quarters.length <= 1"
                                            class="text-sm text-red-500 hover:text-red-600 disabled:cursor-not-allowed disabled:opacity-50"
                                        >
                                            Remove
                                        </button>
                                    </div>

                                    <input type="hidden" :name="`quarters[${index}][quarter_id]`" x-model="quarter.quarter_id">
                                    <input type="hidden" :name="`quarters[${index}][quarter_number]`" x-model="quarter.quarter_number">
                                    <input type="hidden" :name="`quarters[${index}][quarter_name]`" x-model="quarter.quarter_name">

                                    <div class="mb-3">
                                        <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">
                                            Quarter Name
                                        </label>
                                        <input
                                            type="text"
                                            :value="quarter.quarter_name"
                                            readonly
                                            class="dark:bg-dark-900 h-11 w-full rounded-lg border border-gray-300 bg-gray-50 px-4 py-2.5 text-sm text-gray-800 dark:border-gray-700 dark:bg-gray-800 dark:text-white/90"
                                        />
                                    </div>

                                    <div class="mb-3">
                                        <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">
                                            Start Date
                                        </label>
                                        <input
                                            type="text"
                                            :name="`quarters[${index}][start_date]`"
                                            x-model="quarter.start_date"
                                            placeholder="Select quarter start date"
                                            class="quarter-start-picker dark:bg-dark-900 h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 focus:outline-hidden dark:border-gray-700 dark:bg-gray-900 dark:text-white/90"
                                        />
                                    </div>

                                    <div>
                                        <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">
                                            End Date
                                        </label>
                                        <input
                                            type="text"
                                            :name="`quarters[${index}][end_date]`"
                                            x-model="quarter.end_date"
                                            placeholder="Select quarter end date"
                                            class="quarter-end-picker dark:bg-dark-900 h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 focus:outline-hidden dark:border-gray-700 dark:bg-gray-900 dark:text-white/90"
                                        />
                                    </div>
                                </div>
                            </template>
                        </div>
                    </div>
                </x-common.component-card>

                <div class="mt-4 flex justify-end gap-3">
                    <x-ui.button type="button" variant="outline" @click="showEditForm = false">
                        Cancel
                    </x-ui.button>
                    <x-ui.button type="submit" variant="primary">
                        Update School Year
                    </x-ui.button>
                </div>
            </form>
        </div>

        <div x-show="!showAddForm && !showEditForm" x-transition class="px-6 pb-6">
            <div class="mb-4 flex items-center justify-end">
                <button
                    type="button"
                    @click="handleSelectAll()"
                    class="inline-flex items-center rounded-lg border border-gray-300 px-3 py-2 text-sm text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:text-white dark:hover:bg-gray-800"
                >
                    <span x-text="selectAll ? 'Unselect All' : 'Select All on Page'"></span>
                </button>
            </div>

            <div class="grid grid-cols-1 gap-6 md:grid-cols-2 xl:grid-cols-3">
                <template x-for="schoolYear in paginatedSchoolYears" :key="schoolYear.year_id">
                    <div class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm dark:border-white/[0.05] dark:bg-gray-900">
                        <div class="mb-4 flex items-start justify-between gap-3">
                            <div>
                                <h4
                                    class="text-lg font-semibold text-gray-800 dark:text-white"
                                    x-text="formatSchoolYear(schoolYear.start_date, schoolYear.end_date)"
                                ></h4>
                                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                                    School Year
                                </p>
                            </div>

                            <div
                                @click="handleRowSelect(String(schoolYear.year_id))"
                                class="relative flex h-5 w-5 cursor-pointer items-center justify-center rounded-md border-[1.25px] transition-colors duration-200"
                                :class="selectedRows.includes(String(schoolYear.year_id))
                                    ? 'border-brand-500 bg-brand-500'
                                    : 'border-gray-300 bg-transparent hover:border-brand-500 dark:border-gray-700 dark:hover:border-brand-500'"
                            >
                                <span
                                    :class="selectedRows.includes(String(schoolYear.year_id)) ? '' : 'opacity-0'"
                                    class="flex items-center justify-center"
                                >
                                    <svg width="14" height="14" viewBox="0 0 14 14" fill="none">
                                        <path
                                            d="M11.6666 3.5L5.24992 9.91667L2.33325 7"
                                            stroke="white"
                                            stroke-width="1.94437"
                                            stroke-linecap="round"
                                            stroke-linejoin="round"
                                        />
                                    </svg>
                                </span>
                            </div>
                        </div>

                        <div class="rounded-xl bg-gray-50 p-3 dark:bg-white/[0.03]">
                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <p class="text-xs text-gray-500 dark:text-gray-400">Start Date</p>
                                    <p
                                        class="mt-1 text-sm font-medium text-gray-800 dark:text-white"
                                        x-text="formatDate(schoolYear.start_date)"
                                    ></p>
                                </div>

                                <div>
                                    <p class="text-xs text-gray-500 dark:text-gray-400">End Date</p>
                                    <p
                                        class="mt-1 text-sm font-medium text-gray-800 dark:text-white"
                                        x-text="formatDate(schoolYear.end_date)"
                                    ></p>
                                </div>
                            </div>
                        </div>

                        <template x-if="schoolYear.quarter && schoolYear.quarter.length">
                            <div class="mt-4 space-y-2">
                                <p class="text-sm font-semibold text-gray-800 dark:text-white">Quarters</p>

                                <template x-for="quarter in sortedQuarters(schoolYear.quarter)" :key="quarter.quarter_id">
                                    <div class="rounded-xl bg-gray-50 p-3 dark:bg-white/[0.03]">
                                        <p class="text-sm font-medium text-gray-700 dark:text-white" x-text="quarter.quarter_name"></p>
                                        <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                                            <span x-text="formatDate(quarter.start_date)"></span>
                                            -
                                            <span x-text="formatDate(quarter.end_date)"></span>
                                        </p>
                                    </div>
                                </template>
                            </div>
                        </template>

                        <div class="mt-5 flex items-center justify-end gap-3">
                            <button
                                @click="openEditForm(schoolYear)"
                                class="text-gray-700 hover:text-blue-600 dark:text-gray-400 dark:hover:text-blue-400"
                                title="Edit School Year"
                            >
                                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        stroke-width="2"
                                        d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L12 15l-4 1 1-4 8.586-8.586z"
                                    />
                                </svg>
                            </button>

                            <button
                                @click="deleteRow(String(schoolYear.year_id))"
                                class="text-gray-700 hover:text-red-500 dark:text-gray-400 dark:hover:text-red-400"
                                title="Delete School Year"
                            >
                                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        stroke-width="2"
                                        d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"
                                    />
                                </svg>
                            </button>
                        </div>
                    </div>
                </template>
            </div>

            <template x-if="paginatedSchoolYears.length === 0">
              <div class="flex flex-col items-center justify-center rounded-3xl border border-gray-200 bg-white px-6 py-16 text-center shadow-sm dark:border-white/[0.05] dark:bg-white/[0.03]">
                  <div class="mb-5 flex h-20 w-20 items-center justify-center rounded-2xl bg-brand-50 ring-1 ring-brand-100 dark:bg-brand-500/10 dark:ring-brand-500/20">
                      <svg class="h-10 w-10 text-brand-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                          <path
                              stroke-linecap="round"
                              stroke-linejoin="round"
                              stroke-width="1.8"
                              d="M8 7V3m8 4V3m-9 8h10m-11 10h12a2 2 0 002-2V7a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"
                          />
                      </svg>
                  </div>

                  <div class="max-w-md">
                      <h4 class="text-lg font-semibold text-gray-800 dark:text-white">
                          No school years yet
                      </h4>

                      <p class="mt-2 text-sm leading-6 text-gray-500 dark:text-gray-400">
                          You haven’t added any school year records yet. Start by creating your first school year setup and its quarters.
                      </p>
                  </div>

                  <div class="mt-6 flex items-center gap-2 rounded-full bg-gray-50 px-4 py-2 text-xs font-medium text-gray-500 dark:bg-white/[0.04] dark:text-gray-400">
                      <svg class="h-4 w-4 text-brand-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                          <path
                              stroke-linecap="round"
                              stroke-linejoin="round"
                              stroke-width="2"
                              d="M12 4v16m8-8H4"
                          />
                      </svg>
                      Click “Add School Year” to get started
                  </div>
              </div>
          </template>
        </div>

        <div x-show="!showAddForm && !showEditForm" x-transition class="flex items-center justify-between px-6 pb-6">
            <div class="text-sm text-gray-500 dark:text-gray-400">
                Showing
                <span class="font-medium" x-text="startItem"></span>
                to
                <span class="font-medium" x-text="endItem"></span>
                of
                <span class="font-medium" x-text="total"></span>
                school years
            </div>

            <div class="flex items-center gap-2">
                <button
                    type="button"
                    @click="goToPage(page - 1)"
                    :disabled="page <= 1"
                    class="inline-flex items-center rounded-lg border border-gray-300 px-3 py-2 text-sm text-gray-700 hover:bg-gray-50 disabled:pointer-events-none disabled:border-gray-200 disabled:text-gray-400 dark:border-gray-700 dark:text-white dark:hover:bg-gray-800 dark:disabled:border-gray-800 dark:disabled:text-gray-600"
                >
                    Previous
                </button>

                <template x-for="pageNumber in visiblePages()" :key="pageNumber">
                    <button
                        type="button"
                        @click="goToPage(pageNumber)"
                        class="inline-flex min-w-[40px] items-center justify-center rounded-lg border px-3 py-2 text-sm"
                        :class="pageNumber === page
                            ? 'border-brand-500 bg-brand-500 text-white'
                            : 'border-gray-300 text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:text-white dark:hover:bg-gray-800'"
                        x-text="pageNumber"
                    ></button>
                </template>

                <button
                    type="button"
                    @click="goToPage(page + 1)"
                    :disabled="page >= lastPage"
                    class="inline-flex items-center rounded-lg border border-gray-300 px-3 py-2 text-sm text-gray-700 hover:bg-gray-50 disabled:pointer-events-none disabled:border-gray-200 disabled:text-gray-400 dark:border-gray-700 dark:text-white dark:hover:bg-gray-800 dark:disabled:border-gray-800 dark:disabled:text-gray-600"
                >
                    Next
                </button>
            </div>
        </div>
    </div>

    <div x-show="confirmModal" x-cloak class="fixed inset-0 z-50 flex items-center justify-center bg-black/50">
        <div class="w-full max-w-md rounded-2xl bg-white p-6 dark:bg-gray-900">
            <h3 class="text-lg font-semibold text-gray-800 dark:text-white">
                Confirm School Year Creation
            </h3>
            <p class="mt-2 text-sm text-gray-500">
                Are you sure you want to create this school year?
            </p>
            <div class="mt-6 flex justify-end gap-3">
                <x-ui.button variant="outline" @click="confirmModal = false">
                    Cancel
                </x-ui.button>
                <x-ui.button variant="primary" @click="submitForm()">
                    Yes, Create
                </x-ui.button>
            </div>
        </div>
    </div>

    <div x-show="successModal" x-cloak class="fixed inset-0 z-50 flex items-center justify-center bg-black/50">
        <div class="w-full max-w-md rounded-2xl bg-white p-6 text-center dark:bg-gray-900">
            <div class="mb-4 flex justify-center">
                <div class="flex h-14 w-14 items-center justify-center rounded-full bg-green-100">
                    <svg class="h-8 w-8 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/>
                    </svg>
                </div>
            </div>
            <h3 class="text-lg font-semibold text-gray-800 dark:text-white">Success</h3>
            <p class="mt-2 text-sm text-gray-500" x-text="successMessage"></p>
            <div class="mt-6">
                <x-ui.button variant="primary" @click="successModal = false">
                    Close
                </x-ui.button>
            </div>
        </div>
    </div>

    <div x-show="bulkDeleteModal" x-cloak class="fixed inset-0 z-50 flex items-center justify-center bg-black/50">
        <div class="w-full max-w-md rounded-2xl bg-white p-6 dark:bg-gray-900">
            <h3 class="text-lg font-semibold text-gray-800 dark:text-white">Confirm Delete</h3>
            <p class="mt-2 text-sm text-gray-500">
                <template x-if="deleteMode === 'single'">
                    <span>Are you sure you want to delete this school year?</span>
                </template>
                <template x-if="deleteMode !== 'single'">
                    <span>
                        Are you sure you want to delete
                        <strong x-text="selectedRows.length"></strong>
                        selected school year(s)?
                    </span>
                </template>
            </p>
            <div class="mt-6 flex justify-end gap-3">
                <x-ui.button variant="outline" @click="bulkDeleteModal = false">
                    Cancel
                </x-ui.button>
                <x-ui.button variant="primary" @click="confirmBulkDelete()">
                    Yes, Delete
                </x-ui.button>
            </div>
        </div>
    </div>

    <div x-show="deleteSuccessModal" x-cloak class="fixed inset-0 z-50 flex items-center justify-center bg-black/50">
        <div class="w-full max-w-md rounded-2xl bg-white p-6 text-center dark:bg-gray-900">
            <div class="mb-4 flex justify-center">
                <div class="flex h-14 w-14 items-center justify-center rounded-full bg-green-100">
                    <svg class="h-8 w-8 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/>
                    </svg>
                </div>
            </div>
            <h3 class="text-lg font-semibold text-gray-800 dark:text-white">
                Deleted Successfully
            </h3>
            <p class="mt-2 text-sm text-gray-500" x-text="deleteMessage"></p>
            <div class="mt-6">
                <x-ui.button variant="primary" @click="deleteSuccessModal = false">
                    Close
                </x-ui.button>
            </div>
        </div>
    </div>
</div>

<style>
    [x-cloak] { display: none !important; }
</style>
