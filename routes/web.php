<?php

use App\Http\Controllers\ApplicantController;
use App\Http\Controllers\ApplicationController;
use App\Http\Controllers\ApplicationDashboardController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\LoginController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\ContactCenterAppController;
use App\Http\Controllers\LenderFirmController;
use App\Http\Controllers\PapController;
use App\Http\Controllers\BankInstitutionController;
use App\Http\Controllers\PayoutController;
use App\Http\Controllers\RenewalApprovalController;
use App\Http\Controllers\FinanceController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\CmsController;
use App\Http\Controllers\TrackerController;
use App\Http\Controllers\MergeController;
use App\Http\Controllers\DMSController;
use App\Http\Controllers\InitializationController;
use App\Http\Controllers\TestController;
use App\Http\Controllers\MailingController;
use App\Http\Controllers\PropertyController;
use App\Http\Controllers\BrokerFirmController;
use App\Http\Controllers\AppraisalFirmController;
use App\Http\Controllers\ILAController;
use App\Http\Controllers\MortgageController;
use App\Http\Controllers\PricebookController;
use App\Http\Controllers\QuoteController;
use App\Http\Controllers\ChecklistController;
use App\Http\Controllers\NoteController;
use App\Http\Controllers\SalesforceController;
use App\Http\Controllers\AppraisalController;
use App\Http\Controllers\SourceController;

Route::group(['middleware' => 'noAuthentication'], function () {

    Route::get('login', function () {
        return view('login');
    });

    //Login
    Route::post('api/login', [LoginController::class, 'login']);

    //Federation login
    Route::get('t/{token}', [LoginController::class, 'federationLogin']);

    //Reorg Applicants
    Route::get('reorg-applicants/{opportunityId}/{userId}', function () {
        return view('app');
    });
    Route::get('web/applicants/reorg', [ApplicantController::class, 'getApplicantsSF']);
    Route::put('web/applicants/reorg/{opportunityId}/{userId}', [ApplicantController::class, 'updateApplicantsSF']);

    //Reorg Applicants
    Route::get('mailings/{opportunityId}', function () {
        return view('app');
    });
    Route::get('web/mailings', [MailingController::class, 'getMailingsSF']);
    Route::post('web/mailings', [MailingController::class, 'storeMailingsSF']);
    Route::delete('web/mailings/{id}', [MailingController::class, 'destroyMailingsSF']);
    Route::get('web/mailings/title-holders', [MailingController::class, 'getTitleHoldersSF']);

    Route::get('/token', function () {
        return csrf_token();
    });
});

Route::group(['middleware' => 'ssoAuthentication'], function () {
    //Contact Center App
    Route::get('contact-center/{id}/{type}', function () {
        return view('app');
    });
    Route::get('contact-center-app/{id}', function () {
        return view('app');
    });

    //Application Dashboard
    Route::get('application-dashboard/{id}', function () {
        return view('app');
    });

    //Initialization
    Route::get('initialization/{id}/{id2}', function () {
        return view('app');
    });

    //Merge
    Route::get('merge/{id}', function () {
        return view('app');
    });

    //My Apps
    Route::get('my-apps', function () {
        return view('app');
    });

    //test
    Route::get('web/check-session2', [LoginController::class, 'checkSession']);

    //Notes
    Route::get('edit-notes/{opportunityId}/{noteId}', function () {
        return view('app');
    });

    //Nearby Mortgages
    Route::get('nearby-mortgages', function () {
        return view('app');
    });
});

Route::group(['middleware' => 'webAuthentication'], function () {
    //Logout
    Route::get('web/logout', [LoginController::class, 'logout']);
    Route::get('web/check-session', [LoginController::class, 'checkSession']);

    //Users
    Route::get('web/current-user', [UserController::class, 'current']);
    Route::get('web/menus', [UserController::class, 'getMenus']);
    Route::get('/web/users/brokers', [UserController::class, 'getBrokers']);
    Route::get('/web/users/group-users', [UserController::class, 'getUsersByGroups']);
    Route::get('/web/users/current/groups', [UserController::class, 'getCurrentUserGroups']);
    Route::get('/web/users/external-brokers', [UserController::class, 'getExternalBrokers']);
    Route::get('/web/users/accounting', [UserController::class, 'getAccountingUsers']);
    Route::get('/web/users/funding', [UserController::class, 'getFundingUsers']);
    Route::get('/web/users/support', [UserController::class, 'getSupportUsers']);

    //Sources
    Route::get('/web/source/get-sources', [SourceController::class, 'getAutoCompleteSourceData']);

    Route::group(['middleware' => 'accessRight:pap'], function () {

        Route::post('web/pap/upload', [PapController::class, 'uploadFile']);
        Route::get('web/pap/payments/{companyId}', [PapController::class, 'getPayments']);
        Route::get('web/pap/summary/{companyId}', [PapController::class, 'getSummary']);

        Route::get('web/pap/updates', [PapController::class, 'getUpdates']);
        Route::get('web/pap/transactions', [PapController::class, 'getTransactions']);
        Route::get('web/pap/transactions/details/{id}', [PapController::class, 'getTransactionDetails']);
        Route::get('web/pap/transactions/process/{id}', [PapController::class, 'processTransactionWeb']);
        Route::put('web/pap/transactions/reject/{id}', [PapController::class, 'rejectTransaction']);
        Route::put('web/pap/transactions/payments', [PapController::class, 'updatePayments']);

        Route::put('web/pap/bank-info/validate/{id}', [PapController::class, 'validadeBankInfo']);
        Route::put('web/pap/bank-info/{id}', [PapController::class, 'updateBankInfo']);
        Route::put('web/pap/bank-info/reject/{id}', [PapController::class, 'rejectBankInfo']);

        Route::get('web/pap/files/request/{companyId}', [PapController::class, 'requestFile']);
        Route::get('web/pap/files/download/{papFileId}', [PapController::class, 'downloadFile']);
        Route::get('web/pap/files/quickbooks/{papFileId}', [PapController::class, 'quickbooks']);
        Route::post('web/pap/files', [PapController::class, 'getFiles']);
        Route::get('web/pap/files/netsuite/{papFileId}', [PapController::class, 'netsuite']);
    });

    //Salesforce
    Route::get('/web/salesforce/oppurtunity-cap', [SalesforceController::class, 'getOppurtunityCAP']);
    Route::post('/web/salesforce/oppurtunity-cap', [SalesforceController::class, 'storeOpportunityCAPUser']);
    Route::delete('/web/salesforce/oppurtunity-cap/{id}', [SalesforceController::class, 'deleteOpportunityCAP']);

    //Reports
    //Route::get('web/reports/bayview/commitment', [ReportController::class, 'bayviewCommitment']);
    //Route::get('web/reports/bayview/commitment/download', [ReportController::class, 'bayviewCommitmentDownload']);
    //Route::get('web/reports/bayview/acquisition', [ReportController::class, 'bayviewAcquisition']);
    //Route::get('web/reports/bayview/acquisition/download', [ReportController::class, 'bayviewAcquisitionDownload']);
    //Route::get('web/reports/bayview/trial-balance', [ReportController::class, 'bayviewTrialBalance']);
    //Route::get('web/reports/bayview/trial-balance/download', [ReportController::class, 'bayviewTrialBalanceDownload']);
    //Route::get('web/reports/bayview/remittance', [ReportController::class, 'bayviewRemittance']);
    //Route::get('web/reports/bayview/remittance/download', [ReportController::class, 'bayviewRemittanceDownload']);
    Route::get('web/reports', [ReportController::class, 'index']);
    Route::post('web/reports/favourites/{reportId}', [ReportController::class, 'saveReportFavourites']);
    Route::get('web/reports/mic-pipeline', [ReportController::class, 'micPipeline']);
    Route::get('web/reports/commercial-loans-tracker', [ReportController::class, 'commercialLoansTracker']);
    Route::get('web/reports/initial-docs', [ReportController::class, 'initialDocsReport']);
    Route::get('web/reports/mic-forecast', [ReportController::class, 'micForecast']);
    Route::get('web/reports/origination-pipeline', [ReportController::class, 'originationPipeline']);
    Route::get('web/reports/broker-dashboard', [ReportController::class, 'brokerDashboard']);
    Route::get('web/reports/pipeline-forecast', [ReportController::class, 'pipelineForecast']);
    Route::get('web/reports/broker-details', [ReportController::class, 'brokerDetails']);
    Route::get('web/reports/pb-breakdown', [ReportController::class, 'pbBreakdown']);
    Route::get('web/reports/nb-breakdown', [ReportController::class, 'nbBreakdown']);
    Route::get('web/reports/lt-breakdown', [ReportController::class, 'ltBreakdown']);
    Route::get('web/reports/all-breakdown', [ReportController::class, 'allBreakdown']);
    Route::get('web/reports/stage-pipeline', [ReportController::class, 'stagePipeline']);
    Route::get('web/reports/stage-pipeline/counts', [ReportController::class, 'getStagePipelineCounts']);
    Route::get('web/reports/stage-pipeline/compare', [ReportController::class, 'compareStagePipelineDates']);

    //Bank Insitution
    Route::get('web/bank-institutions/code/{id}', [BankInstitutionController::class, 'showByCode']);

    //Payout
    Route::get('web/payouts', [PayoutController::class, 'getPayouts']);
    Route::put('web/payout-accept/{id}', [PayoutController::class, 'acceptPayout']);
    Route::get('web/payout-process', [PayoutController::class, 'getProcessPayout']);
    Route::post('web/payout-confirm', [PayoutController::class, 'processPayout']);
    Route::post('web/payout-cancel', [PayoutController::class, 'cancelPayout']);
    Route::get('web/cancel-payout', [PayoutController::class, 'getCancelPayout']);
    Route::post('web/calculate-payout', [PayoutController::class, 'calculatePayout']);
    Route::put('web/payout/reject/{id}', [PayoutController::class, 'rejectPayout']);
    Route::put('web/payout/send-lawyer/{id}', [PayoutController::class, 'sendLawyer']);

    //Renewal
    Route::get('web/renewals', [RenewalApprovalController::class, 'getRenewals']);
    Route::get('web/renewals/categories', [RenewalApprovalController::class, 'getCategories']);
    Route::get('web/renewals/approved', [RenewalApprovalController::class, 'getApprovedRenewals']);  
    Route::get('web/renewals/broker-requested', [RenewalApprovalController::class, 'getBrokerRequestedRenewals']); 
    Route::get('web/renewals/pending', [RenewalApprovalController::class, 'getPendingReviews']); 
    Route::get('web/renewals/in-progress', [RenewalApprovalController::class, 'getInProgressRenewals']); 
    Route::get('web/renewals/processed', [RenewalApprovalController::class, 'getProcessedRenewals']); 
    Route::get('web/renewals/count/new-renewals', [RenewalApprovalController::class, 'getRenewalsCount']); 
    Route::get('web/renewals/count/in-progress', [RenewalApprovalController::class, 'getInProgressRenewalsCount']);
    Route::get('web/renewals/count/pending', [RenewalApprovalController::class, 'getPendingReviewsCount']);
    Route::get('web/renewals/count/processed', [RenewalApprovalController::class, 'getProcessedRenewalsCount']);
    Route::get('web/renewals/calculate', [RenewalApprovalController::class, 'calculate']);
    Route::get('web/renewals/documents', [RenewalApprovalController::class, 'getDocuments']);
    Route::post('web/renewals', [RenewalApprovalController::class, 'insert']);
    Route::post('web/renewals/non-renewal', [RenewalApprovalController::class, 'nonRenewal']);
    Route::post('web/renewals/pending', [RenewalApprovalController::class, 'pending']);
    Route::post('web/renewals/excel', [RenewalApprovalController::class, 'generateExcelFile']);
    Route::post('web/renewals/document/send', [RenewalApprovalController::class, 'sendDocuments']);
    Route::post('web/renewals/document/recreate', [RenewalApprovalController::class, 'recreateDocuments']);
    Route::put('web/renewals', [RenewalApprovalController::class, 'update']);
    Route::put('web/renewals/documents/date', [RenewalApprovalController::class, 'setDocumentSentDate']);
    Route::get('web/renewals/documents/date', [RenewalApprovalController::class, 'getDocumentSentDate']);
    Route::put('web/renewals/assign-agents', [RenewalApprovalController::class, 'assignAgents']);
    Route::put('web/renewals/broker-request', [RenewalApprovalController::class, 'brokerRequest']);
    Route::put('web/renewals/broker-approval', [RenewalApprovalController::class, 'brokerApproval']);
    Route::post('web/renewals/filter-options', [RenewalApprovalController::class, 'insertFilterOption']);
    Route::get('web/renewals/filter-options', [RenewalApprovalController::class, 'getFilterOption']);

    //Foreclosure
    Route::get('web/foreclosures', [PayoutController::class, 'getForeclosures']);
    Route::get('web/foreclosure-process', [PayoutController::class, 'getProcessForeclosure']);
    Route::get('web/cancel-foreclosures', [PayoutController::class, 'getCancelForeclosures']);
    Route::post('web/foreclosure-cancel', [PayoutController::class, 'cancelForeclosure']);
    Route::post('web/foreclosure-reject', [PayoutController::class, 'foreclosureReject']);

    //Finance
    Route::post('web/finance/nsf-to-qb', [FinanceController::class, 'nsfToQb']);
    Route::post('web/finance/nsf-to-netsuite', [FinanceController::class, 'nsfToNetsuite']);

    //DMS
    Route::get('web/dms/templates', [DMSController::class, 'index']);
    Route::get('web/dms/templates/download/{id}', [DMSController::class, 'download']);
    Route::delete('web/dms/templates/{id}', [DMSController::class, 'destroy']);
    Route::post('web/dms/templates', [DMSController::class, 'store']);
    Route::get('web/dms/templates-approval', [DMSController::class, 'getTemplatesApproval']);
    Route::post('web/dms/template-approval', [DMSController::class, 'storeS3']);
    Route::get('web/dms/templates/download-approval/{id}', [DMSController::class, 'downloadApproval']);
    Route::post('web/dms/setup-template-approval', [DMSController::class, 'setupTemplateApproval']);

    //CMS  
    Route::group(['middleware' => 'accessRight:cmsagent'], function () {
        Route::post('web/cms/commission-approval', [CmsController::class, 'commissionApproval']);
        Route::post('web/cms/save-commission-approval', [CmsController::class, 'saveCommissionApproval']);
        Route::post('web/cms/dispute-agent', [CmsController::class, 'disputeAgent']);
    });

    Route::group(['middleware' => 'accessRight:cms'], function () {
        Route::post('web/cms/type', [CmsController::class, 'getType']);
        Route::get('web/cms/types', [CmsController::class, 'getTypes']);
        Route::get('web/cms/commission-setup', [CmsController::class, 'commissionSetup']);
        Route::post('web/cms/commission-setup-approval', [CmsController::class, 'cmsSetupApproval']);
        Route::post('web/cms/commission-setup-department-approval', [CmsController::class, 'commissionSetupDepartmentApproval']);
        Route::post('web/cms/commission-custom-setup-department-approval', [CmsController::class, 'commissionCustomSetupDepartmentApproval']);
        Route::post('web/cms/commission-setup-structure-department-approval', [CmsController::class, 'commissionSetupStructureDepartmentApproval']);
        Route::post('web/cms/setup', [CmsController::class, 'saveSetup']);
        Route::post('web/cms/commissions', [CmsController::class, 'getCommissions']);
        Route::post('web/cms/calculate-commission', [CmsController::class, 'calculateCommission']);
        Route::post('web/cms/send-notification-agent', [CmsController::class, 'sendNotificationAgent']);
        Route::get('web/cms/users-agents', [CmsController::class, 'getUsers']);
        Route::get('web/cms/agents', [CmsController::class, 'getAgents']);
        Route::get('web/cms/managers', [CmsController::class, 'getManagers']);
        Route::get('web/cms/accounting', [CmsController::class, 'getAccounting']);
        Route::get('web/cms/executives', [CmsController::class, 'getExecutives']);
        Route::put('web/cms/department-approval', [CmsController::class, 'approveByDepartment']);
        Route::post('web/cms/commission-agent-detail', [CmsController::class, 'commissionAgentDetail']);
        Route::post('web/cms/agent-reset-status', [CmsController::class, 'resetAgentStatus']);
        Route::get('web/cms/agent-setup/{id}/{type}', [CmsController::class, 'getAgentSetup']);
        Route::get('web/cms/agent-setup-cms-info/{id}/{type}', [CmsController::class, 'getAgentSetupForCommissionInfo']);
        Route::get('web/cms/custom-setup/{id}/{type}', [CmsController::class, 'getCustomSetup']);
        Route::post('web/cms/save-agent-setup', [CmsController::class, 'saveAgentSetup']);
        Route::delete('web/cms/remove-agent', [CmsController::class, 'removeAgent']);
        Route::post('web/cms/manager-approval', [CmsController::class, 'managerApproval']);
        Route::post('web/cms/accounting-approval', [CmsController::class, 'accountingApproval']);
        Route::post('web/cms/executive-approval', [CmsController::class, 'executiveApproval']);
        Route::get('web/cms/download', [CmsController::class, 'commissionDownloadToCSV']);
        Route::get('web/cms/companies', [CmsController::class, 'getCompanies']);
        Route::post('web/cms/export', [CmsController::class, 'export']);
        Route::get('web/cms/reconciliations', [CmsController::class, 'getReconciliations']);
    });

    Route::group(['middleware' => 'accessRight:cmsshare'], function () {
        Route::post('web/cms/summary-commission-approval', [CmsController::class, 'summaryCommissionApproval']);
    });

    //Application Dashboard
    Route::get('web/application-dashboard/{objectId}/mortgages', [ApplicationDashboardController::class, 'getMortgages']);
    Route::get('/web/application-dashboard/{objectId}/notes', [ApplicationDashboardController::class, 'getNotes']);
    Route::get('/web/application-dashboard/{objectId}/application-id', [ApplicationDashboardController::class, 'getApplicationId']);

    Route::get('/web/application-dashboard/mortgage/{mortgageId}/payments', [ApplicationDashboardController::class, 'getMortgagePayments']);
    Route::get('/web/application-dashboard/mortgage/{mortgageId}/upcoming-payments', [ApplicationDashboardController::class, 'getUpcomingPayments']);
    Route::get('/web/application-dashboard/mortgage/{mortgageId}/renewals', [ApplicationDashboardController::class, 'getRenewals']);
    Route::get('/web/application-dashboard/mortgage/{mortgageId}/bank-info', [ApplicationDashboardController::class, 'getPapBankInfo']);
    Route::get('/web/application-dashboard/mortgage/{mortgageId}/properties', [ApplicationDashboardController::class, 'getProperties']);
    Route::get('/web/application-dashboard/mortgage/{mortgageId}/mortgagors', [ApplicationDashboardController::class, 'getMortgagors']);
    Route::get('/web/application-dashboard/mortgage/{mortgageId}/investors', [ApplicationDashboardController::class, 'getInvestorTracking']);
    Route::get('/web/application-dashboard/mortgage/payout', [ApplicationDashboardController::class, 'calculatePayout']);
    Route::post('/web/application-dashboard/mortgage/insurance', [ApplicationDashboardController::class, 'updateInsurance']);
    Route::post('/web/application-dashboard/mortgage/earthquake', [ApplicationDashboardController::class, 'updateEarthquake']);

    //My Apps
    Route::get('/web/my-apps', [ApplicationDashboardController::class, 'getMyApps']);

    //Mortgage
    Route::get('web/mortgage/property/{propertyId}',  [MortgageController::class, 'getByProperty']);

    //Notes
    Route::get('web/note', [NoteController::class, 'show']);
    Route::post('web/note', [NoteController::class, 'store']);
    Route::delete('web/note/{id}', [NoteController::class, 'destroy']);

    //Contact Center
    Route::get('web/contact-center/lender-firms', [LenderFirmController::class, 'index']);
    Route::post('web/contact-center/lender-firms', [LenderFirmController::class, 'addLenderFirms']);
    Route::get('web/contact-center/insurance-broker-firms', [BrokerFirmController::class, 'index']);
    Route::post('web/contact-center/insurance-broker-firms', [BrokerFirmController::class, 'addBrokerFirms']);
    Route::get('/web/contact-center/insurance/{code}', [BrokerFirmController::class, 'brokerDetails']);
    Route::get('web/contact-center/appraisal-firms', [AppraisalFirmController::class, 'index']);
    Route::post('web/contact-center/appraisal-firms', [AppraisalFirmController::class, 'addAppraisalFirms']);
    Route::put('web/contact-center/appraisal-firms/{id}', [AppraisalFirmController::class, 'updateAppraisalFirms']);
    Route::get('web/contact-center/ila', [ILAController::class, 'index']);
    Route::get('web/contact-center/ila/{code}', [ILAController::class, 'show']);
    Route::post('web/contact-center/ila', [ILAController::class, 'addIlaFirms']);
    Route::put('/web/contact-center/ila/{id}', [ILAController::class, 'updateILA']);
    Route::get('/web/contact-center/appraisal/{code}', [AppraisalFirmController::class, 'apprDetails']);
    Route::get('web/contact-center/appraisal-date/{id}', [PropertyController::class, 'getAppraisalDate']);
    Route::get('web/contact-center/street-types', [PropertyController::class, 'getStreetTypes']);
    Route::get('web/contact-center/direction-types', [PropertyController::class, 'getDirectionTypes']);
    Route::get('web/contact-center/value-method-options', [PropertyController::class, 'getValueMethodOptions']);
    Route::get('web/contact-center/order-method-options', [PropertyController::class, 'getOrderMethodOptions']);
    Route::get('web/contact-center/who-will-pay-options', [PropertyController::class, 'getWhoWillPayOptions']);

    Route::post('web/contact-center/residential-market-valuation', [PropertyController::class, 'getResidentialMarketValuation']);
    Route::post('web/contact-center/estimated-value-range', [PropertyController::class, 'getEstimatedValueRange']);

    Route::get('web/contact-center/property-types', [PropertyController::class, 'getPropertyTypes']);
    Route::get('web/contact-center/unit-type-options', [PropertyController::class, 'getUnitTypeOptions']);
    Route::get('web/contact-center/basement-options', [PropertyController::class, 'getBasementOptions']);
    Route::get('web/contact-center/heat-options', [PropertyController::class, 'getHeatOptions']);
    Route::get('web/contact-center/roofing-options', [PropertyController::class, 'getRoofingOptions']);
    Route::get('web/contact-center/exterior-options', [PropertyController::class, 'getExteriorOptions']);
    Route::get('web/contact-center/house-options', [PropertyController::class, 'getHouseOptions']);
    Route::get('web/contact-center/water-options', [PropertyController::class, 'getWaterOptions']);
    Route::get('web/contact-center/sewage-options', [PropertyController::class, 'getSewageOptions']);
    Route::get('web/contact-center/rental-options', [PropertyController::class, 'getRentalOptions']);
    Route::get('web/contact-center/applicant-type-options', [PropertyController::class, 'getApplicantTypeOptions']);
    Route::get('web/contact-center/marital-status-options', [PropertyController::class, 'getMaritalStatusOptions']);
    Route::get('web/contact-center/broker-options', [ContactCenterAppController::class, 'getBrokerOptions']);
    Route::post('web/contact-center/appraisal-date', [PropertyController::class, 'saveAppraisalDate']);
    Route::post('web/contact-center/send-appraisal-email', [AppraisalFirmController::class, 'sendAppraisalEmail']);
    Route::get('web/contact-center/nearby-mortgages', [ContactCenterAppController::class, 'nearbyMortgages']);
    Route::post('web/contact-center/to-be-paid-out', [MortgageController::class, 'saveToBePaidOut']);
    Route::put('web/contact-center/ila', [ApplicationController::class, 'saveIla']);
    Route::get('web/contact-center/investor-tracking', [ContactCenterAppController::class, 'getInvestorTracking']);
    Route::get('web/contact-center/disbursement/{applicationId}', [ContactCenterAppController::class, 'getDisbursement']);
    Route::put('web/contact-center/part-of-security/{id}', [ContactCenterAppController::class, 'updatePartOfSecurity']);
    Route::put('web/contact-center/sales-journey', [ContactCenterAppController::class, 'updateSalesJourney']);
    Route::get('web/contact-center/{id}/{type}', [ContactCenterAppController::class, 'show']);
    Route::put('web/contact-center/{id}/{type}', [ContactCenterAppController::class, 'store']);
    Route::get('web/contact-center/applicant-title-holder', [ContactCenterAppController::class, 'getApplicantTitleHolder']);
    Route::post('web/contact-center/title-holder', [PropertyController::class, 'saveTitleHolder']);
    Route::get('web/sales-journey/{applicationId}', [ContactCenterAppController::class, 'getSalesJourneyStatus']);

    //Merge
    Route::get('web/merge/documents', [MergeController::class, 'getDocuments']);
    Route::get('web/merge/other-documents', [MergeController::class, 'getOtherDocuments']);
    Route::post('web/merge/merge-documents', [MergeController::class, 'mergeDocuments']);
    Route::post('web/merge/view-documents', [MergeController::class, 'viewDocuments']);
    Route::get('web/merge/applicants-email', [MergeController::class, 'getApplicantsEmail']);


    //Initialization
    Route::post('web/initialization/check-active-quotes', [InitializationController::class, 'checkActiveQuotes']);
    Route::post('web/initialization/quote-selected', [InitializationController::class, 'quoteSelected']);
    Route::post('web/pap/bank-info/institution-name', [PapController::class, 'institutionName']);
    Route::post('web/initialization/initialize', [InitializationController::class, 'initialize']);

    //Quote
    Route::get('web/quote/costs/{applicationId}', [QuoteController::class, 'getCosts']);
    Route::get('web/quote/fees/{companyId}', [QuoteController::class, 'getFees']);
    Route::post('web/quote', [QuoteController::class, 'store']);
    Route::put('web/quote', [QuoteController::class, 'update']);
    Route::delete('web/quote/{savedQuoteId}', [QuoteController::class, 'destroy']);
    Route::get('web/quote/prime-rate', [QuoteController::class, 'getPrimeRate']);
    Route::get('web/quote/ila/{applicationId}', [QuoteController::class, 'getIla']);
    Route::get('web/quote/{applicationId}', [QuoteController::class, 'index']);

    //Pricebook
    Route::get('web/pricebook',  [PricebookController::class, 'index']);
    Route::post('web/pricebook', [PricebookController::class, 'newPricebook']);
    Route::get('web/pricebook/{applicationId}',  [PricebookController::class, 'getByApplication']);

    //Checklist
    Route::get('web/checklist/{applicationId}/{checkListType}/{objectId}', [ChecklistController::class, 'index']);
    Route::post('web/checklist/{objectId}', [ChecklistController::class, 'saveAnswers']);

    //Tracker
    Route::get('/web/tracker', [TrackerController::class, 'index']);
    Route::get('/web/tracker/report', [TrackerController::class, 'generateReport']);
    Route::get('/tracker/support-report-per-day', [TrackerController::class, 'getReportPerDay']);
    Route::get('/tracker/support-report-per-week', [TrackerController::class, 'getSupportFundingsPerWeek']);
    Route::get('/tracker/support-report-per-month', [TrackerController::class, 'getSupportDocsPerMonth']);
    Route::get('/web/tracker/document-types', [TrackerController::class, 'getDocumentTypes']);
    Route::get('/web/tracker/all-users', [TrackerController::class, 'getAllUsers']);
    Route::post('/web/tracker', [TrackerController::class, 'store']);
    Route::put('/web/tracker/accounting-status/{docId}', [TrackerController::class, 'updateAccountingStatus']);
    Route::put('/web/tracker/support-status/{docId}', [TrackerController::class, 'updateSupportStatus']);
    Route::put('/web/tracker/notes/{docId}', [TrackerController::class, 'updateNotes']);
    Route::delete('/web/tracker/{docId}', [TrackerController::class, 'destroy']);

    Route::get('/web/applications/{applicationId}', [ApplicationController::class, 'index']);
    Route::get('/web/applications/{applicationId}/mortgages', [MortgageController::class, 'getMortgages']);   

    //Appraisal
    Route::get('web/appraisal/documents', [AppraisalController::class, 'getDocuments']);
    Route::get('web/appraisal/process', [AppraisalController::class, 'processDocument']);

    //Test
    //Route::get('/web/test/charts', [TestController::class, 'charts']);
    Route::post('/web/test/drill-down', [TestController::class, 'drillDown']);

    //Catch-all
    Route::get('{any}', function () {
        return view('app');
    })->where('any', '^(?!api).*$');
});
