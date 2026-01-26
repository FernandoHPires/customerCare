<template>
    <div class="modal fade" :id="modalId" tabindex="-1" style="display: none">
        <div class="modal-dialog" role="document">
            <div class="modal-content cmsinfo">
                <div class="modal-header">
                    <h5 class="modal-title">Agent Setup Summary - {{ agent?.name }}</h5>
                    <button
                        type="button"
                        class="btn-close"
                       @click="hideModal(modalId)"
                        aria-label="Close"
                    ></button>
                </div>
                <div class="modal-body">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Group Type</th>
                                <th>Commission By</th>
                                <th>Setup Type</th>
                                <th>Percentage/Amount</th>
                                <th>Custom Percentage</th>
                            </tr>
                        </thead>
                        <tbody>
                             <tr v-for="(info, index) in setups" :key="info.id">
                                <td>{{ info?.name }}</td>
                                <td>{{ info?.commission_by === 'a' ? 'Amount' : info?.commission_by === 'p' ? 'Percentage' : info?.commission_by === 'b' && 'Both' }}</td>
                                <td>{{ info?.setup_by  === 'd' ? 'Default' : 'Custom'}}</td>
                                <template v-if ="info?.setup_by === 'd' && info?.commission_by ==='b'">
                                    <td>{{ info?.percentage }} %   -  $ {{ info?.amount }} </td>
                                    <td></td>
                                </template>
                                <template v-if="info?.setup_by === 'd' && info.commission_by ==='p'">
                                    <td>{{ info?.percentage }} %</td>
                                    <td></td>
                                    <td></td>
                                </template>
                                  <template v-if="info?.setup_by === 'd' && info.commission_by === 'a'">
                                        <td>$ {{ info?.amount }}</td>
                                        <td></td>
                                        <td></td>
                                    </template>
                                <template v-if="info?.setup_by === 'c'"  v-for="custom in customSetups">
                                    <td v-if="info?.name === custom.type">$ {{ custom?.amount}}</td>
                                    <td v-if="info?.name === custom.type">{{custom?.percentage }} %</td>
                                </template>
                        </tr>
                        </tbody>
                    </table>
                </div>

                <div class="modal-footer">
                    <button
                        class="btn btn-outline-dark"
                        type="button"
                        @click="hideModal(modalId)"
                    >
                        <i class="bi-x-lg me-1"></i>Close
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>

<script>

import { util } from "../../mixins/util";

export default {
    mixins: [util],
     props: {
        modalId: String,
        agent: Object,
    },
    data() {
        return {
            customSetups: [],
            setups: [],
        };
    },
    watch: {
        agent: {
            handler(newValue, oldValue) {
                this.getData()
            },
            deep: true,
        }
    },
    mounted() {
    },
    methods: {
        getData: function() {

            this.showPreLoader()

            this.axios({
                method: "get",
                url: "/web/cms/agent-setup-cms-info/" + this.agent.id + "/agent"
            })
            .then((response) => {
                if (this.checkApiResponse(response)) {
                    this.setups = response.data.data.setups;
                    this.customSetups = response.data.data.customSetup;
                } else {
                    this.alertMessage = "Error in getting agents groups!";
                    this.showAlert(response.data.status);
                }
            })
            .catch((err) => {
                this.alertMessage = err;
                this.showAlert('error');
            })
            .finally(() => {
                this.hidePreLoader();
            })
        }
    },
}
</script>
