import { createRouter, createWebHistory } from "vue-router";

import Home from '../pages/Home.vue';
import Login from '../views/Login.vue';



//UNI
import PortfolioView from '../pages/PortfolioView.vue';

//catch-all
import NotFound from '../pages/NotFound.vue';


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
        path: "/portfolio-view",
        name: "PortfolioView",
        component: PortfolioView,
    },
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
