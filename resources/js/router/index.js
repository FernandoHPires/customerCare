import { createRouter, createWebHistory } from "vue-router";

import Home from '../pages/Home.vue';
import Login from '../views/Login.vue';



//UNI
import PortfolioView from '../pages/PortfolioView.vue';
import Clientes from '../pages/Clientes.vue';
import Usuarios from '../pages/Usuarios.vue';

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
        path: "/clientes",
        name: "Clientes",
        component: Clientes,
    },
    {
        path: "/usuarios",
        name: "Usuarios",
        component: Usuarios,
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
