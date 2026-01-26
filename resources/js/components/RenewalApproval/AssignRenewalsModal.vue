<template>
    <div class="modal fade" :id="modalId" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Assign Renewal</h5>
                    <button type="button" class="btn-close" data-coreui-dismiss="modal" aria-label="Close"></button>
                </div>
                
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="label-header">Agent</label>
                        <select id="user" v-model="brokerId" class="form-select">
                            <option disabled value="">Select an Agent</option>
                            <option
                                v-for="(broker, key) in brokers"
                                :key="key"
                                :value="broker.id"
                            >
                                {{ broker.fullName }}
                            </option>
                        </select>
                    </div>
                </div>

                <div class="modal-footer">
                    <button class="btn btn-outline-dark"
                        data-coreui-dismiss="modal">
                        <i class="bi-x-lg me-1"></i>Close
                    </button>
                    <button type="button" class="btn btn-success" @click="assignAgent()">
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
    props: { 
        selectedRenewalsId: {
            type: Array,
            default: () => []
        },
    },
    emits: [ "events" , "updateApprovedRenewals" ],
    watch: {
    },
    data () {
        return {
            modalId: 'assignRenewalsModal',
            brokers: [],
            brokerId: null
        }
    },
    mounted() {
        this.getBrokers()
    },
    methods: {
        getBrokers: function() {
            let groupIds = {
                groupId: 56 // Renewal Agents
            }
            
            this.axios
                .get('/web/users/group-users', {params: groupIds})
                .then((response) => {
                    if(this.checkApiResponse(response)) {
                        this.brokers = response.data.data
                    }
                })
                .catch((error) => {
                    this.brokers = []
                })
        },
        assignAgent: function() {
            if(!this.brokerId) {
                this.alertMessage = "Please select an agent"
                this.showAlert("error")
                return
            }

            let emailObj = {
                toAddress: ["adam@amurgroup.ca", "joy@amurgroup.ca"],
                subject: "Mortgage Renewal Agent Assigned",
                bodyType: "html",
            }

            let data = {
                selectedRenewalsId: this.selectedRenewalsId,
                brokerId: this.brokerId,
                emailObj: emailObj
            }

            this.showPreLoader()

            this.axios
                .put('web/renewals/assign-agents', data)
                .then(response => {
                    if(this.checkApiResponse(response)) {
                        this.$emit('updateApprovedRenewals')
                        this.brokerId = null
                    }
                    this.alertMessage = response.data.message
                    this.showAlert(response.data.status)
                    this.hideModal(this.modalId)
                })
                .catch(error => {
                    this.alertMessage = error
                    this.showAlert('error')
                })
                .finally(() => {
                    this.selectedRenewalsId = null
                    this.brokerId = null
                    this.hidePreLoader()
                })
        },
    }
}
</script>