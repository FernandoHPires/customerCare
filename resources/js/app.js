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

// Máscaras de input — uso: v-mask="'phone'" ou v-mask="'cnpj'"
function applyMask(value, type) {
    const d = value.replace(/\D/g, '')
    if (type === 'phone') {
        if (d.length === 0) return ''
        if (d.length <= 2)  return '(' + d
        if (d.length <= 6)  return '(' + d.slice(0,2) + ') ' + d.slice(2)
        if (d.length <= 10) return '(' + d.slice(0,2) + ') ' + d.slice(2,6) + '-' + d.slice(6)
        return '(' + d.slice(0,2) + ') ' + d.slice(2,7) + '-' + d.slice(7,11)
    }
    if (type === 'cnpj') {
        if (d.length === 0)  return ''
        if (d.length <= 2)   return d
        if (d.length <= 5)   return d.slice(0,2) + '.' + d.slice(2)
        if (d.length <= 8)   return d.slice(0,2) + '.' + d.slice(2,5) + '.' + d.slice(5)
        if (d.length <= 12)  return d.slice(0,2) + '.' + d.slice(2,5) + '.' + d.slice(5,8) + '/' + d.slice(8)
        return d.slice(0,2) + '.' + d.slice(2,5) + '.' + d.slice(5,8) + '/' + d.slice(8,12) + '-' + d.slice(12,14)
    }
    return value
}

app.directive('mask', {
    mounted(el, binding) {
        el.addEventListener('input', (e) => {
            const masked = applyMask(e.target.value, binding.value)
            if (e.target.value !== masked) {
                e.target.value = masked
                e.target.dispatchEvent(new Event('input', { bubbles: true }))
            }
        })
        // Formata valor inicial (ao abrir edição)
        if (el.value) {
            el.value = applyMask(el.value, binding.value)
        }
    },
    updated(el, binding) {
        // Reformata quando o v-model muda externamente (ex: abrir modal de edição)
        if (el !== document.activeElement && el.value) {
            const masked = applyMask(el.value, binding.value)
            if (el.value !== masked) el.value = masked
        }
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
