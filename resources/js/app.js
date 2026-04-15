import { createApp } from 'vue'
import App from './views/App.vue'
//import Login from './views/Login.vue'
import router from './router'
import axios from 'axios'
import coreui from '@coreui/coreui-pro/dist/js/coreui.min.js'
import './../css/style.css'
import 'bootstrap-icons/font/bootstrap-icons.css'
import VTooltip from 'v-tooltip'
import 'v-tooltip/dist/v-tooltip.css'

//Main application
const app = createApp(App)
app.use(router)
app.use(VTooltip)

//Directives
app.directive('blur', {
    mounted(el) {
        el.onfocus = (ev) => ev.target.blur()
    }
})

//Axios
axios.defaults.withCredentials = true

// Interceptor global: 401 → salva mensagem e redireciona para login
axios.interceptors.response.use(
    response => response,
    error => {
        if (error.response && error.response.status === 401) {
            if (!window.location.pathname.startsWith('/login')) {
                const msg = error.response?.data?.message
                if (msg) sessionStorage.setItem('auth_alert', msg)
                window.location.href = '/'
            }
        }
        return Promise.reject(error)
    }
)

app.config.globalProperties.axios = axios

//Coreui
app.config.globalProperties.coreui = coreui

app.mount('#app')
