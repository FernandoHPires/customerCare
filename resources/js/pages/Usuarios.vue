<template>
    <div class="card">
        <div class="card-header">
            <div class="d-flex align-items-center">
                <h5 class="mb-0">Usuários</h5>
                <div class="ms-auto pe-2">
                    <button
                        type="button"
                        class="btn btn-primary"
                        @click="openModal('Adicionar', {})"
                    >
                        <i class="bi-plus-lg me-1"></i>Novo Usuário
                    </button>
                </div>
                <div>
                    <div class="input-group">
                        <span class="input-group-text"><i class="bi-search"></i></span>
                        <input
                            type="text"
                            class="form-control"
                            placeholder="Search"
                            v-model="search"
                        />
                    </div>
                </div>
            </div>
        </div>

        <div class="table-responsive usuarios-table">
            <table class="table table-hover mb-0">
                <thead>
                    <tr>
                        <th>Nome</th>
                        <th>Usuário</th>
                        <th>E-mail</th>
                        <th>Telefone</th>
                        <th>Cliente</th>
                        <th></th>
                    </tr>
                </thead>

                <tbody v-if="filteredData.length === 0">
                    <tr>
                        <td colspan="6">Nenhum usuário encontrado.</td>
                    </tr>
                </tbody>

                <tbody v-else>
                    <tr v-for="row in filteredData" :key="row.id">
                        <td>{{ row.fullName }}</td>
                        <td>{{ row.username }}</td>
                        <td>{{ row.email }}</td>
                        <td>{{ formatPhone(row.phone) }}</td>
                        <td>{{ row.companyName }}</td>
                        <td class="text-end">
                            <button
                                type="button"
                                class="btn btn-primary btn-sm"
                                @click="openModal('Editar', row)"
                            >
                                <i class="bi-pencil me-1"></i>Editar
                            </button>
                            <button
                                type="button"
                                class="btn btn-outline-danger btn-sm ms-2"
                                @click="confirmDelete(row)"
                            >
                                <i class="bi-trash me-1"></i>Deletar
                            </button>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Modal Adicionar / Editar -->
    <div
        class="modal fade"
        id="usuarioModal"
        data-coreui-backdrop="static"
        data-coreui-keyboard="false"
        tabindex="-1"
        aria-hidden="true"
        style="display: none;"
    >
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">{{ modalAction }} Usuário</h5>
                    <button type="button" class="btn-close" @click="closeModal()"></button>
                </div>

                <div class="modal-body">
                    <div class="row g-3">

                        <!-- Nome e Sobrenome -->
                        <div class="col-md-6">
                            <label class="form-label">Nome <span class="text-danger">*</span></label>
                            <input
                                type="text"
                                class="form-control"
                                v-model="form.firstName"
                                placeholder="Nome"
                            />
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Sobrenome <span class="text-danger">*</span></label>
                            <input
                                type="text"
                                class="form-control"
                                v-model="form.lastName"
                                placeholder="Sobrenome"
                            />
                        </div>

                        <!-- E-mail -->
                        <div class="col-md-12">
                            <label class="form-label">E-mail <span class="text-danger">*</span></label>
                            <input
                                type="email"
                                class="form-control"
                                :class="form.email && !emailValido ? 'is-invalid' : ''"
                                v-model="form.email"
                                placeholder="email@exemplo.com"
                            />
                            <div v-if="form.email && !emailValido" class="invalid-feedback">
                                Informe um e-mail válido.
                            </div>
                        </div>

                        <!-- Telefone e Cliente -->
                        <div class="col-md-6">
                            <label class="form-label">Telefone</label>
                            <input
                                type="text"
                                class="form-control"
                                v-model="form.phone"
                                v-mask="'phone'"
                                placeholder="(00) 00000-0000"
                            />
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Cliente <span class="text-danger">*</span></label>
                            <SearchableSelect
                                v-model="form.companyId"
                                :options="clienteOptions"
                                placeholder="Pesquisar cliente..."
                            />
                        </div>

                        <!-- Acesso -->
                        <div class="col-12">
                            <hr class="my-1" />
                            <small class="text-muted">
                                {{ modalAction === 'Adicionar' ? 'Defina o usuário e a senha de acesso.' : 'Deixe a senha em branco para manter a senha atual.' }}
                            </small>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Usuário <span class="text-danger">*</span></label>
                            <input
                                type="text"
                                class="form-control"
                                v-model="form.username"
                                placeholder="ex: joao.silva"
                                autocomplete="off"
                            />
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Perfil <span class="text-danger">*</span></label>
                            <SearchableSelect
                                v-model="form.perfilId"
                                :options="perfilOptions"
                                placeholder="Pesquisar perfil..."
                            />
                        </div>
                        <!-- Senha -->
                        <div class="col-md-6">
                            <label class="form-label">
                                Senha <span class="text-danger">*</span>
                            </label>
                            <div class="input-group">
                                <input
                                    :type="showPassword ? 'text' : 'password'"
                                    class="form-control"
                                    v-model="form.password"
                                    placeholder="Senha"
                                    autocomplete="new-password"
                                />
                                <button
                                    class="btn btn-outline-secondary"
                                    type="button"
                                    @click="showPassword = !showPassword"
                                >
                                    <i :class="showPassword ? 'bi-eye-slash' : 'bi-eye'"></i>
                                </button>
                            </div>

                            <!-- Barra de força -->
                            <div v-if="form.password" class="mt-2">
                                <div class="d-flex gap-1 mb-1">
                                    <div v-for="i in 4" :key="i" class="forca-barra" :class="forcaClass(i)"></div>
                                </div>
                                <small :class="forcaTextoClass">{{ forcaTexto }}</small>
                            </div>

                            <!-- Requisitos -->
                            <div v-if="form.password" class="requisitos mt-1">
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

                        <!-- Confirmar Senha -->
                        <div class="col-md-6">
                            <label class="form-label">
                                Confirmar Senha <span class="text-danger">*</span>
                            </label>
                            <input
                                :type="showPassword ? 'text' : 'password'"
                                class="form-control"
                                :class="form.passwordConfirm && form.password !== form.passwordConfirm ? 'is-invalid' : ''"
                                v-model="form.passwordConfirm"
                                placeholder="Repetir senha"
                                autocomplete="new-password"
                            />
                            <div v-if="form.passwordConfirm && form.password !== form.passwordConfirm" class="invalid-feedback">
                                As senhas não coincidem.
                            </div>
                        </div>

                        <!-- Autenticação em dois fatores -->
                        <div class="col-12">
                            <div class="two-factor-box" :class="{ 'two-factor-disabled': !emailValido }">
                                <div class="form-check form-switch mb-1">
                                    <input
                                        class="form-check-input"
                                        type="checkbox"
                                        id="chkTwoFactor"
                                        v-model="form.twoFactorEnabled"
                                        :disabled="!emailValido"
                                    />
                                    <label class="form-check-label" for="chkTwoFactor">
                                        <i class="bi-shield-lock me-1"></i>
                                        Autenticação em dois fatores (2FA por e-mail)
                                    </label>
                                </div>
                                <small>
                                    <span v-if="!emailValido">
                                        <i class="bi-info-circle me-1"></i>Informe um e-mail válido para habilitar o 2FA.
                                    </span>
                                    <span v-else class="text-muted">
                                        Quando ativo, um código será enviado ao e-mail do usuário a cada login.
                                    </span>
                                </small>
                            </div>
                        </div>

                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-dark" @click="closeModal()">
                        <i class="bi-x-lg me-1"></i>Cancelar
                    </button>
                    <button type="button" class="btn btn-primary" @click="saveUsuario()">
                        <i class="bi-check-lg me-1"></i>Salvar
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Dialog de confirmação de exclusão -->
    <ConfirmationDialog
        :event="event"
        :message="dialogMessage"
        :type="dialogType"
        parentModalId="usuarioModal"
        key="usuarioModal"
        @return="dialogOnReturn"
    />

    <!-- Modal de Convite -->
    <div v-if="showConviteModal" class="modal-overlay-convite" @click.self="showConviteModal = false">
        <div class="convite-modal">
            <div class="convite-icon">
                <i class="bi bi-envelope-paper"></i>
            </div>
            <h5 class="convite-titulo">Enviar acesso por e-mail?</h5>
            <p class="convite-texto">
                Deseja enviar um e-mail para <strong>{{ conviteNome }}</strong>
                com o link de acesso ao sistema?
            </p>
            <div class="convite-acoes">
                <button class="btn btn-outline-secondary" @click="showConviteModal = false" :disabled="enviandoConvite">
                    Agora não
                </button>
                <button class="btn btn-primary" @click="enviarConvite()" :disabled="enviandoConvite">
                    <span v-if="enviandoConvite">
                        <span class="spinner-border spinner-border-sm me-1"></span>Enviando...
                    </span>
                    <span v-else><i class="bi bi-send me-1"></i>Enviar e-mail</span>
                </button>
            </div>
        </div>
    </div>

</template>

<script>
import { util } from '../mixins/util';
import SearchableSelect from '../components/SearchableSelect.vue';
import ConfirmationDialog from '../components/ConfirmationDialog.vue';

export default {
    mixins: [util],
    components: { SearchableSelect, ConfirmationDialog },
    name: 'usuarios-page',
    data() {
        return {
            usuarios: [],
            clientes: [],
            perfis: [],
            search: '',
            modalAction: 'Adicionar',
            form: this.emptyForm(),
            showPassword: false,
            usuarioDel: {},
            event: '',
            dialogMessage: '',
            dialogType: '',
            // Convite
            showConviteModal: false,
            conviteUserId: null,
            conviteNome: '',
            enviandoConvite: false,
        };
    },
    computed: {
        req() {
            const s = this.form.password;
            return {
                minimo:    s.length >= 8,
                maiuscula: /[A-Z]/.test(s),
                numero:    /[0-9]/.test(s),
                especial:  /[^A-Za-z0-9]/.test(s),
            };
        },
        forca() {
            return Object.values(this.req).filter(Boolean).length;
        },
        forcaTexto() {
            return ['', 'Fraca', 'Razoável', 'Boa', 'Forte'][this.forca];
        },
        forcaTextoClass() {
            return ['', 'text-danger', 'text-warning', 'text-info', 'text-success'][this.forca];
        },
        emailValido() {
            return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(this.form.email);
        },
        filteredData() {
            const search = this.search && this.search.toLowerCase();
            if (!search) return this.usuarios;
            return this.usuarios.filter((row) =>
                Object.values(row).some((val) =>
                    String(val).toLowerCase().includes(search)
                )
            );
        },
        clienteOptions() {
            return this.clientes.map((c) => ({ id: c.id, label: c.nome }));
        },
        perfilOptions() {
            return this.perfis.map((p) => ({ id: p.id, label: p.nome }));
        },
    },
    mounted() {
        this.getUsuarios();
        this.getClientes();
        this.getPerfis();
    },
    methods: {
        emptyForm() {
            return {
                id: null,
                firstName: '',
                lastName: '',
                username: '',
                email: '',
                phone: '',
                dept: '',
                companyId: null,
                perfilId: null,
                isAdmin:          false,
                twoFactorEnabled: false,
                password:         '',
                passwordConfirm:  '',
            };
        },

        getUsuarios() {
            this.showPreLoader();
            this.axios({ method: 'get', url: '/web/usuarios' })
                .then((response) => {
                    if (this.checkApiResponse(response)) {
                        this.usuarios = response.data.data;
                    } else {
                        this.alertMessage = 'Erro ao carregar usuários.';
                        this.showAlert('error');
                    }
                })
                .catch(() => {})
                .finally(() => this.hidePreLoader());
        },

        getClientes() {
            this.axios({ method: 'get', url: '/web/clientes' })
                .then((response) => {
                    if (this.checkApiResponse(response)) {
                        this.clientes = response.data.data;
                    }
                })
                .catch(() => {});
        },

        getPerfis() {
            this.axios({ method: 'get', url: '/web/perfis' })
                .then((response) => {
                    if (this.checkApiResponse(response)) {
                        this.perfis = response.data.data;
                    }
                })
                .catch(() => {});
        },

        openModal(action, row) {
            this.modalAction = action;
            this.showPassword = false;
            if (action === 'Editar') {
                this.form = {
                    id:              row.id,
                    firstName:       row.firstName,
                    lastName:        row.lastName,
                    username:        row.username,
                    email:           row.email,
                    phone:           row.phone,
                    dept:            row.dept,
                    companyId:       row.companyId,
                    perfilId:        row.perfilId,
                    isAdmin:          !!row.isAdmin,
                    twoFactorEnabled: !!row.twoFactorEnabled,
                    password:         '',
                    passwordConfirm:  '',
                };
            } else {
                this.form = this.emptyForm();
            }
            this.showModal('usuarioModal');
        },

        closeModal() {
            this.hideModal('usuarioModal');
            this.hideBackdrop();
        },

        saveUsuario() {
            // Validações
            if (!this.form.firstName || !this.form.lastName) {
                this.alertMessage = 'Nome e Sobrenome são obrigatórios.';
                this.showAlert('error');
                return;
            }
            if (!this.form.username) {
                this.alertMessage = 'Usuário de acesso é obrigatório.';
                this.showAlert('error');
                return;
            }
            if (!this.form.email) {
                this.alertMessage = 'E-mail é obrigatório.';
                this.showAlert('error');
                return;
            }
            if (!this.emailValido) {
                this.alertMessage = 'Informe um e-mail válido.';
                this.showAlert('error');
                return;
            }
            if (this.form.twoFactorEnabled && !this.emailValido) {
                this.alertMessage = 'Informe um e-mail válido para ativar o 2FA.';
                this.showAlert('error');
                return;
            }
            if (!this.form.companyId) {
                this.alertMessage = 'Selecione um cliente.';
                this.showAlert('error');
                return;
            }
            if (!this.form.perfilId) {
                this.alertMessage = 'Selecione um perfil de acesso.';
                this.showAlert('error');
                return;
            }
            if (this.modalAction === 'Adicionar' && !this.form.password) {
                this.alertMessage = 'Senha é obrigatória para novos usuários.';
                this.showAlert('error');
                return;
            }
            if (this.form.password) {
                if (this.forca < 4) {
                    this.alertMessage = 'A senha não atende todos os requisitos de segurança.';
                    this.showAlert('error');
                    return;
                }
                if (this.form.password !== this.form.passwordConfirm) {
                    this.alertMessage = 'As senhas não coincidem.';
                    this.showAlert('error');
                    return;
                }
            }

            this.showPreLoader();

            const payload = { ...this.form, action: this.modalAction };
            delete payload.passwordConfirm;

            const senhaAlterada  = !!this.form.password;
            const emailUsuario   = this.form.email;
            const nomeUsuario    = (this.form.firstName + ' ' + this.form.lastName).trim();
            const acaoModal      = this.modalAction;

            let conviteData = null;

            this.axios({ method: 'post', url: '/web/usuario', data: payload })
                .then((response) => {
                    if (this.checkApiResponse(response)) {
                        this.alertMessage =
                            acaoModal === 'Adicionar'
                                ? 'Usuário cadastrado com sucesso!'
                                : 'Usuário atualizado com sucesso!';
                        this.showAlert('success');
                        this.closeModal();
                        this.getUsuarios();

                        // Guarda os dados para abrir o modal de convite no finally (após preloader sumir)
                        if (acaoModal === 'Adicionar' || (senhaAlterada && emailUsuario)) {
                            conviteData = {
                                userId : response.data.data?.userId,
                                nome   : nomeUsuario,
                            };
                        }
                    } else {
                        this.alertMessage = response.data.message || 'Erro ao salvar usuário.';
                        this.showAlert('error');
                    }
                })
                .catch(() => {})
                .finally(() => {
                    this.hidePreLoader();
                    // Abre o modal de convite somente depois que o preloader foi removido
                    if (conviteData) {
                        this.conviteUserId    = conviteData.userId;
                        this.conviteNome      = conviteData.nome;
                        this.showConviteModal = true;
                    }
                });
        },

        enviarConvite() {
            this.enviandoConvite = true
            this.axios({ method: 'post', url: '/web/usuario/' + this.conviteUserId + '/enviar-convite' })
                .then((response) => {
                    if (this.checkApiResponse(response)) {
                        this.alertMessage = 'E-mail de acesso enviado com sucesso!'
                        this.showAlert('success')
                    } else {
                        this.alertMessage = response.data.message || 'Erro ao enviar e-mail.'
                        this.showAlert('error')
                    }
                })
                .catch(() => {
                    this.alertMessage = 'Erro ao enviar e-mail.'
                    this.showAlert('error')
                })
                .finally(() => {
                    this.enviandoConvite = false
                    this.showConviteModal = false
                })
        },

        forcaClass(i) {
            if (i > this.forca) return 'barra-vazia';
            return ['', 'barra-fraca', 'barra-razoavel', 'barra-boa', 'barra-forte'][this.forca];
        },

        confirmDelete(row) {
            this.usuarioDel = row;
            this.dialogMessage = 'Tem certeza que deseja excluir este usuário?';
            this.event = 'destroy';
            this.dialogType = 'danger';
            this.showModal('confirmationDialogusuarioModal');
        },

        dialogOnReturn(event, returnMessage) {
            if (event === 'destroy' && returnMessage === 'confirmed') {
                this.showPreLoader();
                this.axios({
                    method: 'delete',
                    url: '/web/usuario/' + this.usuarioDel.id,
                })
                    .then((response) => {
                        if (this.checkApiResponse(response)) {
                            this.alertMessage = 'Usuário excluído com sucesso!';
                            this.showAlert('success');
                            this.getUsuarios();
                        } else {
                            this.alertMessage = 'Erro ao excluir usuário.';
                            this.showAlert('error');
                        }
                    })
                    .catch(() => {})
                    .finally(() => this.hidePreLoader());
            }
        },
    },
};
</script>

<style scoped>
/* ---- Modal Convite ---- */
.modal-overlay-convite {
    position: fixed;
    inset: 0;
    background: rgba(0, 0, 0, 0.5);
    z-index: 1060;
    display: flex;
    align-items: center;
    justify-content: center;
}
.convite-modal {
    background: #fff;
    border-radius: 12px;
    padding: 36px 32px;
    width: 420px;
    max-width: 90vw;
    text-align: center;
    box-shadow: 0 8px 32px rgba(0,0,0,0.18);
}
.convite-icon {
    font-size: 48px;
    color: #124C60;
    margin-bottom: 12px;
}
.convite-titulo {
    font-weight: 700;
    font-size: 18px;
    margin-bottom: 10px;
    color: #1a1a2e;
}
.convite-texto {
    font-size: 14px;
    color: #555;
    margin-bottom: 24px;
    line-height: 1.6;
}
.convite-acoes {
    display: flex;
    gap: 12px;
    justify-content: center;
}

.modal-body .form-label {
    margin-bottom: 2px;
    font-size: 0.875rem;
}

.usuarios-table {
    max-height: calc(100vh - 160px);
    overflow-y: auto;
}

.usuarios-table table thead th {
    position: sticky;
    top: 0;
    z-index: 2;
    background: #f8f9fa;
    border-bottom: 2px solid #dee2e6;
    font-size: 13px;
    font-weight: 600;
    padding: 12px 10px;
    white-space: nowrap;
}

.usuarios-table table tbody td {
    vertical-align: middle;
    padding: 10px;
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

.requisitos { line-height: 1.8; }

.two-factor-box {
    background: #f8f9fa;
    border: 1px solid #dee2e6;
    border-radius: 6px;
    padding: 10px 14px;
    transition: opacity 0.2s;
}

.two-factor-disabled {
    opacity: 0.45;
    pointer-events: none;
    background: #f1f3f5;
    border-color: #dee2e6;
}
</style>
