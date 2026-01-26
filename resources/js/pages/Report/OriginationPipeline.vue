<template>
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item">
                <RouterLink to="/">Home</RouterLink>
            </li>
            <li class="breadcrumb-item">
                <RouterLink to="/reports">Reports</RouterLink>
            </li>
            <li class="breadcrumb-item active">
                Origination Pipeline
            </li>
        </ol>
    </nav>  

    <div class="card" style="max-height : 85vh">
        <div class="card-header">
            <div class="d-flex align-items-center">
                <h5 class="m-0">Origination Pipeline Report - {{ getDate }}</h5>

                <div class="ms-auto">
                    <label class="form-label mb-0">End Date</label>
                    <input 
                        type="date" 
                        v-model="endDate" 
                        @change="getData" 
                        class="form-control"/>
                </div>
            </div>
        </div>

        <div class="card-body table-responsive">
            <!--Funding today-->
            <table class="table table-bordered table-hover">
                <thead>
                    <tr>
                        <th rowspan="2" class="text-center text-white bg-dark-blue">Today's Expected Fundings</th>
                        <th v-for="company in companies" :key="company" colspan="2" class="text-center text-white bg-dark-blue">
                            {{ company }}
                        </th>
                    </tr>
                    <tr>
                        <th class="text-center">Count</th>
                        <th class="text-center">Gross Amount</th>  
                        <th class="text-center">Count</th>
                        <th class="text-center">Gross Amount</th>
                        <th class="text-center">Count</th>
                        <th class="text-center">Gross Amount</th>
                        <th class="text-center">Count</th>
                        <th class="text-center">Gross Amount</th>
                        <th class="text-center">Count</th>
                        <th class="text-center">Gross Amount</th>
                        <th class="text-center">Total Count</th>
                        <th class="text-center">Total Gross Amount</th>
                    </tr>
                </thead>

                <tbody>
                    <tr>
                        <td>Alpine Credits</td>
                        <template v-for="(i, index) in 6" :key="index">
                            <template v-if="fundingToday.alpine"> 
                                <td class="text-center">
                                    <a
                                        href="#"
                                        v-if="fundingToday.alpine[index].investorId !== undefined"
                                        @click="fundingToday.alpine[index].investorId !== undefined && originationPipelineModal('Alpine Credits', fundingToday.alpine[index].companyId, fundingToday.alpine[index].investorId)">
                                        {{ fundingToday.alpine[index].count }}
                                    </a>
                                    <span v-else>
                                        {{ fundingToday.alpine[index].count }}
                                    </span>
                                </td>

                                <td class="text-center">
                                    <a
                                        href="#"
                                        v-if="fundingToday.alpine[index].investorId !== undefined"
                                        @click="fundingToday.alpine[index].investorId !== undefined && originationPipelineModal('Alpine Credits', fundingToday.alpine[index].companyId, fundingToday.alpine[index].investorId)">
                                        {{ formatDecimal(fundingToday.alpine[index].total) }}
                                    </a>
                                    <span v-else>
                                        {{ formatDecimal(fundingToday.alpine[index].total) }}
                                    </span>
                                </td>
                            </template>
                        </template>
                    </tr>

                    <tr>
                        <td>Sequence Capital</td>
                        <template v-for="(i, index) in 6" :key="index">
                            <template v-if="fundingToday.sequence">
                                <td class="text-center">
                                    <a
                                        href="#"
                                        v-if="fundingToday.sequence[index].investorId !== undefined"
                                        @click="fundingToday.sequence[index].investorId !== undefined && originationPipelineModal('Sequence Capital', fundingToday.sequence[index].companyId, fundingToday.sequence[index].investorId)">
                                        {{ fundingToday.sequence[index].count }}
                                    </a>
                                    <span v-else>
                                        {{ fundingToday.sequence[index].count }}
                                    </span>
                                </td>
                                <td class="text-center">
                                    <a
                                        href="#"
                                        v-if="fundingToday.sequence[index].investorId !== undefined"
                                        @click="fundingToday.sequence[index].investorId !== undefined && originationPipelineModal('Sequence Capital', fundingToday.sequence[index].companyId, fundingToday.sequence[index].investorId)">
                                        {{ formatDecimal(fundingToday.sequence[index].total) }}
                                    </a>
                                    <span v-else>
                                        {{ formatDecimal(fundingToday.sequence[index].total) }}
                                    </span>
                                </td>
                            </template>
                        </template>
                    </tr>
                    <tr>
                        <td class="total-row bold">Total</td>
                        <template v-for="(i, index) in 6" :key="index">
                            <template v-if="fundingToday.total">
                                <td class="total-row bold text-center">{{ fundingToday.total[index].count }}</td>
                                <td class="total-row bold text-center">{{ formatDecimal(fundingToday.total[index].total) }}</td>
                            </template>
                        </template>
                    </tr>
                </tbody>
            </table>

            <table class="table table-bordered table-hover">
                <thead>
                    <tr>
                        <th class="nowrap company-origin text-white bg-dark-blue"></th>
                        <th v-for="company in companies" :key="company" colspan="2" class="text-center text-white bg-dark-blue">
                            {{ company }}
                        </th>
                    </tr>
                    <tr>
                        <th>Status</th>
                        <th class="text-center">Count</th>
                        <th class="text-center">Gross Amount</th>
                        <th class="text-center">Count</th>
                        <th class="text-center">Gross Amount</th>
                        <th class="text-center">Count</th>
                        <th class="text-center">Gross Amount</th>
                        <th class="text-center">Count</th>
                        <th class="text-center">Gross Amount</th>
                        <th class="text-center">Count</th>
                        <th class="text-center">Gross Amount</th>
                        <th class="text-center">Total Count</th>
                        <th class="text-center">Total Gross Amount</th>
                    </tr>
                </thead>
                <tbody>
                    <tr class="first-row" v-for="(status, key) in statusOrder" :key="key">
                        <td
                            :class="[
                                status !== 'Funded' && status !== 'Origination' ? 'indent' : '',
                                status === 'Origination' ? 'Origination' : '',
                                status === 'Sub Total' ? 'sub-total-row bold' : '',
                                status === 'Total' ? 'total-row bold total' : '',
                                'first-row'
                            ]">
                                {{ status }}
                        </td>

                        <template v-if="status === 'Origination'">
                            <td class="first-row"></td>
                            <td class="first-row"></td>
                            <td class="first-row"></td>
                            <td class="first-row"></td>
                            <td class="first-row"></td>
                            <td class="first-row"></td>
                            <td class="first-row"></td>
                            <td class="first-row"></td>
                            <td class="first-row"></td>
                            <td class="first-row"></td>
                            <td class="first-row"></td>
                            <td class="first-row"></td>
                        </template>

                        <template v-else v-for="investor in investors" :key="investor.id">
                            <template v-if="totalCompanyData.some(row => row.investorId === investor.id && row.statusOrder === status)">
                                <template v-for="row in totalCompanyData" :key="row.id">
                                    <template v-if="row.investorId === investor.id && status === row.statusOrder">
                                        <template v-if="status === 'Sub Total'">
                                        <td class="sub-total-row bold text-center">{{ row.count }}</td>
                                        <td class="sub-total-row bold text-center">{{ formatDecimal(row.grossAmount) }}</td>
                                    </template>
                                    
                                    <template v-else-if="status === 'Total'">
                                        <td class="total-row bold text-center">{{ row.count }}</td>
                                        <td class="total-row bold text-center">{{ formatDecimal(row.grossAmount) }}</td>
                                    </template>

                                    <template v-else>
                                        <td class="text-center">{{ row.count }}</td>
                                        <td class="text-center">{{ formatDecimal(row.grossAmount) }}</td>
                                    </template>
                                    </template>
                                </template>
                            </template>
                            <template v-else>
                                <template v-if="status === 'Sub Total'">
                                    <td class="sub-total-row bold text-center"></td>
                                    <td class="sub-total-row bold text-center"></td>
                                </template>
                                <template v-else-if="status === 'Total'">
                                    <td class="total-row bold text-center"></td>
                                    <td class="total-row bold text-center"></td>
                                </template>
                                <template v-else>
                                    <td></td>
                                    <td></td>
                                </template>
                            </template>
                        </template>
                    </tr>                
                </tbody>
            </table>

            <!-- Apline table with all fileds -->
            <table class="table table-bordered table-hover">
                <thead>
                    <tr>
                        <th class="nowrap company-origin text-white bg-green">Alpine Credits</th>
                        <th v-for="company in companies" :key="company" colspan="2" class="text-center text-white bg-green">
                            {{ company }}
                        </th>
                    </tr>
                    <tr class="text-grey">
                        <th>Status</th>
                        <th class="text-center">Count</th>
                        <th class="text-center">Gross Amount</th>
                        <th class="text-center">Count</th>
                        <th class="text-center">Gross Amount</th>
                        <th class="text-center">Count</th>
                        <th class="text-center">Gross Amount</th>
                        <th class="text-center">Count</th>
                        <th class="text-center">Gross Amount</th>
                        <th class="text-center">Count</th>
                        <th class="text-center">Gross Amount</th>
                        <th class="text-center">Total Count</th>
                        <th class="text-center">Total Gross Amount</th>
                    </tr>
                </thead>

                <tbody>
                    <tr class="first-row " v-for="(status, key) in statusOrder" :key="key">
                        <td
                            :class="[
                                status !== 'Funded' && status !== 'Origination' ? 'indent' : '',
                                status === 'Origination' ? 'Origination' : '',
                                status === 'Sub Total' ? 'sub-total-row bold' : '',
                                status === 'Total' ? 'total-row bold total' : '',
                                'first-row'
                            ]"
                        >
                            {{ status }}
                        </td>

                        <template v-if="status === 'Origination'">
                            <td class="first-row"></td>
                            <td class="first-row"></td>
                            <td class="first-row"></td>
                            <td class="first-row"></td>
                            <td class="first-row"></td>
                            <td class="first-row"></td>
                            <td class="first-row"></td>
                            <td class="first-row"></td>
                            <td class="first-row"></td>
                            <td class="first-row"></td>
                            <td class="first-row"></td>
                            <td class="first-row"></td>
                        </template>

                        <template v-else v-for="investor in investors" :key="investor.id">
                            <template v-if="alpineData.some(row => row.investorId === investor.id && row.statusOrder === status)">
                                <template v-for="row in alpineData" :key="row.id">
                                    <template v-if="row.investorId === investor.id && status === row.statusOrder">
                                        <template v-if="status === 'Sub Total'">
                                            <td class="sub-total-row bold text-center">{{ row.count }}</td>
                                            <td class="sub-total-row bold text-center">{{ formatDecimal(row.grossAmount) }}</td>
                                        </template>
                                        
                                        <template v-else-if="status === 'Total'">
                                            <td class="total-row bold text-center">{{ row.count }}</td>
                                            <td class="total-row bold text-center">{{ formatDecimal(row.grossAmount) }}</td>
                                        </template>

                                        <template v-else>
                                            <td class="text-center">{{ row.count }}</td>
                                            <td class="text-center">{{ formatDecimal(row.grossAmount) }}</td>
                                        </template>
                                    </template>
                                </template>
                            </template>
                            <template v-else>
                                    <template v-if="status === 'Sub Total'">
                                        <td class="sub-total-row bold text-center"></td>
                                        <td class="sub-total-row bold text-center"></td>
                                    </template>
                                    <template v-else-if="status === 'Total'">
                                        <td class="total-row bold text-center"></td>
                                        <td class="total-row bold text-center"></td>
                                    </template>
                                    <template v-else>
                                        <td></td>
                                        <td></td>
                                    </template>
                                </template>
                        </template>
                    </tr>                    
                </tbody>
            </table>

            <!---Sequence Table with all fields-->
            <table class="table table-bordered table-hover table1">
                <thead>
                    <tr>
                        <th class="nowrap company-origin text-white bg-purple">
                            Sequence Capital
                        </th>
                        <th v-for="company in companies" :key="company" colspan="2" class="text-center text-white bg-purple">
                            {{ company }}
                        </th>
                    </tr>
                    <tr>
                        <th>Status</th>
                        <th class="text-center">Count</th>
                        <th class="text-center">Gross Amount</th>
                        <th class="text-center">Count</th>
                        <th class="text-center">Gross Amount</th>
                        <th class="text-center">Count</th>
                        <th class="text-center">Gross Amount</th>
                        <th class="text-center">Count</th>
                        <th class="text-center">Gross Amount</th>
                        <th class="text-center">Count</th>
                        <th class="text-center">Gross Amount</th>
                        <th class="text-center">Total Count</th>
                        <th class="text-center">Total Gross Amount</th>
                    </tr>
                </thead>

                <tbody>
                    <tr class="first-row " v-for="(status, key) in statusOrder" :key="key">
                        <td
                            :class="[
                                status !== 'Funded' && status !== 'Origination' ? 'indent' : '',
                                status === 'Origination' ? 'Origination' : '',
                                status === 'Sub Total' ? 'sub-total-row bold' : '',
                                status === 'Total' ? 'total-row bold total' : '',
                                'first-row'
                            ]"
                        >
                            {{ status }}
                        </td>

                        <template v-if="status === 'Origination'">
                            <td class="first-row"></td>
                            <td class="first-row"></td>
                            <td class="first-row"></td>
                            <td class="first-row"></td>
                            <td class="first-row"></td>
                            <td class="first-row"></td>
                            <td class="first-row"></td>
                            <td class="first-row"></td>
                            <td class="first-row"></td>
                            <td class="first-row"></td>
                            <td class="first-row"></td>
                            <td class="first-row"></td>
                        </template>

                        <template v-else v-for="investor in investors" :key="investor.id">
                            <template v-if="sequenceData.some(row => row.investorId === investor.id && row.statusOrder === status)">
                                <template v-for="row in sequenceData" :key="row.id">
                                    <template v-if="row.investorId === investor.id && status === row.statusOrder">
                                        <template v-if="status === 'Sub Total'">
                                            <td class="sub-total-row bold text-center">{{ row.count }}</td>
                                            <td class="sub-total-row bold text-center">{{ formatDecimal(row.grossAmount) }}</td>
                                        </template>
                                        
                                        <template v-else-if="status === 'Total'">
                                            <td class="total-row bold text-center">{{ row.count }}</td>
                                            <td class="total-row bold text-center">{{ formatDecimal(row.grossAmount) }}</td>
                                        </template>

                                        <template v-else>
                                            <td class="text-center">{{ row.count }}</td>
                                            <td class="text-center">{{ formatDecimal(row.grossAmount) }}</td>
                                        </template>
                                    </template>
                                </template>
                            </template>
                            <template v-else>
                                <template v-if="status === 'Sub Total'">
                                    <td class="sub-total-row bold text-center"></td>
                                    <td class="sub-total-row bold text-center"></td>
                                </template>
                                <template v-else-if="status === 'Total'">
                                    <td class="total-row bold text-center"></td>
                                    <td class="total-row bold text-center"></td>
                                </template>
                                <template v-else>
                                    <td></td>
                                    <td></td>
                                </template>
                            </template>
                        </template>
                    </tr>
                </tbody>
            </table>

            <!-- Direct To MIC Table -->
            <table class="table table-bordered table-hover table1" v-if="hasDirectMicData">
                <thead>
                    <tr>
                        <th class="nowrap company-origin text-white bg-royal-blue">Direct To MIC</th>
                        <th v-for="company in companies" :key="company" colspan="2" class="text-center text-white bg-royal-blue">
                            {{ company }}
                        </th>
                    </tr>
                    <tr class="text-grey">
                        <th>Status</th>
                        <th class="text-center">Count</th>
                        <th class="text-center">Gross Amount</th>
                        <th class="text-center">Count</th>
                        <th class="text-center">Gross Amount</th>
                        <th class="text-center">Count</th>
                        <th class="text-center">Gross Amount</th>
                        <th class="text-center">Count</th>
                        <th class="text-center">Gross Amount</th>
                        <th class="text-center">Count</th>
                        <th class="text-center">Gross Amount</th>
                        <th class="text-center">Total Count</th>
                        <th class="text-center">Total Gross Amount</th>
                    </tr>
                </thead>

                <tbody>
                    <tr class="first-row " v-for="(status, key) in statusOrder" :key="key">
                        <td
                            :class="[
                                status !== 'Funded' && status !== 'Origination' ? 'indent' : '',
                                status === 'Origination' ? 'Origination' : '',
                                status === 'Sub Total' ? 'sub-total-row bold' : '',
                                status === 'Total' ? 'total-row bold total' : '',
                                'first-row'
                            ]"
                        >
                            {{ status }}
                        </td>

                        <template v-if="status === 'Origination'">
                            <td class="first-row"></td>
                            <td class="first-row"></td>
                            <td class="first-row"></td>
                            <td class="first-row"></td>
                            <td class="first-row"></td>
                            <td class="first-row"></td>
                            <td class="first-row"></td>
                            <td class="first-row"></td>
                            <td class="first-row"></td>
                            <td class="first-row"></td>
                            <td class="first-row"></td>
                            <td class="first-row"></td>
                        </template>

                        <template v-else v-for="investor in investors" :key="investor.id">
                            <template v-if="directMicData.some(row => row.investorId === investor.id && row.statusOrder === status)">
                                <template v-for="row in directMicData" :key="row.id">
                                    <template v-if="row.investorId === investor.id && status === row.statusOrder">
                                        <template v-if="status === 'Sub Total'">
                                            <td class="sub-total-row bold text-center">{{ row.count }}</td>
                                            <td class="sub-total-row bold text-center">{{ formatDecimal(row.grossAmount) }}</td>
                                        </template>
                                        
                                        <template v-else-if="status === 'Total'">
                                            <td class="total-row bold text-center">{{ row.count }}</td>
                                            <td class="total-row bold text-center">{{ formatDecimal(row.grossAmount) }}</td>
                                        </template>

                                        <template v-else>
                                            <td class="text-center">{{ row.count }}</td>
                                            <td class="text-center">{{ formatDecimal(row.grossAmount) }}</td>
                                        </template>
                                    </template>
                                </template>
                            </template>
                            <template v-else>
                                <template v-if="status === 'Sub Total'">
                                    <td class="sub-total-row bold text-center"></td>
                                    <td class="sub-total-row bold text-center"></td>
                                </template>
                                <template v-else-if="status === 'Total'">
                                    <td class="total-row bold text-center"></td>
                                    <td class="total-row bold text-center"></td>
                                </template>
                                <template v-else>
                                    <td></td>
                                    <td></td>
                                </template>
                            </template>
                        </template>
                    </tr>                    
                </tbody>
            </table>
        </div>
    </div>

    <origination-pipeline-modal
        :company="companyTmp"
        :companyId="companuIdTmp"
        :investorId="investorIdTmp"
        :pipelineDetail="pipelineDetail"
        :refreshCount="refreshCount">
    </origination-pipeline-modal>


</template>

<script>
import { util } from "../../mixins/util";
import OriginationPipelineModal from '../../components/modals/OriginationPipelineModal.vue'

export default {
    components: { 
        OriginationPipelineModal
    },    
    mixins: [util],
    emits: ['events'],
    data() {
        return {
            totalCompanyData: [],
            alpineData:[],
            sequenceData:[],
            directMicData: [],
            companies: [
                "ACIF",
                "ACCIF",
                "ACHYF",
                "Private",
                "No Lender",
                "Total"
            ],
            investors: [
                { name: "ACIF", id: 31 },
                { name: "ACCIF", id: 248 },
                { name: "ACHYF", id: 100 },
                { name: "Private", id: 0 },
                { name: "No Lender", id: -1 },
                { name: "Total", id: -2 },
            ],
            statusOrder: [
                "Funded",
                "Origination",
                "Funding",
                "Signing",
                "Initial Docs",
                "Sub Total",
                "Total"
            ],
            fundingToday: [],
            endDate: new Date().toISOString().split('T')[0],
            companyTmp: null,
            companuIdTmp: null,
            investorIdTmp: null,
            refreshCount: 0,
            pipelineDetail: [],
        };
    },
    mounted() {
        this.getData()
    },
    computed: {
        getDate() {
            const today = new Date()
            const options = { year: "numeric", month: "long", day: "numeric" }

            let endDate = new Date(Date.parse(this.endDate + 'T00:00:00')).toLocaleDateString(undefined, options)
            let todayDate = today.toLocaleDateString(undefined, options)

            if(todayDate == endDate) {
                return todayDate
            } else {
                return todayDate + ' to ' + endDate
            }
        },
        getNextMonth() {
            const today = new Date()
            today.setMonth(today.getMonth() + 1)
            const options = { year: "numeric", month: "long" }
            return today.toLocaleDateString(undefined, options)
        },
        hasDirectMicData() {
            return this.directMicData && this.directMicData.length > 0;
        },
    },
    methods: {
        getData: function () {
            this.showPreLoader();

            this.axios.get("/web/reports/origination-pipeline",{
                params: {
                    endDate: this.endDate
                }
            })
            .then((response) => {
                if (this.checkApiResponse(response)) {
                    this.alpineData = response.data.data.alpineData
                    this.sequenceData = response.data.data.sequenceData
                    this.directMicData = response.data.data.directMicData
                    this.totalCompanyData = response.data.data.totalCompanyData
                    this.fundingToday = response.data.data.fundingToday
                    this.pipelineDetail = response.data.data.pipelineDetail
                } else {
                    this.alertMessage = response.data.message
                    this.showAlert(response.data.status)
                }
            })
            .catch((error) => {
                this.alertMessage = error
                this.showAlert('error')
            })
            .finally(() => {
                this.hidePreLoader()
            })
        },
        originationPipelineModal: function(company, companyId, investorId) {
            if (investorId === -1 || investorId === 0 || investorId === 31 || investorId === 100 || investorId === 248 ) {
                this.companyTmp = company
                this.companuIdTmp = companyId
                this.investorIdTmp = investorId
                this.refreshCount++
                this.showModal('originationPipelineModal')                
            }
        },

    }
}
</script>

<style scoped>
.total {
    width: 100%;
    gap: 2rem;
    padding: 0.5rem;
    font-weight: 600;
    background: #80808014;
    color: #2e2b24;
    align-items: center;
    margin: 1rem auto;
    padding-left: 1rem;
}
.indent {
    padding-left: 1rem;
}
.card.mic-pipeline tr.text-grey > th:not(:first-child) {
    color: #4a4a4a;
    text-align: right;
    padding-right: 0.5rem;
}
.card.mic-pipeline tr.text-grey th:first-child {
    color: #4a4a4a;
    text-align: left;
}
.card.mic-pipeline tr th.company-origin {
    text-align: left;
}
.sub-total-row {
    vertical-align: bottom;
    background: #fff2d9;
}
.sub-total {
    display: flex;
    flex-direction: column;
}
.total-row {
    background: #ffd486;
}
table tr.first-row:first-child {
    background: #fff2d9;
    font-weight: 600;
}
th:nth-child(1),
td:nth-child(1) {
    width: 150px;
}
.bg-royal-blue {
    background-color: #4169e1;
}
</style>