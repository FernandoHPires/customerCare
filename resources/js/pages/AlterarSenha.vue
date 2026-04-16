<template>
    <div class="d-flex justify-content-center">
        <div class="card alterar-senha-card">

            <!-- Banner de reset obrigatório -->
            <div v-if="resetObrigatorio" class="alert alert-warning rounded-0 mb-0 d-flex align-items-center gap-2">
                <i class="bi-shield-lock-fill fs-5"></i>
                <span>Você precisa definir uma nova senha antes de continuar usando o sistema.</span>
            </div>

            <div class="card-header">
                <h5 class="mb-0"><i class="bi-lock me-2"></i>Alterar Senha</h5>
            </div>

            <div class="card-body">
                <div class="row g-3">

                    <!-- Senha Atual -->
                    <div class="col-12">
                        <label class="form-label">Senha Atual <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <input
                                :type="show.atual ? 'text' : 'password'"
                                class="form-control"
                                v-model="form.senhaAtual"
                                placeholder="Digite sua senha atual"
                                autocomplete="current-password"
                            />
                            <button class="btn btn-outline-secondary" type="button" @click="show.atual = !show.atual">
                                <i :class="show.atual ? 'bi-eye-slash' : 'bi-eye'"></i>
                            </button>
                        </div>
                    </div>

                    <div class="col-12"><hr class="my-1" /></div>

                    <!-- Nova Senha -->
                    <div class="col-12">
                        <label class="form-label">Nova Senha <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <input
                                :type="show.nova ? 'text' : 'password'"
                                class="form-control"
                                v-model="form.novaSenha"
                                placeholder="Digite a nova senha"
                                autocomplete="new-password"
                            />
                            <button class="btn btn-outline-secondary" type="button" @click="show.nova = !show.nova">
                                <i :class="show.nova ? 'bi-eye-slash' : 'bi-eye'"></i>
                            </button>
                        </div>

                        <!-- Barra de força -->
                        <div v-if="form.novaSenha" class="mt-2">
                            <div class="d-flex gap-1 mb-1">
                                <div
                                    v-for="i in 4"
                                    :key="i"
                                    class="forca-barra"
                                    :class="forcaClass(i)"
                                ></div>
                            </div>
                            <small :class="forcaTextoClass">{{ forcaTexto }}</small>
                        </div>

                        <!-- Confirmar Nova Senha -->
                        <div class="mt-3">
                            <label class="form-label">Confirmar Nova Senha <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <input
                                    :type="show.confirmar ? 'text' : 'password'"
                                    class="form-control"
                                    :class="form.confirmarSenha && form.novaSenha !== form.confirmarSenha ? 'is-invalid' : ''"
                                    v-model="form.confirmarSenha"
                                    placeholder="Repita a nova senha"
                                    autocomplete="new-password"
                                />
                                <button class="btn btn-outline-secondary" type="button" @click="show.confirmar = !show.confirmar">
                                    <i :class="show.confirmar ? 'bi-eye-slash' : 'bi-eye'"></i>
                                </button>
                            </div>
                            <small
                                v-if="form.confirmarSenha && form.novaSenha !== form.confirmarSenha"
                                class="text-danger"
                            >
                                As senhas não coincidem.
                            </small>
                        </div>

                        <!-- Requisitos -->
                        <div class="requisitos mt-2">
                            <small :class="req.minimo ? 'text-success' : 'text-muted'">
                                <i :class="req.minimo ? 'bi-check-circle-fill' : 'bi-circle'"></i>
                                Mínimo 8 caracteres
                            </small><br>
                            <small :class="req.maiuscula ? 'text-success' : 'text-muted'">
                                <i :class="req.maiuscula ? 'bi-check-circle-fill' : 'bi-circle'"></i>
                                Uma letra maiúscula
                            </small><br>
                            <small :class="req.numero ? 'text-success' : 'text-muted'">
                                <i :class="req.numero ? 'bi-check-circle-fill' : 'bi-circle'"></i>
                                Um número
                            </small><br>
                            <small :class="req.especial ? 'text-success' : 'text-muted'">
                                <i :class="req.especial ? 'bi-check-circle-fill' : 'bi-circle'"></i>
                                Um caractere especial (!@#$%...)
                            </small>
                        </div>
                    </div>


                </div>
            </div>

            <div class="card-footer d-flex justify-content-end gap-2">
                <button v-if="!resetObrigatorio" type="button" class="btn btn-outline-dark" @click="limpar()">
                    <i class="bi-x-lg me-1"></i>Limpar
                </button>
                <button type="button" class="btn btn-primary" @click="salvar()">
                    <i class="bi-check-lg me-1"></i>Salvar
                </button>
            </div>
        </div>
    </div>
</template>

<script>
import { util } from '../mixins/util';

export default {
    mixins: [util],
    name: 'alterar-senha-page',
    data() {
        return {
            form: {
                senhaAtual:     '',
                novaSenha:      '',
                confirmarSenha: '',
            },
            show: {
                atual:     false,
                nova:      false,
                confirmar: false,
            },
        };
    },
    computed: {
        resetObrigatorio() {
            const user = this.getUser();
            return user && user.resetRequest;
        },
        req() {
            const s = this.form.novaSenha;
            return {
                minimo:   s.length >= 8,
                maiuscula: /[A-Z]/.test(s),
                numero:   /[0-9]/.test(s),
                especial: /[^A-Za-z0-9]/.test(s),
            };
        },
        forca() {
            return Object.values(this.req).filter(Boolean).length; // 0-4
        },
        forcaTexto() {
            return ['', 'Fraca', 'Razoável', 'Boa', 'Forte'][this.forca];
        },
        forcaTextoClass() {
            return [
                '',
                'text-danger',
                'text-warning',
                'text-info',
                'text-success',
            ][this.forca];
        },
    },
    methods: {
        forcaClass(i) {
            if (i > this.forca) return 'barra-vazia';
            return ['', 'barra-fraca', 'barra-razoavel', 'barra-boa', 'barra-forte'][this.forca];
        },

        limpar() {
            this.form = { senhaAtual: '', novaSenha: '', confirmarSenha: '' };
            this.show = { atual: false, nova: false, confirmar: false };
        },

        salvar() {
            if (!this.form.senhaAtual) {
                this.alertMessage = 'Informe a senha atual.';
                this.showAlert('error');
                return;
            }
            if (!this.form.novaSenha) {
                this.alertMessage = 'Informe a nova senha.';
                this.showAlert('error');
                return;
            }
            if (this.forca < 4) {
                this.alertMessage = 'A nova senha não atende todos os requisitos.';
                this.showAlert('error');
                return;
            }
            if (this.form.novaSenha !== this.form.confirmarSenha) {
                this.alertMessage = 'As senhas não coincidem.';
                this.showAlert('error');
                return;
            }

            this.showPreLoader();
            this.axios({
                method: 'post',
                url: '/web/alterar-senha',
                data: {
                    senhaAtual: this.form.senhaAtual,
                    novaSenha:  this.form.novaSenha,
                },
            })
                .then((response) => {
                    if (this.checkApiResponse(response)) {
                        this.alertMessage = 'Senha alterada com sucesso!';
                        this.showAlert('success');

                        if (this.resetObrigatorio) {
                            // Faz logout e força novo login com a nova senha
                            setTimeout(() => {
                                this.axios({ method: 'get', url: '/web/logout' })
                                    .finally(() => { window.location.href = '/'; });
                            }, 1500);
                        } else {
                            this.limpar();
                        }
                    } else {
                        this.alertMessage = response.data.message || 'Erro ao alterar senha.';
                        this.showAlert('error');
                    }
                })
                .catch((error) => console.error(error))
                .finally(() => this.hidePreLoader());
        },
    },
};
</script>

<style scoped>
.alterar-senha-card {
    width: 100%;
    max-width: 480px;
    margin-top: 20px;
}

.form-label {
    margin-bottom: 2px;
    font-size: 0.875rem;
}

.forca-barra {
    height: 5px;
    flex: 1;
    border-radius: 3px;
    transition: background 0.3s;
}

.barra-vazia    { background: #dee2e6; }
.barra-fraca    { background: #dc3545; }
.barra-razoavel { background: #ffc107; }
.barra-boa      { background: #0dcaf0; }
.barra-forte    { background: #198754; }

.requisitos {
    line-height: 1.8;
}
</style>
