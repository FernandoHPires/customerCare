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
                PAP Bank File - Credit
            </li>
        </ol>
    </nav>

    <div class="row mb-3">
        <div class="col-6">
            <div class="card">
                <div class="card-header">
                    Credit Bank File Conversion
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
                                <input class="form-control" type="file"
                                    ref="file"
                                    @change="handleUploadFile()"
                                    hidden
                                />
                                <button type="button" class="btn btn-secondary"
                                    @click="uploadFile()"
                                    :disabled="lastFileStatus == 'R' || lastFileStatus == 'P'"
                                >
                                    <i class="bi-upload me-1"></i>Upload File
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
                        <td>
                            <span v-if="file.status == 'C'" class="badge bg-success">Complete</span>
                            <span v-else-if="file.status == 'R'" class="badge bg-warning text-dark">Requested</span>
                            <span v-else-if="file.status == 'P'" class="badge bg-warning text-dark">Processing</span>
                            <span v-else-if="file.status == 'E'" class="badge bg-danger" v-tooltip="file.message">Error</span>
                        </td>
                        <td>{{ formatPhpDateTime(file.createdAt) }}</td>
                        <td>{{ file.createdBy }}</td>
                        <td class="text-end">
                            <button type="button" class="btn btn-primary" v-if="file.status == 'C'" @click="download(file.id)">
                                <i class="bi-cloud-download me-1"></i>Download
                            </button>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</template>

<script>
import { util } from '../../mixins/util'
import VTooltip from 'v-tooltip'
import ConfirmationDialog from '../../components/ConfirmationDialog'
import { DatePicker } from 'v-calendar'
import 'v-calendar/dist/style.css'

export default {
    mixins: [util],
    emits: ['events'],
    components: { VTooltip, ConfirmationDialog, DatePicker },
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
                {id: 16, name: 'Manchester Investments Inc'},
                {id: 182, name: 'Blue Stripe Financial Ltd.'},
                {id: 1970, name: 'Amur Financial Group - BC'},
                {id: 1971, name: 'Amur Financial Group - AB'},
                {id: 1972, name: 'Amur Financial Group - ON'}
            ],
            summaryData: {},
            event: '',
            dialogMessage: '',
            endDate: new Date(),
            startDate: new Date((new Date()).valueOf() - 1000*60*60*1440),
            companyIdFilter: 0,
        }
    },
    mounted() {
        this.getData()
    },
    methods: {
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
        handleUploadFile: function() {
            this.showPreLoader()

            let file = this.$refs['file'].files[0]
            
            if((file.size / 1024 / 1024) > 20) {
                this.alertMessage = 'Error - The maximum file size allowed for upload is 20MB'
                this.showAlert('error')
                this.hidePreLoader()
                this.$refs['file'].value = null
                return
            }

            if(file.size <= 0) {
                this.alertMessage = 'Error - File cannot be empty'
                this.showAlert('error')
                this.hidePreLoader()
                this.$refs['file'].value = null
                return
            }

            let data = JSON.stringify({
                companyId: this.companyId
            })
            
            let blob = new Blob([data], {
                type: 'application/json'
            })

            let formData = new FormData();

            formData.append('content', blob)
            formData.append('file', file)
            
            this.axios.post(
                'web/pap/upload',
                formData,
                {
                    headers: {
                        'Content-Type': 'multipart/form-data'
                    }
                }
            )
            .then(response => {
                this.alertMessage = response.data.message
                this.showAlert(response.data.status)

                if(this.checkApiResponse(response)) {
                    this.getData()
                }                
            })
            .catch(error => {
                this.alertMessage = error
                this.showAlert('error')
            })
            .finally(() => {
                this.$refs['file' + key].value = null
                this.hidePreLoader()
            })
        },
        uploadFile: function() {
            if(this.validateCompany()) {
                this.$refs['file'].click()
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
                type: "C",
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
        }
    }
}
</script>