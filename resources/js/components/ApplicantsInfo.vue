<template>
    <div class="row mb-2">
        <div class="col-5">
            <div class="card">
                <div class="card-body p-2">
                    <a href="#" @click="copyAppHeader()" class="fw-bold">
                        #{{ application.id }} {{ applicants[0]?.spouses[0]?.lastName }}, {{ applicants[0]?.spouses[0]?.firstName }}
                        <i class="bi bi-copy ms-1"></i>
                    </a>

                    <br>
                    <a href="#" @click="openMap(cleanedAddress)"><small class="muted">{{ cleanedAddress }}</small></a>
                    <br>
                    
                    <template v-if="application.newApplicationId && application.newApplicationId > 0">
                        <a class="small" href="#" @click.prevent="openNewApp">
                            <i class="bi bi-archive me-1"></i>New {{ application.companyAbbr }} App #{{ application.newApplicationId }}
                        </a>
                        <br>
                    </template>

                    <template v-if="application.oldApplicationId && application.oldApplicationId > 0">
                        <a class="small" href="#" @click.prevent="openOldApp">
                        <i class="bi bi-archive me-1"></i>Old {{ application.companyAbbr }} App #{{ application.oldApplicationId }}</a>
                    </template>
                </div>
            </div>
        </div>

        <div v-if="(salesJourney.updateSalesJourney && showSalesJourney && (application.companyId != 701))" class="col-4">
            <div class="card">
                <div class="card-body p-2 d-flex gap-2">
                    <div class="form-group flex-fill">
                        <label class="table-header">Referring Agent</label>
                        <select v-model="salesJourney.referringAgentId" class="form-select" 
                            @change="updateSalesJourney('referring_agent_id', salesJourney.referringAgentId, this.oldReferring)">
                            <option v-for="option in agentOptions" :key="option.id" :value="option.id">{{ option.name }}</option>
                        </select>
                    </div>

                    <div class="form-group flex-fill">
                        <label class="table-header">Broker</label>
                        <select v-model="salesJourney.brokerId" class="form-select"
                            @change="updateSalesJourney('broker_id', salesJourney.brokerId, this.oldBroker)">
                            <option v-for="option in agentOptions" :key="option.id" :value="option.id">{{ option.name }}</option>
                        </select>
                    </div>
                </div>
            </div>
        </div>

        <div v-else class="col-4"></div> 

        <div class="col-3 d-flex flex-column">
            <span v-if="alpineInterestString" class="text-danger fw-bold">
                {{ alpineInterestString }}
            </span>
            <div v-if="isSalesJourney && application.companyId != 701" class="d-flex ms-auto">
                <span class="fs-6 badge bg-success">Sales Journey is active</span>
            </div>
            <div v-else-if="application.companyId != 701" class="d-flex ms-auto">
                <span class="fs-6 badge bg-warning">Sales Journey is inactive</span>
            </div>
        </div>

    </div>
</template>

<script>
import { util } from '../mixins/util'
import { Clipboard } from "v-clipboard"

export default {
    directives: {
        clipboard: Clipboard
    },
    data() {
        return {
            oldReferring: '',
            oldBroker: '',
        };
    },     
    mixins: [util],
    props: {
        application: Object,
        applicants: Array,
        properties: Array,
        isSalesJourney: Boolean,
        agentOptions: Array,
        salesJourney: Object,
        showSalesJourney: {
            type: Boolean,
            default: false
        }
    },
    computed: {
        alpineInterestString() {
            const interests = this.properties
            .map((prop) => prop.alpineInterest)
            .filter((val) => val !== null && val !== undefined && val !== "" && Number(val) !== 100);

            if (!interests.length) {
                return "";
            }

            return `AS TO A ${interests.join(" & ")}% INTEREST`;
        },
        cleanedAddress() {
            const address = this.properties[0]?.fullAddress || '';
            return address.replace('&amp;', '&');
        }
    },
    watch: {
        'salesJourney.referringAgentId'(newVal, oldVal) {
            this.oldReferring = oldVal;
        },
        'salesJourney.brokerId'(newVal, oldVal) {
            this.oldBroker = oldVal;
        }
    },
    methods: {
        copyAppHeader() {
            var text = '#' + this.application.id + ' ' + this.applicants[0]?.spouses[0]?.lastName + ', ' + this.applicants[0]?.spouses[0]?.firstName + ' ' + this.properties[0]?.fullAddress
            Clipboard.copy(text)

            this.alertMessage = 'Application copied to clipboard!'
            this.showAlert('success')
        },
        openMap: function(address) {
            if (address) {
                window.open(`https://www.google.com/maps/search/?api=1&query=${encodeURIComponent(address)}`, "_blank");
            }
        },
        openNewApp() {
            if (this.application.newApplicationId) {
                window.location.href = `https://amurfinancial.lightning.force.com/lightning/r/Opportunity/${this.application.salesforceIdNew}/view`;
            }
        },
        openOldApp() {
            if (this.application.oldApplicationId) {
                window.location.href = `https://amurfinancial.lightning.force.com/lightning/r/Opportunity/${this.application.salesforceIdOld}/view`;
            }
        },
        getAgentName(agentId) {
            const agent = this.agentOptions.find(agent => agent.id === agentId);
            return agent ? agent.name : '';
        },
        updateSalesJourney(field, value, oldValue) {

            this.showPreLoader()

            this.axios({
                method: 'put',
                url: '/web/contact-center/sales-journey',
                data: {
                    salesJourneyId: this.salesJourney.id,
                    field: field,
                    value: value,
                }
            })
            .then(response => {
                if(response.data.status === 'success') {
                    
                } else {
                    
                    if (field === 'referring_agent_id') {
                        this.salesJourney.referringAgentId = oldValue;
                    } else if (field === 'broker_id') {
                        this.salesJourney.brokerId = oldValue;
                    }

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
            })
        }

    }
}
</script>

<style lang="scss" scoped>
.nav-tabs .ml-auto {
    margin-left: auto;
    list-style: none;
}

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
    padding-right: 1rem;
    padding-top: 0;
    border: none;
    font-size: 0.875em;
    font-weight: normal;
}
</style>
