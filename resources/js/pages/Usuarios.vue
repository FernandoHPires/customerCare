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
                        <td>{{ row.phone }}</td>
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
                                v-model="form.email"
                                placeholder="email@exemplo.com"
                            />
                        </div>

                        <!-- Telefone e Cliente -->
                        <div class="col-md-6">
                            <label class="form-label">Telefone</label>
                            <input
                                type="text"
                                class="form-control"
                                v-model="form.phone"
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
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">
                                Confirmar Senha <span class="text-danger">*</span>
                            </label>
                            <input
                                :type="showPassword ? 'text' : 'password'"
                                class="form-control"
                                v-model="form.passwordConfirm"
                                placeholder="Repetir senha"
                                autocomplete="new-password"
                            />
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
        };
    },
    computed: {
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
                isAdmin: false,
                password: '',
                passwordConfirm: '',
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
                .catch((error) => console.error(error))
                .finally(() => this.hidePreLoader());
        },

        getClientes() {
            this.axios({ method: 'get', url: '/web/clientes' })
                .then((response) => {
                    if (this.checkApiResponse(response)) {
                        this.clientes = response.data.data;
                    }
                })
                .catch((error) => console.error(error));
        },

        getPerfis() {
            this.axios({ method: 'get', url: '/web/perfis' })
                .then((response) => {
                    if (this.checkApiResponse(response)) {
                        this.perfis = response.data.data;
                    }
                })
                .catch((error) => console.error(error));
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
                    isAdmin:         !!row.isAdmin,
                    password:        '',
                    passwordConfirm: '',
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
            if (this.form.password && this.form.password !== this.form.passwordConfirm) {
                this.alertMessage = 'As senhas não coincidem.';
                this.showAlert('error');
                return;
            }

            this.showPreLoader();

            const payload = { ...this.form, action: this.modalAction };
            delete payload.passwordConfirm;

            this.axios({ method: 'post', url: '/web/usuario', data: payload })
                .then((response) => {
                    if (this.checkApiResponse(response)) {
                        this.alertMessage =
                            this.modalAction === 'Adicionar'
                                ? 'Usuário cadastrado com sucesso!'
                                : 'Usuário atualizado com sucesso!';
                        this.showAlert('success');
                        this.closeModal();
                        this.getUsuarios();
                    } else {
                        this.alertMessage = 'Erro ao salvar usuário.';
                        this.showAlert('error');
                    }
                })
                .catch((error) => console.error(error))
                .finally(() => this.hidePreLoader());
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
                    .catch((error) => console.error(error))
                    .finally(() => this.hidePreLoader());
            }
        },
    },
};
</script>

<style scoped>
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

</style>
