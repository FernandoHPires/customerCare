<template>
    <div class="modal" id="lenderModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">

                <div class="modal-header">
                    <h5 class="modal-title">Lender Codes</h5>
                    <button type="button" class="btn-close" data-coreui-dismiss="modal" aria-label="Close"></button>
                </div>

                <div class="modal-header">
                    <div class="d-flex w-100">
                        <div class="col-6">
                            <input v-model="searchFirm" @keyup="searchField" type="text" class="form-control" placeholder="Search Firm">
                        </div>
                        <div class="ms-auto">
                            <button class="btn btn-primary" @click="addLender">
                                <i class="bi-plus-lg me-1"></i> Add Lending Firm
                            </button>
                        </div>
                    </div>
                </div>

                <div v-if="addNewLender">
                    <div class="modal-body">
                        <div class="row d-flex align-items-end">
                            <div class="col-4 px-3">
                                <label class="form-label">Firm Name</label>
                                <input v-model="firmName" type="text" class="form-control">
                            </div>
                            <div class="col-4 px-3">
                                <label class="form-label">Abbreviation</label>
                                <input v-model="abbreviation" type="text" class="form-control">
                            </div>
                        </div>
                        <div class="row d-flex align-items-end">
                            <div class="col-4 px-3">
                                <label class="form-label">Comments</label>
                                <textarea v-model="commentsLender" rows="2" class="form-control"></textarea>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button class="btn btn-outline-dark" @click="cancel"><i class="bi-x-lg me-1"></i>Cancel</button>
                        <button class="btn btn-success" @click="save"><i class="bi-save me-1"></i>Save</button>
                    </div>
                </div>

                <div class="modal-body">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Code</th>
                                <th>Firm</th>
                                <th>Abbr</th>
                                <th>Comments</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="firm in firmArr" :key="firm.lender_branch_code">
                                <td>{{ firm.lender_branch_code }}</td>
                                <td>{{ firm.firm_name }}</td>
                                <td>{{ firm.abbr }}</td>
                                <td>{{ firm.comments }}</td>
                                <td class="text-end">
                                    <button @click="selectFirmCode(firm)" class="btn btn-sm btn-primary" data-coreui-dismiss="modal">
                                        <i class="bi bi-box-arrow-right"></i> Select
                                    </button>
                                </td>
                            </tr>
                        </tbody>
                    </table>

                    <div class="d-flex mt-3 mb-3">
                        <div>Page {{ curPage }} of {{ pageTotal }}</div>
                        <div class="flex-grow-1"></div>
                        <div class="paginate">
                            <div class="d-flex btn-group">
                                <button v-if="curPage !== 1" @click="pageClick(curPage - 1)" class="btn btn-xs btn-primary">Prev</button>
                                <button
                                    v-for="page in loopPages"
                                    :key="page"
                                    @click="pageClick(page)"
                                    :class="page === curPage ? 'btn btn-xs btn-primary' : 'btn btn-xs btn-outline-primary'"
                                >
                                    {{ page }}
                                </button>
                                <button v-if="curPage !== pageTotal" @click="pageClick(curPage + 1)" class="btn btn-xs btn-primary">Next</button>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="modal-footer">
                    <button class="btn btn-outline-dark" data-coreui-dismiss="modal"><i class="bi-x-lg me-1"></i>Close</button>
                </div>
            </div>
        </div>
    </div>
</template>

<script>
import { util } from "../../mixins/util";
export default {
    mixins: [util],
    emits: ['selectFirm'],
    data() {
        return {
            firms: [],
            hold: [],
            firmName: '',
            abbreviation: '',
            commentsLender: '',
            searchFirm: '',
            addNewLender: false,
            curPage: 1,
            pageSize: 10,
        }
    },
    computed: {
        pageTotal() {
            return Math.ceil(this.filteredFirms.length / this.pageSize)
        },
        firmArr() {
            let start = (this.curPage - 1) * this.pageSize
            let end = start + this.pageSize
            return this.filteredFirms.slice(start, end)
        },
        filteredFirms() {
            return this.hold.length ? this.hold : this.firms
        },
        loopPages() {
            let total = this.pageTotal
            let pages = []
            let maxPages = 10
            let start = Math.max(1, this.curPage - Math.floor(maxPages / 2))
            let end = Math.min(total, start + maxPages - 1)
            for (let i = start; i <= end; i++) pages.push(i)
            return pages
        }
    },
    methods: {
        getFirms() {
            this.axios.get('/web/contact-center/lender-firms')
                .then(response => {
                    if (response.data.status === 'success') {
                        this.firms = response.data.data
                        this.hold = []
                    }
                })
        },
        searchField() {
            let term = this.searchFirm.toLowerCase()
            this.curPage = 1
            this.hold = this.firms.filter(f =>
                f.firm_name.toLowerCase().includes(term) ||
                f.abbr.toLowerCase().includes(term) ||
                f.lender_branch_code.toString().includes(term)
            )
        },
        addLender() {
            this.addNewLender = true
        },
        cancel() {
            this.firmName = ''
            this.abbreviation = ''
            this.commentsLender = ''
            this.addNewLender = false
        },
        save() {
            if (!this.firmName) {
                this.alertMessage = 'Firm Name must be informed!';
                this.showAlert('error');
                return
            }

            let data = {
                firmName: this.firmName,
                abbreviation: this.abbreviation,
                commentsLender: this.commentsLender
            }

            this.axios.post('/web/contact-center/lender-firms', data)
                .then(response => {
                    if (response.data.status === 'success') {
                        this.cancel()
                        this.getFirms()
                    }
                })
        },
        pageClick(n) {
            this.curPage = n
        },
        selectFirmCode(firm) {
            this.$emit('selectFirm', firm)
        }
    },
    mounted() {
        this.getFirms()
    }
}
</script>

<style scoped>
.form-label {
    margin-top: 0.5rem;
    margin-bottom: 0;
}
</style>