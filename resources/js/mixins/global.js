import { reactive } from "vue";

const state = reactive({
    vars: {}
})

export const global = {
    state
}
