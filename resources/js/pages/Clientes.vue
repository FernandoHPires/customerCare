<template>
    <div class="card">
        <div class="card-header">
            <div class="d-flex align-items-center">
                <h5 class="mb-0">Clientes</h5>
                <div class="ms-auto pe-2">
                    <button
                        type="button"
                        class="btn btn-primary"
                        @click="openModal('Adicionar', {})"
                    >
                        <i class="bi-plus-lg me-1"></i>Novo Cliente
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

        <div class="table-responsive clientes-table">
            <table class="table table-hover mb-0">
                <thead>
                    <tr>
                        <th>Nome</th>
                        <th>Nome Fantasia</th>
                        <th>CNPJ</th>
                        <th>E-mail</th>
                        <th>Telefone</th>
                        <th>Status</th>
                        <th></th>
                    </tr>
                </thead>

                <tbody v-if="filteredData.length === 0">
                    <tr>
                        <td colspan="7">Nenhum cliente encontrado.</td>
                    </tr>
                </tbody>

                <tbody v-else>
                    <tr v-for="row in filteredData" :key="row.id">
                        <td>{{ row.nome }}</td>
                        <td>{{ row.nomeFantasia }}</td>
                        <td>{{ formatCnpj(row.cnpj) }}</td>
                        <td>{{ row.email }}</td>
                        <td>{{ formatPhone(row.telefone) }}</td>
                        <td>
                            <span
                                class="badge"
                                :class="row.status === 'A' ? 'bg-success' : 'bg-secondary'"
                            >
                                {{ row.status === 'A' ? 'Ativo' : 'Inativo' }}
                            </span>
                        </td>
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
        id="clienteModal"
        data-coreui-backdrop="static"
        data-coreui-keyboard="false"
        tabindex="-1"
        aria-hidden="true"
        style="display: none;"
    >
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">{{ modalAction }} Cliente</h5>
                    <button type="button" class="btn-close" @click="closeModal()"></button>
                </div>

                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-md-8">
                            <label class="form-label">Nome <span class="text-danger">*</span></label>
                            <input
                                type="text"
                                class="form-control"
                                v-model="form.nome"
                                placeholder="Razão Social"
                            />
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">CNPJ</label>
                            <input
                                type="text"
                                class="form-control"
                                v-model="form.cnpj"
                                v-mask="'cnpj'"
                                placeholder="00.000.000/0000-00"
                            />
                        </div>
                        <div class="col-md-8">
                            <label class="form-label">Nome Fantasia</label>
                            <input
                                type="text"
                                class="form-control"
                                v-model="form.nomeFantasia"
                                placeholder="Nome Fantasia"
                            />
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Telefone</label>
                            <input
                                type="text"
                                class="form-control"
                                v-model="form.telefone"
                                v-mask="'phone'"
                                placeholder="(00) 00000-0000"
                            />
                        </div>
                        <div class="col-md-8">
                            <label class="form-label">E-mail</label>
                            <input
                                type="email"
                                class="form-control"
                                v-model="form.email"
                                placeholder="email@exemplo.com"
                            />
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Status</label>
                            <select class="form-select" v-model="form.status">
                                <option value="A">Ativo</option>
                                <option value="I">Inativo</option>
                            </select>
                        </div>
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-dark" @click="closeModal()">
                        <i class="bi-x-lg me-1"></i>Cancelar
                    </button>
                    <button type="button" class="btn btn-primary" @click="saveCliente()">
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
        parentModalId="clienteModal"
        key="clienteModal"
        @return="dialogOnReturn"
    />

</template>

<script>
import { util } from '../mixins/util';
import ConfirmationDialog from '../components/ConfirmationDialog.vue';

export default {
    mixins: [util],
    components: { ConfirmationDialog },
    name: 'clientes-page',
    data() {
        return {
            clientes: [],
            search: '',
            modalAction: 'Adicionar',
            form: this.emptyForm(),
            clienteDel: {},
            event: '',
            dialogMessage: '',
            dialogType: '',
        };
    },
    computed: {
        filteredData() {
            const search = this.search && this.search.toLowerCase();
            if (!search) return this.clientes;
            return this.clientes.filter((row) =>
                Object.values(row).some(
                    (val) => String(val).toLowerCase().includes(search)
                )
            );
        },
    },
    mounted() {
        this.getClientes();
    },
    methods: {
        emptyForm() {
            return {
                id: null,
                nome: '',
                nomeFantasia: '',
                cnpj: '',
                email: '',
                telefone: '',
                status: 'A',
            };
        },

        getClientes() {
            this.showPreLoader();
            this.axios({ method: 'get', url: '/web/clientes' })
                .then((response) => {
                    if (this.checkApiResponse(response)) {
                        this.clientes = response.data.data;
                    } else {
                        this.alertMessage = 'Erro ao carregar clientes.';
                        this.showAlert('error');
                    }
                })
                .catch(() => {})
                .finally(() => this.hidePreLoader());
        },

        openModal(action, row) {
            this.modalAction = action;
            if (action === 'Editar') {
                this.form = { ...row };
            } else {
                this.form = this.emptyForm();
            }
            this.showModal('clienteModal');
        },

        closeModal() {
            this.hideModal('clienteModal');
            this.hideBackdrop();
        },

        saveCliente() {
            if (!this.form.nome || this.form.nome.trim() === '') {
                this.alertMessage = 'O campo Nome é obrigatório.';
                this.showAlert('error');
                return;
            }

            this.showPreLoader();

            this.axios({
                method: 'post',
                url: '/web/cliente',
                data: { ...this.form, action: this.modalAction },
            })
                .then((response) => {
                    if (this.checkApiResponse(response)) {
                        this.alertMessage =
                            this.modalAction === 'Adicionar'
                                ? 'Cliente cadastrado com sucesso!'
                                : 'Cliente atualizado com sucesso!';
                        this.showAlert('success');
                        this.closeModal();
                        this.getClientes();
                    } else {
                        this.alertMessage = 'Erro ao salvar cliente.';
                        this.showAlert('error');
                    }
                })
                .catch(() => {})
                .finally(() => this.hidePreLoader());
        },

        confirmDelete(row) {
            this.clienteDel = row;
            this.dialogMessage = 'Tem certeza que deseja excluir este cliente?';
            this.event = 'destroy';
            this.dialogType = 'danger';
            this.showModal('confirmationDialogclienteModal');
        },

        dialogOnReturn(event, returnMessage) {
            if (event === 'destroy' && returnMessage === 'confirmed') {
                this.showPreLoader();
                this.axios({
                    method: 'delete',
                    url: '/web/cliente/' + this.clienteDel.id,
                })
                    .then((response) => {
                        if (this.checkApiResponse(response)) {
                            this.alertMessage = 'Cliente excluído com sucesso!';
                            this.showAlert('success');
                            this.getClientes();
                        } else {
                            this.alertMessage = 'Erro ao excluir cliente.';
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
.modal-body .form-label {
    margin-bottom: 2px;
    font-size: 0.875rem;
}

.clientes-table {
    max-height: calc(100vh - 160px);
    overflow-y: auto;
}

.clientes-table table thead th {
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

.clientes-table table tbody td {
    vertical-align: middle;
    padding: 10px;
}
</style>
