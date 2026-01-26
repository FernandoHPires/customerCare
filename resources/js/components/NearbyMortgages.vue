<template>   
    <div class="mb-4" style="overflow-x: hidden; overflow-y: auto; height: 110vh;">
        <div class="card mb-3">
            <div class="card-body">
                    <div class="row mb-2">
                        <div class="d-flex align-items-center justify-content-start flex-wrap">
                            <h5 class="me-2 mb-0">#{{ applicationId }}</h5>
                            <h6 class="mb-0"> - {{ fullAddress }}</h6>
                        </div>
                    </div>

 
                    <div class="card mb-3">
                        <div class="card-header">
                            <div class="d-flex align-items-center">
                                <div>
                                    <span class="m-1">Average</span>
                                </div>
                            </div>
                        </div>
                        <div class="card-body">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th class="table-header">LTV %</th>
                                        <th class="table-header">Appraisal</th>
                                        <th class="table-header">Assessment</th>
                                        <th class="table-header">Appr/Assess</th>
                                        <th class="table-header">Mortgage Amount</th>
                                        <th class="table-header">Int. Rate %</th>

                                    </tr>
                                </thead>

                                <tbody>
                                    <tr>
                                        <td class="table-body align-items-center">{{ formatDecimal(averageLtv) }}</td>
                                        <td class="table-body">{{ formatDecimal(averageAppraisal) }}</td>
                                        <td class="table-body">{{ formatDecimal(averageAssessment) }}</td>
                                        <td class="table-body">{{ formatDecimal(averageAppAssessment) }}</td>
                                        <td class="table-body">{{ formatDecimal(averageMtgAmount) }}</td>
                                        <td class="table-body">{{ formatDecimal(averageIntRate) }}</td>
                                    </tr>
                                
                                </tbody>
                            </table> 
                        </div>


                    </div>

                    <div>
                        <ul class="nav nav-tabs d-flex align-items-center mb-2 sticky-top bg-light-ultra">
                            <li class="nav-item" v-for="tab in navigationTabs" :key="tab.name">
                                <a
                                    class="nav-link"
                                    :class="{ active: selectedTab === tab.name }"
                                    href="#"
                                    @click.prevent="tabChanged(tab.name)"
                                >
                                    {{ tab.label }}
                                </a>
                            </li>
                        </ul>

                        <div v-if="selectedTab === 'postalCode1'">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th class="table-header">TACL #</th>
                                        <th class="table-header">Mtge #</th>
                                        <th class="table-header">Date Ordered</th>
                                        <th class="table-header">LTV %</th>
                                        <th class="table-header">Assessment</th>
                                        <th class="table-header">Appraisal</th>
                                        <th class="table-header">Appr/Assess</th>
                                        <th class="table-header">Mortgage Amount</th>
                                        <th class="table-header">Position</th>
                                        <th class="table-header">Int. Rate %</th>
                                        <th class="table-header">Postal Code</th>
                                        <th class="table-header">City</th>
                                        <th class="table-header">ILA</th>
                                        <th class="table-header">Yield</th>
                                        <th class="table-header">Appraiser</th>
                                        <th class="table-header">Lender</th>
                                    </tr>
                                </thead>

                                <tbody>
                                    <tr v-for="(prop, k) in postalCode1Data" :key="k">
                                        <td class="table-body">{{ prop.applicationId }}</td>
                                        <td class="table-body">{{ prop.mortgageCode }}</td>
                                        <td class="table-body">{{ formatDate(prop.dateOrdered) }}</td>
                                        <td class="table-body">{{ formatDecimal(prop.ltv) }}</td>
                                        <td class="table-body">{{ formatDecimal(prop.assessment) }}</td>
                                        <td class="table-body">{{ formatDecimal(prop.appraisal) }}</td>
                                        <td class="table-body">{{ formatDecimal(prop.appraisalAssessment) }}</td>
                                        <td class="table-body">{{ formatDecimal(prop.mortgageAmount) }}</td>
                                        <td class="table-body">{{ prop.position }}</td>
                                        <td class="table-body">{{ formatDecimal(prop.interestRate) }}</td>
                                        <td class="table-body">{{ prop.postalCode }}</td>
                                        <td class="table-body">{{ prop.city }}</td>
                                        <td class="table-body">{{ prop.ila }}</td>
                                        <td class="table-body">{{ prop.yield }}</td>
                                        <td class="table-body">{{ prop.appraiser }}</td>
                                        <td class="table-body">{{ prop.lender }}</td>                                    
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                        <div v-if="selectedTab === 'postalCode2'">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th class="table-header">TACL #</th>
                                        <th class="table-header">Mtge #</th>
                                        <th class="table-header">Date Ordered</th>
                                        <th class="table-header">LTV %</th>
                                        <th class="table-header">Assessment</th>
                                        <th class="table-header">Appraisal</th>
                                        <th class="table-header">Appr/Assess</th>
                                        <th class="table-header">Mortgage Amount</th>
                                        <th class="table-header">Position</th>
                                        <th class="table-header">Int. Rate %</th>
                                        <th class="table-header">Postal Code</th>
                                        <th class="table-header">City</th>
                                        <th class="table-header">ILA</th>
                                        <th class="table-header">Yield</th>
                                        <th class="table-header">Appraiser</th>
                                        <th class="table-header">Lender</th>
                                    </tr>
                                </thead>

                                <tbody>
                                    <tr v-for="(prop, k) in postalCode2Data" :key="k">
                                        <td class="table-body">{{ prop.applicationId }}</td>
                                        <td class="table-body">{{ prop.mortgageCode }}</td>
                                        <td class="table-body">{{ formatDate(prop.dateOrdered) }}</td>
                                        <td class="table-body">{{ formatDecimal(prop.ltv) }}</td>
                                        <td class="table-body">{{ formatDecimal(prop.assessment) }}</td>
                                        <td class="table-body">{{ formatDecimal(prop.appraisal) }}</td>
                                        <td class="table-body">{{ formatDecimal(prop.appraisalAssessment) }}</td>
                                        <td class="table-body">{{ formatDecimal(prop.mortgageAmount) }}</td>
                                        <td class="table-body">{{ prop.position }}</td>
                                        <td class="table-body">{{ formatDecimal(prop.interestRate) }}</td>
                                        <td class="table-body">{{ prop.postalCode }}</td>
                                        <td class="table-body">{{ prop.city }}</td>
                                        <td class="table-body">{{ prop.ila }}</td>
                                        <td class="table-body">{{ prop.yield }}</td>
                                        <td class="table-body">{{ prop.appraiser }}</td>
                                        <td class="table-body">{{ prop.lender }}</td>                                    
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                        <div v-if="selectedTab === 'postalCode3'">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th class="table-header">TACL #</th>
                                        <th class="table-header">Mtge #</th>
                                        <th class="table-header">Date Ordered</th>
                                        <th class="table-header">LTV %</th>
                                        <th class="table-header">Assessment</th>
                                        <th class="table-header">Appraisal</th>
                                        <th class="table-header">Appr/Assess</th>
                                        <th class="table-header">Mortgage Amount</th>
                                        <th class="table-header">Position</th>
                                        <th class="table-header">Int. Rate %</th>
                                        <th class="table-header">Postal Code</th>
                                        <th class="table-header">City</th>
                                        <th class="table-header">ILA</th>
                                        <th class="table-header">Yield</th>
                                        <th class="table-header">Appraiser</th>
                                        <th class="table-header">Lender</th>
                                    </tr>
                                </thead>

                                <tbody>
                                    <tr v-for="(prop, k) in postalCode3Data" :key="k">
                                        <td class="table-body">{{ prop.applicationId }}</td>
                                        <td class="table-body">{{ prop.mortgageCode }}</td>
                                        <td class="table-body">{{ formatDate(prop.dateOrdered) }}</td>
                                        <td class="table-body">{{ formatDecimal(prop.ltv) }}</td>
                                        <td class="table-body">{{ formatDecimal(prop.assessment) }}</td>
                                        <td class="table-body">{{ formatDecimal(prop.appraisal) }}</td>
                                        <td class="table-body">{{ formatDecimal(prop.appraisalAssessment) }}</td>
                                        <td class="table-body">{{ formatDecimal(prop.mortgageAmount) }}</td>
                                        <td class="table-body">{{ prop.position }}</td>
                                        <td class="table-body">{{ formatDecimal(prop.interestRate) }}</td>
                                        <td class="table-body">{{ prop.postalCode }}</td>
                                        <td class="table-body">{{ prop.city }}</td>
                                        <td class="table-body">{{ prop.ila }}</td>
                                        <td class="table-body">{{ prop.yield }}</td>
                                        <td class="table-body">{{ prop.appraiser }}</td>
                                        <td class="table-body">{{ prop.lender }}</td>                                    
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                        <div v-if="selectedTab === 'investorPayout'">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th class="table-header">TACL #</th>
                                        <th class="table-header">Mtge #</th>
                                        <th class="table-header">Date Ordered</th>
                                        <th class="table-header">LTV %</th>
                                        <th class="table-header">Assessment</th>
                                        <th class="table-header">Appraisal</th>
                                        <th class="table-header">Appr/Assess</th>
                                        <th class="table-header">Mortgage Amount</th>
                                        <!-- <th class="table-header">Position</th> -->
                                        <th class="table-header">Int. Rate %</th>
                                        <th class="table-header">Postal Code</th>
                                        <th class="table-header">City</th>
                                        <th class="table-header">ILA</th>
                                        <th class="table-header">Yield</th>
                                        <th class="table-header">Appraiser</th>
                                        <!-- <th class="table-header">Lender</th> -->
                                    </tr>
                                </thead>

                                <tbody>
                                    <tr v-for="(prop, k) in investorPayoutData" :key="k">
                                        <td class="table-body">{{ prop.applicationId }}</td>
                                        <td class="table-body">{{ prop.mortgageCode }}</td>
                                        <td class="table-body">{{ formatDate(prop.dateOrdered) }}</td>
                                        <td class="table-body">{{ formatDecimal(prop.ltv) }}</td>
                                        <td class="table-body">{{ formatDecimal(prop.assessment) }}</td>
                                        <td class="table-body">{{ formatDecimal(prop.appraisal) }}</td>
                                        <td class="table-body">{{ formatDecimal(prop.appraisalAssessment) }}</td>
                                        <td class="table-body">{{ formatDecimal(prop.mortgageAmount) }}</td>
                                        <!-- <td class="table-body">{{ prop.position }}</td> -->
                                        <td class="table-body">{{ formatDecimal(prop.interestRate) }}</td>
                                        <td class="table-body">{{ prop.postalCode }}</td>
                                        <td class="table-body">{{ prop.city }}</td>
                                        <td class="table-body">{{ prop.ila }}</td>
                                        <td class="table-body">{{ prop.yield }}</td>
                                        <td class="table-body">{{ prop.appraiser }}</td>
                                        <!-- <td class="table-body">{{ prop.lender }}</td> -->
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                
            </div>
        </div>
    </div>
</template>

<script>
import { property } from 'lodash';
import { util } from '../mixins/util'

export default {        
    mixins: [util],
    components: { },
    mounted() {
        console.log('NearbyMortgages.vue mounted')
        this.getData()
    },
    data() {
        return {
            modalId: 'nearbyMortgages',
            selectedTab: 'postalCode1',
            navigationTabs: [],
            postalCode1Data: [],
            postalCode2Data: [],
            postalCode3Data: [],
            investorPayoutData: [],
            averageLtv: 0,
            averageAppraisal: 0,
            averageAssessment: 0,
            averageAppAssessment: 0,
            averageMtgAmount: 0,
            averageIntRate: 0,
            applicationId: 0,
            propertyId: 0,
            postalCode: '',
            city: '',
            fullAddress: ''
        }
    },
    methods: {
        tabChanged(tab) {
            this.selectedTab = tab
            this.calculateAverages()
        },
        calculateAverages() {
            console.log('calculateAverages')
            let data = []
            if (this.selectedTab === 'postalCode1') {
                data = this.postalCode1Data
            } else if (this.selectedTab === 'postalCode2') {
                data = this.postalCode2Data
            } else if (this.selectedTab === 'postalCode3') {
                data = this.postalCode3Data
            } else if (this.selectedTab === 'investorPayout') {
                data = this.investorPayoutData
            }

            let ltv = 0
            let appraisal = 0
            let assessment = 0
            let appAssessment = 0
            let mtgAmount = 0
            let intRate = 0
            let countData = 0

            data.forEach((item) => {
                if (item.appraisal != 0 && item.assessment != 0) {
                    countData++
                    ltv += item.ltv
                    appraisal += item.appraisal
                    assessment += item.assessment
                    appAssessment += item.appraisalAssessment
                    mtgAmount += item.mortgageAmount
                    intRate += item.interestRate
                }
            })

            if (countData > 0) {
                this.averageLtv = ltv / countData
                this.averageAppraisal = appraisal / countData
                this.averageAssessment = assessment / countData
                this.averageAppAssessment = appAssessment / countData
                this.averageMtgAmount = mtgAmount / countData
                this.averageIntRate = intRate / countData
            } else {
                this.averageLtv = 0
                this.averageAppraisal = 0
                this.averageAssessment = 0
                this.averageAppAssessment = 0
                this.averageMtgAmount = 0
                this.averageIntRate = 0
            }

        },
        getData() {

            this.applicationId = this.$route.query.opportunityId
            this.propertyId = this.$route.query.propertyId
            this.postalCode = this.$route.query.postalCode
            this.city = this.$route.query.city

            this.selectedTab = 'postalCode1'
            this.navigationTabs = [
                { name: 'postalCode1', label: 'Same ' + (this.postalCode.substring(0, 3) || '') },
                { name: 'postalCode2', label: 'Same ' + (this.postalCode.substring(0, 5) || '') },
                { name: 'postalCode3', label: 'Same ' + (this.postalCode.substring(0, 6) || '') },
                { name: 'investorPayout', label: 'Same City (' + (this.city || '') + ')' },
            ];

            this.showPreLoader()

            this.axios.get('/web/contact-center/nearby-mortgages', {
                params: {
                    applicationId: this.applicationId,
                    propertyId: this.propertyId,
                    postalCode: this.postalCode,
                    city: this.city
                }
            })
            .then(response => {
                this.postalCode1Data = response.data.data.postalCode1
                this.postalCode2Data = response.data.data.postalCode2
                this.postalCode3Data = response.data.data.postalCode3
                this.investorPayoutData = response.data.data.investorPayout
                this.fullAddress = response.data.data.fullAddress
                this.calculateAverages()
            })
            .catch(error => {
                console.log(error)
                this.alertMessage = error
                this.showAlert("error")
            })
            .finally(() => {
                this.hidePreLoader()
            })


        },
    },
}
</script>

<style lang="scss" scoped>

    .form-label {
        margin-top: 0.5rem;
        margin-bottom: 0px;
    }

    .table-header {
        padding-right: 1rem; 
        padding-bottom: 0;
        border: none; 
        font-size: 0.875em;
        font-weight: bold;
        
    }

    .table-body {
        padding-right: 1rem;
        padding-top: 0;
        border: none;
        font-size: 0.875em;
        font-weight: normal;
    }

    .custom-modal-width {
        max-width: 100vw;
        width: auto;
    }
</style>