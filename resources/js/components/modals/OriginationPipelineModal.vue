<template>
    <div class="modal" :id="modalId" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">{{ this.company }} - {{ this.investorName }}</h5>
                    <button type="button" class="btn-close" data-coreui-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <table class="table mb-0">
                        <thead>
                            <tr>
                                <th class="table-header">#</th>
                                <th class="table-header">Application</th>
                                <th class="table-header">Agent</th>
                                <th class="table-header">Funding Date</th>
                                <th class="table-header">Gross Amount</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="(row, k) in applications" :key="k">
                                <td>{{ k + 1 }}</td>
                                <td>{{ row.aplicationId }}</td>
                                <td>{{ row.agentName }}</td>
                                <td>{{ formatPhpDate(row.fundingDate) }}</td>
                                <td>{{ formatDecimal(row.grossAmount) }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <div class="modal-footer">
                    <button class="btn btn-outline-dark" type="button" data-coreui-dismiss="modal">
                        <i class="bi-x-lg me-1"></i>Close
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>

<script>
    import { util } from '../../mixins/util'
    import { DatePicker } from 'v-calendar'

    export default {
        mixins: [util],
        emits: ['refresh'],
        components: { DatePicker },
        props: ['company', 'companyId', 'investorId', 'pipelineDetail', 'refreshCount'],
        watch: {
            refreshCount: {
                handler(newValue, oldValue) {
                    this.getData()
                },
                deep: true
            }     
        }, 
        data() {
            return {
                modalId: 'originationPipelineModal',
                applications: [],
                investorName: '',
                investors: [
                    { name: "ACIF", id: 31 },
                    { name: "ACCIF", id: 248 },
                    { name: "ACHYF", id: 100 },
                    { name: "Private", id: 0 },
                    { name: "No Lender", id: -1 },
                    { name: "Total", id: -2 },
                ],
            }
        },
        methods: {
            getData: function() {

                this.applications = []

                const investor = this.investors.find(inv => inv.id === this.investorId)
                this.investorName = investor ? investor.name : ''

                if (Array.isArray(this.pipelineDetail)) {
                    this.applications = this.pipelineDetail.filter(item =>
                        item.companyId === this.companyId &&
                        item.investorId === this.investorId
                    )
                }
            }
        }
    }
</script>