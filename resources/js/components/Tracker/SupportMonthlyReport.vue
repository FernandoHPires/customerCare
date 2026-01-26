<template>
    <!-- Month-Year Dropdown -->
    <h5 class="mb-3">Support For Month</h5>
    <div class="mb-3 d-flex align-items-end">
        <div class="me-3">
            <label class="form-label">Select Month</label>
            <select
                :value="selectedMonthYear"
                @input="$emit('update:selectedMonthYear', $event.target.value)"
                class="form-select"
            >
                <option v-for="option in last12Months" :key="option.value" :value="option.value">
                    {{ option.label }}
                </option>
            </select>
        </div>
        <button class="btn btn-primary" @click="$emit('fetch-month-data')">Apply</button>
    </div>

    <!-- Table -->
    <div class="table-responsive">
        <table class="table table-bordered table-striped text-center">
            <thead class="bg-light">
                <tr>
                    <th>Support</th>
                    <th v-for="day in daysInMonth" :key="day">{{ day }}</th>
                    <th>Totals</th>
                </tr>
            </thead>
            <tbody>
                <tr v-for="(row, index) in monthlySupportRows" :key="index">
                    <td>{{ row.support }}</td>
                    <td
                        v-for="day in daysInMonth"
                        :key="day"
                        :style="row.daily[day] > 0 ? { backgroundColor: getShade(row.daily[day], monthlyMin, monthlyMax) } : {}"
                    >
                        {{ row.daily[day] > 0 ? row.daily[day] : '' }}
                    </td>
                    <td :style="{ backgroundColor: getShade(row.total, monthlyTotalMin, monthlyTotalMax) }">
                        {{ row.total }}
                    </td>
                </tr>

                <tr class="fw-bold">
                    <td>Totals</td>
                    <td v-for="day in daysInMonth" :key="day">
                        {{ monthlyTotals[day] > 0 ? monthlyTotals[day] : '' }}
                    </td>
                    <td>{{ monthlyGrandTotal }}</td>
                </tr>
            </tbody>
        </table>
    </div>

    <div class="mt-2 small text-muted">
        {{ monthlyGrandTotal }} orders
        min: {{ monthlyMin }} |
        max: {{ monthlyMax }} |
        daily avg: {{ monthlyAverage }}
    </div>
</template>

<script>
export default {
    name: "SupportMonthReport",
    props: {
        selectedMonthYear: String,
        last12Months: Array,
        daysInMonth: Array,
        monthlySupportRows: Array,
        monthlyTotals: Object,
        monthlyGrandTotal: Number,
        monthlyMin: Number,
        monthlyMax: Number,
        monthlyAverage: Number,
        monthlyTotalMin: Number,
        monthlyTotalMax: Number,
    },
    emits: ["update:selectedMonthYear", "fetch-month-data"],
    methods: {
        fetchSupportMonthReport() {
            this.$emit("fetch-month-data");
        },
        hexToRgb(hex) {
            hex = hex.replace(/^#/, "");
            const bigint = parseInt(hex, 16);
            return [
            (bigint >> 16) & 255,
            (bigint >> 8) & 255,
            bigint & 255,
            ];
        },
        rgbToHex(rgb) {
            return `#${rgb.map(c => c.toString(16).padStart(2, '0')).join('')}`;
        },
        getColorGradient(startHex, endHex, ratio) {
            const startRGB = this.hexToRgb(startHex);
            const endRGB = this.hexToRgb(endHex);
            const r = Math.round(startRGB[0] + ratio * (endRGB[0] - startRGB[0]));
            const g = Math.round(startRGB[1] + ratio * (endRGB[1] - startRGB[1]));
            const b = Math.round(startRGB[2] + ratio * (endRGB[2] - startRGB[2]));
            return this.rgbToHex([r, g, b]);
        },
        getShade(value, min = this.monthlyMin, max = this.monthlyMax) {
            if (max === min) return "#ffffff";
            const ratio = (value - min) / (max - min);
            return this.getColorGradient("#e0f5e0", "#339933", ratio);
        },
    },
};
</script>