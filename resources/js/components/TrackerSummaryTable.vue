<template>
    <div v-if="type === 'gross'" :class="`col-md-${colSize}`">
        <div class="card shadow-sm">
            <div class="card-header bg-light text-center fw-bold">
                {{ title }}
            </div>

            <div class="card-body p-0">
                <table class="table table-sm mb-0 text-center">
                    <thead>
                        <tr>
                            <th><small>Lender</small></th>
                            <th><small>Count</small></th>
                            <th><small>Gross</small></th>
                        </tr>
                    </thead>
                    
                    <tbody>
                        <tr v-for="(row, index) in summaryData.summaryArray" :key="index">
                            <td><small>{{ row.lender }}</small></td>
                            <td><small>{{ row.count }}</small></td>
                            <td><small>{{ row.total }}</small></td>
                        </tr>
                        <tr class="fw-bold">
                            <td><small>Totals</small></td>
                            <td><small>{{ summaryData.totalCounts }}</small></td>
                            <td><small>{{ summaryData.totalGross }}</small></td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Origination Group Table -->
    <div v-else-if="type === 'origination'" class="col-md-6">
        <div class="card shadow-sm">
            <div class="card-header bg-light text-center fw-bold">
                Funding by Origination Group
            </div>

            <div class="card-body p-0">
                <table class="table table-sm mb-0 text-center">
                    <thead>
                        <tr>
                        <th><small></small></th>
                        <th><small>NB #</small></th>
                        <th><small>NB $</small></th>
                        <th><small>PB #</small></th>
                        <th><small>PB $</small></th>
                        <th><small>Total #</small></th>
                        <th><small>Total $</small></th>
                        </tr>
                    </thead>

                    <tbody>
                        <tr 
                            v-for="(row, index) in data"
                            :key="index"
                            :class="{'fw-bold': ['Alpine Total', 'All Total'].includes(row.companyAbbr)}"
                        >
                            <td><small>{{ row.companyAbbr }}</small></td>
                            <td><small>{{ row.nbCount }}</small></td>
                            <td><small>{{ row.nbAmount }}</small></td>
                            <td><small>{{ row.pbCount }}</small></td>
                            <td><small>{{ row.pbAmount }}</small></td>
                            <td><small>{{ row.totalCount }}</small></td>
                            <td><small>{{ row.totalAmount }}</small></td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</template>

<script setup>
const props = defineProps({
    type: {
        type: String,
        default: 'gross' // 'gross' or 'origination'
    },
    title: String,
    summaryData: {
        type: Object,
        default: () => ({
            summaryArray: [],
            totalCounts: 0,
            totalGross: 0
        })
    },
    colSize: {
        type: [String, Number],
        default: 6
    },
    data: {
        type: Array,
        default: () => []
    }
})
</script>