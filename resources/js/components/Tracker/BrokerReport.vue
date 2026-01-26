<template>
  <div>
    <!-- Calendar Filters -->
    <div class="d-flex mb-3">
      <div class="me-3">
        <label for="startDate" class="form-label">Start Date</label>
        <input
          type="date"
          id="startDate"
          v-model="filters.startDate"
          class="form-control"
        />
      </div>
      <div>
        <label for="endDate" class="form-label">End Date</label>
        <input
          type="date"
          id="endDate"
          v-model="filters.endDate"
          class="form-control"
        />
      </div>
      <button
        class="btn btn-primary ms-3 align-self-end"
        @click="$emit('apply')"
      >
        Apply
      </button>
    </div>

    <!-- Report Table -->
    <table class="table table-bordered table-striped text-center">
      <thead class="bg-light">
        <tr>
          <th>Brokers</th>
          <th>Title Docs</th>
          <th>Suitability</th>
          <th>Disbursement PA</th>
          <th>Transfer</th>
          <th>Funding NB</th>
          <th>Funding PB</th>
          <th>Transmittal Letter</th>         
        </tr>
      </thead>

      <tbody v-if="reportData.length > 0">
        <tr v-for="(row, index) in reportData" :key="index">
          <td>{{ row.broker }}</td>
          <td :style="row.titleDocs > 0 ? { backgroundColor: getShade(row.titleDocs, columnMins.titleDocs, columnMaxs.titleDocs) } : {}">
            {{ row.titleDocs > 0 ? row.titleDocs : '' }}
          </td>
          <td :style="row.suitability > 0 ? { backgroundColor: getShade(row.suitability, columnMins.suitability, columnMaxs.suitability) } : {}">
            {{ row.suitability > 0 ? row.suitability : '' }}
          </td>
          <td :style="row.disbursementPA > 0 ? { backgroundColor: getShade(row.disbursementPA, columnMins.disbursementPA, columnMaxs.disbursementPA) } : {}">
            {{ row.disbursementPA > 0 ? row.disbursementPA : '' }}
          </td>
          <td :style="row.transfer > 0 ? { backgroundColor: getShade(row.transfer, columnMins.transfer, columnMaxs.transfer) } : {}">
            {{ row.transfer > 0 ? row.transfer : '' }}
          </td>
          <td :style="row.fundingNB > 0 ? { backgroundColor: getShade(row.fundingNB, columnMins.fundingNB, columnMaxs.fundingNB) } : {}">
            {{ row.fundingNB > 0 ? row.fundingNB : '' }}
          </td>
          <td :style="row.fundingPB > 0 ? { backgroundColor: getShade(row.fundingPB, columnMins.fundingPB, columnMaxs.fundingPB) } : {}">
            {{ row.fundingPB > 0 ? row.fundingPB : '' }}
          </td>
          <td :style="row.transmittalLetter > 0 ? { backgroundColor: getShade(row.transmittalLetter, columnMins.transmittalLetter, columnMaxs.transmittalLetter) } : {}">
            {{ row.transmittalLetter > 0 ? row.transmittalLetter : '' }}
          </td>
        </tr>


        <tr class="fw-bold">
          <td>{{ reportTotals.broker }}</td>
          <td>{{ reportTotals.titleDocs }}</td>
          <td>{{ reportTotals.suitability }}</td>
          <td>{{ reportTotals.disbursementPA }}</td>
          <td>{{ reportTotals.transfer }}</td>          
          <td>{{ reportTotals.fundingNB }}</td>
          <td>{{ reportTotals.fundingPB }}</td>
          <td>{{ reportTotals.transmittalLetter }}</td>

        </tr>

      </tbody>

      <tbody v-else>
        <tr>
          <td colspan="11" class="text-center">No data available</td>
        </tr>
      </tbody>
    </table>
  </div>
</template>
  
<script>
export default {
  name: "BrokerReport",
  props: {
    reportData: Array,
    reportTotals: Object,
    filters: Object,
    fetchReportData: Function,
    columnMins: Object,
    columnMaxs: Object, 
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
      getShade(value, min, max) {
        if (max === min) return "#ffffff";
        const ratio = (value - min) / (max - min);
        return this.getColorGradient("#e0f5e0", "#339933", ratio);
      }
  },
};
</script>  