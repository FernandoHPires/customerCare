<template>
    <div class="card">
    <div class="card-body d-flex justify-content-center">
    <div class="simulacao-container p-2">

        <!-- Dados Gerais -->
        <div class="row g-2 mb-3">
            <div class="col-md-3">
                <label class="form-label">Unidades Venda</label>
                <input type="number" class="form-control" v-model="form.unidadesVenda" @blur="autoSave" />
            </div>
            <div class="col-md-3">
                <label class="form-label">Área Total m²</label>
                <input type="number" step="0.01" class="form-control" v-model="form.areaTotalM2" @blur="autoSave" />
            </div>
            <div class="col-md-3">
                <label class="form-label">Valor Previsto m²</label>
                <input type="number" step="0.01" class="form-control" v-model="form.valorPrevisto" @blur="autoSave" />
            </div>
            <div class="col-md-3">
                <label class="form-label">Aquisição Terreno R$</label>
                <input type="number" step="0.01" class="form-control" v-model="form.valorAquisicaoTerreno" @blur="autoSave" />
            </div>

            <div class="col-md-3">
                <label class="form-label">Aquisição Terreno %</label>
                <input type="number" step="0.01" class="form-control" v-model="form.percentualAquisicaoTerreno" @blur="autoSave" />
            </div>
            <div class="col-md-3">
                <label class="form-label">% Permuta Física/Financ.</label>
                <input type="number" step="0.01" class="form-control" v-model="form.percentualPermutaFisica" @blur="autoSave" />
            </div>
            <div class="col-md-3">
                <label class="form-label">VGV Estimado</label>
                <input type="number" step="0.01" class="form-control" v-model="form.vgv" @blur="autoSave" />
            </div>
            <div class="col-md-3">
                <label class="form-label">Exposição Caixa VP</label>
                <input type="number" step="0.01" class="form-control" v-model="form.exposicaoCaixa" @blur="autoSave" />
            </div>

            <div class="col-md-3">
                <label class="form-label">Resultado Líquido (VPL)</label>
                <input type="number" step="0.01" class="form-control" v-model="form.resultadoLiquido" @blur="autoSave" />
            </div>
        </div>

        <!-- Composição de Custos -->
        <h6 class="text-muted mb-2 border-bottom pb-1">Composição de Custos</h6>
        <div class="row g-2">

            <div class="col-md-6">
                <label class="form-label">Obra R$</label>
                <input type="number" step="0.01" class="form-control" v-model="form.valorCustoObra" @blur="autoSave" />
            </div>
            <div class="col-md-6">
                <label class="form-label">Obra %</label>
                <input type="number" step="0.01" class="form-control" v-model="form.percentualCustoObra" @blur="autoSave" />
            </div>

            <div class="col-md-6">
                <label class="form-label">Comissão R$</label>
                <input type="number" step="0.01" class="form-control" v-model="form.valorComissao" @blur="autoSave" />
            </div>
            <div class="col-md-6">
                <label class="form-label">Comissão %</label>
                <input type="number" step="0.01" class="form-control" v-model="form.percentualComissao" @blur="autoSave" />
            </div>

            <div class="col-md-6">
                <label class="form-label">Tributos R$</label>
                <input type="number" step="0.01" class="form-control" v-model="form.valorTributo" @blur="autoSave" />
            </div>
            <div class="col-md-6">
                <label class="form-label">Tributos %</label>
                <input type="number" step="0.01" class="form-control" v-model="form.percentualTributo" @blur="autoSave" />
            </div>

            <div class="col-md-6">
                <label class="form-label">Incorporação R$</label>
                <input type="number" step="0.01" class="form-control" v-model="form.valorIncorporacao" @blur="autoSave" />
            </div>
            <div class="col-md-6">
                <label class="form-label">Incorporação %</label>
                <input type="number" step="0.01" class="form-control" v-model="form.percentualIncorporacao" @blur="autoSave" />
            </div>

            <div class="col-md-6">
                <label class="form-label">Marketing R$</label>
                <input type="number" step="0.01" class="form-control" v-model="form.valorMarketing" @blur="autoSave" />
            </div>
            <div class="col-md-6">
                <label class="form-label">Marketing %</label>
                <input type="number" step="0.01" class="form-control" v-model="form.percentualMarketing" @blur="autoSave" />
            </div>

            <div class="col-md-6">
                <label class="form-label">Gestão Obra R$</label>
                <input type="number" step="0.01" class="form-control" v-model="form.valorDespesaObra" @blur="autoSave" />
            </div>
            <div class="col-md-6">
                <label class="form-label">Gestão Obra %</label>
                <input type="number" step="0.01" class="form-control" v-model="form.percentualDespesaObra" @blur="autoSave" />
            </div>

            <div class="col-md-6">
                <label class="form-label">Gestão Vendas R$</label>
                <input type="number" step="0.01" class="form-control" v-model="form.valorDespesaVenda" @blur="autoSave" />
            </div>
            <div class="col-md-6">
                <label class="form-label">Gestão Vendas %</label>
                <input type="number" step="0.01" class="form-control" v-model="form.percentualDespesaVenda" @blur="autoSave" />
            </div>

            <div class="col-md-6">
                <label class="form-label">Administração R$</label>
                <input type="number" step="0.01" class="form-control" v-model="form.valorAdministracao" @blur="autoSave" />
            </div>
            <div class="col-md-6">
                <label class="form-label">Administração %</label>
                <input type="number" step="0.01" class="form-control" v-model="form.percentualAdministracao" @blur="autoSave" />
            </div>

        </div>

    </div>
    </div>
    </div>
</template>

<script>
import { util } from '../mixins/util';

export default {
    mixins: [util],
    data() {
        return {
            debounceTimer: null,
            form: {
                unidadesVenda:              0,
                areaTotalM2:                0,
                valorPrevisto:              0,
                valorAquisicaoTerreno:      0,
                percentualAquisicaoTerreno: 0,
                percentualPermutaFisica:    0,
                vgv:                        0,
                exposicaoCaixa:             0,
                resultadoLiquido:           0,
                valorCustoObra:             0,
                percentualCustoObra:        0,
                valorComissao:              0,
                percentualComissao:         0,
                valorTributo:               0,
                percentualTributo:          0,
                valorIncorporacao:          0,
                percentualIncorporacao:     0,
                valorMarketing:             0,
                percentualMarketing:        0,
                valorDespesaObra:           0,
                percentualDespesaObra:      0,
                valorDespesaVenda:          0,
                percentualDespesaVenda:     0,
                valorAdministracao:         0,
                percentualAdministracao:    0,
            },
        };
    },
    mounted() {
        this.load();
    },
    methods: {
        load() {
            this.showPreLoader();
            this.axios({ method: 'get', url: '/web/simulacao' })
                .then((response) => {
                    if (this.checkApiResponse(response) && response.data.data) {
                        this.form = { ...this.form, ...response.data.data };
                    }
                })
                .catch((error) => {
                    console.error('Erro ao carregar simulação:', error);
                })
                .finally(() => {
                    this.hidePreLoader();
                });
        },
        autoSave() {
            clearTimeout(this.debounceTimer);
            this.debounceTimer = setTimeout(() => {
                this.saving = true;
                this.axios({ method: 'post', url: '/web/simulacao', data: this.form })
                    .then(() => {})
                    .catch((error) => {
                        console.error('Erro ao salvar simulação:', error);
                    })
                    .finally(() => {});
            }, 400);
        },
    },
};
</script>

<style scoped>
.form-label {
    margin-top: 0.5rem;
    margin-bottom: 0px;
    font-size: 0.875rem;
}

.simulacao-container {
    width: 100%;
    max-width: 860px;
    background: #fff;
    border: 1px solid #dee2e6;
    border-radius: 8px;
}

@keyframes spin {
    from { transform: rotate(0deg); }
    to   { transform: rotate(360deg); }
}
.spin {
    display: inline-block;
    animation: spin 1s linear infinite;
}
</style>
