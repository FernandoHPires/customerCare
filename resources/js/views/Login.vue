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
                                <h3>Login</h3>
                                <p class="text-medium-emphasis">Entre com seu usuário e senha</p>

                                <div class="input-group mb-3">
                                    <span class="input-group-text">
                                        <i class="bi-person"></i>
                                    </span>
                                    <input class="form-control" type="text" placeholder="Usuário" v-model="username" :disabled="confirmandoSessao">
                                </div>

                                <div class="input-group mb-4">
                                    <span class="input-group-text">
                                        <i class="bi-key"></i>
                                    </span>
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
        }
    },
    mounted() {
        // Exibe mensagem de sessão derrubada (vinda do interceptor)
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
                data: {
                    username: this.username,
                    password: this.password,
                    force:    force,
                }
            })
            .then(response => {
                if (this.checkApiResponse(response)) {
                    window.location.href = '/'

                } else if (response.data.status === 'session_active') {
                    this.confirmandoSessao = true

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
