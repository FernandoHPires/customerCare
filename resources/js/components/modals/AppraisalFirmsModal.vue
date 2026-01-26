<template>
    <div class="modal" id="appraisalFirmsModal" tabindex="-1">
        <div class="modal-dialog modal-fullscreen">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Appraisal Firm Code</h5>
                    <button type="button" class="btn-close" data-coreui-dismiss="modal" aria-label="Close"></button>
                </div>

                <div class="modal-header">
                    <div class="d-flex w-100">
                        <div class="col-6">
                            <input v-model="searchFirm" @keyup="searchField" type="text" class="form-control" placeholder="Search Firm">
                        </div>

                        <div class="ms-auto">
                            <button class="btn btn-primary" @click="addAppraisalFirm">
                                <i class="bi-plus-lg me-1"></i>Add Appraisal Firm
                            </button>
                        </div>
                    </div>
                </div>      

                <div v-if="addNewFirm">
                    <div class="modal-body">
                        <div class="row d-flex align-items-end">
                            <div class="col-2">
                                <label for="firstName" class="form-label"><span class="text-danger"></span>Name</label>
                                <input v-model="appraisalName" type="text" class="form-control">
                            </div>
                            <div class="col-2">
                                <label for="" class="form-label">Telephone</label>
                                <input v-model="appraisalTelephone" rows="1" class="form-control">
                            </div>
                            <div class="col-2">
                                <label for="" class="form-label">Fax</label>
                                <input v-model="appraisalFax" rows="1" class="form-control">
                            </div>
                            <div class="col-2">
                                <label for="" class="form-label">Email</label>
                                <input v-model="appraisalEmail" rows="1" class="form-control">
                            </div>
                            <div class="col-2">
                                <label for="" class="form-label">Website</label>
                                <input v-model="appraisalWebsite" rows="1" class="form-control">
                            </div>
                        </div>
                        <div class="row d-flex align-items-end">
                            <div class="col-2">
                                <label for="" class="form-label">Unit Number</label>
                                <input v-model="appraisalUnitNumber" rows="1" class="form-control">
                            </div>
                            <div class="col-2">
                                <label for="" class="form-label">Street Number</label>
                                <input v-model="appraisalStreetNumber" rows="1" class="form-control">
                            </div>
                            <div class="col-2">
                                <label for="" class="form-label">Street Name</label>
                                <input v-model="appraisalStreetName" rows="1" class="form-control">
                            </div>
                            <div class="col-2">
                                <label for="" class="form-label">Street Type</label>
                                <select v-model="appraisalStreetType" class="form-select">
                                    <option value=""></option>
                                    <option value="AVE">AVE</option>
                                    <option value="BLVD">BLVD</option>
                                    <option value="CRES">CRES</option>
                                    <option value="CRT">CRT</option>
                                    <option value="DR">DR</option>
                                    <option value="HWY">HWY</option>
                                    <option value="LANE">LANE</option>
                                    <option value="PL">PL</option>
                                    <option value="RANG">RANG</option>
                                    <option value="RD">RD</option>
                                    <option value="ST">ST</option>
                                    <option value="WAY">WAY</option>
                                    <option value="OTHER">OTHER</option>
                                </select>
                            </div>
                            <div class="col-2">
                                <label for="" class="form-label">Direction</label>
                                <select v-model="appraisalDirection" class="form-select">
                                    <option value="N/A">N/A</option>
                                    <option value="E">East</option>
                                    <option value="W">West</option>
                                    <option value="N">North</option>
                                    <option value="NE">North East</option>
                                    <option value="NW">North West</option>
                                    <option value="S">South</option>
                                    <option value="SE">South East</option>
                                    <option value="SW">South West</option>
                                </select>
                            </div>
                            <div class="col-2">
                                <label for="" class="form-label">City</label>
                                <input v-model="appraisalCity" rows="1" class="form-control">
                            </div>
                            <div class="col-2">
                                <label for="" class="form-label">Province</label>
                                <input v-model="appraisalProvince" rows="1" class="form-control">
                            </div>
                            <div class="col-2">
                                <label for="" class="form-label">Postal Code</label>
                                <input v-model="appraisalPostalCode" rows="1" class="form-control">
                            </div>
                        </div>
                        <div class="row d-flex align-items-end mt-3">
                            <div class="col-2">
                                <label for="" class="form-label">PO Box</label>
                                <input v-model="appraisalPOBox" rows="1" class="form-control">
                            </div>
                            <div class="col-2">
                                <label for="" class="form-label">STN</label>
                                <input v-model="appraisalSTN" rows="1" class="form-control">
                            </div>
                            <div class="col-2">
                                <label for="" class="form-label">RR</label>
                                <input v-model="appraisalRR" rows="1" class="form-control">
                            </div>
                            <div class="col-2">
                                <label for="" class="form-label">Site</label>
                                <input v-model="appraisalSite" rows="1" class="form-control">
                            </div>
                            <div class="col-2">
                                <label for="" class="form-label">Comp</label>
                                <input v-model="appraisalComp" rows="1" class="form-control">
                            </div>
                            <div class="col-2">
                                <label for="" class="form-label">Designation</label>
                                <select v-model="appraisalDesignation" class="form-select">
                                    <option value="0">N/A</option>
                                    <option value="1">CRA</option>
                                    <option value="2">AACI</option>
                                    <option value="3">DAR</option>
                                    <option value="4">CREA</option>
                                    <option value="5">RI(BC)</option>
                                    <option value="6">P.App.</option>
                                </select>
                            </div>
                            
                        </div>
                        <div class="row d-flex align-items-end mt-3">
                            <div class="col-2">
                                <label for="" class="form-label">Rating</label>
                                <input v-model="appraisalRating" rows="1" class="form-control">
                            </div>
                            <div class="col-4">
                                <label for="" class="form-label">Areas Covered</label>
                                <textarea v-model="appraisalAreasCovered" type="text" rows="1" class="form-control"></textarea>
                            </div>
                            <div class="col-4">
                                <label for="" class="form-label">Comments</label>
                                <textarea v-model="appraisalComments" type="text" rows="1" class="form-control"></textarea>
                            </div>
                        </div>
                    </div>

                    <div class="modal-footer">
                        <button class="btn btn-outline-dark" type="button" @click="cancel()">
                            <i class="bi bi-x-lg me-1"></i>Cancel
                        </button>
                        <button class="btn btn-success" type="button" @click="save()">
                            <i class="bi bi-save me-1"></i>Save
                        </button>
                    </div>
                </div>

                <div class="modal-body">
                    <div class="card mb-1" v-for="firm in firmArr" :key="firm.appraisalFirmCode">
                        <div class="card-body">
                            <div class="row">
                                <div class="col-11">
                                    <div class="row d-flex align-items-end">
                                        <div class="col-1">
                                            <label class="form-label">Code</label>
                                            <input v-model="firm.appraisalFirmCode" type="text" class="form-control border-0" readonly>
                                        </div>

                                        <div class="col-3">
                                            <label class="form-label">Name</label>
                                            <input v-model="firm.name" type="text" class="form-control border-0" readonly>
                                        </div>

                                        <div class="col-1">
                                            <label class="form-label">Phone</label>
                                            <input v-model="firm.telephone" type="text" class="form-control border-0" readonly>
                                        </div>

                                        <div class="col-1">
                                            <label class="form-label">Fax</label>
                                            <input v-model="firm.fax" type="text" class="form-control border-0" readonly>
                                        </div>

                                        <div class="col-2">
                                            <label class="form-label">Email</label>
                                            <input v-model="firm.email" type="email" class="form-control border-0" readonly>
                                        </div>

                                        <div class="col-2">
                                            <label class="form-label">Website</label>
                                            <input v-model="firm.website" type="text" class="form-control border-0" readonly>
                                        </div>

                                        <div class="col-1">
                                            <label class="form-label">Designation</label>
                                            <input v-model="firm.designation" type="text" class="form-control border-0" readonly>
                                        </div>

                                        <div class="col-1">
                                            <label class="form-label">Status</label>
                                            <input v-model="firm.status" type="text" class="form-control border-0" readonly>
                                        </div>

                                        <div class="col-4" v-if="!isEditing(firm)">
                                            <label class="form-label">Mailing Address</label>
                                            <textarea v-model="firm.address" rows="2" type="text" class="form-control border-0" readonly></textarea>
                                        </div>
                                        <div class="col-4" v-if="!isEditing(firm)">
                                            <label class="form-label">Areas Covered</label>
                                            <textarea v-model="firm.areasCovered" rows="2" type="text" class="form-control border-0" readonly></textarea>
                                        </div>

                                        <div class="col-4" v-if="!isEditing(firm)">
                                            <label class="form-label">Comments</label>
                                            <textarea v-model="firm.comments" rows="2" type="text" class="form-control border-0" readonly></textarea>
                                        </div>
                                    </div>

                                    <div class="row d-flex align-items-end" v-if="isEditing(firm)">                                    
                                        <div class="col-1">
                                            <label class="form-label">Unit Number</label>
                                            <input v-model="firm.unitNumber" type="text" class="form-control">
                                        </div>
                                        <div class="col-1">
                                            <label class="form-label">Street Number</label>
                                            <input v-model="firm.streetNumber" type="text" class="form-control">
                                        </div>
                                        <div class="col-2">
                                            <label class="form-label">Street Name</label>
                                            <input v-model="firm.streetName" type="text" class="form-control">
                                        </div>
                                        <div class="col-1">
                                            <label class="form-label">Street Type</label>
                                            <input v-model="firm.streetType" type="text" class="form-control">
                                        </div>
                                        <div class="col-1">
                                            <label class="form-label">Direction</label>
                                            <select v-model="firm.direction" class="form-select">
                                                <option value="N/A">N/A</option>
                                                <option value="E">East</option>
                                                <option value="W">West</option>
                                                <option value="N">North</option>
                                                <option value="NE">North East</option>
                                                <option value="NW">North West</option>
                                                <option value="S">South</option>
                                                <option value="SE">South East</option>
                                                <option value="SW">South West</option>
                                            </select>
                                        </div>
                                        <div class="col-1">
                                            <label class="form-label">City</label>
                                            <input v-model="firm.city" type="text" class="form-control">
                                        </div>
                                        <div class="col-1">
                                            <label class="form-label">Province</label>
                                            <input v-model="firm.province" type="text" class="form-control">
                                        </div>
                                        <div class="col-1">
                                            <label class="form-label">Postal Code</label>
                                            <input v-model="firm.postalCode" type="text" class="form-control">
                                        </div>
                                        <div class="col-1">
                                            <label class="form-label">PO Box</label>
                                            <input v-model="firm.poBox" type="text" class="form-control">
                                        </div>
                                        <div class="col-1">
                                            <label class="form-label">STN</label>
                                            <input v-model="firm.stn" type="text" class="form-control">
                                        </div>
                                        <div class="col-1">
                                            <label class="form-label">RR</label>
                                            <input v-model="firm.rr" type="text" class="form-control">
                                        </div>
                                        <div class="col-1">
                                            <label class="form-label">Site</label>
                                            <input v-model="firm.site" type="text" class="form-control">
                                        </div>
                                        <div class="col-1">
                                            <label class="form-label">Comp</label>
                                            <input v-model="firm.comp" type="text" class="form-control">
                                        </div>
                                        <div class="col-4">
                                            <label class="form-label">Areas Covered</label>
                                            <textarea v-model="firm.areasCovered" rows="2" type="text" class="form-control"></textarea>
                                        </div>

                                        <div class="col-4">
                                            <label class="form-label">Comments</label>
                                            <textarea v-model="firm.comments" rows="2" type="text" class="form-control"></textarea>
                                        </div>
                                    </div>
                                </div>

                                <!-- Right side with button -->
                                <div class="col-1 d-flex flex-column align-items-center justify-content-center gap-2">
                                    <button @click="selectFirmCode(firm.appraisalFirmCode)" type="button" class="btn btn-primary d-flex" data-coreui-dismiss="modal">
                                        <i class="bi bi-box-arrow-right me-1"></i>Select
                                    </button>
                                    <button 
                                        v-if="!isEditing(firm)"
                                        class="btn btn-outline-secondary d-flex"
                                        @click="editMailAreaComments(firm)"
                                        type="button"
                                        >
                                        <i class="bi bi-pencil me-1"></i>Edit
                                    </button>

                                    <template v-else>
                                        <button 
                                            class="btn btn-success d-flex" 
                                            @click="saveMailAreaComments(firm)"
                                            type="button"
                                        >
                                            <i class="bi bi-save me-1"></i>Save
                                        </button>
                                        <button 
                                            class="btn btn-outline-secondary" 
                                            @click="cancelEdit(firm)" 
                                            type="button"
                                        >
                                            <i class="bi bi-x-lg me-1"></i>Cancel
                                        </button>
                                    </template>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="d-flex mt-3 mb-3">
                        <div class="text-sm">
                            Page {{ curPage }} of {{ pageTotalComputed }}
                        </div>
                        <div class="flex-grow-1"></div>
                        <div class="paginate">
                            <div class="d-flex btn-group">
                                <button v-if="curPage !== 1" @click="pageClick(curPage-1)" class="btn btn-xs btn-primary" type="button">
                                    Prev
                                </button>
                                <button
                                    v-for="page in loopPages"
                                    :key="page"
                                    @click="pageClick(page)"
                                    :class="page === curPage ? 'btn btn-xs btn-primary' : 'btn btn-xs btn-outline-primary'"
                                    type="button"
                                >
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
import { util } from "../../mixins/util";
export default {
    mixins: [util],
    emits: ['selectInsBrokerCode'],
    data() {
        return {
            firms: [],
            hold: [],
            searchFirm: '',
            editingFirmCode: null,
            originalFirmData: null,
            isSaved: false,

            appraisalName: '',
            appraisalTelephone: '',
            appraisalFax: '',
            appraisalEmail: '',
            appraisalWebsite: '',
            appraisalUnitNumber: '',
            appraisalStreetNumber: '',
            appraisalStreetName: '',
            appraisalStreetType: '',
            appraisalDirection: '',
            appraisalCity: '',
            appraisalProvince: '',
            appraisalPostalCode: '',
            appraisalPOBox: '',
            appraisalSTN: '',
            appraisalRR: '',
            appraisalSite: '',
            appraisalComp: '',
            appraisalDesignation: '',
            appraisalRating: '',
            appraisalAreasCovered: '',
            appraisalComments: '',

            curPage: 1,
            pageSize: 10,
            startIndex: 0,
            endIndex: 0,
            pageTotal: 0,
            addNewFirm: false
        }
    },
    computed: {
        filteredFirms() {
            return this.hold.length ? this.hold : this.firms
        },
        pageTotalComputed() {
            return Math.ceil(this.filteredFirms.length / this.pageSize)
        },
        firmArr() {
            let start = (this.curPage - 1) * this.pageSize
            let end   = start + this.pageSize
            return this.filteredFirms.slice(start, end)
        },
        loopPages() {
            let total = this.pageTotalComputed
            let maxPages = 10
            let pages = []

            let start = Math.max(1, this.curPage - Math.floor(maxPages / 2))
            let end = Math.min(total, start + maxPages - 1)

            for (let i = start; i <= end; i++) {
                pages.push(i)
            }

            return pages
        }
    },
    methods: {
        getFirms() {
            this.axios.get("/web/contact-center/appraisal-firms")
                .then(response => {
                    if (response.data.status === "success") {
                        this.firms = response.data.data
                        this.hold = []
                    }
                })
        },
        paginate(arr) {
            let start = (this.curPage - 1) * this.pageSize
            let end = start + this.pageSize
            this.pageTotal = Math.ceil(arr.length / this.pageSize)

            let maxPages = 10
            let startPage, endPage

            if (this.pageTotal <= maxPages) {
                startPage = 1
                endPage = this.pageTotal
            } else if (this.curPage <= Math.floor(maxPages / 2)) {
                startPage = 1
                endPage = maxPages
            } else if (this.curPage + Math.ceil(maxPages / 2) - 1 >= this.pageTotal) {
                startPage = this.pageTotal - maxPages + 1
                endPage = this.pageTotal
            } else {
                startPage = this.curPage - Math.floor(maxPages / 2)
                endPage = this.curPage + Math.ceil(maxPages / 2) - 1
            }

            this.startIndex = startPage
            this.endIndex = endPage

            return arr.slice(start, end)
        },
        pageClick(n) {
            this.curPage = n
        },
        searchField() {
            const term = this.searchFirm.toLowerCase()
            this.curPage = 1
            this.hold = this.firms.filter(search =>
                search.name.toLowerCase().includes(term) ||
                search.address.toLowerCase().includes(term) ||
                search.appraisalFirmCode.toString().includes(term) ||
                search.email.toLowerCase().includes(term) ||
                search.telephone.toLowerCase().includes(term) ||
                search.areasCovered.toLowerCase().includes(term) ||
                search.comments.toLowerCase().includes(term)
            )
        },
        selectFirmCode(firm) {
            this.$emit('selectInsBrokerCode', firm)
        },
        isEditing(firm) {
            return this.editingFirmCode === firm.appraisalFirmCode
        },
        editMailAreaComments(firm) {
            this.originalFirmData = { ...firm }
            this.editingFirmCode = firm.appraisalFirmCode
            this.isSaved = false
        },
        cancelEdit() {
            this.editingFirmCode = null
            this.originalFirmData = null
            this.isSaved = false
            this.getFirms()
        },
        saveMailAreaComments(firm) {
            let data = {
                appraisalName: firm.name,
                appraisalComments: firm.comments,
                appraisalTelephone: firm.telephone,
                appraisalFax: firm.fax,
                appraisalEmail: firm.email,
                appraisalWebsite: firm.website,
                appraisalUnitNumber: firm.unitNumber,
                appraisalStreetNumber: firm.streetNumber,
                appraisalStreetName: firm.streetName,
                appraisalStreetType: firm.streetType,
                appraisalDirection: firm.direction,
                appraisalCity: firm.city,
                appraisalProvince: firm.province,
                appraisalPostalCode: firm.postalCode,
                appraisalPOBox: firm.poBox,
                appraisalSTN: firm.stn,
                appraisalRR: firm.rr,
                appraisalSite: firm.site,
                appraisalComp: firm.comp,
                appraisalDesignation: firm.designation,
                appraisalRating: firm.rating,
                appraisalAreasCovered: firm.areasCovered
            }

            this.axios.put(`/web/contact-center/appraisal-firms/${firm.appraisalFirmCode}`, data)
                .then(response => {
                    if (response.data.status === 'success') {
                        this.getFirms()
                    }
                })

            this.editingFirmCode = null
            this.isSaved = true
        },
        addAppraisalFirm() {
            this.addNewFirm = true
        },
        cancel() {
            this.appraisalName = ''
            this.appraisalTelephone = ''
            this.appraisalFax = ''
            this.appraisalEmail = ''
            this.appraisalWebsite = ''
            this.appraisalUnitNumber = ''
            this.appraisalStreetNumber = ''
            this.appraisalStreetName = ''
            this.appraisalStreetType = ''
            this.appraisalDirection = ''
            this.appraisalCity = ''
            this.appraisalProvince = ''
            this.appraisalPostalCode = ''
            this.appraisalPOBox = ''
            this.appraisalSTN = ''
            this.appraisalRR = ''
            this.appraisalSite = ''
            this.appraisalComp = ''
            this.appraisalDesignation = ''
            this.appraisalRating = ''
            this.appraisalAreasCovered = ''
            this.appraisalComments = ''
            this.addNewFirm = false
        },
        save() {
            if (!this.appraisalName) {
                this.alertMessage = "Name must be informed!";
                this.showAlert("error");
                return
            }

            const data = {
                appraisalName: this.appraisalName,
                appraisalComments: this.appraisalComments,
                appraisalTelephone: this.appraisalTelephone,
                appraisalFax: this.appraisalFax,
                appraisalEmail: this.appraisalEmail,
                appraisalWebsite: this.appraisalWebsite,
                appraisalUnitNumber: this.appraisalUnitNumber,
                appraisalStreetNumber: this.appraisalStreetNumber,
                appraisalStreetName: this.appraisalStreetName,
                appraisalStreetType: this.appraisalStreetType,
                appraisalDirection: this.appraisalDirection,
                appraisalCity: this.appraisalCity,
                appraisalProvince: this.appraisalProvince,
                appraisalPostalCode: this.appraisalPostalCode,
                appraisalPOBox: this.appraisalPOBox,
                appraisalSTN: this.appraisalSTN,
                appraisalRR: this.appraisalRR,
                appraisalSite: this.appraisalSite,
                appraisalComp: this.appraisalComp,
                appraisalDesignation: this.appraisalDesignation,
                appraisalRating: this.appraisalRating,
                appraisalAreasCovered: this.appraisalAreasCovered
            }

            this.axios.post('/web/contact-center/appraisal-firms', data)
                .then(response => {
                    if (response.data.status === 'success') {
                        this.cancel()
                        this.getFirms()
                    }
                })

            this.addNewFirm = false
        }
    },
    mounted() {
        this.getFirms()
    }
}
</script>