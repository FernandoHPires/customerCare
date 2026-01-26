<template>
    <div class="modal fade" :id="modalId + checkListType" data-coreui-backdrop="static" tabindex="-1">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" v-if="checkListType === 'RTB'">Ready to Buy Checklist</h5>
                    <h5 class="modal-title" v-else>Appraisal Checklist</h5>                    
                    <button type="button" class="btn-close" @click="closeModal()" aria-label="Close"></button>
                </div>

                <div class="modal-body">
                    <template v-if="pendingData.length > 0">
                        <h5>Before going ahead, please fix the following item(s)</h5>

                        <ul class="list-group">
                            <li class="list-group-item" v-for="(item, index) in pendingData" :key="index">{{ item }}</li>
                        </ul>
                    </template>

                    <template v-else>
                        <ul class="list-group">
                            <template v-for="(question, key) in questions" :key="key">
                                <li v-bind:class="['list-group-item',  question.correctAnswer === false ? 'bg-danger bg-opacity-25' : '']">
                                    <label>{{ key + 1 }}. {{ question.question }}</label>

                                    <template v-if="question.type === 'select'">
                                        <div class="form-group">
                                            <select class="form-select" v-model="question.answer">
                                                <option value="none">--none--</option>
                                                <option v-for="(option, k) in question.option.options" :key="k" :value="option.value">
                                                    {{ option.name }}
                                                </option>
                                            </select>
                                        </div>

                                        <template v-if="question.parentQuestion.length > 0 && question.answer != question.option.answer && question.answer !== 'none'">
                                            <div class="form-group mt-2 ms-4" v-for="(parentQuestion, kk) in question.parentQuestion" :key="kk">

                                                <div v-if="parentQuestion.type === 'select'">
                                                    <label>{{ kk + 1 }}. {{ parentQuestion.question }}</label>
                                                    <div class="form-group">
                                                        <select class="form-select" v-model="parentQuestion.answer">
                                                            <option value="none">--none--</option>
                                                            <option v-for="(option, kkk) in parentQuestion.option.options" :key="kkk" :value="option.value">
                                                                {{ option.name }}
                                                            </option>
                                                        </select>
                                                    </div>
                                                </div>

                                                <div v-else-if="parentQuestion.type === 'input'">
                                                    <label>{{ kk + 1 }}. {{ parentQuestion.question }}</label>
                                                    <input class="form-control" type="text" v-model="parentQuestion.answer" />
                                                </div>
                                                
                                                <template v-else>
                                                    <input 
                                                        type="checkbox" 
                                                        class="form-check-input me-2"
                                                        :disabled="parentQuestion.disable === 'disabled'" 
                                                        v-model="parentQuestion.answer"
                                                    />
                                                    <label>{{ parentQuestion.question }}</label>
                                                </template>
                                            </div>
                                        </template>
                                    </template>

                                    <div v-else-if="question.type === 'input'" class="form-group">
                                        <input class="form-control" type="text" v-model="question.answer" />
                                    </div>

                                    <div v-else-if="question.type === 'checkbox'" class="form-group">
                                        <input class="form-control" type="checkbox" v-model="responses[question.id]" />
                                        <label>{{ question.question }}</label>
                                    </div>
                                </li>
                            </template>
                        </ul>
                    </template>
                </div>

                <div class="modal-footer" v-if="pendingData.length == 0">
                    <button class="btn btn-primary" @click="saveChecklist">
                        <i class="bi bi-save me-1"></i>Save Checklist
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>

<script>
import { util } from '../mixins/util';
import { quote } from '../mixins/quote';

export default {
    mixins: [util, quote],
    components: { },
    props: ['application', 'quote', 'checkListType', 'refreshCount'],
    watch: {
        application: function(newValue, oldValue) {
            this.getData()
        },
        refreshCount: {
            handler(newValue, oldValue) {
                this.getData()
            },
            deep: true
        },
    },
    data() {
        return {
            modalId: 'checklist',
            pendingData: [],
            questions: [],
            responses: {},
        }
    },
    methods: {
        beforeDestroy() {
            this.pendingData = [];
            this.questions = [];
        },          
        closeModal: function() {
            this.hideModal(this.modalId + this.checkListType)
            this.$emit('close')
        },
        saveChecklist: function() {
            this.questions.forEach(question => {
                if(question.answer === 'none') {
                    question.correctAnswer = false
                } else if(question.option.answer === '') {
                    question.correctAnswer = true
                } else if(question.answer === question.option.answer) {
                    question.correctAnswer = true
                } else if(question.parentQuestion.length > 0) {
                    let parentQuestion = question.parentQuestion.find(parentQuestion => parentQuestion.answer === false)
                    if(parentQuestion) {
                        question.correctAnswer = false
                    } else {
                        question.correctAnswer = true
                    }
                } else {
                    if (question.answer === '') {
                        question.correctAnswer = false
                    } else {
                        if ((question.answer !== question.option.answer && question.option.answer !== undefined)) {
                            question.correctAnswer = false
                        } else {
                            question.correctAnswer = true
                        }                        
                    }                    
                }
            })

            var hasError = false
            this.questions.forEach(question => {
                if(question.correctAnswer === false) {
                    hasError = true
                }
            })

            if(hasError) {
                this.alertMessage = 'Please fix the error(s) before saving the checklist'
                this.showAlert('error')
                return
            }

            this.showPreLoader()

            this.axios({
                method: 'post',
                url: '/web/checklist/' + this.quote.id,
                data: {
                    questions: this.questions
                 }
            })
            .then(response => {
                if(response.data.status === 'success') {
                    this.hideModal(this.modalId + this.checkListType)
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
        getData: function() {

            if(this.application.id === undefined || this.quote.id === undefined) return

            this.pendingData = []
            this.questions = []

            this.axios({
                method: 'get',
                url: '/web/checklist/' + this.application.id + '/' + this.checkListType + '/' + this.quote.id
            })
            .then(response => {
                this.pendingData = response.data.data.pendingData
                this.questions = response.data.data.questions
            })
            .catch(error => {
                this.alertMessage = error
                this.showAlert("error")
            })
        },
    }
}
</script>