<template>
    
    <div class="card">
        <div class="card-header">
            <div class="d-flex">
                <h5>Agent Setup</h5>

                <div class="ms-auto pe-2">
                    <button
                        type="button"
                        class="btn btn-primary"
                        @click="agentSetup('Add')"
                    >
                        <i class="bi-plus-lg me-1"></i>Add
                    </button>
                </div>
                <div class="text-end">
                    <div class="input-group">
                        <span class="input-group-text"
                            ><i class="bi-search"></i
                        ></span>
                        <input
                            type="text"
                            class="form-control"
                            placeholder="Search"
                            v-model="search"
                        />
                    </div>
                </div>
            </div>
        </div>

        <div class="card-body">
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th>Agent Name</th>
                        <th>Company Group</th>
                        <th></th>
                    </tr>
                </thead>

                <tbody v-if="filteredData.length == 0">
                    <tr>
                        <td colspan="7">No Agent Setup</td>
                    </tr>
                </tbody>

                <tbody v-else>
                    <tr
                        v-for="agent in filteredData"
                        :key="agent.id"
                    >
                        <td>{{ agent.name }}</td>
                        <td>{{ agent.companyGroupName }}</td>
                        <td class="text-end">
                            <button
                                type="button"
                                class="btn btn-primary"
                                @click="agentSetup('Edit', agent)"
                            >
                                <i class="bi-pencil me-1"></i>Edit
                            </button>
                            <button
                                type="button"
                                class="btn btn-outline-danger mx-2"
                                @click="removeAgent(agent)"
                            >
                                <i class="bi-trash me-1"></i>Delete
                            </button>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>

    <AgentModal
        :users="users"
        :companies="companies"
        :action="modalAction"
        :agent="agent"
        :setups="setups"
        :customSetups="customSetups"
        :customAmount="customAmount"
        :showCustom="showCustom"
        :defaultGroup="defaultGroup"
        @selectCompany = "onSelectCompany"
        @saveChanges="onSaveChanges"
        @selectUser="onSelectUser"
    />

    <CustomSetup @saveCustom="onSaveCustom" />

    <ConfirmationDialog
        :event="event"
        :message="dialogMessage"
        type="danger"
        :parentModalId="modalId"
        :key="modalId"
        @return="removeDialogOnReturn"
    />
</template>

<script>
import { util } from "../../mixins/util";
import AgentModal from "../../components/CMS/AgentModal.vue";
import CustomSetup from "../../components/CMS/CustomSetup.vue";
import ConfirmationDialog from "../../components/ConfirmationDialog";

export default {
    mixins: [util],
    components: { AgentModal, CustomSetup, ConfirmationDialog },
    name: "agent-setup",
    emits: ['events'],
    data() {
        return {
            modalId: "agentSetup",
            event: "",
            user: "",
            userType: "user",
            users: [],
            modalAction: "Add",
            agents: [],
            setups: [],
            customSetups: [],
            dialogMessage: null,
            agent: null,
            setupIndex: 0,
            customAmount: [],
            customData: [],
            showCustom: false,
            defaultGroup: "",
            types: [],
            search: "",
            company: "",
                companies: [
                { id: 'ACL', name: 'Alpine Credits Limited' },
                { id: 'SNR', name: 'Alpine Credits Limited (Senior Broker)' },
                { id: 'SQC', name: 'Sequence Capital' },
                { id: 'SON', name: 'Sequence Capital (Ontario)' },
                
            ],
        };
    },
    computed: {
        filteredData() {
            var search = this.search && this.search.toLowerCase();
            var data = this.agents;
            data = data.filter(function (row) {
                return Object.keys(row).some(function (key) {
                    return String(row[key]).toLowerCase().indexOf(search) > -1;
                });
            });
            return data;
        },
    },

    methods: {
        getUsers() {
            this.showPreLoader()

            this.axios({
                method: "get",
                url: "web/cms/users-agents",
            })
            .then((response) => {
                if (this.checkApiResponse(response)) {
                    this.users = response.data.data;
                } else {
                    this.alertMessage = "Error in getting agents!";
                    this.showAlert(response.data.status);
                }
            })
            .catch((error) => {
                console.error("An error occurred:", error);
            })
            .finally(() => {
                this.hidePreLoader(); 
            })
        },
        getAgents() {
            this.showPreLoader()

            this.axios({
                method: "get",
                url: "web/cms/agents",
            })
            .then((response) => {
                if (this.checkApiResponse(response)) {
                    this.agents = response.data.data;
                } else {
                    this.alertMessage = "Error in getting agents!";
                    this.showAlert(response.data.status);
                }
            })
            .catch((error) => {
                console.error("An error occurred:", error);
            })
            .finally(() => {
                this.hidePreLoader()
            })
        },
        getUserGroups() {
            if (this.user !== "") {
                this.axios({
                    method: "get",
                    url: "web/cms/agent-setup/" + this.user + "/" + this.userType,
                })
                .then((response) => {
                    if (this.checkApiResponse(response)) {
                        this.setups = response.data.data.setups;
                        this.customSetups = response.data.data.customSetup;

                        if (this.customSetups.length > 0) {
                            this.showCustom = true;
                        } else {
                            this.showCustom = false;
                        }
                    } else {
                        this.alertMessage = "Error in getting agents groups!";
                        this.showAlert(response.data.status);
                    }
                })
                .catch((err) => {
                    this.alertMessage = err;
                    this.showAlert("failed");
                });
            } else {
                this.loading = false;
                this.showGroupOpt = false;
                this.showGroupBtn = false;
            }
        },
        agentSetup(action, agent) {
            this.users = "";
            if (action === "Edit") {
                this.agent = agent;
                this.user = agent.id;
                this.userType = "agent";
                this.defaultGroup = agent.companyGroup;
                this.company = agent.companyGroup;
                this.getUserGroups();
            } else {
                this.agent = null;
                this.user = "";
                this.userType = "user";
                this.defaultGroup = "";
                this.company = "";
                this.getUsers();
                this.setups = [];
                this.customSetups = [];
                this.showCustom = false;
            }
            this.modalAction = action;
            this.showModal("agentSetup");
        },
        onSaveChanges() {
            this.hideModal("agentSetup");
            this.agent = null;
            this.user = "";
            this.userType = "user";
            this.setups = [];
            this.customSetups = [];
            this.setupIndex = 0;
            this.customAmount = null;
            this.getAgents();
        },
        onSelectUser(user) {
            this.user = user;
            this.getUserGroups();
        },
        onSelectCompany(company) {
            this.company = company;
            this.getUserGroups();
        },
        onSaveCustom(customSetup) {
            this.customSetups.push(customSetup);
        },

        removeAgent(agentData) {
            this.dialogMessage = "Are you sure you want to remove this agent setup?";
            this.event = this.modalId;
            this.agentData = agentData;
            this.showModal('confirmationDialog' + this.modalId);
        },

        removeDialogOnReturn: function (event, returnMessage) {
            if(returnMessage !== 'confirmed') {
                return
            }

            let data = {
                agent: this.agentData,
                removeReason: '',
            }

            this.axios({
                method: "delete",
                url: "web/cms/remove-agent",
                data: data,
            })
            .then((response) => {
                this.alertMessage = response.data.message;
                this.showAlert(response.data.status);
                this.getAgents();
            })
            .catch((error) => {
                this.alertMessage = error;
                this.showAlert("error");
            })
            .finally(() => {
                this.hidePreLoader()
            })
        },
    },
    mounted() {
        this.getAgents();
    },
};
</script>
