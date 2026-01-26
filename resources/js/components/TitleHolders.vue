<template>
    <div class="modal" id="TitleHolders" tabindex="-1">
        <div class="modal-dialog modal-xl">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Edit Title Holders</h5>
                    <button type="button" class="btn-close" data-coreui-dismiss="modal" aria-label="Close"></button>
                </div>

                <div class="modal-body">
                    
                    <div class="col-md-12 mb-3">
                        <label class="fw-bold" for="application_id">Current Title Holders</label>
                        <input v-model="currentTitleHolders" type="text" class="form-control" v-tooltip="currentTitleHolders" readonly>
                    </div>

                    <div class="col-md-12">
                        <label class="fw-bold" for="followup_date">New Title Holders</label>
                        <input v-model="newTitleHolders" type="text" class="form-control" v-tooltip="newTitleHolders" readonly>
                    </div>

                    <div class="d-flex align-items-center">
                        <div class="me-auto"></div>
                        <button
                            class="btn btn-primary mt-3"
                            type="button"
                            v-tooltip="'Load Current Title Holders'"
                            @click="loadTitleHolders(currentTitleHolders)">
                            <i class="bi bi-arrow-clockwise"></i>
                            Load
                        </button>
                    </div>

                    <div class="row mt-3">
                        <div class="col-6">
                            <div class="card">
                                <div class="card-header">Applicants</div>
                                <div class="card-body">
                                    <div class="d-flex">
                                        <table class="table table-hover mb-0">
                                            <thead>
                                                <tr>
                                                    <th>Name</th>
                                                    <th>Type</th>
                                                    <th></th>
                                                </tr>
                                            </thead>
                                            <tbody v-if="applicants.length == 0">
                                                <tr>
                                                    <td colspan="7">No Applicants</td>
                                                </tr>
                                            </tbody>
                                            <tbody v-else>
                                                <tr v-for="(applicant, key) in applicants" :key="key">
                                                    <td>{{ applicant.name }}</td>
                                                    <td>{{ applicant.type }}</td>
                                                    
                                                    <td class="text-end">
                                                        <button
                                                            type="button"
                                                            class="btn btn-success"
                                                            @click="transferDocToMerge(applicant.name, applicant.type)"
                                                            data-coreui-toggle="tooltip"
                                                            data-coreui-placement="top"
                                                            title="Transfer applicant to Title Holder">
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
                                <div class="card-header">Corporations</div>
                                <div class="card-body">
                                    <div class="d-flex">
                                        <table class="table table-hover mb-0">
                                            <tbody v-if="corporations.length == 0">
                                                <tr>
                                                    <td colspan="7">No Corporations</td>
                                                </tr>
                                            </tbody>
                                            <tbody v-else>
                                                <tr
                                                    v-for="(corporation, key) in corporations" :key="key">
                                                    <td>{{ corporation.name }}</td>
                                                    <td class="text-end">
                                                        <button
                                                            type="button"
                                                            class="btn btn-success"
                                                            @click="transferDocToMerge(corporation.name, corporation.type)"
                                                            data-coreui-toggle="tooltip"
                                                            data-coreui-placement="top"
                                                            title="Transfer Corporation to Title Holder">
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
                                <div class="card-header">Title Holders</div>

                                <div class="card-body p-0">
                                    <div class="list-group w-100">
                                        <draggable
                                            v-model="titleHolders"
                                            group="people"
                                            @start="drag = true"
                                            @end="console.log(titleHolders)"
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
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-dark" @click="hideModal(modalId)">
                        <i class="bi-x-lg me-1"></i>Close
                    </button>

                    <button type="button" class="btn btn-success" @click="saveTitleHolder()">
                        <i class="bi-save me-1"></i>Save
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>

<script>
import { util } from "../mixins/util"
import draggable from "vuedraggable";

export default {
    mixins: [util],
    props: ['applicationId', 'propertyId', 'refreshCount', 'thIndex', 'thType'],
    components: {
        draggable,
    },
    data() {
        return {
            modalId: 'TitleHolders',
            newTitleHolders: '',
            currentTitleHolders: '',
            applicants: [],
            corporations: [],
            titleHolders: [],
        };
    },
    watch: {
        refreshCount: {
            handler(newValue, oldValue) {
                this.getData()
            },
            deep: true
        },
        titleHolders: {
            handler(newValue) {
                this.newTitleHolders = newValue.map(x => x.name).join(', ')
            },
            deep: true
        }        
    },
    methods: {

        getData() {

            this.newTitleHolders = ''
            this.currentTitleHolders = ''
            this.applicants = []
            this.corporations = []
            this.titleHolders = []

            let data = {
                applicationId: this.applicationId,
                propertyId: this.propertyId
            }

            this.axios.get(
                '/web/contact-center/applicant-title-holder',
                {params: data}
            )
            .then(response => {
                this.applicants = response.data.data.applicants
                this.corporations = response.data.data.corporations
                this.currentTitleHolders = response.data.data.titleHolders
            })
            .catch(error => {
                this.alertMessage = error
                this.showAlert('error')
            })
        }, 
        transferDocToMerge: function (name, type) {
            if (!this.titleHolders.some((doc) => doc.name === name)) {
                const titleHolderToAdd = {
                    name: name,
                    type: type
                };
                this.titleHolders.push(titleHolderToAdd);
            } else {
                this.alertMessage = "Title Holder already exists in the list!";
                this.showAlert("error");
            }
        },
        remove: function (docName) {

            const index = this.titleHolders.findIndex(
                (doc) => doc.name === docName
            );
            if (index !== -1) {
                this.titleHolders.splice(index, 1);
            }
        },

        saveTitleHolder: function() {

            if (this.newTitleHolders === '') {
                this.alertMessage = "Please select at least one title holder!"
                this.showAlert('error')
                return
            }

            this.hideModal(this.modalId)

            this.$emit('titleHoldersUpdated', {
                propertyId: this.propertyId,
                thIndex: this.thIndex,
                titleHolders: this.newTitleHolders,
                thType: this.thType
            });

        },
        loadTitleHolders(currentTitleHolders) {
            if (!currentTitleHolders) return;

            const names = currentTitleHolders.split(',')
                .map(name => name.trim())
                .filter(name => name !== '');

            names.forEach(name => {
                const alreadyExists = this.titleHolders.some(holder => holder.name === name);
                if (!alreadyExists) {
                    this.titleHolders.push({
                        name: name,
                        type: 'Unknown' // ou '' se preferir
                    });
                }
            });
        }


        

    },
}
</script>
