<template>
    <div class="bg-light min-vh-100 d-flex flex-row align-items-center">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-lg-8">

                    <!-- Alerta de sessão derrubada -->
                    <div v-if="authAlert" class="alert alert-warning d-flex align-items-center gap-2 mb-3" role="alert">
                        <i class="bi-exclamation-triangle-fill fs-5"></i>
                        <span>{{ authAlert }}</span>
                    </div>

                    <div class="card-group d-block d-md-flex row">
                        <div class="card col-md-7 p-4 mb-0">
                            <div class="card-body">

                                <!-- ── Tela de login ── -->
                                <template v-if="!twoFactor">
                                    <h3>Login</h3>
                                    <p class="text-medium-emphasis">Entre com seu usuário e senha</p>

                                    <div class="input-group mb-3">
                                        <span class="input-group-text"><i class="bi-person"></i></span>
                                        <input class="form-control" type="text" placeholder="Usuário" v-model="username" :disabled="confirmandoSessao">
                                    </div>

                                    <div class="input-group mb-4">
                                        <span class="input-group-text"><i class="bi-key"></i></span>
                                        <input class="form-control" type="password" placeholder="Senha" v-on:keyup.enter="login()" v-model="password" :disabled="confirmandoSessao">
                                    </div>

                                    <!-- Confirmação de sessão ativa -->
                                    <div v-if="confirmandoSessao" class="alert alert-warning mb-3">
                                        <div class="d-flex align-items-center gap-2 mb-2">
                                            <i class="bi-shield-exclamation fs-5"></i>
                                            <strong>Sessão ativa detectada</strong>
                                        </div>
                                        <p class="mb-3 small">Já existe uma sessão ativa para este usuário. Se continuar, a sessão anterior será encerrada.</p>
                                        <div class="d-flex gap-2">
                                            <button class="btn btn-sm btn-outline-secondary" type="button" @click="cancelarConfirmacao()">
                                                <i class="bi-x-lg me-1"></i>Cancelar
                                            </button>
                                            <button class="btn btn-sm btn-warning" type="button" @click="login(true)">
                                                <i class="bi-box-arrow-in-right me-1"></i>Continuar e encerrar sessão anterior
                                            </button>
                                        </div>
                                    </div>

                                    <div v-else class="row">
                                        <div class="col-6">
                                            <button class="btn btn-primary px-4" type="button" @click="login()">Login</button>
                                        </div>
                                    </div>
                                </template>

                                <!-- ── Tela de verificação 2FA ── -->
                                <template v-else>
                                    <div class="d-flex align-items-center gap-2 mb-3">
                                        <button class="btn btn-sm btn-outline-secondary" @click="voltarLogin()">
                                            <i class="bi-arrow-left"></i>
                                        </button>
                                        <h3 class="mb-0">Verificação em duas etapas</h3>
                                    </div>

                                    <div class="alert alert-info d-flex align-items-start gap-2 mb-4">
                                        <i class="bi-envelope-fill fs-5 mt-1"></i>
                                        <div>
                                            <div>Enviamos um código de 6 dígitos para:</div>
                                            <strong>{{ maskedEmail }}</strong>
                                        </div>
                                    </div>

                                    <label class="form-label">Código de verificação</label>
                                    <div class="input-group mb-2">
                                        <span class="input-group-text"><i class="bi-shield-lock"></i></span>
                                        <input
                                            ref="codeInput"
                                            class="form-control form-control-lg text-center"
                                            type="text"
                                            inputmode="numeric"
                                            maxlength="6"
                                            placeholder="000000"
                                            v-model="twoFactorCode"
                                            style="letter-spacing: 8px; font-size: 1.5rem;"
                                            @keyup.enter="verifyCode()"
                                        />
                                    </div>

                                    <div class="d-flex justify-content-between align-items-center mb-4">
                                        <small class="text-muted">O código expira em 10 minutos.</small>
                                        <button
                                            class="btn btn-sm btn-link p-0"
                                            :disabled="reenvioSegundos > 0"
                                            @click="resendCode()"
                                        >
                                            <span v-if="reenvioSegundos > 0">Reenviar em {{ reenvioSegundos }}s</span>
                                            <span v-else>Reenviar código</span>
                                        </button>
                                    </div>

                                    <button class="btn btn-primary w-100" type="button" @click="verifyCode()">
                                        <i class="bi-check-lg me-1"></i>Verificar
                                    </button>
                                </template>

                            </div>
                        </div>

                        <div class="card col-md-5 p-0">
                            <img src="/images/uniLogo.png" class="login-image" alt="UNI Gestão de Negócios">
                        </div>
                    </div>

                </div>
            </div>
        </div>
    </div>
</template>

<script>
import { util } from '../mixins/util'

export default {
    mixins: [util],
    data() {
        return {
            username:          '',
            password:          '',
            confirmandoSessao: false,
            authAlert:         null,
            // 2FA
            twoFactor:         false,
            maskedEmail:       '',
            twoFactorCode:     '',
            reenvioSegundos:   0,
            _reenvioTimer:     null,
        }
    },
    mounted() {
        const msg = sessionStorage.getItem('auth_alert')
        if (msg) {
            this.authAlert = msg
            sessionStorage.removeItem('auth_alert')
        }
    },
    methods: {
        login(force = false) {
            this.axios({
                method: 'post',
                url: 'api/login',
                data: { username: this.username, password: this.password, force },
            })
            .then(response => {
                if (this.checkApiResponse(response)) {
                    window.location.href = '/'

                } else if (response.data.status === 'session_active') {
                    this.confirmandoSessao = true

                } else if (response.data.status === 'two_factor_required') {
                    this.maskedEmail   = response.data.maskedEmail
                    this.twoFactor     = true
                    this.twoFactorCode = ''
                    this.startReenvioCountdown()
                    this.$nextTick(() => this.$refs.codeInput?.focus())

                } else {
                    this.confirmandoSessao = false
                    this.alertMessage = response.data.message
                    this.showAlert('error')
                    this.password = ''
                }
            })
            .catch(error => {
                console.log(error)
                this.alertMessage = 'Erro ao realizar login.'
                this.showAlert('error')
            })
        },

        verifyCode() {
            if (!this.twoFactorCode || this.twoFactorCode.length < 6) {
                this.alertMessage = 'Digite o código de 6 dígitos.'
                this.showAlert('error')
                return
            }

            this.axios({
                method: 'post',
                url: 'api/two-factor/verify',
                data: { code: this.twoFactorCode },
            })
            .then(response => {
                if (this.checkApiResponse(response)) {
                    window.location.href = '/'
                } else {
                    this.alertMessage = response.data.message
                    this.showAlert('error')
                    this.twoFactorCode = ''
                    this.$nextTick(() => this.$refs.codeInput?.focus())
                }
            })
            .catch(error => {
                console.log(error)
                this.alertMessage = 'Erro ao verificar código.'
                this.showAlert('error')
            })
        },

        resendCode() {
            this.axios({ method: 'post', url: 'api/two-factor/resend' })
            .then(response => {
                if (this.checkApiResponse(response)) {
                    this.alertMessage = response.data.message || 'Novo código enviado!'
                    this.showAlert('success')
                    this.startReenvioCountdown()
                } else {
                    this.alertMessage = response.data.message
                    this.showAlert('error')
                }
            })
            .catch(() => {
                this.alertMessage = 'Erro ao reenviar código.'
                this.showAlert('error')
            })
        },

        startReenvioCountdown() {
            clearInterval(this._reenvioTimer)
            this.reenvioSegundos = 60
            this._reenvioTimer = setInterval(() => {
                this.reenvioSegundos--
                if (this.reenvioSegundos <= 0) {
                    clearInterval(this._reenvioTimer)
                }
            }, 1000)
        },

        voltarLogin() {
            clearInterval(this._reenvioTimer)
            this.twoFactor     = false
            this.twoFactorCode = ''
            this.maskedEmail   = ''
            this.password      = ''
        },

        cancelarConfirmacao() {
            this.confirmandoSessao = false
            this.password          = ''
        },
    }
}
</script>

<style scoped>
.login-image {
    width: 100%;
    height: 100%;
    object-fit: cover;
}
</style>
