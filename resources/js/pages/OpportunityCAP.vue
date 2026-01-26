<template>
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item">
                <RouterLink to="/">Home</RouterLink>
            </li>
            <li class="breadcrumb-item">
                Salesforce
            </li>
            <li class="breadcrumb-item active">
                Oppurtunity CAP
            </li>
        </ol>
    </nav>

    <div class="card">
        <div class="card-header d-flex">
            <div class="ms-auto">
                <button class="btn btn-primary" type="button" @click="editCap({})">
                    <i class="bi-plus-lg me-1"></i>Add New User
                </button>
            </div>
        </div>

        <div class="card-body">
            <div class="pb-4 d-flex flex-row align-items-center justify-content-start gap-3">
                <div>
                    Standard Limit
                </div>
                <div>
                    <input
                        class="form-control"
                        v-model="standardLimit"
                        type="number"
                    />
                </div>
                <div>
                    <button class="btn btn-success" type="button" @click="standardLimitSave()">
                        <i class="bi-save me-1"></i>Save
                    </button>
                </div>
            </div>
            
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th>User</th>
                        <th>CAP Limit</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="(oppurtunityCap, key) in data" :key="key">
                        <td>{{ oppurtunityCap.userName }}</td>
                        <td>{{ oppurtunityCap.capLimit }}</td>
                        <td class="text-end nowrap">
                            <button class="btn btn-secondary me-1" type="button" @click="editCap(oppurtunityCap)">
                                <i class="bi-pencil me-1"></i>Edit
                            </button>

                            <button class="btn btn-outline-danger" type="button" @click="destroyCap(oppurtunityCap)">
                                <i class="bi-trash me-1"></i>Delete
                            </button>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>

    <div class="modal fade" aria-hidden="true" id="editCap" data-coreui-keyboard="false" tabindex="-1" style="display: none;">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Add/Edit User</h5>
                    <button type="button" class="btn-close" data-coreui-dismiss="modal" aria-label="Close"></button>
                </div>

                <div class="modal-body">
                    <div class="mb-3">
                        <label for="user" class="form-label">User</label>
                        <select id="user" v-model="userId" class="form-select">
                            <option disabled value="">Select User</option>
                            <option
                                v-for="(broker, key) in brokers"
                                :key="key"
                                :value="broker.id"
                            >
                                {{ broker.fullName }}
                            </option>
                        </select>
                    </div>

                    <div>
                        <label for="CAP" class="form-label">CAP</label>
                        <div class="input-group">
                            <input
                                type="number"
                                id="CAP"
                                class="form-control"
                                placeholder="Enter CAP"
                                v-model= "capLimit"
                            />
                        </div>
                    </div>
                </div>

                <div class="modal-footer">
                    <button class="btn btn-outline-dark" type="button" data-coreui-dismiss="modal">
                        <i class="bi-x-lg me-1"></i>Close
                    </button>

                    <button
                        class="btn btn-success"
                        type="button"
                        @click="storeUser()"
                    >
                        <i class="bi-save me-1"></i>Save
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>

<script>
import { util } from '../mixins/util'

export default {
    mixins: [util],
    emits: ['events'],
    data() {
        return {
            data: [],
            standardLimit: null,
            brokers: [],
            userId: null,
            capLimit: null,
        }
    },
    mounted() {
        this.getData()
        this.getBrokers()
    },
    methods: {
        standardLimitSave: function() {
            if(this.standardLimit == '' || this.standardLimit == '0' || this.standardLimit == 0) {
                this.alertMessage = 'Invalid CAP Entry'
                this.showAlert('error')
                return
            }

            this.showPreLoader()

            this.axios.post(
                '/web/salesforce/oppurtunity-cap',
                {
                    userId: null,
                    capLimit: this.standardLimit,
                }
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
        destroyCap: function(oppurtunityCap) {
            this.showPreLoader()

            this.axios.delete(
                '/web/salesforce/oppurtunity-cap/' + oppurtunityCap.id
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
        editCap: function(cap) {
            this.userId = cap.userId || null;
            this.capLimit = cap.capLimit || null;
            this.showModal('editCap');
        },
        storeUser: function() {
            if(this.userId == '' || this.userId == null) {
                this.alertMessage = 'User is required'
                this.showAlert('error')
                return
            }

            if(this.capLimit == '' || this.capLimit == null) {
                this.alertMessage = 'CAP limit is required'
                this.showAlert('error')
                return
            }

            this.showPreLoader()

            this.axios.post(
                '/web/salesforce/oppurtunity-cap',
                {
                    userId: this.userId,
                    capLimit: this.capLimit,
                }
            )
            .then(response => {
                if(this.checkApiResponse(response)) {
                    this.getData()
                    this.hideModal('editCap')
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
        getBrokers: function() {
            this.axios.get('/web/users/brokers')
            .then((response) => {
                if(this.checkApiResponse(response)) {
                    this.brokers = response.data.data
                }
            })
            .catch((error) => {
                this.brokers = []
            })
        },
        getData: function() {
            this.showPreLoader()

            this.axios({
                method: 'get',
                url: '/web/salesforce/oppurtunity-cap'
            })
            .then(response => {
                if(this.checkApiResponse(response)) {
                    this.standardLimit = response.data.data.find(data => data.userId === null)?.capLimit || null
                    this.data = response.data.data.filter(data => data.userId !== null)
                } else {
                    this.alertMessage = error
                    this.showAlert('error')
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
    }
}
</script>
