<template>
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item">
                <RouterLink to="/">Home</RouterLink>
            </li>
            <li class="breadcrumb-item active">
                Appraisal
            </li>
        </ol>
    </nav>

    <div class="card">
        <div class="card-body">

            <div class="input-group mb-3" style="max-width: 350px;">
                <span class="input-group-text">TACL#</span>
                <input type="text" class="form-control" v-model="applicationId" v-on:keyup.enter="searchApplication()" />
                <button class="btn btn-primary" type="button" @click="searchApplication()">
                    <i class="bi-search me-1"></i>Search
                </button>
            </div>

            <div class="card mb-3">
                <div class="card-header">
                    Select the Appraisal File
                </div>

                <div class="card-body overflow-y-auto document-container">
                    <div v-if="documents.length == 0" class="text-secondary">
                        <div>No documents found</div>
                    </div>

                    <div class="form-check" v-for="(document, index) in documents" :key="index">
                        <input
                            class="form-check-input"
                            type="radio"
                            name="documentOptions"
                            :id="'radio' + index"
                            :value="document.uniqueId"
                            v-model="uniqueId"
                        />
                        <label class="form-check-label fw-semibold text-secondary ms-2" :for="'radio' + index">
                            {{ document.name }}
                        </label>
                    </div>
                </div>

                <div class="card-footer">
                    <button :disabled="!uniqueId" class="btn btn-success" type="button" @click="processAppraisalFile()">
                        <i class="bi-gear-wide-connected me-1"></i>Process
                    </button>
                </div>
            </div>

            <div class="card mb-3">
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-4">
                            <table class="table table-hover mb-0">
                                <thead>
                                    <tr>
                                        <th scope="col" class="text-center" colspan="2">Appraisal Info</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <th>File No</th>
                                        <td>
                                            <AppraisalValue :value="appraisalData?.fileNo" />
                                        </td>
                                    </tr>
                                    <tr>
                                        <th>Appraisal Date</th>
                                        <td>
                                            <AppraisalValue :value="appraisalData?.appraisalDate" />
                                        </td>
                                    </tr>
                                    <tr>
                                        <th>Market Value</th>
                                        <td>
                                            <AppraisalValue :value="appraisalData?.marketValue" />
                                        </td>
                                    </tr>
                                    <tr>
                                        <th>Price Range</th>
                                        <td>
                                            <AppraisalValue :value="appraisalData?.priceRange" />
                                        </td>
                                    </tr>
                                    <tr>
                                        <th>Borrower Name</th>
                                        <td>
                                            <AppraisalValue :value="appraisalData?.borrowerName" />
                                        </td>
                                    </tr>
                                    <tr>
                                        <th>Appraisal Company</th>
                                        <td>
                                            <AppraisalValue :value="appraisalData?.appraisalCompany" />
                                        </td>
                                    </tr>
                                    <tr>
                                        <th>Property ID</th>
                                        <td>
                                            <AppraisalValue :value="appraisalData?.propertyId" />
                                        </td>
                                    </tr>
                                </tbody>
                            </table>

                            <table class="table table-hover mb-0 mt-3">
                                <thead>
                                    <tr>
                                        <th scope="col" class="text-center" colspan="2">Property Address</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <th>Address</th>
                                        <td>
                                            <AppraisalValue :value="appraisalData?.propertyAddress" />
                                        </td>
                                    </tr>
                                    <tr>
                                        <th>City</th>
                                        <td>
                                            <AppraisalValue :value="appraisalData?.propertyCity" />
                                        </td>
                                    </tr>
                                    <tr>
                                        <th>Province</th>
                                        <td>
                                            <AppraisalValue :value="appraisalData?.propertyProvince" />
                                        </td>
                                    </tr>
                                    <tr>
                                        <th>Postal Code</th>
                                        <td>
                                            <AppraisalValue :value="appraisalData?.propertyPostalCode" />
                                        </td>
                                    </tr>
                                </tbody>
                            </table>

                            <table class="table table-hover mb-0 mt-3">
                                <thead>
                                    <tr>
                                        <th scope="col" class="text-center">Risk Assessment</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td>
                                            <AppraisalValue :value="appraisalData?.analysisAndRiskAssessment" />
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>

                        <div class="col-md-8">
                            <table class="table table-hover mb-0">
                                <thead>
                                    <tr>
                                        <th scope="col" class="text-center" colspan="5">Market Values</th>
                                    </tr>
                                    <tr>
                                        <th scope="col" class="text-center"></th>
                                        <th scope="col" class="text-center">Subject</th>
                                        <th scope="col" class="text-center">Comparable 1</th>
                                        <th scope="col" class="text-center">Comparable 2</th>
                                        <th scope="col" class="text-center">Comparable 3</th>
                                    </tr>
                                </thead>

                                <tbody>
                                    <tr>
                                        <th>Address</th>
                                        <td>
                                            <AppraisalValue :value="appraisalData?.propertyAddress" />
                                        </td>
                                        <td>
                                            <AppraisalValue :value="appraisalData?.comparableProperty1FullAddress" />
                                        </td>
                                        <td>
                                            <AppraisalValue :value="appraisalData?.comparableProperty2FullAddress" />
                                        </td>
                                        <td>
                                            <AppraisalValue :value="appraisalData?.comparableProperty3FullAddress" />
                                        </td>
                                    </tr>
                                    <tr>
                                        <th>Sale Price</th>
                                        <td>
                                            
                                        </td>
                                        <td>
                                            <AppraisalValue :value="appraisalData?.comparableProperty1MarketValue" />
                                        </td>
                                        <td>
                                            <AppraisalValue :value="appraisalData?.comparableProperty2MarketValue" />
                                        </td>
                                        <td>
                                            <AppraisalValue :value="appraisalData?.comparableProperty2MarketValue" />
                                        </td>
                                    </tr>
                                    <tr>
                                        <th>Type</th>
                                        <td>
                                            <AppraisalValue :value="appraisalData?.propertyBuildingType" />
                                        </td>
                                        <td>
                                            <AppraisalValue :value="appraisalData?.comparableProperty1BuildingType" />
                                        </td>
                                        <td>
                                            <AppraisalValue :value="appraisalData?.comparableProperty2BuildingType" />
                                        </td>
                                        <td>
                                            <AppraisalValue :value="appraisalData?.comparableProperty3BuildingType" />
                                        </td>
                                    </tr>
                                    <tr>
                                        <th>Bedrooms</th>
                                        <td>
                                            <AppraisalValue :value="appraisalData?.propertyBedrooms" />
                                        </td>
                                        <td>
                                            <AppraisalValue :value="appraisalData?.comparableProperty1Bedrooms" />
                                        </td>
                                        <td>
                                            <AppraisalValue :value="appraisalData?.comparableProperty2Bedrooms" />
                                        </td>
                                        <td>
                                            <AppraisalValue :value="appraisalData?.comparableProperty3Bedrooms" />
                                        </td>
                                    </tr>
                                    <tr>
                                        <th>Bathrooms</th>
                                        <td>
                                            <AppraisalValue :value="appraisalData?.propertyBathrooms" />
                                        </td>
                                        <td>
                                            <AppraisalValue :value="appraisalData?.comparableProperty1Bathrooms" />
                                        </td>
                                        <td>
                                            <AppraisalValue :value="appraisalData?.comparableProperty2Bathrooms" />
                                        </td>
                                        <td>
                                            <AppraisalValue :value="appraisalData?.comparableProperty3Bathrooms" />
                                        </td>
                                    </tr>
                                    <tr>
                                        <th>Parking Type</th>
                                        <td>
                                            <AppraisalValue :value="appraisalData?.propertyParkingType" />
                                        </td>
                                        <td>
                                            <AppraisalValue :value="appraisalData?.comparableProperty1ParkingType" />
                                        </td>
                                        <td>
                                            <AppraisalValue :value="appraisalData?.comparableProperty2ParkingType" />
                                        </td>
                                        <td>
                                            <AppraisalValue :value="appraisalData?.comparableProperty3ParkingType" />
                                        </td>
                                    </tr>
                                    <tr>
                                        <th>Square Footage</th>
                                        <td>
                                            <AppraisalValue :value="appraisalData?.propertySquareFootage" />
                                        </td>
                                        <td>
                                            <AppraisalValue :value="appraisalData?.comparableProperty1SquareFootage" />
                                        </td>
                                        <td>
                                            <AppraisalValue :value="appraisalData?.comparableProperty2SquareFootage" />
                                        </td>
                                        <td>
                                            <AppraisalValue :value="appraisalData?.comparableProperty3SquareFootage" />
                                        </td>
                                    </tr>
                                    <tr>
                                        <th>Lot Size</th>
                                        <td>
                                            <AppraisalValue :value="appraisalData?.propertyLotSize" />
                                        </td>
                                        <td>
                                            <AppraisalValue :value="appraisalData?.comparableProperty1LotSize" />
                                        </td>
                                        <td>
                                            <AppraisalValue :value="appraisalData?.comparableProperty2LotSize" />
                                        </td>
                                        <td>
                                            <AppraisalValue :value="appraisalData?.comparableProperty3LotSize" />
                                        </td>
                                    </tr>
                                    <tr>
                                        <th>Condition</th>
                                        <td>
                                            <AppraisalValue :value="appraisalData?.propertyCondition" />
                                        </td>
                                        <td>
                                            <AppraisalValue :value="appraisalData?.comparableProperty1Condition" />
                                        </td>
                                        <td>
                                            <AppraisalValue :value="appraisalData?.comparableProperty2Condition" />
                                        </td>
                                        <td>
                                            <AppraisalValue :value="appraisalData?.comparableProperty3Condition" />
                                        </td>
                                    </tr>
                                    <tr>
                                        <th>Basement Type</th>
                                        <td>
                                            <AppraisalValue :value="appraisalData?.propertyBasementType" />
                                        </td>
                                        <td>
                                            <AppraisalValue :value="appraisalData?.comparableProperty1BasementType" />
                                        </td>
                                        <td>
                                            <AppraisalValue :value="appraisalData?.comparableProperty2BasementType" />
                                        </td>
                                        <td>
                                            <AppraisalValue :value="appraisalData?.comparableProperty3BasementType" />
                                        </td>
                                    </tr>
                                    <tr>
                                        <th>Assessment Value</th>
                                        <td>
                                            <AppraisalValue :value="appraisalData?.propertyAssessmentValue" />
                                        </td>
                                        <td>
                                            
                                        </td>
                                        <td>
                                            
                                        </td>
                                        <td>
                                            
                                        </td>
                                    </tr>
                                    <tr>
                                        <th>Taxes</th>
                                        <td>
                                            <AppraisalValue :value="appraisalData?.propertyTaxes" />
                                        </td>
                                        <td>
                                            
                                        </td>
                                        <td>
                                            
                                        </td>
                                        <td>
                                            
                                        </td>
                                    </tr>
                                    <tr>
                                        <th>Year Built</th>
                                        <td>
                                            <AppraisalValue :value="appraisalData?.propertyYearBuilt" />
                                        </td>
                                        <td>
                                            
                                        </td>
                                        <td>
                                            
                                        </td>
                                        <td>
                                            
                                        </td>
                                    </tr>
                                    <tr>
                                        <th>Heating Type</th>
                                        <td>
                                            <AppraisalValue :value="appraisalData?.propertyHeatingType" />
                                        </td>
                                        <td>
                                            
                                        </td>
                                        <td>
                                            
                                        </td>
                                        <td>
                                            
                                        </td>
                                    </tr>
                                    <tr>
                                        <th>Cooling Type</th>
                                        <td>
                                            <AppraisalValue :value="appraisalData?.propertyCoolingType" />
                                        </td>
                                        <td>
                                            
                                        </td>
                                        <td>
                                            
                                        </td>
                                        <td>
                                            
                                        </td>
                                    </tr>
                                    <tr>
                                        <th>Roof Type</th>
                                        <td>
                                            <AppraisalValue :value="appraisalData?.propertyRoofType" />
                                        </td>
                                        <td>
                                            
                                        </td>
                                        <td>
                                            
                                        </td>
                                        <td>
                                            
                                        </td>
                                    </tr>
                                    <tr>
                                        <th>Exterior Finish</th>
                                        <td>
                                            <AppraisalValue :value="appraisalData?.propertyExteriorFinish" />
                                        </td>
                                        <td>
                                            
                                        </td>
                                        <td>
                                            
                                        </td>
                                        <td>
                                            
                                        </td>
                                    </tr>
                                    <tr>
                                        <th>Interior Finish</th>
                                        <td>
                                            <AppraisalValue :value="appraisalData?.propertyInteriorFinish" />
                                        </td>
                                        <td>
                                            
                                        </td>
                                        <td>
                                            
                                        </td>
                                        <td>
                                            
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>

                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script>
import { util } from '../mixins/util'
import AppraisalValue from '../components/Appraisal/AppraisalValue'

export default {
    mixins: [util],
    emits: ['events'],
    components: {
        AppraisalValue
    },
    data() {
        return {
            applicationId: null,
            documents: [],
            uniqueId: null,
            appraisalData: [],
        }
    },
    methods: {
        searchApplication: function() {
            if (!this.applicationId) {
                this.alertMessage = "Please enter the TACL#"
                this.showAlert("error")
                return;
            }

            this.uniqueId = null
            this.appraisalData = []
            this.documents = []

            this.showPreLoader()

            let data = {
                applicationId: this.applicationId
            }

            this.axios
                .get("web/appraisal/documents", { params: data })
                .then((response) => {
                    if (this.checkApiResponse(response)) {
                        this.documents = response.data.data;
                    } else {
                        this.alertMessage = response.data.message;
                        this.showAlert(response.data.status);
                    }
                })
                .catch((error) => {
                    this.alertMessage = error;
                    this.showAlert("error");
                })
                .finally(() => {
                    this.hidePreLoader();
                });
        },
        processAppraisalFile: function() {
            if(!this.uniqueId) {
                this.alertMessage = "Please select an appraisal file";
                this.showAlert("warning");
                return;
            }

            this.showPreLoader()

            let data = {
                uniqueId: this.uniqueId
            }

            this.axios
            .get("web/appraisal/process", { params: data })
            .then((response) => {
                if(this.checkApiResponse(response)) {
                    this.appraisalData = response.data.data
                } else {
                    this.alertMessage = response.data.message
                    this.showAlert(response.data.status)
                }
            })
            .catch((error) => {
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

<style scoped> 
.document-container {
    max-height: 200px;
}
</style>
