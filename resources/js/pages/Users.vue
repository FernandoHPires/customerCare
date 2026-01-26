<template>
    <div>
        <div class="card mb-4">
            <div class="card-header">
                <div class="d-flex">
                    <div>
                        Users
                    </div>

                    <div class="ms-auto">
                        <button type="button" class="btn btn-primary" @click="edit(0)">
                            <i class="bi-plus-lg me-1"></i>Add
                        </button>
                    </div>
                </div>
            </div>

            <div class="card-body">
                <table class="table table-hover table-sm">
                    <thead>
                        <tr>
                            <th>Username</th>
                            <th>First Name</th>
                            <th>Last Name</th>
                            <th>Email</th>
                            <th></th>
                        </tr>
                    </thead>

                    <tbody>
                        <tr v-for="(user, key) in users" :key="key">
                            <td>{{  user.username }}</td>
                            <td>{{  user.firstName }}</td>
                            <td>{{  user.lastName }}</td>
                            <td>{{  user.email }}</td>
                            <td class="text-end">
                                <button type="button" class="btn btn-secondary me-2" @click="edit(user.id)">
                                    <i class="bi-pencil me-1"></i>Edit
                                </button>
                                <button type="button" class="btn btn-outline-danger" @click="destroy(user.id)">
                                    <i class="bi-trash"></i>
                                </button>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <div class="modal fade" id="edit" tabindex="-1" data-coreui-keyboard="false">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Edit</h5>
                        <button type="button" class="btn-close" data-coreui-dismiss="modal" aria-label="Close"></button>
                    </div>

                    <div class="modal-body">
                        <div class="mb-2">
                            <label>Username</label>
                            <input class="form-control" type="text" v-model="username">
                        </div>

                        <div class="mb-2">
                            <label>First Name</label>
                            <input class="form-control" type="text" v-model="firstName">
                        </div>

                        <div class="mb-2">
                            <label>Last Name</label>
                            <input class="form-control" type="text" v-model="lastName">
                        </div>

                        <div class="mb-3">
                            <label>Email</label>
                            <input class="form-control" type="text" v-model="email">
                        </div>
                    </div>

                    <div class="modal-footer">
                        <button class="btn btn-outline-dark" type="button" data-coreui-dismiss="modal"><i class="bi-x-lg me-1"></i>Close</button>
                        <button class="btn btn-success" type="button" @click="store()"><i class="bi-save me-1"></i>Save</button>
                    </div>
                </div>
            </div>
        </div>

        <ConfirmationDialog :event="event" :message="dialogMessage" type="danger" @return="confirmationDialogOnReturn" />
    </div>
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
            users: [],
            userId: '',
            username: '',
            firstName: '',
            lastName: '',
            email: '',
            event: '',
            dialogMessage: ''
        }
    },
    mounted() {
        this.getData()
    },
    methods: {
        confirmationDialogOnReturn: function(event, returnMessage) {
            if(returnMessage == 'confirmed') {
                if(event == 'destroy') {
                    this.showPreLoader()

                    this.axios({
                        method: 'delete',
                        url: 'api/users/' + this.userId
                    })
                    .then(response => {
                        if(this.checkApiResponse(response)) {
                            this.getData()
                        } else {
                            this.alertMessage = response.data.message
                            this.showAlert(response.data.status)
                        }
                    })
                    .catch(error => {
                        console.log(error)
                    })
                    .finally(() => {
                        this.hidePreLoader()
                    })
                }
            }
        },
        destroy: function(id) {
            this.userId = id
            this.event = 'destroy'
            this.dialogMessage = 'Are you sure you want to delete this user?'
            this.showModal('confirmationDialog')
        },
        edit: function(id) {
            if(id === 0) {
                this.userId = 0
                this.username = ''
                this.firstName = ''
                this.lastName = ''
                this.email = ''
            } else {
                let element = this.getElementById(this.users, id)
                this.userId = element.id
                this.username = element.username
                this.firstName = element.firstName
                this.lastName = element.lastName
                this.email = element.email
            }

            this.showModal('edit')
        },
        store: function() {
            this.showPreLoader()

            let data = {
                id: this.userId,
                username: this.username,
                firstName: this.firstName,
                lastName: this.lastName,
                email: this.email
            }

            this.axios({
                method: 'post',
                url: 'api/users',
                data: data
            })
            .then(response => {
                if(this.checkApiResponse(response)) {
                    this.hideModal('edit')
                    this.getData()
                } else {
                    this.alertMessage = response.data.message
                    this.showAlert(response.data.status)
                }
            })
            .catch(error => {
                this.alertMessage = 'Error'
                this.showAlert('error')
            })
            .finally(() => {
                this.hidePreLoader()
            })
        },
        getData: function() {
            this.showPreLoader()

            this.axios({
                method: 'get',
                url: 'api/users'
            })
            .then(response => {
                console.log("response users", response)
                if(this.checkApiResponse(response)) {
                    this.users = response.data.users
                } else {
                    this.alertMessage = response.data.message
                    this.showAlert(response.data.status)
                }
            })
            .catch(error => {
                this.alertMessage = 'Error'
                this.showAlert('error')
            })
            .finally(() => {
                this.hidePreLoader()
            })
        }
    }
}
</script>