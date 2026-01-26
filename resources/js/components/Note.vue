<template>
    <div class="modal" id="Notes" tabindex="-1">
        <div class="modal-dialog modal-xl">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Edit Notes</h5>
                    <button type="button" class="btn-close" data-coreui-dismiss="modal" aria-label="Close"></button>
                </div>

                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-3">
                            <label class="fw-bold" for="application_id">Application</label>
                            <input disabled class="form-control" name="application_id" :value="this.applicationId" />
                        </div>

                        <div class="col-md-3">
                            <label class="fw-bold" for="followup_date">Followup Date</label>
                            <input v-model="note.followUpDate" type="date" class="form-control">
                        </div>

                        <div class="col-md-3">
                            <label class="fw-bold" for="followed_up">Followed Up</label>
                            <select v-model="note.followedUp" class="form-select" name="followed_up">
                                <option value="yes">Yes</option>
                                <option value="no">No</option>
                            </select>
                        </div>

                        <div class="col-md-3">
                            <label class="fw-bold" for="follower_up">Assigned To</label>
                            <select v-model="note.followerUp" class="form-select" name="follower_up">
                                <option v-for="(follower, key) in usersData" :key="key" :value="follower.id">
                                    {{ follower.fullName }}
                                </option>
                            </select>
                        </div>

                        <div class="mt-3 col-md-12">
                            <MyEditor :note="note.noteText" @change="updateNote" />
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-dark" @click="hideModal(modalId)">
                        <i class="bi-x-lg me-1"></i>Close
                    </button>

                    <button type="button" class="btn btn-success" @click="saveNote()">
                        <i class="bi-save me-1"></i>Save
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>

<script>
import { util } from "../mixins/util"
import MyEditor from '../components/MyEditor.vue'

export default {
    mixins: [util],
    components: {
        MyEditor,
    },
    data() {
        return {
            turnDownReasons: [],
            categoriesData: [],
            usersData: [],
            modalId: 'Notes',
            applicationId: '',
            note: [],
            noteText: '',
        };
    },
    props: ['objectId', 'noteData', 'refreshCount'],
    watch: {
        refreshCount: {
            handler(newValue, oldValue) {
                this.getData()
            },
            deep: true
        },
    },
    methods: {
        updateNote: function(note) {
            this.noteText = note
        },
        getData() {
            let data = {
                noteId: this.noteData.noteId
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
                this.note = response.data.data.noteData
                this.noteText = this.note.noteText
            })
            .catch(error => {
                this.alertMessage = error
                this.showAlert('error')
            })
        }, 

        saveNote() {
            this.showPreLoader()

            let data = {
                applicationId: this.applicationId,
                noteData: {
                    categoryId: this.note.categoryId,
                    followUpDate: this.note.followUpDate,
                    followedUp: this.note.followedUp,
                    followerUp: this.note.followerUp,
                    noteId: this.note.noteId,
                    noteText: this.noteText,
                    turnDownId: this.note.turnDownId,
                },
            }

            this.axios.post(
                "/web/note",
                data
            )
            .then(response => {
                this.hideModal(this.modalId)
                this.$emit('refresh')
            })
            .catch((error) => {
                this.alertMessage = error
                this.showAlert("error")
            })
            .finally(() => {
                this.hidePreLoader()
            });
        },
    },
}
</script>
