<template>
    <div class="card">
        <div class="card-header">
            <div class="d-flex align-items-center">
                <h5 class="mb-0">Filiais</h5>
                <div class="ms-auto pe-2">
                    <button
                        type="button"
                        class="btn btn-primary"
                        @click="openModal('Adicionar', {})"
                    >
                        <i class="bi-plus-lg me-1"></i>Nova Filial
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

        <div class="table-responsive filiais-table">
            <table class="table table-hover mb-0">
                <thead>
                    <tr>
                        <th>Código</th>
                        <th>Nome</th>
                        <th></th>
                    </tr>
                </thead>

                <tbody v-if="filteredData.length === 0">
                    <tr>
                        <td colspan="3">Nenhuma filial encontrada.</td>
                    </tr>
                </tbody>

                <tbody v-else>
                    <tr v-for="row in filteredData" :key="row.id">
                        <td>{{ row.codigo }}</td>
                        <td>{{ row.nome }}</td>
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

    <div
        class="modal fade"
        id="filialModal"
        data-coreui-backdrop="static"
        data-coreui-keyboard="false"
        tabindex="-1"
        aria-hidden="true"
        style="display: none;"
    >
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">{{ modalAction }} Filial</h5>
                    <button type="button" class="btn-close" @click="closeModal()"></button>
                </div>

                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label">Código <span class="text-danger">*</span></label>
                            <input
                                type="number"
                                class="form-control"
                                v-model="form.codigo"
                                min="1"
                                placeholder="Código Mega"
                                :disabled="!!form.id"
                            />
                        </div>
                        <div class="col-md-8">
                            <label class="form-label">Nome <span class="text-danger">*</span></label>
                            <input
                                type="text"
                                class="form-control"
                                v-model="form.nome"
                                placeholder="Nome da filial"
                            />
                        </div>
                        <div class="col-md-12">
                            <label class="form-label">Contas a Receber <span class="text-danger">*</span></label>
                            <select class="form-select" v-model="form.contasReceber">
                                <option value="" disabled>Selecione...</option>
                                <option value="CAR">Carteira de Recebíveis</option>
                                <option value="COR">Contas a Receber</option>
                                <option value="CRR">Carteira de Recebíveis e Contas a Receber</option>
                            </select>
                        </div>
                    </div>

                    <div v-if="form.id" class="empreendimentos mt-4">
                        <h6 class="empreendimentos-titulo">Empreendimentos</h6>
                        <div class="row g-2 align-items-end">
                            <div class="col-md-3">
                                <label class="form-label">Código <span class="text-danger">*</span></label>
                                <input
                                    type="number"
                                    class="form-control"
                                    v-model="formEmpreendimento.codigo"
                                    min="1"
                                    placeholder="Código Mega"
                                    :disabled="!!formEmpreendimento.id"
                                />
                            </div>
                            <div :class="formEmpreendimento.id ? 'col-md-5' : 'col-md-6'">
                                <label class="form-label">Descrição <span class="text-danger">*</span></label>
                                <input
                                    type="text"
                                    class="form-control"
                                    v-model="formEmpreendimento.descricao"
                                    placeholder="Descrição do empreendimento"
                                    @keyup.enter="saveEmpreendimento()"
                                />
                            </div>
                            <div v-if="formEmpreendimento.id" class="col-md-4 d-flex gap-2">
                                <button type="button" class="btn btn-outline-dark flex-fill" @click="cancelarEdicaoEmpreendimento()">
                                    <i class="bi-x-lg me-1"></i>Cancelar
                                </button>
                                <button type="button" class="btn btn-primary flex-fill" @click="saveEmpreendimento()">
                                    <i class="bi-check-lg me-1"></i>Salvar
                                </button>
                            </div>
                            <div v-else class="col-md-3">
                                <button type="button" class="btn btn-primary w-100" @click="saveEmpreendimento()">
                                    <i class="bi-plus-lg me-1"></i>Adicionar
                                </button>
                            </div>
                        </div>

                        <div class="table-responsive empreendimentos-table mt-3">
                            <table class="table table-sm table-hover mb-0">
                                <thead>
                                    <tr>
                                        <th>Código</th>
                                        <th>Descrição</th>
                                        <th></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr v-if="empreendimentos.length === 0">
                                        <td colspan="3" class="text-muted">Nenhum empreendimento cadastrado.</td>
                                    </tr>
                                    <tr v-for="row in empreendimentos" :key="row.id">
                                        <td>{{ row.codigo }}</td>
                                        <td>{{ row.descricao }}</td>
                                        <td class="text-end text-nowrap">
                                            <button
                                                type="button"
                                                class="btn btn-primary btn-sm"
                                                title="Editar empreendimento"
                                                @click="editarEmpreendimento(row)"
                                            >
                                                <i class="bi-pencil"></i>
                                            </button>
                                            <button
                                                type="button"
                                                class="btn btn-outline-danger btn-sm ms-1"
                                                title="Excluir empreendimento"
                                                @click="confirmarExclusaoEmpreendimento(row)"
                                            >
                                                <i class="bi-trash"></i>
                                            </button>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-dark" @click="closeModal()">
                        <i class="bi-x-lg me-1"></i>Cancelar
                    </button>
                    <button type="button" class="btn btn-primary" @click="saveFilial()">
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
        parentModalId="filialModal"
        key="filialModal"
        @return="dialogOnReturn"
    />

</template>

<script>
import { util } from '../mixins/util';
import ConfirmationDialog from '../components/ConfirmationDialog.vue';

export default {
    mixins: [util],
    components: { ConfirmationDialog },
    name: 'filiais-page',
    data() {
        return {
            filiais: [],
            search: '',
            modalAction: 'Adicionar',
            form: this.emptyForm(),
            empreendimentos: [],
            formEmpreendimento: { id: null, codigo: '', descricao: '' },
            empreendimentoExcluir: null,
            filialDel: {},
            event: '',
            dialogMessage: '',
            dialogType: '',
        };
    },
    computed: {
        filteredData() {
            const search = this.search && this.search.toLowerCase();
            if (!search) return this.filiais;
            return this.filiais.filter((row) =>
                [row.codigo, row.nome].some(
                    (val) => String(val).toLowerCase().includes(search)
                )
            );
        },
    },
    mounted() {
        this.getFiliais();
    },
    methods: {
        emptyForm() {
            return {
                id: null,
                codigo: '',
                nome: '',
                contasReceber: '',
            };
        },

        getFiliais() {
            this.showPreLoader();
            this.axios({ method: 'get', url: '/web/filiais' })
                .then((response) => {
                    if (this.checkApiResponse(response)) {
                        this.filiais = response.data.data;
                    } else {
                        this.alertMessage = 'Erro ao carregar filiais.';
                        this.showAlert('error');
                    }
                })
                .catch(() => {})
                .finally(() => this.hidePreLoader());
        },

        openModal(action, row) {
            this.modalAction = action;
            this.empreendimentos = [];
            this.formEmpreendimento = { id: null, codigo: '', descricao: '' };
            this.empreendimentoExcluir = null;
            if (action === 'Editar') {
                this.form = { ...row, contasReceber: row.contasReceber || '' };
                this.getEmpreendimentos();
            } else {
                this.form = this.emptyForm();
            }
            this.showModal('filialModal');
        },

        getEmpreendimentos() {
            this.axios({ method: 'get', url: '/web/filial/' + this.form.id + '/empreendimentos' })
                .then((response) => {
                    if (this.checkApiResponse(response)) {
                        this.empreendimentos = response.data.data;
                    } else {
                        this.alertMessage = 'Erro ao carregar empreendimentos.';
                        this.showAlert('error');
                    }
                })
                .catch(() => {});
        },

        saveEmpreendimento() {
            const descricao = (this.formEmpreendimento.descricao || '').trim();
            if (!this.formEmpreendimento.codigo || descricao === '') {
                this.alertMessage = 'Os campos Código e Descrição do empreendimento são obrigatórios.';
                this.showAlert('error');
                return;
            }

            this.showPreLoader();

            this.axios({
                method: 'post',
                url: '/web/filial-empreendimento',
                data: {
                    id: this.formEmpreendimento.id,
                    filialId: this.form.id,
                    codigo: this.formEmpreendimento.codigo,
                    descricao,
                },
            })
                .then((response) => {
                    if (this.checkApiResponse(response)) {
                        this.formEmpreendimento = { id: null, codigo: '', descricao: '' };
                        this.getEmpreendimentos();
                    } else {
                        this.alertMessage = response.data.message || 'Erro ao salvar empreendimento.';
                        this.showAlert('error');
                    }
                })
                .catch(() => {})
                .finally(() => this.hidePreLoader());
        },

        editarEmpreendimento(row) {
            this.formEmpreendimento = { id: row.id, codigo: row.codigo, descricao: row.descricao };
        },

        cancelarEdicaoEmpreendimento() {
            this.formEmpreendimento = { id: null, codigo: '', descricao: '' };
        },

        confirmarExclusaoEmpreendimento(row) {
            this.empreendimentoExcluir = row;
            this.dialogMessage = 'Tem certeza que deseja excluir este empreendimento?';
            this.event = 'destroyEmpreendimento';
            this.dialogType = 'danger';
            this.showModal('confirmationDialogfilialModal');
        },

        deleteEmpreendimento(row) {
            this.showPreLoader();

            this.axios({ method: 'delete', url: '/web/filial-empreendimento/' + row.id })
                .then((response) => {
                    if (this.checkApiResponse(response)) {
                        this.alertMessage = 'Empreendimento excluído com sucesso!';
                        this.showAlert('success');
                        this.getEmpreendimentos();
                    } else {
                        this.alertMessage = response.data.message || 'Erro ao excluir empreendimento.';
                        this.showAlert('error');
                    }
                })
                .catch(() => {})
                .finally(() => this.hidePreLoader());
        },

        closeModal() {
            this.hideModal('filialModal');
            this.hideBackdrop();
        },

        saveFilial() {
            if (!this.form.codigo || !this.form.nome || this.form.nome.trim() === '' || !this.form.contasReceber) {
                this.alertMessage = 'Os campos Código, Nome e Contas a Receber são obrigatórios.';
                this.showAlert('error');
                return;
            }

            this.showPreLoader();

            this.axios({
                method: 'post',
                url: '/web/filial',
                data: { ...this.form, action: this.modalAction },
            })
                .then((response) => {
                    if (this.checkApiResponse(response)) {
                        if (this.modalAction === 'Adicionar') {
                            this.form.id = response.data.data.filialId;
                            this.modalAction = 'Editar';
                            this.alertMessage = 'Filial cadastrada! Agora adicione os empreendimentos.';
                        } else {
                            this.alertMessage = 'Filial atualizada com sucesso!';
                            this.closeModal();
                        }
                        this.showAlert('success');
                        this.getFiliais();
                    } else {
                        this.alertMessage = response.data.message || 'Erro ao salvar filial.';
                        this.showAlert('error');
                    }
                })
                .catch(() => {})
                .finally(() => this.hidePreLoader());
        },

        confirmDelete(row) {
            this.filialDel = row;
            this.dialogMessage = 'Tem certeza que deseja excluir esta filial?';
            this.event = 'destroy';
            this.dialogType = 'danger';
            this.showModal('confirmationDialogfilialModal');
        },

        dialogOnReturn(event, returnMessage) {
            if (event === 'destroyEmpreendimento' && returnMessage === 'confirmed') {
                this.deleteEmpreendimento(this.empreendimentoExcluir);
            }

            if (event === 'destroy' && returnMessage === 'confirmed') {
                this.showPreLoader();
                this.axios({
                    method: 'delete',
                    url: '/web/filial/' + this.filialDel.id,
                })
                    .then((response) => {
                        if (this.checkApiResponse(response)) {
                            this.alertMessage = 'Filial excluída com sucesso!';
                            this.showAlert('success');
                            this.getFiliais();
                        } else {
                            this.alertMessage = response.data.message || 'Erro ao excluir filial.';
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

.empreendimentos {
    border-top: 1px solid #dee2e6;
    padding-top: 16px;
}

.empreendimentos-titulo {
    font-weight: 600;
    margin-bottom: 10px;
}

.empreendimentos-table {
    max-height: 260px;
    overflow-y: auto;
}

.filiais-table {
    max-height: calc(100vh - 160px);
    overflow-y: auto;
}

.filiais-table table thead th {
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

.filiais-table table tbody td {
    vertical-align: middle;
    padding: 10px;
}
</style>
