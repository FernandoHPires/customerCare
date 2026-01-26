<template>
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item">Reports</li>
            <li class="breadcrumb-item">Sales</li>
            <li class="breadcrumb-item active">Initial Docs</li>
        </ol>
    </nav>

    <div class="card" style="max-height: 80vh">
        <div class="card-header">
            <div class="d-flex">
                <div class="pe-2">
                    <label>Start Date</label>
                    <DatePicker v-model="startDate" :model-config="modelConfig" :timezone="timezone">
                        <template v-slot="{ inputValue, inputEvents }">
                            <input
                                class="form-control"
                                :value="inputValue"
                                v-on="inputEvents"
                            />
                        </template>
                    </DatePicker>
                </div>

                <div class="pe-2">
                    <label>End Date</label>
                    <DatePicker v-model="endDate" :model-config="modelConfig" :timezone="timezone">
                        <template v-slot="{ inputValue, inputEvents }">
                            <input
                                class="form-control"
                                :value="inputValue"
                                v-on="inputEvents"
                            />
                        </template>
                    </DatePicker>
                </div>

                <div class="me-auto"></div>

                <!--<div class="me-2">
                    <button class="btn btn-outline-success" type="button" @click="download('C')">
                        <i class="bi-filetype-csv me-1"></i>Download CSV
                    </button>
                </div>

                <div>
                    <button class="btn btn-outline-primary" type="button" @click="download('E')">
                        <i class="bi-key me-1"></i>Download Encrypted
                    </button>
                </div>-->
            </div>
        </div>

        <div class="card-body table-responsive">
            <table class="table table-hover table-sm">
                <thead>
                    <tr>
                        <th>Agent</th>
                        <th style="width: 9%;" v-for="(column, key) in columns" :key="key" class="pe-3">
                            {{ column }}
                        </th>
                    </tr>
                </thead>

                <tbody>
                    <tr v-for="(row, key) in rows" :key="key">
                        <td>{{ row.agentName }}</td>
                        <td style="width: 9%;" v-for="(report, k) in row.reports" :key="k">
                            <span>{{ formatNumber(report) }}</span>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</template>

<script>
import { util } from '../../mixins/util'
import { DatePicker } from 'v-calendar'

export default {
    mixins: [util],
    emits: ['events'],
    components: { DatePicker },
    watch: {
        startDate: {
            handler(newValue, oldValue) {
                if(oldValue !== null) this.getData()
            },
            deep: true
        },
        endDate: {
            handler(newValue, oldValue) {
                if(oldValue !== null) this.getData()
            },
            deep: true
        }
    },
    data() {
        return {
            columns: [],
            rows: [],
            startDate: null,
            endDate: null,
        }
    },
    beforeMount() {
        var d = new Date()
        const month = d.getMonth();
        while(d.getMonth() === month) {
            d.setDate(d.getDate() - 1);
        }
        this.startDate = new Date(d.getFullYear(), d.getMonth() + 1, 1)
        this.endDate = new Date(d.getFullYear(), d.getMonth() + 2, 0)
    },
    mounted() {
        this.getData()
    },
    methods: {
        getData: function() {
            this.showPreLoader()

            let data = {
                startDate: this.startDate,
                endDate: this.endDate,
            }

            this.axios.get(
                '../web/reports/initial-docs',
                {params: data}
            )
            .then(response => {
                if(this.checkApiResponse(response)) {
                    this.columns = response.data.data.columns
                    this.rows = response.data.data.rows
                } else {
                    this.alertMessage = response.data.message
                    this.showAlert(response.data.status)
                }
            })
            .catch(error => {
                this.alertMessage = error
                this.showAlert('error')
            })
            .finally(() => {
                this.hidePreLoader()
            })
        }
    }
}
</script>
