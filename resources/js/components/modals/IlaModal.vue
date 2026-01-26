<template>
    <div class="modal" id="ilaModal" tabindex="-1">
        <div class="modal-dialog modal-xl">
            <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">ILA</h5>
                <button type="button" class="btn-close" data-coreui-dismiss="modal" aria-label="Close"></button>
            </div>

            <div class="modal-header">
                <div class="d-flex w-100">
                    <div class="col-6">
                        <input v-model="searchFirm" @keyup="searchField" type="text" class="form-control" placeholder="Search Firm">
                    </div>

                    <div class="ms-auto">
                        <button class="btn btn-primary" @click="addAppraisalFirm">
                            <i class="bi-plus-lg me-1"></i>Add New ILA Firm
                        </button>
                    </div>
                </div>
            </div>
            <ILAFirmForm 
                v-if="addNewFirm"
                :firmData="formData"
                mode="add"
                @save="save"
                @cancel="cancel"
            />
            <div class="modal-body">
                <div class = "table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Code</th>
                                <th>Firm</th>
                                <th>Name</th>
                                <th>Position</th>
                                <th>Phone</th>
                                <th>Fax</th>
                                <th>Email</th>
                                <th>Mailing Address</th>                            
                                <th>Use as Ila</th>
                                <th>Comments</th>
                                <th>Rating</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            <template v-for="firm in firmArr" :key="firm.id">
                                <!-- Normal Row -->
                                <tr>
                                    <td>{{ firm.id }}</td>
                                    <td>{{ firm.firmName }}</td>
                                    <td>{{ firm.name }}</td>
                                    <td>{{ firm.position }}</td>
                                    <td>{{ firm.telephone }}</td>
                                    <td>{{ firm.fax }}</td>
                                    <td>{{ firm.email }}</td>
                                    <td>{{ firm.address }}</td>
                                    <td>{{ firm.useAsIla }}</td>
                                    <td>{{ firm.comments }}</td>
                                    <td>{{ firm.rating }}</td>
                                    <td class="text-end">
                                        <button @click="seclectFirmCode(firm)" type="button" class="btn btn-sm btn-primary" data-coreui-dismiss="modal">
                                            <i class="bi bi-box-arrow-right me-1"></i>Select
                                        </button>
                                        <button @click="editILA(firm)" type="button" class="btn btn-outline-secondary btn-sm mt-1">
                                            <i class="bi bi-pencil me-1"></i>Edit
                                        </button>
                                    </td>
                                </tr>

                                <tr v-if="editingFirm && editingFirm.id === firm.id" class="table-active">
                                    <td colspan="12">
                                        <ILAFirmForm 
                                            :firmData="editingFirm"
                                            mode="edit"
                                            @save="saveILA"
                                            @cancel="cancelEdit"
                                        />
                                    </td>
                                </tr>
                            </template>
                        </tbody>
                        </table>
                    </div>

                    <div class="d-flex mt-3 mb-3">
                        <div class="text-sm">
                            Page {{ curPage }} of {{ pageTotal }}
                        </div>
                        <div class="flex-grow-1"></div>
                        <div class="paginate">
                            <div class="d-flex btn-group">
                                <button v-if="curPage !== 1" @click="pageClick(curPage-1)" class="btn btn-xs btn-primary" type="button">
                                    Prev
                                </button>
                                <button v-for="page in loopPages" :key="page" @click="pageClick(page)"
                                    :class="page === curPage ? 'btn btn-xs btn-primary' : 'btn btn-xs btn-outline-primary'">
                                    {{ page }}
                                </button>
                                <button v-if="curPage !== pageTotal" @click="pageClick(curPage+1)" class="btn btn-xs btn-primary" type="button">
                                    Next
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="modal-footer">                
                   <button class="btn btn-outline-dark" type="button" data-coreui-dismiss="modal">
                       <i class="bi bi-x-lg me-1"></i>Close 
                   </button>
                </div>
            </div>
        </div>
    </div>
</template>

<script>
import { util } from '../../mixins/util'
import ILAFirmForm from '../../components/ILAFirmForm.vue';

export default {
    components: {
        ILAFirmForm
    },
    mixins: [util],
    props: ['applicationId', 'refreshCount'],
    emits: ['selected'],
    data() {
        return {
            firms: [],
            hold: [],
            searchFirm: '',
            editingFirm: null,
            originalData: {},
            curPage: 1,
            pageSize: 10,
            addNewFirm: false,

            ilaFirmName: '',
            useAsIla: '',
            ilaPosition: '',
            ilaName: '',
            ilaTelephone: '',
            ilaFax: '',
            ilaEmail: '',
            appraisalWebsite: '',
            ilaUnitNumber: '',
            ilaStreetNumber: '',
            ilaStreetName: '',
            ilaStreetType: '',
            ilaDirection: '',
            ilaCity: '',
            ilaProvince: '',
            ilaPostalCode: '',
            ilaPOBox: '',
            ilaSTN: '',
            ilaRR: '',
            ilaSite: '',
            ilaComp: '',
            appraisalDesignation: '',
            ilaRating: '',
            appraisalAreasCovered: ''
        }
    },
    computed: {
        pageTotal() {
            let data = this.hold.length ? this.hold : this.firms
            return Math.ceil(data.length / this.pageSize)
        },
        startIndex() {
            return this.pageTotal <= 10 ? 1 : Math.max(1, this.curPage - 5)
        },
        endIndex() {
            return Math.min(this.pageTotal, this.startIndex + 9)
        },
        loopPages() {
            const pages = []
            for (let i = this.startIndex; i <= this.endIndex; i++) {
                pages.push(i)
            }
            return pages
        },
        firmArr() {
            let data = this.hold.length ? this.hold : this.firms
            let start = (this.curPage - 1) * this.pageSize
            let end = start + this.pageSize
            return data.slice(start, end)
        }
    },
    methods: {
        getFirms() {
            this.axios.get('/web/contact-center/ila', {
                params: { applicationId: this.applicationId }
            }).then(response => {
                if (response.data.status === 'success') {
                    this.firms = response.data.data
                    this.hold = []
                }
            })
        },
        addAppraisalFirm() {
            this.addNewFirm = true
        },
        pageClick(n) {
            this.curPage = n
        },
        searchField() {
            let term = this.searchFirm.toLowerCase()
            this.curPage = 1
            this.hold = this.firms.filter(search => {
                return [search.name, search.firmName, search.address, search.email, search.telephone]
                    .some(f => (f ?? '').toLowerCase().includes(term)) ||
                    (search.id && search.id.toString().includes(term))
            })
        },
        seclectFirmCode(firm) {
            this.$emit('selected', firm.id)
        },
        editILA(firm) {
            if (!firm) return
            this.originalData = { ...firm }
            this.editingFirm = { ...firm }
        },
        cancelEdit() {
            Object.assign(this.editingFirm, this.originalData)
            this.editingFirm = null
        },
        async saveILA(firm) {
            try {
                await this.axios.put(`/web/contact-center/ila/${firm.id}`, firm)
                this.editingFirm = null
                this.getFirms()
            } catch (error) {
                console.error("Error updating firm:", error)
            }
        },
        cancel() {
            this.ilaFirmName = ''
            this.useAsIla = ''
            this.ilaPosition = ''
            this.ilaName = ''
            this.ilaComments = ''
            this.ilaTelephone = ''
            this.ilaFax = ''
            this.ilaEmail = ''
            this.appraisalWebsite = ''
            this.ilaUnitNumber = ''
            this.ilaStreetNumber = ''
            this.ilaStreetName = ''
            this.ilaStreetType = ''
            this.ilaDirection = ''
            this.ilaCity = ''
            this.ilaProvince = ''
            this.ilaPostalCode = ''
            this.ilaPOBox = ''
            this.ilaSTN = ''
            this.ilaRR = ''
            this.ilaSite = ''
            this.ilaComp = ''
            this.appraisalDesignation = ''
            this.ilaRating = ''
            this.appraisalAreasCovered = ''
            this.addNewFirm = false
        },
        save(firm) {
            if (!firm.firmName) {
                this.alertMessage = "Firm Name must be informed!";
                this.showAlert("error");
                return;
            }

            let data = {
                ilaFirmName: firm.firmName,
                useAsIla: firm.useAsIla,
                ilaPosition: firm.position,
                ilaName: firm.name,
                ilaComments: firm.comments,
                ilaTelephone: firm.telephone,
                ilaFax: firm.fax,
                ilaEmail: firm.email,
                appraisalWebsite: firm.appraisalWebsite || '',
                ilaStreetNumber: firm.streetNumber,
                ilaStreetName: firm.streetName,
                ilaStreetType: firm.streetType,
                ilaDirection: firm.direction,
                ilaCity: firm.city,
                ilaProvince: firm.province,
                ilaPostalCode: firm.postalCode,
                ilaPOBox: firm.poBox,
                ilaSTN: firm.stn,
                ilaRR: firm.rr,
                ilaSite: firm.site,
                ilaComp: firm.comp,
                appraisalDesignation: firm.appraisalDesignation || '',
                ilaRating: firm.rating,
                appraisalAreasCovered: firm.appraisalAreasCovered || ''
            }

            this.axios.post('/web/contact-center/ila', data)
                .then(response => {
                    if (response.data.status === 'success') {
                        this.cancel()
                        this.getFirms()
                    }
                })
                .catch(error => console.error("Error saving firm:", error))

            this.addNewFirm = false
        }
    },
    mounted() {
        this.getFirms()
    },
    watch: {
        refreshCount() {
            this.getFirms()
        }
    }
}
</script>