<template>
    <div class="modal" id="insuranceBrokerModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Insurance Broker Code</h5>
                <button type="button" class="btn-close" data-coreui-dismiss="modal" aria-label="Close"></button>
            </div>

            <div class="modal-header">
                <div class="d-flex w-100">
                    <div class="col-6">
                        <input v-model="searchFirm" @keyup="searchField" type="text" class="form-control" placeholder="Search Firm">
                    </div>

                    <div class="ms-auto">
                        <button class="btn btn-primary" @click="addInsurance">
                            <i class="bi-plus-lg me-1"></i>
                            Add Insurance Firm
                        </button>
                    </div>
                </div>
            </div>      

            <div v-if="addNewInsurance">
                <div class="modal-body">
                    <div class="row d-flex align-items-end">
                        <div class="col-4 px-3">
                                <label for="firstName" class="form-label"><span class="text-danger"></span>Firm Name</label>
                                <input v-model="firmNameInsurance" type="text" class="form-control">
                        </div>
                        <div class="col-8 px-3">
                            <label for="" class="form-label">Comments</label>
                            <textarea v-model="commentsInsurance" rows="1" class="form-control"></textarea>
                        </div>
                    </div>
                </div>

                <div class="modal-footer">
                    <button class="btn btn-outline-dark" type="button" @click="cancel()">
                        <i class="bi-x-lg me-1"></i>
                        Cancel 
                    </button>
                    <button class="btn btn-success" type="button" @click="save()">
                        <i class="bi-save me-1"></i>
                        Save 
                    </button>
                </div>
            </div>


            <div class="modal-body">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th scope="col">Code</th>
                            <th scope="col">Firm</th>
                            <th scope="col">Comments</th>
                            <th scope="col"></th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="firm in firmArr" :key="firm">
                            <th scope="row">{{ firm.insurance_branch_code }}</th>
                            <td>{{ firm.firm_name }}</td>
                            <td>{{ firm.comments }}</td>
                            <td class="text-end">
                                <button @click="selectFirmCode(firm.insurance_branch_code)" type="button" class="btn btn-sm btn-primary" data-coreui-dismiss="modal">
                                    <i class="bi bi-box-arrow-right"></i>
                                    Select
                                </button>
                            </td>
                        </tr>
                    </tbody>
                </table>
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
                    <i class="bi-x-lg me-1"></i>
                    Close 
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
            firmNameInsurance: '',
            commentsInsurance: '',

            curPage: 1,
            pageSize: 10,
            startIndex: 0,
            endIndex: 0,
            pageTotal: 0,
            addNewInsurance: false
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
            let end = start + this.pageSize
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
            this.axios.get('/web/contact-center/insurance-broker-firms')
            .then(response => {
                if (response.data.status === 'success') {
                    this.firms = response.data.data
                    this.hold = []
                }
            })
        },
        pageClick(n) {
            this.curPage = n
        },
        searchField() {
            const term = this.searchFirm.toLowerCase()
            this.curPage = 1
            this.hold = this.firms.filter(search =>
                search.firm_name.toLowerCase().includes(term) ||
                search.insurance_branch_code.toString().includes(term)
            )
        },
        selectFirmCode(firm) {
            this.$emit('selectInsBrokerCode', firm)
        },
        addInsurance() {
            this.addNewInsurance = true
        },
        cancel() {
            this.firmNameInsurance = ''
            this.commentsInsurance = ''
            this.addNewInsurance = false
        },        
        save() {
            if (!this.firmNameInsurance) {
                this.alertMessage = "Firm Name must be informed!";
                this.showAlert("error");
                return
            }

            let data = {
                firmNameInsurance: this.firmNameInsurance,
                commentsInsurance: this.commentsInsurance
            }

            this.axios.post('/web/contact-center/insurance-broker-firms', data)
                .then(response => {
                    if (response.data.status === 'success') {
                        this.cancel()
                        this.getFirms()
                    }
                })

            this.addNewInsurance = false
        }
    },
    mounted() {
        this.getFirms()
    }
}
</script>

<style lang="scss" scoped>
.nav-tabs .ml-auto {
    margin-left: auto;
    list-style: none;
}
.form-label {
    margin-top: 0.5rem;
    margin-bottom: 0px;
}
</style>