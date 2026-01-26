<template>
    <ul v-bind:class="['nav nav-tabs d-flex align-items-center w-100 mb-2 sticky-top bg-light-ultra', type == 'all' ? '' : 'border-0']">
        <template v-if="type == 'all'">
            <li class="nav-item" v-for="tab in tabs" :key="tab.name">
                <a
                    class="nav-link"
                    :class="{ active: selectedTab === tab.name }"
                    href="#"
                    @click="tabChanged(tab.name)"
                >
                    {{ tab.label }}
                </a>
            </li>
        </template>

        <li class="ml-auto d-flex align-items-center">
            <button v-if="selectedTab != 'view' && selectedTab != 'quote' && selectedTab != 'disbursement'" class="btn btn-success me-2 mt-1 mb-1" @click="store()">
                <i class="bi bi-save me-1"></i>Save
            </button>
        </li>
    </ul>

    <template v-if="selectedTab == 'view'">
        <ContactCenterViewApp
            :application="application"
            :applicants="applicants"
            :corporations="corporations"
            :properties="properties"
            :mailings="mailings"
            :vehicles="vehicles"
            :assets="assets"
            :liabilities="liabilities"
            :otherIncomes="otherIncomes"
            :presentEmployments="presentEmployments"
            :previousEmployments="previousEmployments"
            :quotes="quotes"
            :activeMortgages="activeMortgages"
            :isSalesJourney="isSalesJourney"
            :totalIncome="totalIncome"
            :agentOptions="agentOptions"
            :salesJourney="salesJourney"/>
    </template>

    <div v-if="selectedTab === 'applicants'">
        <ApplicantsInfo 
            :application="application"
            :applicants="applicants"
            :properties="properties"
            :isSalesJourney="isSalesJourney"
            :agentOptions="agentOptions"
            :salesJourney="salesJourney"
            :showSalesJourney="false"
        />
        <div class="card card-body mb-3">
            <div class="d-flex align-items-start flex-wrap">
                <div class="form-group px-1 align-self-start">
                    <div class="pb-1">
                        <label for="firstName" class="table-header">Amount Requested</label>
                        <currency-input v-model="application.amountRequested" />
                    </div>       
                    <div v-if="application.companyId === 701">
                        <label class="table-header">Date</label>
                        <input v-model="application.date" type="date" class="form-control">
                    </div>            
                </div>
                <div class="form-group px-1 align-self-start col-2">
                    <div class="pb-1">
                        <label class="table-header">Purpose Type</label>
                        <select v-model="application.purposeId" class="form-select">
                            <option v-for="purpose in purposeDetailOptions" :value="purpose.id" :key="purpose.id">{{ purpose.name }}</option>
                        </select>
                    </div>
                    <div>
                        <label for="lastName" class="table-header">Purpose Detail</label>
                        <textarea v-model="application.purposeDetail" rows="2" class="form-control"></textarea>
                    </div>
                    <div v-if="application.companyId === 701">
                        <label class="table-header">Business Channel</label>
                        <select v-model="application.businessChannelId" class="form-select">
                            <option v-for="channel in businessChannelOptions" :value="channel.id" :key="channel.id">{{ channel.name }}</option>
                        </select>                    
                    </div>

                </div>

                <div class="form-group px-1 align-self-start col-2" v-if="application.companyId !== 701">
                    <div class="pb-1">
                        <label class="table-header">Urgency</label>
                        <select v-model="application.urgencyValue" class="form-select">
                            <option v-for="urgency in urgencyOptions" :value="urgency.id" :key="urgency.id">{{ urgency.name }}</option>
                        </select>
                    </div>
                </div>


                <div class="form-group px-1 align-self-start" v-if="application.companyId === 701">
                    <div class="pb-1">
                        <label class="table-header">Agent</label>
                        <select v-model="application.agent" class="form-select">
                            <option value=""></option>
                            <option v-for="agent in sequenceAgentList" :key="agent.id"
                                :value="agent.id">{{ agent.name }}</option>
                        </select>
                    </div>

                    <div class="pb-1">
                        <label class="table-header">Signing Agent/MS</label>
                        <select v-model="application.signingAgent" class="form-select">
                            <option value=""></option>
                            <option v-for="agent in signingAgentOptions" :key="agent.id"
                                :value="agent.id">{{ agent.name }}</option>
                        </select>
                    </div>

                    <div>
                        <label class="table-header">BDM</label>
                        <select v-model="application.bdmId" class="form-select">
                            <option value=""></option>
                            <option v-for="bdmSequenceAgent in bdmSequenceAgents" :key="bdmSequenceAgent.id"
                                :value="bdmSequenceAgent.id">{{ bdmSequenceAgent.fullName }}</option>
                        </select>
                    </div>

                    <div>
                        <label class="table-header">Underwriting Assistant</label>
                        <select v-model="application.uwAsstId" class="form-select">
                            <option value=""></option>
                            <option v-for="underWritingSequenceAgent in underWritingSequenceAgents" :key="underWritingSequenceAgent.id"
                                :value="underWritingSequenceAgent.id">{{ underWritingSequenceAgent.fullName }}</option>
                        </select>
                    </div>
                </div>

                <div class="form-group px-1 align-self-start" v-if="application.companyId === 701" style="max-width: 210px;">
                    <div class="pb-1">
                        <label class="table-header">Source</label>
                        <AutoComplete
                            :modelValue="selectedSource1"
                            :endpoint="'web/source/get-sources'"
                            :options="sourceOptions"
                            :placeholder="'Search a source...'"
                            @update:modelValue="(value) => handleSourceChange(value, 'Source1')"
                        />
                    </div>

                    <div>
                        <label class="table-header">Source 2</label>
                        <AutoComplete
                            :modelValue="selectedSource2"
                            :endpoint="'web/source/get-sources'"
                            :options="sourceOptions"
                            :placeholder="'Search a source...'"
                            @update:modelValue="(value) => handleSourceChange(value, 'Source2')"
                        />
                    </div>
                </div>

                <div class="form-group px-1 align-self-start" v-if="application.companyId === 701">
                    <div class="pb-1">
                        <label class="table-header">CMOS Number</label>
                        <input v-model="application.cmosNumber" type="text" class="form-control">
                    </div>

                    <div class="pb-1">
                        <label class="table-header">CMOS Name</label>
                        <input v-model="application.cmosName" type="text" class="form-control">
                    </div>

                    <div>
                        <label class="table-header">Lender ID</label>
                        <input v-model="application.lenderId" type="text" class="form-control">
                    </div>
                </div>

                <div class="form-group px-1 align-self-start" v-if="application.companyId === 701" style="max-width: 410px;">
                    <div class="pb-1">
                        <label class="table-header">Broker</label>
                        <AutoComplete
                            :modelValue="selectedBroker"
                            :endpoint="'web/users/external-brokers'"
                            :options="brokerOptions"
                            :placeholder="'Search a broker...'"
                            @update:modelValue="handleBrokerChange"
                        />
                    </div>

                    <div class="pb-1">
                        <label class="table-header">Broker Office</label>
                         <span class="py-1 d-block">{{ selectedBroker?.brokerOfficeName || 'No Broker Selected' }}</span>
                    </div>

                    <div class="d-flex align-items-start flex-wrap gap-4">
                        <div>
                            <label class="table-header">National Broker</label>
                            <span class="py-1 d-block">{{ selectedBroker?.nationalBrokerName || 'No Broker Selected' }}</span>
                        </div>

                        <div>
                            <label class="table-header">CPS</label>
                            <span class="py-1 d-block">{{ selectedBroker?.cps || 'No Broker Selected' }}</span>
                        </div>

                        <div>
                            <label class="table-header">License Checked</label>
                            <input class="form-check-input d-block" type="checkbox" v-model="isLicenseChecked" @change="handleLicenseCheckedChange"/>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="card mb-2" v-for="(applicant, key) in applicants" :key="key">
            <template v-if="!applicant.isRemoved">
                <div class="card-header">
                    <div class="d-flex align-items-center">
                        <div>
                            <span class="m-1"><b>Applicant #{{ key + 1 }}</b></span>
                        </div>

                        <div class="me-auto"></div>

                        <div>
                            <button class="btn btn-primary" v-if="key === 0" @click="addFields('applicants')">
                                <i class="bi-plus-lg me-1"></i>Add Applicant
                            </button>
                        </div>

                        <div class="ms-2" v-if="applicant.spouses.length < 2">
                            <button class="btn btn-primary" @click="addFields('spouses', key)">
                                <i class="bi-plus-lg me-1"></i>Add Spouse
                            </button>
                        </div>

                        <div class="ms-2" v-if="key != 0">
                            <button class="btn btn-outline-danger" v-tooltip="'Remove Applicant'" @click="removeBtn(key,'applicants')">
                                <i class="bi bi-trash"></i>
                            </button>
                        </div>

                    </div>
                </div>

                <div class="card-body">
                    <div class="card mb-2">
                        <div class="card-body pt-0">
                            <template v-for="(spouse, k) in applicant.spouses" :key="k">
                                <hr class="mb-0" v-if="k !== 0">

                                <div class="d-flex align-items-end flex-wrap mt-2">
                                    <div class="form-group px-1">
                                        <label for="firstName" class="table-header"><span class="text-danger">*</span>First Name</label>
                                        <input v-model="spouse.firstName" type="text" class="form-control">
                                    </div>
                                    <div class="form-group px-1">
                                        <label for="lastName" class="table-header">Middle Name</label>
                                        <input v-model="spouse.middleName" type="text" class="form-control">
                                    </div>
                                    <div class="form-group px-1">
                                        <label for="lastName" class="table-header"><span class="text-danger">*</span>Last Name</label>
                                        <input v-model="spouse.lastName" type="text" class="form-control">
                                    </div>
                                    <div class="form-group px-1">
                                        <label for="lastName" class="table-header">Preferred Name</label>
                                        <input v-model="spouse.preferredName" type="text" class="form-control">
                                    </div>
                                    <div class="form-group px-1">
                                        <label for="gender" class="table-header">Gender</label>
                                        <select v-model="spouse.gender" class="form-select">
                                            <option value="" key=""></option>
                                            <option value="M" key="M">Male</option>
                                            <option value="F" key="F">Female</option>
                                        </select>
                                    </div>
                                    <div class="form-group px-1">
                                        <label class="table-header">Date of Birth</label>
                                        <input v-model="spouse.dateOfBirth" type="date" class="form-control">
                                    </div>
                                    <div class="form-group px-1">
                                        <label class="table-header">Age</label>
                                        <input v-model="spouse.age" type="text" class="form-control" disabled>
                                    </div>
                                    <div class="form-group px-1">
                                        <label class="table-header">Social Insurance Number</label>
                                        <input v-model="spouse.sin" type="text" class="form-control">
                                    </div>
                                    <div class="form-group px-1">
                                        <label class="table-header">Beacon Score</label>
                                        <input v-model="spouse.beaconScore" type="text" class="form-control">
                                    </div>
                                    <div class="form-group px-1">
                                        <label class="table-header">Applicant Type</label>
                                        <select v-model="spouse.type" class="form-select" @change="spouseTypeChanged(spouse)">
                                            <option v-for="option in applicantTypeOptionsCMS" :key="option.value" :value="option.text">{{ option.text }}</option>
                                        </select>
                                    </div>
                                    <div class="form-group px-1">
                                        <label class="table-header">Main Contact</label>
                                        <select v-model="spouse.mainContact" class="form-select">
                                            <option value="Yes" key="Yes">Yes</option>
                                            <option value="No" key="No">No</option>
                                        </select>
                                    </div>
                                    <div class="form-group px-1">
                                        <label class="table-header">Relation</label>
                                        <input v-model="spouse.relation" type="text" class="form-control">
                                    </div>
                                    <div class="form-group px-1">
                                        <label class="table-header">PEP or HIO</label>
                                        <select v-model="spouse.isPep" class="form-select">
                                            <option value="" key=""></option>
                                            <option value="Yes" key="Yes">Yes</option>
                                            <option value="No" key="No">No</option>
                                        </select>
                                    </div>
                                    <div v-if="spouse.isPep == 'Yes'" class="form-group px-1">
                                        <label class="table-header">PEP Description</label>
                                        <input v-model="spouse.pepDescription" type="text" class="form-control">
                                    </div>

                                    <div v-if="spouse.type === 'Do not contact' || spouse.type === 'Not a co-applicant'" class="form-group px-1">
                                        <label class="table-header">Additional Roles</label>
                                        <select v-model="otherTmp" :disabled="true" class="form-select">
                                            <option value="" key="">Select...</option>
                                        </select>
                                    </div>
                                    <div v-else class="form-group px-1">
                                        <label class="table-header">Additional Roles</label>
                                        <MultiSelect :key="k" v-model="spouse.signatureType" :options="applicantTypeOptionsSignature"></MultiSelect>
                                    </div>
                                    <div class="form-group px-1">
                                        <label class="table-header">Signer</label>
                                        <select v-model="spouse.signer" class="form-select" :disabled="!(spouse.type === 'Applicant') && !(spouse.type === 'Co-Applicant')">
                                            <option v-for="signer in signersOptions" :value="signer.id" :key="signer.id">{{ signer.name }}</option>
                                        </select>
                                    </div>

                                </div>
                            </template>
                        </div>
                    </div>

                    <div class="row d-flex align-items-end">
                        <div class="col-2">
                            <label class="table-header">Home Phone</label>
                            <PhoneInput v-model="applicant.homePhone" />
                        </div>
                        <div class="col-2">
                            <label class="table-header">Mobile</label>
                            <PhoneInput v-model="applicant.homeMobile" />
                        </div>
                        <div class="col-2">
                            <label class="table-header">Email</label>
                            <input v-model="applicant.email" type="text" class="form-control">
                        </div>
                        <div class="col-2">
                            <label class="table-header">Marital Status</label>
                            <select v-model="applicant.maritalStatus" class="form-select">
                                <option v-for="option in maritalStatusOptionsCMS" :value="option.text" :key="option.value">{{ option.text }}</option>
                            </select>
                        </div>
                        <div class="col-2">
                            <label class="table-header">Credit Bureau Received</label>
                            <input v-model="applicant.creditBureauRec" type="date" class="form-control">
                        </div>
                        <div class="col-2">
                            <label class="table-header">Years of Marital Status</label>
                            <input v-model="applicant.yearsOfMaritalStatus" type="text" class="form-control">
                        </div>
                        <div class="col-2">
                            <label class="table-header"># of Children</label>
                            <input v-model="applicant.childrenCount" type="text" class="form-control">
                        </div>
                        <div class="col-2">
                            <label class="table-header">Ages</label>
                            <input v-model="applicant.childrenAges" type="text" class="form-control">
                        </div>
                    </div>

                    <div class="row d-flex align-items-end">
                        <div class="d-flex align-items-center mt-3">
                            <h6 class="mt-1">Contacts</h6>
                            <button class="ms-3 btn btn-primary" @click="addFields('contacts',key)">
                                <i class="bi-plus-lg me-1"></i>Add Contact
                            </button>
                        </div>

                        <div v-if="applicant.contacts.length > 0">
                            <div class="row">
                                <div class="col-3">
                                    <label class="table-header">Type</label>
                                </div>
                                <div class="col-3">
                                    <label class="table-header">Info</label>
                                </div>
                                <div class="col-1"></div>
                            </div>

                            <template v-for="(contact, c) in applicant.contacts" :key="c">
                                <div v-if="!contact.isRemoved" class="row mb-2">
                                    <div class="col-3">
                                        <select v-model="contact.type" class="form-select">
                                            <option v-for="option in applicant.contactOptions" :key="option" :value="option.id">{{option.name}}</option>
                                        </select>
                                    </div>
                                    <div class="col-3">
                                        <template v-if="contact.type.includes('Cellular') || contact.type.includes('Pager') || contact.type.includes('Work') || contact.type.includes('Fax')">
                                            <PhoneInput v-model="contact.info" />
                                        </template>
                                        <template v-else-if="contact.type.includes('Email')">
                                            <input v-model="contact.info" type="email" class="form-control">
                                        </template>
                                        <template v-else>
                                            <input v-model="contact.info" type="text" class="form-control">
                                        </template>
                                    </div>
                                    <div class="col-1">
                                        <button class="btn btn-outline-danger" v-tooltip="'Remove Contact'" @click="removeFields(c, 'contacts', key)">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </div>
                                </div>
                            </template>
                        </div>
                    </div>
                </div>
            </template>
        </div> 
    </div>

    <!--Corporations-->
    <div class="card mb-2" v-if="selectedTab === 'applicants'">
        <div class="card mb-2">
            <div class="card-header">
                <div class="d-flex align-items-center">
                    <div>
                        <span class="m-1"><b>Companies</b></span>
                    </div>

                    <div class="me-auto"></div>

                    <div>
                        <button class="btn btn-primary" @click="addFields('corporations')"><i class="bi-plus-lg me-1"></i>Add Company</button>
                    </div>
                </div>
            </div>

            <div v-if="corporations.length > 0" class="card-body">
                <div class="row">
                    <div class="col-2">
                        <label class="table-header">Company Name</label>
                    </div>
                    <div class="col-2">
                        <label class="table-header">Inc. No.</label>
                    </div>
                    <div class="col-2">
                        <label class="table-header">Directors</label>
                    </div>
                    <div class="col-1">
                        <label class="table-header">Phone</label>
                    </div>
                    <div class="col-1">
                        <label class="table-header">Mobile</label>
                    </div>
                    <div class="col-2">
                        <label class="table-header">Email</label>
                    </div>
                    <div class="col-1"></div>
                </div>

                <template v-for="(corp, index) in corporations" :key="index">
                    <div class="row mb-1" v-if="!corp.isRemoved">
                        <div class="col-2">
                            <input v-model="corp.companyName" type="text" class="form-control">
                        </div>
                        <div class="col-2">
                            <input v-model="corp.incNumber" type="text" class="form-control">
                        </div>
                        <div class="col-2">
                            <input v-model="corp.directors" type="text" class="form-control">
                        </div>
                        <div class="col-1">
                            <PhoneInput v-model="corp.phone" />
                        </div>
                        <div class="col-1">
                            <PhoneInput v-model="corp.mobile" />
                        </div>
                        <div class="col-2">
                            <input v-model="corp.email" type="text" class="form-control">
                        </div>
                        <div class="col-1">
                            <button class="btn btn-outline-danger" v-tooltip="'Remove Company'" @click="removeFields(index, 'corporations')">
                                <i class="bi bi-trash"></i>
                            </button>
                        </div>
                    </div>
                </template>
            </div>
        </div>
    </div>

    <!--Mailing / Properties-->
    <div class="mb-2" v-if="selectedTab === 'properties'">
        <ApplicantsInfo 
            :application="application"
            :applicants="applicants"
            :properties="properties"
            :isSalesJourney="isSalesJourney"
            :agentOptions="agentOptions"
            :salesJourney="salesJourney"
            :showSalesJourney="false"
        />

        <template v-if="mailings.length > 0 && activeMailing">
            <template v-for="(mailing, key) in mailings" :key="key">
                <div class="card mt-2" v-if="!mailing.isRemoved">
                    <div class="card-header">
                        <div class="d-flex align-items-center">
                            <div>
                                <span class="m-1"><b>Mailing #{{ key + 1 }}</b></span>
                            </div>

                            <div class="me-auto"></div>

                            <div>
                                <button class="btn btn-primary" v-if="key === 0" @click="addFields('mailing')">
                                    <i class="bi-plus-lg me-1"></i>Add Mailing
                                </button>
                            </div>

                            <div class="ms-2">
                                <button class="btn btn-outline-danger" v-tooltip="'Remove Mailing'" @click="removeFields(key,'mailing')">
                                    <i class="bi bi-trash"></i> 
                                </button>
                            </div>
                        </div>
                    </div>

                    <div class="card-body">
                        <div class="d-flex align-items-end">
                            <div class="form-group px-1">
                                <label for="" class="table-header">Type</label>
                                <select v-model="mailing.type" class="form-select">
                                    <option value="Mailing" key="Mailing">Mailing</option>
                                    <option value="Previous" key="Previous">Previous</option>
                                </select>
                            </div>

                            <div v-if="totalApplicants > 9" class="form-group px-1">
                                <label class="table-header">Title Holder</label>
                                <div class="d-flex">
                                    <div 
                                        class="form-control me-1 bg-white"
                                        style="min-width: 0; flex-grow: 1; white-space: nowrap; overflow-x: auto;">
                                        {{ mailing.recipients }}
                                    </div>

                                    <button 
                                        class="btn btn-secondary"
                                        type="button"
                                        v-tooltip="'Edit Title Holder'"
                                        @click="editTitleHolder(mailing.id,key,'mailing')">
                                        <i class="bi bi-pencil"></i>
                                    </button>
                                </div>
                            </div>

                            <div v-else class="form-group px-1">
                                <label for="" class="table-header">Title Holder</label>
                                <select v-model="mailing.recipients" class="form-select">
                                    <option v-for="option in titleHoldersObj" :key="option" :value="option.id">{{option.name}}</option>
                                </select>
                            </div>
                        </div>

                        <div class="d-flex align-items-end flex-wrap">
                            <div class="form-group px-1 flex-fill">
                                <label for="" class="table-header">Unit #</label>
                                <input v-model="mailing.unitNumber" type="text" class="form-control">
                            </div>
                            <div class="form-group px-1 flex-fill">
                                <label for="" class="table-header">Unit Type</label>
                                <select v-model="mailing.unitType" class="form-select">
                                    <option v-for="option in unitTypeOptions" :key="option" :value="option.value">{{option.text}}</option>
                                </select>
                            </div>
                            <div class="form-group px-1 flex-fill">
                                <label for="" class="table-header">Street #</label>
                                <input v-model="mailing.streetNumber" type="text" class="form-control">
                            </div>
                            <div class="form-group px-1 flex-fill">
                                <label for="" class="table-header">Street Name</label>
                                <input v-model="mailing.streetName" type="text" class="form-control">
                            </div>
                            <div class="form-group px-1 flex-fill">
                                <label for="" class="table-header">Street Type</label>
                                <select v-model="mailing.streetType" class="form-select">
                                    <option v-for="option in streetOptions" :key="option" :value="option.value">{{ option.text }}</option>
                                </select>
                            </div>
                            <div class="form-group px-1 flex-fill">
                                <label for="" class="table-header">Direction</label>
                                <select v-model="mailing.streetDirection" class="form-select">
                                    <option v-for="option in directionCMSOptions" :key="option" :value="option.value">{{ option.text }}</option>
                                </select>
                            </div>
                            <div class="form-group px-1 flex-fill">
                                <label class="table-header">City</label>
                                <input v-model="mailing.city" type="text" class="form-control">
                            </div>
                            <div class="form-group px-1 flex-fill">
                                <label class="table-header">Province</label>
                                <select v-model="mailing.province" class="form-select">
                                    <option v-for="option in provinceOptions" :key="option" :value="option.value">{{ option.text }}</option>
                                </select>
                            </div>
                            <div class="form-group px-1 flex-fill">
                                <label for="" class="table-header">Postal Code</label>
                                <input v-model="mailing.postalCode" type="text" class="form-control">
                            </div>
                        </div>

                        <div class="d-flex align-items-end flex-wrap">
                            <div class="form-group px-1">
                                <label class="table-header">PO Box Number</label>
                                <input v-model="mailing.poNumber" type="text" class="form-control">
                            </div>
                            <div class="form-group px-1">
                                <label class="table-header">Station</label>
                                <input v-model="mailing.station" type="text" class="form-control">
                            </div>
                            <div class="form-group px-1">
                                <label class="table-header">RR</label>
                                <input v-model="mailing.rrNumber" type="text" class="form-control">
                            </div>
                            <div class="form-group px-1">
                                <label class="table-header">Site</label>
                                <input v-model="mailing.site" type="text" class="form-control">
                            </div>
                            <div class="form-group px-1">
                                <label class="table-header">Comp</label>
                                <input v-model="mailing.compartment" type="text" class="form-control">
                            </div>
                            <div class="form-group px-1">
                                <label class="table-header">How Long</label>
                                <input v-model="mailing.howLong" type="text" class="form-control">
                            </div>
                            <div class="form-group px-1 flex-fill">
                                <label for="" class="table-header">Other Format</label>
                                <textarea v-model="mailing.other" rows="2" class="form-control"></textarea>
                            </div>
                        </div>
                    </div>
                </div>
            </template>
        </template>

        <template v-else>
            <div class="card">
                <div class="card-header">
                    <div class="d-flex align-items-center">
                        <div>
                            <span class="m-1"><b>Mailing</b></span>
                        </div>

                        <div class="me-auto"></div>

                        <div>
                            <button class="btn btn-primary" @click="addFields('mailing')">
                                <i class="bi-plus-lg me-1"></i>Add Mailing
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </template>
    </div>

    <div class="card mb-2" v-if="selectedTab === 'properties' && properties.length === 0">
        <div class="card-header">
            <div class="d-flex align-items-center">

                <div class="me-auto"></div>

                <div>
                    <button class="btn btn-primary" @click="addFields('properties')">
                        <i class="bi-plus-lg me-1"></i>Add Property
                    </button>
                </div>
            </div>
        </div>
    </div>

    <template v-if="selectedTab === 'properties' && properties.length > 0">
        <div class="card mb-2" v-for="(property, key) in properties" :key="key">
            <div v-if="!property.isRemoved">

                <div class="accordion" :id="'accordionProparties' + key">
                    <div class="accordion-item">
                        <div class="accordion-header" style="background-color: rgba(var(--cui-body-color-rgb), 0.03)">
                            <div class="d-flex align-items-center p-2">
                                <a class="accordion-toggle"
                                    data-coreui-toggle="collapse"
                                    :data-coreui-target="'#collapse' + key"
                                    aria-expanded="true" 
                                    :aria-controls="'collapse' + key"
                                    :href="'#accordionProparties' + key">
                                    <span class="m-1"><b>Property #{{ key + 1 }}</b></span>
                                </a>
                                
                                <div class="me-auto"></div>

                                <div>
                                    <button class="btn btn-primary" v-tooltip="'Add Property'" @click="addFields('properties')">
                                        <i class="bi-plus-lg me-1"></i>Add Property
                                    </button>
                                </div>

                                <div class="ms-2 me-2">
                                    <button class="btn btn-outline-danger" v-if="properties.length > 1" v-tooltip="'Remove Property'" @click="removeBtn(key,'properties')">
                                        <i class="bi bi-trash"> </i> 
                                    </button>
                                </div>
                            </div>
                        </div>

                        <div :id="'collapse' + key" class="accordion-collapse collapse show" :data-coreui-parent="'#accordionProparties' + key">
                            <div class="accordion-body p-0">
                                <div class="card-body">

                                    <div class="d-flex align-items-end flex-wrap">
                                        <div v-if="totalApplicants > 9" class="form-group px-1 flex-fill">
                                            <label class="table-header">Title Holder</label>

                                            <div class="d-flex">
                                                <div 
                                                    class="form-control me-1 bg-white"
                                                    style="min-width: 0; flex-grow: 1; white-space: nowrap; overflow-x: auto;">
                                                    {{ property.titleHolders }}
                                                </div>

                                                <button 
                                                    class="btn btn-secondary"
                                                    type="button"
                                                    v-tooltip="'Edit Title Holder'"
                                                    @click="editTitleHolder(property.id,key,'property')">
                                                    <i class="bi bi-pencil"></i>
                                                </button>
                                            </div>
                                        </div>

                                        <div v-else class="form-group px-1 flex-fill">
                                            <label for="" class="table-header">Title Holder</label>
                                            <select v-model="property.titleHolders" class="form-select">
                                                <option v-for="option in titleHoldersObj" :key="option" :value="option.id">{{option.name}}</option>
                                            </select>
                                        </div>
                                        <div class="form-group px-1">
                                            <label for="" class="table-header">Type</label>
                                            <select v-model="property.type" class="form-select">
                                                <option v-for="type in propertyTypesCMS" :key="type.value" :value="type.text">{{ type.text }}</option>
                                            </select>
                                        </div>
                                        <div class="form-group px-1">
                                            <label for="" class="table-header">{{ application.companyId === 701 ? "Sequence’s Interest" : "Alpine's Interest" }}</label>
                                            <input v-model="property.alpineInterest" type="text" class="form-control">
                                        </div>
                                        <div class="form-group px-1">
                                            <label for="" class="table-header">Property Taxes</label>
                                            <input v-model="property.taxes" type="number" class="form-control">
                                        </div>
                                        <div class="form-group px-1">
                                            <label for="" class="table-header">Arrears/UTD</label>
                                            <input v-model="property.arrearsUtd" type="text" class="form-control">
                                        </div>
                                        <div class="form-group px-1">
                                            <label for="" class="table-header">Insurance Broker</label>
                                            <div class="input-group">
                                                <span class="input-group-text">{{ property.insBrokerName }}</span>
                                                <input v-model="property.insBrokerId" type="text" class="form-control" style="max-width: 80px;">
                                                <button 
                                                    class="btn btn-secondary"
                                                    type="button"
                                                    @click="selectInsBrokerCode(key)">
                                                    <i class="bi bi-search"></i>
                                                </button>
                                            </div>
                                        </div>
                                        <div class="form-group px-1">
                                            <label for="" class="table-header">Arrears/UTD</label>
                                            <input v-model="property.insArrears" type="text" class="form-control">
                                        </div>
                                        <div class="form-group px-1">
                                            <label for="" class="table-header">Insurance Expire Date</label>
                                            <input v-model="property.insExpireDate" type="date" class="form-control">
                                        </div>
                                    </div>

                                    <div class="d-flex align-items-end flex-wrap">
                                        <div class="form-group px-1">
                                            <label for="" class="table-header">Unit #</label>
                                            <input v-model="property.unitNumber" type="text" class="form-control">
                                        </div>
                                        <div class="form-group px-1">
                                            <label for="" class="table-header">Unit Type</label>
                                            <select v-model="property.unitType" class="form-select">
                                                <option v-for="option in unitTypeOptionsCMS" :key="option.value" :value="option.text">{{option.text}}</option>
                                            </select>
                                        </div>
                                        <div class="form-group px-1">
                                            <label for="" class="table-header">Street #</label>
                                            <input v-model="property.streetNumber" type="text" class="form-control">
                                        </div>
                                        <div class="form-group px-1 flex-fill">
                                            <label for="" class="table-header">Street Name</label>
                                            <input v-model="property.streetName" type="text" class="form-control">
                                        </div>
                                        <div class="form-group px-1">
                                            <label for="" class="table-header">Street Type</label>
                                            <select v-model="property.streetType" class="form-select">
                                                <option v-for="option in streetOptions" :key="option" :value="option.value">{{ option.text }}</option>
                                            </select>
                                        </div>
                                        <div class="form-group px-1">
                                            <label for="" class="table-header">Direction</label>
                                            <select v-model="property.direction" class="form-select">
                                                <option v-for="option in directionCMSOptions" :key="option" :value="option.value">{{ option.text }}</option>
                                            </select>
                                        </div>
                                        <div class="form-group px-1">
                                            <label class="table-header">City</label>
                                            <input v-model="property.city" type="text" class="form-control">
                                        </div>
                                        <div class="form-group px-1">
                                            <label class="table-header">Province</label>
                                            <select v-model="property.province" class="form-select">
                                                <option v-for="option in provinceOptions" :key="option" :value="option.value">{{ option.text }}</option>
                                            </select>
                                        </div>
                                        <div class="form-group px-1">
                                            <label for="" class="table-header">Postal Code</label>
                                            <input v-model="property.postalCode" type="text" class="form-control">
                                        </div>
                                        <div class="form-group px-1">
                                            <label for="" class="table-header">PID</label>
                                            <input v-model="property.pid" type="text" class="form-control">
                                        </div>
                                        <div class="form-group px-1 flex-fill">
                                            <label for="" class="table-header">Legal</label>
                                            <textarea v-model="property.legal" rows="2" class="form-control"></textarea>
                                        </div>
                                        <div class="form-group px-1">
                                            <label for="" class="table-header">Same as Mailing Address?</label>
                                            <select v-model="property.sameAsMailing" class="form-select">
                                                <option value="Yes" key="Yes">Yes</option>
                                                <option value="No" key="No">No</option>
                                            </select>
                                        </div>
                                    </div>

                                    <div v-if="property.unitType === 'Apt' || property.unitType === 'M/H' || property.unitType === 'T/H'" class="d-flex align-items-end flex-wrap">
                                        <div class="form-group px-1">
                                            <label for="" class="table-header">Strata Fee</label>
                                            <input v-model="property.strata" type="text" class="form-control">
                                        </div>
                                        <div class="form-group px-1">
                                            <label for="" class="table-header">Arrears / UTD</label>
                                            <input v-model="property.strataArrears" type="text" class="form-control">
                                        </div>
                                        <div class="form-group px-1">
                                            <label for="" class="table-header">Company Name</label>
                                            <input v-model="property.strataCompany" type="text" class="form-control">
                                        </div>
                                        <div class="form-group px-1">
                                            <label for="" class="table-header">Contact Name</label>
                                            <input v-model="property.strataContact" type="text" class="form-control">
                                        </div>
                                        <div class="form-group px-1">
                                            <label for="" class="table-header">Phone</label>
                                            <PhoneInput v-model="property.strataPhone" />
                                        </div>
                                        <div class="form-group px-1">
                                            <label for="" class="table-header">Fax</label>
                                            <input v-model="property.strataFax" type="text" class="form-control">
                                        </div>
                                        <div class="form-group px-1">
                                            <label for="" class="table-header">Email</label>
                                            <input v-model="property.strataEmail" type="text" class="form-control">
                                        </div>
                                        <div class="form-group px-1">
                                            <label for="" class="table-header">Other</label>
                                            <input v-model="property.strataOther" type="text" class="form-control">
                                        </div>
                                    </div>

                                    <div class="d-flex align-items-end flex-wrap">
                                        <div class="form-group px-1">
                                            <label for="" class="table-header">Customer Value</label>
                                            <currency-input v-model="property.customerValue" />
                                        </div>
                                        <div class="form-group px-1">
                                            <label for="" class="table-header">Assessed Value</label>
                                            <currency-input v-model="property.assessedValue" />
                                        </div>
                                        <div class="form-group px-1">
                                            <label for="" class="table-header">Land Ass'd Value</label>
                                            <currency-input v-model="property.landAssdValue" />
                                        </div>
                                        <div class="form-group px-1">
                                            <label for="" class="table-header">Building Ass'd Value</label>
                                            <currency-input v-model="property.buildingAssdValue" />
                                        </div>
                                        <div class="form-group px-1">
                                            <label for="" class="table-header"># of years</label>
                                            <input v-model="property.numberOfYears" type="text" class="form-control">
                                        </div>
                                        <div class="form-group px-1">
                                            <label for="" class="table-header">Cost Price</label>
                                            <currency-input v-model="property.costPrice" />
                                        </div>
                                        <div class="form-group px-1">
                                            <label for="" class="table-header">Downpayment</label>
                                            <currency-input v-model="property.downpayment" />
                                        </div>
                                        <div class="form-group px-1 flex-fill">
                                            <label for="" class="table-header">Own/Rent</label>
                                            <select v-model="property.ownRent" class="form-select">
                                                <option v-for="option in ownRentOptions" :key="option" :value="option.value">{{ option.text }}</option>
                                            </select>
                                        </div>
                                        <div class="form-group px-1 flex-fill">
                                            <label for="" class="table-header">Part of Security</label>
                                            <select v-model="property.partOfSecurity" class="form-select">
                                                <option v-for="option in partOfSecOptions" :key="option" :value="option.value">{{ option.text }}</option>
                                            </select>
                                        </div>
                                        <div class="form-group px-1 flex-fill">
                                            <label for="" class="table-header">Rural/Urban</label>
                                            <select v-model="property.ruralUrban" class="form-select">
                                                <option v-for="option in ruralUrbanOptions" :key="option" :value="option.value">{{ option.text }}</option>
                                            </select>
                                        </div>
                                    </div>

                                    <div class="d-flex align-items-end flex-wrap">
                                        <div class="form-group px-1 flex-fill">
                                            <label for="" class="table-header">Value Method</label>
                                            <select v-model="property.valueMethod" class="form-select">
                                                <option v-for="option in valueMethodOptionsCMS" :key="option.value" :value="option.text">{{option.text}}</option>
                                            </select>
                                        </div>
                                        <div class="form-group px-1">
                                            <label for="" class="table-header">Appraisal Firm Code</label>
                                            <div class="input-group">
                                                <span class="input-group-text">{{ property.appraisalFirmName }}</span>
                                                <input v-model="property.appraisersFirmCode" type="number" class="form-control" style="max-width: 80px;">
                                                <button 
                                                    class="btn btn-secondary"
                                                    type="button"
                                                    @click="selectAppraisalFirmsCode(key)">
                                                    <i class="bi bi-search"></i>
                                                </button>
                                            </div>
                                        </div>
                                        <div class="form-group px-1 flex-fill">
                                            <label for="" class="table-header">Order Method</label>
                                            <select v-model="property.orderMethod" class="form-select" @change="onOrderMethodChange(property)">
                                                <option v-for="option in orderMethodOptionsCMS" :key="option.value" :value="option.text">{{ option.text }}</option>
                                            </select>
                                        </div>
                                        <div class="form-group px-1 flex-fill">
                                            <label for="" class="table-header">Who Will Pay</label>
                                            <select v-model="property.whoWillPay" class="form-select">
                                                <option v-for="option in whoWillPayOptionsCMS" :key="option.value" :value="option.text">{{ option.text }}</option>
                                            </select>
                                        </div>
                                        <div class="form-group px-1 flex-fill">
                                            <label for="" class="table-header">Appraiser's Estimate</label>
                                            <currency-input v-model="property.estimateValue"/>
                                        </div>
                                        <div class="form-group px-1 flex-fill">
                                            <label for="" class="table-header">Recent Property Type</label>
                                            <select v-model="property.recentPropType" class="form-select">
                                                <option v-for="option in propTypeOptions" :key="option" :value="option.value">{{ option.text }}</option>
                                            </select>
                                        </div>
                                        <div class="form-group px-1 flex-fill">
                                            <label for="" class="table-header">Recent Property Value</label>
                                            <currency-input v-model="property.recentPropValue"/>
                                        </div>
                                        <div class="form-group px-1 flex-fill">
                                            <label for="" class="table-header">Recent Property Date</label>
                                            <input v-model="property.recentPropDate" type="date" class="form-control">
                                        </div>
                                    </div>

                                    <div class="d-flex align-items-end flex-wrap">
                                        <div class="form-group px-1">
                                            <label for="" class="table-header">Date Ordered</label>
                                            <input v-model="property.appraisalDateOrdered" type="date" class="form-control" disabled>
                                        </div>
                                        <div class="form-group px-1">
                                            <label for="" class="table-header">Appraisal Received</label>
                                            <select v-model="property.appraisalReceived" class="form-select" @change="propertyCheckList(property, key)">
                                                <option value="Yes" key="Yes">Yes</option>
                                                <option value="No" key="No">No</option>
                                            </select>
                                        </div>
                                        <div class="form-group px-1">
                                            <label for="" class="table-header">Report Date</label>
                                            <input v-model="property.appraisalDateReceived" type="date" class="form-control" disabled>
                                        </div>
                                        <div class="form-group px-1">
                                            <label for="" class="table-header">Appraisal Value</label>
                                            <currency-input v-model="property.appraisedValue" disabled/>
                                        </div>

                                        <div class="form-group px-1 text-center">
                                            <button class="btn btn-primary" type="button" @click="appraisalUpdate(property.id)" :disabled="property.id === 0">
                                                Update Appraisal
                                            </button>
                                        </div>

                                        <div v-if="property.appraisalUpdate" class="form-group px-1 mt-2">
                                            <h6 :style="{ color: 'red' }">Appraisal updating in progress! New appraisal ordered on {{ property.required }}</h6>
                                        </div>
                                    </div>
                                    <div class="d-flex gap-2 mt-2">
                                        <div class="card">
                                            <div class="card-body d-flex flex-column justify-content-end h-100">
                                                <div class="form-group" v-if="property.hasMarketValuationAccess">
                                                    <button
                                                        button class="btn btn-primary" type="button"
                                                        @click="confirmFetchResidentialMarketValuation(property.id)"
                                                        :disabled="property.id === 0 || restrictedValuationGroup"
                                                    >
                                                        Residential Market Valuation
                                                    </button>
                                                </div>

                                                <div class="d-flex align-items-end flex-wrap gap-2">
                                                    <div class="form-group">
                                                        <label for="" class="table-header">Market Valuation Confidence</label>
                                                        <input v-model="property.marketValuationConfidence" type="text" class="form-control" disabled>
                                                    </div>
                                                    <div class="form-group">
                                                        <label for="" class="table-header">Market Valuation Amount</label>
                                                        <currency-input v-model="property.marketValuationAmount" disabled/>
                                                    </div>
                                                    <div class="form-group">
                                                        <label for="" class="table-header">Market Valuation Date</label>
                                                        <input v-model="property.marketValuationDate" type="date" class="form-control" disabled>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="card">
                                            <div class="card-body">
                                                <div class="d-flex align-items-end flex-wrap">
                                                    <div class="form-group">
                                                        <button
                                                            button class="btn btn-primary" type="button"
                                                            @click="confirmFetchEstimatedValueRange(property.id)"
                                                            :disabled="property.id === 0 || restrictedValuationGroup"
                                                        >
                                                            Estimated Value Range
                                                        </button>
                                                    </div>
                                                </div>

                                                <div class="d-flex align-items-end flex-wrap gap-2">
                                                    <div class="form-group">
                                                        <label for="" class="table-header">Estimated Low Range Value</label>
                                                        <currency-input v-model="property.estimatedLowRangeValue" disabled/>
                                                    </div>
                                                     <div class="form-group">
                                                        <label for="" class="table-header">Estimated Mid Range Value</label>
                                                        <currency-input v-model="property.estimatedMidRangeValue" disabled/>
                                                    </div>
                                                    <div class="form-group">
                                                        <label for="" class="table-header">Estimated High Range Value</label>
                                                        <currency-input v-model="property.estimatedHighRangeValue" disabled/>
                                                    </div>
                                                    <div class="form-group">
                                                        <label for="" class="table-header">Estimated Range Value Date</label>
                                                        <input v-model="property.estimatedRangeDate" type="date" class="form-control" disabled>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="card mt-3">
                                        <div class="card-header">
                                            <div class="d-flex align-items-center">
                                                <div>
                                                    <span class="m-1">Property Mortgages</span>
                                                </div>
                                                
                                                <div class="me-auto"></div>

                                                <div>
                                                    <button class="btn btn-primary" @click="addFields('propertyMortgages', key)">
                                                        <i class="bi-plus-lg me-1"></i>Add
                                                    </button>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="card-body">
                                            <template v-for="(propertyMortgage, i) in property.propertyMortgages" :key="i">
                                                <div v-if="!propertyMortgage.isRemoved" class="d-flex align-items-end flex-wrap">
                                                    
                                                    <hr class="w-100" v-if="i != 0"/>

                                                    <div class="form-group px-1">
                                                    <label for="" class="table-header">Mortgage Balance</label>
                                                    <currency-input v-model="propertyMortgage.mortgageBalance" :disabled="propertyMortgage.setting==='dependent'"/>
                                                    </div>
                                                    <div class="form-group px-1">
                                                        <label for="" class="table-header">Balance Date</label>
                                                        <input v-model="propertyMortgage.balanceDate" type="date" class="form-control" :disabled="propertyMortgage.setting==='dependent'">
                                                    </div>
                                                    <div class="form-group px-1">
                                                        <label for="" class="table-header">Payment</label>
                                                        <currency-input v-model="propertyMortgage.payment" :disabled="propertyMortgage.setting==='dependent'"/>
                                                    </div>
                                                    <div class="form-group px-1 flex-fill">
                                                        <label for="" class="table-header">Payment Type</label>
                                                        <select v-model="propertyMortgage.paymentType" class="form-select" :disabled="propertyMortgage.setting==='dependent'">
                                                            <option value="Monthly" key="Monthly">Monthly</option>
                                                            <option value="Weekly" key="Weekly">Weekly</option>
                                                            <option value="Bi-monthly" key="Bi-monthly">Bi-monthly</option>
                                                            <option value="Bi-weekly" key="Bi-weekly">Bi-weekly</option>
                                                        </select>
                                                    </div>
                                                    <div class="form-group px-1 flex-fill">
                                                        <label for="" class="table-header">PIT</label>
                                                        <select v-model="propertyMortgage.pit" class="form-select" :disabled="propertyMortgage.setting==='dependent'">
                                                            <option value="Yes" key="Yes">Yes</option>
                                                            <option value="No" key="No">No</option>
                                                        </select>
                                                    </div>
                                                    <div class="form-group px-1 flex-fill">
                                                        <label for="" class="table-header">Mortgage Insurance</label>
                                                        <select v-model="propertyMortgage.mtgeIns" class="form-select" :disabled="propertyMortgage.setting==='dependent'">
                                                            <option value="Yes" key="Yes">Yes</option>
                                                            <option value="No" key="No">No</option>
                                                        </select>
                                                    </div>
                                                    <div class="form-group px-1 flex-fill">
                                                        <label for="" class="table-header">Running Account</label>
                                                        <select v-model="propertyMortgage.runningAccount" class="form-select" :disabled="propertyMortgage.setting==='dependent'">
                                                            <option value="Yes" key="Yes">Yes</option>
                                                            <option value="No" key="No">No</option>
                                                        </select>
                                                    </div>
                                                    <div class="form-group px-1 flex-fill">
                                                        <label for="" class="table-header">Line of Credit</label>
                                                        <select v-model="propertyMortgage.lineOfCredit" class="form-select" :disabled="propertyMortgage.setting==='dependent'">
                                                            <option value="Yes" key="Yes">Yes</option>
                                                            <option value="No" key="No">No</option>
                                                        </select>
                                                    </div>                                                
                                                    <div class="form-group px-1 flex-fill">
                                                        <label for="" class="table-header">To be Paid Out</label>
                                                        <select v-model="propertyMortgage.toBePaidOut" class="form-select" :disabled="propertyMortgage.setting==='dependent'">
                                                            <option value="By Alpine">By Alpine</option>
                                                            <option value="By AOL">By AOL</option>
                                                            <option value="By Client">By Client</option>
                                                            <option value="No">No</option>
                                                            <option value="Postponement">Postponement</option>
                                                        </select>
                                                    </div>
                                                    <div class="form-group px-1">
                                                        <label for="" class="table-header">Term End</label>
                                                        <input v-model="propertyMortgage.term" type="date" class="form-control" :disabled="propertyMortgage.setting==='dependent'">
                                                    </div>
                                                    <div class="form-group px-1">
                                                        <label for="" class="table-header">Rate</label>
                                                        <input v-model="propertyMortgage.rate" type="text" class="form-control" :disabled="propertyMortgage.setting==='dependent'">
                                                    </div>
                                                    <div class="form-group px-1 flex-fill">
                                                        <label class="table-header">Lender Code</label>
                                                        <div class="input-group">
                                                            <span class="input-group-text">{{ propertyMortgage.lenderName }}</span>
                                                            <input v-model="propertyMortgage.lenderCode" type="text" class="form-control" style="max-width: 80px;" :disabled="propertyMortgage.setting==='dependent'">
                                                            <button 
                                                                class="btn btn-secondary"
                                                                type="button"
                                                                @click="selectFirm(key, i)"
                                                                :disabled="propertyMortgage.setting==='dependent'">
                                                                <i class="bi bi-search"></i>
                                                            </button>
                                                        </div>
                                                    </div>
                                                    <div class="form-group px-1">
                                                        <label for="" class="table-header">Arrears/UTD</label>
                                                        <input v-model="propertyMortgage.arrearsUtd" type="text" class="form-control" :disabled="propertyMortgage.setting==='dependent'">
                                                    </div>
                                                    <div class="form-group px-1 flex-fill">
                                                        <label for="" class="table-header">Solicit at Term</label>
                                                        <select v-model="propertyMortgage.solicitAtTerm" class="form-select" :disabled="propertyMortgage.setting==='dependent'">
                                                            <option value="Yes" key="Yes">Yes</option>
                                                            <option value="No" key="No">No</option>
                                                        </select>
                                                    </div>
                                                    <div class="form-group px-1 flex-fill">
                                                        <label for="" class="table-header">Soft Confirmed</label>
                                                        <select v-model="propertyMortgage.softConfirmed" class="form-select" :disabled="propertyMortgage.setting==='dependent'">
                                                            <option value="Yes" key="Yes">Yes</option>
                                                            <option value="No" key="No">No</option>
                                                        </select>
                                                    </div>
                                                    <div class="form-group px-1">
                                                        <label for="" class="table-header">Final Confirmed</label>
                                                        <select v-model="propertyMortgage.finalConfirmed" class="form-select" :disabled="propertyMortgage.setting==='dependent'">
                                                            <option value="Yes" key="Yes">Yes</option>
                                                            <option value="No" key="No">No</option>
                                                        </select>
                                                    </div>
                                                    <div class="form-group px-1">
                                                        <label for="" class="table-header">Co-Lender Rank</label>
                                                        <select v-model="propertyMortgage.coLenderRank" class="form-select" :disabled="propertyMortgage.setting==='dependent'">
                                                            <option v-for="option in coLenderRankOptions" :key="option" :value="option.value">{{ option.text }}</option>
                                                        </select>
                                                    </div>
                                                    <div class="form-group px-1">
                                                        <button 
                                                            class="btn btn-outline-danger"
                                                            :disabled="propertyMortgage.setting==='dependent'" 
                                                            @click="removeFields(i, 'propertyMortgages', key)">
                                                            <i class="bi bi-trash me-1"></i>Remove
                                                        </button>
                                                    </div>
                                                </div>
                                            </template>
                                        </div>
                                    </div>

                                    <div class="mt-3">
                                        <h6 class="mb-0">Property Info</h6>

                                        <template v-for="(propertyMortgage, i) in property.propertyMortgages" :key="i">
                                            <div class="d-flex align-items-end flex-wrap mb-1">
                                                <div class="form-group px-1">
                                                    <label v-if="i===0" class="table-header">#</label>
                                                    <currency-input :value="i" disabled />
                                                </div>

                                                <div class="form-group px-1">
                                                    <label v-if="i===0" class="table-header">Balance</label>
                                                    <currency-input v-model="propertyMortgage.mortgageBalance" disabled/>
                                                </div>

                                                <div class="form-group px-1">
                                                    <label v-if="i===0" class="table-header">Interalia</label>
                                                    <select v-model="propertyMortgage.setting" class="form-select">
                                                        <option value="" key=""></option>
                                                        <option value="master" key="master">Master</option>
                                                        <option value="dependent" key="dependent">Dependent</option>
                                                    </select>
                                                </div>
                                                <div class="form-group px-1">
                                                    <label v-if="i===0" class="table-header">Master</label>
                                                    <select v-model="propertyMortgage.master" class="form-select">
                                                        <option v-for="option in propertyMortgage.masterOptions" :key="option" :value="option.id">{{option.name}}</option>
                                                    </select>
                                                </div>
                                            </div>
                                        </template>

                                        <div class="d-flex align-items-end flex-wrap mt-2">
                                            <div class="form-group px-1">
                                                <label for="" class="table-header">Year Built</label>
                                                <input v-model="property.yearBuilt" type="text" class="form-control">
                                            </div>
                                            <div class="form-group px-1">
                                                <label for="" class="table-header">Lot Size</label>
                                                <input v-model="property.lotSize" type="text" class="form-control">
                                            </div>
                                            <div class="form-group px-1">
                                                <label for="" class="table-header">Floor Area</label>
                                                <input v-model="property.floorArea" type="text" class="form-control">
                                            </div>
                                            <div class="form-group px-1 flex-fill">
                                                <label for="" class="table-header">Measurement</label>
                                                <select v-model="property.floorMeasurement" class="form-select">
                                                    <option value="Square Feet">Square Feet</option>
                                                    <option value="Square Meters">Square Meters</option>
                                                </select>
                                            </div>
                                            <div class="form-group px-1 flex-fill">
                                                <label for="" class="table-header">Basement</label>
                                                <select v-model="property.basement" class="form-select">
                                                    <option v-for="option in basementOptionsCMS" :key="option.value" :value="option.text">{{ option.text }}</option>
                                                </select>
                                            </div>
                                            <div class="form-group px-1">
                                                <label for="" class="table-header">Bedrooms</label>
                                                <input v-model="property.bedrooms" type="text" class="form-control">
                                            </div>
                                            <div class="form-group px-1">
                                                <label for="" class="table-header">Bathrooms</label>
                                                <input v-model="property.bathrooms" type="text" class="form-control">
                                            </div>
                                            <div class="form-group px-1 flex-fill">
                                                <label for="" class="table-header">Heat</label>
                                                <select v-model="property.heat" class="form-select">
                                                    <option v-for="option in heatOptionsCMS" :key="option.value" :value="option.text">{{ option.text }}</option>
                                                </select>
                                            </div>
                                        </div>
                                        <div class="d-flex align-items-end flex-wrap">
                                            <div class="form-group px-1">
                                                <label for="" class="table-header">Heating Cost</label>
                                                <currency-input v-model="property.heatingCost" />
                                            </div>
                                            <div class="form-group px-1">
                                                <label for="" class="table-header">Roofing</label>
                                                <select v-model="property.roofing" class="form-select">
                                                    <option v-for="option in roofingOptionsCMS" :key="option.value" :value="option.text">{{ option.text }}</option>
                                                </select>
                                            </div>
                                            <div class="form-group px-1">
                                                <label for="" class="table-header">Exterior Finish</label>
                                                <select v-model="property.exteriorFinish" class="form-select">
                                                    <option v-for="option in exteriorOptionsCMS" :key="option.value" :value="option.text">{{ option.text }}</option>
                                                </select>
                                            </div>
                                            <div class="form-group px-1">
                                                <label for="" class="table-header">Housing Style</label>
                                                <select v-model="property.houseStyle" class="form-select">
                                                    <option v-for="option in houseOptionsCMS" :key="option.value" :value="option.text">{{ option.text }}</option>
                                                </select>
                                            </div>
                                            <div class="form-group px-1">
                                                <label for="" class="table-header">Garage</label>
                                                <input v-model="property.garage" type="text" class="form-control">
                                            </div>
                                            <div class="form-group px-1">
                                                <label for="" class="table-header">Out Building</label>
                                                <select v-model="property.outBuilding" class="form-select">
                                                    <option value="" key=""></option>
                                                    <option value="Yes" key="Yes">Yes</option>
                                                    <option value="No" key="No">No</option>
                                                </select>
                                            </div>
                                            <div class="form-group px-1">
                                                <label for="" class="table-header">Water Source</label>
                                                <select v-model="property.waterSource" class="form-select">
                                                    <option v-for="option in waterOptionsCMS" :key="option.value" :value="option.text">{{ option.text }}</option>
                                                </select>
                                            </div>
                                            <div class="form-group px-1">
                                                <label for="" class="table-header">Sewage</label>
                                                <select v-model="property.sewage" class="form-select">
                                                    <option v-for="option in sewageOptionsCMS" :key="option.value" :value="option.text">{{ option.text }}</option>
                                                </select>
                                            </div>
                                            <div class="form-group px-1 flex-fill">
                                                <label for="" class="table-header">Comments</label>
                                                <textarea v-model="property.comments" rows="3" class="form-control"></textarea>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="card mt-3 col-6">
                                        <div class="card-header">
                                            <div class="d-flex align-items-center">
                                                <div>
                                                    <span class="m-1">Property Rental</span>
                                                </div>
                                                
                                                <div class="me-auto"></div>

                                                <div>
                                                    <button class="btn btn-primary" @click="addFields('rental', key)">
                                                        <i class="bi-plus-lg me-1"></i>Add
                                                    </button>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="card-body">
                                            <div class="row">
                                                <div class="col-3">
                                                    <label class="table-header">Type</label>
                                                </div>
                                                <div class="col-3">
                                                    <label class="table-header">Monthly Income</label>
                                                </div>
                                                <div class="col-1"></div>
                                            </div> 
                                            <template v-for="(rental, index) in property.propertyRentals" :key="index">
                                                <div v-if="!rental.isRemoved" class="row mb-2">
                                                    <div class="col-3">
                                                        <select v-model="rental.type" class="form-select">
                                                            <option v-for="option in rentalOptionsCMS" :key="option.value" :value="option.text">{{ option.text }}</option>
                                                        </select>
                                                    </div>
                                                    <div class="col-3">
                                                        <currency-input v-model="rental.monthlyIncome" />
                                                    </div>
                                                    <div class="col-1">
                                                        <button class="btn btn-outline-danger" v-tooltip="'Remove Income'" @click="removeFields(index, 'rental', key)">
                                                            <i class="bi bi-trash"></i>
                                                        </button>
                                                    </div>
                                                </div>
                                            </template>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </template>

    <div v-if="selectedTab === 'vehicles'">
        <ApplicantsInfo 
            :application="application"
            :applicants="applicants"
            :properties="properties"
            :isSalesJourney="isSalesJourney"
            :agentOptions="agentOptions"
            :salesJourney="salesJourney"
            :showSalesJourney="false"
        />
        <div class="card mb-2">
            <div class="card-header">
                <div class="d-flex align-items-center">
                    <div>
                        <span class="m-1"><b>Vehicles</b></span>
                    </div>

                    <div class="me-auto"></div>

                    <div>
                        <button class="btn btn-primary" @click="addFields('vehicles')" v-tooltip="'Add Vehicle'">
                            <i class="bi-plus-lg me-1"></i>Add
                        </button>
                    </div>
                </div>
            </div>

            <div class="card-body">
                <div class="row">
                    <div class="col-5">
                        <label class="table-header">Year Make Model</label>
                    </div>
                    <div class="col-2">
                        <label class="table-header">Own/Lease</label>
                    </div>
                    <div class="col-2">
                        <label class="table-header">Financed</label>
                    </div>
                    <div class="col-2">
                        <label class="table-header">Expiry</label>
                    </div>
                    <div class="col-1"></div>
                </div>

                <template v-for="(vehicle, index) in vehicles" :key="`vehicle-${index}`">
                    <div class="row mb-1" v-if="!vehicle.isRemoved">
                        <div class="col-5">
                            <input v-model="vehicle.model" type="text" class="form-control">
                        </div>
                        <div class="col-2">
                            <select v-model="vehicle.ownLease" class="form-select">
                                <option value="Own" key="Own">Own</option>
                                <option value="Lease" key="Lease">Lease</option>
                            </select>
                        </div>
                        <div class="col-2">
                            <select v-model="vehicle.financed" class="form-select" @change="checkFinance($event, index, vehicle.model)">
                                <option value="Yes" key="Yes">Yes</option>
                                <option value="No" key="No">No</option>
                            </select>
                        </div>
                        <div class="col-2">
                            <select v-model="vehicle.expiry" class="form-select">
                                <option value="n/a" key="n/a">N/A</option>
                                <option value="1" key="1">1</option>
                                <option value="2" key="2">2</option>
                                <option value="3" key="3">3</option>
                                <option value="4" key="4">4</option>
                                <option value="5" key="5">5</option>
                                <option value="6" key="6">6</option>
                                <option value="7" key="7">7</option>
                                <option value="8" key="8">8</option>
                                <option value="9" key="9">9</option>
                                <option value="10" key="10">10</option>
                                <option value="11" key="11">11</option>
                                <option value="12" key="12">12</option>
                            </select>
                        </div>
                        <div class="col-1 d-flex gap-2">
                            <button class="btn btn-primary" @click="addFields('vehicles')" v-tooltip="'Add Vehicle'">
                                <i class="bi-plus-lg"></i>
                            </button>
                            <button class="btn btn-outline-danger" v-if="index != 0" v-tooltip="'Remove Vehicle'" @click="removeFields(index, 'vehicles')">
                                <i class="bi bi-trash"></i>
                            </button>
                        </div>
                    </div>
                </template>
            </div>
        </div>

        <div class="card mb-2">
            <div class="card-header">
                <div class="d-flex align-items-center">
                    <div>
                        <span class="m-1"><b>Liabilities</b></span>
                    </div>

                    <div class="me-auto"></div>

                    <div>
                        <button class="btn btn-primary" @click="addFields('liabilities')" v-tooltip="'Add Liabilitie'">
                            <i class="bi-plus-lg me-1"></i>Add
                        </button>
                    </div>
                </div>
            </div>             

            <div class="card-body">
                <div class="row">
                    <div class="col-3">
                        <label class="table-header">Lender / Security Type</label>
                    </div>
                    <div class="col-2">
                        <label class="table-header">Balance</label>
                    </div>
                    <div class="col-2">
                        <label class="table-header">Monthly Payment</label>
                    </div>
                    <div class="col-2">
                        <label class="table-header">To be Paid Out</label>
                    </div>
                    <div class="col-2"></div>
                    <div class="col-1"></div>
                </div>

                <template v-for="(liability, index) in liabilities" :key="index">
                    <div class="row mb-1" v-if="!liability.isRemoved">
                        <div class="col-3">
                            <input v-model="liability.lender" type="text" class="form-control">
                        </div>
                        <div class="col-2">
                            <currency-input v-model="liability.balanceOwed" />
                        </div>
                        <div class="col-2">
                            <currency-input v-model="liability.monthlyPayment" />
                        </div>
                        <div class="col-2">
                            <select v-model="liability.toBePaidOut" class="form-select">
                                <option v-for="option in payoutOptions" :key="option.text" :value="option.value">{{ option.value }}</option>
                            </select>
                        </div>
                        <div class="col-2">
                            <input v-model="liability.comment" type="text" class="form-control" disabled>
                        </div>
                        <div class="col-1 d-flex gap-2">
                            <button class="btn btn-primary" @click="addFields('liabilities')" v-tooltip="'Add Liability'">
                                <i class="bi-plus-lg"></i>
                            </button>
                            <button class="btn btn-outline-danger" v-tooltip="'Remove Liabilitie'" @click="removeFields(index, 'liabilities')">
                                <i class="bi bi-trash"></i>
                            </button>
                        </div>
                    </div>
                </template>
            </div>

            <div class="d-flex justify-content-center align-items-center mb-2">
                <div class="col-6">
                    <div class="card">
                        <div class="card-body">
                            <table class="table mb-0">
                                <thead>
                                    <tr class="text-center align-middle">
                                        <th>Total Liabilities</th>
                                        <th>Total Balance</th>
                                        <th>Total Payment</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr class="text-center align-middle">
                                        <td>{{ this.totalLiabilities }}</td>
                                        <td>{{ formatDecimal(this.totalBalance) }}</td>
                                        <td>{{ formatDecimal(this.totalPayment) }}</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div v-if="selectedTab === 'income'">
        <ApplicantsInfo 
            :application="application"
            :applicants="applicants"
            :properties="properties"
            :isSalesJourney="isSalesJourney"
            :agentOptions="agentOptions"
            :salesJourney="salesJourney"
            :showSalesJourney="false"
        />
        <div class="card mb-2">
            <div class="card-header">
                <div class="d-flex align-items-center">
                    <div>
                        <span class="m-1"><b>Present Employment</b></span>
                    </div>
                </div>
            </div>

            <div class="card-body">
                <div class="row">
                    <div class="col-1"></div>
                    <div class="col-2">
                        <label class="table-header">Employer Name</label>
                    </div>
                    <div class="col-1">
                        <label class="table-header">Position</label>
                    </div>
                    <div class="col-1">
                        <label class="table-header">Years</label>
                    </div>
                    <div class="col-1">
                        <label class="table-header">Phone</label>
                    </div>
                    <div class="col-1">
                        <label class="table-header">Hourly Rate</label>
                    </div>
                    <div class="col-1">
                        <label class="table-header">Type</label>
                    </div>                    
                    <div class="col-1">
                        <label class="table-header">Gross Income</label>
                    </div>
                    <div class="col-1 text-center">
                        <label class="table-header">Self Employed</label>
                    </div>
                    <div class="col-1 text-center">
                        <label class="table-header">POI Received</label>
                    </div>
                    <div class="col-1"></div>
                </div>

                <template v-for="(presentEmployment, index) in presentEmployments" :key="index">
                    <div v-if="!presentEmployment.isRemoved" class="row mb-2">
                        <div class="col-1">
                            <input v-model="presentEmployment.spouseName" type="text" class="form-control" disabled>    
                        </div>
                        <div class="col-2">
                            <input v-model="presentEmployment.employerName" type="text" class="form-control">
                        </div>
                        <div class="col-1">
                            <input v-model="presentEmployment.position" type="text" class="form-control">
                        </div>
                        <div class="col-1">
                            <input v-model="presentEmployment.years" type="text" class="form-control">
                        </div>
                        <div class="col-1">
                            <input v-model="presentEmployment.phone" type="text" class="form-control">
                        </div>
                        <div class="col-1">
                            <input v-model="presentEmployment.incomeHourly" type="text" class="form-control">
                        </div>
                        <div class="col-1">
                            <select v-model="presentEmployment.status" class="form-select">
                                <option value="F/T" key="F/T">F/T</option>
                                <option value="P/T" key="P/T">P/T</option>
                            </select>
                        </div>
                        <div class="col-1">
                            <currency-input v-model="presentEmployment.incomeMonthly" />
                        </div>
                        <div class="col-1 text-center">
                            <input type="checkbox" v-model="presentEmployment.selfEmployed">
                        </div>
                        <div class="col-1 text-center">
                            <input type="checkbox" v-model="presentEmployment.poiReceived">
                        </div>
                        
                        <div class="col-1">
                            <button class="btn btn-primary" v-tooltip="'Add new Present Employment'" @click="addFields('presentEmployments',index)">
                                <i class="bi-plus-lg"></i>
                            </button>
                            <button class="btn btn-outline-danger ms-3" 
                                v-tooltip="'Remove Present Employment'" 
                                @click="removeFields(index, 'presentEmployments')"
                                v-show="presentEmployment.enabledDelete">
                                <i class="bi bi-trash"></i>
                            </button>
                        </div>
                    </div>
                </template>
            </div>
        </div>

        <div class="card mb-2">
            <div class="card-header">
                <div class="d-flex align-items-center">
                    <div>
                        <span class="m-1"><b>Previous Employment</b></span>
                    </div>
                </div>
            </div>

            <div class="card-body">
                <div class="row">
                    <div class="col-3"></div>
                    <div class="col-3">
                        <label class="table-header">Employer Name</label>
                    </div>
                    <div class="col-3">
                        <label class="table-header">Position</label>
                    </div>
                    <div class="col-1">
                        <label class="table-header">Years</label>
                    </div>
                    <div class="col-1"> </div>
                </div>

                <template v-for="(previousEmployment, index) in previousEmployments" :key="index">
                    <div v-if="!previousEmployment.isRemoved" class="row mb-2">
                        <div class="col-3">
                            <input v-model="previousEmployment.spouseName" type="text" class="form-control" disabled>
                        </div>

                        <div class="col-3">
                            <input v-model="previousEmployment.employerName" type="text" class="form-control">
                        </div>
                        <div class="col-3">
                            <input v-model="previousEmployment.position" type="text" class="form-control">
                        </div>
                        <div class="col-1">
                            <input v-model="previousEmployment.years" type="text" class="form-control">
                        </div>
                        <div class="col-1">
                            <button class="btn btn-primary" v-tooltip="'Add new Previous Employment'" @click="addFields('previousEmployments', index)">
                                <i class="bi-plus-lg"></i>
                            </button>
                            <button class="btn btn-outline-danger ms-3" 
                                    v-tooltip="'Remove Previous Employment'" 
                                    @click="removeFields(index, 'previousEmployments')"
                                    v-show="previousEmployment.enabledDelete">
                                <i class="bi bi-trash"></i>
                            </button>
                        </div>
                    </div>
                </template>
            </div>
        </div>
            <div class="card mb-2">
            <div class="card-header">
                <div class="d-flex align-items-center">
                    <div>
                        <span class="m-1"><b>Rental Income</b></span>
                    </div>
                    <div class="me-auto"></div>
                    <!-- <div>
                        <button class="btn btn-primary" @click="addFields('rentals')"><i class="bi-plus-lg me-1"></i>Add</button>
                    </div> -->
                </div>
            </div>

            <div class="card-body">
                <div class="row">
                    <div class="col-8">
                        <label for="" class="table-header">Property</label>
                    </div>
                    <div class="col-3">
                        <label for="" class="table-header">Monthly Income</label>
                    </div>
                </div>
                <template v-for="(rental, index) in properties.flatMap(property => property.propertyRentals)" :key="index">
                    <div class="row mb-2">
                        <div class="col-8">
                            <input v-model="rental.property" type="text" class="form-control" disabled>
                        </div>
                        <div class="col-3">
                            <input v-model="rental.monthlyIncome" type="text" class="form-control" disabled>
                        </div>
                        <!-- <div class="col-1 d-flex gap-2">
                            <button class="btn btn-primary" @click="addFields('rental')">
                                <i class="bi-plus-lg"></i>
                            </button>
                            <button class="btn btn-outline-danger" v-tooltip="'Remove Rental Income'" @click="removeFields(index, 'rental')">
                                <i class="bi bi-trash"></i>
                            </button>
                        </div> -->
                    </div>
                </template>
               
            </div>
        </div>
        <div class="card mb-2">
            <div class="card-header">
                <div class="d-flex align-items-center">
                    <div>
                        <span class="m-1"><b>Other Source of Income</b></span>
                    </div>

                    <div class="me-auto"></div>

                    <div>
                        <button class="btn btn-primary" @click="addFields('otherSourceIncome')"><i class="bi-plus-lg me-1"></i>Add</button>
                    </div>
                </div>
            </div>

            <div class="card-body">
                <div class="row">
                    <div class="col-8">
                        <label for="" class="table-header">Source</label>
                    </div>
                    <div class="col-3">
                        <label for="" class="table-header">Monthly Income</label>
                    </div>
                    <div class="col-1"></div>
                </div>

                <template v-for="(otherIncome, index) in otherIncomes" :key="index">
                    <div class="row mb-2" v-if="!otherIncome.isRemoved">
                        <div class="col-8">
                            <input v-model="otherIncome.source" type="text" class="form-control">
                        </div>
                        <div class="col-3">
                            <currency-input v-model="otherIncome.monthly" />
                        </div>
                        <div class="col-1 d-flex gap-2">
                            <button class="btn btn-primary" @click="addFields('otherSourceIncome')">
                                <i class="bi-plus-lg"></i>
                            </button>
                            <button class="btn btn-outline-danger" v-tooltip="'Remove Income'" @click="removeFields(index, 'otherSourceIncome')">
                                <i class="bi bi-trash"></i>
                            </button>
                        </div>
                    </div>
                </template>
            </div>
            <div class="row d-flex align-items-center">
                    <div class="col-auto mt-1">
                        <label>
                            <h6 class="ps-3">Total Income:</h6>
                        </label>
                    </div>
                    <div class="col-auto">
                        <span>{{ formatDecimal(totalIncome) }}</span>
                    </div>
                </div>
        </div>

      

        <div class="card mb-2">
            <div class="card-header">
                <div class="d-flex align-items-center">
                    <div>
                        <span class="m-1"><b>Assets</b></span>
                    </div>

                    <div class="me-auto"></div>

                    <div>
                        <button class="btn btn-primary" @click="addFields('assets')"><i class="bi-plus-lg me-1"></i>Add</button>
                    </div>
                </div>
            </div>

            <div class="card-body">
                <div class="row mb-2">
                    <div class="col-8">
                        <label class="table-header">Description</label>
                    </div>
                    <div class="col-3">
                        <label class="table-header">Amount</label>
                    </div>
                    <div class="col-1"></div>
                </div>

                <template v-for="(asset, index) in assets" :key="`asset-${index}`">
                    <div class="row mb-2" v-if="!asset.isRemoved">
                        <div class="col-8">
                            <input v-model="asset.description" type="text" class="form-control">
                        </div>
                        <div class="col-3">
                            <currency-input v-model="asset.amount" />
                        </div>
                        <div class="col-1 d-flex gap-2">
                            <button class="btn btn-primary" @click="addFields('assets')">
                                <i class="bi-plus-lg"></i>
                            </button>
                            <button class="btn btn-outline-danger" v-tooltip="'Remove Asset'" @click="removeFields(index, 'assets')">
                                <i class="bi bi-trash"></i>
                            </button>
                        </div>
                    </div>
                </template>
            </div>
        </div>
    </div>

    <div v-if="selectedTab === 'administration'">
        <ApplicantsInfo 
            :application="application"
            :applicants="applicants"
            :properties="properties"
            :isSalesJourney="isSalesJourney"
            :agentOptions="agentOptions"
            :salesJourney="salesJourney"
            :showSalesJourney="false"
        />
        <div class="card mb-2">
            <div class="card-body">
                <div class="d-flex align-items-start"> 
                    <div class="form-group px-1">
                        <label class="table-header">Solicit at Term</label>
                        <select v-model="application.solicit" class="form-select">
                            <option value="Yes" key="Yes">Yes</option>
                            <option value="No" key="No">No</option>
                        </select>
                    </div>
                    <div class="form-group px-1">
                        <label class="table-header">Presell</label>
                        <select v-model="application.presell" class="form-select">
                            <option value="Yes" key="Yes">Yes</option>
                            <option value="No" key="No">No</option>
                        </select>
                    </div>
                    <div class="form-group px-1">
                        <label class="table-header">Signed Date</label>
                        <input v-model="application.signedDate" type="date" class="form-control">
                    </div>
                    <div class="form-group px-1">
                        <label class="table-header">Expected Funding Date</label>
                        <input v-model="application.fundingDate" type="date" class="form-control">
                    </div>
                    <div class="form-group px-1">
                        <label class="table-header">Signing Appointment</label>
                        <input v-model="application.signingDatetime" type="datetime-local" class="form-control">
                    </div>
                </div>
            </div>
        </div>
    </div>

    <Checklist
        :application="application" 
        :quote="propertyTmp"
        :checkListType="'APC'"
        :refreshCount="refreshCount"
        @close="checklistClosed"
    />
    <ContactCenterDisbursement :selectedTab="selectedTab" :application="application" key="quote" />
    <ContactCenterQuote :selectedTab="selectedTab" :isSalesJourney="isSalesJourney" :application="application" :applicants="applicants" :agentOptions="agentOptions" :salesJourney="salesJourney" key="quote" @refreshData="getData" />
    <LenderModal @selectFirm="onSelectFirm" />
    <InsuranceBrokerModal @selectInsBrokerCode="onSelectInsBrokerCode" />
    <AppraisalFirmsModal @selectInsBrokerCode="onSelectAppraisalFirmCode" />

    <appraisal-date-modal
        :propertyId="propertyIdTmp"        
        :refreshCount="refreshCount"
        @refresh="getData()">
    </appraisal-date-modal>

    <ConfirmationDialog
        :event="event"
        :message="dialogMessage"
        type="danger"
        :parentModalId="modalId"
        :key="modalId"
        @return="removeDialogOnReturn"
    />

    <!-- Confirmation Dialog for fetchResidentialMarketValuation -->
    <ConfirmationDialog
        :event="residentialMarketValuationEvent"
        :message="residentialMarketValuationDialogMessage"
        type="success"
        :parentModalId="residentialMarketValuationModalId"
        :key="residentialMarketValuationModalId"
        @return="residentialMarketValuationDialogOnReturn"
    />

    <!--Confirmation Dialog for Confirmation Dialog for fetchEstimatedValueRange -->
    <ConfirmationDialog
        :event="estimatedValueRangeEvent"
        :message="estimatedValueRangeDialogMessage"
        type="success"
        :parentModalId="estimatedValueRangeModalId"
        :key="estimatedValueRangeModalId"
        @return="estimatedValueRangeDialogOnReturn"
    />

    <TitleHolders
        :applicationId="applicationId"
        :propertyId="propertyIdTmp"
        :refreshCount="refreshCount"
        :thIndex="thIndex"
        :thType="thType"
        @titleHoldersUpdated="updateLocalTitleHolders">
    </TitleHolders>

</template>

<script>
import { util } from '../mixins/util'
import { application } from '../mixins/application'
import CurrencyInput from '../components/CurrencyInput.vue'
import PhoneInput from '../components/PhoneInput.vue'
import SourceInput from '../components/SourceInput.vue'
import LenderModal from '../components/modals/LenderModal.vue'
import InsuranceBrokerModal from '../components/modals/InsuranceBrokerModal.vue'
import AppraisalDateModal from '../components/modals/AppraisalDateModal.vue'
import AppraisalFirmsModal from '../components/modals/AppraisalFirmsModal.vue'
import ConfirmationDialog from '../components/ConfirmationDialog'
import ContactCenterViewApp from '../components/ContactCenterViewApp.vue'
import ContactCenterQuote from '../components/ContactCenterQuote.vue'
import Checklist from '../components/Checklist'
import ContactCenterDisbursement from '../components/ContactCenterDisbursement.vue'
import ApplicantsInfo from '../components/ApplicantsInfo.vue'
import TitleHolders from '../components/TitleHolders.vue'
import MultiSelect from "../components/MultiSelect.vue"
import AutoComplete from "../components/AutoComplete.vue"
export default {
    components: { 
        CurrencyInput,
        PhoneInput,
        SourceInput,
        LenderModal,
        InsuranceBrokerModal,
        AppraisalDateModal,
        AppraisalFirmsModal,
        ConfirmationDialog,
        ContactCenterViewApp,
        ContactCenterQuote,
        Checklist,
        ContactCenterDisbursement,
        ApplicantsInfo,
        TitleHolders,
        MultiSelect,
        AutoComplete,
    },
    mixins: [util,application],
    emits: ['events'],
    data() {
        return {
            otherTmp: '',
            showChecklistAppr: false,
            applicationId: '',
            type: '',
            modalId: 'contactCenter',

            residentialMarketValuationModalId: 'residentialMarketValuation', // For fetchResidentialMarketValuation
            residentialMarketValuationEvent: null,
            residentialMarketValuationDialogMessage: '',
            residentialMarketValuationIdexTmp: null,
            estimatedValueRangeModalId: 'estimatedValueRange', // Unique modal ID
            estimatedValueRangeEvent: null,
            estimatedValueRangeDialogMessage: '',
            estimatedValueRangeIdexTmp: null,
            event: '',
            dialogMessage: null,
            application: {},
            applicants: [],
            mailings: [],
            corporations: [],
            properties: [],
            vehicles: [],
            liabilities: [],
            presentEmployments: [],
            previousEmployments: [],
            otherIncomes: [],
            assets: [],
            companies: [],
            selectedTab: 'view',
            validations: [],
            totalLiabilities: 0,
            totalBalance: 0,
            totalPayment: 0,
            totalIncome: 0,
            titleHoldersObj: [],
            corporations: [],
            activeMortgages: [],
            checks: [],
            streetOptions: [],
            directionCMSOptions: [],
            valueMethodOptionsCMS: [],
            orderMethodOptionsCMS: [],
            whoWillPayOptionsCMS: [],
            propertyTypesCMS: [],
            unitTypeOptionsCMS: [],
            basementOptionsCMS: [],
            heatOptionsCMS: [],
            roofingOptionsCMS: [],
            exteriorOptionsCMS: [],
            houseOptionsCMS: [],
            waterOptionsCMS: [],
            sewageOptionsCMS: [],
            rentalOptionsCMS: [],
            applicantTypeOptionsCMS: [],
            applicantTypeOptionsSignature: [],
            maritalStatusOptionsCMS: [],
            tabs: [
                { name: 'view', label: 'Summary' },
                { name: 'applicants', label: 'Applicants' },
                { name: 'properties', label: 'Mailing / Properties' },
                { name: 'vehicles', label: 'Vehicles / Liabilities' },
                { name: 'income', label: 'Income' },
                { name: 'quote', label: 'Quote' },
                { name: 'administration', label: 'Administration' }
            ],
            refreshCount: 0,
            propertyIdTmp: 0,
            idexTmp: 0,
            quotes: [],
            propertyTmp: {},
            keyTmp: 0,
            emailAddress: '',
            subject: '',
            message: '',
            purposeDetailOptions: [],
            totalApplicants: 0,
            thIndex: 0,
            thType: '',
            isSalesJourney: false,
            restrictedValuationGroup: true,
            signersOptions: [],
            agentOptions: [],
            signingAgentOptions: [],
            agentSequenceOptions: [],
            brokerOptions: [],
            salesJourney:[],
            sourceOptions: [],
            sequenceAgentList: [],
            urgencyOptions: [],
            businessChannelOptions: []
        }
    },  
    mounted() {
        this.applicationId = this.$route.params.id
        this.type = this.$route.params.type

        if(this.type !== 'all') {
            this.selectedTab = this.type
        }

        this.getData()
        this.getSalesJourneyStatus()
        this.getQuotes()
        this.getStreetTypes()
        this.getDirectionTypes()
        this.getValueMethodOptions()
        this.getOrderMethodOptions()
        this.getWhoWillPayOptions()
        this.getPropertyTypes()
        this.getUnitTypeOptions()
        this.getBasementOptions()
        this.getHeatOptions()
        this.getRoofingOptions()
        this.getExteriorOptions()
        this.getHouseOptions()
        this.getWaterOptions()
        this.getSewageOptions()
        this.getRentalOptions()
        this.getApplicantTypeOptions()
        this.getMaritialStatusOptions()
        this.getBrokerOptions()
    },
    watch: {
        liabilities: {
            handler(val) {
                this.summaryLiabilities()
            },
            deep: true,
        },
        properties: {
            handler(val) {
                this.summaryIncome()
            },
            deep: true,
        },
        presentEmployments: {
            handler(val) {
                this.summaryIncome()
            },
            deep: true,
        },
        otherIncomes: {
            handler(val) {
                this.summaryIncome()
            },
            deep: true,
        }
    },
    computed: {
        activeMailing: function () {
            var active = false

            this.mailings.forEach((mailing, index) => {
                if(!mailing.isRemoved) {
                    active = true
                }
            })

            return active
        },
        bdmSequenceAgents() {
            return this.agentSequenceOptions.filter(
                (sequenceAgent) => sequenceAgent.isBdm === "yes"
            );
        },
        underWritingSequenceAgents() {
            return this.agentSequenceOptions.filter(
                (sequenceAgent) => sequenceAgent.isUnderwritingAssistant === "yes"
            );
        },
        selectedBroker() {
            return (
                this.brokerOptions.find(
                    (broker) => broker.id === this.application.brokerId
                ) || {}
            )
        },
        selectedSource1() {
            return (
                this.sourceOptions.find(
                    (source) => source.id === parseInt(this.application.source1)
                ) || {}
            )
        },
        selectedSource2() {
            return (
                this.sourceOptions.find(
                    (source) => source.id === parseInt(this.application.source2)
                ) || {}
            )
        },
        isLicenseChecked() {
            if(this.application.licenseChecked){
                if(this.application.licenseChecked === 'No'){
                    return false
                } else {
                    return true
                }
            }else {
                return false
            }
        }
    },
    methods: {
        onOrderMethodChange: function(property) {

            this.axios({
                method: 'post',
                url: '/web/contact-center/send-appraisal-email' ,
                data: {
                    applicationId: this.applicationId,
                    orderMethod: property.orderMethod,
                    appraisalFirmCode: property.appraisersFirmCode,
                    propertyId: property.id,
                    payer: property.whoWillPay
                }
            })
            .then(response => {
                if(response.data.status === 'success') {                   

                    this.emailAddress = response.data.data.emailAddress
                    this.subject = response.data.data.subject
                    this.message = response.data.data.message

                    if (this.emailAddress === '' || this.subject === '' || this.message === '') {
                        return                        
                    }
                    location.replace('mailto:' + this.emailAddress + '?Subject=' + this.subject  + '&Body=' + this.message);
                }
                
            })
        },
        tabChanged: function(tab) {
            this.selectedTab = tab
        },
        summaryIncome: function() {
            this.totalIncome = 0

            Object.values(this.properties.flatMap(property => property.propertyRentals)).forEach(row => {
                this.totalIncome += Number(row.monthlyIncome) || 0;
            });

            Object.values(this.presentEmployments).forEach(row => {
                this.totalIncome += Number(row.incomeMonthly) || 0;
            });

            Object.values(this.otherIncomes).forEach(row => {
                this.totalIncome += Number(row.monthly) || 0;
            });

        },
        summaryLiabilities: function() {
            let x = 0;
            this.totalLiabilities = 0;
            this.totalBalance = 0;
            this.totalPayment = 0;

            
            Object.values(this.liabilities).forEach(liability => {
                x += 1;
                this.totalLiabilities = x;
                this.totalBalance += Number(liability.balanceOwed) || 0;
                this.totalPayment += Number(liability.monthlyPayment) || 0;
            });
        },
        confirmFetchResidentialMarketValuation(propertyId) {
            this.residentialMarketValuationDialogMessage = "Are you sure you want to fetch Residential Market Valuation?";
            this.residentialMarketValuationEvent = 'fetchResidentialMarketValuation';
            this.residentialMarketValuationIdexTmp = propertyId;
            this.showModal('confirmationDialog' + this.residentialMarketValuationModalId);            
        },
        fetchResidentialMarketValuation(propertyId) {
            if (propertyId === 0) return

            this.showPreLoader();

            this.axios({
                method: 'post',
                url: '/web/contact-center/residential-market-valuation',
                params: { propertyId }
            })
            .then((response) => {
                if(response.data.data && response.data.data.status === 'success') {
                    this.getData();
                } else {
                    this.alertMessage = response.data.data.message
                    this.showAlert(response.data.data.status)
                }
            })
            .catch((error) => {
                this.alertMessage = error
                 if (error.response && error.response.status === 403) {
                    this.restrictedValuationGroup = true;
                }
                this.showAlert('error')
            })
            .finally(() => {
                this.hidePreLoader()
            })
        },
        residentialMarketValuationDialogOnReturn(event, status) {
            if (status !== 'confirmed') {
                this.residentialMarketValuationEvent = null;
                this.residentialMarketValuationDialogMessage = '';
                this.residentialMarketValuationIdexTmp = null;
                return;
            }

            if (this.residentialMarketValuationEvent === 'fetchResidentialMarketValuation') {
                this.fetchResidentialMarketValuation(this.residentialMarketValuationIdexTmp);
            }

            this.residentialMarketValuationEvent = null;
            this.residentialMarketValuationDialogMessage = '';
            this.residentialMarketValuationIdexTmp = null;
        },
        confirmFetchEstimatedValueRange(propertyId) {
            this.estimatedValueRangeDialogMessage = "Are you sure you want to fetch Estimated Value Range Details?";
            this.estimatedValueRangeEvent = 'fetchEstimatedValueRange';
            this.estimatedValueRangeIdexTmp = propertyId;
            this.showModal('confirmationDialog' + this.estimatedValueRangeModalId);
        },
        fetchEstimatedValueRange: function(propertyId) {
            if (propertyId === 0) return;

            this.showPreLoader();

            this.axios({
                method: 'post',
                url: '/web/contact-center/estimated-value-range',
                params: { propertyId }
            })
            .then((response) => {
                if (response.data.data.status === 'success') {
                    this.getData();
                } else {
                    this.alertMessage = response.data.data.message
                    this.showAlert(response.data.data.status)
                }
            })
            .catch((error) => {
                this.alertMessage = error
                this.showAlert('error')
            })
           .finally(() => {
                this.hidePreLoader();
            });
        },
        estimatedValueRangeDialogOnReturn(event, status) {
            if (status !== 'confirmed') {
                this.estimatedValueRangeEvent = null;
                this.estimatedValueRangeDialogMessage = '';
                this.estimatedValueRangeIdexTmp = null;
                return;
            }

            if (this.estimatedValueRangeEvent === 'fetchEstimatedValueRange') {
                this.fetchEstimatedValueRange(this.estimatedValueRangeIdexTmp);
            }

            this.estimatedValueRangeEvent = null;
            this.estimatedValueRangeDialogMessage = '';
            this.estimatedValueRangeIdexTmp = null;
        },
        addFields: function(field, index) {
            if(field === 'vehicles') {
                this.vehicles.push({
                    id: 0,
                    model: '',
                    ownLease: 'Own',
                    financed: 'No',
                    expiry: 'n/a',
                    isRemoved: false
                })

            } else if(field === 'liabilities') {
                this.liabilities.push({ 
                    id: 0,
                    lender: '',
                    balanceOwed: 0,
                    monthlyPayment: 0,
                    toBePaidOut: 'No',
                    comment: '',
                    isRemoved: false
                })
            } else if(field === 'assets') {
                this.assets.push({ 
                    id: 0,
                    description: '', 
                    amount: 0, 
                    isRemoved: false })
            } else if (field === 'applicants') {
                this.applicants.push({
                    spouses: [
                        {
                            id: 0,
                            firstName: '',
                            middleName: '',
                            lastName: '',
                            preferredName: '',
                            gender: 'F',
                            dateOfBirth: null,
                            age: '',
                            sin: '',
                            beaconScore: 0,
                            type: 'Applicant',
                            mainContact: 'No',
                            relation: '',
                            isPep: '',
                            pepDescription: '',
                            signatureType: 'Applicant',
                            signer: 0,
                            isRemoved: false
                        }
                    ],
                    contacts: [],
                    id: 0,
                    homePhone: '',
                    homeMobile: '',
                    email: '',
                    maritalStatus: '',
                    creditBureauRec: 0,
                    yearsOfMaritalStatus: '',
                    childrenCount: '',
                    childrenAges: '',
                    contactOptions: [],
                    isRemoved: false
                });
            } else if (field === 'spouses') {
                this.applicants[index].spouses.push({
                    id: 0,
                    firstName: '',
                    middleName: '',
                    lastName: '',
                    preferredName: '',
                    gender: 'F',
                    dateOfBirth: null,
                    age: '',
                    sin: '',
                    beaconScore: 0,
                    type: 'Applicant',
                    mainContact: 'No',
                    relation: '',
                    isPep: '',
                    pepDescription: '',
                    signatureType: 'Applicant',
                    signer: 0,
                    isRemoved: false
                });
            } else if (field === 'mailing') {
                this.mailings.push({
                    id: 0,
                    type: 'Mailing',
                    recipients: '',
                    unitNumber: '',
                    unitType: 'N/A',
                    streetNumber: '',
                    streetName: '',
                    streetType: 'ST',
                    streetDirection: 'N/A',
                    city: '',
                    province: 'BC',
                    postalCode: '',
                    poNumber: '',
                    station: '',
                    rrNumber: '',
                    site: '',
                    compartment: '',
                    other: '',
                    howLong: '',
                    isRemoved: false
                });
                
            } else if (field === 'properties') {
                this.properties.push({
                    id: 0,
                    titleHolders: null,
                    alpineInterest: 100,
                    taxes: 0,
                    arrearsUtd: null,
                    insBrokerId: 0,
                    insArrears: null,
                    insExpireDate: null,
                    unitNumber: '',
                    unitType: null,
                    streetNumber: null,
                    streetName: null,
                    streetType: null,
                    direction: 'N/A',
                    city: null,
                    province: null,
                    postalCode: null,
                    pid: null,
                    legal: null,
                    sameAsMailing: null,
                    customerValue: null,
                    assessedValue: null,
                    landAssdValue: null,
                    buildingAssdValue: null,
                    numberOfYears: null,
                    costPrice: null,
                    downpayment: null,
                    ownRent: null,
                    partOfSecurity: null,
                    ruralUrban: null,
                    strata: null,
                    valueMethod: 'Appraisal',
                    appraisersFirmCode: null,
                    appraiserCode: null,
                    orderMethod: null,
                    whoWillPay: null,
                    appraisalDateOrdered: null,
                    appraisalReceived: null,
                    appraisalDateReceived: null,
                    appraisedValue: null,
                    estimateValue: null,
                    recentPropType: null,
                    recentPropValue: null,
                    recentPropDate: null,
                    marketValuationConfidence: null,
                    marketValuationAmount: null,
                    marketValuationDate: null,
                    estimatedLowRangeValue: null,
                    estimatedHighRangeValue: null,
                    estimatedRangeDate: null,
                    propertyRentals: [
                        {
                            id: 0,
                            type: 'N/A',
                            property: '',
                            monthlyIncome: 0,
                            isRemoved: false
                        }
                    ],
                    propertyMortgages: [
                        {
                            id: 0,
                            mortgageBalance: null,
                            balanceDate: null,
                            payment: null,
                            paymentType: null,
                            pit: null,
                            mtgeIns: null,
                            runningAccount: null,
                            lineOfCredit: null,
                            toBePaidOut: 'No',
                            term: null,
                            rate: null,
                            lenderCode: null,
                            arrearsUtd: null,
                            solicitAtTerm: null,
                            softConfirmed: null,
                            finalConfirmed: null,
                            deferredConfirmed: null,
                            deferredAmount: null,
                            coLenderRank: null,
                            master: null,
                            setting: null,
                            isRemoved: false
                        }
                    ],
                    yearBuilt: null,
                    lotSize: null,
                    floorArea: null,
                    floorMeasurement: null,
                    basement: null,
                    bedrooms: null,
                    bathrooms: null,
                    heat: null,
                    heatingCost: null,
                    roofing: null,
                    exteriorFinish: null,
                    houseStyle: null,
                    garage: null,
                    outBuilding: null,
                    waterSource: null,
                    sewage: null,
                    comments: null,
                    strata: null,
                    strataArrears: null,
                    strataCompany: null,
                    strataContact: null,
                    strataPhone: null,
                    strataFax: null,
                    strataEmail: null,
                    strataOther: null,
                    isRemoved: false
                });                
            } else if (field === 'propertyMortgages') {
                this.properties[index].propertyMortgages.push({
                    id: 0,
                    mortgageBalance: 0,
                    balanceDate: null,
                    payment: null,
                    paymentType: null,
                    pit: null,
                    mtgeIns: null,
                    runningAccount: null,
                    lineOfCredit: null,
                    toBePaidOut: 'No',
                    term: null,
                    rate: null,
                    lenderCode: null,
                    arrearsUtd: null,
                    solicitAtTerm: null,
                    softConfirmed: null,
                    finalConfirmed: null,
                    deferredConfirmed: null,
                    deferredAmount: null,
                    coLenderRank: null,
                    master: null,
                    setting: null,
                    isRemoved: false
                });
            } else if (field === 'rental') {
                this.properties[index].propertyRentals.push({
                    id: 0,
                    type: 'N/A',
                    property: '',
                    monthlyIncome: 0,
                    isRemoved: false
                });
                
            } else if (field === 'otherSourceIncome') {
                this.otherIncomes.push({
                    id: 0,
                    source: '',
                    monthly: 0,
                    isRemoved: false
                });                
            } else if(field === 'corporations') {
                this.corporations.push({
                    id: 0,
                    companyName: '',
                    incNumber: '',
                    directors: '',
                    phone: '',
                    mobile: '',
                    email: '',
                    isRemoved: false
                });

            } else if (field === 'contacts') {
                this.applicants[index].contacts.push({
                    id: 0,
                    type: '',
                    info: '',
                    isRemoved: false
                });
            } else if (field === 'presentEmployments') {

                let nameTmp = this.presentEmployments[index].spouseName;
                let spouseIdTmp = this.presentEmployments[index].spouseId;

                this.presentEmployments.push({
                    id: 0,
                    spouseId: spouseIdTmp,
                    spouseName: nameTmp,
                    employerName: '',
                    position: '',
                    years: '',
                    phone: '',
                    incomeHourly: '',
                    status: 'F/T',
                    incomeMonthly: 0,
                    selfEmployed: false,
                    poiReceived: false,
                    enabledDelete: true,
                    isRemoved: false
                });
            } else if (field === 'previousEmployments') {

                let nameTmp = this.previousEmployments[index].spouseName;
                let spouseIdTmp = this.previousEmployments[index].spouseId;

                this.previousEmployments.push({
                    id: 0,
                    spouseId: spouseIdTmp,
                    spouseName: nameTmp,
                    employerName: '',
                    position: '',
                    years: '',
                    enabledDelete: true,
                    isRemoved: false
                });
            }
        },
        removeFields: function(index, field, indexFather) {
            if(field === 'assets') {
                this.assets[index].isRemoved = true
            } else if (field === 'otherSourceIncome') {
                this.otherIncomes[index].isRemoved = true
            } else if(field === 'liabilities') {
                this.liabilities[index].isRemoved = true
            } else if( field === 'vehicles' ) {
                this.vehicles[index].isRemoved = true
            } else if(field === 'rental') {
                this.properties[indexFather].propertyRentals[index].isRemoved = true
            } else if (field === 'propertyMortgages') {
                this.properties[indexFather].propertyMortgages[index].isRemoved = true
            } else if (field === 'applicants') {
                this.applicants[index].isRemoved = true
                this.store()
            } else if (field === 'corporations') {
                this.corporations[index].isRemoved = true
            } else if (field === 'contacts') {
                this.applicants[indexFather].contacts[index].isRemoved = true
            } else if(field === 'mailing') {
                this.mailings[index].isRemoved = true
            } else if (field === 'properties') {
                this.properties[index].isRemoved = true
                this.store()
            } else if (field === 'presentEmployments') {
                this.presentEmployments[index].isRemoved = true
            } else if (field === 'previousEmployments') {
                this.previousEmployments[index].isRemoved = true
            }
        },
        checkFinance: function(e, vehicleIndex, model){
            if( e.target.value === 'Yes' ) {
                this.liabilities.push({ 
                    id: 0,
                    lender: '',
                    balanceOwed: 0,
                    monthlyPayment: 0,
                    comment: 'Secured by ' + model, 
                    toBePaidOut: 'No',
                    isRemoved: false
                })
            } else {
                this.removeFinance(vehicleIndex)
            }
        },
        removeFinance: function(vehicleIndex){
            let balances = this.app.outstandingBalance.values
            for(let i = 0; i < balances.length; i++) {
                if( typeof balances[i].vehicleIndex !== "undefined" ) {
                    if( balances[i].vehicleIndex === vehicleIndex ) {
                        balances.splice(i, 1);
                    }
                }
            }
            this.app.outstandingBalance.values = balances;
        },
        selectFirm: function(propertyIndex, mortgageIndex) {
            this.propertyIndex = propertyIndex;
            this.mortgageIndex = mortgageIndex;
            this.showModal('lenderModal');
        },
        onSelectFirm: function(v) {

            let propertyIndex = this.propertyIndex;
            let mortgageIndex = this.mortgageIndex;

            if (this.properties[propertyIndex] && this.properties[propertyIndex].propertyMortgages[mortgageIndex]) {
                this.properties[propertyIndex].propertyMortgages[mortgageIndex].lenderCode = v.lender_branch_code;
                this.properties[propertyIndex].propertyMortgages[mortgageIndex].lenderName = v.firm_name;
            }
        },
        selectInsBrokerCode: function(propertyIndex) {
            this.propertyIndex = propertyIndex;
            this.showModal('insuranceBrokerModal');
        },
        selectAppraisalFirmsCode: function(propertyIndex) {
            this.propertyIndex = propertyIndex;
            this.showModal('appraisalFirmsModal');
        },
        onSelectInsBrokerCode: function(v) {
            let propertyIndex = this.propertyIndex;

            if (this.properties[propertyIndex]) {
                this.properties[propertyIndex].insBrokerId = v;
            }
        },
        onSelectAppraisalFirmCode: function(v) {
            let propertyIndex = this.propertyIndex;

            if (this.properties[propertyIndex]) {
                this.properties[propertyIndex].appraisersFirmCode = v;
            }
        },
        appraisalUpdate: function(propertyId) {
            if (propertyId > 0) {
                this.propertyIdTmp = propertyId
                this.refreshCount++
                this.showModal('appraisalDateModal')
            }

        },
        removeBtn: function(idexTmp, removeEvent) {
            if (removeEvent === 'applicants') {
                this.dialogMessage = "Are you sure you want to DELETE this applicant and all aplicant's information?"
            } else if (removeEvent === 'properties') {
                this.dialogMessage = "Are you sure you want to DELETE this property and all property's information?"
            }

            this.idexTmp = idexTmp
            this.event = removeEvent
            this.showModal('confirmationDialog' + this.modalId)
        },
        editTitleHolder: function(propertyId, thIndex, thType) {
            this.refreshCount++
            this.propertyIdTmp = propertyId
            this.thIndex = thIndex
            this.thType = thType
            this.showModal('TitleHolders')
        },
        updateLocalTitleHolders({ thIndex, titleHolders , thType }) {
            if (thType === 'property') {
                if (this.properties[thIndex]) {
                    this.properties[thIndex].titleHolders = titleHolders;
                }                
            } else {
                if (this.mailings[thIndex]) {
                    this.mailings[thIndex].recipients = titleHolders;
                }
            }
        },
        removeDialogOnReturn: function (event, returnMessage, returnRemoveReason) {
            if (returnMessage === 'confirmed') {
                if(event === 'applicants') {
                    this.removeFields(this.idexTmp, 'applicants')

                } else if(event === 'properties') {
                    this.removeFields(this.idexTmp, 'properties')
                }
            }
        },
        store: function() {
            // Validate amountRequested
            const amount = parseFloat(this.application.amountRequested);
            if (isNaN(amount) || amount <= 0) {
                this.alertMessage = 'Amount Requested is required and cannot be zero';
                this.showAlert('error');
                return;
            }

            this.showPreLoader()

            this.axios({
                method: 'put',
                url: '/web/contact-center/' + this.applicationId + '/' + this.type,
                data: {
                    applicants: this.applicants,
                    mailings: this.mailings,
                    corporations: this.corporations,
                    properties: this.properties,
                    vehicles: this.vehicles,
                    liabilities: this.liabilities,
                    presentEmployments: this.presentEmployments,
                    previousEmployments: this.previousEmployments,
                    otherIncomes: this.otherIncomes,
                    assets: this.assets,
                    application: this.application
                }
            })
            .then(response => {
                if(response.data.status === 'success') {
                    this.getData()
                    
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
        getStreetTypes: function() {
            this.axios.get('/web/contact-center/street-types')
            .then(response => {
                if(this.checkApiResponse(response)){
                    this.streetOptions = response.data.data.map(item => ({
                        value: item.abbreviation,
                        text: item.name
                    }));
                }
            })
            .catch(error => {
                console.error('Error fetching street types:', error);
            });
        },
        getDirectionTypes: function() {
            this.axios.get('/web/contact-center/direction-types')
            .then(response => {
                if(this.checkApiResponse(response)){
                    this.directionCMSOptions = response.data.data.map(item => ({
                        value: item.abbreviation,
                        text: item.name
                    }));
                }
            })
            .catch(error => {
                console.error('Error fetching street types:', error);
            });
        },
        getValueMethodOptions: function() {
            this.axios.get('/web/contact-center/value-method-options')
            .then(response => {
                if(this.checkApiResponse(response)){
                    this.valueMethodOptionsCMS = response.data.data.map(item => ({
                        value: item.id,
                        text: item.name
                    }));
                }
            })
            .catch(error => {
                console.error('Error fetching street types:', error);
            });
        },
        getOrderMethodOptions: function() {
            this.axios.get('/web/contact-center/order-method-options')
            .then(response => {
                if(this.checkApiResponse(response)){
                    this.orderMethodOptionsCMS = response.data.data.map(item => ({
                        value: item.id,
                        text: item.name
                    }));
                }
            })
            .catch(error => {
                console.error('Error fetching street types:', error);
            });
        },
        getBrokerOptions: function() {
            this.axios.get('/web/contact-center/broker-options')
            .then(response => {
                if(this.checkApiResponse(response)){
                    /*this.orderMethodOptionsCMS = response.data.data.map(item => ({
                        value: item.id,
                        text: item.name
                    }));*/

                    this.brokerOptions = response.data.data
                }
            })
            .catch(error => {
                console.error('Error fetching street types:', error);
            });
        },
        getWhoWillPayOptions: function() {
            this.axios.get('/web/contact-center/who-will-pay-options')
            .then(response => {
                if(this.checkApiResponse(response)){
                    this.whoWillPayOptionsCMS = response.data.data.map(item => ({
                        value: item.id,
                        text: item.name
                    }));
                }
            })
            .catch(error => {
                console.error('Error fetching street types:', error);
            });
        },
        getPropertyTypes: function() {
            this.axios.get('/web/contact-center/property-types')
            .then(response => {
                if(this.checkApiResponse(response)){
                    this.propertyTypesCMS = response.data.data.map(item => ({
                        value: item.id,
                        text: item.name
                    }));
                }
            })
            .catch(error => {
                console.error('Error fetching street types:', error);
            });
        },
        getUnitTypeOptions: function() {
            this.axios.get('/web/contact-center/unit-type-options')
            .then(response => {
                if(this.checkApiResponse(response)){
                    this.unitTypeOptionsCMS = response.data.data.map(item => ({
                        value: item.id,
                        text: item.name
                    }));
                }
            })
            .catch(error => {
                console.error('Error fetching street types:', error);
            });
        },
        getBasementOptions: function() {
            this.axios.get('/web/contact-center/basement-options')
            .then(response => {
                if(this.checkApiResponse(response)){
                    this.basementOptionsCMS = response.data.data.map(item => ({
                        value: item.id,
                        text: item.name
                    }));
                }
            })
            .catch(error => {
                console.error('Error fetching street types:', error);
            });
        },
        getHeatOptions: function() {
            this.axios.get('/web/contact-center/heat-options')
            .then(response => {
                if(this.checkApiResponse(response)){
                    this.heatOptionsCMS = response.data.data.map(item => ({
                        value: item.id,
                        text: item.name
                    }));
                }
            })
            .catch(error => {
                console.error('Error fetching street types:', error);
            });
        },
        getRoofingOptions: function() {
            this.axios.get('/web/contact-center/roofing-options')
            .then(response => {
                if(this.checkApiResponse(response)){
                    this.roofingOptionsCMS = response.data.data.map(item => ({
                        value: item.id,
                        text: item.name
                    }));
                }
            })
            .catch(error => {
                console.error('Error fetching street types:', error);
            });
        },
        getExteriorOptions: function() {
            this.axios.get('/web/contact-center/exterior-options')
            .then(response => {
                if(this.checkApiResponse(response)){
                    this.exteriorOptionsCMS = response.data.data.map(item => ({
                        value: item.id,
                        text: item.name
                    }));
                }
            })
            .catch(error => {
                console.error('Error fetching street types:', error);
            });
        },
        getHouseOptions: function() {
            this.axios.get('/web/contact-center/house-options')
            .then(response => {
                if(this.checkApiResponse(response)){
                    this.houseOptionsCMS = response.data.data.map(item => ({
                        value: item.id,
                        text: item.name
                    }));
                }
            })
            .catch(error => {
                console.error('Error fetching street types:', error);
            });
        },
        getWaterOptions: function() {
            this.axios.get('/web/contact-center/water-options')
            .then(response => {
                if(this.checkApiResponse(response)){
                    this.waterOptionsCMS = response.data.data.map(item => ({
                        value: item.id,
                        text: item.name
                    }));
                }
            })
            .catch(error => {
                console.error('Error fetching street types:', error);
            });
        },
        getSewageOptions: function() {
            this.axios.get('/web/contact-center/sewage-options')
            .then(response => {
                if(this.checkApiResponse(response)){
                    this.sewageOptionsCMS = response.data.data.map(item => ({
                        value: item.id,
                        text: item.name
                    }));
                }
            })
            .catch(error => {
                console.error('Error fetching street types:', error);
            });
        },
        getRentalOptions: function() {
            this.axios.get('/web/contact-center/rental-options')
            .then(response => {
                if(this.checkApiResponse(response)){
                    this.rentalOptionsCMS = response.data.data.map(item => ({
                        value: item.id,
                        text: item.name
                    }));
                }
            })
            .catch(error => {
                console.error('Error fetching street types:', error);
            });
        },
        getApplicantTypeOptions: function() {
            this.axios.get('/web/contact-center/applicant-type-options')
            .then(response => {
                if(this.checkApiResponse(response)){
                    this.applicantTypeOptionsCMS = response.data.data.map(item => ({
                        value: item.id,
                        text: item.name
                    }))

                    this.applicantTypeOptionsSignature = response.data.data.filter(
                        item => item.name !== 'Do not contact' && item.name !== 'Not a co-applicant' && item.name !== 'Transmittal_Letter'
                    );

                    this.applicantTypeOptionsSignature = this.applicantTypeOptionsSignature.map(item => ({
                        id: item.id,
                        name: item.name
                    }))
                }
            })
            .catch(error => {
                console.error('Error fetching street types:', error);
            });
        },
        getMaritialStatusOptions: function() {
            this.axios.get('/web/contact-center/marital-status-options')
            .then(response => {
                if(this.checkApiResponse(response)){
                    this.maritalStatusOptionsCMS = response.data.data.map(item => ({
                        value: item.id,
                        text: item.name
                    }));
                }
            })
            .catch(error => {
                console.error('Error fetching street types:', error);
            });
        },
        getSalesJourneyStatus: function() {
            this.axios({
                method: 'get',
                url: '/web/sales-journey/' + this.applicationId
            })
            .then(response => {
                this.isSalesJourney = response.data.data
            })
            .catch(error => {
                console.log(error)
            })
        },
        getData: function() {
            this.showPreLoader()

            this.axios({
                method: 'get',
                url: '/web/contact-center/' + this.applicationId + '/' + this.type
            })
            .then(response => {
                let data = response.data.data

                this.application = data.application || {}

                this.applicants = data.applicants || []
                this.applicants.forEach(applicant => {
                    applicant.homePhone = applicant.homePhone?.replace(/\D/g, '') || ''
                    applicant.homeMobile = applicant.homeMobile?.replace(/\D/g, '') || ''
                    // Format phone numbers in contacts
                    applicant.contacts?.forEach(contact => {
                        if (contact.type.includes('Phone') || contact.type.includes('Mobile')) {
                            contact.info = contact.info?.replace(/\D/g, '') || ''
                        }
                    })
                })
                this.mailings = data.mailings || []
                this.corporations = data.corporations || []
                this.corporations.forEach(corporation => {
                    corporation.phone = corporation.phone?.replace(/\D/g, '') || ''
                    corporation.mobile = corporation.mobile?.replace(/\D/g, '') || ''
                })
                this.properties = data.properties || []
                // Set default direction to 'N/A' if not provided
                this.properties.forEach(property => {
                    if (!property.direction) {
                        property.direction = 'N/A';
                    }
                });
                this.titleHoldersObj = data.titleHolders || []

                this.vehicles = data.vehicles || []
                this.liabilities = data.liabilities || []

                this.presentEmployments = data.presentEmployments || []
                this.previousEmployments = data.previousEmployments || []
                this.otherIncomes = data.otherIncomes || []
                this.assets = data.assets || []
                this.activeMortgages = data.activeMortgages || []
                this.checks = data.checks || []
                this.purposeDetailOptions = data.purposeDetailOptions || []
                this.businessChannelOptions = data.businessChannelOptions || []

                this.properties.forEach(property => {
                    if (!property.valueMethod || property.valueMethod === '' || property.valueMethod === null || property.valueMethod === 'undefined') {
                        property.valueMethod = 'Appraisal';
                    }
                });

                this.totalApplicants = data.totalApplicants || 0
                this.restrictedValuationGroup = data.restrictedValuationGroup || false
                this.signersOptions = data.signersOptions || []
                this.agentOptions = data.agentOptions || []
                this.agentSequenceOptions = data.agentSequenceOptions || []
                //this.brokerOptions = data.brokerOptions || []
                this.sourceOptions = data.sourceOptions || []
                this.salesJourney = data.salesJourney || []
                this.signingAgentOptions = data.signingAgentOptions || []
                this.sequenceAgentList = data.sequenceAgentList || []
                this.urgencyOptions = data.urgencyOptions || []

                

                if (this.application.companyId === 701) {
                    const exists = this.tabs.some(tab => tab.name === 'disbursement')
                    if (!exists) {
                        this.tabs.splice(6, 0, { name: 'disbursement', label: 'Disbursement' })
                    }                
                    //this.tabs.splice(6, 0, { name: 'disbursement', label: 'Disbursement' });
                } else {
                    this.tabs = [
                        { name: 'view', label: 'Summary' },
                        { name: 'applicants', label: 'Applicants' },
                        { name: 'properties', label: 'Mailing / Properties' },
                        { name: 'vehicles', label: 'Vehicles / Liabilities' },
                        { name: 'income', label: 'Income' },
                        { name: 'quote', label: 'Quote' },
                        { name: 'administration', label: 'Administration' }
                    ]
                }
            })
            .catch(error => {
                this.alertMessage = error
                this.showAlert("error")
            })
            .finally(() => {
                this.hidePreLoader()
            })
        },
        propertyCheckList(property, key) {

            this.showChecklistAppr = false;
            this.keyTmp = key

            if(property.appraisalReceived === 'Yes') {
                this.propertyTmp = property
                this.refreshCount++
                this.showChecklistAppr = true;
                this.showModal('checklistAPC');
            } else {
                this.showChecklistAppr = false;
                //this.storeQuoteChanges(quote)
            }
        },
        checklistClosed: function() {
            this.showChecklistAppr = false;
            this.properties[this.keyTmp].appraisalReceived = 'No'
        },
        spouseTypeChanged: function(spouse) {
            if (spouse.type === 'Applicant' || spouse.type === 'Co-Applicant') {

            } else {
                spouse.signer = 0;
            }   
        },
        handleBrokerChange(newValue) {
            if (!newValue) return;

            const brokerId = parseInt(newValue.value, 10);
            this.application.brokerId = brokerId;
            if(this.selectedBroker.brokerOfficeId && this.selectedBroker.nationalBrokerId) {
                this.application.brokerOfficeId = this.selectedBroker.brokerOfficeId;
                this.application.nationalBrokerId = this.selectedBroker.nationalBrokerId;
            }
        },
        handleSourceChange(newValue, sourceType) {
            if (!newValue) return;

            const sourceId = parseInt(newValue.value, 10);
            if (sourceType === 'Source1') {
                this.application.source1 = sourceId.toString();
            } else if (sourceType === 'Source2') {
                this.application.source2 = sourceId.toString();
            }
        },
        handleLicenseCheckedChange(event) {
            if(event.target.checked) {
                this.application.licenseChecked = 'Yes';
                this.isLicenseChecked = true;
            } else {
                this.application.licenseChecked = 'No';
                this.isLicenseChecked = false;
            }
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