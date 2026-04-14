<template>
    <div class="card">
        <div class="card-header">
            <div class="d-flex align-items-center">
                <h5 class="mb-0">Permissões</h5>
                <div class="ms-auto">
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

        <div class="table-responsive permissoes-table">
            <table class="table table-hover mb-0">
                <thead>
                    <tr>
                        <th>Perfil</th>
                        <th>Descrição</th>
                        <th>Menus com Acesso</th>
                        <th></th>
                    </tr>
                </thead>

                <tbody v-if="filteredData.length === 0">
                    <tr>
                        <td colspan="4">Nenhum perfil encontrado.</td>
                    </tr>
                </tbody>

                <tbody v-else>
                    <tr v-for="row in filteredData" :key="row.id">
                        <td>{{ row.nome }}</td>
                        <td>{{ row.descricao }}</td>
                        <td>
                            <span class="badge bg-primary">{{ row.qtdMenus }}</span>
                        </td>
                        <td class="text-end">
                            <button
                                type="button"
                                class="btn btn-primary btn-sm"
                                @click="editPermissao(row)"
                            >
                                <i class="bi-shield-lock me-1"></i>Editar Acesso
                            </button>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Modal de Permissões -->
    <div
        class="modal fade"
        id="permissaoModal"
        data-coreui-backdrop="static"
        data-coreui-keyboard="false"
        tabindex="-1"
        aria-hidden="true"
        style="display: none;"
    >
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="bi-shield-lock me-2"></i>Permissões — {{ perfilSelecionado.nome }}
                    </h5>
                    <button type="button" class="btn-close" @click="closeModal()"></button>
                </div>

                <div class="modal-body">
                    <div v-if="loadingMenus" class="text-center py-4">
                        <div class="spinner-border text-primary" role="status"></div>
                    </div>

                    <div v-else>
                        <div
                            v-for="modulo in modulosMenu"
                            :key="modulo.id"
                            class="modulo-bloco mb-3"
                        >
                            <!-- Cabeçalho do módulo -->
                            <div class="modulo-header d-flex align-items-center justify-content-between">
                                <div class="d-flex align-items-center gap-2">
                                    <i :class="modulo.icon" class="text-primary"></i>
                                    <strong>{{ modulo.label }}</strong>
                                </div>
                                <div class="d-flex gap-2">
                                    <button
                                        type="button"
                                        class="btn btn-outline-secondary btn-xs"
                                        @click="marcarTodos(modulo, true)"
                                    >
                                        Marcar todos
                                    </button>
                                    <button
                                        type="button"
                                        class="btn btn-outline-secondary btn-xs"
                                        @click="marcarTodos(modulo, false)"
                                    >
                                        Desmarcar todos
                                    </button>
                                </div>
                            </div>

                            <!-- Checkboxes dos submenus -->
                            <div class="modulo-itens">
                                <div
                                    v-for="menu in modulo.menus"
                                    :key="menu.id"
                                    class="form-check form-check-inline menu-item"
                                >
                                    <input
                                        class="form-check-input"
                                        type="checkbox"
                                        :id="'menu_' + menu.id"
                                        v-model="menu.checked"
                                    />
                                    <label class="form-check-label" :for="'menu_' + menu.id">
                                        {{ menu.label }}
                                    </label>
                                </div>
                            </div>
                        </div>

                        <div v-if="modulosMenu.length === 0" class="text-muted text-center py-3">
                            Nenhum menu cadastrado no sistema.
                        </div>
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-dark" @click="closeModal()">
                        <i class="bi-x-lg me-1"></i>Cancelar
                    </button>
                    <button type="button" class="btn btn-primary" @click="savePermissao()">
                        <i class="bi-check-lg me-1"></i>Salvar
                    </button>
                </div>
            </div>
        </div>
    </div>

</template>

<script>
import { util } from '../mixins/util';

export default {
    mixins: [util],
    name: 'permissoes-page',
    data() {
        return {
            permissoes: [],
            search: '',
            perfilSelecionado: {},
            modulosMenu: [],
            loadingMenus: false,
        };
    },
    computed: {
        filteredData() {
            const search = this.search && this.search.toLowerCase();
            if (!search) return this.permissoes;
            return this.permissoes.filter((row) =>
                Object.values(row).some((val) =>
                    String(val).toLowerCase().includes(search)
                )
            );
        },
    },
    mounted() {
        this.getPermissoes();
    },
    methods: {
        getPermissoes() {
            this.showPreLoader();
            this.axios({ method: 'get', url: '/web/permissoes' })
                .then((response) => {
                    if (this.checkApiResponse(response)) {
                        this.permissoes = response.data.data;
                    } else {
                        this.alertMessage = 'Erro ao carregar permissões.';
                        this.showAlert('error');
                    }
                })
                .catch((error) => console.error(error))
                .finally(() => this.hidePreLoader());
        },

        editPermissao(row) {
            this.perfilSelecionado = row;
            this.modulosMenu = [];
            this.loadingMenus = true;
            this.showModal('permissaoModal');

            this.axios({ method: 'get', url: '/web/permissao/' + row.id })
                .then((response) => {
                    if (this.checkApiResponse(response)) {
                        this.modulosMenu = response.data.data;
                    }
                })
                .catch((error) => console.error(error))
                .finally(() => { this.loadingMenus = false; });
        },

        closeModal() {
            this.hideModal('permissaoModal');
            this.hideBackdrop();
        },

        marcarTodos(modulo, value) {
            modulo.menus.forEach((m) => { m.checked = value; });
        },

        savePermissao() {
            const menuIds = [];
            this.modulosMenu.forEach((modulo) => {
                modulo.menus.forEach((menu) => {
                    if (menu.checked) menuIds.push(menu.id);
                });
            });

            this.showPreLoader();
            this.axios({
                method: 'post',
                url: '/web/permissao/' + this.perfilSelecionado.id,
                data: { menuIds },
            })
                .then((response) => {
                    if (this.checkApiResponse(response)) {
                        this.alertMessage = 'Permissões salvas com sucesso!';
                        this.showAlert('success');
                        this.closeModal();
                        this.getPermissoes();
                    } else {
                        this.alertMessage = 'Erro ao salvar permissões.';
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
.permissoes-table {
    max-height: calc(100vh - 160px);
    overflow-y: auto;
}

.permissoes-table table thead th {
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

.permissoes-table table tbody td {
    vertical-align: middle;
    padding: 10px;
}

.modulo-bloco {
    border: 1px solid #dee2e6;
    border-radius: 0.5rem;
    overflow: hidden;
}

.modulo-header {
    background: #f8f9fa;
    padding: 10px 14px;
    border-bottom: 1px solid #dee2e6;
}

.modulo-itens {
    padding: 12px 14px;
    display: flex;
    flex-wrap: wrap;
    gap: 4px 0;
}

.menu-item {
    min-width: 200px;
    margin: 4px 0;
}

.btn-xs {
    padding: 2px 8px;
    font-size: 0.75rem;
}
</style>
