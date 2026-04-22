<template>
    <div class="home-wrapper">

        <!-- Hero -->
        <div class="home-hero">
            <div class="home-hero-content">
                <div class="home-greeting">{{ greeting }},</div>
                <div class="home-username">{{ user.fullName || '...' }}</div>
                <div class="home-date">{{ currentDate }}</div>
            </div>
            <div class="home-hero-bg-text">UNI</div>
        </div>

        <!-- Acesso rápido -->
        <div
            v-if="menuCarregado"
            :class="['home-cards', cardsAcesso.length > 3 ? 'home-cards--grid2' : '']"
        >
            <div
                v-for="card in cardsAcesso"
                :key="card.label"
                class="home-card"
                @click="navegarPara(card)"
            >
                <div class="home-card-icon">
                    <i :class="['bi', card.icon]"></i>
                </div>
                <div class="home-card-label">{{ card.label }}</div>
                <div class="home-card-sub">{{ card.sub }}</div>
            </div>
        </div>

    </div>
</template>

<script>
import { util } from "../mixins/util";

// Mapa de subtítulos amigáveis por rota
const SUBTITULOS = {
    '/portfolio-view'  : 'Portfólio de empreendimentos',
    '/clientes'        : 'Gestão de clientes',
    '/usuarios'        : 'Gestão de usuários',
    '/perfis'          : 'Perfis de acesso',
    '/permissoes'      : 'Configuração de permissões',
};

// Ícones distintos por rota (Bootstrap Icons)
const ICONES = {
    '/clientes'        : 'bi-building',
    '/usuarios'        : 'bi-person-gear',
    '/perfis'          : 'bi-shield-check',
    '/permissoes'      : 'bi-key',
    '/portfolio-view'  : 'bi-graph-up-arrow',
};

// Cards fixos exibidos quando o usuário tem acesso ao portfolio
const CARDS_PORTFOLIO = [
    {
        label : 'Dashboard',
        sub   : 'Indicadores e gráficos',
        icon  : 'bi-pie-chart',
        path  : '/portfolio-view',
        query : { tab: 'dashboard' },
    },
    {
        label : 'Portfolio',
        sub   : 'Visão geral dos empreendimentos',
        icon  : 'bi-graph-up-arrow',
        path  : '/portfolio-view',
        query : { tab: 'visaoGeral' },
    },
    {
        label : 'Simulação de Viabilidades',
        sub   : 'Análise e projeções',
        icon  : 'bi-calculator',
        path  : '/portfolio-view',
        query : { tab: 'simulacaoViabilidade' },
    },
];

export default {
    mixins: [util],
    data() {
        return {
            user          : {},
            menus         : [],
            menuCarregado : false,
        };
    },
    computed: {
        greeting() {
            const hour = new Date().getHours();
            if (hour < 12) return "Bom dia";
            if (hour < 18) return "Boa tarde";
            return "Boa noite";
        },
        currentDate() {
            const date = new Date().toLocaleDateString("pt-BR", {
                weekday : "long",
                day     : "2-digit",
                month   : "long",
                year    : "numeric",
            });
            return date.charAt(0).toUpperCase() + date.slice(1);
        },

        // Lista plana de todas as rotas acessíveis ao usuário (exceto Home)
        rotasAcessiveis() {
            const rotas = [];
            for (const menu of this.menus) {
                // Pula apenas o Home (path '/')
                if (menu.path === '/') continue;

                if (menu.submenus && menu.submenus.length) {
                    // Menu pai com path null mas com filhos (ex: Cadastros, Acessos)
                    // — processa os submenus normalmente
                    for (const sub of menu.submenus) {
                        if (sub.type === 'page' && sub.path) {
                            rotas.push({
                                label    : sub.name,
                                icon     : sub.icon || 'bi-arrow-right-circle',
                                path     : sub.path,
                                categoria: menu.name,
                            });
                        }
                    }
                } else if (menu.path) {
                    // Menu direto com URL própria (ex: Portfolio)
                    rotas.push({
                        label    : menu.name,
                        icon     : menu.icon || 'bi-arrow-right-circle',
                        path     : menu.path,
                        categoria: '',
                    });
                }
            }
            return rotas;
        },

        temAcessoPortfolio() {
            return this.rotasAcessiveis.some(r => r.path === '/portfolio-view');
        },

        // Cards que serão exibidos na tela
        cardsAcesso() {
            if (this.temAcessoPortfolio) {
                return CARDS_PORTFOLIO;
            }

            // Sem acesso ao portfolio: gera cards de tudo que o usuário tem acesso
            return this.rotasAcessiveis.map(rota => ({
                label : rota.label,
                sub   : SUBTITULOS[rota.path] || rota.categoria || 'Acesso rápido',
                icon  : ICONES[rota.path] || rota.icon || 'bi-arrow-right-circle',
                path  : rota.path,
                query : {},
            }));
        },
    },
    mounted() {
        this.loadUser();
        this.loadMenus();
    },
    methods: {
        loadUser() {
            this.axios({ method: "get", url: "/web/current-user" })
                .then((response) => {
                    if (this.checkApiResponse(response)) {
                        this.user = response.data.data;
                    }
                })
                .catch(() => {});
        },

        loadMenus() {
            this.axios({ method: "get", url: "/web/menus" })
                .then((response) => {
                    if (this.checkApiResponse(response)) {
                        this.menus = response.data.data;
                    }
                })
                .catch(() => {})
                .finally(() => {
                    this.menuCarregado = true;
                });
        },

        navegarPara(card) {
            this.$router.push({ path: card.path, query: card.query || {} });
        },
    },
};
</script>

<style scoped>
.home-wrapper {
    display: flex;
    flex-direction: column;
    align-items: center;
    padding: 48px 24px 40px;
    gap: 32px;
}

/* Hero */
.home-hero {
    position: relative;
    border-radius: 20px;
    overflow: hidden;
    width: 100%;
    max-width: 800px;
    padding: 56px 64px;
    color: #fff;
    box-shadow: 0 8px 32px rgba(18, 76, 96, 0.25);

    /* 3 camadas empilhadas:
       1. Overlay: cor sólida na esquerda → transparente na direita
       2. Imagem do condomínio
       3. Fallback de cor caso a imagem não carregue              */
    background:
        linear-gradient(
            90deg,
            rgba(18, 76, 96, 0.95)  0%,
            rgba(18, 76, 96, 0.85) 20%,
            rgba(18, 76, 96, 0.40) 45%,
            rgba(18, 76, 96, 0.10) 65%,
            transparent            85%
        ),
        url('/images/img_condominio.png') center / cover no-repeat,
        #124C60;
}

/* Conteúdo e UNI ficam acima de tudo */
.home-hero::before { content: none; }
.home-hero::after  { content: none; }

/* Conteúdo acima dos pseudo-elementos */
.home-hero-content {
    position: relative;
    z-index: 2;
}

.home-hero-bg-text {
    position: absolute;
    right: 30px;
    bottom: 0px;
    font-size: 140px;
    font-weight: 800;
    color: rgba(255, 255, 255, 0.22);
    line-height: 1;
    pointer-events: none;
    letter-spacing: -8px;
    user-select: none;
    z-index: 2;
}

.home-greeting {
    font-size: 1.1rem;
    font-weight: 400;
    opacity: 0.80;
    text-transform: capitalize;
    margin-bottom: 4px;
}

.home-username {
    font-size: 2.4rem;
    font-weight: 700;
    letter-spacing: -0.5px;
    line-height: 1.2;
    margin-bottom: 16px;
}

.home-date {
    font-size: 0.9rem;
    opacity: 0.65;
    display: flex;
    align-items: center;
    gap: 6px;
}

.home-date::before {
    content: "📅";
    font-size: 0.85rem;
}

/* Cards */
.home-cards {
    display: flex;
    gap: 20px;
    width: 100%;
    max-width: 800px;
    flex-wrap: wrap;
}

/* Grid 2 colunas — usado quando há mais de 3 cards */
.home-cards--grid2 {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    justify-items: stretch;
}

.home-card {
    flex: 1;
    min-width: 200px;
    background: #fff;
    border-radius: 14px;
    padding: 28px 24px;
    cursor: pointer;
    color: #2c3e50;
    box-shadow: 0 2px 12px rgba(0, 0, 0, 0.07);
    border: 1px solid #e8edf2;
    transition: transform 0.18s ease, box-shadow 0.18s ease;
    display: flex;
    flex-direction: column;
    gap: 8px;
}

.home-card:hover {
    transform: translateY(-4px);
    box-shadow: 0 8px 24px rgba(18, 76, 96, 0.15);
    border-color: #20556E;
    color: #124C60;
}

.home-card-icon {
    font-size: 1.6rem;
    color: #20556E;
    margin-bottom: 4px;
}

.home-card-label {
    font-size: 1rem;
    font-weight: 700;
}

.home-card-sub {
    font-size: 0.8rem;
    color: #8a9ab0;
}
</style>
