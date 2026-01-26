<template>
    <div class="mb-4" style="overflow-x: hidden; overflow-y: no; height: 95vh;">
        <div v-if="mode == 'view'">
            <div class="d-flex">
                <h5 class="ms-2">TACL# {{ applicationId }}</h5>
                <div class="ms-auto"></div>

                <button
                    type="button"
                    class="btn btn-primary"
                    @click="editNote({noteId: 0})">
                    <i class="bi bi-plus-lg me-1"></i>Add New Note
                </button>
            </div>
            
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th>Follow Up Date</th>
                        <th>Mortgage</th>
                        <th class="text-start">Note</th>
                        <th>Assigned To</th>
                        <th></th>
                    </tr>
                </thead>

                <tbody>
                    <tr v-for="(note, key) in activeNotes" :key="key">
                        <td>
                            <small :class="{ 'text-danger': isPastDate(note.followUpDate) }">{{ formatDate(note.followUpDate) }}</small>
                        </td>

                        <td><small>{{ note.mortgageCode }}</small></td>
                        <td class="text-start"><small v-html="formatNote(note.noteText)"></small></td>
                        <td><small>{{ note.delegatedTo }}</small></td>
                        <td class="text-end nowrap">
                            <button
                                type="button"
                                class="btn btn-primary me-1"
                                v-tooltip="'Edit Note'"
                                @click="editNote(note)">
                                <i class="bi bi-pencil"></i>
                            </button>
                            <button
                                type="button"
                                class="btn btn-outline-danger"
                                v-tooltip="'Delete Note'"
                                @click="destroyNote(note)">
                                <i class="bi bi-trash"></i>
                            </button>
                        </td>
                    </tr>

                    <tr v-if="activeNotes.length === 0">
                        <td colspan="100%" class="text-center">
                            No active notes found
                        </td>
                    </tr>

                    <tr v-if="inactiveNotes.length > 0">
                        <td colspan="100%" class="p-0">
                            <div class="d-flex align-items-center my-2">
                                <hr class="bg-dark w-100" style="height: 5px; opacity: 1;">
                                <h5 class="mx-2 mb-0 text-center" style="min-width: 130px;">Closed Notes</h5>
                                <hr class="bg-dark w-100" style="height: 5px; opacity: 1;">
                            </div>
                        </td>
                    </tr>

                    <tr v-for="(note, key) in inactiveNotes" :key="key">
                        <td>
                            <small :class="{ 'text-danger': isPastDate(note.followUpDate) }">{{ formatDate(note.followUpDate) }}</small>
                        </td>

                        <td><small>{{ note.mortgageCode }}</small></td>
                        <td class="text-start"><small v-html="formatNote(note.noteText)"></small></td>
                        <td><small>{{ note.delegatedTo }}</small></td>
                        <td class="text-end nowrap">
                            <button
                                type="button"
                                class="btn btn-primary me-1"
                                v-tooltip="'Edit Note'"
                                @click="editNote(note)">
                                <i class="bi bi-pencil"></i>
                            </button>
                            <button
                                type="button"
                                class="btn btn-outline-danger"
                                v-tooltip="'Delete Note'"
                                @click="destroyNote(note)">
                                <i class="bi bi-trash"></i>
                            </button>
                        </td>
                    </tr>
                    
                    <tr v-if="inactiveNotes.length === 0">
                        <td colspan="100%" class="text-center">
                            No inactive notes found
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <div v-else class="card mb-3">
            <div class="card-body">
                <div class="row">
                    <div class="col-md-3">
                        <label class="fw-bold" for="application_id">Application</label>
                        <input disabled class="form-control" name="application_id" :value="this.applicationId" />
                    </div>

                    <div class="col-md-3">
                        <label class="fw-bold" for="followup_date">Follow Up Date</label>
                        <input v-model="noteData.followUpDate" type="date" class="form-control">
                    </div>

                    <div class="col-md-3">
                        <label class="fw-bold" for="followedUp">Followed Up</label>
                        <select v-model="noteData.followedUp" class="form-select" name="followedUp">
                            <option value="yes">Yes</option>
                            <option value="no">No</option>
                        </select>
                    </div>

                    <div class="col-md-3">
                        <label class="fw-bold" for="followerUp">Assigned To</label>
                        <select v-model="noteData.followerUp" class="form-select" name="followerUp">
                            <option v-for="(follower, k) in usersData" :key="k" :value="follower.id">
                                {{ follower.fullName }}
                            </option>
                        </select>
                    </div>

                    <div class="col-md-3">
                        <label class="fw-bold" for="categoryId">Category</label>
                        <select v-model="noteData.categoryId" class="form-select" name="categoryId">
                            <option v-for="(category, k) in categoriesData" :key="k" :value="category.id">
                                {{ category.name }}
                            </option>
                        </select>
                    </div>

                    <div class="mt-3 col-md-12">
                        <MyEditor :note="noteData.noteText" @change="updateNote" />
                    </div>
                </div>
            </div>

            <div class="card-footer d-flex justify-content-center align-items-center">
                <button type="button" class="btn btn-outline-dark me-1" @click="cancel()">
                    <i class="bi bi-x-lg me-1"></i>Cancel
                </button>

                <button type="button" class="btn btn-success" @click="saveNote()">
                    <i class="bi bi-save me-1"></i>Save
                </button>
            </div>
        </div>
    </div>

    <ConfirmationDialog
        :event="event"
        :message="dialogMessage"
        type="danger"
        :parentModalId="modalId"
        :key="modalId"
        @return="dialogOnReturn"
    />
</template>

<script>
import { util } from "../mixins/util"
import MyEditor from '../components/MyEditor'
import ConfirmationDialog from '../components/ConfirmationDialog'

export default {
    components: {
        MyEditor, ConfirmationDialog
    },
    mixins: [util],
    data() {
        return {
            notes: [],
            noteData: [],
            turnDownReasons: [],
            categoriesData: [],
            usersData: [],
            applicationId: '',
            noteText: '',
            noteId: 0,
            mode: 'view',
            modalId: 'editNotes',
            event: '',
            dialogMessage: '',
        };
    },
    mounted() {
        this.getData()
        this.getApplicationId()
    },
    computed: {
        activeNotes() {
            return this.notes
                    .filter(note => note.followedUp === 'no')
                    .sort((a, b) => new Date(b.followUpDate) - new Date(a.followUpDate));
        },
        inactiveNotes() {
            return this.notes
                    .filter(note => note.followedUp === 'yes')
                    .sort((a, b) => new Date(b.followUpDate) - new Date(a.followUpDate));
        },
    },
    methods: {
        isPastDate(date) {
            const today = new Date()
            const followUpDate = new Date(date)
            return followUpDate < today
        },
        formatNote(noteContent) {
            if (!noteContent) return ''

            return noteContent.replace(/\n/g, '<br>')
        },
        updateNote: function(note) {
            this.noteText = note
        },
        cancel: function() {
            this.mode = 'view'
        },
        editNote: function(note) {
            this.getNote(note.noteId)
            this.mode = 'edit'
        },
        destroyNote: function(note) {
            this.noteId = note.noteId
            this.dialogMessage = 'Are you sure you want to delete this note?'
            this.event = 'delete'
            this.showModal('confirmationDialog' + this.modalId)
        },
        dialogOnReturn: function(event, returnMessage) {
            if(returnMessage === "confirmed" && event === "delete") {
                this.showPreLoader()

                this.axios.delete(
                    '/web/note/' + this.noteId
                )
                .then(response => {
                    this.getData()
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
            }
        },
        saveNote: function() {
            if(this.noteData.noteId == 0) {
                if(this.noteData.categoryId == 0 || this.noteData.categoryId == null) {
                    this.alertMessage = 'Category must be informed'
                    this.showAlert('error')
                    return
                }
            }

            this.showPreLoader()

            let data = {
                applicationId: this.applicationId,
                noteData: {
                    noteId: this.noteData.noteId,
                    categoryId: this.noteData.categoryId,
                    followUpDate: this.noteData.followUpDate,
                    followedUp: this.noteData.followedUp,
                    followerUp: this.noteData.followerUp,
                    noteText: this.noteText,
                    turnDownId: this.noteData.turnDownId,
                },
            }

            this.axios.post(
                "/web/note",
                data
            )
            .then(response => {
                this.getData()
                this.alertMessage = response.data.message
                this.showAlert(response.data.status)
                this.mode = 'view'
            })
            .catch((error) => {
                this.alertMessage = error
                this.showAlert('error')
            })
            .finally(() => {
                this.hidePreLoader()
            })
        },

        getNote: function(noteId) {
            this.showPreLoader()

            let data = {
                noteId: noteId,
                opportunityId: this.$route.params.opportunityId,
            }

            this.axios.get(
                '/web/note',
                {params: data}
            )
            .then(response => {
                this.turnDownReasons = response.data.data.turnDownReasons
                this.categoriesData = response.data.data.categoriesData
                this.usersData = response.data.data.usersData
                this.applicationId = response.data.data.applicationId
                this.noteData = response.data.data.noteData
                this.noteText = this.noteData.noteText
            })
            .catch(error => {
                this.alertMessage = error
                this.showAlert('error')
            })
            .finally(() => {
                this.hidePreLoader()
            })
        },

        getApplicationId: function() {
            this.showPreLoader()

            this.axios.get(`/web/application-dashboard/${this.$route.params.opportunityId}/application-id`)
            .then(response => {
                this.applicationId = response.data.data
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

            this.axios.get(`/web/application-dashboard/${this.$route.params.opportunityId}/notes`)
            .then(response => {
                this.notes = response.data.data
            })
            .catch(error => {
                this.alertMessage = error
                this.showAlert('error')
            })
            .finally(() => {
                this.hidePreLoader()
            })
        },
    },
}
</script>
