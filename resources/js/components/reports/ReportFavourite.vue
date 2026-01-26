<template>
    <div class="card favourite">
        <div class="card-header border-bottom">
            <h4 class="card-title">
                Favourites <i class="bi bi-star-fill"></i>
            </h4>
            <h6 class="card-subtitle pb-2 pt-1 text-muted">
                Your favourite reports
            </h6>
        </div>
        <div class="card-body">
            <div class="d-flex gap-2">
                <ul
                    class="list-group list-unstyled"
                    v-for="reportFav in filteredReports"
                >
                    <li class="list-group-item">
                        <a
                            class="text-decor-none text-success bold"
                            href="#"
                            @click="this.$router.push(`/reports/${reportFav.route}`)"
                            >{{ reportFav.name }}</a
                        >
                    </li>
                </ul>
            </div>
        </div>
    </div>
</template>

<script>
export default {
    props: ["filteredReports"],
}
</script>
