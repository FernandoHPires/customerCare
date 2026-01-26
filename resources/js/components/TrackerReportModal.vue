<template>
    <div class="px-3 py-2">
        <!-- Tabs -->
        <ul class="nav nav-tabs mb-3">
            <li class="nav-item">
                <a
                    class="nav-link"
                    :class="{ active: selectedTab === 'brokers' }"
                    href="#"
                    @click.prevent="selectedTab = 'brokers'"
                >
                    Brokers
                </a>
            </li>
            <li class="nav-item">
                <a
                    class="nav-link"
                    :class="{ active: selectedTab === 'supportMonth' }"
                    href="#"
                    @click.prevent="selectedTab = 'supportMonth'"
                >
                    Support For Month
                </a>
            </li>
            <li class="nav-item">
                <a
                    class="nav-link"
                    :class="{ active: selectedTab === 'fundingsWeek' }"
                    href="#"
                    @click.prevent="selectedTab = 'fundingsWeek'"
                >
                    Support Fundings per Week
                </a>
            </li>
            <li class="nav-item">
                <a
                    class="nav-link"
                    :class="{ active: selectedTab === 'fundingsDay' }"
                    href="#"
                    @click.prevent="selectedTab = 'fundingsDay'"
                >
                    Fundings per Day
                </a>
            </li>
        </ul>

        <!-- Brokers Tab -->
        <div v-if="selectedTab === 'brokers'">
            <BrokerReport
                :report-data="reportData"
                :report-totals="reportTotals"
                :column-mins="columnMins"
                :column-maxs="columnMaxs"
                :filters="filters"
                @apply="fetchReportData"
            />
        </div>
        <!-- Support For Month Tab -->
        <div v-if="selectedTab === 'supportMonth'">
            <SupportMonthlyReport
                v-model:selectedMonthYear="selectedMonthYear"
                :last12Months="last12Months"
                :daysInMonth="daysInMonth"
                :monthlySupportRows="monthlySupportRows"
                :monthlyTotals="monthlyTotals"
                :monthlyGrandTotal="monthlyGrandTotal"
                :monthlyMin="monthlyMin"
                :monthlyMax="monthlyMax"
                :monthlyAverage="monthlyAverage"
                :monthly-total-min="monthlyTotalMin"
                :monthly-total-max="monthlyTotalMax"
                @fetch-month-data="fetchSupportMonthReport"
            />
            <hr class="my-4" />
            <SupportWeeklyReport
                :weeks="weeks"
                :weeklyReportRows="weeklyReportRows"
                :weeklyTotals="weeklyTotals"
                :grandTotal="grandTotal"
                :minCount="minCount"
                :maxCount="maxCount"
                :averageCount="averageCount"
                :totalMin="totalMin"
                :totalMax="totalMax"
            />
        </div>

        <!-- Support Fundings per Week Tab -->
        <div v-if="selectedTab === 'fundingsWeek'">
            <SupportWeeklyReport
                :weeks="weeks"
                :weeklyReportRows="weeklyReportRows"
                :weeklyTotals="weeklyTotals"
                :grandTotal="grandTotal"
                :minCount="minCount"
                :maxCount="maxCount"
                :averageCount="averageCount"
                :totalMin="totalMin"
                :totalMax="totalMax"
            />
            <hr class="my-4" />
            <SupportMonthlyReport
                v-model:selectedMonthYear="selectedMonthYear"
                :last12Months="last12Months"
                :daysInMonth="daysInMonth"
                :monthlySupportRows="monthlySupportRows"
                :monthlyTotals="monthlyTotals"
                :monthlyGrandTotal="monthlyGrandTotal"
                :monthlyMin="monthlyMin"
                :monthlyMax="monthlyMax"
                :monthlyAverage="monthlyAverage"
                :monthly-total-min="monthlyTotalMin"
                :monthly-total-max="monthlyTotalMax"
                @fetch-month-data="fetchSupportMonthReport"
            />
        </div>

        <!-- Support Fundings per Day Tab -->
        <div v-if="selectedTab === 'fundingsDay'">
            <SupportDailyReport
                :days="daysInMonth"
                :dailyReportRows="dailyReportRows"
                :dailyTotals="dailyTotals"
                :grandTotal="dailyGrandTotal"
                :minCount="dailyMin"
                :maxCount="dailyMax"
                :averageCount="dailyAverage"
                :totalMin="dailyTotalMin"
                :totalMax="dailyTotalMax"
                v-model:selectedMonthYear="selectedMonthYear"
                :last12Months="last12Months"
                @fetch-day-data="fetchDayReport"
            />
            <hr class="my-4" />
            <SupportWeeklyReport
                :weeks="weeks"
                :weeklyReportRows="weeklyReportRows"
                :weeklyTotals="weeklyTotals"
                :grandTotal="grandTotal"
                :minCount="minCount"
                :maxCount="maxCount"
                :averageCount="averageCount"
                :totalMin="totalMin"
                :totalMax="totalMax"
            />
        </div>
    </div>
</template>

<script>
import { util } from "../mixins/util";
import BrokerReport from "../components/Tracker/BrokerReport.vue";
import SupportMonthlyReport from "../components/Tracker/SupportMonthlyReport.vue";
import SupportWeeklyReport from "../components/Tracker/SupportWeeklyReport.vue";
import SupportDailyReport from "../components/Tracker/SupportDailyReport.vue";

export default {
    mixins: [util],
    name: "ReportModal",
    components: {
        BrokerReport,
        SupportMonthlyReport,
        SupportWeeklyReport,
        SupportDailyReport,
    },
    props: {
        dataល: Array,
        currentUser: Object,
    },
    emits: ["close", "apply-filters"],
    data() {
        return {
            selectedTab: "brokers", // Demand tab
            weeklyReportRaw: [], // raw data from backend
            weeks: [], // dynamic week list
            weeklyReportRows: [], // transformed data ready for table
            reportData: [],
            filters: {
                startDate: "",
                endDate: "",
            },
            selectedMonthYear: "",
            last12Months: [],
            monthlySupportRows: [],
            dailyReportRows: [], // Renamed from daySupportRows for consistency
            daysInMonth: [],
            monthlyTotals: {},
            monthlyGrandTotal: 0,
            monthlyMin: 0,
            monthlyMax: 0,
            monthlyAverage: 0,
            dailyTotals: {}, // Added for daily report
            dailyGrandTotal: 0, // Added for daily report
            dailyMin: 0, // Added for daily report
            dailyMax: 0, // Added for daily report
            dailyAverage: 0, // Added for daily report
            dailyTotalMin: 0, // Added for daily report
            dailyTotalMax: 0, // Added for daily report
        };
    },
    mounted() {
        this.generateLast12Months();
    },
    computed: {
        weeklyTotals() {
            const totals = {};
            this.weeklyReportRows.forEach((row) => {
                Object.entries(row.weekly).forEach(([week, count]) => {
                    if (!totals[week]) totals[week] = 0;
                    totals[week] += count;
                });
            });
            return totals;
        },
        grandTotal() {
            return this.weeklyReportRows.reduce((sum, row) => sum + row.total, 0);
        },
        minCount() {
            const allCounts = this.weeklyReportRows.flatMap((row) =>
                Object.values(row.weekly).filter((val) => val > 0)
            );
            return allCounts.length ? Math.min(...allCounts) : 0;
        },
        maxCount() {
            const allCounts = this.weeklyReportRows.flatMap((row) =>
                Object.values(row.weekly).filter((val) => val > 0)
            );
            return allCounts.length ? Math.max(...allCounts) : 0;
        },
        totalMin() {
            const totals = this.weeklyReportRows
                .map((row) => row.total)
                .filter((val) => val > 0);
            return totals.length ? Math.min(...totals) : 0;
        },
        totalMax() {
            const totals = this.weeklyReportRows
                .map((row) => row.total)
                .filter((val) => val > 0);
            return totals.length ? Math.max(...totals) : 0;
        },
        averageCount() {
            const allCounts = this.weeklyReportRows.flatMap((row) =>
                Object.values(row.weekly).filter((val) => val > 0)
            );
            return allCounts.length
                ? Math.round(allCounts.reduce((a, b) => a + b, 0) / allCounts.length)
                : 0;
        },
        dailyTotals() {
            const totals = {};
            this.dailyReportRows.forEach((row) => {
                Object.entries(row.daily).forEach(([day, count]) => {
                    if (!totals[day]) totals[day] = 0;
                    totals[day] += count;
                });
            });
            return totals;
        },
        dailyGrandTotal() {
            return this.dailyReportRows.reduce((sum, row) => sum + row.total, 0);
        },
        dailyMin() {
            const allCounts = this.dailyReportRows.flatMap((row) =>
                Object.values(row.daily).filter((val) => val > 0)
            );
            return allCounts.length ? Math.min(...allCounts) : 0;
        },
        dailyMax() {
            const allCounts = this.dailyReportRows.flatMap((row) =>
                Object.values(row.daily).filter((val) => val > 0)
            );
            return allCounts.length ? Math.max(...allCounts) : 0;
        },
        dailyTotalMin() {
            const totals = this.dailyReportRows
                .map((row) => row.total)
                .filter((val) => val > 0);
            return totals.length ? Math.min(...totals) : 0;
        },
        dailyTotalMax() {
            const totals = this.dailyReportRows
                .map((row) => row.total)
                .filter((val) => val > 0);
            return totals.length ? Math.max(...totals) : 0;
        },
        dailyAverage() {
            const allCounts = this.dailyReportRows.flatMap((row) =>
                Object.values(row.daily).filter((val) => val > 0)
            );
            return allCounts.length
                ? Math.round(allCounts.reduce((a, b) => a + b, 0) / allCounts.length)
                : 0;
        },
        reportTotals() {
            const totals = {
                broker: "Totals",
                titleDocs: 0,
                transmittalLetter: 0,
                instructionNB: 0,
                instructionPB: 0,
                transfer: 0,
                fundingNB: 0,
                fundingPB: 0,
                creditBureau: 0,
                initialDocsNB: 0,
                initialDocsPB: 0,
                suitability: 0,
                disbursementPA: 0,

            };

            this.reportData.forEach((row) => {
                totals.titleDocs += parseInt(row.titleDocs || 0, 10);
                totals.transmittalLetter += parseInt(row.transmittalLetter || 0, 10);
                totals.instructionNB += parseInt(row.instructionNB || 0, 10);
                totals.instructionPB += parseInt(row.instructionPB || 0, 10);
                totals.transfer += parseInt(row.transfer || 0, 10);
                totals.fundingNB += parseInt(row.fundingNB || 0, 10);
                totals.fundingPB += parseInt(row.fundingPB || 0, 10);
                totals.creditBureau += parseInt(row.creditBureau || 0, 10);
                totals.initialDocsNB += parseInt(row.initialDocsNB || 0, 10);
                totals.initialDocsPB += parseInt(row.initialDocsPB || 0, 10);
                totals.suitability += parseInt(row.suitability || 0, 10);
                totals.disbursementPA += parseInt(row.disbursementPA || 0, 10);
            });

            return totals;
        },
        monthlyTotalMin() {
            const totals = this.monthlySupportRows
                .map((row) => row.total)
                .filter((val) => val > 0);
            return totals.length ? Math.min(...totals) : 0;
        },
        monthlyTotalMax() {
            const totals = this.monthlySupportRows
                .map((row) => row.total)
                .filter((val) => val > 0);
            return totals.length ? Math.max(...totals) : 0;
        },
        columnMins() {
            const fields = [
                "titleDocs",
                "transmittalLetter",
                "transfer",
                "fundingNB",
                "fundingPB",
                "suitability",
                "disbursementPA",
            ];
            const mins = {};
            fields.forEach((field) => {
                const values = this.reportData.map((row) => parseInt(row[field] || 0, 10));
                mins[field] = values.length ? Math.min(...values) : 0;
            });
            return mins;
        },
        columnMaxs() {
            const fields = [
                "titleDocs",
                "transmittalLetter",                
                "transfer",
                "fundingNB",
                "fundingPB",
                "suitability",
                "disbursementPA",
            ];
            const maxs = {};
            fields.forEach((field) => {
                const values = this.reportData.map((row) => parseInt(row[field] || 0, 10));
                maxs[field] = values.length ? Math.max(...values) : 0;
            });
            return maxs;
        },
    },
    watch: {
        selectedTab(val) {
            if (val === "supportMonth" || val === "fundingsWeek" || val === "fundingsDay") {
                if (!this.weeklyReportRows.length) {
                    this.fetchSupportWeeklyReport();
                }
                if (!this.monthlySupportRows.length) {
                    this.fetchSupportMonthReport();
                }
                if (!this.dailyReportRows.length && this.selectedMonthYear) {
                    this.fetchDayReport();
                }
            }
        },
        selectedMonthYear(newValue) {
            console.log('selectedMonthYear updated to:', newValue);
            if (this.selectedTab === "fundingsDay") {
                this.fetchDayReport();
            }
        },
    },
    methods: {
        fetchReportData() {
            const { startDate, endDate } = this.filters;

            this.showPreLoader();

            this.axios
                .get("/web/tracker/report", {
                    params: { startDate, endDate, companyId: this.currentUser.companyId },
                })
                .then((response) => {
                    if (this.checkApiResponse(response)) {
                        let allData = response.data.data || [];

                        // Ensure companyId exists in all data rows
                        allData = allData.map((row) => ({
                            ...row,
                            companyId: row.companyId || 0, // Set to 0 if missing
                        }));
                        // Apply company filtering logic
                        if (this.currentUser.companyId === 701) {
                            // Show only brokers with companyId 701
                            this.reportData = allData.filter((row) => row.companyId === 701);
                        } else {
                            // Show all brokers except those with companyId 701
                            this.reportData = allData.filter((row) => row.companyId !== 701);
                        }
                    }
                })
                .catch((error) => {
                    console.error("Error fetching report data:", error);
                })
                .finally(() => {
                    this.hidePreLoader();
                });
        },

        fetchSupportMonthReport() {
            this.showPreLoader();
            const [year, month] = this.selectedMonthYear.split("-").map(Number);
            const params = { year, month };

            this.axios
                .get("/tracker/support-report-per-month", { params })
                .then((response) => {
                    if (this.checkApiResponse(response)) {
                        const rawData = response.data.data || [];
                        const days = new Date(year, month, 0).getDate();
                        this.daysInMonth = [];
                        for (let i = 1; i <= days; i++) {
                            this.daysInMonth.push(i);
                        }

                        const grouped = {};
                        rawData.forEach((item) => {
                            const key = `${item.supportId}`;
                            const day = new Date(item.supportDate).getDate();
                            if (!grouped[key]) {
                                grouped[key] = {
                                    support: item.support,
                                    supportId: item.supportId,
                                    daily: {},
                                    total: 0,
                                };
                            }
                            grouped[key].daily[day] = (grouped[key].daily[day] || 0) + item.count;
                            grouped[key].total += item.count;
                        });

                        this.monthlySupportRows = Object.values(grouped);
                        this.monthlyTotals = {};
                        this.monthlyGrandTotal = 0;
                        this.monthlyMin = Infinity;
                        this.monthlyMax = 0;

                        this.daysInMonth.forEach((day) => {
                            let dayTotal = 0;
                            this.monthlySupportRows.forEach((row) => {
                                dayTotal += row.daily[day] || 0;
                            });
                            this.monthlyTotals[day] = dayTotal;
                            this.monthlyGrandTotal += dayTotal;
                        });

                        // Min and Max from all row.daily[day] values
                        const allDailyCounts = [];
                        this.monthlySupportRows.forEach((row) => {
                            this.daysInMonth.forEach((day) => {
                                const val = row.daily[day] || 0;
                                if (val > 0) {
                                    allDailyCounts.push(val);
                                }
                            });
                        });

                        this.monthlyMax = allDailyCounts.length ? Math.max(...allDailyCounts) : 0;
                        this.monthlyMin = allDailyCounts.length ? Math.min(...allDailyCounts) : 0;

                        this.monthlyAverage = Math.round(this.monthlyGrandTotal / this.daysInMonth.length);
                    }
                })
                .catch((error) => {
                    console.error("Error fetching monthly support report:", error);
                })
                .finally(() => {
                    this.hidePreLoader();
                });
        },

        fetchSupportWeeklyReport() {
            this.showPreLoader();
            this.axios
                .get("/tracker/support-report-per-week")
                .then((response) => {
                    if (this.checkApiResponse(response)) {
                        const rawData = response.data.data || [];
                        this.weeklyReportRaw = rawData;

                        if (rawData.length === 0) {
                            this.weeks = [];
                            this.weeklyReportRows = [];
                            return;
                        }

                        const startDateStr = rawData[0].startDate;
                        const startDate = new Date(startDateStr);
                        startDate.setDate(startDate.getDate() - startDate.getDay() + 1);
                        // Generate correct week numbers for the last 10 weeks
                        const getWeekNumber = (date) => {
                            const d = new Date(Date.UTC(date.getFullYear(), date.getMonth(), date.getDate()));
                            const dayNum = d.getUTCDay() || 7;
                            d.setUTCDate(d.getUTCDate() + 4 - dayNum);
                            const yearStart = new Date(Date.UTC(d.getUTCFullYear(), 0, 1));
                            return Math.ceil((((d - yearStart) / 86400000) + 1) / 7);
                        };

                        const weeks = [];
                        const mondayDates = [];

                        for (let i = 9; i >= 0; i--) {
                            const monday = new Date(startDate);
                            monday.setDate(startDate.getDate() - i * 7);
                            mondayDates.push(monday);
                            weeks.push(String(getWeekNumber(monday)));
                        }

                        this.weeks = weeks;

                        // Group and format data
                        const grouped = {};

                        rawData.forEach((item) => {
                            const key = `${item.supportId}__${item.docType}`;
                            if (!grouped[key]) {
                                grouped[key] = {
                                    support: item.support,
                                    docType: item.docType,
                                    weekly: {},
                                    total: 0,
                                };
                            }

                            grouped[key].weekly[String(item.weekNum)] = item.count;
                            grouped[key].total += item.count;
                        });

                        // Ensure all weeks exist in each row (fill with 0)
                        Object.values(grouped).forEach((row) => {
                            weeks.forEach((week) => {
                                if (!row.weekly[week]) {
                                    row.weekly[week] = 0;
                                }
                            });
                        });
                        this.weeklyReportRows = Object.values(grouped);
                    }
                })
                .catch((error) => {
                    console.error("Error fetching support weekly report:", error);
                })
                .finally(() => {
                    this.hidePreLoader();
                });
        },

        fetchDayReport() {
            console.log('Fetching day report with selectedMonthYear:', this.selectedMonthYear);
            this.showPreLoader();
            const [year, month] = this.selectedMonthYear.split("-").map(Number);
            const params = { year, month, docTypes: ["fundingNB", "fundingPB"] };

            this.axios
                .get("/tracker/support-report-per-day", { params })
                .then((response) => {
                    if (this.checkApiResponse(response)) {
                        const rawData = response.data.data || [];
                        const days = new Date(year, month, 0).getDate();
                        this.daysInMonth = [];
                        for (let i = 1; i <= days; i++) {
                            this.daysInMonth.push(i);
                        }

                        const grouped = {};
                        rawData.forEach((item) => {
                            const key = `${item.supportId}`;
                            const day = new Date(item.supportDate).getDate();
                            if (!grouped[key]) {
                                grouped[key] = {
                                    support: item.support,
                                    supportId: item.supportId,
                                    daily: {},
                                    total: 0,
                                };
                            }
                            grouped[key].daily[day] = (grouped[key].daily[day] || 0) + item.count;
                            grouped[key].total += item.count;
                        });

                        this.dailyReportRows = Object.values(grouped);
                        this.dailyTotals = {};
                        this.dailyGrandTotal = 0;
                        this.dailyMin = Infinity;
                        this.dailyMax = 0;

                        this.daysInMonth.forEach((day) => {
                            let dayTotal = 0;
                            this.dailyReportRows.forEach((row) => {
                                dayTotal += row.daily[day] || 0;
                            });
                            this.dailyTotals[day] = dayTotal;
                            this.dailyGrandTotal += dayTotal;
                        });

                        // Min and Max from all row.daily[day] values
                        const allDailyCounts = [];
                        this.dailyReportRows.forEach((row) => {
                            this.daysInMonth.forEach((day) => {
                                const val = row.daily[day] || 0;
                                if (val > 0) {
                                    allDailyCounts.push(val);
                                }
                            });
                        });

                        this.dailyMax = allDailyCounts.length ? Math.max(...allDailyCounts) : 0;
                        this.dailyMin = allDailyCounts.length ? Math.min(...allDailyCounts) : 0;

                        this.dailyAverage = Math.round(this.dailyGrandTotal / this.daysInMonth.length);
                    }
                })
                .catch((error) => {
                    console.error("Error fetching daily funding report:", error);
                })
                .finally(() => {
                    this.hidePreLoader();
                });
        },

        generateLast12Months() {
            const months = [
                "January",
                "February",
                "March",
                "April",
                "May",
                "June",
                "July",
                "August",
                "September",
                "October",
                "November",
                "December",
            ];

            const today = new Date();
            const options = [];

            for (let i = 0; i < 12; i++) {
                const date = new Date(today.getFullYear(), today.getMonth() - i, 1);
                const month = date.getMonth();
                const year = date.getFullYear();

                options.push({
                    label: `${months[month]}/${year}`,
                    value: `${year}-${String(month + 1).padStart(2, "0")}`,
                });
                console.log('Generated month option:', options[options.length - 1]);
            }

            this.last12Months = options;
            this.selectedMonthYear = options[0].value; // default to current month
            console.log('Initial selectedMonthYear:', this.selectedMonthYear);
        },
    },
};
</script>