<template>
    <div>
      <h5 class="mb-3">Support Fundings per Week</h5>
      <div class="table-responsive">
        <table class="table table-bordered table-striped text-center">
          <thead class="bg-light">
            <tr>
              <th>Support</th>
              <th>Type</th>
              <th v-for="week in weeks" :key="week">{{ week }}</th>
              <th>Totals</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="(row, index) in weeklyReportRows" :key="index">
              <td>{{ row.support }}</td>
              <td>{{ row.doc_type }}</td>
              <td
                v-for="week in weeks"
                :key="week"
                :style="row.weekly[week] > 0 ? { backgroundColor: getShade(row.weekly[week], minCount, maxCount) } : {}"
                >
                {{ row.weekly[week] > 0 ? row.weekly[week] : '' }}
              </td>
              <td :style="{ backgroundColor: getShade(row.total, totalMin, totalMax) }">
                {{ row.total }}
              </td>
            </tr>
            <tr class="fw-bold">
              <td colspan="2">Totals</td>
              <td v-for="week in weeks" :key="week">
              {{ weeklyTotals[week] > 0 ? weeklyTotals[week] : '' }}
            </td>
              <td>{{ grandTotal }}</td>
            </tr>
          </tbody>
        </table>
      </div>
      <div class="mt-2 small text-muted">
        {{ grandTotal }} fundings
        min: {{ minCount }} |
        max: {{ maxCount }} |
        weekly avg: {{ averageCount }}
      </div>
    </div>
  </template>
  
  <script>
  export default {
    name: "SupportWeeklyReport",
    props: {
      weeks: Array,
      weeklyReportRows: Array,
      weeklyTotals: Object,
      grandTotal: Number,
      minCount: Number,
      maxCount: Number,
      averageCount: Number,
      totalMin: Number,
      totalMax: Number,  
    },
    methods: {
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
        getShade(value, min = this.minCount, max = this.maxCount) {
            if (max === min) return "#ffffff";
            const ratio = (value - min) / (max - min);
            return this.getColorGradient("#e0f5e0", "#339933", ratio);
        }
    }
  };
  </script>  