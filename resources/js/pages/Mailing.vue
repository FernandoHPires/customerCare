<template>
    <div style="overflow-x: hidden;">
        <div class="text-end mb-3">
            <button 
                class="btn btn-success" 
                @click="addMailing()">
                <i class="bi bi-plus-lg me-1"></i>Add Mailing
            </button>
        </div>

        <div v-if="mailings.length == 0">
            <div class="alert alert-info">
                No mailings found
            </div>
        </div>

        <div v-else class="card mb-2" v-for="(mailing, key) in mailings" :key="key">
            <div class="card-header">
                <div class="d-flex">
                    <div>
                        Mailing #{{ key + 1 }}
                    </div>

                    <div class="ms-auto">
                        <button class="btn btn-outline-danger" @click="confirmDestroy(mailing)">
                            <i class="bi bi-trash me-1"></i>Delete
                        </button>
                    </div>
                </div>
            </div>

            <div class="card-body">
                <div class="d-flex align-items-end mb-2">
                    <div class="me-2">
                        <label>Type</label>
                        <select v-model="mailing.type" class="form-select">
                            <option value="Mailing">Mailing</option>
                            <option value="Previous">Previous</option>
                        </select>
                    </div>

                    <div class="me-2">
                        <label>Recipient</label>
                        <select v-model="mailing.recipients" class="form-select">
                            <option v-for="(e, k) in recipients" :value="e.id" :key="k">{{ e.name }}</option>
                        </select>
                    </div>
                </div>

                <div class="d-flex align-items-end mb-2">
                    <div class="me-2">
                        <label>Unit Number</label>
                        <div class="input-group">
                            <input class="form-control" type="text" v-model="mailing.unitNumber">
                        </div>
                    </div>

                    <div class="me-2">
                        <label>Unit Type</label>
                        <select v-model="mailing.unitType" class="form-select">
                            <option v-for="(e, k) in unitTypes" :value="e.id" :key="k">{{ e.name }}</option>
                        </select>
                    </div>

                    <div class="me-2">
                        <label>Street Number</label>
                        <div class="input-group">
                            <input class="form-control" type="text" v-model="mailing.streetNumber">
                        </div>
                    </div>

                    <div class="me-2">
                        <label>Street Name</label>
                        <div class="input-group">
                            <input class="form-control" type="text" v-model="mailing.streetName">
                        </div>
                    </div>

                    <div class="me-2">
                        <label>Street Type</label>
                        <select v-model="mailing.streetType" class="form-select">
                            <option v-for="(e, k) in streetTypes" :value="e.id" :key="k">{{ e.name }}</option>
                        </select>
                    </div>
                </div>

                <div class="d-flex align-items-end mb-2">
                    <div class="me-2">
                        <label>Direction</label>
                        <select v-model="mailing.streetDirection" class="form-select">
                            <option v-for="(e, k) in directions" :value="e.id" :key="k">{{ e.name }}</option>
                        </select>
                    </div>

                    <div class="me-2">
                        <label>City</label>
                        <div class="input-group">
                            <input class="form-control" type="text" v-model="mailing.city">
                        </div>
                    </div>

                    <div class="form-group me-2">
                        <label>Province</label>
                        <select v-model="mailing.province" class="form-select">
                            <option v-for="(e, k) in provinces" :value="e.id" :key="k">{{ e.name }}</option>
                        </select>
                    </div>

                    <div class="form-group me-2">
                        <label>Postal Code</label>
                        <div class="input-group">
                            <input class="form-control" type="text" v-model="mailing.postalCode">
                        </div>
                    </div>
                </div>

                <div class="d-flex align-items-end row">
                    <div class="form-group me-2 col-md-5">
                        <label>Other</label>
                        <div class="input-group">
                            <textarea class="form-control" v-model="mailing.other" rows="5"></textarea>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="text-center mt-3" v-if="mailings.length > 0">
            <button class="btn btn-primary" @click="save()">
                <i class="bi bi-save me-1"></i>Save
            </button>
        </div>
    </div>

    <ConfirmationDialog
        :event="event"
        :message="dialogMessage"
        type="danger"
        :parentModalId="modalId"
        :key="modalId"
        @return="destroy"
    />
</template>

<script>
import { util } from '../mixins/util'
import ConfirmationDialog from '../components/ConfirmationDialog'

export default {
    mixins: [util],
    emits: ['events'],
    components: { ConfirmationDialog },
    data() {
        return {
            mailings: [],
            mailing: {},
            event: '',
            dialogMessage: '',
            modalId: 'mailing',
            recipients: [],
            unitTypes: [
                { id: 'Apt', name: 'Apartment' },
                { id: 'Lease', name: 'Lease' },
                { id: 'M/H', name: 'M/H' },
                { id: 'T/H', name: 'T/H' },
                { id: 'NA', name: 'N/A' },
            ],
            streetTypes: [
                { id: 'AVE', name: 'AVE' },
                { id: 'BLVD', name: 'BLVD' },
                { id: 'CRES', name: 'CRES' },
                { id: 'CRT', name: 'CRT' },
                { id: 'DR', name: 'DR' },
                { id: 'HWY', name: 'HWY' },
                { id: 'LANE', name: 'LANE' },
                { id: 'PL', name: 'PL' },
                { id: 'RANG', name: 'RANG' },
                { id: 'RD', name: 'RD' },
                { id: 'ST', name: 'ST' },
                { id: 'WAY', name: 'WAY' },
            ],
            directions: [
                { id: 'N', name: 'N' },
                { id: 'NE', name: 'NE' },
                { id: 'NW', name: 'NW' },
                { id: 'S', name: 'S' },
                { id: 'SE', name: 'SE' },
                { id: 'SW', name: 'SW' },
                { id: 'E', name: 'E' },
                { id: 'W', name: 'W' },
                { id: 'N/A', name: 'N/A' },
            ],
            provinces: [
                { id: 'AB', name: 'Alberta' },
                { id: 'BC', name: 'British Columbia' },
                { id: 'MB', name: 'Manitoba' },
                { id: 'NB', name: 'New Brunswick' },
                { id: 'NL', name: 'Newfoundland and Labrador' },
                { id: 'NT', name: 'Northwest Territories' },
                { id: 'NS', name: 'Nova Scotia' },
                { id: 'NU', name: 'Nunavut' },
                { id: 'ON', name: 'Ontario' },
                { id: 'PE', name: 'Prince Edward Island' },
                { id: 'QC', name: 'Quebec' },
                { id: 'SK', name: 'Saskatchewan' },
                { id: 'YT', name: 'Yukon' },
            ]
        }
    },
    mounted() {
        this.getData()
        this.getTitleHolders()
    },
    methods: {
        addMailing: function() {
            this.mailings.push({
                id: 0,
                type: '',
                recipients: '',
                unitNumber: '',
                unitType: '',
                streetNumber: '',
                streetName: '',
                streetType: '',
                streetDirection: '',
                city: '',
                province: '',
                postalCode: '',
                other: '',
            })
        },
        confirmDestroy: function(mailing) {
            this.mailing = mailing
            this.dialogMessage = 'Are you sure you want to delete this mailing?'
            this.showModal('confirmationDialog' + this.modalId)
        },
        destroy: function(event, returnMessage) {
            if(returnMessage !== 'confirmed') {
                return
            }

            this.showPreLoader()

            this.axios.delete(
                '/web/mailings/' + this.mailing.id
            )
            .then(response => {
                if(this.checkApiResponse(response)) {
                    this.getData()
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
        save: function() {
            this.showPreLoader()

            let data = {
                opportunityId: this.$route.params.opportunityId,
                mailings: this.mailings
            }

            this.axios.post(
                '/web/mailings',
                data
            )
            .then(response => {
                if(this.checkApiResponse(response)) {
                    this.getData()
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
        getTitleHolders: function() {
            this.showPreLoader()

            let data = {
                opportunityId: this.$route.params.opportunityId
            }

            this.axios.get(
                '/web/mailings/title-holders',
                {params: data}
            )
            .then(response => {
                if(this.checkApiResponse(response)) {
                    this.recipients = response.data.data
                } else {
                    this.alertMessage = response.data.message;
                    this.showAlert(response.data.status);
                }
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

            let data = {
                opportunityId: this.$route.params.opportunityId
            }

            this.axios.get(
                '/web/mailings',
                {params: data}
            )
            .then(response => {
                if(this.checkApiResponse(response)) {
                    this.mailings = response.data.data
                } else {
                    this.alertMessage = response.data.message;
                    this.showAlert(response.data.status);
                }
            })
            .catch(error => {
                this.alertMessage = error
                this.showAlert('error')
            })
            .finally(() => {
                this.hidePreLoader()
            })
        }
    }
}
</script>
