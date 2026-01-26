<template>
    <div class="modal" :id="modalId" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Appraisal Update</h5>
                    <button type="button" class="btn-close" data-coreui-dismiss="modal" aria-label="Close"></button>
                </div>

                <div class="modal-body">
                    <template v-if="stage == 1">
                        <div class="row">
                            <div class="form-group">
                                <label class="form-label">Date Ordered</label>
                                <input type="date" class="form-control" v-model="required">
                            </div>
                        </div>
                    </template>
                    
                    <template v-if="stage === 2">
                        <div class="row">
                            <div class="col-4">
                                <label for="" class="form-label">Date Ordered</label>
                                <input v-model="required" type="date" class="form-control" disabled>
                            </div>
                            <div class="col-4">
                                <label for="" class="form-label">Report Date</label>
                                <input v-model="received" type="date" class="form-control">
                            </div>
                            <div class="col-4">
                                <label for="" class="form-label">Property Value</label>
                                <input v-model="appraisalValue" type="number" class="form-control">
                            </div>
                        </div>
                    </template>
                </div>

                <div class="modal-footer">
                    <button class="btn btn-outline-dark" type="button" data-coreui-dismiss="modal">
                        <i class="bi-x-lg me-1"></i>Close
                    </button>
                    <button class="btn btn-success" type="button" @click="save()">
                        <i class="bi-save me-1"></i>Save
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>

<script>
import { util } from '../../mixins/util'

export default {
    mixins: [util],
    emits: ['refresh'],
    props: ['propertyId', 'refreshCount'],
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
            modalId: 'appraisalDateModal',
            presentDate: new Date(),
            id: '',
            applicationId: '',
            required: '',
            received: '',
            appraisalValue: null,
            stage: '',
        }
    },
    methods: {
        closeModal: function(modalId) {
            this.resetData()
            this.hideModal(modalId)
        },
        resetData: function() {
            this.id = ''
            this.applicationId = ''
            this.required = ''
            this.received = ''
            this.appraisalValue = null
            this.stage = ''
        },
        save: function() {
            this.validations = []
            if(this.stage === 1 && (this.required === null || this.required === '' || this.required === '0000-00-00')) {
                this.validations.push('Date Ordered is required');
            }

            if(this.stage === 2) {
                if(this.required === null || this.required === '' || this.required === '0000-00-00') {
                    this.validations.push('Date Ordered is required')
                }
                if(this.received === null || this.received === '' || this.received === '0000-00-00') {
                    this.validations.push('Report Date is required')
                }
                if(this.appraisalValue === null || this.appraisalValue === "" || this.appraisalValue === 0) {
                    this.validations.push('Property Value is required')
                }
            }

            if(this.validations.length > 0) {
                this.alertMessage = this.validations.join('<br>')
                this.showAlert('error')
                return
            }

            this.showPreLoader()

            let data = {
                id: this.id,
                applicationId: this.applicationId,
                propertyId: this.propertyId,
                required: this.required,
                received: this.received,
                appraisalValue: this.appraisalValue,
                stage: this.stage,
            }

            this.axios({
                method: 'post',
                url: '/web/contact-center/appraisal-date',
                data: data,
            })
            .then(response => {
                if(this.checkApiResponse(response)) {
                    this.$emit('refresh')
                    this.closeModal(this.modalId)
                }
                this.alertMessage = response.data.message
                this.showAlert(response.data.status)
            })
            .catch(error => {
                this.alertMessage = error
                this.showAlert('error')
            })
            .finally(() => {
                this.hidePreLoader()
            })
        },
        getData: function() {

            this.showPreLoader()

            this.axios({
                method: 'get',
                url: '/web/contact-center/appraisal-date/' + this.propertyId
            })
            .then(response => {
                this.id = response.data.data.id
                this.applicationId = response.data.data.applicationId
                this.required = response.data.data.required
                this.received = response.data.data.received
                this.appraisalValue = response.data.data.appraisalValue
                this.stage = response.data.data.stage
            })
            .catch(error => {
                this.alertMessage = error
                this.showAlert("error")
            })
            .finally(() => {
                this.hidePreLoader()
            })
        },
    }
}
</script>
<style lang="scss" scoped>
.form-label {
    margin-top: 0.5rem;
    margin-bottom: 0px;
}
</style>'