<template>
    <div style="overflow-x: hidden;">
        <div class="text-end mb-3">
            <button 
                class="btn btn-success" 
                @click="addApplicant()">
                <i class="bi bi-plus-lg me-1"></i>Add Applicant
            </button>
        </div>

        <div class="card mb-2" v-for="(applicant, key) in applicants" :key="key">
            <div class="card-header">
                Applicant #{{ key + 1 }}
            </div>

            <div class="card-body">
                <div class="d-flex">
                    <div class="pe-2">
                        <label class="form-label">Spouse 1</label>
                        <select class="form-select" v-model="applicant.spouse1" @change="onChange(applicant.spouse1, key, 0)">
                            <option value="0">None</option>
                            <option v-for="(spouse, key) in spouses" :key="key" :value="spouse.id">
                                {{ spouse.name }}
                            </option>
                        </select>
                    </div>

                    <div class="pe-2">
                        <label class="form-label">Spouse 2</label>
                        <select class="form-select" v-model="applicant.spouse2" @change="onChange(applicant.spouse2, key, 1)">
                            <option value="0">None</option>
                            <option v-for="(spouse, key) in spouses" :key="key" :value="spouse.id">
                                {{ spouse.name }}
                            </option>
                        </select>
                    </div>
                </div>
            </div>
        </div>

        <div class="text-center mt-3">
            <button 
                class="btn btn-primary" 
                @click="save()">
                <i class="bi bi-save me-1"></i>Save
            </button>
        </div>

    </div>
</template>

<script>
import { util } from "../mixins/util"
//import { DatePicker } from 'v-calendar'
//import 'v-calendar/dist/style.css'

export default {
    mixins: [util],
    emits: ["events"],
    //components: { DatePicker },
    data() {
        return {
            applicants: [
                /*{
                    id: 1,
                    spouse1: 1,
                    spouse2: 2,
                },
                {
                    id: 2,
                    spouse1: 3,
                    spouse2: 0,
                },*/
            ],
            spouses: [
                /*{
                    id: 1,
                    name: "John Wick",
                },
                {
                    id: 2,
                    name: "Anne Wick",
                },*/
            ],
        }
    },
    mounted() {
        this.getData()
    },
    methods: {
        addApplicant: function() {
            var applicants = this.applicants

            var newApplicant = {
                id: 0,
                spouse1: 0,
                spouse2: 0,
            }

            applicants.push(newApplicant)
            this.applicants = applicants
        },
        findById: function(obj, id) {
            let foundKey = null

            obj.forEach(function(element, key) {
                if(element !== undefined && element.id == id) {
                    foundKey = key
                }
            })

            return foundKey
        },
        save: function() {
            var applicants = this.applicants
            var spouses = JSON.parse(JSON.stringify(this.spouses))
            var findById = this.findById

            applicants.forEach(function(applicant, index) {
                let spouse1Key = findById(spouses, applicant.spouse1)
                if(spouse1Key !== null) delete spouses[spouse1Key]

                let spouse2Key = findById(spouses, applicant.spouse2)
                if(spouse2Key !== null) delete spouses[spouse2Key]
            })

            var message = ''
            spouses.forEach(function(spouse, index) {
                message += spouse.name + '<br>'
            })

            if(message !== '') {
                this.alertMessage = '<b>The following spouses are not assigned</b><br>' + message
                this.showAlert("error")
                return
            }

            applicants.forEach(function(applicant, index) {
                if(applicant.spouse1 == 0 && applicant.spouse2 == 0) {
                    message = 'It should have at least one spouse per applicant group'
                }
            })

            if(message !== '') {
                this.alertMessage = message
                this.showAlert("error")
                return
            }

            this.showPreLoader()

            let data = {
                applicants: this.applicants
            }

            this.axios({
                method: 'put',
                url: '/web/applicants/reorg/' + this.$route.params.opportunityId + '/' + this.$route.params.userId,
                data: data
            })
            .then(response => {
                if(this.checkApiResponse(response)) {
                    this.getData()
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
        onChange: function(id, applicantKey, spouseKey) {
            var applicants = this.applicants

            applicants.forEach(function(applicant, index) {
                if(index == applicantKey && spouseKey == 0) {

                } else if(id == applicant.spouse1) {
                    applicant.spouse1 = 0
                }

                if(index == applicantKey && spouseKey == 1) {

                } else if(id == applicant.spouse2) {
                    applicant.spouse2 = 0
                }
            })

            this.applicants = applicants
        },
        getData: function() {
            this.showPreLoader()

            let data = {
                opportunityId: this.$route.params.opportunityId,
                userId: this.$route.params.userId
            }

            this.axios.get(
                '/web/applicants/reorg',
                {params: data}
            )
            .then(response => {
                if(this.checkApiResponse(response)) {
                    this.applicants = response.data.data.applicants
                    this.spouses = response.data.data.spouses
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
    }
}
</script>
