<template>
    <table class="table table-hover">
        <thead>
            <tr>
                <th @click="filteredSort('company')">
                    <i class="me-1" v-bind:class="[getSortIcon('company')]"></i>Company
                </th>
                <th @click="filteredSort('status')">
                    <i class="me-1" v-bind:class="[getSortIcon('status')]"></i>Status
                </th>
                <th @click="filteredSort('applicationId')">
                    <i class="me-1" v-bind:class="[getSortIcon('applicationId')]"></i>TACL#
                </th>
                <th @click="filteredSort('clientName')">
                    <i class="me-1" v-bind:class="[getSortIcon('clientName')]"></i>Client Name
                </th>
                <th @click="filteredSort('homePhone')">
                    <i class="me-1" v-bind:class="[getSortIcon('homePhone')]"></i>Phone
                </th>
                <th @click="filteredSort('agent')">
                    <i class="me-1" v-bind:class="[getSortIcon('agent')]"></i>Agent
                </th>
                <th @click="filteredSort('signingAgent')">
                    <i class="me-1" v-bind:class="[getSortIcon('signingAgent')]"></i>Signing Agent
                </th>
                <th @click="filteredSort('signingDateTimeS')">
                    <i class="me-1" v-bind:class="[getSortIcon('signingDateTimeS')]"></i>Signing Date
                </th>
                <th @click="filteredSort('fundingDateS')">
                    <i class="me-1" v-bind:class="[getSortIcon('fundingDateS')]"></i>Funding Date
                </th>
                <th @click="filteredSort('followupName')">
                    <i class="me-1" v-bind:class="[getSortIcon('followupName')]"></i>Follow Up
                </th>
                <th @click="filteredSort('followupDateS')">
                    <i class="me-1" v-bind:class="[getSortIcon('followupDateS')]"></i>Follow Up Date
                </th>
                <th @click="filteredSort('amountRequired')">
                    <i class="me-1" v-bind:class="[getSortIcon('amountRequired')]"></i>Amount Required
                </th>
                <th @click="filteredSort('categoryName')">
                    <i class="me-1" v-bind:class="[getSortIcon('categoryName')]"></i>Category
                </th>
                <th @click="filteredSort('readyBuy')">
                    <i class="me-1" v-bind:class="[getSortIcon('readyBuy')]"></i>R2B
                </th>
                <th @click="filteredSort('investor')">
                    <i class="me-1" v-bind:class="[getSortIcon('investor')]"></i>Lender
                </th>
                <th @click="filteredSort('fmCommitted')">
                    <i class="me-1" v-bind:class="[getSortIcon('fmCommitted')]"></i>FM
                </th>
            </tr>
        </thead>

        <tbody>
            <tr v-for="app in filteredData" :key="app.id">
                <td><small>{{ app.company }}</small></td>
                <td><small>{{ app.status }}</small></td>
                <td>
                    <small><a :href="'https://amurfinancial.lightning.force.com/lightning/r/Opportunity/' + app.salesforceId + '/view'">{{ app.applicationId }}</a></small>
                </td>
                <td><small>{{ app.clientName }}</small></td>
                <td><small>{{ app.homePhone }}</small></td>
                <td><small>{{ app.agent }}</small></td>
                <td><small>{{ app.signingAgent }}</small></td>
                <td>
                    <small><span v-bind:class="[app.signingDateTimeOverdue === null ? 'text-info' : (app.signingDateTimeOverdue ? 'text-danger' : '')]">{{ app.signingDateTime }}</span></small>
                </td>
                <td>
                    <small><span v-bind:class="[app.fundingDateOverdue === null ? 'text-info' : (app.fundingDateOverdue ? 'text-danger' : '')]">{{ app.fundingDate }}</span></small>
                </td>
                <td><small>{{ app.followupName }}</small></td>
                <td>
                    <small><span v-bind:class="[app.followUpDateOverdue === null ? 'text-info' : (app.followUpDateOverdue ? 'text-danger' : '')]">{{ app.followupDate }}</span></small>
                </td>
                <td><small>{{ formatNumber(app.amountRequired) }}</small></td>
                <td><small>{{ app.categoryName }}</small></td>
                <td><small>{{ app.readyBuy }}</small></td>
                <td><small>{{ app.investor }}</small></td>
                <td><small>{{ app.fmCommitted }}</small></td>
            </tr>
        </tbody>
    </table>
</template>

<script>
import { util } from '../mixins/util'

export default {
    mixins: [util],
    emits: ['events'],
    props: ['query'],
    data() {
        return {
            apps: [],
            search: '',
            currentSort: 'followupDateS',
            currentSortDir: 'bi-sort-down'            
        }
    },
    mounted() {
        this.getData()
    },
    computed: {
        filteredData() {
            var search = this.search && this.search.toLowerCase()
            var data = this.apps
            data = data.filter(function(row) {
                return Object.keys(row).some(function(key) {
                    return (
                        String(row[key]).toLowerCase().indexOf(search) > -1
                    )
                })
            })
            return data
        }
    },    
    methods: {
        getData() {
            this.showPreLoader()

            this.axios.get(
                '/web/my-apps',
                {params: {userId: this.query}}
            )
            .then(response => {
                this.apps = response.data.data
            })
            .catch(error => {
                this.apps = []
            })
            .finally(() => {
                this.hidePreLoader()
            })
        }
    }
}
</script>