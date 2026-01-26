<template>
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item">Reports</li>
            <li class="breadcrumb-item">Bayview</li>
            <li class="breadcrumb-item active">Commitment</li>
        </ol>
    </nav>
    
    <div class="card" style="max-height: 80vh">
        <div class="card-header">
            <div class="d-flex">
                <div class="me-auto"></div>

                <div class="me-2">
                    <button class="btn btn-outline-success" type="button" @click="download('C')">
                        <i class="bi-filetype-csv me-1"></i>Download CSV
                    </button>
                </div>

                <div>
                    <button class="btn btn-outline-primary" type="button" @click="download('E')">
                        <i class="bi-key me-1"></i>Download Encrypted
                    </button>
                </div>
            </div>
        </div>
        <div class="card-body table-responsive">
            <table class="table table-hover table-sm">
                <thead>
                    <tr>
                        <th v-for="(column, key) in columns" :key="key" class="pe-3">
                            {{ column.name }}
                        </th>
                    </tr>
                </thead>

                <tbody>
                    <tr v-for="(row, key) in rows" :key="key">
                        <td class="nowrap" v-for="(column, k) in columns" :key="k">
                            <span v-if="column.type == 'D'">{{ formatDecimal(row[k]) }}</span>
                            <span v-else>{{ row[k] }}</span>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</template>

<script>
import { util } from '../../mixins/util'

export default {
    mixins: [util],
    emits: ['events'],
    //components: { VTooltip, ConfirmationDialog },
    data() {
        return {
            columns: [],
            rows: []
        }
    },
    mounted() {
        this.getData()
    },
    methods: {
        download: function(type) {
            this.showPreLoader()

            let data = {
                type: type
            }

            this.axios.get(
                '/web/reports/bayview/commitment/download',
                {params: data}
            )
            .then(response => {
                if(this.checkApiResponse(response)) {
                    const link = document.createElement('a')

                    link.href = 'data:text/plain;base64,' + response.data.data.file
                    link.setAttribute('download', response.data.data.fileName)
                    document.body.appendChild(link)
                    link.click()
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
        },
        getData: function() {
            this.showPreLoader()

            let data = {
                //startDate: this.startDate,
                //endDate: this.endDate,
            }

            this.axios.get(
                '/web/reports/bayview/commitment',
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
