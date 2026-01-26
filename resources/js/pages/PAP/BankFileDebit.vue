<template>
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item">
                <RouterLink to="/">Home</RouterLink>
            </li>
            <li class="breadcrumb-item">
                Finance
            </li>
            <li class="breadcrumb-item active">
                PAP Bank File - Debit
            </li>
        </ol>
    </nav>

    <div class="row mb-3">
        <div class="col-6">
            <div class="card">
                <div class="card-header">
                    Payment Bank File Request
                </div>

                <div class="card-body">
                    <div class="d-flex">
                        <div>
                            <label for="" class="form-label">Company</label>
                            <select class="form-select" v-model="companyId">
                                <option value=""></option>
                                <option v-for="(company, key) in companies" :key="key" :value="company.id">{{ company.name }}</option>
                            </select>
                        </div>

                        <div class="ms-auto">
                            <div class="d-grid gap-2">
                                <button type="button" class="btn btn-secondary"
                                    @click="requestFile()"
                                    :disabled="lastFileStatus == 'R' || lastFileStatus == 'P'"
                                >
                                    <i class="bi-arrow-clockwise me-1"></i>Request File
                                </button>

                                <button type="button" class="btn btn-outline-secondary" @click="summary()">
                                    <i class="bi-list-ol me-1"></i>File Summary
                                </button>

                                <button type="button" class="btn btn-outline-secondary" @click="preview()">
                                    <i class="bi-eye me-1"></i>Preview Payments
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-6">
            <div class="card">
                <div class="card-header">
                    Filters
                </div>
                <div class="card-body">

                    <div class="d-flex">
                        <div class="pe-1">
                            <label class="form-label">Company</label>
                            <select class="form-select" v-model="companyIdFilter" @change="getData()">
                                <option value="0">All</option>
                                <option v-for="(company, key) in companies" :key="key" :value="company.id">{{ company.name }}</option>
                            </select>
                        </div>

                        <div class="pe-1">
                            <label class="form-label">Start Date</label>
                            <DatePicker v-model="startDate" :model-config="modelConfig" :timezone="timezone">
                                <template v-slot="{ inputValue, inputEvents }">
                                    <input
                                        class="form-control"
                                        :value="inputValue"
                                        v-on="inputEvents"
                                        @change="getData()"
                                    />
                                </template>
                            </DatePicker>
                        </div>

                        <div class="pe-1">
                            <label class="form-label">End Date</label>
                            <DatePicker v-model="endDate" :model-config="modelConfig" :timezone="timezone">
                                <template v-slot="{ inputValue, inputEvents }">
                                    <input
                                        class="form-control"
                                        :value="inputValue"
                                        v-on="inputEvents"
                                        @change="getData()"
                                    />
                                </template>
                            </DatePicker>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-body">
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th>Company</th>
                        <th>File Name</th>
                        <th>Reference Date</th>
                        <th>Status</th>
                        <th>Created At</th>
                        <th>Created By</th>
                        <th></th>
                    </tr>
                </thead>

                <tbody>
                    <tr v-for="(file, key) in files" :key="key">
                        <td>{{ file.companyName }}</td>
                        <td>{{ file.fileName }}</td>
                        <td>{{ formatPhpDate(file.referenceDate) }}</td>
                        <td>
                            <span v-if="file.status == 'C'" class="badge bg-success">Complete</span>
                            <span v-else-if="file.status == 'R'" class="badge bg-warning text-dark">Requested</span>
                            <span v-else-if="file.status == 'P'" class="badge bg-warning text-dark">Processing</span>
                            <span v-else-if="file.status == 'E'" class="badge bg-danger" v-tooltip="file.message">Error</span>
                        </td>
                        <td>{{ formatPhpDateTime(file.createdAt) }}</td>
                        <td>{{ file.createdBy }}</td>
                        <td class="text-end text-nowrap">
                            <button type="button" class="btn btn-primary" v-if="file.status == 'C'" @click="download(file.id)">
                                <i class="bi-cloud-download me-1"></i>Download
                            </button>

                            <button type="button" class="btn btn-secondary ms-1" v-if="file.status != 'E'" @click="netsuite(file)">
                                <i class="bi-file-earmark-spreadsheet me-1"></i>Netsuite
                            </button>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>

    <!--Modal-->
    <pap-preview-payments :payments="payments"></pap-preview-payments>

    <pap-file-summary :summary="summaryData"></pap-file-summary>

    <ConfirmationDialog
        :event="event"
        :message="dialogMessage"
        parentModalId=""
        type="success"
        key="papFile"
        @return="confirmationDialogOnReturn"
    />
</template>

<script>
import { util } from '../../mixins/util'
import VTooltip from 'v-tooltip'
import ConfirmationDialog from '../../components/ConfirmationDialog'
import PapPreviewPayments from '../../components/PAP/PreviewPayments'
import PapFileSummary from '../../components/PAP/FileSummary.vue'
import { DatePicker } from 'v-calendar'
import 'v-calendar/dist/style.css'

export default {
    mixins: [util],
    emits: ['events'],
    components: { VTooltip, ConfirmationDialog, PapPreviewPayments, PapFileSummary, DatePicker },
    watch: {
        startDate: {
            handler(newValue, oldValue) {
                this.getData()
            },
            deep: true
        },
        endDate: {
            handler(newValue, oldValue) {
                this.getData()
            },
            deep: true
        }        
    },      
    data() {
        return {
            files: [],
            lastFileStatus: '',
            payments: [],
            companyId: '',
            companies: [
                {id: 5, name: 'Ryan Mortgage Income Fund Inc.'},
                {id: 16, name: 'Manchester Investments Inc.'},
                {id: 182, name: 'Blue Stripe Financial Ltd.'},
                {id: 1970, name: 'Amur Financial Group - BC'},
                {id: 1971, name: 'Amur Financial Group - AB'},
                {id: 1972, name: 'Amur Financial Group - ON'}
            ],
            summaryData: {},
            event: '',
            dialogMessage: '',
            endDate: new Date(),
            startDate: new Date((new Date()).valueOf() - 1000*60*60*360),
            companyIdFilter: 0
        }
    },  
    mounted() {
        this.getData()
    },
    methods: {
        summary: function() {
            if(this.validateCompany()) {
                this.showPreLoader()

                this.axios({
                    method: 'get',
                    url: 'web/pap/summary/' + this.companyId
                })
                .then(response => {
                    if(this.checkApiResponse(response)) {
                        this.summaryData = response.data.data
                        this.showModal('papFileSummary')
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
        },
        
        download: function(papFileId) {
            this.showPreLoader()

            this.axios({
                method: 'get',
                url: 'web/pap/files/download/' + papFileId
            })
            .then(response => {
                if(this.checkApiResponse(response)) {
                    window.open(response.data.data.uri, "_blank")
                } else {
                    this.alertMessage = response.data.message
                    this.showAlert(response.data.status)
                }
            })
            .catch(error => {
                console.log(error)

                this.alertMessage = error
                this.showAlert('error')
            })
            .finally(() => {
                this.hidePreLoader()
            })
        },
        confirmationDialogOnReturn: function(event, returnMessage) {
            if(returnMessage == 'confirmed') {
                this.showPreLoader()

                this.axios({
                    method: 'get',
                    url: 'web/pap/files/request/' + this.companyId
                })
                .then(response => {
                    if(this.checkApiResponse(response)) {
                        this.companyId = ''
                        this.getData()
                    }

                    this.alertMessage = response.data.message
                    this.showAlert(response.data.status)
                })
                .catch(error => {
                    this.alertMessage = error
                    this.showAlert('error')
                })
                .finally(() => {
                    this.hidePreLoader()
                })
            }
        },
        requestFile: function() {
            if(this.validateCompany()) {
                this.dialogMessage = 'Are you sure you want to request the bank file?'
                this.showModal('confirmationDialog')
            }
        },
        preview: function() {
            if(this.validateCompany()) {
                this.showPreLoader()

                this.axios({
                    method: 'get',
                    url: 'web/pap/payments/' + this.companyId
                })
                .then(response => {
                    if(this.checkApiResponse(response)) {
                        this.payments = response.data.data
                        this.showModal('papPreviewPayments')
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
        },
        validateCompany: function() {
            if(this.companyId == '') {
                this.alertMessage = 'Company should be selected!'
                this.showAlert('error')
                return false
            }

            return true
        },
        
        getData: function() {
            this.showPreLoader()

            let data = {
                type: "D",
                startDate: this.startDate,
                endDate: this.endDate,
                companyIdFilter: this.companyIdFilter,
            }            

            this.axios({
                method: 'post',
                url: 'web/pap/files',
                data: data
            })
            
            .then(response => {
                if(this.checkApiResponse(response)) {
                    this.files = response.data.data.files
                    this.lastFileStatus = response.data.data.lastFileStatus
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
        netsuite: function(file) {
            this.showPreLoader()

            this.axios({
                method: 'get',
                url: 'web/pap/files/netsuite/' + file.id
            })
            .then(response => {
                if(this.checkApiResponse(response)) {
                    // Create CSV content
                    const csvContent = response.data.data
                    const blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' })
                    const link = document.createElement('a')
                    link.href = URL.createObjectURL(blob)
                    link.setAttribute('download', 'PAP #' + file.fileNumber + '.csv')
                    document.body.appendChild(link)
                    link.click()
                    document.body.removeChild(link)
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