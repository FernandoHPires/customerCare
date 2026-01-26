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
                Template Approval
            </li>
        </ol>
    </nav>

    <div class="col-6 order-last order-md-first">
        <div class="card">
            <div class="card-header">
                Filters
            </div>
            <div class="card-body">

                <div class="d-flex">
                    <div class="pe-3">
                        <div class="form-label">Status</div>
                        <select v-model="statusSelected" class="form-select" @change="getData">
                            <option value="">All</option>
                            <option value="p">Pending</option>
                            <option value="a">Approved</option>
                            <option value="r">Rejected</option>
                        </select>
                    </div>

                    <div class="pe-3">
                        <label for="" class="form-label">Start Date</label>
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

                    <div>
                        <label for="" class="form-label">End Date</label>
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
                        <th @click="filteredSort('createdAt')">
                            <i class="me-1" v-bind:class="[getSortIcon('createdAt')]"></i>Created At
                        </th>
                        <th @click="filteredSort('createdBy')">
                            <i class="me-1" v-bind:class="[getSortIcon('createdBy')]"></i>Created By
                        </th>
                        <th @click="filteredSort('approvedAt')">
                            <i class="me-1" v-bind:class="[getSortIcon('approvedAt')]"></i>Approved At
                        </th>
                        <th @click="filteredSort('approvedBy')">
                            <i class="me-1" v-bind:class="[getSortIcon('approvedBy')]"></i>Approved By
                        </th>
                        <th></th>

                    </tr>
                </thead>

                <tbody v-if="filteredData.length == 0">
                    <tr>
                        <td colspan="6">There are no templates for approval</td>
                    </tr>
                </tbody>

                <tbody v-else>
                    <tr v-for="(template, key) in filteredData" :key="key">
                        <td>{{ template.name }}</td>
                        <td>{{ formatPhpDateTime(template.createdAt) }}</td>
                        <td>{{ template.createdBy }}</td>
                        <td>{{ formatPhpDateTime(template.approvedAt) }}</td>
                        <td>{{ template.approvedBy }}</td>
                        <td class="text-end nowrap">
                            <button 
                                type="button"
                                class="btn me-2 btn-primary" 
                                @click="download(template)">
                                <i class="bi-download me-1"></i>Download
                            </button>
                            <button
                                type="button"
                                class="btn" 
                                :class="{
                                    'btn-danger me-2 disabled': template.status === 'r',
                                    'btn-success disabled': template.status === 'a',
                                    'btn btn-success': template.status === 'p',
                                    'disabled': template.status.isActive === false
                                }"
                                @click="approval(template, 'a')">
                                <i class="bi-check-lg me-1"></i>
                                {{ template.status === 'a' ? 'Approved' : 'Approve' }}
                            </button>
                            <button
                                type="button"
                                class="btn"
                                :class="{
                                    'btn-danger disabled': template.status === 'r',
                                    'btn-outline-danger ms-2 disabled': template.status === 'a',
                                    'btn btn-outline-danger ms-2': template.status === 'p',
                                    'disabled': template.status.isActive === false
                                }"
                                @click="reject(template.id)">
                                <i class="bi-x-circle me-1"></i>
                                {{ template.status === 'r' ? 'Rejected' : 'Reject' }}
                            </button>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>

    <reject-dialog
        :event="event"
        :message="dialogMessage"
        type="success"
        :parentModalId="modalId"
        :key="modalId"
        @return="rejectDialogOnReturn">
    </reject-dialog>

</template>

<script>
import { util } from '../../mixins/util'
import { DatePicker } from 'v-calendar'
import RejectDialog from '../../components/RejectDialog'
import { reject } from 'lodash'

export default {
    mixins: [util],
    emits: ['events'],
    components: { DatePicker, RejectDialog},
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
            templates: [],
            startDate: new Date((new Date()).valueOf() - 1000*60*60*1440),
            endDate: new Date(),            
            statusSelected: 'p',
            search: '',
            currentSort: 'name',
            currentSortDir: 'bi-sort-up',
            modalId: 'TemplateApproval',
            dialogMessage: '',
            event: '',
            templateId: 0
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
        getData: function() {

            this.showPreLoader()

            let data = {
                startDate: this.startDate,
                endDate: this.endDate,
                status: this.statusSelected
            }

            this.axios.get(
                'web/dms/templates-approval',
                {params: data}
            )
            .then(response => {
                if(this.checkApiResponse(response)) {
                    this.templates = response.data.data
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
        download: function(template) {

            this.showPreLoader()

            this.axios({
                method: 'get',
                url: 'web/dms/templates/download-approval/' + template.id,
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
        approval: function(template, status) {

            this.showPreLoader()

            let data = {
                id: template.id,
                status: status,
                reason: ''
            }
            this.axios({
                method: "post",
                url: "web/dms/setup-template-approval",
                data: data            })
            .then((response) => {
                if (this.checkApiResponse(response)) {
                    this.getData();
                }
                this.alertMessage = response.data.message
                this.showAlert(response.data.status)
            })
            .catch(error => {
                this.alertMessage = error
                this.showAlert('error')
            })
            .finally(() => {
                this.hidePreLoader(); 
            })
        },
        reject: function(templateId) {
            this.templateId = templateId
            this.dialogMessage = 'Reject Reason'
            this.event = 'update'
            this.showModal('rejectDialog' + this.modalId)
        },
        rejectDialogOnReturn: function(event, returnMessage, rejectReason) {
            if(returnMessage == 'rejected') {

                this.showPreLoader()

                let data = {
                    id: this.templateId,
                    status: 'r',
                    reason: rejectReason
                }
                this.axios({
                    method: "post",
                    url: "web/dms/setup-template-approval",
                    data: data
                })
                .then((response) => {
                    if (this.checkApiResponse(response)) {
                        this.getData();
                    }
                    this.alertMessage = response.data.message
                    this.showAlert(response.data.status)
                })
                .catch(error => {
                    this.alertMessage = error
                    this.showAlert('error')
                })
                .finally(() => {
                    this.hidePreLoader();
                })
            }
        },
    }
}
</script>