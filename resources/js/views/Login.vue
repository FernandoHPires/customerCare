<template>
    <div class="login-page">

        <!-- Partículas decorativas de fundo -->
        <div class="login-orb login-orb--1"></div>
        <div class="login-orb login-orb--2"></div>
        <div class="login-orb login-orb--3"></div>

        <!-- Alerta flutuante (sessão expirada, convite inválido, etc.) -->
        <div v-if="authAlert" class="login-alert-topo">
            <i class="bi-exclamation-triangle-fill me-2"></i>{{ authAlert }}
        </div>

        <!-- Card central -->
        <div class="login-card" :class="{ 'login-card--2fa': twoFactor }">

            <!-- Logo -->
            <div class="login-logo">
                <img src="/images/uniLogo.png" alt="UNI Gestão de Negócios" />
            </div>

            <!-- ══════════════ TELA DE LOGIN ══════════════ -->
            <template v-if="!twoFactor">

                <h2 class="login-titulo">Olá, seja bem-vindo!</h2>
                <p class="login-subtitulo">Entre com suas credenciais para continuar</p>

                <!-- Campo Usuário -->
                <div class="login-campo">
                    <i class="bi-person login-campo__icone"></i>
                    <input
                        class="login-campo__input"
                        type="text"
                        placeholder="Usuário"
                        v-model="username"
                        :disabled="confirmandoSessao"
                        @keyup.enter="!confirmandoSessao && login()"
                    />
                </div>

                <!-- Campo Senha -->
                <div class="login-campo">
                    <i class="bi-lock login-campo__icone"></i>
                    <input
                        class="login-campo__input"
                        type="password"
                        placeholder="Senha"
                        v-model="password"
                        :disabled="confirmandoSessao"
                        @keyup.enter="!confirmandoSessao && login()"
                    />
                </div>

                <!-- Aviso de sessão ativa -->
                <div v-if="confirmandoSessao" class="login-sessao-aviso">
                    <div class="login-sessao-aviso__titulo">
                        <i class="bi-shield-exclamation me-2"></i>Sessão ativa detectada
                    </div>
                    <p class="login-sessao-aviso__texto">
                        Já existe uma sessão ativa para este usuário. Se continuar, ela será encerrada.
                    </p>
                    <div class="d-flex gap-2">
                        <button class="login-btn login-btn--outline" type="button" @click="cancelarConfirmacao()">
                            Cancelar
                        </button>
                        <button class="login-btn login-btn--warning" type="button" @click="login(true)">
                            <i class="bi-box-arrow-in-right me-1"></i>Continuar mesmo assim
                        </button>
                    </div>
                </div>

                <!-- Turnstile + Botão de login -->
                <div v-else>
                    <div
                        id="turnstile-widget"
                        class="cf-turnstile login-turnstile"
                        data-sitekey="0x4AAAAAADAvFbWGKkvtT5FJ"
                        data-callback="onTurnstileSuccess"
                        data-expired-callback="onTurnstileExpired"
                        data-theme="light"
                    ></div>

                    <button
                        class="login-btn login-btn--primary"
                        type="button"
                        @click="login()"
                        :disabled="carregando || !turnstileToken"
                    >
                        <span v-if="carregando">
                            <span class="spinner-border spinner-border-sm me-2" role="status"></span>
                            Aguarde...
                        </span>
                        <span v-else>
                            <i class="bi-box-arrow-in-right me-2"></i>Entrar
                        </span>
                    </button>
                </div>

            </template>

            <!-- ══════════════ TELA 2FA ══════════════ -->
            <template v-else>

                <button class="login-voltar" @click="voltarLogin()">
                    <i class="bi-arrow-left me-1"></i>Voltar
                </button>

                <h2 class="login-titulo">Verificação em duas etapas</h2>

                <div class="login-2fa-info">
                    <i class="bi-envelope-paper fs-4 mb-2 d-block"></i>
                    <p class="mb-1">Código enviado para</p>
                    <strong>{{ maskedEmail }}</strong>
                </div>

                <!-- Input do código -->
                <div class="login-codigo-wrapper">
                    <input
                        ref="codeInput"
                        class="login-codigo-input"
                        type="text"
                        inputmode="numeric"
                        maxlength="6"
                        placeholder="000000"
                        v-model="twoFactorCode"
                        @keyup.enter="verifyCode()"
                    />
                </div>

                <div class="login-2fa-rodape">
                    <small>Expira em 10 minutos</small>
                    <button
                        class="login-reenviar"
                        :disabled="reenvioSegundos > 0"
                        @click="resendCode()"
                    >
                        <span v-if="reenvioSegundos > 0">
                            <i class="bi-clock me-1"></i>Reenviar em {{ reenvioSegundos }}s
                        </span>
                        <span v-else>
                            <i class="bi-arrow-repeat me-1"></i>Reenviar código
                        </span>
                    </button>
                </div>

                <button
                    class="login-btn login-btn--primary"
                    type="button"
                    @click="verifyCode()"
                    :disabled="carregando"
                >
                    <span v-if="carregando">
                        <span class="spinner-border spinner-border-sm me-2" role="status"></span>
                        Verificando...
                    </span>
                    <span v-else>
                        <i class="bi-shield-check me-2"></i>Verificar código
                    </span>
                </button>

            </template>

            <!-- Rodapé do card -->
            <div class="login-card__rodape">
                UNI Gestão de Negócios &copy; {{ anoAtual }}
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
            carregando:        false,
            anoAtual:          new Date().getFullYear(),
            // Cloudflare Turnstile — token preenchido pelo callback do widget
            turnstileToken:    '',
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

        // Expõe os callbacks do Turnstile no escopo global para o widget os encontrar
        window.onTurnstileSuccess = (token) => {
            this.turnstileToken = token
        }
        window.onTurnstileExpired = () => {
            this.turnstileToken = ''
        }
    },
    methods: {
        login(force = false) {
            if (this.carregando) return
            this.carregando = true

            this.axios({
                method: 'post',
                url: 'api/login',
                data: {
                    username:         this.username,
                    password:         this.password,
                    force,
                    turnstileToken:   this.turnstileToken,
                },
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
                    this.resetarTurnstile()
                }
            })
            .catch(() => {
                this.alertMessage = 'Erro ao realizar login.'
                this.showAlert('error')
                this.resetarTurnstile()
            })
            .finally(() => {
                this.carregando = false
            })
        },

        verifyCode() {
            if (!this.twoFactorCode || this.twoFactorCode.length < 6) {
                this.alertMessage = 'Digite o código de 6 dígitos.'
                this.showAlert('error')
                return
            }

            if (this.carregando) return
            this.carregando = true

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
            .catch(() => {
                this.alertMessage = 'Erro ao verificar código.'
                this.showAlert('error')
            })
            .finally(() => {
                this.carregando = false
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

        // Reseta o widget do Turnstile após falha de login
        // (o token é de uso único — precisa ser renovado a cada tentativa)
        resetarTurnstile() {
            this.turnstileToken = ''
            if (window.turnstile) {
                window.turnstile.reset('#turnstile-widget')
            }
        },
    }
}
</script>

<style scoped>

/* ═══════════════════════════════════════════════
   PÁGINA — fundo com gradiente animado
═══════════════════════════════════════════════ */
.login-page {
    min-height: 100vh;
    display: flex;
    align-items: center;
    justify-content: center;
    background: linear-gradient(135deg, #071e26 0%, #0d3442 40%, #124C60 70%, #1a6880 100%);
    position: relative;
    overflow: hidden;
    padding: 24px;
}

/* ─── Orbes decorativas de fundo ─────────────── */
.login-orb {
    position: absolute;
    border-radius: 50%;
    filter: blur(80px);
    opacity: 0.15;
    pointer-events: none;
}
.login-orb--1 {
    width: 500px; height: 500px;
    background: #3bb8d8;
    top: -120px; left: -120px;
    animation: orbFloat 12s ease-in-out infinite;
}
.login-orb--2 {
    width: 400px; height: 400px;
    background: #0d8ca8;
    bottom: -100px; right: -80px;
    animation: orbFloat 16s ease-in-out infinite reverse;
}
.login-orb--3 {
    width: 250px; height: 250px;
    background: #ffffff;
    top: 50%; right: 20%;
    animation: orbFloat 20s ease-in-out infinite;
}
@keyframes orbFloat {
    0%, 100% { transform: translateY(0px) scale(1); }
    50%       { transform: translateY(-30px) scale(1.05); }
}

/* ═══════════════════════════════════════════════
   CARD PRINCIPAL — glassmorphism
═══════════════════════════════════════════════ */
.login-card {
    position: relative;
    z-index: 10;
    width: 100%;
    max-width: 420px;
    background: rgba(255, 255, 255, 0.97);
    border-radius: 24px;
    padding: 44px 40px 28px;
    box-shadow:
        0 32px 80px rgba(0, 0, 0, 0.35),
        0 0 0 1px rgba(255, 255, 255, 0.12);
    animation: cardEntrada 0.5s cubic-bezier(0.16, 1, 0.3, 1) both;
}
.login-card--2fa {
    max-width: 440px;
}
@keyframes cardEntrada {
    from { opacity: 0; transform: translateY(28px) scale(0.97); }
    to   { opacity: 1; transform: translateY(0)    scale(1); }
}

/* ─── Logo ───────────────────────────────────── */
.login-logo {
    text-align: center;
    margin-bottom: 24px;
}
.login-logo img {
    height: 120px;
    width: auto;
    max-width: 100%;
    object-fit: contain;
    filter: drop-shadow(0 4px 16px rgba(18, 76, 96, 0.15));
}

/* ─── Título e subtítulo ─────────────────────── */
.login-titulo {
    font-size: 1.45rem;
    font-weight: 700;
    color: #0d3442;
    text-align: center;
    margin-bottom: 6px;
}
.login-subtitulo {
    font-size: 0.85rem;
    color: #7a9aaa;
    text-align: center;
    margin-bottom: 28px;
}

/* ═══════════════════════════════════════════════
   CAMPOS DE INPUT
═══════════════════════════════════════════════ */
.login-campo {
    position: relative;
    margin-bottom: 16px;
}
.login-campo__icone {
    position: absolute;
    left: 14px;
    top: 50%;
    transform: translateY(-50%);
    color: #7a9aaa;
    font-size: 1rem;
    pointer-events: none;
}
.login-campo__input {
    width: 100%;
    height: 48px;
    padding: 0 16px 0 42px;
    border: 1.5px solid #dde8ee;
    border-radius: 12px;
    font-size: 0.95rem;
    color: #0d3442;
    background: #f7fbfd;
    outline: none;
    transition: border-color 0.2s, box-shadow 0.2s, background 0.2s;
}
.login-campo__input::placeholder { color: #aabfc8; }
.login-campo__input:focus {
    border-color: #124C60;
    background: #ffffff;
    box-shadow: 0 0 0 3px rgba(18, 76, 96, 0.1);
}
.login-campo__input:disabled {
    opacity: 0.55;
    cursor: not-allowed;
}

/* ═══════════════════════════════════════════════
   TURNSTILE
═══════════════════════════════════════════════ */
.login-turnstile {
    display: flex;
    justify-content: center;
    margin: 20px 0 16px;
}

/* ═══════════════════════════════════════════════
   BOTÕES
═══════════════════════════════════════════════ */
.login-btn {
    width: 100%;
    height: 50px;
    border: none;
    border-radius: 12px;
    font-size: 0.95rem;
    font-weight: 600;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: transform 0.15s, box-shadow 0.15s, opacity 0.15s;
    margin-top: 4px;
}
.login-btn:disabled {
    opacity: 0.5;
    cursor: not-allowed;
    transform: none !important;
}

/* Primário */
.login-btn--primary {
    background: linear-gradient(135deg, #124C60, #1a6880);
    color: #ffffff;
    box-shadow: 0 6px 20px rgba(18, 76, 96, 0.35);
}
.login-btn--primary:not(:disabled):hover {
    transform: translateY(-2px);
    box-shadow: 0 10px 28px rgba(18, 76, 96, 0.45);
}
.login-btn--primary:not(:disabled):active {
    transform: translateY(0);
}

/* Outline */
.login-btn--outline {
    background: transparent;
    color: #124C60;
    border: 1.5px solid #124C60;
    width: auto;
    padding: 0 20px;
    height: 40px;
}
.login-btn--outline:hover { background: rgba(18,76,96,0.06); }

/* Warning */
.login-btn--warning {
    background: linear-gradient(135deg, #d97706, #f59e0b);
    color: #ffffff;
    width: auto;
    padding: 0 20px;
    height: 40px;
    box-shadow: 0 4px 12px rgba(217, 119, 6, 0.3);
}
.login-btn--warning:hover { transform: translateY(-1px); }

/* ═══════════════════════════════════════════════
   AVISO DE SESSÃO ATIVA
═══════════════════════════════════════════════ */
.login-sessao-aviso {
    background: #fffbeb;
    border: 1px solid #fcd34d;
    border-radius: 12px;
    padding: 16px;
    margin-bottom: 16px;
}
.login-sessao-aviso__titulo {
    font-weight: 700;
    color: #92400e;
    margin-bottom: 8px;
    font-size: 0.9rem;
}
.login-sessao-aviso__texto {
    font-size: 0.82rem;
    color: #78350f;
    margin-bottom: 14px;
}

/* ═══════════════════════════════════════════════
   2FA
═══════════════════════════════════════════════ */
.login-voltar {
    background: none;
    border: none;
    color: #7a9aaa;
    font-size: 0.82rem;
    cursor: pointer;
    padding: 0;
    margin-bottom: 18px;
    display: flex;
    align-items: center;
    transition: color 0.15s;
}
.login-voltar:hover { color: #124C60; }

.login-2fa-info {
    background: linear-gradient(135deg, #e8f4f8, #d0eaf3);
    border-radius: 12px;
    padding: 18px;
    text-align: center;
    margin-bottom: 24px;
    color: #0d3442;
    font-size: 0.88rem;
}
.login-2fa-info strong { color: #124C60; font-size: 0.95rem; }

.login-codigo-wrapper {
    margin-bottom: 16px;
}
.login-codigo-input {
    width: 100%;
    height: 64px;
    text-align: center;
    font-size: 2rem;
    font-weight: 700;
    letter-spacing: 14px;
    color: #0d3442;
    border: 2px solid #dde8ee;
    border-radius: 14px;
    background: #f7fbfd;
    outline: none;
    transition: border-color 0.2s, box-shadow 0.2s;
}
.login-codigo-input::placeholder {
    color: #c5d8e0;
    letter-spacing: 10px;
}
.login-codigo-input:focus {
    border-color: #124C60;
    box-shadow: 0 0 0 3px rgba(18, 76, 96, 0.12);
    background: #ffffff;
}

.login-2fa-rodape {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 20px;
    font-size: 0.8rem;
    color: #7a9aaa;
}
.login-reenviar {
    background: none;
    border: none;
    font-size: 0.8rem;
    color: #124C60;
    cursor: pointer;
    font-weight: 600;
    padding: 0;
    transition: opacity 0.15s;
}
.login-reenviar:disabled {
    color: #aabfc8;
    cursor: default;
}

/* ═══════════════════════════════════════════════
   ALERTA DE TOPO
═══════════════════════════════════════════════ */
.login-alert-topo {
    position: fixed;
    top: 20px;
    left: 50%;
    transform: translateX(-50%);
    background: #fffbeb;
    border: 1px solid #fcd34d;
    color: #92400e;
    padding: 12px 24px;
    border-radius: 12px;
    font-size: 0.88rem;
    font-weight: 500;
    box-shadow: 0 8px 24px rgba(0,0,0,0.15);
    z-index: 9999;
    white-space: nowrap;
    animation: cardEntrada 0.3s ease both;
}

/* ═══════════════════════════════════════════════
   RODAPÉ DO CARD
═══════════════════════════════════════════════ */
.login-card__rodape {
    text-align: center;
    font-size: 0.72rem;
    color: #aabfc8;
    margin-top: 28px;
    padding-top: 16px;
    border-top: 1px solid #eef3f6;
}

</style>
