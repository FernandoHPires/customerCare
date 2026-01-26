<template>
    <div
        class="modal fade"
        :id="modalId"
        data-coreui-backdrop="static" data-coreui-keyboard="false" tabindex="-1" 
        role="dialog" aria-hidden="true" style="display: none; z-index: 8000"
    >
        <div class="modal-dialog summary-approval" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Detailed Deals</h5>
                    <button
                        type="button"
                        class="btn-close"
                        @click="hideModal(modalId)"
                        aria-label="Close"
                    ></button>
                </div>

                <div class="modal-body">
                    <table class="table table-hover commission-approval-modal">
                        <thead>
                            <tr>
                                <th>S. No</th>

                                <th @click="filteredSort('tacl')">
                                    <i class="me-1" v-bind:class="[getSortIcon('tacl')]"></i>TACL#
                                </th>

                                <th @click="filteredSort('mortgageCode')">
                                    <i class="me-1" v-bind:class="[getSortIcon('mortgageCode')]"></i>Mortgage Code
                                </th>

                                <th @click="filteredSort('province')">
                                    <i class="me-1" v-bind:class="[getSortIcon('province')]"></i>Province
                                </th>

                                <th @click="filteredSort('agent')">
                                    <i class="me-1" v-bind:class="[getSortIcon('agent')]"></i>Agent
                                </th>

                                <th @click="filteredSort('grossAmount')">
                                    <i class="me-1" v-bind:class="[getSortIcon('grossAmount')]"></i>Gross Amount
                                </th>

                                <th @click="filteredSort('bonus')">
                                    <i class="me-1" v-bind:class="[getSortIcon('bonus')]"></i>Net Bonus
                                </th>

                                <th @click="filteredSort('commissionBy')">
                                    <i class="me-1" v-bind:class="[getSortIcon('commissionBy')]"></i>Commission By
                                </th>

                                <th @click="filteredSort('flatFee')">
                                    <i class="me-1" v-bind:class="[getSortIcon('flatFee')]"></i>Flat Fee / Percentage
                                </th>

                                <th @click="filteredSort('commissionValue')">
                                    <i class="me-1" v-bind:class="[getSortIcon('commissionValue')]"></i>Commission Amount
                                </th>

                                <th>Info</th>
                            </tr>
                        </thead>

                        <tbody v-if="filteredData.length == 0">
                            <tr>
                                <td colspan="8">No commission detail</td>
                            </tr>
                        </tbody>

                        <tbody v-else>
                            <template v-for="(commi, key) in filteredData" :key="key">
                            <tr :class="commi.status === 'C' ? 'bg-orange': ''" >
                                <td>{{ key + 1 }}</td> 
                                <td>{{ commi.tacl }}</td>
                                <td>{{ commi.mortgageCode }}</td>
                                <td>{{ commi.province }}</td>
                                <td>{{ commi.agent }}</td>
                                <td>{{ formatDecimal(commi.grossAmount) }}</td>
                                <td>{{ formatDecimal(commi.bonus) }}</td>
                                <td>{{ commi.commissionBy }}</td>
                                <td>{{ formatDecimal(commi.flatFee) }}</td>
                                <td>{{ commi.commissionValue }}</td>
                                <td v-if="commi.status === 'C'"><i class="bi bi-info-circle" v-tooltip="recalculationStatusMessage"></i></td>
                                <td v-else></td>
                            </tr>
                            </template>
                        </tbody>

                        <tbody>
                            <tr>
                                <td>Total</td> 
                                <td></td>
                                <td></td>
                                <td></td>
                                <td></td>
                                <td>{{ formatDecimal(totalCommission.grossAmount) }}</td>
                                <td>{{ formatDecimal(totalCommission.bonus) }}</td>
                                <td></td>
                                <td>{{ formatDecimal(totalCommission.flatFee) }}</td>
                                <td>{{ formatDecimal(totalCommission.commissionValue) }}</td>
                                <td></td>
                            </tr>
                        </tbody>

                    </table>
                </div>

                <div class="modal-footer">
                    <button
                        class="btn btn-outline-dark"
                        type="button"
                        @click="hideModal(modalId)"
                    >
                        <i class="bi-x-lg me-1"></i>Close
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>

<script>
import { util } from "../../mixins/util";
import VTooltip from "v-tooltip";

export default {
    mixins: [util],
    props: {
        commission: {
            type: Object,
            required: true,
        },
        modalId: String,
    },
    emits: ["refresh"],
    components: {VTooltip},
    data() {
        return {
            data: [],
            event: "",
            dialogMessage: "",
            commissions: [],
            totalCommission: {
                    grossAmount: 0,
                    bonus: 0,
                    flatFee: 0,
                    commissionValue: 0
                },
            recalculationStatusMessage:'Calculation was changed for this record',
            search: '',
            currentSort: 'mortgageCode',
            currentSortDir: 'bi-sort-up',
        }
    },
    mounted() {
        this.getData()
    },
    computed: {
        filteredData() {
            var search = this.search && this.search.toLowerCase()
            var data   = this.commissions
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
    watch: {
        // Watch for changes in the 'commission' prop
        commission: {
            handler(newValue, oldValue) {
                // Call the 'getData()' function whenever the 'commission' prop changes
                this.getData();
            },
            deep: true, // Watch for nested property changes
        },
    },
    methods: {
        getData: function () {
            this.showPreLoader()

            let referenceDate = new Date(this.commission.referenceDate);

            let data = {
                referenceDate: referenceDate,
                group: this.commission.group,
                cmsTypeId: this.commission.id,
                cmsDetailId: this.commission.cmsDetailId,
                agentName: this.commission.agentName,
                amountTotal: this.commission.amount,
            };

            this.axios({
                method: "post",
                url: "web/cms/summary-commission-approval",
                data: data,
            })
            .then((response) => {
                if (this.checkApiResponse(response)) {
                    this.commissions = response.data.data;
                    this.totals(this.commissions);
                } else {
                    this.alertMessage = response.data.message;
                    this.showAlert(response.data.status);
                }
            })
            .catch((error) => {
                this.alertMessage = error;
                this.showAlert("error");
            })
            .finally(() => {
                this.hidePreLoader();
            })
        },
        totals: function (commissions) {
            this.totalCommission = {
                grossAmount: 0,
                bonus: 0,
                flatFee: 0,
                commissionValue: 0
            };

            commissions.forEach(commission => {
                this.totalCommission.grossAmount += Number(commission.grossAmount);
                this.totalCommission.bonus += Number(commission.bonus);
                this.totalCommission.flatFee += Number(commission.flatFee);
                this.totalCommission.commissionValue += Number(commission.commissionValue);
            });
        }      
    },
};
</script>
