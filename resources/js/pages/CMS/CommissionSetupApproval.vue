<template>
    <div class="row">
        <div class="col-6 mb-4">
            <div class="card">
                <div class="card-header">
                    Filters
                </div>

                <div class="card-body">
                    <div class="d-flex">

                        <div class="pe-2">
                            <label for="" class="form-label">Status</label>
                            <select class="form-select" v-model="selectedFilter">
                                    <option value="all">All</option>
                                    <option value="pending">Pending</option>
                                    <option value="approved">Approved</option>
                                    <option value="rejected">Rejected</option>
                            </select>
                        </div>

                        <div class="pe-2">
                            <label for="" class="form-label">Start Date</label>
                            <DatePicker v-model="startDate" :model-config="modelConfig" :timezone="timezone">
                                <template v-slot="{ inputValue, inputEvents }">
                                    <input
                                        class="form-control"
                                        :value="inputValue"
                                        v-on="inputEvents"
                                    />
                                </template>
                            </DatePicker>
                        </div>

                        <div>
                            <label for="" class="form-label">End Date</label>
                            <DatePicker v-model="endDate" :model-config="modelConfig" :timezone="timezone">
                                <template v-slot="{ inputValue, inputEvents }">
                                    <input
                                        class="form-control"
                                        :value="inputValue"
                                        v-on="inputEvents"
                                    />
                                </template>
                            </DatePicker>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="card mb-2">
        <div class="card-header">
            <div class="d-flex">
                <h5 class="m-0">Agent Approval - New Agents</h5>
                <div class="ms-auto d-flex gap-2">


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
                        <th>Description</th>
                        <th>Setup Info</th>
                        <th>Created By</th>
                        <th>Created At</th>
                        <th>Accounting</th>
                    </tr>
                </thead>

                <tbody>
                    <tr v-for="item in filteredData" :key="item.id">
                        <template v-if="item?.isActive && item?.table_name ==='cms_agent' && !item?.description.includes('was deleted')">
                            <td>{{ item.description }}</td>
                            <td>
                                <button
                                    type="button"
                                    class="btn btn-primary me-2 mb-2"
                                    @click="getMoreInfo(item?.agentId)"
                                    :disabled="item?.agentId === 0">
                                    <i class="bi bi-info-circle"></i>
                                    More
                                </button>
                            </td>
                            <td>{{ (item?.created_by) }}</td>
                            <td>{{ item?.created_at}}</td>
                            <td>
                                <button
                                    type="button"
                                    class="btn me-2 mb-2" 
                                    :class="{
                                            'btn-danger disabled': item.accounting_status === 'R',
                                            'btn-success disabled': item.accounting_status === 'A',
                                            'btn btn-success': item.accounting_status === 'P',
                                            'disabled': item.isActive === false ||  accounting.length <= 0
                                        }"
                                    @click="setupApprovalByDepartment('accounting', item, 'A')"><i class="bi-check-lg me-1"></i>{{ item?.accounting_status === 'A' ? 'Approved' : 'Approve' }}</button>
                                <button
                                    type="button"
                                    class="btn mb-2"
                                    :class="{
                                            'btn-danger disabled': item.accounting_status === 'R',
                                            'btn-outline-danger ms-2 disabled': item.accounting_status === 'A',
                                            'btn btn-outline-danger ms-2': item.accounting_status === 'P',
                                            'disabled': item.isActive === false ||  accounting.length <= 0
                                        }"
                                    @click="setupApprovalByDepartment('accounting', item, 'R')"><i class="bi-x-circle me-1"></i>{{ item?.accounting_status === 'R' ? 'Rejected' : 'Reject' }}</button>
                            </td>
                        </template>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>

    <div class="card mb-2">
        <div class="card-header">
            <div class="d-flex">
                <h5 class="m-0">Agent Approval - Commission Update</h5>
            </div>
        </div>
        
        <div class="card-body">
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th>Agent</th>
                        <th>Group</th>
                        <th>Previous setup</th>
                        <th>New setup</th>
                        <th>Setup Info</th>
                        <th>Created By</th>                            
                        <th>Accounting</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="item in filteredData" :key="item.id">
                        <template v-if="item?.isActive && (item?.table_name === 'cms_agent_setup' || item?.table_name ==='cms_commission_setup') && item?.type !=='CS'">
                            <td>{{ item.agentName }}</td>
                            <td>{{ item.groupName }}</td>
                            <td>{{ item.description }}</td>
                            <td>{{ item.newDescription }}</td>
                            <td>
                                <button 
                                    type="button"
                                    class="btn btn-primary me-2 mb-2"
                                    @click="getMoreInfo(item?.agentId)"
                                    :disabled="item?.agentId === 0">
                                    <i class="bi bi-info-circle"></i>
                                    More
                                </button>
                            </td>
                            <td>{{ item?.created_by }}</td>
                            <td>{{ item?.created_at }}</td>
                            <td>
                                <button
                                    type="button"
                                    class="btn me-2 mb-2" 
                                    :class="{
                                        'btn-danger disabled': item.accounting_status === 'R',
                                        'btn-success disabled': item.accounting_status === 'A',
                                        'btn btn-success': item.accounting_status === 'P',
                                        'disabled': item.isActive === false || accounting.length <= 0
                                    }"
                                    @click="customSetupApprovalByDepartment('accounting', item, 'A')"><i class="bi-check-lg me-1"></i>{{ item?.accounting_status === 'A' ? 'Approved' : 'Approve' }}</button>
                                <button
                                    type="button"
                                    class="btn mb-2"
                                    :class="{
                                        'btn-danger disabled': item.accounting_status === 'R',
                                        'btn-outline-danger ms-2 disabled': item.accounting_status === 'A',
                                        'btn btn-outline-danger ms-2': item.accounting_status === 'P',
                                        'disabled': item.isActive === false || accounting.length <= 0
                                    }"
                                    @click="customSetupApprovalByDepartment('accounting', item, 'R')"><i class="bi-x-circle me-1"></i>{{ item?.accounting_status === 'R' ? 'Rejected' : 'Reject' }}</button>
                            </td>
                        </template>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>

    <div class="card mb-2">
        <div class="card-header">
            <div class="d-flex">
                <h5 class="m-0">Commission Structure Approval</h5>
            </div>
        </div>

        <div class="card-body">
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th>Group</th>
                        <th>Previous setup</th>
                        <th>New setup</th>
                        <th>Created By</th>
                        <th>Created At</th>
                        <th>Accounting</th>
                        <th>Executive</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="item in filteredData" :key="item.id">
                        <template v-if="item?.isActive && item?.table_name === 'cms_commission_setup' && item?.type === 'CS'">
                            <td>{{ item.groupName }}</td>
                            <td>{{ item.description }}</td>
                            <td>{{ item.newDescription }}</td>
                            <td>{{ item?.created_by }}</td>
                            <td>{{ item?.created_at}}</td>
                            <td>
                                <button
                                    type="button"
                                    class="btn me-2 mb-2" 
                                    :class="{
                                        'btn-danger disabled': item.accounting_status === 'R',
                                        'btn-success disabled': item.accounting_status === 'A',
                                        'btn btn-success': item.accounting_status === 'P',
                                        'disabled': item.isActive === false || accounting.length <= 0
                                                                            }"
                                    @click="setupStructureApprovalByDepartment('accounting', item, 'A')"><i class="bi-check-lg me-1"></i>{{ item?.accounting_status === 'A' ? 'Approved' : 'Approve' }}</button>
                                <button
                                    type="button"
                                    class="btn mb-2"
                                    :class="{
                                        'btn-danger disabled': item.accounting_status === 'R',
                                        'btn-outline-danger ms-2 disabled': item.accounting_status === 'A',
                                        'btn btn-outline-danger ms-2': item.accounting_status === 'P',
                                        'disabled': item.isActive === false || accounting.length <= 0
                                                                            }"
                                    @click="setupStructureApprovalByDepartment('accounting', item, 'R')"> <i class="bi-x-circle me-1"></i> {{ item?.accounting_status === 'R' ? 'Rejected' : 'Reject' }}</button>
                            </td>
                            <td>
                                <button
                                    type="button"
                                    class="btn me-2 mb-2"
                                    :class="{
                                        'btn-danger disabled': item.executive_status === 'R',
                                        'btn-success disabled': item.executive_status === 'A',
                                        'btn btn-success': item.executive_status === 'P',
                                        'disabled': item.isActive === false || executives.length <= 0
                                    }"
                                    @click="setupStructureApprovalByDepartment('executive', item, 'A')"><i class="bi-check-lg me-1"></i>{{ item?.executive_status === 'A' ? 'Approved' : 'Approve' }}</button>
                                <button
                                    type="button"
                                    class="btn mb-2"
                                    :class="{
                                        'btn-danger disabled': item.executive_status === 'R',
                                        'btn-outline-danger ms-2 disabled': item.executive_status === 'A',
                                        'btn btn-outline-danger ms-2': item.executive_status === 'P',
                                        'disabled': item.isActive === false || executives.length <= 0
                                    }"
                                    @click="setupStructureApprovalByDepartment('executive', item, 'R')"> <i class="bi-x-circle me-1"></i> {{ item?.executive_status === 'R' ? 'Rejected' : 'Reject' }}</button>
                            </td>
                        </template>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>

    <CMSInfoModal :modalId="modalId" :agent="agent" />
</template>

<script>
import { util } from "../../mixins/util";
import CMSInfoModal from "../../components/CMS/CMSInfoModal";
import { DatePicker } from 'v-calendar'
import 'v-calendar/dist/style.css'

export default {
    mixins: [util],
    emits: ["events"],
    components: { CMSInfoModal, DatePicker },
    watch: {
        startDate: {
            handler(newValue, oldValue) {
                if(typeof oldValue !== 'object') this.getData()
            },
            deep: true
        },
        endDate: {
            handler(newValue, oldValue) {
                if(typeof oldValue !== 'object') this.getData()
            },
            deep: true
        }
    }, 
    data() {
        return {
            startDate: new Date((new Date()).valueOf() - 1000*60*60*1440),
            endDate: new Date((new Date()).valueOf() + 1000*60*60*1),
            commissionSetup: [],
            search: "",
            selectedFilter: "pending",  
            executives: [],
            accounting: [],
            setupInfo: [],
            agents:[],
            agent: null,  
            users: [],
            user: "",
            userType: "user",
            setups: [],
            customSetups: [],
            defaultGroup: "",
            types: [],
            company: "",
            userName: "",
            companies: [
                { id: 'ACL', name: 'Alpine Credits Limited' },
                { id: 'SNR', name: 'Alpine Credits Limited (Senior Broker)' },
                { id: 'SQC', name: 'Sequence Capital' },
                { id: 'SON', name: 'Sequence Capital (Ontario)' },
                

            ],
            modalId: 'CMSInfoModal',
        };
    },

    mounted() {
      this.getData();
      this.getAccounting();
      this.getExecutives();   
      this.getAgents();   
    },
    computed: {
        filteredData() {
            let search = this.search && this.search.toLowerCase();
            var data = this.commissionSetup;
            const tableIdMap = {};

            data = data.map((item) => {
                const tableId = item.table_id;
                if (!tableIdMap[tableId]) {
                item.isActive = true;
                tableIdMap[tableId] = item;
                } else {
                    tableIdMap[tableId].isActive = false;
                // If there's a duplicate, mark the previous occurrence as inactive
                if(tableIdMap[tableId].accounting_status === null && tableIdMap[tableId].executive_status === null){
                    tableIdMap[tableId].isActive = false;
                }
                item.isActive = true;
                tableIdMap[tableId] = item;
                }
                return item;
            });

           // Filter before console log
            if(search !== "") {
                const filteredData = data.filter(function (row) {
                    return Object.keys(row).some(function (key) {
                        return String(row[key]).toLowerCase().indexOf(search) > -1;
                    });
                });
                return filteredData;
            }
         
        return this.commissionSetup.filter(item => {

            if (this.selectedFilter === 'all') {
                return true;
            } else if (this.selectedFilter === 'approved') {
                return item.accounting_status === 'A' || item.executive_status === 'A';
            } else if (this.selectedFilter === 'pending') {
                if (item.table_name === 'cms_commission_setup' && item.type === 'CS') {
                    return item.accounting_status === 'P' || item.executive_status === 'P';
                }else{
                    return item.accounting_status === 'P';
                }
            } else if (this.selectedFilter === 'rejected') {
                return item.accounting_status === 'R' || item.executive_status === 'R';
            } else {
                return false; 
            }
        });
    },
    },
    methods: {
        getData: function () {

            this.showPreLoader();

            let data = {
                startDate: this.startDate,
                endDate: this.endDate,
            }

            this.axios({
                method: "post",
                url: "web/cms/commission-setup-approval",
                data: data
            })
            .then((response) => {
                if (this.checkApiResponse(response)) {
                    this.commissionSetup = response.data.data;
                } else {
                    this.alertMessage = "Error in getting commission setup!";
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

        getMoreInfo: function(id) {
            if (id > 0) {
                let selectedAgent = this.agents.find(agent => agent.id === id)
                this.agent = selectedAgent
                this.showModal("CMSInfoModal")
            }            
        },
         
        setupApprovalByDepartment : function (department, item, status) {
            
            this.showPreLoader()

            this.axios({
                method: "post",
                url: "web/cms/commission-setup-department-approval",
                data: {
                    id: item.id,
                    tableId: item.table_id,
                    status: status,
                    department: department
                }
            })
            .then((response) => {
                if (this.checkApiResponse(response)) {
                    this.getData();
                } else {
                    this.alertMessage = "Error in getting commission setup!";
                    this.showAlert(response.data.status);
                }
            }).finally(() => {
                this.hidePreLoader(); 
            })
        },

        customSetupApprovalByDepartment : function (department, item, status) {
            this.getData();
            console.log(' custom departmentApproval', department,item, status)
            this.showPreLoader()
            this.axios({
                method: "post",
                url: "web/cms/commission-custom-setup-department-approval",
                data: {
                    tableId: item.table_id,
                    status: status,
                    department: department
                }
            })
            .then((response) => {
                if (this.checkApiResponse(response)) {
                    this.getData();
                } else {
                    this.alertMessage = "Error in getting commission setup!";
                    this.showAlert(response.data.status);
                }
            }).finally(() => {
                this.hidePreLoader(); 
            })
        },

        setupStructureApprovalByDepartment : function (department, item, status) {

            this.showPreLoader()

            this.axios({
                method: "post",
                url: "web/cms/commission-setup-structure-department-approval",
                data: {
                    tableId: item.table_id,
                    status: status,
                    department: department
                }
            })
            .then((response) => {
                if (this.checkApiResponse(response)) {
                    this.getData();
                } else {
                    this.alertMessage = "Error in getting commission setup!";
                    this.showAlert(response.data.status);
                }
            }).finally(() => {
                this.hidePreLoader(); 
            })
        },

        getAccounting: function () {
            this.axios({
                method: "get",
                url: "web/cms/accounting",
            })
            .then((response) => {
                if (this.checkApiResponse(response)) {
                    this.accounting = response.data.data;
                }
            })
            .catch((error) => {
                console.log(error);
            });
        },

        getExecutives: function () {
            this.axios({
                method: "get",
                url: "web/cms/executives",
            })
            .then((response) => {
                if (this.checkApiResponse(response)) {
                    this.executives = response.data.data;
                }
            })
            .catch((error) => {
                console.log(error);
            });
        },

        getAgents: function() {

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
    },
}
</script>
