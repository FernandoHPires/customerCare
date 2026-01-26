<template>
    <div class="bg-secondary ps-3 py-2 reports-main">
        <div class="d-flex align-items-center">
            <h2 class="text-white">{{ reportName.toUpperCase() }}</h2>
            <div class="ms-auto">
                <div class="input-group pe-3">
                    <span class="input-group-text"
                        ><i class="bi-search"></i></span
                    ><input
                        type="text"
                        class="form-control"
                        placeholder="Search Reports"
                        v-model="inputValue"
                    />
                </div>
            </div>
        </div>
    </div>
    <div v-for="report in reports" class="my-5">
        <table
            class="table table-hover table-bordered"
            v-if="reportName === report.name"
        >
            <thead>
                <tr>
                    <th
                        class="bg-info"
                        scope="col"
                        v-for="tableHead in report.tableHead"
                    >
                        {{ tableHead }}
                    </th>
                </tr>
            </thead>
            <tbody v-for="tableData in report.tableData">
                <tr>
                    <td v-if="tableData.id">{{ tableData.id }}</td>
                    <td v-if="tableData.first">{{ tableData.first }}</td>
                    <td v-if="tableData.last">{{ tableData.last }}</td>
                    <td v-if="tableData.phone">{{ tableData.phone }}</td>
                    <td v-if="tableData.city">{{ tableData.city }}</td>
                    <td v-if="tableData.date">{{ tableData.date }}</td>
                </tr>
            </tbody>
        </table>
    </div>
</template>
<script>
import { util } from "../../mixins/util";

export default {
    mixins: [util],
    data() {
        return {
            reportName: "",
            inputValue: "",
        };
    },
    mounted() {
        this.reportName = this.$route.params.name;
    },
    methods: {},
};
</script>
