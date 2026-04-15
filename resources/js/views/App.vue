<template>
    <template v-if="arrayStartsWith(fullScreenRoutes, currentPage)">
        <router-view @events="handleEvents" :key="reloadPage" />
    </template>

    <template v-else-if="currentPage !== ''">
        <div v-bind:class="['sidebar sidebar-dark sidebar-fixed bg-brand', (arrayStartsWith(collapseMenuRoutes, currentPage)) ? 'sidebar-narrow-unfoldable' : '']" id="sidebar">
            
            <div class="sidebar-header">
                <div class="sidebar-brand w-100">
                    <div class="sidebar-brand-full text-center">
                        <div class="d-flex align-items-center justify-content-center h-100">
                            <a href="/"><img width="110" src="/images/uniLogoPequena.png" /></a>
                        </div>
                    </div>

                    <div class="sidebar-brand-narrow text-center">
                        <div style="height: 50px;">
                            <span class="text-primary fw-bold">AFG</span>
                        </div>
                    </div>
                </div>
            </div>

            <PerfectScrollbar>
                <ul class="sidebar-nav pt-1" data-coreui="navigation">
                    <template v-for="(menu, key) in menus" :key="key">
                        <li class="nav-group">
                            <a
                                v-bind:class="['nav-link text-white', menu.submenus !== undefined ? 'nav-group-toggle' : '']"
                                href="#"
                                @click="toPage(menu, true)"
                            >
                                <i v-bind:class="[menu.icon, 'nav-icon']"></i>{{ menu.name }}
                            </a>

                            <ul class="nav-group-items" v-if="menu.submenus !== undefined">
                                <li class="nav-item" v-for="(submenu, k) in menu.submenus" :key="k">
                                    <a
                                        v-if="submenu.type == 'page'"
                                        class="nav-link"
                                        href="#"
                                        @click="toPage(submenu, true)"
                                    >
                                        <span class="nav-icon"></span>{{ submenu.name }}
                                    </a>

                                    <hr v-else class="m-0" />
                                </li>
                            </ul>
                        </li>
                    </template>
                </ul>
            </PerfectScrollbar>

            <button class="btn btn-dark d-lg-none" type="button" @click="toggleSidebar">
                <i class="bi bi-x-lg"></i>
            </button>
        </div>

        <div class="wrapper d-flex flex-column min-vh-100 bg-light-ultra">
            <header class="header header-sticky bg-brand-light mb-4">
                <div class="container-fluid">
                    <button
                        class="header-toggler px-md-0 me-md-3 d-md-none text-dark"
                        type="button"
                        @click="toggleSidebar()"
                    >
                        <i class="bi-list"></i>
                    </button>

                    <ul class="header-nav ms-auto me-3">
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle text-dark" data-coreui-toggle="dropdown" href="#" role="button" aria-expanded="false">
                                <i class="bi-person-circle"></i>
                            </a>

                            <div class="dropdown-menu dropdown-menu-end pt-0">
                                <div class="dropdown-header bg-body-tertiary text-body-secondary fw-semibold rounded-top mb-2">Configurações</div>

                                <a class="dropdown-item" href="#" @click="toPage({ path: '/alterar-senha' })">
                                    <i class="bi bi-lock me-2"></i>Alterar minha senha
                                </a>

                                <hr class="dropdown-divider">
                                
                                <a class="dropdown-item" href="#" @click="logout()">
                                    <i class="bi bi-box-arrow-right me-2"></i>Logout
                                </a>
                            </div>
                        </li>
                    </ul>
                </div>
            </header>

            <div class="body flex-grow-1 px-3">
                <div class="container-fluid">
                    <router-view @events="handleEvents" :key="reloadPage" />
                </div>
            </div>

            <footer class="footer" v-if="currentPage == '/'">
                <div>
                    <a href="https://unigestaodenegocios.com.br" target="_blank"
                        >UNI - Gestão de Negócios</a
                    >
                    &copy; {{ year }}
                </div>
                <div class="ms-auto"></div>
            </footer>
        </div>
    </template>

    <div id="alert-success" class="alert-overlay" style="display: none">
        <div
            class="alert alert-success alert-dismissible fade show mx-3 mx-xs-3 mx-sm-5 mx-md-10 mx-xxl-20"
            role="alert"
            style="bottom: 5%"
        >
            <span id="alert-message-success"><!--message goes here--></span>
            <button
                type="button"
                class="btn-close"
                @click="hideAlert('alert-success')"
                aria-label="Close"
            ></button>
        </div>
    </div>

    <div id="alert-warning" class="alert-overlay" style="display: none">
        <div
            class="alert alert-warning alert-dismissible fade show mx-3 mx-xs-3 mx-sm-5 mx-md-10 mx-xxl-20"
            role="alert"
            style="bottom: 15%"
        >
            <span id="alert-message-warning"><!--message goes here--></span>
            <button
                type="button"
                class="btn-close"
                @click="hideAlert('alert-warning')"
                aria-label="Close"
            ></button>
        </div>
    </div>

    <div id="alert-error" class="alert-overlay" style="display: none">
        <div
            class="alert alert-danger alert-dismissible fade show mx-3 mx-xs-3 mx-sm-5 mx-md-10 mx-xxl-20"
            role="alert"
            style="bottom: 5%"
        >
            <span id="alert-message-error"><!--message goes here--></span>
            <button
                type="button"
                class="btn-close"
                @click="hideAlert('alert-error')"
                aria-label="Close"
            ></button>
        </div>
    </div>

    <div class="preloader" id="preloader" style="display: none">
        <div class="d-flex justify-content-center h-100">
            <div
                class="spinner-border text-primary align-self-center"
                role="status"
            >
                <span class="visually-hidden">Loading...</span>
            </div>
        </div>
    </div>

    <div class="backdrop" id="backdrop" style="display: none">
        <div class="d-flex justify-content-center h-100"></div>
    </div>

    <!-- Modal de inatividade -->
    <div v-if="inactivityModal" class="inactivity-overlay">
        <div class="inactivity-card">
            <div class="inactivity-icon">
                <i class="bi-clock-history"></i>
            </div>
            <h5 class="mb-2">Sua sessão está prestes a expirar</h5>
            <p class="text-muted mb-3">
                Por inatividade, você será desconectado em
            </p>
            <div class="inactivity-countdown">{{ inactivityCountdownFormatted }}</div>
            <p class="text-muted mt-2 mb-4 small">Deseja continuar conectado?</p>
            <div class="d-flex gap-2 justify-content-center">
                <button class="btn btn-outline-secondary" @click="doLogout()">
                    <i class="bi-box-arrow-right me-1"></i>Sair agora
                </button>
                <button class="btn btn-primary" @click="continueSession()">
                    <i class="bi-check-lg me-1"></i>Continuar conectado
                </button>
            </div>
        </div>
    </div>
</template>

<script>
import { util } from "../mixins/util"
import { PerfectScrollbar } from 'vue3-perfect-scrollbar'
import 'vue3-perfect-scrollbar/style.css'

export default {
    mixins: [util],
    components: {
        PerfectScrollbar,
    },
    data() {
        return {
            year: new Date().getFullYear(),
            reloadPage: 0,
            currentPage: '',
            collapseMenuRoutes: [
                '/tracker',
                '/renewals-tracker',
                '/in-progress-renewals',
                '/renewals-for-approval',
                '/processed-renewals',
                '/renewals-tracker',
                '/renewals-summary',
            ],
            fullScreenRoutes: [
                '/login',
                '/contact-center',
                '/contact-center-app',
                '/merge',
                '/initialization',
                '/reorg-applicants',
                '/mailings',
                '/application-dashboard',
                '/my-apps',
                '/edit-notes',
                '/nearby-mortgages',
            ],
            user: {},
            menus: [],
            // Inatividade
            inactivityModal:     false,
            inactivityCountdown: 300,
            _idleTimer:          null,
            _countdownTimer:     null,
        };
    },
    computed: {
        inactivityCountdownFormatted() {
            const m = Math.floor(this.inactivityCountdown / 60);
            const s = this.inactivityCountdown % 60;
            return `${m}:${s.toString().padStart(2, '0')}`;
        },
    },
    mounted() {
        this.checkSession()
        this.getCurrentUser()
        this.getMenu()
        this.startInactivityWatch()

        this.$router.isReady().then(() => {
            this.currentPage = this.$route.path
        });
    },
    methods: {
        // ── Inatividade ──────────────────────────────────────────────────────
        startInactivityWatch() {
            const events = ['mousemove', 'keydown', 'click', 'scroll', 'touchstart'];
            events.forEach(evt => window.addEventListener(evt, this.onUserActivity, { passive: true }));
            this.resetIdleTimer();
        },

        onUserActivity() {
            if (!this.inactivityModal) {
                this.resetIdleTimer();
            }
        },

        resetIdleTimer() {
            clearTimeout(this._idleTimer);
            this._idleTimer = setTimeout(() => {
                this.showInactivityWarning();
            }, 25 * 60 * 1000); // 25 minutos sem atividade
        },

        showInactivityWarning() {
            this.inactivityModal     = true;
            this.inactivityCountdown = 300; // 5 minutos para reagir

            this._countdownTimer = setInterval(() => {
                this.inactivityCountdown--;

                if (this.inactivityCountdown <= 0) {
                    clearInterval(this._countdownTimer);
                    this.inactivityModal = false;
                    this.doLogout();
                }
            }, 1000);
        },

        continueSession() {
            clearInterval(this._countdownTimer);
            this.inactivityModal     = false;
            this.inactivityCountdown = 300;
            this.resetIdleTimer();

            // Renova a sessão no servidor
            this.axios({ method: 'get', url: '/web/check-session' })
                .catch(() => { window.location.href = '/'; });
        },

        doLogout() {
            clearTimeout(this._idleTimer);
            clearInterval(this._countdownTimer);
            this.inactivityModal = false;
            this.logout();
        },
        // ─────────────────────────────────────────────────────────────────────

        logout: function () {
            this.axios({
                method: "get",
                url: "/web/logout",
            })
            .then((response) => {
                window.location.href = "/"
            })
            .catch((error) => {
                this.alertMessage = error
                this.showAlert("error")
            })
        },

        showSidebar: function () {
            this.coreui.Sidebar.getInstance(
                document.querySelector("#sidebar")
            ).show()
        },
        hideSidebar: function () {
            this.coreui.Sidebar.getInstance(
                document.querySelector("#sidebar")
            ).hide()
        },
        toggleSidebar: function () {
            this.coreui.Sidebar.getInstance(
                document.querySelector("#sidebar")
            ).toggle()
        },
        toPage: function(menu, toggle = false) {
            console.log('toPage', menu, toggle)

            if(menu.path === undefined || menu.path === null) {
                return
            }

            this.$router.push(menu.path)

            if(screen.width < 960 && toggle) {
                this.toggleSidebar()
            }

            if(menu.path == this.currentPage) {
                this.reloadPage++
            }

            this.currentPage = menu.path
        },
        handleEvents: function (type, pageName) {
            this.toPage({path: pageName}, false)
        },

        getCurrentUser: function () {
            this.axios({
                method: "get",
                url: "/web/current-user",
            })
            .then((response) => {
                if (this.checkApiResponse(response)) {
                    this.user = response.data.data

                    console.log('Current User:', this.user)

                    this.setUser(this.user)
                }
            })
            .catch((error) => {
                console.log(error)
            })
        },

        getMenu: function () {
            this.axios({
                method: "get",
                url: "/web/menus",
            })
            .then((response) => {
                if(this.checkApiResponse(response)) {
                    this.menus = response.data.data                    
                }
                console.log(this.menus)
            })
            .catch((error) => {
                console.log(error)
            })
        },

        checkSession: function () {
            setTimeout(() => this.getCheckSession(), 1000 * 60 * 10)
        },
        
        getCheckSession: function () {
            this.axios({
                method: "get",
                url: "/web/check-session",
            })
            .then((response) => {
                if (this.checkApiResponse(response)) {
                    this.checkSession()
                    return
                }

                window.location.href = "/"
            })
            .catch((error) => {
                window.location.href = "/"
            })
        },
    },
}
</script>
<style scoped>
    .bg-brand {
        background-color: #1C4D64 !important;
    }
    .bg-brand-light {
        background-color: #255E78 !important;
    }
    .bg-brand-lighter {
        background-color: #2F6F8C !important;
    }
    .footer {
        background-color: #f5f7f9;
        color: #1C4D64;
        border-top: 1px solid #dce3e8;
    }

    .inactivity-overlay {
        position: fixed;
        inset: 0;
        background: rgba(0, 0, 0, 0.55);
        z-index: 99999;
        display: flex;
        align-items: center;
        justify-content: center;
    }

    .inactivity-card {
        background: #fff;
        border-radius: 12px;
        padding: 2rem 2.5rem;
        max-width: 400px;
        width: 90%;
        text-align: center;
        box-shadow: 0 8px 32px rgba(0,0,0,0.18);
    }

    .inactivity-icon {
        font-size: 2.5rem;
        color: #ffc107;
        margin-bottom: 0.75rem;
    }

    .inactivity-countdown {
        font-size: 2.8rem;
        font-weight: 700;
        color: #dc3545;
        letter-spacing: 2px;
    }
</style>

