<template>
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item">
                <RouterLink to="/">Home</RouterLink>
            </li>
            <li class="breadcrumb-item">
                <RouterLink to="/reports">Reports</RouterLink>
            </li>
            <li class="breadcrumb-item active">
                Mic Pipeline
            </li>
        </ol>
    </nav>  

    <div class="card" style="max-height : 85vh">
        <div class="card-header">
            <h5>MIC Pipeline Report - {{ getDate }}</h5>
        </div>
        
        <div class="card-body table-responsive">
            <!--total-->
            <table class="table table-bordered table-hover">
                <thead>
                    <tr>
                        <th class="nowrap company-origin text-white bg-dark-blue">Total for MIC</th>
                        <th
                            v-for="company in companies"
                            :key="company"
                            colspan="4"
                            class="text-center text-white bg-dark-blue"
                        >
                            {{ company }}
                        </th>
                        <th class="text-center  text-white bg-dark-blue" colspan="2">Total</th>
                    </tr>
                    <tr>
                        <th>Status</th>
                        <th class="text-center">Count</th>
                        <th class="text-center">Gross Amount</th>
                        <th class="text-center">Weighted Average LTV</th>
                        <th class="text-center">Weighted Average Yield</th>
                        <th class="text-center">Count</th>
                        <th class="text-center">Gross Amount</th>
                        <th class="text-center">Weighted Average LTV</th>
                        <th class="text-center">Weighted Average Yield</th>
                        <th class="text-center">Count</th>
                        <th class="text-center">Gross Amount</th>
                        <th class="text-center">Weighted Average LTV</th>
                        <th class="text-center">Weighted Average Yield</th>
                        <th class="text-center">Total Count</th>
                        <th class="text-center">Total Gross Amount</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="status in statusOrder" class="first-row">
                        <td
                            :class="[
                                status !== 'Funded' && status !== 'Pipeline' ? 'indent' : '',
                                status === 'Pipeline' ? 'pipeline' : '',
                                'first-row'
                            ]"
                        >
                            {{ status }}
                        </td>

                        <template v-if="status === 'Pipeline'">
                            <td class="first-row text-center"></td>
                            <td class="first-row text-center"></td>
                            <td class="first-row text-center"></td>
                            <td class="first-row text-center"></td>
                            <td class="first-row text-center"></td>
                            <td class="first-row text-center"></td>
                            <td class="first-row text-center"></td>
                            <td class="first-row text-center"></td>
                            <td class="first-row text-center"></td>
                            <td class="first-row text-center"></td>
                            <td class="first-row text-center"></td>
                            <td class="first-row text-center"></td>
                        </template>

                        <template
                            v-for="company in companyWithIds"
                            :key="company.id"
                        >
                            <template
                                v-for="(item, totalStatus) in totalAmount"
                            >
                                <template v-if="status === totalStatus">
                                    <template
                                        v-if="
                                            totalAmount[totalStatus][
                                                company.id
                                            ] !== undefined
                                        "
                                    >
                                        <td class="text-center pe-3">
                                            {{
                                                totalAmount[totalStatus][
                                                    company.id
                                                ][0]?.count
                                            }}
                                        </td>
                                        <td class="text-center pe-3">
                                            {{
                                                formatDecimal(
                                                    totalAmount[totalStatus][
                                                        company.id
                                                    ][0]?.gross_amount
                                                )
                                            }}
                                        </td>
                                        <td class="text-center pe-3">
                                            {{
                                                formatDecimal(
                                                    totalAmount[totalStatus][
                                                        company.id
                                                    ][0]?.weighted_average_lvt.toFixed(
                                                        2
                                                    )
                                                )
                                            }}%
                                        </td>
                                        <td class="text-center pe-3">
                                            {{
                                                formatDecimal(
                                                    totalAmount[totalStatus][
                                                        company.id
                                                    ][0]?.weighted_average_yield.toFixed(
                                                        2
                                                    )
                                                )
                                            }}%
                                        </td>
                                    </template>
                                    <template v-else>
                                        <td></td>
                                        <td></td>
                                        <td></td>
                                        <td></td>
                                    </template>
                                </template>
                            </template>
                        </template>
                        <td class="text-center pe-3">
                            {{ getTotalStatus("total", status)?.count }}
                        </td>
                        <td class="text-center pe-3">
                            {{
                                formatDecimal(
                                    getTotalStatus("total", status)
                                        ?.gross_amount
                                )
                            }}
                        </td>
                    </tr>

                    <tr>
                        <td class="sub-total-row bold sub-total">
                            <span>Sub Total Pipeline</span>
                        </td>
                        <template v-for="company in companies" :key="company">
                            <td class="sub-total-row bold text-center pe-3">
                                {{ getCompanySubTotalPipeline(company).count }}
                            </td>
                            <td class="sub-total-row bold text-center pe-3">
                                {{ getCompanySubTotalPipeline(company).totalGross }}
                            </td>
                            <td class="sub-total-row"></td>
                            <td class="sub-total-row"></td>
                        </template>
                        <td class="sub-total-row bold text-center pe-3">
                            {{ getTotalGrossCountAndAmount("total").grossCountWithoutFunded }}
                        </td>
                        <td class="sub-total-row bold text-center pe-3">
                            {{ formatDecimal(getTotalGrossCountAndAmount("total").grossGrossAmountWithoutFunded) }}
                        </td>
                    </tr>
                    <tr>
                        <td class="total-row bold total">
                            <span>Total</span>
                        </td>
                        <template v-for="company in companies" :key="company">
                            <td class="total-row bold text-center pe-3">
                                {{ getCompanyTotal(company).count }}
                            </td>
                            <td class="total-row bold text-center pe-3">
                                {{ getCompanyTotal(company).totalGross }}
                            </td>
                            <td class="total-row"></td>
                            <td class="total-row"></td>
                        </template>
                        <td class="total-row bold text-center pe-3">
                            {{ getTotalGrossCountAndAmount("total").grossCount }}
                        </td>
                        <td class="total-row bold text-center pe-3">
                            {{ formatDecimal(getTotalGrossCountAndAmount("total").grossGrossAmount) }}
                        </td>
                    </tr>
                </tbody>
            </table>
            <!--end of total-->

            <!-- Apline table with all fileds -->
            <table class="table table-bordered table-hover">
                <thead>
                    <tr>
                        <th class="nowrap company-origin text-white bg-green">Alpine Credits</th>
                        <th
                            v-for="company in companies"
                            :key="company"
                            colspan="4"
                            class="text-center text-white bg-green"
                        >
                            {{ company }}
                        </th>
                        <th class="text-center text-white bg-green" colspan="2">Total</th>
                    </tr>
                    <tr class="text-grey">
                        <th>Status</th>
                        <th>Count</th>
                        <th class="text-center pe-3">Gross Amount</th>
                        <th class="text-center">Weighted Average LTV</th>
                        <th class="text-center">Weighted Average Yield</th>
                        <th class="text-center">Count</th>
                        <th class="text-center pe-3">Gross Amount</th>
                        <th class="text-center">Weighted Average LTV</th>
                        <th class="text-center">Weighted Average Yield</th>
                        <th class="text-center">Count</th>
                        <th class="text-center pe-3">Gross Amount</th>
                        <th class="text-center">Weighted Average LTV</th>
                        <th class="text-center">Weighted Average Yield</th>
                        <th class="text-center">Total Count</th>
                        <th class="text-center">Total Gross Amount</th>
                    </tr>
                </thead>

                <tbody>
                    <tr
                        class="first-row"
                        v-for="(data, status) in sortedAlpineData"
                        :key="status"
                    >
                        <td
                            :class="[
                                status !== 'Funded' && status !== 'Pipeline' ? 'indent' : '',
                                status === 'Pipeline' ? 'pipeline' : '',
                                'first-row'
                            ]"
                        >
                            {{ status }}
                        </td>

                        <template v-if="status === 'Pipeline'">
                            <td class="first-row"></td>
                            <td class="first-row"></td>
                            <td class="first-row"></td>
                            <td class="first-row"></td>
                            <td class="first-row"></td>
                            <td class="first-row"></td>
                            <td class="first-row"></td>
                            <td class="first-row"></td>
                            <td class="first-row"></td>
                            <td class="first-row"></td>
                            <td class="first-row"></td>
                            <td class="first-row"></td>
                        </template>
                        
                        <template
                            v-else
                            v-for="company in companies"
                            :key="company"
                        >
                            <template v-if="data[company]">
                                <td class="text-center pe-3">
                                    {{ data[company][0].count }}
                                </td>
                                <td class="text-center pe-3">
                                    {{
                                        formatDecimal(
                                            data[company][0].gross_amount
                                        )
                                    }}
                                </td>
                                <td class="text-center pe-3">
                                    {{
                                        formatDecimal(
                                            data[company][0]
                                                .weighted_average_lvt
                                        )
                                    }}%
                                </td>
                                <td class="text-center pe-3">
                                    {{
                                        formatDecimal(
                                            data[company][0]
                                                .weighted_average_yield
                                        )
                                    }}%
                                </td>
                            </template>
                            <template v-else>
                                <td></td>
                                <td></td>
                                <td></td>
                                <td></td>
                            </template>
                        </template>
                        <td class="text-center pe-3">
                            {{
                                getTotalStatus("Alpine Credits", status)?.count
                            }}
                        </td>
                        <td class="text-center pe-3">
                            {{
                                formatDecimal(
                                    getTotalStatus("Alpine Credits", status)
                                        ?.gross_amount
                                )
                            }}
                        </td>
                    </tr>
                    <tr>
                        <td class="sub-total-row bold sub-total">
                            <span>Sub Total Pipeline</span>
                        </td>
                        <template v-for="company in companies" :key="company">
                            <td class="sub-total-row bold text-center pe-3">
                                {{
                                    getSubTotal(
                                        "Alpine Credits Limited",
                                        company
                                    )?.count
                                }}
                            </td>
                            <td class="sub-total-row bold text-center pe-3">
                                {{
                                    formatDecimal(
                                        getSubTotal(
                                            "Alpine Credits Limited",
                                            company
                                        )?.gross_amount
                                    )
                                }}
                            </td>
                            <td class="sub-total-row"></td>
                            <td class="sub-total-row"></td>
                        </template>
                        <td class="sub-total-row bold text-center pe-3">
                            {{
                                getTotalGrossCountAndAmount("Alpine Credits")
                                    .grossCountWithoutFunded
                            }}
                        </td>
                        <td class="sub-total-row bold text-center pe-3">
                            {{
                                formatDecimal(
                                    getTotalGrossCountAndAmount(
                                        "Alpine Credits"
                                    ).grossGrossAmountWithoutFunded
                                )
                            }}
                        </td>
                    </tr>
                    <!-- getTotalWithFunded -->
                    <tr>
                        <td class="total-row bold total">
                            <span>Total</span>
                        </td>
                        <template v-for="company in companies" :key="company">
                            <td class="total-row bold text-center pe-3">
                                {{
                                    getTotalWithFunded(
                                        "Alpine Credits Limited",
                                        company
                                    )?.count
                                }}
                            </td>
                            <td class="total-row bold text-center pe-3">
                                {{
                                    formatDecimal(
                                        getTotalWithFunded(
                                            "Alpine Credits Limited",
                                            company
                                        )?.gross_amount
                                    )
                                }}
                            </td>
                            <td class="total-row"></td>
                            <td class="total-row"></td>
                        </template>
                        <td class="total-row bold text-center pe-3">
                            {{
                                getTotalGrossCountAndAmount("Alpine Credits")
                                    .grossCount
                            }}
                        </td>
                        <td class="total-row bold text-center pe-3">
                            {{
                                formatDecimal(
                                    getTotalGrossCountAndAmount(
                                        "Alpine Credits"
                                    ).grossGrossAmount
                                )
                            }}
                        </td>
                    </tr>
                </tbody>
            </table>
            <!--end of alpine-->

            <!---Sequence Table with all fields-->
            <table
                class="table table-bordered table-hover table1"
            >
                <thead>
                    <tr>
                        <th class="nowrap company-origin text-white bg-purple">Sequence Capital</th>
                        <th
                            v-for="company in companies"
                            :key="company"
                            colspan="4"
                            class="text-center text-white bg-purple"
                        >
                            {{ company }}
                        </th>
                        <th class="text-center text-white bg-purple" colspan="2">Total</th>
                    </tr>
                    <tr>
                        <th>Status</th>
                        <th class="text-center">Count</th>
                        <th class="text-center pe-3">Gross Amount</th>
                        <th class="text-center">Weighted Average LTV</th>
                        <th class="text-center">Weighted Average Yield</th>
                        <th class="text-center">Count</th>
                        <th class="text-center pe-3">Gross Amount</th>
                        <th class="text-center">Weighted Average LTV</th>
                        <th class="text-center">Weighted Average Yield</th>
                        <th class="text-center">Count</th>
                        <th class="text-center pe-3">Gross Amount</th>
                        <th class="text-center">Weighted Average LTV</th>
                        <th class="text-center">Weighted Average Yield</th>
                        <th class="text-center">Total Count</th>
                        <th class="text-center">Total Gross Amount</th>
                    </tr>
                </thead>
                <tbody>
                    <tr
                        class="first-row"
                        v-for="status in statusOrder"
                        :key="status"
                    >
                        <td
                            :class="{
                                indent:
                                    status !== 'Funded' &&
                                    status !== 'Pipeline',
                                pipeline: status === 'Pipeline',
                            }"
                        >
                            {{ status }}
                        </td>
                        <template
                            v-for="company in companies"
                            :key="company"
                            class="mic-info"
                        >
                            <template
                                v-if="
                                    status === 'Pipeline' ||
                                    sortedSequenceData[status]?.[company]
                                "
                            >
                                <td class="text-center pe-3">
                                    {{
                                        sortedSequenceData[status]?.[
                                            company
                                        ]?.[0]?.count || ""
                                    }}
                                </td>
                                <td class="text-center pe-3">
                                    {{
                                        formatDecimal(
                                            sortedSequenceData[status]?.[
                                                company
                                            ]?.[0]?.gross_amount || ""
                                        )
                                    }}
                                </td>
                                <td class="text-center pe-3">
                                    {{
                                        formatDecimal(
                                            sortedSequenceData[status]?.[
                                                company
                                            ]?.[0]?.weighted_average_lvt || ""
                                        )
                                    }}<span v-if="status !== 'Pipeline'"
                                        >%</span
                                    >
                                </td>
                                <td class="text-center pe-3">
                                    {{
                                        formatDecimal(
                                            sortedSequenceData[status]?.[
                                                company
                                            ]?.[0]?.weighted_average_yield || ""
                                        )
                                    }}<span v-if="status !== 'Pipeline'"
                                        >%</span
                                    >
                                </td>
                            </template>
                            <template v-else>
                                <td></td>
                                <td></td>
                                <td></td>
                                <td></td>
                            </template>
                        </template>
                        <td class="text-center pe-3">
                            {{
                                getTotalStatus("Sequence Capital", status)
                                    ?.count
                            }}
                        </td>
                        <td class="text-center pe-3">
                            {{
                                formatDecimal(
                                    getTotalStatus("Sequence Capital", status)
                                        ?.gross_amount
                                )
                            }}
                        </td>
                    </tr>
                    <tr>
                        <td class="sub-total-row bold sub-total">
                            <span>Sub Total Pipeline</span>
                        </td>
                        <template v-for="company in companies" :key="company">
                            <td class="sub-total-row bold text-center pe-3">
                                {{
                                    getSubTotal("Sequence Capital", company)
                                        ?.count
                                }}
                            </td>
                            <td class="sub-total-row bold text-center pe-3">
                                {{
                                    formatDecimal(
                                        getSubTotal("Sequence Capital", company)
                                            ?.gross_amount
                                    )
                                }}
                            </td>
                            <td class="sub-total-row"></td>
                            <td class="sub-total-row"></td>
                        </template>
                        <td class="sub-total-row bold text-center pe-3">
                            {{
                                getTotalGrossCountAndAmount("Sequence Capital")
                                    .grossCountWithoutFunded
                            }}
                        </td>
                        <td class="sub-total-row bold text-center pe-3">
                            {{
                                formatDecimal(
                                    getTotalGrossCountAndAmount(
                                        "Sequence Capital"
                                    ).grossGrossAmountWithoutFunded
                                )
                            }}
                        </td>
                    </tr>
                    <!-- getTotalWithFunded -->
                    <tr>
                        <td class="total-row bold total">
                            <span>Total</span>
                        </td>
                        <template v-for="company in companies" :key="company">
                            <td class="total-row bold text-center pe-3">
                                {{
                                    getTotalWithFunded(
                                        "Sequence Capital",
                                        company
                                    )?.count
                                }}
                            </td>
                            <td class="total-row bold text-center pe-3">
                                {{
                                    formatDecimal(
                                        getTotalWithFunded(
                                            "Sequence Capital",
                                            company
                                        )?.gross_amount
                                    )
                                }}
                            </td>
                            <td class="total-row"></td>
                            <td class="total-row"></td>
                        </template>
                        <td class="total-row bold text-center pe-3">
                            {{
                                getTotalGrossCountAndAmount("Sequence Capital")
                                    .grossCount
                            }}
                        </td>
                        <td class="total-row bold text-center pe-3">
                            {{
                                formatDecimal(
                                    getTotalGrossCountAndAmount(
                                        "Sequence Capital"
                                    ).grossGrossAmount
                                )
                            }}
                        </td>
                    </tr>
                </tbody>
            </table>
            <!--end of sequence-->
            <div v-if="hasDirectToMICData">
                <!--Direct To MIC-->
                <table class="table table-bordered table-hover table1">
                    <thead>
                        <tr>
                            <th class="nowrap company-origin text-white bg-royal-blue">Direct To MIC</th>
                            <th
                                v-for="company in companies"
                                :key="company"
                                colspan="4"
                                class="text-center text-white bg-royal-blue"
                            >
                                {{ company }}
                            </th>
                            <th class="text-center text-white bg-royal-blue" colspan="2">Total</th>
                        </tr>
                        <tr>
                            <th>Status</th>
                            <th class="text-center">Count</th>
                            <th class="text-center pe-3">Gross Amount</th>
                            <th class="text-center">Weighted Average LTV</th>
                            <th class="text-center">Weighted Average Yield</th>
                            <th class="text-center">Count</th>
                            <th class="text-center pe-3">Gross Amount</th>
                            <th class="text-center">Weighted Average LTV</th>
                            <th class="text-center">Weighted Average Yield</th>
                            <th class="text-center">Count</th>
                            <th class="text-center pe-3">Gross Amount</th>
                            <th class="text-center">Weighted Average LTV</th>
                            <th class="text-center">Weighted Average Yield</th>
                            <th class="text-center">Total Count</th>
                            <th class="text-center">Total Gross Amount</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr
                            class="first-row"
                            v-for="status in statusOrder"
                            :key="status"
                        >
                            <td
                                :class="{
                                    indent:
                                        status !== 'Funded' &&
                                        status !== 'Pipeline',
                                    pipeline: status === 'Pipeline',
                                }"
                            >
                                {{ status }}
                            </td>
                            <template
                                v-for="company in companies"
                                :key="company"
                                class="mic-info"
                            >
                                <template
                                    v-if="
                                        status === 'Pipeline' ||
                                        sortedDirectToMICData[status]?.[company]
                                    "
                                >
                                    <td class="text-center pe-3">
                                        {{
                                            sortedDirectToMICData[status]?.[
                                                company
                                            ]?.[0]?.count || ""
                                        }}
                                    </td>
                                    <td class="text-center pe-3">
                                        {{
                                            formatDecimal(
                                                sortedDirectToMICData[status]?.[
                                                    company
                                                ]?.[0]?.gross_amount || ""
                                            )
                                        }}
                                    </td>
                                    <td class="text-center pe-3">
                                        {{
                                            formatDecimal(
                                                sortedDirectToMICData[status]?.[
                                                    company
                                                ]?.[0]?.weighted_average_lvt || ""
                                            )
                                        }}<span v-if="status !== 'Pipeline'"
                                            >%</span
                                        >
                                    </td>
                                    <td class="text-center pe-3">
                                        {{
                                            formatDecimal(
                                                sortedDirectToMICData[status]?.[
                                                    company
                                                ]?.[0]?.weighted_average_yield || ""
                                            )
                                        }}<span v-if="status !== 'Pipeline'"
                                            >%</span
                                        >
                                    </td>
                                </template>
                                <template v-else>
                                    <td></td>
                                    <td></td>
                                    <td></td>
                                    <td></td>
                                </template>
                            </template>
                            <td class="text-center pe-3">
                                {{
                                    getTotalStatus("DirectToMIC", status)
                                        ?.count
                                }}
                            </td>
                            <td class="text-center pe-3">
                                {{
                                    formatDecimal(
                                        getTotalStatus("DirectToMIC", status)
                                            ?.gross_amount
                                    )
                                }}
                            </td>
                        </tr>
                        <tr>
                            <td class="sub-total-row bold sub-total">
                                <span>Sub Total Pipeline</span>
                            </td>
                            <template v-for="company in companies" :key="company">
                                <td class="sub-total-row bold text-center pe-3">
                                    {{
                                        getSubTotal("DirectToMIC", company)
                                            ?.count
                                    }}
                                </td>
                                <td class="sub-total-row bold text-center pe-3">
                                    {{
                                        formatDecimal(
                                            getSubTotal("DirectToMIC", company)
                                                ?.gross_amount
                                        )
                                    }}
                                </td>
                                <td class="sub-total-row"></td>
                                <td class="sub-total-row"></td>
                            </template>
                            <td class="sub-total-row bold text-center pe-3">
                                {{
                                    getTotalGrossCountAndAmount("DirectToMIC")
                                        .grossCountWithoutFunded
                                }}
                            </td>
                            <td class="sub-total-row bold text-center pe-3">
                                {{
                                    formatDecimal(
                                        getTotalGrossCountAndAmount(
                                            "DirectToMIC"
                                        ).grossGrossAmountWithoutFunded
                                    )
                                }}
                            </td>
                        </tr>
                        <!-- getTotalWithFunded -->
                        <tr>
                            <td class="total-row bold total">
                                <span>Total</span>
                            </td>
                            <template v-for="company in companies" :key="company">
                                <td class="total-row bold text-center pe-3">
                                    {{
                                        getTotalWithFunded(
                                            "DirectToMIC",
                                            company
                                        )?.count
                                    }}
                                </td>
                                <td class="total-row bold text-center pe-3">
                                    {{
                                        formatDecimal(
                                            getTotalWithFunded(
                                                "DirectToMIC",
                                                company
                                            )?.gross_amount
                                        )
                                    }}
                                </td>
                                <td class="total-row"></td>
                                <td class="total-row"></td>
                            </template>
                            <td class="total-row bold text-center pe-3">
                                {{
                                    getTotalGrossCountAndAmount("DirectToMIC")
                                        .grossCount
                                }}
                            </td>
                            <td class="total-row bold text-center pe-3">
                                {{
                                    formatDecimal(
                                        getTotalGrossCountAndAmount(
                                            "DirectToMIC"
                                        ).grossGrossAmount
                                    )
                                }}
                            </td>
                        </tr>
                    </tbody>
                </table>
                <!--end of other-->
            </div>
        </div>
    </div>
</template>

<script>
import { util } from "../../mixins/util";

export default {
    mixins: [util],
    emits: ['events'],
    data() {
        return {
            pipeline: [],
            micNames: [],
            companies: [
                "ACIF",
                "ACCIF",
                "ACHYF",
            ],
            companyWithIds: [
                { name: "ACIF", id: 31 },
                { name: "ACCIF", id: 248 },
                { name: "ACHYF", id: 100 },
            ],
            statusOrder: [
                "Funded",
                "Pipeline",
                "Funding",
                "Signing",
                "Initial Docs",
            ],
            alpineData: [],
            sequenceData: [],
            directToMICData: [],
            micSummary: [],
            micSummaryData: [],
            micAlpine: [],
            micSequence: [],
            micDirectToMIC: [],
            micAlpineWithFunded: [],
            micSequenceWithFunded: [],
            micDirectToMICWithFunded: [],
            totalAmount: 0,
            micSummaryTotalStatus: [],
            micSummaryTotalStatusSequence: [],
            micSummaryTotalStatusAlpine: [],
            micSummaryTotalStatusDirectToMIC: [],
        };
    },
    mounted() {
        this.getData()
    },
    computed: {
        sortedAlpineData() {
            return Object.fromEntries(
                Object.entries(this.alpineData).sort(
                    ([statusA], [statusB]) =>
                        this.statusOrder.indexOf(statusA) -
                        this.statusOrder.indexOf(statusB)
                )
            );
        },
        sortedSequenceData() {
            return Object.fromEntries(
                Object.entries(this.sequenceData).sort(
                    ([statusA], [statusB]) =>
                        this.statusOrder.indexOf(statusA) -
                        this.statusOrder.indexOf(statusB)
                )
            );
        },
        sortedDirectToMICData() {
            return Object.fromEntries(
                Object.entries(this.directToMICData).sort(
                    ([statusA], [statusB]) =>
                        this.statusOrder.indexOf(statusA) -
                        this.statusOrder.indexOf(statusB)
                )
            );
        },
        hasDirectToMICData() {
            return Object.keys(this.sortedDirectToMICData).length > 0;
        },
        getDate() {
            const today = new Date();
            const options = { year: "numeric", month: "long", day: "numeric" };
            return today.toLocaleDateString(undefined, options);
        },
    },

    methods: {
    transformCompanyStatus(data) {
        const oldToNewCompanies = {
            "Ryan Mortgage Income Fund Inc.": "ACIF",
            "Manchester Investments Inc": "ACCIF",
            "Blue Stripe Financial Ltd.": "ACHYF"
        };
        return Object.fromEntries(
            Object.entries(data).map(([oldCompany, statusData]) => [
                oldToNewCompanies[oldCompany] || oldCompany,
                statusData
            ])
        );
    },
    transformStatusCompany(data) {
        const oldToNewCompanies = {
            "Ryan Mortgage Income Fund Inc.": "ACIF",
            "Manchester Investments Inc": "ACCIF",
            "Blue Stripe Financial Ltd.": "ACHYF"
        };
        return Object.fromEntries(
            Object.entries(data).map(([status, companies]) => [
                status,
                Object.fromEntries(
                    Object.entries(companies).map(([oldCompany, values]) => [
                        oldToNewCompanies[oldCompany] || oldCompany,
                        values
                    ])
                )
            ])
        );
    },
    getCompanyTotal(company) {
    let totalCount = 0;
    let totalGross = 0;
        // From Alpine (including Funded)
        if (this.micAlpineWithFunded[company]) {
            for (let status in this.micAlpineWithFunded[company]) {
                this.micAlpineWithFunded[company][status].forEach(item => {
                    totalCount += item.count;
                    totalGross += item.gross_amount;
                });
            }
        }
        // From Sequence (including Funded)
        if (this.micSequenceWithFunded[company]) {
            for (let status in this.micSequenceWithFunded[company]) {
                this.micSequenceWithFunded[company][status].forEach(item => {
                    totalCount += item.count;
                    totalGross += item.gross_amount;
                });
            }
        }
        // From Direct To Mic (including Funded)
        if (this.micDirectToMICWithFunded[company]) {
            for (let status in this.micDirectToMICWithFunded[company]) {
                this.micDirectToMICWithFunded[company][status].forEach(item => {
                    totalCount += item.count;
                    totalGross += item.gross_amount;
                });
            }
        }
        return { count: totalCount, totalGross: this.formatDecimal(totalGross) };
        },
        getData: function () {
            // console.log("getData");
            this.showPreLoader();
            this.axios
                .get("/web/reports/mic-pipeline")
                .then((response) => {
                    if (this.checkApiResponse(response)) {
                        this.alpineData = this.transformStatusCompany(response.data.data.pipeline.alpineReports || {});
                        this.sequenceData = this.transformStatusCompany(response.data.data.pipeline.sequenceReports || {});
                        this.directToMICData = this.transformStatusCompany(response.data.data.pipeline.directToMICReports || {});
                        this.micAlpine = this.transformCompanyStatus(response.data.data.pipeline.micAlpine || {});
                        this.micSequence = this.transformCompanyStatus(response.data.data.pipeline.micSequence || {});
                        this.micDirectToMIC = this.transformCompanyStatus(response.data.data.pipeline.micDirectToMIC || {});
                        this.micAlpineWithFunded = this.transformCompanyStatus(response.data.data.pipeline.micAlpineWithFunded || {});
                        this.micSequenceWithFunded = this.transformCompanyStatus(response.data.data.pipeline.micSequenceWithFunded || {});
                        this.micDirectToMICWithFunded = this.transformCompanyStatus(response.data.data.pipeline.micDirectToMICWithFunded || {});
                        this.micSummary =
                            response.data.data.pipeline.micSummary;
                        this.totalAmount =
                            response.data.data.pipeline.totalAmount;
                        this.micSummaryTotalStatus =
                            response.data.data.pipeline.micSummaryTotalStatus;
                        this.micSummaryTotalStatusAlpine =
                            response.data.data.pipeline.micSummaryTotalStatusAlpine;
                        this.micSummaryTotalStatusSequence =
                            response.data.data.pipeline.micSummaryTotalStatusSequence;
                        this.micSummaryTotalStatusDirectToMIC = 
                            response.data.data.pipeline.micSummaryTotalStatusDirectToMIC;
                        
                        if (
                            this.sequenceData.Pipeline === undefined ||
                            this.alpineData.Pipeline === undefined || 
                            this.directToMICData.Pipeline === undefined
                        ) {
                            this.alpineData.Pipeline =
                                this.sequenceData.Pipeline = {
                                    "Ryan Mortgage Income Fund Inc.": [
                                        {
                                            count: "",
                                            gross_amount: "",
                                            weighted_average_lvt: "",
                                            weighted_average_yield: "",
                                        },
                                    ],
                                    "Manchester Investments Inc": [
                                        {
                                            count: "",
                                            gross_amount: "",
                                            weighted_average_lvt: "",
                                            weighted_average_yield: "",
                                        },
                                    ],
                                    "Blue Stripe Financial Ltd.": [
                                        {
                                            count: "",
                                            gross_amount: "",
                                            weighted_average_lvt: "",
                                            weighted_average_yield: "",
                                        },
                                    ],
                                };
                        }
                    } else {
                        this.alertMessage = response.data.message;
                        this.showAlert(response.data.status);
                    }
                })
                .catch((error) => {
                    this.alertMessage = error;
                    this.showAlert("error", error);
                })
                .finally(() => {
                    this.hidePreLoader();
                });
        },
        getSubTotal(origin, companyDefault) {
            let data;
            if (origin === "Alpine Credits Limited") {
                data = this.micAlpine;
            } else if (origin === "DirectToMIC") {
                data = this.micDirectToMIC;
            } else {
                data = this.micSequence;
            }
            

            for (let company in data) {
                let gross_amount = 0;
                let count = 0;

                if (company === companyDefault) {
                    const companyData = data[company];

                    for (let status in companyData) {
                        const statusData = companyData[status];
                        statusData.forEach((item) => {
                            gross_amount += item.gross_amount;
                            count += item.count;
                        });
                    }

                    return { gross_amount, count };
                }
            }
        },
        getTotalWithFunded(origin, companyDefault) {
            let data;
            if (origin === "Alpine Credits Limited") {
                data = this.micAlpineWithFunded;
            }  else if (origin === "DirectToMIC") {
                data = this.micDirectToMICWithFunded;
            }  else {
                data = this.micSequenceWithFunded;
            }

            for (let company in data) {
                let gross_amount = 0;
                let count = 0;
                // console.log("company", company);
                if (company === companyDefault) {
                    const companyData = data[company];
                    //  console.log("companyData", companyData);
                    for (let status in companyData) {
                        //console.log("status", status);
                        const statusData = companyData[status];
                        statusData.forEach((item) => {
                            gross_amount += item.gross_amount;
                            count += item.count;
                        });
                    }
                    return { gross_amount, count };
                }
            }
        },
        getCompanySubTotalPipeline(company) {
            let totalCount = 0;
            let totalGross = 0;
            const statusesToInclude = ["Pipeline", "Funding", "Signing", "Initial Docs"];
            // From Alpine
            if (this.micAlpine[company]) {
                for (let status of statusesToInclude) {
                    if (this.micAlpine[company][status]) {
                        this.micAlpine[company][status].forEach(item => {
                            totalCount += item.count;
                            totalGross += item.gross_amount;
                        });
                    }
                }
            }
            // From Sequence
            if (this.micSequence[company]) {
                for (let status of statusesToInclude) {
                    if (this.micSequence[company][status]) {
                        this.micSequence[company][status].forEach(item => {
                            totalCount += item.count;
                            totalGross += item.gross_amount;
                        });
                    }
                }
            }
            // From Direct To MIC
            if (this.micDirectToMIC[company]) {
                for (let status of statusesToInclude) {
                    if (this.micDirectToMIC[company][status]) {
                        this.micDirectToMIC[company][status].forEach(item => {
                            totalCount += item.count;
                            totalGross += item.gross_amount;
                        });
                    }
                }
            }
            return { count: totalCount, totalGross: this.formatDecimal(totalGross) };
        },
        getTotalStatus(origin, status) {
            // console.log("inside getTotalStatus", status);
            let count = 0;
            let gross_amount = 0;
            let statusArray;

            if (origin === "Alpine Credits") {
                statusArray = this.micSummaryTotalStatusAlpine[status];
            }
            if (origin === "Sequence Capital") {
                statusArray = this.micSummaryTotalStatusSequence[status];
            }
            if (origin === "DirectToMIC") {
                statusArray = this.micSummaryTotalStatusDirectToMIC[status];
            }
            if (origin === "total") {
                statusArray = this.micSummaryTotalStatus[status];
            }

            // console.log(statusArray);

            if (statusArray) {
                statusArray.forEach((item) => {
                    count += item.count;
                    gross_amount += item.gross_amount;
                });
            }

            //console.log("count & grandTotal", count, gross_amount);
            if (count === 0 && gross_amount === 0) {
                return { count: "", gross_amount: "" };
            } else {
                return { count, gross_amount };
            }
        },
        getTotalGrossCountAndAmount(origin) {
            //total
            let totalArray;
            let grossCount = 0;
            let grossGrossAmount = 0;
            let grossCountWithoutFunded = 0;
            let grossGrossAmountWithoutFunded = 0;

            if (origin === "Alpine Credits") {
                totalArray = this.micSummaryTotalStatusAlpine;
            }
            if (origin === "Sequence Capital") {
                totalArray = this.micSummaryTotalStatusSequence;
            }
            if (origin === "DirectToMIC") {
                totalArray = this.micSummaryTotalStatusDirectToMIC;
            }
            if (origin === "total") {
                totalArray = this.micSummaryTotalStatus;
            }

            for (let status in totalArray) {
                totalArray[status].forEach((item) => {
                    grossCount += item.count;
                    grossGrossAmount += item.gross_amount;
                });
            }
            for (let status in totalArray) {
                if (status !== "Funded")
                    totalArray[status].forEach((item) => {
                        grossCountWithoutFunded += item.count;
                        grossGrossAmountWithoutFunded += item.gross_amount;
                    });
            }

            return {
                grossCount,
                grossGrossAmount,
                grossCountWithoutFunded,
                grossGrossAmountWithoutFunded,
            };
        },
    },
}
</script>

<style scoped>
.total {
    width: 100%;
    gap: 2rem;
    padding: 0.5rem;
    font-weight: 600;
    background: #80808014;
    color: #2e2b24;
    align-items: center;
    margin: 1rem auto;
    padding-left: 1rem;
}
.total.alpine {
    background: #f0fdf0;
}
.total.sequence {
    background: #fff2ff;
}
.indent {
    padding-left: 1rem;
}
.card.mic-pipeline tr.text-grey > th:not(:first-child) {
    color: #4a4a4a;
    text-align: right;
    padding-right: 0.5rem;
}
.card.mic-pipeline tr.text-grey th:first-child {
    color: #4a4a4a;
    text-align: left;
}
.mic-data td {
    text-align: right;
    padding-right: 1rem;
}
.card.mic-pipeline tr th.company-origin {
    text-align: left;
}
.sub-total-row {
    vertical-align: bottom;
    background: #fff2d9;
}
.sub-total {
    display: flex;
    flex-direction: column;
}
.total-row {
    background: #ffd486;
}
.table-summary tr th.company-origin {
    text-align: left;
}
.table-summary tr th {
    padding-right: 1rem;
}
table tr.first-row:first-child {
    background: #fff2d9;
    font-weight: 600;
}
table td.pipeline {
    color: black;
    font-size: 0.8rem;
    font-weight: 600;
}
.mic-pipeline table {
    border: 1px solid #ededed;
}
th:nth-child(1),
td:nth-child(1) {
    width: 150px;
    /* Set the desired width for the first column */
}
.bg-royal-blue {
    background-color: #4169e1;
}
@media (min-width: 120rem) {
    .table-width-half {
        width: 50%;
    }
}
</style>