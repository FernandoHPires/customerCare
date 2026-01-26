<template>
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item">
                <RouterLink to="/">Home</RouterLink>
            </li>
            <li class="breadcrumb-item">
                DMS
            </li>
            <li class="breadcrumb-item active">
                Template Management
            </li>
        </ol>
    </nav>

    <div class="card">
        <div class="card-header">
            Add New Template
        </div>

        <div class="card-body">
            <div class="d-flex align-items-end">
                <div class="me-2">
                    <label class="form-label">Company / Province</label>
                    <select class="form-select" v-model="company">
                        <option value=""></option>
                        <option v-for="(company, key) in companies" :key="key" :value="company.id">{{ company.name }}</option>
                    </select>
                </div>

                <div class="me-2">
                    <label class="form-label">Document Type</label>
                    <select class="form-select" v-model="documentType">
                        <option value=""></option>
                        <option v-for="(company, key) in documentTypes" :key="key" :value="company.id">{{ company.name }}</option>
                    </select>
                </div>

                <div class="me-2">
                    <button class="btn btn-success me-2" type="button"
                        @click="upload(null)">
                        <i class="bi-upload me-1"></i>Upload
                    </button>
                </div>
            </div>
        </div>
    </div>

    <div class="card mt-3">
        <div class="card-header">
            <div class="d-flex align-items-center">
                <div></div>

                <div class="ms-auto">
                    <div class="input-group">
                        <span class="input-group-text"><i class="bi-search"></i></span>
                        <input type="text" class="form-control" placeholder="Search" v-model="search">
                    </div>
                </div>
            </div>
        </div>

        <div class="card-body">
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th @click="filteredSort('name')">
                            <i class="me-1" v-bind:class="[getSortIcon('name')]"></i>Name
                        </th>
                        <th @click="filteredSort('province')">
                            <i class="me-1" v-bind:class="[getSortIcon('province')]"></i>Company / Province
                        </th>
                        <th @click="filteredSort('documentType')">
                            <i class="me-1" v-bind:class="[getSortIcon('documentType')]"></i>Document Type
                        </th>
                        <th @click="filteredSort('updatedAt')">
                            <i class="me-1" v-bind:class="[getSortIcon('updatedAt')]"></i>Updated At
                        </th>
                        <th></th>
                    </tr>
                </thead>

                <tbody v-if="filteredData.length == 0">
                    <tr>
                        <td colspan="5">No templates</td>
                    </tr>
                </tbody>

                <tbody v-else>
                    <tr v-for="(template, key) in filteredData" :key="key">
                        <td>{{ template.name }}</td>
                        <td>{{ template.province }}</td>
                        <td>{{ template.documentType }}</td>
                        <td>{{ formatPhpDateTime(template.updatedAt) }}</td>

                        <td class="text-end nowrap">
                            <button class="btn btn-primary me-2" type="button"
                                @click="download(template)">
                                <i class="bi-download me-1"></i>Download
                            </button>

                            <button class="btn btn-success me-2" type="button"
                                @click="upload(template)"
                                :disabled="template.waitingApproval">
                                <i v-if="template.waitingApproval === false" class="bi-upload me-1"></i>
                                {{ template.waitingApproval === false ? 'Replace' : 'Pending Approval' }}
                                
                            </button>

                            <button class="btn btn-outline-danger me-2" type="button"
                                @click="confirmDestroy(template)">
                                <i class="bi-trash me-1"></i>Delete
                            </button>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <input type="file"
            ref="file"
            @change="handleUploadFile()"
            hidden
        />
    </div>

    <ConfirmationDialog
        :event="event"
        :message="dialogMessage"
        type="danger"
        :parentModalId="modalId"
        :key="modalId"
        @return="destroy"
    />
</template>

<script>
import { util } from '../../mixins/util'
import ConfirmationDialog from '../../components/ConfirmationDialog'

export default {
    mixins: [util],
    emits: ['events'],
    components: { ConfirmationDialog },
    watch: {

    },     
    data() {
        return {
            search: '',
            currentSort: 'province',
            currentSortDir: 'bi-sort-up',
            templates: [],
            template: null,
            event: null,
            dialogMessage: '',
            modalId: 'templateManagement',
            companies: [
                { id: 'AB', name: 'AB' },
                { id: 'BC', name: 'BC' },
                { id: 'ON', name: 'ON' },
                { id: 'QC', name: 'QC' },
                { id: 'SQC', name: 'SQC' },
            ],
            company: '',
            documentTypes: [
                { id: 'Collection', name: 'Collection' },
                { id: 'Foreclosure', name: 'Foreclosure' },
                { id: 'Mortgage', name: 'Mortgage' },
                { id: 'Payout', name: 'Payout' },
                { id: 'Renewal', name: 'Renewal' },
                { id: 'Renewal Multi', name: 'Renewal Multi' },
                { id: 'Solicit', name: 'Solicit' },
                { id: 'Transfer', name: 'Transfer' },
            ],
            documentType: '',
        }
    },
    mounted() {
        this.getData()
    },
    computed: {
        filteredData() {
            var search = this.search && this.search.toLowerCase()
            var data = this.templates

            data = data.filter(function(row) {
                return Object.keys(row).some(function(key) {
                    return (
                        String(row[key]).toLowerCase().indexOf(search) > -1
                    )
                })
            })

            return data
        }
    },
    methods: {
        download: function(template) {
            this.showPreLoader()

            this.axios({
                method: 'get',
                url: 'web/dms/templates/download/' + template.id,
            })

            .then(response => {
                if(this.checkApiResponse(response)) {
                    const link = document.createElement('a')
                    link.href = 'data:text/xml;base64,' + response.data.data.file
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

            let data
            if(this.template != null) {
                data = JSON.stringify({
                    id: this.template.id
                })
            } else {
                data = JSON.stringify({
                    company: this.company,
                    documentType: this.documentType
                })
            }
            
            let blob = new Blob([data], {
                type: 'application/json'
            })

            let formData = new FormData();

            formData.append('content', blob)
            formData.append('file', file)
            
            this.axios.post(
                'web/dms/template-approval',
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
                this.$refs['file'].value = null
                this.hidePreLoader()
            })
        },
        upload: function(template) {
            if(template == null && this.documentType == '') {
                this.alertMessage = 'Error - Please select a document type'
                this.showAlert('error')
                this.$refs['file'].value = null
                return
            }

            this.template = template
            this.$refs['file'].click()
        },
        confirmDestroy: function(template) {
            this.template = template
            this.dialogMessage = 'Are you sure you want to delete this template?'
            this.showModal('confirmationDialog' + this.modalId)
        },
        destroy: function(event, returnMessage) {
            if(returnMessage !== 'confirmed') {
                return
            }

            this.showPreLoader()

            this.axios({
                method: 'delete',
                url: 'web/dms/templates/' + this.template.id
            })

            .then(response => {
                if(this.checkApiResponse(response)) {
                    this.getData()
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

            this.axios({
                method: 'get',
                url: 'web/dms/templates'
            })

            .then(response => {
                if(this.checkApiResponse(response)) {
                    this.templates = response.data.data
                    this.company = ''
                    this.documentType = ''
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