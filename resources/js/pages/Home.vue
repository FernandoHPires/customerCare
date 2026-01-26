<template>
    <div class="d-flex flex-row-reverse">
        <div>
            <span>Welcome,</span>
            <h5>{{ user.fullName }}</h5>
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
    mounted() {
        this.load();
    },
    methods: {
        load: function () {
            this.showPreLoader();
            setTimeout(() => {
                this.user = this.getUser();
                this.hidePreLoader();
            }, 400);
        },
    },
};
</script>
