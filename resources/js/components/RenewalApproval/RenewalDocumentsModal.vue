<template>
    <div class="modal fade" :id="modalId" tabindex="-1">
        <div class="modal-dialog modal-xl">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Renewal Documents</h5>
                    <button type="button" class="btn-close" data-coreui-dismiss="modal" aria-label="Close"></button>
                </div>
                
                <div class="modal-body">
                    <div class="card">
                        <div class="card-header d-flex justify-content-end align-items-end">
                            <button 
                                class="btn btn-outline-primary" 
                                @click="viewDocuments()">
                                <i class="bi bi-file-pdf me-1"></i>View Documents
                            </button>
                        </div>

                        <div class="card-body">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Document</th>
                                        <th>Sent</th>
                                        <!-- <th>Stage</th>
                                        <th>Stage Date</th> -->
                                        <th></th>
                                    </tr>
                                </thead>

                                <tbody v-if="documents.length == 0">
                                    <tr>
                                        <td colspan="100%">No documents</td>
                                    </tr>
                                </tbody>
                                
                                <tbody>
                                    <tr v-for="(document, key) in documents" :key="key">
                                        <td>{{ document.documentName }}</td>
                                        <td>{{ documentSentDate }}</td>
                                        <!-- <td>{{ document.docSentAt }}</td> -->
                                        <!-- <td>{{ document.stage }}</td>
                                        <td>{{ document.stageDate }}</td> -->
                                        <td class="nowrap text-end">
                                            <button
                                                type="button"
                                                class="btn btn-secondary me-2"
                                                @click="recreateDocuments(document)"
                                            >
                                                <i class="bi bi-gear-wide-connected me-1"></i>Recreate
                                            </button>

                                            <!-- <button
                                                type="button"
                                                class="btn btn-success"
                                                :disabled="document.applicationDocumentId===0 || brokerApprovalStatus !== 'A'"
                                                @click="selectApplicantsEmails(document)"
                                            >
                                                <i class="bi bi-send me-1"></i>Send
                                            </button> -->

                                            <button
                                                type="button"
                                                class="btn btn-success me-2"
                                                :disabled="document.applicationDocumentId===0 || brokerApprovalStatus !== 'A'"
                                                @click="openMail()"
                                            >
                                                <i class="bi bi-send me-1"></i>Send
                                            </button>

                                            <button
                                                type="button"
                                                class="btn btn-secondary"
                                                :disabled="document.applicationDocumentId===0 || brokerApprovalStatus !== 'A'"
                                                @click="sentDocumentSentDate()"
                                            >
                                                <i class="bi bi-calendar-date me-1"></i>Docs Sent
                                            </button>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- <applicants-email-selector
        :opportunityId="applicationId"
        :refreshCount="refreshCountApp"
        @return="mergeAndSendOnReturn">
    </applicants-email-selector> -->

</template>

<script>
import { util } from '../../mixins/util'
// import ApplicantsEmailSelector from '../../components/modals/ApplicantsEmailSelector.vue'

export default {
    mixins: [util],
    props: ['applicationId', 'mortgageId', 'renewalApprovalId', 'refreshCount', 'companyId', 'brokerApprovalStatus', 'originationCompanyFullName', 'termDueDate', 'borrowerName', 'mortgageCode', 'selectedMortgageRenewalId'],
    emits: [ "events" ],
    // components: {ApplicantsEmailSelector},    
    watch: {
        refreshCount: {
            handler(newValue, oldValue) {
                this.documentSentDate = null;
                this.getDocuments();
                this.getDocumentSentDate();
            },
            deep: true
        }     
    }, 
    data () {
        return {
            modalId: 'createdDocsModal',
            documents: [],
            emailOptions: [],
            applicants: [],
            documentSelected: [],
            documentSentDate: null,
            // refreshCountApp: 0,
        }
    },
    methods: {
        getDocuments: function() {

            this.showPreLoader();

            let data = {
                applicationId: this.applicationId,
                mortgageId: this.mortgageId,
                renewalApprovalId: this.renewalApprovalId
            };

            this.axios
                .get("/web/renewals/documents", { params: data })
                .then((response) => {
                    if (this.checkApiResponse(response)) {
                        this.documents = response.data.data;          
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
        viewDocuments: function () {
            // Sequence Application
            if(this.companyId == 701) {
                window.open("https://amurfinancialgroup.sharepoint.com/sites/appdocument-dev/Shared%20Documents/SQCTACL/" + this.applicationId, "_blank");
            // Alpine Application
            } else {
                window.open("https://amurfinancialgroup.sharepoint.com/sites/appdocument-dev/Shared%20Documents/ACLTACL/" + this.applicationId, "_blank");
            }
        },
        recreateDocuments: function(document) {

            this.showPreLoader();

            let data = {
                applicationDocumentId : document.applicationDocumentId,
                documentId: document.documentId,
                applicationId: this.applicationId,
                mortgageId: this.mortgageId
            }; 

            this.axios
                .post("web/renewals/document/recreate", data)
                .then((response) => {
                    if (this.checkApiResponse(response)) {
                        this.alertMessage = 'Documents recreated successfully!';
                        this.showAlert("success");
                        this.getDocuments();
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
        sendDocuments: function(document, applicants) {

            this.showPreLoader();

            let data = {
                applicationDocumentId : document.applicationDocumentId,
                documentId: document.documentId,
                applicationId: this.applicationId,
                mortgageId: this.mortgageId,
                applicants: applicants
            };

            this.axios
                .post("web/renewals/document/send", data)
                .then((response) => {
                    if (this.checkApiResponse(response)) {
                        this.alertMessage = 'Documents sent successfully!';
                        this.showAlert("success");
                        this.getDocuments();
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
        sentDocumentSentDate: function() {

            this.showPreLoader();

            let docsData = {
                mortgageId: this.mortgageId,
                renewalId: this.selectedMortgageRenewalId
            };


            this.axios
            .put("web/renewals/documents/date", docsData)
            .then((response) => {
                if (this.checkApiResponse(response)) {
                    this.getDocumentSentDate();
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
        getDocumentSentDate: function() {

            this.showPreLoader();

            let docsData = {
                mortgageId: this.mortgageId,
                renewalId: this.selectedMortgageRenewalId
            };


            this.axios
            .get("web/renewals/documents/date", { params: docsData })
            .then((response) => {
                if (this.checkApiResponse(response)) {
                    this.documentSentDate = response.data.data;   
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
        // selectApplicantsEmails: function(document) {
        //     this.documentSelected = document
        //     this.refreshCountApp++
        //     this.showModal('applicantsEmailSelector')
        // },
        // mergeAndSendOnReturn: function(applicants) {            
            
        //     this.applicants = applicants
        //     this.sendDocuments(this.documentSelected, this.applicants);
            
        // },
        openMail: function() {

            this.showPreLoader()

            let data = {
                opportunityId: this.applicationId
            }

            this.axios.get('/web/merge/applicants-email',
                {params: data}
            )
            .then(response => {

                this.emailOptions = response.data.data.emailOptions
                const emailOptionsArr = this.emailOptions.map(opt => opt.id);

                var toEmail = "";

                if(emailOptionsArr.length !== 0) {
                    toEmail = emailOptionsArr[0];
                }

                const email = encodeURIComponent(toEmail);
                const subject = encodeURIComponent(`Renewal Offer - #${this.applicationId}, ${this.mortgageCode}, ${this.borrowerName}`);
                const bodyText = `
                    Congratulations!! We are pleased to offer you a renewal by ${this.originationCompanyFullName}.

                    Your mortgage matures on ${this.termDueDate}.

                    Renewing your mortgage is quick and simple.  All you have to do is review, sign and return a copy of the following attachments along with a copy of the required documents listed below.

                    Review, sign and return:
                    • Renewal Agreement
                    • Pre-Authorized Debit Agreement (“PAD”).

                    Want to Sign it Electronically ?
                    Provide us with a valid e-mail address.     
                    
                    Additional required documents to send us:
                    • A void cheque for the PAD (or check off that you authorize use of the current void).
                    • Proof of valid home insurance
                    • Proof that Property Taxes are paid up to date for the current year (or proof of good standing if you’re on a payment plan).
                    • 1st  mortgage statement 
                    • Your monthly Strata/Condo Fees are current and up to date.

                    Making your monthly payments:
                    The PAD authorizes automatic payments to be withdrawn from your account and paid to the Lender. To ensure accuracy, we do require a new PAD form signed by bank account holders along with a copy of the void cheque.

                    How to send us your documents? Choose any method below:
                    • Provided we have a valid e-mail address for each applicant, we will send you the Renewal Documents via e-mail for quick paperless signing.
                    • Fax them to 1-604-581-2161.
                    • Scan or take visible pictures with your phone and e-mail them to renewal@amurgroup.ca
                    •  Mail them attention “Renewals” to our address listed below
                            13450 102nd Avenue, Suite 1900, Surrey, BC, V3T 2X2
                            
                    We will take care of everything else once we have received all the required documents and signed pages.  Please ensure that all three sections are completed and received in our office by no later than the maturity date of ${this.termDueDate}

                    If your mortgage is not renewed your mortgage will become due and payable at that time and this renewal offer will become null and void.
                                        
                    On behalf of the Alpine Credits family, we would like to take this opportunity to thank you for your continued loyalty and business and for allowing us to continue fulfilling your and your family’s financial needs.  If you have any questions about your mortgage, or the renewal agreement, feel free to contact our office at 1-800-587-2161.
                    PLEASE CONFIRM YOU HAVE RECEIVED THESE DOCUMENTS
                `;

                const body = encodeURIComponent(bodyText.trim());

                var mailto = `mailto:${email}?subject=${subject}&body=${body}`;
                if(emailOptionsArr.length > 1) {
                    const ccEmails = emailOptionsArr.slice(1).join(',');
                    const cc = encodeURIComponent(ccEmails);
                    mailto += `&cc=${cc}`;
                }

                const newWindow = window.open(mailto);

                if (!newWindow) {
                    this.alertMessage = "Failed to open Outlook, please contact IT support."
                    this.showAlert("error")
                }
                            
            })
            .catch(error => {
                console.log(error)
                this.alertMessage = error
                this.showAlert("error")
            })
            .finally(() => {
                this.hidePreLoader()
            })
        }
    }
}
</script>