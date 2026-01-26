import { createRouter, createWebHistory } from "vue-router";

import Home from '../pages/Home.vue';
import Login from '../views/Login.vue';
import PAPNew from '../pages/PAP/NewPAP.vue';
import PAPBankUpdate from '../pages/PAP/BankUpdate.vue';
import PAPUpdates from '../pages/PAP/Updates.vue';
import PAPBankFileDebit from '../pages/PAP/BankFileDebit.vue';
import PAPBankFileCredit from '../pages/PAP/BankFileCredit.vue';

import PayoutTracker from '../pages/Payout/PayoutTracker.vue';
import PayoutApproval from '../pages/Payout/PayoutApproval.vue';
import ProcessPayout from '../pages/Payout/ProcessPayout.vue';
import CancelPayout from '../pages/Payout/CancelPayout.vue';

import ForeclosureTracker from '../pages/Payout/ForeclosureTracker.vue';
import ProcessForeclosure from '../pages/Payout/ProcessForeclosure.vue';
import CancelForeclosure from '../pages/Payout/CancelForeclosure.vue';
import Reports from '../pages/Report.vue';

import PriceBook from '../pages/PriceBook.vue';
import NSFtoQB from '../pages/Finance/NSFtoQB.vue';
import NSFtoNetsuite from '../pages/Finance/NSFtoNetsuite.vue';

//Reports
import MicPipeline from '../pages/Report/MicPipeline.vue';
import InitialDocs from '../pages/Report/InitialDocs.vue';
import CommercialLoansTracker from '../pages/Report/CommercialLoansTracker.vue';
import MicForecast from '../pages/Report/MicForecast.vue';
import OriginationPipeline from '../pages/Report/OriginationPipeline.vue';
import BrokerDashboard from '../pages/Report/BrokerDashboard.vue';
import BrokerDetails from '../pages/Report/BrokerDetails.vue';
import StagePipeline from '../pages/Report/StagePipeline.vue';

//DMS
import TemplateManagement from '../pages/DMS/TemplateManagement.vue';
import TemplateApproval from '../pages/DMS/TemplateApproval.vue';

//CMS
import CMSCommissionSetup from '../pages/CMS/CommissionSetup.vue';
import CMSAgentSetup from '../pages/CMS/AgentSetup.vue';
import CMSCommissionApproval from '../pages/CMS/CommissionApproval.vue';
import CMSApporvalDepartment from '../components/CMS/CMSApprovalDepartment.vue';
import CMSCommissionSetupApproval from '../pages/CMS/CommissionSetupApproval.vue';
import CMSReconciliation from '../pages/CMS/Reconciliation.vue';

//Tracker
import Tracker from '../pages/Tracker.vue';

//standalone apps
import ApplicationDashboard from '../pages/ApplicationDashboard.vue';
import ContactCenterApp from '../pages/ContactCenterApp.vue';
import ContactCenter from '../pages/ContactCenter.vue';
import Merge from '../pages/Merge.vue';
import Initialization from '../pages/Initialization.vue';
import ReorgApplicants from '../pages/ReorgApplicants.vue';
import Mailing from '../pages/Mailing.vue';
import MyApps from '../pages/MyApps.vue';
import EditNotes from "../pages/EditNotes.vue";
import NearbyMortgages from "../components/NearbyMortgages.vue";
import RenewalsTracker from '../pages/Renewals/RenewalsTracker.vue';
import InProgressRenewals from '../pages/Renewals/InProgressRenewals.vue';
import RenewalForApproval from '../pages/Renewals/RenewalForApproval.vue';
import ProcessedRenewals from '../pages/Renewals/ProcessedRenewals.vue';
import RenewalsSummary from '../pages/Renewals/RenewalsSummary.vue';
import OpportunityCAP from '../pages/OpportunityCAP.vue';

//catch-all
import NotFound from '../pages/NotFound.vue';

//Appraisal
import Appraisal from '../pages/Appraisal.vue';

const routes = [
    {
        path: "/",
        name: "Home",
        component: Home,
    },
    {
        path: "/login",
        name: "Login",
        component: Login,
    },
    //Pages
    {
        path: "/pap-new",
        name: "PAPNew",
        component: PAPNew,
    },
    {
        path: "/pap-bank-update",
        name: "PAPBankUpdate",
        component: PAPBankUpdate,
    },
    {
        path: "/pap-updates",
        name: "PAPUpdates",
        component: PAPUpdates,
    },
    {
        path: "/pap-bank-file-debit",
        name: "PAPBankFileDebit",
        component: PAPBankFileDebit,
    },
    {
        path: "/pap-bank-file-credit",
        name: "PAPBankFileCredit",
        component: PAPBankFileCredit,
    },

    //Reports
    {
        path: "/reports",
        name: "Reports",
        component: Reports,
    },
    //{
    //    path: "/reports/bayview-commitment",
    //    name: BayviewCommitment,
    //    component: BayviewCommitment,
    //},
    //{
    //    path: "/reports/bayview-acquisition",
    //    name: BayviewAcquisition,
    //    component: BayviewAcquisition,
    //},
    //{
    //    path: "/reports/bayview-remittance",
    //    name: BayviewRemittance,
    //    component: BayviewRemittance,
    //},
    //{
    //    path: "/reports/bayview-trial-balance",
    //    name: BayviewTrialBalance,
    //    component: BayviewTrialBalance,
    //},
    {
        path: "/reports/mic-pipeline",
        name: "MicPipeline",
        component: MicPipeline,
    },
    {
        path: "/reports/initial-docs",
        name: "InitialDocs",
        component: InitialDocs,
    },
    {
        path: "/reports/commercial-loans-tracker",
        name: "CommercialLoansTracker",
        component: CommercialLoansTracker,
    },
    {
        path: "/reports/mic-forecast",
        name: "MicForecast",
        component: MicForecast,
    },
    {
        path: "/reports/origination-pipeline",
        name: "OriginationPipeline",
        component: OriginationPipeline,
    },
    {
        path: "/reports/broker-dashboard",
        name: "BrokerDashboard",
        component: BrokerDashboard,
    },
    {
        path: "/reports/broker-details/:userId",
        name: "BrokerDetails",
        component: BrokerDetails,
    },
    {
        path: "/reports/stage-pipeline",
        name: "StagePipeline",
        component: StagePipeline,
    },

    //DMS
    {
        path: "/template-management",
        name: "TemplateManagement",
        component: TemplateManagement,
    },
    {
        path: "/template-approval",
        name: "TemplateApproval",
        component: TemplateApproval,
    },


    //Payout
    {
        path: "/payout-tracker",
        name: "PayoutTracker",
        component: PayoutTracker,
    },
    {
        path: "/payout-approval",
        name: "PayoutApproval",
        component: PayoutApproval,
    },
    {
        path: "/process-payout",
        name: "ProcessPayout",
        component: ProcessPayout,
    },
    {
        path: "/cancel-payout",
        name: "CancelPayout",
        component: CancelPayout,
    },

    //Foreclosure
    {
        path: "/foreclosure-tracker",
        name: "ForeclosureTracker",
        component: ForeclosureTracker,
    },
    {
        path: "/process-foreclosure",
        name: "ProcessForeclosure",
        component: ProcessForeclosure,
    },
    {
        path: "/cancel-foreclosure",
        name: "CancelForeclosure",
        component: CancelForeclosure,
    },

    //CMS
    {
        path: "/cms-commission-setup",
        name: "CMSCommissionSetup",
        component: CMSCommissionSetup,
    },
    {
        path: "/cms-setup-approval",
        name: "CMSCommissionSetupApproval",
        component: CMSCommissionSetupApproval,
    },
    {
        path: "/cms-agent-setup",
        name: "CMSAgentSetup",
        component: CMSAgentSetup,
    },
    {
        path: "/cms-department-approval",
        name: "CMSDepartmentApproval",
        component: CMSApporvalDepartment,
    },
    {
        path: "/cms-commission-approval",
        name: "CMSCommissionApproval",
        component: CMSCommissionApproval,
    },
    {
        path: "/cms-reconciliation",
        name: "CMSReconciliation",
        component: CMSReconciliation,
    },

    //Tracker
    {
        path: "/tracker",
        name: "Tracker",
        component: Tracker,
        props: route => ({ applicationId: route.query.applicationId, opportunityId: route.query.opportunityId })
    },

    //Finance - Utils
    {
        path: "/nsf-to-qb",
        name: "NSFtoQB",
        component: NSFtoQB,
    },
    {
        path: "/nsf-to-netsuite",
        name: "NSFtoNetsuite",
        component: NSFtoNetsuite,
    },

    //Standalone Apps
    {
        path: "/application-dashboard/:id",
        name: ApplicationDashboard,
        component: ApplicationDashboard,
    },
    {
        path: "/contact-center-app/:id",
        name: ContactCenterApp,
        component: ContactCenterApp,
    },
    {
        path: "/contact-center/:id/:type",
        name: ContactCenter,
        component: ContactCenter,
    },
    {
        path: "/edit-notes/:opportunityId",
        name: EditNotes,
        component: EditNotes,
    },
    {
        path: "/nearby-mortgages",
        name: NearbyMortgages,
        component: NearbyMortgages,
    },
    {
        path: "/note/:id/:type",
        name: ContactCenter,
        component: ContactCenter,
    },
    {
        path: "/merge/:opportunityId",
        name: "Merge",
        component: Merge,
    },
    {
        path: "/initialization/:opportunityId/:userId",
        name: "Initialization",
        component: Initialization,
    },
    {
        path: "/reorg-applicants/:opportunityId/:userId",
        name: "ReorgApplicants",
        component: ReorgApplicants,
    },
    {
        path: "/mailings/:opportunityId",
        name: "Mailing",
        component: Mailing,
    },
    {
        path: "/my-apps",
        name: "MyApps",
        component: MyApps,
        props: route => ({ query: route.query.q })
    },
    {
        path: "/price-book",
        name: "PriceBook",
        component: PriceBook,
    },

    // Renewals Tracker
    {
        path: "/renewals-tracker",
        name: "RenewalsTracker",
        component: RenewalsTracker,
    },
    {
        path: "/in-progress-renewals",
        name: "InProgressRenewals",
        component: InProgressRenewals,
    },
    {
        path: "/renewals-for-approval",
        name: "RenewalForApproval",
        component: RenewalForApproval,
    },
    {
        path: "/processed-renewals",
        name: "ProcessedRenewals",
        component: ProcessedRenewals,
    },
    {
        path: "/renewals-summary",
        name: "RenewalsSummary",
        component: RenewalsSummary,
    },

    {
        path: "/appraisal",
        name: "Appraisal",
        component: Appraisal,
    },

    // Salesforce
    {
        path: "/opportunity-cap",
        name: "OpportunityCAP",
        component: OpportunityCAP,
    },

    //Test
    

    //DO NOT ADD ROUTES BELOW THIS LINE
    //Catch-all
    {
        path: "/:catchAll(.*)",
        component: NotFound,
    },
];

const router = createRouter({
    history: createWebHistory(process.env.BASE_URL),
    routes,
});

export default router;
