<template>
    <ul class="nav nav-tabs d-flex align-items-center w-100 mb-2 sticky-top bg-light-ultra" style="top: 57px; z-index: 100;">
        <li class="nav-item" v-for="tab in tabs" :key="tab.name">
            <a
                class="nav-link text-dark"
                :class="{ active: selectedTab === tab.name }"
                href="#"
                @click.prevent="tabChanged(tab.name)"
            >
                {{ tab.label }}
            </a>
        </li>
    </ul>

    <div v-if="selectedTab == 'dashboard'">
        <Dashboard />
    </div>

    <div v-if="selectedTab == 'visaoGeral'">
        <VisaoGeral />
    </div>

    <div v-if="selectedTab == 'simulacaoViabilidade'">
        <SimulacaoViabilidade />
    </div>
    
</template>

<script>
import { util } from '../mixins/util'
import VisaoGeral from '../components/VisaoGeral.vue'
import Dashboard from './Dashboard.vue'
import SimulacaoViabilidade from './SimulacaoViabilidade.vue'

export default {
    components: {
        VisaoGeral,
        Dashboard,
        SimulacaoViabilidade
    },
    mixins: [util],
    emits: ['events'],
    data() {
        return {            
            tabs: [
                { name: 'dashboard', label: 'Dashboard' },
                { name: 'visaoGeral', label: 'Visão Geral do Portfólio' },
                { name: 'simulacaoViabilidade', label: 'Simulação de Viabilidade' },
            ],
            selectedTab: 'dashboard'
        }
    },  
    mounted() {
        if (this.$route.query.tab) {
            this.selectedTab = this.$route.query.tab;
        }
        this.getData()
    },
    methods: {
        tabChanged: function(tab) {
            this.selectedTab = tab
        },        
        getData: function() {

            this.showPreLoader()

            this.axios({
                method: 'get',
                url: '/web/portfolio' 
            })
            .then(response => {
                if (this.checkApiResponse(response)) {
                  
                    console.log('Portfolio data:', response.data.data);
                } else {
                    this.alertMessage = 'Error in getting portfolio data!';
                    this.showAlert(response.data.status);
                }
                
            })
            .catch(error => {
                this.alertMessage = error
                this.showAlert("error")
            })
            .finally(() => {
                this.hidePreLoader()
            })
        }
    }
}
</script>

<style lang="scss" scoped>
.nav-tabs .ml-auto {
    margin-left: auto;
    list-style: none;
}
.form-label {
    margin-top: 0.5rem;
    margin-bottom: 0px;
}
.table-header {
    padding-right: 1rem;
    padding-bottom: 0;
    border: none; 
    font-size: 0.875em;
    font-weight: bold;
}
.table-body {
    padding-right: 1rem;
    padding-top: 0;
    border: none;
    font-size: 0.875em;
    font-weight: normal;
}
</style>