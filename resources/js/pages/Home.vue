<template>
    <div class="home-wrapper">

        <!-- Hero -->
        <div class="home-hero">
            <div class="home-hero-bg-text">UNI</div>
            <div class="home-hero-content">
                <div class="home-greeting">{{ greeting }},</div>
                <div class="home-username">{{ user.fullName || '...' }}</div>
                <div class="home-date">{{ currentDate }}</div>
            </div>
        </div>

        <!-- Quick access -->
        <div class="home-cards">
            <div class="home-card" @click="$router.push({ path: '/portfolio-view', query: { tab: 'dashboard' } })">
                <div class="home-card-icon">
                    <i class="bi bi-pie-chart"></i>
                </div>
                <div class="home-card-label">Dashboard</div>
                <div class="home-card-sub">Indicadores e gráficos</div>
            </div>
            <div class="home-card" @click="$router.push({ path: '/portfolio-view', query: { tab: 'visaoGeral' } })">
                <div class="home-card-icon">
                    <i class="bi bi-graph-up-arrow"></i>
                </div>
                <div class="home-card-label">Portfolio</div>
                <div class="home-card-sub">Visão geral dos empreendimentos</div>
            </div>
            <div class="home-card" @click="$router.push({ path: '/portfolio-view', query: { tab: 'simulacaoViabilidade' } })">
                <div class="home-card-icon">
                    <i class="bi bi-calculator"></i>
                </div>
                <div class="home-card-label">Simulação de Viabilidades</div>
                <div class="home-card-sub">Análise e projeções</div>
            </div>
        </div>

    </div>
</template>

<script>
import { util } from "../mixins/util";

export default {
    mixins: [util],
    data() {
        return {
            user: {},
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
                weekday: "long",
                day: "2-digit",
                month: "long",
                year: "numeric",
            });
            return date.charAt(0).toUpperCase() + date.slice(1);
        },
    },
    mounted() {
        this.loadUser();
    },
    methods: {
        loadUser() {
            this.axios({ method: "get", url: "/web/current-user" })
                .then((response) => {
                    if (this.checkApiResponse(response)) {
                        this.user = response.data.data;
                    }
                })
                .catch((error) => {
                    console.error("Error loading user:", error);
                });
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
    background: linear-gradient(135deg, #124C60 0%, #20556E 60%, #1a6a88 100%);
    border-radius: 20px;
    width: 100%;
    max-width: 800px;
    padding: 56px 64px;
    color: #fff;
    box-shadow: 0 8px 32px rgba(18, 76, 96, 0.25);
    position: relative;
}

.home-hero-bg-text {
    position: absolute;
    right: 24px;
    bottom: -10px;
    font-size: 140px;
    font-weight: 800;
    color: rgba(255, 255, 255, 0.06);
    line-height: 1;
    pointer-events: none;
    letter-spacing: -8px;
    user-select: none;
}

.home-greeting {
    font-size: 1.1rem;
    font-weight: 400;
    opacity: 0.75;
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
    opacity: 0.6;
}

/* Cards */
.home-cards {
    display: flex;
    gap: 20px;
    width: 100%;
    max-width: 800px;
    flex-wrap: wrap;
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
