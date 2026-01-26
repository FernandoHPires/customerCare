<template>
    <ReportFavourite :filteredReports="this.filteredReports" />

    <div class="card my-4 all-reports">
        <div class="card-header border-bottom d-flex align-items-center">
            <div class="card-title my-2">
                <h4 class="m-0">
                    All Reports <i class="bi bi-file-bar-graph"></i>
                </h4>
            </div>
            <div class="ms-auto">
                <div class="input-group pe-3">
                    <span class="input-group-text"
                        ><i class="bi-search"></i></span
                    ><input
                        type="text"
                        class="form-control"
                        placeholder="Search Reports"
                        v-model="inputValue"
                    />
                </div>
            </div>
        </div>
        <div
            class="card-body d-flex gap-2"
            v-if="Object.keys(filteredData).length !== 0"
        >
            <div
                v-for="(sections, departmentName) in filteredData"
                class="mb-1"
                :key="departmentName"
            >
                <div class="card-department">
                    <h4 class="card-title pb-3">{{ departmentName }}</h4>

                    <template
                        v-for="(section, sectionName) in sections"
                        :key="sectionName"
                    >
                        <ul
                            class="list-group list-group-report mb-2"
                            v-if="Object.keys(section).length !== 0"
                        >
                            <li class="list-unstyled">
                                <strong class="ms-3">{{ sectionName }}</strong>

                                <ul class="list-group report-list">
                                    <li class="list-group-item">
                                        <template
                                            v-for="(report, key) in section"
                                            :key="key"
                                        >
                                            <h6
                                                class="my-2"
                                                v-if="
                                                    report !== undefined &&
                                                    Object.keys(report)
                                                        .length !== 0
                                                "
                                            >
                                                <RouterLink
                                                    class="text-decor-none text-primary-blue"
                                                    :to="`/reports/${report.route}`"
                                                    >{{ report.name }}
                                                </RouterLink>

                                                <a
                                                    class="text-primary-blue"
                                                    href="#"
                                                    @click="
                                                        toggleFavourite(report)
                                                    "
                                                >
                                                    <i
                                                        v-bind:class="[
                                                            report.favorite
                                                                ? 'bi bi-star-fill'
                                                                : 'bi bi-star',
                                                            'ms-1',
                                                        ]"
                                                    ></i>
                                                </a>
                                            </h6>
                                        </template>
                                    </li>
                                </ul>
                            </li>
                        </ul>
                    </template>
                </div>
            </div>
        </div>
    </div>
</template>

<script>
import { util } from "../mixins/util";
import ReportFavourite from "../components/reports/ReportFavourite.vue";
import { RouterLink } from "vue-router";

export default {
    mixins: [util],
    emits: ["events"],
    data() {
        return {
            inputValue: "",
            allReports: [],
            filteredReports: [],
        };
    },
    components: {
        ReportFavourite,
        RouterLink,
    },
    computed: {
        filteredData() {
            let search = this.inputValue && this.inputValue.toLowerCase();
            let allReports = JSON.parse(JSON.stringify(this.allReports));
            let filteredReports = [];

            for (const department in allReports) {
                for (const section in allReports[department]) {
                    for (const report in allReports[department][section]) {
                        if (allReports[department][section][report].favorite) {
                            filteredReports.push(
                                allReports[department][section][report]
                            );
                        }

                        if (
                            !(
                                String(
                                    allReports[department][section][report].name
                                )
                                    .toLowerCase()
                                    .indexOf(search) > -1
                            )
                        ) {
                            delete allReports[department][section][report];
                        }
                    }
                }
            }

            this.filteredReports = filteredReports;

            return allReports;
        },
    },
    mounted() {
        this.getData();
    },

    methods: {
        toggleFavourite: function (report) {
            this.axios
                .post(`web/reports/favourites/${report.id}`)
                .then((response) => {
                    this.alertMessage = response.data.message;
                    this.showAlert(response.data.status);

                    if (this.checkApiResponse(response)) {
                        this.getData();
                    }
                })
                .catch((error) => {
                    this.alertMessage = error;
                    this.showAlert("error");
                })
                .finally(() => {
                    this.hidePreLoader();
                });
        },
        getData: function () {
            this.showPreLoader();
            this.axios
                .get("web/reports")
                .then((response) => {
                    if (this.checkApiResponse(response)) {
                        this.allReports = response.data.data;
                        //console.log(this.allReports);
                    } else {
                        this.alertMessage = response.data.message;
                        this.showAlert(response.data.status);
                    }
                })
                .catch((error) => {
                    this.alertMessage = error;
                    this.showAlert("error");
                })
                .finally(() => {
                    this.hidePreLoader();
                });
        },
    },
};
</script>

<style scoped>
.card-link + .card-link {
    margin-left: 0 !important;
    margin-top: 0.5rem;
}
</style>
