<template>
    <ApplicantsInfo 
        :application="application"
        :applicants="applicants"
        :properties="properties"
        :isSalesJourney="isSalesJourney"
        :agentOptions="agentOptions"
        :salesJourney="salesJourney"
        :showSalesJourney="true"
    />
    <div class="card mb-2">
        <div class="card-body">
            <div class = "table-responsive">
                <table class="table">
                    <tbody>
                        <tr>
                            <td class="table-header">Amount Requested</td>
                            <td class="table-header">Purpose Type</td>
                            <td class="table-header">Purpose Detail</td>
                            <td class="table-header">Date of Application</td>
                            <td class="table-header">Company</td>
                            <td class="table-header">Urgency</td>                            
                            <td class="table-header" v-if="application.companyId === 701">BDM</td>
                            <td colspan="3" class="table-header" v-if="application.companyId === 701">Broker</td>
                        </tr>

                        <tr>
                            <td class="table-body">{{ formatDecimal(application.amountRequested) }}</td>
                            <td class="table-body">{{ application.categoryName }}</td>
                            <td class="table-body custon-wrap">{{ application.purposeDetail }}</td>
                            <td class="table-body">{{ convertDateFormat(application.date) }}</td>
                            <td class="table-body">{{ application.companyName }}</td>
                            <td class="table-body">{{ application.urgency || "" }}</td>
                            <td class="table-body" v-if="application.companyId === 701">{{ application.bdmName || "" }}</td>
                            <td colspan="3" class="table-body" v-if="application.companyId === 701">{{ application.brokerName || "" }}</td>
                        </tr>

                        <tr>
                            <td class="table-header">Source 1</td>
                            <td class="table-header">Source 2</td>
                            <td class="table-header">Initial Source</td>
                            <td class="table-header">Agent</td>
                            <td class="table-header">Signing Agent</td>
                            <td class="table-header">ILA</td>
                            <td class="table-header" v-if="application.companyId === 701">Underwriting Assistant</td>
                            <td colspan="3" class="table-header" v-if="application.companyId === 701">Broker Office</td>
                        </tr>

                        <tr>
                            <td class="table-body">{{ application.sourceName }}</td>
                            <td class="table-body">{{ application.source2Name }}</td>
                            <td class="table-body">{{ application.initialSourceName }}</td>
                            <td class="table-body">{{ application.agentName }}</td>
                            <td class="table-body">{{ application.signingAgentName }}</td>
                            <td class="table-body">
                                <button type="button" class="btn btn-link p-0" @click="ilaDetails(application.ila)">
                                    {{ application.ilaName }}
                                </button>
                            </td>
                            <td class="table-body" v-if="application.companyId === 701">{{ application.uwAsstName || "" }}</td>
                            <td colspan="3" class="table-body" style="max-width: 410px;white-space: normal;" v-if="application.companyId === 701" >{{ application.brokerOfficeName || "" }}</td>
                        </tr>

                        <tr>
                            <td class="table-header"></td>
                            <td class="table-header"></td>
                            <td class="table-header"></td>
                            <td class="table-header"></td>
                            <td class="table-header"></td>
                            <td class="table-header"></td>
                            <td class="table-header" v-if="application.companyId === 701">Business Channel</td>
                            <td class="table-header" v-if="application.companyId === 701">National Broker</td>
                            <td class="table-header" v-if="application.companyId === 701">CPS</td>
                            <td class="table-header" v-if="application.companyId === 701">License Checked</td>
                        </tr>

                        <tr>
                            <td class="table-body"></td>
                            <td class="table-body"></td>
                            <td class="table-body"></td>
                            <td class="table-body"></td>
                            <td class="table-body"></td>
                            <td class="table-body"></td>
                            <td class="table-body" v-if="application.companyId === 701">{{ application.businessChannel || "" }}</td>
                            <td class="table-body" v-if="application.companyId === 701">{{ application.nationalBrokerName || "" }}</td>
                            <td class="table-body" v-if="application.companyId === 701">{{ application.cps || "" }}</td>
                            <td class="table-body" v-if="application.companyId === 701">{{ application.licenseChecked || "No" }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    
    <div class="card mb-2" v-if="quotes.length > 0">
        <div class="card-body">
            <h5><span class="badge bg-info"><i class="bi bi-currency-dollar me-1"></i> Quote</span></h5>

            <div class="table-container text-center mb-0">
                <table class="table">
                    <thead>
                        <tr>
                            <th class="table-header"></th>
                            <th class="table-header">Gross Amount</th>
                            <th class="table-header">Net Amount</th>
                            <th class="table-header">Interest Rate</th>
                            <th class="table-header">2nd Year</th>
                            <th class="table-header">Monthly Payment</th>
                            <th class="table-header">Amort.</th>
                            <th class="table-header" v-for="(position, key) in quotes[0].positions" :key="key">
                                #{{ key + 1 }} Pos
                            </th>
                            <th class="table-header">LTV</th>
                            <th class="table-header">R2B</th>
                            <th class="table-header">Lender</th>
                            <th class="table-header">FM</th>
                            <th class="table-header">Other Fee</th>
                            <th class="table-header">Appr. LTV</th>
                            <th class="table-header">Notes</th>
                        </tr>
                    </thead>

                    <tbody>
                        <template v-for="(quote, key) in quotes" :key="key">
                            <tr v-if="quote.enableQuote == 'Yes'">
                                <td class="table-body p-0">
                                    <a @click="copyQuote(quote, this.application.companyId)" href="#" v-tooltip="{ content: 'Copy Quote', html: false }">
                                        <i class="bi bi-copy"></i>
                                    </a>
                                </td>
                                <td class="table-body">{{ formatDecimal(quote.grossAmount) }}</td>
                                <td class="table-body">{{ formatDecimal(quote.netAmount) }}</td>
                                <td class="table-body">{{ formatDecimal(quote.interestRate) }}%</td>
                                <td class="table-body">
                                    <span v-if="quote.loanTerm > 12">{{ formatDecimal(quote.secondYear) }}%</span>
                                </td>
                                <td class="table-body">{{ formatDecimal(quote.monthlyPayment) }}</td>
                                <td class="table-body">{{ formatDecimal(quote.amortization) }}</td>
                                <td class="table-body" v-for="(position, key) in quote.positions" :key="key">
                                    {{ position.position }}
                                </td>
                                <td class="table-body">{{ formatDecimal(quote.ltv) }}%</td>
                                <td class="table-body">
                                    <button type="button" class="btn btn-link" @click="openInvestorTrackingModal(quote)">
                                        {{ quote.readyToBuy }}
                                    </button>
                                </td>
                                <td class="table-body">{{ quote.lender }}</td>
                                <td class="table-body">{{ quote.fm }}</td>
                                <td class="table-body">{{ formatDecimal(quote.otherFee) }}</td>
                                <td class="table-body">{{ formatDecimal(quote.apprLtv.ltv) }}%</td>
                                <td class="table-body quote-notes">{{ quote.quoteComments }}</td>
                            </tr>
                        </template>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="card mb-2"> 
        <div class="card-body">
            <h5><span class="badge bg-info"><i class="bi bi-person me-1"></i>Applicants</span></h5>

            <template v-for="(applicant, key) in applicants" :key="key">

                <hr v-if="key != 0" />

                <table class="table">
                    <thead>
                        <tr>
                            <th class="table-header">Name</th>
                            <th class="table-header">Gender</th>
                            <th class="table-header">Age</th>
                            <th class="table-header">DOB</th>
                            <th class="table-header">SIN</th>
                            <th class="table-header">Beacon</th>
                            <th class="table-header">Applicant Type</th>
                            <th class="table-header">Relation</th>
                            <th class="table-header">PEP or HIO</th>
                            <th class="table-header">PEP Description</th>
                            <th class="table-header">Additional Roles</th>
                            <th class="table-header">Signer</th>
                        </tr>
                    </thead>

                    <tbody>
                        <tr v-for="(spouse, k) in applicant.spouses" :key="k">
                            <td class="table-body">{{ spouse.firstName }} <span v-if="spouse.middleName">{{ spouse.middleName }}</span> {{ spouse.lastName }}  <span v-if="spouse.preferredName">({{ spouse.preferredName }})</span></td>
                            <td class="table-body">{{ spouse.gender }}</td>
                            <td class="table-body">{{ spouse.age }}</td>
                            <td class="table-body">{{ convertDateFormat(spouse.dateOfBirth) }}</td>
                            <td class="table-body">{{ spouse.sin }}</td>
                            <td class="table-body">{{ spouse.beaconScore }}</td>
                            <td class="table-body">{{ spouse.type }}</td>
                            <td class="table-body">{{ spouse.relation }}</td>
                            <td class="table-body">{{ spouse.isPep }}</td>
                            <td class="table-body">{{ spouse.pepDescription }}</td>
                            <td class="table-body">{{ getSignatureType(spouse.signatureType) }}</td>
                            <td class="table-body">{{ getSignerName(spouse.signer) }}</td>
                            
                        </tr>
                    </tbody>
                </table>

                <table class="table">
                    <thead>
                        <tr>
                            <th class="table-header">Phone</th>
                            <th class="table-header">Mobile</th>
                            <th class="table-header">Email</th>
                            <th class="table-header">Marital Status</th>
                            <th class="table-header">Years</th>
                            <th class="table-header">Children</th>
                            <th class="table-header">Ages</th>
                            <th class="table-header">Credit Bureau</th>
                        </tr>
                    </thead>

                    <tbody>
                        <tr>
                            <td class="table-body">
                            <PhoneInput v-model="applicant.homePhone" :readOnly="true" />
                        </td>
                        <td class="table-body">
                            <PhoneInput v-model="applicant.homeMobile" :readOnly="true" />
                        </td>
                            <td class="table-body">
                                <a :href="'mailto:' + applicant.email + '?Subject=#' + application.id  + ' ' + applicants[0]?.spouses[0]?.lastName + ', ' + applicants[0]?.spouses[0]?.firstName">{{ applicant.email }}</a>
                            </td>
                            <td class="table-body">{{ applicant.maritalStatus }}</td>
                            <td class="table-body">{{ applicant.yearsOfMaritalStatus }}</td>
                            <td class="table-body">{{ applicant.childrenCount }}</td>
                            <td class="table-body">{{ applicant.childrenAges }}</td>
                            <td class="table-body">{{ convertDateFormat(applicant.creditBureauRec) }}</td>
                        </tr>
                    </tbody>
                </table>

                <!-- Contacts -->
                <div class="contacts-container">
                    <div class="contact-item" v-for="(contact, index) in applicant.contacts" :key="index">
                        <span class="contact-type">{{ contact.type }}</span>
                        <template v-if="contact.type.includes('Email')">
                            <br><a :href="'mailto:' + contact.info + '?Subject=#' + application.id  + ' ' + applicants[0]?.spouses[0]?.lastName + ', ' + applicants[0]?.spouses[0]?.firstName">{{ contact.info }}</a>
                        </template>

                        <template v-else-if="contact.type.includes('Cellular') || contact.type.includes('Pager')">
                            <PhoneInput v-model="contact.info" :readOnly="true" />
                        </template>

                        <template v-else>
                            <br>{{ contact.info }}
                        </template>
                    </div>
                </div>
            </template>
        </div>
    </div>

    <div v-if="corporations.length > 0" class="card mb-2"> 
        <div class="card-body">
            <h5><span class="badge bg-info"><i class="bi bi-buildings me-1"></i>Corporation</span></h5>
            <table class="table">
                <thead>
                    <tr>
                        <th class="table-header">Name</th>
                        <th class="table-header">Inc. Number</th>
                        <th class="table-header">Directors</th>
                        <th class="table-header">Phone</th>
                        <th class="table-header">Fax</th>
                        <th class="table-header">Email</th>
                    </tr>
                </thead>

                <tbody>
                    <tr v-for="(corp, k) in corporations" :key="k">
                        <td class="table-body">{{ corp.companyName }}</td>
                        <td class="table-body">{{ corp.incNumber }}</td>
                        <td class="table-body">{{ corp.directors }}</td>
                        <td class="table-body">
                            <PhoneInput v-model="corp.phone" :readOnly="true" />
                        </td>
                        <td class="table-body">
                            <PhoneInput v-model="corp.mobile" :readOnly="true" />
                        </td>
                        <td class="table-body">{{ corp.email }}</td>
                    </tr>
                </tbody>
            </table>            
        </div>
    </div>

    <div v-if="mailings.length > 0" class="card mb-2"> 
        <div class="card-body">
            <h5><span class="badge bg-info"><i class="bi bi-envelope me-1"></i>Mailing / Previous Address</span></h5>

            <table class="table">
                <template v-for="(mailing, m) in mailings" :key="m">
                    <thead>
                        <tr>
                            <th class="table-header">Recipients</th>
                            <th class="table-header">Years at {{ mailing.type }} Address</th>
                        </tr>
                    </thead>

                    <tbody>
                        <tr>
                            <td class="table-body">{{ mailing.recipients }}</td>
                            <td class="table-body">{{ formatYears(mailing.howLong) }} Years</td>
                        </tr>
                    </tbody>
                </template>
            </table>            
        </div>
    </div>

    <div class="card mb-2">
        <div class="card-body">
            <template v-for="(property, key) in properties" :key="key">
                <hr v-if="key != 0">

                <div class="d-flex align-items-center mb-2">
                    <h5 class="mb-0 me-4">
                        <span class="badge bg-info"><i class="bi bi-house me-1"></i>Property #{{ (key + 1) }}</span>
                    </h5>

                    <a href="#" @click="openMap(property.fullAddress)" class="me-2">
                        <h5 class="mb-0">
                            <span class="badge bg-secondary"><i class="bi bi-map me-1"></i>{{ property.addressNoZip }}</span>
                        </h5>
                    </a>

                    <a href="#" v-if="property.postalCode !== ''" class="" @click="openNearbyMortgage(property)">
                        <h5 class="mb-0">
                            <span class="badge bg-light text-danger">
                                <i class="bi bi-geo-alt me-1"></i>{{ property.postalCode }}
                            </span>
                        </h5>
                    </a>
                </div>

                <table class="table">
                    <tbody>
                        <tr>
                            <td colspan="2" class="table-header">Title Holder(s)</td>
                            <td class="table-header">Type</td>
                            <td class="table-header">{{ application.companyId === 701 ? "Sequence’s Interest" : "Alpine's Interest" }}</td>
                            <td class="table-header">Own/Rent</td>
                            <td class="table-header">Same as Mailing</td>
                            <td class="table-header">Part of Security</td>
                        </tr>

                        <tr>
                            <td colspan="2" class="table-body" style="max-width: 280px; white-space: normal;">{{ property.titleHolders }}</td>
                            <td class="table-body">{{ property.type }}</td>
                            <td class="table-body">{{ property.alpineInterest }}</td>
                            <td class="table-body">{{ property.ownRent }}</td>
                            <td class="table-body">{{ property.sameAsMailing }}</td>
                            <td class="table-body">{{ property.partOfSecurity }}</td>
                        </tr>

                        <tr v-if="property.unitType === 'Apt' || property.unitType === 'M/H' || property.unitType === 'T/H'">
                            <td class="table-header">Strata Fee</td>
                            <td class="table-header">Arrears</td>
                            <td class="table-header">Company - Contact</td>
                            <td class="table-header">Phone</td>
                            <td class="table-header">Fax</td>
                            <td class="table-header">Email</td>
                            <td class="table-header">Other</td>
                            <td class="table-header"></td>
                            <td class="table-header"></td>
                        </tr>

                        <tr v-if="property.unitType === 'Apt' || property.unitType === 'M/H' || property.unitType === 'T/H'">
                            <td class="table-body">{{ formatDecimal(property.strata) }}</td>
                            <td class="table-body">{{ property.strataArrears }}</td>
                            <td class="table-body">{{ property.strataCompany }}</td>
                            <td class="table-body">
                                <PhoneInput v-model="property.strataPhone" :readOnly="true" />
                            </td>
                            <td class="table-body">{{ property.strataFax }}</td>
                            <td class="table-body">{{ property.strataEmail }}</td>
                            <td class="table-body comments-cell">{{ property.strataOther }}</td>
                            <td class="table-body"></td>
                            <td class="table-body"></td>
                        </tr>

                        <tr>
                            <td class="table-header">How Long?</td>
                            <td class="table-header">PID</td>
                            <td class="table-header">Property Taxes</td>
                            <td class="table-header">Arrears</td>
                            <td class="table-header">Ins. Broker</td>
                            <td class="table-header">Arrears</td>
                            <td class="table-header">Ins. Expiry</td>
                            <td class="table-header"></td>
                            <td class="table-header"></td>
                        </tr>

                        <tr>
                            <td class="table-body">{{ property.numberOfYears }}</td>
                            <td class="table-body">{{ property.pid }}</td>
                            <td class="table-body">{{ formatDecimal(property.taxes) }}</td>
                            <td class="table-body">{{ property.arrearsUtd }}</td>
                            <td class="table-body">
                                <button type="button" class="btn btn-link p-0" @click="insDetails(property?.insBrokerId)">
                                {{ property?.insBrokerName }}
                                </button>
                            </td>
                            <td class="table-body">{{ property.insArrears }}</td>
                            <td class="table-body">{{ convertDateFormat(property.insExpireDate) }}</td>
                            <td class="table-body"></td>
                            <td class="table-body"></td>
                        </tr>

                        <tr>
                            <td class="table-header">Cost Price</td>
                            <td class="table-header">Downpayment</td>
                            <td class="table-header">Cust. Value</td>
                            <td class="table-header">Ass. Value</td>
                            <td class="table-header">Appr. Value</td>
                            <td class="table-header">Appr. By</td>
                            <td class="table-header">Appr. Date</td>
                            <td class="table-header">Appr. Received</td>
                            <td class="table-header"></td>
                        </tr>

                        <tr>
                            <td class="table-body">{{ formatDecimal(property.costPrice) }}</td>
                            <td class="table-body">{{ formatDecimal(property.downpayment) }}</td>
                            <td class="table-body">{{ formatDecimal(property.customerValue) }}</td>
                            <td class="table-body">
                                {{ formatDecimal(property.assessedValue) }}<br>(L: {{ formatDecimal(property.landAssdValue) }} B: {{ formatDecimal(property.buildingAssdValue) }})
                            </td>
                            <td class="table-body">{{ formatDecimal(property.appraisedValue) }}</td>
                            <td class="table-body">
                                <button type="button" class="btn btn-link p-0" @click="apprDetails(property.appraisersFirmCode)">
                                {{ property.appraisalFirmName }}
                            </button>
                            </td>
                            <td class="table-body">{{ convertDateFormat(property.appraisalDateOrdered) }}</td>
                            <td class="table-body">{{ convertDateFormat(property.appraisalDateReceived) }}</td>
                            <td class="table-body"></td>
                        </tr>

                        <tr>
                            <td class="table-header">Built</td>
                            <td class="table-header">Lot Size</td>
                            <td class="table-header">Floor Area</td>
                            <td class="table-header">Basement</td>
                            <td class="table-header">Bedrooms</td>
                            <td class="table-header">Bathrooms</td>
                            <td class="table-header">Heat</td>
                            <td class="table-header">Roofing</td>
                            <td class="table-header"></td>
                        </tr>

                        <tr>
                            <td class="table-body">{{ property.yearBuilt }}</td>
                            <td class="table-body">{{ property.lotSize }}</td>
                            <td class="table-body">{{ property.floorArea }}</td>
                            <td class="table-body">{{ property.basement }}</td>
                            <td class="table-body">{{ property.bedrooms }}</td>
                            <td class="table-body">{{ property.bathrooms }}</td>
                            <td class="table-body">{{ property.heat }}</td>
                            <td class="table-body">{{ property.roofing }}</td>
                            <td class="table-body"></td>
                        </tr>

                        <tr>
                            <td class="table-header">Exterior Finish</td>
                            <td class="table-header">House Style</td>
                            <td class="table-header">Garage</td>
                            <td class="table-header">Out Building</td>
                            <td class="table-header">Water Source</td>
                            <td class="table-header">Sewage</td>
                            <td class="table-header">Other</td>
                            <td class="table-header"></td>
                            <td class="table-header"></td>
                        </tr>

                        <tr>
                            <td class="table-body">{{ property.exteriorFinish }}</td>
                            <td class="table-body">{{ property.houseStyle }}</td>
                            <td class="table-body">{{ property.garage }}</td>
                            <td class="table-body">{{ property.outBuilding }}</td>
                            <td class="table-body">{{ property.waterSource }}</td>
                            <td class="table-body">{{ property.sewage }}</td>
                            <td class="table-body comments-cell">{{ property.comments }}</td>
                            <td class="table-body"></td>
                            <td class="table-body"></td>
                        </tr>
                    </tbody>
                </table>

                <table v-if="property.propertyMortgages.length > 0" class="table mt-2">
                    <thead>
                        <tr>
                            <th class="table-header">#</th>
                            <th class="table-header">Balance</th>
                            <th class="table-header">Balance Date</th>
                            <th class="table-header">Payment</th>
                            <th class="table-header">Payment Type</th>
                            <th class="table-header">PIT</th>
                            <th class="table-header">Line of Credit</th>
                            <th class="table-header">Term</th>
                            <th class="table-header">Rate</th>
                            <th class="table-header">Lender</th>
                            <th class="table-header">Arrears</th>
                            <th class="table-header">To Be Paid Out</th>
                            <th class="table-header">Solicit at Term</th>
                            <!--<th class="table-header">Deferred Confirmed</th>
                            <th class="table-header">Deferred Amount</th>-->
                            <th class="table-header">Updated By</th>
                            <th class="table-header">Updated At</th>
                        </tr>
                    </thead>

                    <tbody>
                        <tr v-for="(propertyMortgage, k) in property.propertyMortgages" :key="k">
                            <td class="table-body">{{ k + 1 }}</td>
                            <td class="table-body">{{ formatDecimal(propertyMortgage.mortgageBalance) }}</td>
                            <td class="table-body">{{ convertDateFormat(propertyMortgage.balanceDate) }}</td>
                            <td class="table-body">{{ formatDecimal(propertyMortgage.payment) }}</td>
                            <td class="table-body">{{ propertyMortgage.paymentType }}</td>
                            <td class="table-body">{{ propertyMortgage.pit }}</td>
                            <td class="table-body">{{ propertyMortgage.lineOfCredit }}</td>
                            <td class="table-body">{{ convertDateFormat(propertyMortgage.term) }}</td>
                            <td class="table-body">{{ propertyMortgage.rate }}%</td>
                            <td class="table-body">{{ propertyMortgage.lenderName }}</td>
                            <td class="table-body">{{ propertyMortgage.arrearsUtd }}</td>
                            <td class="table-body">{{ propertyMortgage.toBePaidOut }}</td>
                            <td class="table-body">{{ propertyMortgage.solicitAtTerm }}</td>
                            <!--<td class="table-body">{{ propertyMortgage.deferredConfirmed }}</td>
                            <td class="table-body">{{ propertyMortgage.deferredAmount }}</td>-->
                            <td class="table-body">{{ propertyMortgage.updatedBy || propertyMortgage.createdBy }}</td>
                            <td class="table-body">{{ propertyMortgage.updatedAt }}</td>
                        </tr>
                    </tbody>
                </table>

                <table v-if="property.inHouseMortgages.length > 0" class="table">
                    <thead>
                        <tr>
                            <th class="table-header">Code</th>
                            <th class="table-header">Balance</th>
                            <th class="table-header">Payment</th>
                            <th class="table-header">Payment Type</th>
                            <th class="table-header">Term</th>
                            <th class="table-header">Rate</th>
                            <th class="table-header">Current Rate</th>
                            <th class="table-header">Lender</th>
                            <th class="table-header">To Be Paid Out</th>
                        </tr>
                    </thead>

                    <tbody>
                        <tr v-for="(mtg, m) in property.inHouseMortgages" :key="m">
                            <td class="table-body">{{ mtg.mortgageCode }}</td>
                            <td class="table-body">{{ formatDecimal(mtg.balance) }}</td>
                            <td class="table-body">{{ formatDecimal(mtg.payment) }}</td>
                            <td class="table-body">Monthly</td>
                            <td class="table-body">{{ mtg.term }}</td>
                            <td class="table-body">{{ formatDecimal(mtg.interestRate) }}</td>
                            <td class="table-body">{{ formatDecimal(mtg.currentInterestRate) }}</td>
                            <td class="table-body">{{ mtg.lenderName }}</td>
                            <td class="table-body">
                                <select v-model="mtg.payout" class="form-select" @change="updateToBePaidOut(mtg)">
                                    <option value="Yes">Yes</option>
                                    <option value="No">No</option>
                                </select>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </template>
        </div>
    </div>

    <div class="row mb-2">
        <div class="col-6" v-if="vehicles.length > 0">
            <div class="card">
                <div class="card-body">
                    <h5><span class="badge bg-info"><i class="bi bi-car-front me-1"></i>Vehicles</span></h5>

                    <table class="table">
                        <thead>
                            <tr>
                                <th class="table-header">Vehicle</th>
                                <th class="table-header">Expiry</th>
                            </tr>
                        </thead>

                        <tbody>
                            <tr v-for="(vehicle, key) in vehicles" :key="key">
                                <td class="table-body">{{ vehicle.model }}</td>
                                <td class="table-body">{{ vehicle.expiry }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="col-6" v-if="assets.length > 0">
            <div class="card mb-2">
                <div class="card-body">
                    <h5><span class="badge bg-info"><i class="bi bi-cash-coin me-1"></i>Assets</span></h5>
                    <table class="table">
                        <thead>
                            <tr>
                                <th class="table-header">Asset</th>
                                <th class="table-header">Value</th>
                            </tr>
                        </thead>

                        <tbody>
                            <tr v-for="(asset, key) in assets" :key="key">
                                <td class="table-body">{{ asset.description }}</td>
                                <td class="table-body">{{ formatDecimal(asset.amount) }}</td>
                            </tr>
                        </tbody>

                        <tbody v-if="assets.length > 0">
                            <tr>
                                <td class="table-body fw-bold">Total: {{ assets.length }}</td>
                                <td class="table-body fw-bold">
                                    {{ formatDecimal(assets.reduce((sum, asset) => sum + (asset.amount || 0), 0)) }}
                                </td>
                            </tr>
                        </tbody>

                    </table>
                </div>
            </div>
        </div>
    </div>

    <div class="card mb-2">
        <div class="card-body">
            <h5><span class="badge bg-info"><i class="bi bi-credit-card me-1"></i>Liabilities</span></h5>

            <table class="table">
                <thead>
                    <tr>
                        <th class="table-header">Lender / Security Type</th>
                        <th class="table-header">To Be Paid Out</th>
                        <th class="table-header">Balance</th>
                        <th class="table-header">Payment</th>
                    </tr>
                </thead>

                <tbody>
                    <tr v-for="(liability, key) in liabilities" :key="key">
                        <td class="table-body">{{ liability.lender }}</td>
                        <td class="table-body">{{ liability.toBePaidOut }}</td>
                        <td class="table-body">{{ formatDecimal(liability.balanceOwed) }}</td>
                        <td class="table-body">{{ formatDecimal(liability.monthlyPayment) }}</td>
                    </tr>
                </tbody>

                <tbody v-if="liabilities.length > 0">
                    <tr>
                        <td class="table-body"></td>
                        <td class="table-body fw-bold">Total: {{ liabilities.length }}</td>
                        <td class="table-body fw-bold">
                            {{ formatDecimal(liabilities.reduce((sum, liability) => sum + (liability.balanceOwed || 0), 0)) }}
                        </td>
                        <td class="table-body fw-bold">
                            {{ formatDecimal(liabilities.reduce((sum, liability) => sum + (liability.monthlyPayment || 0), 0)) }}
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>

    <div class="card mb-2">
        <div class="card-body">
            <h5><span class="badge bg-info"><i class="bi bi-buildings me-1"></i>Present Employment</span></h5>

            <table class="table">
                <thead>
                    <tr>
                        <th class="table-header">Applicant</th>
                        <th class="table-header">Employer</th>
                        <th class="table-header">Position</th>
                        <th class="table-header">Years</th>
                        <th class="table-header">Phone</th>
                        <th class="table-header">Rate Hourly</th>
                        <th class="table-header">Income</th>
                        <th class="table-header">Type</th>
                    </tr>
                </thead>

                <tbody>
                    <tr v-for="(pe, key) in presentEmployments" :key="key">
                        <td class="table-body">{{ pe.spouseName }}</td>
                        <td class="table-body">{{ pe.employerName }}</td>
                        <td class="table-body">{{ pe.position }}</td>
                        <td class="table-body">{{ pe.years }}</td>
                        <td class="table-body"><PhoneInput :value="pe.phone" :readOnly="true" /></td>
                        <td class="table-body">{{ formatDecimal(pe.incomeHourly) }}</td>
                        <td class="table-body">{{ formatDecimal(pe.incomeMonthly) }}</td>
                        <td class="table-body">{{ pe.status }}</td>
                    </tr>
                </tbody>
            </table>

            <h5><span class="badge bg-info"><i class="bi bi-building me-1"></i>Previous Employment</span></h5>
            <table class="table">
                <thead>
                    <tr>
                        <th class="table-header">Applicant</th>
                        <th class="table-header">Employer</th>
                        <th class="table-header">Position</th>
                        <th class="table-header">Years</th>
                    </tr>
                </thead>

                <tbody>
                    <tr v-for="(pe, key) in previousEmployments" :key="key">
                        <td class="table-body">{{ pe.spouseName }}</td>
                        <td class="table-body">{{ pe.employerName }}</td>
                        <td class="table-body">{{ pe.position }}</td>
                        <td class="table-body">{{ pe.years }}</td>
                    </tr>
                </tbody>
            </table>

            <h5><span class="badge bg-info"><i class="bi bi-building me-1"></i>Rental Income</span></h5>
            <table class="table">
                <thead>
                    <tr>
                        <th class="table-header">Property</th>
                        <th class="table-header">Monthly Income</th>
                    </tr>
                </thead>

                <tbody>
                    <tr v-for="(rental, index) in properties.flatMap(property => property.propertyRentals)" :key="index">
                        <td class="table-body">{{ rental.property}}</td>
                        <td class="table-body">{{ formatDecimal(rental.monthlyIncome) }}</td>
                    </tr>
                </tbody>

                <tbody v-if="properties.flatMap(property => property.propertyRentals).length > 0">
                    <tr>
                        <td class="table-body fw-bold">
                            Total: {{ properties.flatMap(property => property.propertyRentals).length }}
                        </td>
                        <td class="table-body fw-bold">
                            {{ formatDecimal(properties.flatMap(property => property.propertyRentals)
                                .reduce((sum, rental) => sum + (rental.monthlyIncome || 0), 0)) }}
                        </td>
                    </tr>
                </tbody>
            </table>  
            <h5><span class="badge bg-info"><i class="bi bi-building me-1"></i>Other Source of Income</span></h5>
            <table class="table">
                <thead>
                    <tr>
                        <th class="table-header">Source</th>
                        <th class="table-header">Monthly Income</th>
                    </tr>
                </thead>

                <tbody>
                    <tr v-for="(other, index) in otherIncomes" :key="index">
                        <td class="table-body">{{ other.source }}</td>
                        <td class="table-body">{{ formatDecimal(other.monthly) }}</td>
                    </tr>
                </tbody>
                </table>          
            <div class="card-body mt-2">
                <div><h6>Total Income: {{ formatDecimal(this.totalIncome) }}</h6></div>
            </div>
        </div>
    </div>

    <div class="modal" id="ilaModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content min-width-600">
                <div class="modal-header">
                    <h5 class="modal-title">ILA</h5>
                    <button type="button" class="btn-close" data-coreui-dismiss="modal" aria-label="Close"></button>
                </div>

                <div class="modal-body">
                    <table class="table" style="width: 100% !important;">
                        <tbody>
                            <tr>
                                <td><b>Code</b></td>
                                <td>{{ ila.id }}</td>
                            </tr>
                            <tr>
                                <td><b>Name</b></td>
                                <td>{{ ila.firmName }}</td>
                            </tr>
                            <tr>
                                <td><b>Phone</b></td>
                                <td>{{ ila.telephone }}</td>
                            </tr>
                            <tr>
                                <td><b>Email</b></td>
                                <td><a :href="'mailto:' + ila.email">{{ ila.email }}</a></td>
                            </tr>
                            <tr>
                                <td><b>Mailling Address</b></td>
                                <td>{{ ila.address }}</td>
                            </tr>
                            <tr>
                                <td><b>Comments</b></td>
                                <td class="comments">{{ ila.comments }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <div class="modal" id="apprModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content min-width-600">
                <div class="modal-header">
                    <h5 class="modal-title">Appraisal Details</h5>
                    <button type="button" class="btn-close" data-coreui-dismiss="modal" aria-label="Close"></button>
                </div>

                <div class="modal-body">
                    <table class="table" style="width: 100% !important;">
                        <tbody>
                            <tr>
                                <td><b>Code</b></td>
                                <td>{{ apprData.appraisalFirmCode }}</td>
                            </tr>
                            <tr>
                                <td><b>Name</b></td>
                                <td>{{ apprData.name }}</td>
                            </tr>
                            <tr>
                                <td><b>Phone</b></td>
                                <td>{{ apprData.telephone }}</td>
                            </tr>
                            <tr>
                                <td><b>Email</b></td>
                                <td><a :href="'mailto:' + apprData.email">{{ apprData.email }}</a></td>
                            </tr>
                            <tr>
                                <td><b>Mailling Address</b></td>
                                <td>{{ apprData.address }}</td>
                            </tr>
                            <tr>
                                <td><b>Comments</b></td>
                                <td class="comments">{{ apprData.comments }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <div class="modal" id="insModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content min-width-600">
                <div class="modal-header">
                    <h5 class="modal-title">Insurance Broker Details</h5>
                    <button type="button" class="btn-close" data-coreui-dismiss="modal" aria-label="Close"></button>
                </div>

                <div class="modal-body">
                    <table class="table" style="width: 100% !important;">
                        <tbody>
                            <tr>
                                <td><b>Code</b></td>
                                <td>{{ insBrokerData.insuranceCode }}</td>
                            </tr>
                            <tr>
                                <td><b>Name</b></td>
                                <td>{{insBrokerData.firmName}}</td>
                            </tr>
                            <tr>
                                <td><b>Phone</b></td>
                                <td>{{ insBrokerData.phone }}</td>
                            </tr>
                            <tr>
                                <td><b>Email</b></td>
                                <td><a :href="'mailto:' + insBrokerData.email">{{ insBrokerData.email }}</a></td>
                            </tr>
                            <tr>
                                <td><b>Mailling Address</b></td>
                                <td>{{ insBrokerData.brokerAddress }}</td>
                            </tr>
                            <tr>
                                <td><b>Comments</b></td>
                                <td class="comments">{{ insBrokerData.comments }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>


    <investor-tracking-modal
        :selectedQuote="selectedQuote"
        :applicationId="application.id"
        :refreshCount="refreshCount">
    </investor-tracking-modal>

</template>

<script>
import { util } from '../mixins/util'
import { quote } from '../mixins/quote'
import PhoneInput from './PhoneInput.vue'
import NearbyMortgages from './NearbyMortgages.vue'
import InvestorTrackingModal from '../components/modals/InvestorTrackingModal.vue'
import ApplicantsInfo from '../components/ApplicantsInfo.vue'

export default {
    mixins: [util, quote],
    components: { 
        NearbyMortgages,
        InvestorTrackingModal,
        ApplicantsInfo,
        PhoneInput
    },
    props: ['application','applicants','corporations','properties','mailings','vehicles','assets','liabilities','presentEmployments','previousEmployments','quotes','activeMortgages', 'totalIncome','otherIncomes','isSalesJourney','agentOptions','salesJourney'],
    data() {
        return {
            ila: {},
            selectedProperty: [],
            selectedQuote: [],
            refreshCount: 0,
            liabilityTotalBalance: 0,
            liabilityTotalPayment: 0,
            apprData: {},
            insBrokerData: {},
        }
    },
    methods: {
        openNewApp() {
            if (this.application.newApplicationId) {
                window.location.href = `https://amurfinancial.lightning.force.com/lightning/r/Opportunity/${this.application.salesforceIdNew}/view`;
            }  
        },
        openOldApp() {
            if (this.application.oldApplicationId) {
                window.location.href = `https://amurfinancial.lightning.force.com/lightning/r/Opportunity/${this.application.salesforceIdOld}/view`;
            }  
        },
        generateCopyText(quote) {
            // Table headers
            let headers = "Gross Amount\tNet Amount\tInterest Rate\t2nd Year\tMonthly Payment\tAmort.\t";
            headers += quote.positions.map((_, key) => `#${key + 1} Pos`).join("\t") + "\tLTV\tR2B\tLender\tFM";

            // Quote values
            let values = [
                this.formatDecimal(quote.grossAmount),
                this.formatDecimal(quote.netAmount),
                this.formatDecimal(quote.interestRate) + "%",
                this.formatDecimal(quote.secondYear) + "%",
                this.formatDecimal(quote.monthlyPayment),
                this.formatDecimal(quote.amortization),
                ...quote.positions.map(pos => pos.position),
                this.formatDecimal(quote.ltv) + "%",
                quote.readyToBuy,
                quote.lender,
                quote.fm
            ].join("\t");

            return `${headers}\n${values}`;
        },
        openNearbyMortgage(property) {
            const url = `${window.location.origin}/nearby-mortgages?opportunityId=${this.application.id}&propertyId=${property.id}&postalCode=${property.postalCode}&city=${property.city}`;

            const popupWidth = screen.availWidth;
            const popupHeight = Math.round(screen.availHeight * 0.8);
            const left = 0;
            const top = Math.round((screen.availHeight - popupHeight) / 2);
            const setup = `width=${popupWidth},height=${popupHeight},left=${left},top=${top},resizable=yes,scrollbars=yes,toolbar=no,menubar=no,location=no,status=no`;
            window.open(url, 'popupWindow', setup);
        },
        openInvestorTrackingModal(quote) {
            this.selectedQuote = quote
            this.refreshCount++
            this.showModal('investorTrackingModal')
        },
        openMap: function(address) {
            window.open('https://www.google.com/maps/search/?api=1&query=' + address, '_blank');
        },
        ilaDetails(ilaCode) {
            this.showPreLoader()

            this.axios.get('/web/contact-center/ila/' + ilaCode)
            .then(response => {
                this.ila = response.data.data
                this.showModal('ilaModal')
            })
            .catch(error => {
                
            })
            .finally(() => {
                this.hidePreLoader()
            })
        },
        apprDetails(apprCode) {
            console.log(apprCode)
            this.showPreLoader()

            this.axios.get('/web/contact-center/appraisal/' + apprCode)
            .then(response => {
                this.apprData = response.data.data
                console.log('appr data is',this.apprData);
                this.showModal('apprModal')
            })
            .catch(error => {
                console.error(error)
            })
            .finally(() => {
                this.hidePreLoader()
            })
        },
        insDetails(insCode) {
            this.showPreLoader()

            this.axios.get('/web/contact-center/insurance/' + insCode)
            .then(response => {
                this.insBrokerData = response.data.data
                this.showModal('insModal')
            })
            .catch(error => {
                console.error(error)
            })
            .finally(() => {
                this.hidePreLoader()
            })
        },
        getInsuranceAmount(value) {
            switch (value) {
                case "1":
                    return "0-100k";
                case "2":
                    return "100k-250k";
                case "3":
                    return "250k-500k";
                case "4":
                    return "over 500k";
                case "0":
                    return "Do not know";
            }

        },
        formatMailingAddress(mailing) {
            if (mailing.other === null || mailing.other === "") {
                let fullAddress = '';
                if (mailing.unitNumber !== '') {
                    fullAddress += mailing.unitNumber + '-';
                }
                fullAddress += mailing.streetNumber + ' ' + mailing.streetName + ' ' + mailing.streetType;
                fullAddress += ' PO Box ' + mailing.poNumber
                fullAddress += ' STN ' + mailing.station
                fullAddress += ' SITE ' + mailing.site
                fullAddress += ' COMPARTMENT ' + mailing.compartment
                fullAddress += ' RR ' + mailing.rrNumber
                fullAddress += ' ' + mailing.city + ' ' + mailing.province + ' ' + mailing.postalCode;
                return fullAddress;                
            }
            return mailing.other;
        },

        updateToBePaidOut(mortgage) {
            const mortgageId = mortgage.mortgage_id || mortgage.id;
            
            this.axios({
                method: 'post',
                url: '/web/contact-center/to-be-paid-out',
                data: {
                    mortgageId: mortgageId,
                    toBePaidOut: mortgage.payout
                }
            }).then(response => {
                if(this.checkApiResponse(response)){
                    this.$emit('refresh');
                }
            }).catch(error => {
                console.error(error)
            })
        },
        formatYears(value) {
            if (!value) return '0';
            return value;
        },
        getSignerName(signerId) {

            if (!signerId || !this.applicants || !this.applicants.length) {
                return '';
            }

            for (const applicant of this.applicants) {
                if (applicant.spouses && applicant.spouses.length) {
                    const signerSpouse = applicant.spouses.find(s => s.id === signerId);
                    if (signerSpouse) {
                        return `${signerSpouse.firstName} ${signerSpouse.middleName || ''} ${signerSpouse.lastName}`.replace(/\s+/g, ' ').trim();
                    }
                }
            }

            return '';            
        },
        getSignatureType(signatureTypes) {
            if (!Array.isArray(signatureTypes)) {                
                return '';
            }

            return signatureTypes.map(s => s.text).join(', ');
        }
    }
}
</script>

<style scoped>

table, tr, th, td {
    width: auto !important;
    padding-right: 1em;
    white-space: nowrap;
}

.table-header {
    padding-right: 1rem; 
    padding-bottom: 0;
    border: none; 
    font-size: 0.875em;
    font-weight: bold;
    
}

.table-body {
    vertical-align: middle;
    padding-right: 1rem;
    padding-top: 0;
    border: none;
    font-size: 0.875em;
    font-weight: normal;
}

.table-container {
    width: 100%;
    overflow-x: auto;
}

.contacts-container {
    display: flex;
    flex-wrap: wrap;
    gap: 0.1rem;
}

.contact-item {
    box-sizing: border-box;
    padding-right: 1rem;
    font-size: 0.875em;
    font-weight: normal;
    border: none;
    margin-bottom: 0;
}

.contact-type {
  font-weight: bold;
}

h5{
    margin-bottom: 0.1rem;
}

.card {
    border-width: 1px; 
    border-color: #ccc; 
    border-radius: 0.35rem;
    padding: 0;
    box-shadow: none;
}

.card-body {
    padding: 0.2rem;
}

.table {
    margin-bottom: 1rem;
    border-collapse: collapse;
    padding: 0rem;
}

.clickable {
  cursor: pointer;
  text-decoration: none;
  white-space: nowrap;
}

.clickable:hover {
  text-decoration: underline;
}

.no-padding {
  padding: 3px !important;
}
.small-tooltip {
    font-size: 10px !important; /* Adjust size as needed */
    padding: 4px 8px; /* Adjust tooltip padding */
}

.quote-notes {
    white-space: normal !important;
    word-wrap: break-word;
    overflow-wrap: break-word;
    max-width: 100%;
    padding: 8px;
    text-align: left; /* Ensures text starts from the left */
}

/* Make sure "Other Fee" and "Quote Notes" are properly aligned */
td {
    vertical-align: top !important; /* Aligns text correctly */
}

.custon-wrap {
    word-wrap: break-word;
    word-break: break-all;
    white-space: pre-line;
}

.comments-cell {
    max-width: 900px !important;
    white-space: normal !important;
    word-wrap: break-word !important;
    overflow-wrap: break-word !important;
    padding: 8px !important;
    text-align: left !important;
    vertical-align: top !important;
}

.comments{
    white-space: break-spaces;
}
.modal-content.min-width-600{
    min-width: 600px;
}

</style>
