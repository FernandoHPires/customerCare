<template>
    <div class="modal fade" :id="modalId" data-coreui-keyboard="false" tabindex="-1" style="display: none;">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">File Summary</h5>
                    <button type="button" class="btn-close" @click="hideModal(modalId)" aria-label="Close"></button>
                </div>

                <div class="modal-body">
                    <div class="card">
                        <div class="card-header">
                            {{ summary.companyName }}
                        </div>

                        <div class="card-body">
                            <div class="pb-2">
                                <label>Reference Date:</label><strong class="ps-1">{{ formatPhpDate(summary.referenceDate) }}</strong>
                            </div>

                            <div class="row">
                                <div class="col-6">
                                    <div class="card">
                                        <div class="card-body">
                                            <label>Total Payments</label>
                                            <br>
                                            <h3>{{ formatNumber(summary.totalPaymentsCount) }}</h3>
                                        </div>
                                    </div>
                                </div>

                                <div class="col-6">
                                    <div class="card">
                                        <div class="card-body">
                                            <label>Total Payments Amount</label>
                                            <br>
                                            <h3>{{ formatCurrency(summary.totalPaymentsAmount) }}</h3>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>


<script>
import { util } from '../../mixins/util'

export default {
    mixins: [util],
    emits: ['events'],
    props: ['summary'],
    data() {
        return {
            modalId: 'papFileSummary'
        }
    },
    methods: {

    }
}
</script>