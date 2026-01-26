<template>
    <div class="modal" :id="modalId" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Select the applicant's email</h5>
                    <button type="button" class="btn-close" data-coreui-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <table class="table mb-0">
                        <thead>
                            <tr>
                                <th class="table-header">Applicant</th>
                                <th class="table-header">Type</th>
                                <th class="table-header">Email</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="(row, k) in applicants" :key="k">
                                <td>{{ row.name }}</td>
                                <td>{{ row.type }}</td>
                                <td>
                                    <select v-model="row.email" class="form-select">
                                        <option v-for="row in emailOptions" :value="row.id" :key="row.id">{{ row.name }}</option>
                                    </select>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <div class="modal-footer">

                    <button class="btn btn-outline-dark" 
                        type="button" data-coreui-dismiss="modal">
                        <i class="bi-x-lg me-1"></i>
                        Close
                    </button>

                    <button class="btn btn-success" 
                        type="button" 
                        @click="sendEmail">
                        <i class="bi bi-envelope-arrow-up"></i>
                        Send
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
        components: { },
        props: ['opportunityId', 'refreshCount'],
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
                modalId: 'applicantsEmailSelector',
                applicants: [],
                emailOptions: [],

            }
        },
        methods: {
            getData: function() {

                console.log("getData called for ApplicantsEmailSelector"+ this.refreshCount)

                this.showPreLoader()

                let data = {
                    opportunityId: this.opportunityId
                }

                this.axios.get('/web/merge/applicants-email',
                    {params: data}
                )
                .then(response => {

                    this.applicants = response.data.data.applicants
                    this.emailOptions = response.data.data.emailOptions
                                
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
            sendEmail: function() {

                const missingEmail = this.applicants.find(applicant => !applicant.email);

                if (missingEmail) {

                    
                    this.alertMessage = `Please select an email for all applicants. Missing for: ${missingEmail.name}`;
                    this.showAlert("error");

                    return;

                } else {

                    this.hideModal(this.modalId);

                    this.$emit('return', this.applicants);
                }

                
            }
        }
    }
</script>