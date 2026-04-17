<template>
    <div class="card">
        <div class="card-header">
            <div class="d-flex align-items-center">
                <h5 class="mb-0">Perfis</h5>
                <div class="ms-auto pe-2">
                    <button
                        type="button"
                        class="btn btn-primary"
                        @click="openModal('Adicionar', {})"
                    >
                        <i class="bi-plus-lg me-1"></i>Novo Perfil
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

        <div class="table-responsive perfis-table">
            <table class="table table-hover mb-0">
                <thead>
                    <tr>
                        <th>Nome</th>
                        <th>Descrição</th>
                        <th></th>
                    </tr>
                </thead>

                <tbody v-if="filteredData.length === 0">
                    <tr>
                        <td colspan="3">Nenhum perfil encontrado.</td>
                    </tr>
                </tbody>

                <tbody v-else>
                    <tr v-for="row in filteredData" :key="row.id">
                        <td>{{ row.nome }}</td>
                        <td>{{ row.descricao }}</td>
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
        id="perfilModal"
        data-coreui-backdrop="static"
        data-coreui-keyboard="false"
        tabindex="-1"
        aria-hidden="true"
        style="display: none;"
    >
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">{{ modalAction }} Perfil</h5>
                    <button type="button" class="btn-close" @click="closeModal()"></button>
                </div>

                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-12">
                            <label class="form-label">Nome <span class="text-danger">*</span></label>
                            <input
                                type="text"
                                class="form-control"
                                v-model="form.nome"
                                placeholder="Ex: Administrador, Gestor..."
                            />
                        </div>
                        <div class="col-12">
                            <label class="form-label">Descrição</label>
                            <input
                                type="text"
                                class="form-control"
                                v-model="form.descricao"
                                placeholder="Descrição do perfil"
                            />
                        </div>
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-dark" @click="closeModal()">
                        <i class="bi-x-lg me-1"></i>Cancelar
                    </button>
                    <button type="button" class="btn btn-primary" @click="savePerfil()">
                        <i class="bi-check-lg me-1"></i>Salvar
                    </button>
                </div>
            </div>
        </div>
    </div>

    <ConfirmationDialog
        :event="event"
        :message="dialogMessage"
        :type="dialogType"
        parentModalId="perfilModal"
        key="perfilModal"
        @return="dialogOnReturn"
    />

</template>

<script>
import { util } from '../mixins/util';
import ConfirmationDialog from '../components/ConfirmationDialog.vue';

export default {
    mixins: [util],
    components: { ConfirmationDialog },
    name: 'perfis-page',
    data() {
        return {
            perfis: [],
            search: '',
            modalAction: 'Adicionar',
            form: this.emptyForm(),
            perfilDel: {},
            event: '',
            dialogMessage: '',
            dialogType: '',
        };
    },
    computed: {
        filteredData() {
            const search = this.search && this.search.toLowerCase();
            if (!search) return this.perfis;
            return this.perfis.filter((row) =>
                Object.values(row).some((val) =>
                    String(val).toLowerCase().includes(search)
                )
            );
        },
    },
    mounted() {
        this.getPerfis();
    },
    methods: {
        emptyForm() {
            return { id: null, nome: '', descricao: '' };
        },

        getPerfis() {
            this.showPreLoader();
            this.axios({ method: 'get', url: '/web/perfis' })
                .then((response) => {
                    if (this.checkApiResponse(response)) {
                        this.perfis = response.data.data;
                    } else {
                        this.alertMessage = 'Erro ao carregar perfis.';
                        this.showAlert('error');
                    }
                })
                .catch(() => {})
                .finally(() => this.hidePreLoader());
        },

        openModal(action, row) {
            this.modalAction = action;
            this.form = action === 'Editar' ? { ...row } : this.emptyForm();
            this.showModal('perfilModal');
        },

        closeModal() {
            this.hideModal('perfilModal');
            this.hideBackdrop();
        },

        savePerfil() {
            if (!this.form.nome || this.form.nome.trim() === '') {
                this.alertMessage = 'O campo Nome é obrigatório.';
                this.showAlert('error');
                return;
            }
            this.showPreLoader();
            this.axios({
                method: 'post',
                url: '/web/perfil',
                data: { ...this.form, action: this.modalAction },
            })
                .then((response) => {
                    if (this.checkApiResponse(response)) {
                        this.alertMessage =
                            this.modalAction === 'Adicionar'
                                ? 'Perfil cadastrado com sucesso!'
                                : 'Perfil atualizado com sucesso!';
                        this.showAlert('success');
                        this.closeModal();
                        this.getPerfis();
                    } else {
                        this.alertMessage = 'Erro ao salvar perfil.';
                        this.showAlert('error');
                    }
                })
                .catch(() => {})
                .finally(() => this.hidePreLoader());
        },

        confirmDelete(row) {
            this.perfilDel = row;
            this.dialogMessage = 'Tem certeza que deseja excluir este perfil?';
            this.event = 'destroy';
            this.dialogType = 'danger';
            this.showModal('confirmationDialogperfilModal');
        },

        dialogOnReturn(event, returnMessage) {
            if (event === 'destroy' && returnMessage === 'confirmed') {
                this.showPreLoader();
                this.axios({ method: 'delete', url: '/web/perfil/' + this.perfilDel.id })
                    .then((response) => {
                        if (this.checkApiResponse(response)) {
                            this.alertMessage = 'Perfil excluído com sucesso!';
                            this.showAlert('success');
                            this.getPerfis();
                        } else {
                            this.alertMessage = 'Erro ao excluir perfil.';
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

.perfis-table {
    max-height: calc(100vh - 160px);
    overflow-y: auto;
}

.perfis-table table thead th {
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

.perfis-table table tbody td {
    vertical-align: middle;
    padding: 10px;
}
</style>
