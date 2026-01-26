<template>
    <div class="modal fade" :id="modalId" data-coreui-keyboard="false" tabindex="-1" style="display: none;">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Send Docs</h5>
                    <button type="button" class="btn-close" data-coreui-dismiss="modal" aria-label="Close"></button>
                </div>
                
                <div class="modal-body">
                    <div class = "p-3 text-center">
                        <p class = "p-1">Are you sure you want to send the document below to the borrower?</p>
                        <div>
                            <div class = "p-1" v-if="documents.length == 0">
                                No Documents found.
                            </div>

                            <div v-else class="d-inline-block text-start">
                                <div 
                                    class="d-flex align-items-center p-1" 
                                    v-for="(document, index) in documents" 
                                    :key="index"
                                >
                                    <label>
                                        <input type="checkbox" v-model="document.checked" />
                                        {{ document.documentName }}
                                    </label>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-dark" data-coreui-dismiss="modal">
                        <i class="bi-save me-1"></i>Close
                    </button>
                    
                    <button type="button" class="btn btn-success" @click="sendDocuments()">
                        <i class="bi-save me-1"></i>Send
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
        applicationId: {
            type: Number,
            default: null
        }
    },
    emits: [ "events" ],
    watch: {
    },
    data () {
        return {
            modalId: 'sendDocsModal',
            documents: []
        }
    },
    mounted() {
        this.$nextTick(() => {
            const modalEl = document.getElementById(this.modalId);
            if (modalEl) {
                modalEl.addEventListener('shown.coreui.modal', () => {
                    this.getDocuments();
                });
            }
        });
    },
    methods: {
        getDocuments: function() {
            this.showPreLoader();

            let data = {
                applicationId: this.applicationId
            };

            this.axios
                .get("/web/renewals/documents", { params: data })
                .then((response) => {
                    if (this.checkApiResponse(response)) {
                        this.documents = response.data.data.map(document => ({
                            ...document,         
                            checked: false     
                        }))
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
                });
        },
        sendDocuments: function() {
            const checkedDocs = this.documents
                .filter(document => document.checked)
                .map(document => document.documentName)

            if (checkedDocs.length > 0) {

                let data = {
                    toAddress: "adam@amurgroup.ca",
                    subject: "Send Docs",
                    bodyType: "html",
                    body: "<p>" + checkedDocs.join(', ') + "</p>",
                }

                this.showPreLoader()

                this.axios
                    .post('web/renewals/email',data)
                    .then(response => {
                        if(this.checkApiResponse(response)) {
                            this.alertMessage = "Email Sent"
                            this.showAlert("success")
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
                        this.hideModal(this.modalId)
                    })
            } else {
                this.alertMessage = "No Documents Selected"
                this.showAlert('error')
            }
        }
    }
}
</script>