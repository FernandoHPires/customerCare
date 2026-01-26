<template>
    <div class="modal" :id="modalId" tabindex="-1">
        <div class="modal-dialog modal-xl">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Investor Tracking Summary</h5>
                    <button type="button" class="btn-close" data-coreui-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <table class="table">
                        <thead>
                            <tr>
                                <th class="table-header">LP Date</th>
                                <th class="table-header">Purchase</th>
                                <th class="table-header">Investor</th>
                                <th class="table-header">FM Committed</th>
                                <th class="table-header">Fund Manager</th>
                                <th class="table-header">Discount</th>
                                <th class="table-header">Price</th>
                                <th class="table-header">Yield</th>
                                <th class="table-header">Comment</th>
                                <th class="table-header">GM Committed</th>
                                <th class="table-header">General Manager</th>
                                <th class="table-header">Last Update</th>
                            </tr>
                        </thead>

                        <tbody>
                            <tr v-for="(it, key) in investorsTrackingData" :key="key">
                                <td class="table-body">{{ formatDate(it.lpDate) }}</td>
                                <td class="table-body">{{ formatDate(it.purchase) }}</td>
                                <td class="table-body">{{ it.investor }}</td>
                                <td class="table-body">{{ it.fmCommitted }}</td>
                                <td class="table-body">{{ it.fundManager }}</td>
                                <td class="table-body">{{ formatDecimal(it.discount) }}</td>
                                <td class="table-body">{{ formatDecimal(it.price) }}</td>
                                <td class="table-body">{{ formatDecimal(it.yield) }}%</td>
                                <td class="table-body">{{ it.comment }}</td>
                                <td class="table-body">{{ it.gmCommitted }}</td>
                                <td class="table-body">{{ it.generalManager }}</td>
                                <td class="table-body">{{ it.lastUpdate }}</td>                               
                            </tr>
                        </tbody>
                    </table>
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

import { quote } from '../../mixins/quote';
import { util } from '../../mixins/util'
import { DatePicker } from 'v-calendar'

export default {
    mixins: [util],
    emits: ['refresh'],
    components: { DatePicker },
    props: ['selectedQuote','applicationId','refreshCount'],
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
            modalId: 'investorTrackingModal',
            appraisalUpdate: [],
            presentDate: new Date(),
            investorsTrackingData: []
        }
    },
    methods: {
        getData: function() {            

            this.showPreLoader()

            let data = {
                savedQuoteId : this.selectedQuote.id,
                applicationId: this.applicationId
            }

            this.axios.get(
                '/web/contact-center/investor-tracking',
                {params: data}
            )
            .then(response => {

                this.investorsTrackingData = response.data.data
                            
            })
            .catch(error => {
                this.alertMessage = error
                this.showAlert("error")
            })
            .finally(() => {
                this.hidePreLoader()
            })
        },          
        closeModal: function(modalId) {
            this.resetData()
            this.hideModal(modalId)
        },
        resetData: function() {
            this.appraisalUpdate = []
        }
    }
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
        vertical-align: middle;
        padding-right: 1rem;
        padding-top: 0;
        border: none;
        font-size: 0.875em;
        font-weight: normal;
    }
.modal-xl {
    max-width: 100% !important;
    width: 100% !important;
}


</style>'