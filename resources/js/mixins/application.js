export const application = {
    data() {
        return {
            maritalStatusOptions: [
                { text: "", value: "" },
                { text: "Married", value: "Married" },
                { text: "Single", value: "Single" },
                { text: "Common Law", value: "Common Law" },
                { text: "Divorced", value: "Divorced" },
                { text: "Separated", value: "Separated" },
                { text: "Widowed", value: "Widowed" }
            ],
            provinceOptions: [
                { text: "", value: "" },
                { text: "Alberta", value: "AB" },
                { text: "British Columbia", value: "BC" },
                { text: "Manitoba", value: "MB" },
                { text: "New Brunswick", value: "NB" },
                { text: "Newfoundland and Labrador", value: "NL" },
                { text: "Northwest Territories", value: "NT" },
                { text: "Nova Scotia", value: "NS" },
                { text: "Nunavut", value: "NU" },
                { text: "Ontario", value: "ON" },
                { text: "Prince Edward Island", value: "PE" },
                { text: "Quebec", value: "QC" },
                { text: "Saskatchewan", value: "SK" },
                { text: "Yukon", value: "YT" }
            ],
            purposeOptions: [
                { text: "", value: "" },
                { text: "Debt Consolidation", value: "484" },
                { text: "Home Renovation", value: "485" },
                { text: "Property Purchase", value: "486" },
                { text: "Business Capital", value: "487" },
                { text: "Automobile", value: "488" },
                { text: "Investment", value: "489" },
                { text: "Vacation", value: "490" },
                { text: "Temporary Needs", value: "491" },
                { text: "Other", value: "Other" }
            ],
            employmentOptions: [
                { text: "", value: "" },
                { text: "Full-time", value: "F/T" },
                { text: "Part-time", value: "P/T" }
            ],
            propertyOptions: [
                { text: "", value : "" },
                { text: "Single Family Dwelling", value : "Single Family Dwelling" },
                { text: "Freehold Townhome", value : "Freehold Townhome" },
                { text: "Condo Townhome", value : "Condo Townhome" },
                { text: "Condo Highrise", value : "Condo Highrise" },
                { text: "Other", value : "Other" }
            ],
            unitTypeOptions: [
                { text: "N/A", value: "N/A" },
                { text: "Apt", value: "Apt" },
                { text: "Lease", value: "Lease" },
                { text: "M/H", value: "M/H" },
                { text: "T/H", value: "T/H"},
            ],
            ownRentOptions: [
                { text: "Own", value: "Own" },
                { text: "Rent", value: "Rent" }
            ],
            partOfSecOptions: [
                { text: "Yes", value: "Yes" },
                { text: "No", value: "No" }
            ],
            ruralUrbanOptions: [
                { text: "N/A", value: "N/A" },
                { text: "Rural", value: "Rural" },
                { text: "Urban", value: "Urban" },
            ],
            apprRecOptions: [
                { text: "Yes", value: "Yes" },
                { text: "No", value: "No" }
            ],
            valueMethodOptions: [
                { text: "Appraisal", value: "Appraisal" },
                { text: "Assessment", value: "Assessment" },
                { text: "Assetssment/Lookup", value: "Assessment/Lookup" },
                { text: "Landcor", value: "Landcor" }
            ],
            orderMethodOptions: [
                { text: "EM - Appr", value: "EM - Appr" },
                { text: "EM - COVID1", value: "EM - COVID1" },
                { text: "EM - Est", value: "EM - Est" },
                { text: "Phone", value: "Phone" }
            ],
            whoWillPayOptions: [
                { text: "Alpine", value: "Alpine" },
                { text: "Client", value: "Client" },
                { text: "Other", value: "Other" }
            ],
            propTypeOptions: [
                { text: "", value: "" },
                { text: "CMA Value", value: "467" },
                { text: "Foreclosure Appraisal Value", value: "468" },
                { text: "Listing Price", value: "469" },
                { text: "Sale Price", value: "470" }
            ],
            insuranceOptions: [
                { text: "Unknown", value: "unknown" },
                { text: "Yes", value: "yes" },
                { text: "No", value: "no" }
            ],
            insuranceAmtOptions: [
                { text: "0-100k", value: "1" },
                { text: "100k-200k", value: "2" },
                { text: "250k-500k", value: "3" },
                { text: "over 500k", value: "4" },
                { text: "Dont't Know", value: "0" },
            ],
            applicantTypeOptions: [
                { text: "Applicant", value: "Applicant" },
                { text: "Co-Applicant", value: "Co-Applicant" },
                { text: "Covenanter", value: "Covenanter" },
                { text: "Do not Contact", value: "Do not contact" },
                { text: "Executor of the Will", value: "Executor of the Will" },
                { text: "Guarantor", value: "Guarantor" },
                { text: "Not a Co-Applicant", value: "Not a co-applicant" },
                { text: "Power of Attorney", value: "Power of Attorney" },
                { text: "Transmital Letter", value: "Transmital_Letter" },
            ],
            basementOptions: [
                { text: "N/A", value: "N/A" },
                { text: "Crawl Space", value: "Crawl Space" },
                { text: "Full - finished", value: "Full - Finished" },
                { text: "Full - unfinished", value: "Full - Unfinished" },
                { text: "Other", value: "Other" },
                { text: "Partial", value: "Partial" },
            ],
            heatOptions: [
                { text: "", value: "" },
                { text: "Electric", value: "Electric" },
                { text: "Gas", value: "Gas" },
                { text: "Oil", value: "Oil" },
                { text: "Other", value: "Other" },
                { text: "Propane", value: "Propane" },
                { text: "Wood Stove", value: "Wood Stove" },
            ],
            roofingOptions: [
                { text: "", value: "" },
                { text: "Asphalt Shingle", value: "Asphalt Shingle" },
                { text: "Metal", value: "Metal" },
                { text: "Other", value: "Other" },
                { text: "Tar & Gravel", value: "Tar & Gravel" },
                { text: "Tile", value: "Tile" },
                { text: "Wood Shingle", value: "Wood Shingle" },
            ],
            exteriorOptions: [
                { text: "", value: "" },
                { text: "Aluminum Siding", value: "Aluminum Siding" },
                { text: "Brick", value: "Brick" },
                { text: "Other", value: "Other" },
                { text: "Stucco", value: "Stucco" },
                { text: "Vinyl Siding", value: "Vinyl Siding" },
                { text: "Wood Siding", value: "Wood Siding" },
            ],
            houseOptions: [
                { text: "", value: "" },
                { text: "2 Floor", value: "2 Floor" },
                { text: "Bungalo", value: "Bungalo" },
                { text: "Condo", value: "Condo" },
                { text: "Duplex", value: "Duplex" },
                { text: "Mobile", value: "Mobile" },
                { text: "Other", value: "Other" },
                { text: "Rancher", value: "Rancher" },
                { text: "Split Level", value: "Split level" },
                { text: "Townhouse", value: "Townhouse" },
            ],
            waterOptions: [
                { text: "", value: "" },
                { text: "Communal Wall", value: "Communal Wall" },
                { text: "Municipal Water", value: "Municipal Water" },
                { text: "Other", value: "Other" },
                { text: "Private Well", value: "Private Well" },
            ],
            sewageOptions: [
                { text: "", value: "" },
                { text: "Other", value: "Other" },
                { text: "Septic", value: "Septic" },
                { text: "Sewer", value: "Sewer" },
            ],
            payoutOptions: [
                { text: "By AAR", value: "By AAR" },
                { text: "By Alpine", value: "By Alpine" },
                { text: "By AOL", value: "By AOL" },
                { text: "By Client", value: "By Client" },
                { text: "By GET", value: "By GET" },
                { text: "No", value: "No" },
                { text: "Postponement", value: "Postponement" }
            ],
            coLenderRankOptions: [
                { text: "N/A", value: "N/A" },
                { text: "Before", value: "Before" },
                { text: "After", value: "After" },
                { text: "Pari Passu", value: "Pari Passu" }
            ],
            rentalOptions: [
                { text: "N/A", value: "N/A" },
                { text: "Other", value: "Other" },
                { text: "Rental income", value: "Rental income" },
                { text: "Rental suite", value: "Rental suite" },
                { text: "Room & board", value: "Room & board" }
            ],
            propertyTypes: [
                { text: "2nd Residence", value: "2nd Residence" },
                { text: "Bare Land", value: "Bare Land" },
                { text: "Commercial", value: "Commercial" },
                { text: "Construction", value: "Construction" },
                { text: "Rental", value: "Rental" },
                { text: "Residence", value: "Residence" },
            ],
        }
    }
}