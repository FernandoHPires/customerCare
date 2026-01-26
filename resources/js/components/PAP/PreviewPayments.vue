<template>
    <div class="modal fade" :id="modalId" data-coreui-keyboard="false" tabindex="-1" style="display: none;">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Preview Mortgage Payments</h5>
                    <button type="button" class="btn-close" @click="hideModal(modalId)" aria-label="Close"></button>
                </div>

                <div class="modal-body">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Client</th>
                                <th>Mortgage Code</th>
                                <th>Due Date</th>
                                <th>Amount</th>
                                <th>Bank</th>
                                <th>Transit</th>
                                <th>Account</th>
                            </tr>
                        </thead>

                        <tbody v-if="payments.length > 0">
                            <tr v-for="(payment, key) in payments" :key="key">
                                <td>{{ payment.clientName }}</td>
                                <td>{{ payment.mortgageCode }}</td>
                                <td>{{ formatPhpDate(payment.dueDate) }}</td>
                                <td>{{ formatDecimal(payment.amount) }}</td>
                                <td>{{ payment.bank }}</td>
                                <td>{{ payment.transit }}</td>
                                <td>{{ payment.account }}</td>
                            </tr>
                        </tbody>

                        <tbody v-else>
                            <tr>
                                <td colspan="7">No payments</td>
                            </tr>
                        </tbody>
                    </table>
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
    props: ['payments'],
    data() {
        return {
            modalId: 'papPreviewPayments'
        }
    },
    methods: {

    }
}
</script>
