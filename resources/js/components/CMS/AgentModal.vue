<template>
    <div class="modal fade" :id="modalId" data-coreui-keyboard="false" tabindex="-1" style="display: none;">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">{{ action }} Agent</h5>
                    <button type="button" class="btn-close" @click="closeModel(modalId)" aria-label="Close"></button>
                </div>
                
                <div class="modal-body">
                    <table class="table table-hover">
                        <tbody v-if="users.length == 0">
                            <tr colspan="2">
                                <td><b>{{ $props.agent?.name }}</b></td>
                            </tr>
                            <tr colspan="2">
                                <td><b>{{ $props.agent?.companyGroupName }}</b></td>
                            </tr>
                        </tbody>

                        <tbody v-else>
                            <tr>
                                <td>
                                    <label>Agent</label>
                                    <select v-model="user" @change="getUserGroups" class="form-select">
                                        <option value=""></option>
                                        <option v-for="user in users" :key="user.id" :value="user.id">{{ user.fullName }}</option>
                                    </select>
                                </td>
                            </tr>

                            <tr>
                                <td> 
                                    <label>Company Group</label>
                                    <select v-model="company" @change="getCompanyGroups" class="form-select">
                                        <option v-for="(c, key) in companies" :key="key" :value="c.id">{{ c.name }}</option>
                                    </select>
                                </td>
                            </tr>

                        </tbody>
                    </table>

                    <div class="row mb-2">
                        <div class="col">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Group Type</th>
                                        <th>Commission By</th>
                                        <th>Setup Type</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr v-if="setups.length === 0 || ((user === '' || company === '') && action === 'Add')">
                                        <td colspan="3">No Agent Setup found.</td>
                                    </tr>
                                    <tr v-else v-for="setup in setups" :key="setup.id" :style="{ background: setup.commission_by === 'n' ? '#f2f2f2' : '' }">
                                        <td>
                                            {{ setup.name }}
                                        </td>
                                        <td>
                                            <div class="d-flex align-items-center gap-2">
                                                <div class="d-flex align-items-center gap-2">
                                                    <input type="radio" v-model="setup.commission_by" value="p" :disabled="setup.id===12"/>
                                                    <label class="ml-2">Percentage</label>
                                                </div>
                                                <div class="d-flex align-items-center gap-2">
                                                    <input type="radio" v-model="setup.commission_by" value="a" />
                                                    <label class="ml-2">Amount</label>
                                                </div>
                                                <div class="d-flex align-items-center gap-2">
                                                    <input type="radio" v-model="setup.commission_by" value="b" :disabled="setup.id===12"/>
                                                    <label class="ml-2">Both</label>
                                                </div>
                                                <div class="d-flex align-items-center gap-2">
                                                    <input type="radio" v-model="setup.commission_by" value="n" />
                                                    <label class="ml-2">NA</label>
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            <div class="d-flex align-items-center gap-2">
                                                <div class="d-flex align-items-center gap-2">
                                                    <input type="radio" v-model="setup.setup_by" value="d" :disabled="setup.commission_by === 'n'" />
                                                    <label class="ml-2">Default</label>
                                                </div>
                                                <div class="d-flex align-items-center gap-2">
                                                    <input type="radio" v-model="setup.setup_by" value="c" :disabled="setup.commission_by === 'n'" @click="shouwCustomSetupButton"/>
                                                    <label class="ml-2">Custom</label>
                                                </div>
                                            </div>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                    
                    <div class="card" v-if="showCustom || showCustomAdd">
                        <div class="card-header">
                            <div class="d-flex align-items-center">
                                <div>Custom Setup</div>
                                <div class="ms-auto">
                                    <button type="button" class="btn btn-primary" @click="addCustom()">
                                        <i class="bi-plus-lg me-1"></i>Add
                                    </button>
                                </div>
                            </div>
                        </div>

                        <div class="card-body">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Group Type</th>
                                        <th>Effective At</th>
                                        <th>Percentage</th>
                                        <th>Amount</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr v-if="customSetups.length === 0">
                                        <td colspan="4">No custom setup found</td>
                                    </tr>
                                    <tr v-for="custom in customSetups" :key="custom.id">
                                        <td>{{ custom.type }}</td>
                                        <td v-if="custom.cmsTypeId===12">
                                            {{ formatEffectiveDate(custom.effective) }} - {{ formatEffectiveDate(custom.effectiveUntil) }}
                                        </td>
                                        <td v-else>
                                            {{ formatEffectiveDate(custom.effective) }}
                                        </td>                                        
                                        <td>{{ custom.percentage }}%</td>
                                        <td>${{ formatDecimal(custom.amount) }}</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-dark" @click="closeModel(modalId)">
                        <i class="bi-x-lg me-1"></i>Close
                    </button>
                    <button type="button" class="btn btn-success" @click="saveChange">
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
        users: Array,
        companies: Array,
        action: String, 
        agent: Object,
        setups: Array,
        customSetups: Array,
        customAmount: Object,
        showCustom: Boolean,
        defaultGroup: String
    },
    emits: [ "events", "saveChanges", "selectUser", "customSetup", "selectCompany" ],
    watch: {
        defaultGroup: function (newValue, oldValue) {
            this.company = newValue
        }
    },
    data () {
        return {
            modalId: 'agentSetup',
            user: '',
            selectedGroup: '',
            commissionBy: [],
            setupBy: [],
            customSetupId: '',
            showGroupOpt: false,
            loading: false,
            company: '',
            showCustomAdd: false,
        }
    },
    methods: {
        formatEffectiveDate(date) {
            const regex = /^[a-zA-Z]{3}\s\/\s\d{4}$/;
            if (regex.test(date)) {
                return date;
            } else {

                const [year, month, day] = date.split('-').map(Number);
                const formattedDate = new Date(year, month - 1, day);

                if (!isNaN(formattedDate.getTime())) {
                    let dataAux = formattedDate.toLocaleString('default', { month: 'short' }) + ' / ' + formattedDate.getFullYear();
                    dataAux = dataAux.replace('.', '').replace(/\b\w/g, (l) => l.toUpperCase());
                    return dataAux;
                } else {
                    return 'Invalid date format';
                }
            }
        },
        getUserGroups() {
            this.$emit('selectUser', this.user)
        },
        getCompanyGroups() {
            this.$emit('selectCompany', this.company)
        },
        saveChange() {
            
            if (this.action === 'Edit') {
                this.company = this.$props.agent?.companyGroup
            }else {
                if (this.company === '' || this.company === null) {
                    this.alertMessage = "Please select company group";
                    this.showAlert("error");
                    return;                
                }
            }


            for (let i = 0; i < this.setups.length; i++) {
                if (this.setups[i].setup_by === 'c') {
                    if (this.customSetups.length === 0) {
                        this.alertMessage = "Custom setup is required";
                        this.showAlert("error");
                        return;
                    } else {
                        var aux = 'no';
                        for (let j = 0; j < this.customSetups.length; j++) {
                            if (this.customSetups[j].type === this.setups[i].name) {
                                aux = 'yes';
                            }
                        }
                        if (aux === 'no') {
                            this.alertMessage = "Custom setup is required for " + this.setups[i].name + " group";
                            this.showAlert("error");
                            return;
                        }
                    }
                }
            }

            let userId = this.user
            let type = 'user';

            if( this.$props.agent !== null ) {
                userId = this.$props.agent?.id
                type = 'agent'
            }
            
            if (userId === '' || userId === null) {
                this.alertMessage = "Agent is required";
                this.showAlert("error");
                return;
            }
            

            let data = {
                saveGroups: this.setups,
                customAmt: this.customSetups,
                userId: userId,
                userType: type,
                companyGroup: this.company
            }

            this.showPreLoader();

            this.user    = '';
            this.company = '';

            this.axios({
                method: 'post',
                url: 'web/cms/save-agent-setup',
                data: data
            })
            .then((response) => {
                this.$emit('saveChanges');
                })
            .catch((error) => {
                this.alertMessage = "Error";
                this.showAlert("error");
            })
            .finally(() => {
                this.hidePreLoader();
            });
        },
        addCustom() {
            this.showModal('customSetup');
        },
        closeModel(modalId) {
            this.user    = '';
            this.company = '';
            this.hideModal(modalId)
        },
        shouwCustomSetupButton() {

            this.showCustomAdd = true;

            console.log('showCustomAdd', this.showCustomAdd);
        },
    }
}
</script>