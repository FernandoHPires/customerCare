<template>
    <div class="mb-4" style="overflow-x: hidden;">
        <div class="row">
            <div class="mb-3">
                <div class="d-flex">
                    <div class="me-auto"></div>

                    <div class="me-2">
                        <button 
                            class="btn btn-outline-primary" 
                            @click="viewDocuments()">
                            <i class="bi bi-file-pdf"></i>
                            View Documents
                        </button>
                    </div>

                    <div class="me-2">
                        <button 
                            class="btn btn-primary" 
                            @click="mergeDocuments('merge')">
                            <i class="bi bi-intersect"></i>
                            Merge Documents
                        </button>
                    </div>

                    <div>
                        <button 
                            class="btn btn-success"
                            @click="applicantsEmail()">
                            <i class="bi bi-envelope-arrow-up"></i>
                            Merge Documents and Send
                        </button>
                    </div>
                </div>
            </div>

            <div class="col-6">
                <div class="card">
                    <div class="card-header">Documents Available</div>
                    <div class="card-body">
                        <div class="d-flex">
                            <table class="table table-hover mb-0">
                                <tbody v-if="documents.length == 0">
                                    <tr>
                                        <td colspan="7">No documents</td>
                                    </tr>
                                </tbody>
                                <tbody v-else>
                                    <tr v-for="(document, key) in documents" :key="key">
                                        <td>{{ document.name }}</td>
                                        <td class="text-end">
                                            <button
                                                type="button"
                                                class="btn btn-success"
                                                @click="transferDocToMerge(document.name, document.serverRelativeUrl)"
                                                data-coreui-toggle="tooltip"
                                                data-coreui-placement="top"
                                                title="Transfer document to merge">
                                                <i class="bi-box-arrow-right"></i>
                                            </button>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <div class="card mt-3">
                    <div class="card-header">Other Documents</div>
                    <div class="card-body">
                        <div class="d-flex">
                            <table class="table table-hover mb-0">
                                <tbody v-if="otherDocuments.length == 0">
                                    <tr>
                                        <td colspan="7">No documents</td>
                                    </tr>
                                </tbody>
                                <tbody v-else>
                                    <tr
                                        v-for="(otherDocument, key) in otherDocuments" :key="key">
                                        <td>{{ otherDocument.name }}</td>
                                        <td class="text-end">
                                            <button
                                                type="button"
                                                class="btn btn-success"
                                                @click="transferOtherDocToMerge(otherDocument.name, otherDocument.serverRelativeUrl)"
                                                data-coreui-toggle="tooltip"
                                                data-coreui-placement="top"
                                                title="Transfer document to merge">
                                                <i class="bi-box-arrow-right"></i>
                                            </button>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-6">
                <div class="card">
                    <div class="card-header">Documents to Merge</div>

                    <div class="card-body p-0">
                        <div class="list-group w-100">
                            <draggable
                                v-model="docsToMerge"
                                group="people"
                                @start="drag = true"
                                @end="console.log(docsToMerge)"
                                item-key="id"
                            >
                                <template #item="{ element }">
                                    <div class="list-group-item list-group-item-action">
                                        <div class="d-flex">
                                            <div class="me-2">
                                                <a href="#"><i class="bi-grip-vertical me-1"></i></a>
                                            </div>

                                            <div>
                                                {{ element.name }}
                                            </div>

                                            <div class="ms-auto"></div>

                                            <div>
                                                <button type="button" class="btn btn-outline-danger" @click="remove(element.name)">
                                                    <i class="bi-trash"></i>
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </template>
                            </draggable>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <applicants-email-selector
        :opportunityId="this.$route.params.opportunityId"
        :refreshCount="refreshCount"
        @return="mergeAndSendOnReturn">
    </applicants-email-selector>

</template>

<script>
import draggable from "vuedraggable";
import { util } from "../mixins/util";
import ApplicantsEmailSelector from '../components/modals/ApplicantsEmailSelector.vue'

export default {
    mixins: [util],
    emits: ["events"],
    components: {
        draggable, ApplicantsEmailSelector
    },
    data() {
        return {
            documents: [],
            otherDocuments: [],
            docsToMerge: [],
            refreshCount: 0,
            applicants: [],
        };
    },
    async mounted() {
        this.showPreLoader();

        try {
            await this.getData();
            await this.getOtherDocs();
        } catch (error) {
            this.alertMessage = error;
            this.showAlert("error");
        } finally {
            this.hidePreLoader();
        }
    },    
    methods: {
        getData: async function () {

            this.showPreLoader();

            let data = {
                opportunityId: this.$route.params.opportunityId
            };

            this.axios
                .get("/web/merge/documents", { params: data })
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

        getOtherDocs: async function () {

            this.showPreLoader();

            let data = {
                opportunityId: this.$route.params.opportunityId
            };

            this.axios
                .get("/web/merge/other-documents", { params: data })
                .then((response) => {
                    if (this.checkApiResponse(response)) {
                        this.otherDocuments = response.data.data;
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
                }
            );
        },

        transferDocToMerge: function (docName, serverRelativeUrl) {

            if (!this.docsToMerge.some((doc) => doc.name === docName)) {
                const selectedDocument = this.documents.find(
                    (doc) => doc.name === docName
                );
                if (selectedDocument) {
                    const documentToAdd = {
                        name: docName,
                        serverRelativeUrl: serverRelativeUrl
                    };
                    this.docsToMerge.push(documentToAdd);
                } else {
                    this.alertMessage = "Document not found";
                    this.showAlert("error");
                }
            } else {
                this.alertMessage = "Document already exists in the list!";
                this.showAlert("error");
            }
        },

        transferOtherDocToMerge: function (docName, serverRelativeUrl) {

            if (!this.docsToMerge.some((doc) => doc.name === docName)) {
                const selectedDocument = this.otherDocuments.find(
                    (doc) => doc.name === docName
                );
                if (selectedDocument) {
                    const documentToAdd = {
                        name: docName,
                        serverRelativeUrl: serverRelativeUrl
                    };
                    this.docsToMerge.push(documentToAdd);
                } else {
                    this.alertMessage = "Document not found";
                    this.showAlert("error");
                }
            } else {
                this.alertMessage = "Document already exists in the list!";
                this.showAlert("error");
            }
        },

        remove: function (docName) {

            const index = this.docsToMerge.findIndex(
                (doc) => doc.name === docName
            );
            if (index !== -1) {
                this.docsToMerge.splice(index, 1);
            }
        },

        mergeDocuments: function (sendType) {

            if (this.docsToMerge.length === 0) {
                this.alertMessage = 'There are no documents to merge';
                this.showAlert('error');
            } else {

                this.showPreLoader();
                const opportunityId = this.$route.params.opportunityId;

                let data = {
                    opportunityId: this.$route.params.opportunityId,
                    docsToMerge: this.docsToMerge,
                    type: sendType,
                    applicants: this.applicants
                };

                this.axios.post(
                    "/web/merge/merge-documents", 
                    data
                )
                .then((response) => {
                    if (this.checkApiResponse(response)) {
                        this.alertMessage = response.data.message
                        this.showAlert(response.data.status);
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
            }
        },

        viewDocuments: function () {

            this.showPreLoader();
            const opportunityId = this.$route.params.opportunityId;

            let data = {
                opportunityId: this.$route.params.opportunityId,
            };

            this.axios.post(
                "/web/merge/view-documents", 
                data
            )
            .then((response) => {
                if (this.checkApiResponse(response)) {
                    const applicationId = response.data.data.applicationId;
                    const companyId = response.data.data.companyId;
                    // Sequence Application
                    if(companyId == 701) {
                        window.open("https://amurfinancialgroup.sharepoint.com/sites/appdocument/Shared%20Documents/SQCTACL/"+applicationId, "_blank");
                    // Alpine Application
                    } else {
                        //window.open("https://amurfinancialgroup.sharepoint.com/sites/appdocument-dev/Shared%20Documents/ACLTACL/"+applicationId, "_blank");
                        window.open("https://amurfinancialgroup.sharepoint.com/sites/appdocument/Shared%20Documents/ACLTACL/"+applicationId, "_blank");
                    }
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
        applicantsEmail: function () {
            this.refreshCount++
            this.showModal('applicantsEmailSelector')
        },
        mergeAndSendOnReturn: function(applicants) {
            
             this.applicants = applicants;

            this.mergeDocuments('mergeAndSend');
        },
    },
};
</script>
