<template>

    <div class="row mb-3">
        <div class="col-6">
            <div class="card">
                <div class="card-header">Filter</div>

                <div class="card-body">
                    <div class="d-flex">
                        <div class="pe-2">
                            <label class="form-label">Reference Month</label>
                            <select class="form-select" v-model="reference" @change="getData()">
                                <option
                                    v-for="(reference, key) in references"
                                    :key="key"
                                    :value="reference.id"
                                >
                                    {{ reference.name }}
                                </option>
                            </select>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <h5 class="m-0">Reconciliation</h5>
        </div>

        <div class="card-body">
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th>Agent</th>
                        <th class="text-end">Funded Gross Amount</th>
                        <th class="text-end">Funded Total Count</th>
                        <th class="text-end">Commission Gross Amount</th>
                        <th class="text-end">Commission Total Count</th>
                        <th class="text-end">Gross Difference</th>
                    </tr>
                </thead>

                <tbody>
                    <tr v-for="(item, key) in reconciliation" :key="key">
                        <td>{{ item.name }}</td>
                        <td class="text-end">{{ formatDecimal(item.fundedGrossAmount) }}</td>
                        <td class="text-end">{{ formatNumber(item.fundedTotalCount) }}</td>
                        <td class="text-end">{{ formatDecimal(item.commissionGrossAmount) }}</td>
                        <td class="text-end">{{ formatNumber(item.commissionTotalCount) }}</td>
                        <td class="text-end">{{ formatNumber(item.fundedGrossAmount - item.commissionGrossAmount) }}</td>
                    </tr>
                </tbody>

                <tfoot>
                    <tr>
                        <td><b>Total</b></td>
                        <td class="text-end"><b>{{ formatDecimal(totals.fundedGrossAmount) }}</b></td>
                        <td class="text-end"><b>{{ formatNumber(totals.fundedTotalCount) }}</b></td>
                        <td class="text-end"><b>{{ formatDecimal(totals.commissionGrossAmount) }}</b></td>
                        <td class="text-end"><b>{{ formatNumber(totals.commissionTotalCount) }}</b></td>
                        <td class="text-end"><b>{{ formatNumber(totals.fundedGrossAmount - totals.commissionGrossAmount) }}</b></td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>

</template>

<script>
import { util } from "../../mixins/util"
//import AgentModal from "../../components/CMS/AgentModal.vue"

export default {
    mixins: [util],
    components: { },
    emits: ['events'],
    data() {
        return {
            references: [],
            reference: null,
            reconciliation: [],
            totals: {
                fundedGrossAmount: 0,
                fundedTotalCount: 0,
                commissionGrossAmount: 0,
                commissionTotalCount: 0
            }
        }
    },
    mounted() {
        this.references = this.getReferencePeriods(4)
    },
    methods: {
        getData: function() {
            this.showPreLoader()

            let data = {
                referencePeriod: this.reference
            }

            this.axios.get(
                'web/cms/reconciliations',
                {
                    params: data
                }
            )
            .then(response => {
                if(this.checkApiResponse(response)) {
                    this.reconciliation = response.data.data.reconciliation
                    this.totals = response.data.data.totals
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
