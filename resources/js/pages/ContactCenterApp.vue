<template>
    <div>
        <ContactCenterViewApp
            :application="application"
            :applicants="applicants"
            :properties="properties"
            :vehicles="vehicles"
            :liabilities="liabilities"
            :presentEmployments="presentEmployments"
            :previousEmployments="previousEmployments"
            :quotes="quotes" />
    </div>
</template>

<script>
import { util } from '../mixins/util'
import ContactCenterViewApp from '../components/ContactCenterViewApp.vue'

export default {
    components: { 
        ContactCenterViewApp,
    },
    mixins: [util],
    emits: ['events'],
    data() {
        return {
            application: {},
            applicants: [],
            properties: [],
            vehicles: [],
            liabilities: [],
            presentEmployments: [],
            previousEmployments: [],
            quotes: []
        }
    },
    mounted() {
        this.getData()
        this.getQuotes()
    },
    methods: {
        getQuotes: function() {
            this.axios({
                method: 'get',
                url: '/web/quote/' + this.$route.params.id
            })
            .then(response => {
                this.quotes = response.data.data.quotes
            })
            .catch(error => {
                console.log(error)
            })
        },
        getData: function() {
            this.showPreLoader()

            this.axios({
                method: 'get',
                url: '/web/contact-center/' + this.$route.params.id + '/all'
            })
            .then(response => {
                this.application = response.data.data.application
                this.applicants = response.data.data.applicants
                this.properties = response.data.data.properties
                this.vehicles = response.data.data.vehicles
                this.liabilities = response.data.data.liabilities
                this.presentEmployments = response.data.data.presentEmployments
                this.previousEmployments = response.data.data.previousEmployments
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
