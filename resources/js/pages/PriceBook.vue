<template>
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item">
                <RouterLink to="/">Home</RouterLink>
            </li>
            <li class="breadcrumb-item active">
                Pricebook
            </li>
        </ol>
    </nav>

    <div class="row">
        <div class="col-6 mb-1">
            <div class="card">
                <div class="card-header">
                    Filters
                </div>
                <div class="card-body">
                    <div class="d-flex">

                        <div class="pe-3">
                            <label for="" class="form-label">Company</label>
                            <select class="form-select" v-model="company">
                                    <option value=""></option>
                                    <option value="ACL">ACL</option>
                                    <option value="SQC">SQC</option>
                            </select>
                        </div>

                        <div class="pe-3">
                            <label for="" class="form-label">Position</label>
                            <select class="form-select" v-model="position">
                                    <option value=""></option>
                                    <option value="1st">1st</option>
                                    <option value="2nd">2nd</option>
                            </select>
                        </div>

                        <div class="pe-3">
                            <label for="" class="form-label">Province</label>
                            <select class="form-select" v-model="province">
                                    <option value=""></option>
                                    <option value="BC">BC</option>
                                    <option value="ON">ON</option>
                                    <option value="AB">AB</option>
                            </select>
                        </div>

                        <div class="pe-6">
                            <label for="" class="form-label">City Classification</label>
                            <select class="form-select" v-model="cityClassification">
                                    <option value=""></option>
                                    <option value="U">Urban</option>
                                    <option value="R">Rural</option>
                            </select>
                        </div>


                        <div>
                            <label for="" class="form-label">Display</label>
                            <select class="form-select" v-model="display">
                                    <option value="A">All Records</option>
                                    <option value="C">Current Records</option>
                            </select>
                        </div>

                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="card mt-3">
        <div class="card-header">
            Pricebook
        </div>

        <div class="card-body">
            <table class="table table-hover">
                <thead>
                        <tr>
                            <th>Start LTV</th>
                            <th>End LTV</th>
                            <th>Interest Rate</th>
                            <th>Effective At</th>
                            <th></th>
                        </tr>
                    </thead>

                <tbody v-if="pricebook.length == 0">
                    <tr>
                        <td colspan="6">There are no Data</td>
                    </tr>
                </tbody>

                <tbody v-else>
                    <tr v-for="(row, key) in pricebook" :key="key">
                        <td>{{ row.startLtv }}</td>
                        <td>{{ row.endLtv }}</td>
                        <template v-if="row.isEditing === 'no'">
                            <td>{{ row.interestRate }}</td>
                            <td>{{ formatPhpDate(row.effectiveAt) }}</td>
                            <td class="text-end nowrap">
                                <button 
                                    type="button"
                                    class="btn me-2 btn-primary" 
                                    @click="edit(row)">
                                    <i class="bi-pencil me-1"></i>Edit
                                </button>
                            </td>
                        </template>

                        <template v-else>
                            <td>
                                <input type="text" class="form-control" v-model="row.interestRate"/>
                            </td>
                            <td>
                                <input type="date" class="form-control" v-model="effectiveAt"/>
                            </td>
                            <td class="text-end nowrap">
                            <button
                                class="btn btn-success"
                                type="button"
                                @click="save(row)">
                                <i class="bi-save me-1"></i>Save
                            </button>
                            </td>
                        </template>

                    </tr>
                </tbody>
            </table>
        </div>
    </div>

</template>

<script>
import { util } from '../mixins/util'
import 'v-calendar/dist/style.css'

export default {
    mixins: [util],
    emits: ['events'],
    components: { },
    watch: {
        company: {
            handler(newValue, oldValue) {
                this.getData()
            },
            deep: true
        },
        position: {
            handler(newValue, oldValue) {
                this.getData()
            },
            deep: true
        },
        province: {
            handler(newValue, oldValue) {
                this.getData()
            },
            deep: true
        },
        cityClassification: {
            handler(newValue, oldValue) {
                this.getData()
            },
            deep: true
        },
        display: {
            handler(newValue, oldValue) {
                this.getData()
            },
            deep: true
        }, 
    },      
    data() {
        return {
            company: '',
            position: '',
            province: '',
            cityClassification: '',
            display: 'C',
            effectiveAt: null,
            pricebook: [],
        }
    },
    mounted() {
        this.getData()
    }, 
    methods: {
        getData: function() {

            this.effectiveAt = null

            if (this.company === '' || this.position === '' || this.province === '' || this.cityClassification === '' ) {
                return;
            }

            this.showPreLoader();

            let data = {
                company : this.company,
                position : this.position,
                province : this.province,
                cityClassification : this.cityClassification,
                display: this.display
            }

            this.axios.get(
                'web/pricebook',
                {params: data}
            )
            .then(response => {
                if(this.checkApiResponse(response)) {
                    this.pricebook = response.data.data
                } else {
                    this.alertMessage = response.data.message
                    this.showAlert(response.data.status)
                }
            })
            .catch(error => {
                this.alertMessage = error
                this.showAlert('error')
            })
            .finally(() => {
                this.hidePreLoader();
            })
        },
        edit: function(row) {
            row.isEditing = 'yes';
        },
        save: function(row) {

            if (row.interestRate < 0) {
                this.alertMessage = 'The interest rate must be greater than zero'
                this.showAlert('error')
                return;
            }
            if (this.effectiveAt == '' || this.effectiveAt == null) {
                this.alertMessage = 'Effective At must be provided'
                this.showAlert('error')
                return;
            }

            const today = new Date();
            today.setHours(0, 0, 0, 0);

            const effectiveDate = new Date(this.effectiveAt);
            effectiveDate.setHours(0, 0, 0, 0);

            if (effectiveDate < today) {
                this.alertMessage = 'Effective At must be in the future';
                this.showAlert('error');
                return;
            }

            this.showPreLoader()

            this.axios({
                method: "post",
                url: "web/pricebook",
                data: {
                    id: row.id,
                    interestRate: row.interestRate,
                    effectiveAt: this.effectiveAt,
                }
            })
            .then((response) => {
                if (this.checkApiResponse(response)) {
                    this.alertMessage = "New interest rate created!";
                    this.showAlert(response.data.status);
                    this.effectiveAt = null
                    this.getData();
                } else {
                    this.alertMessage = "Error in getting commission setup!";
                    this.showAlert(response.data.status);
                }
            }).finally(() => {
                this.hidePreLoader(); 
            })


            

        }
    }
}
</script>