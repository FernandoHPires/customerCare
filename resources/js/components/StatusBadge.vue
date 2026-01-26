<template>
    <span v-if="status == 'Done'"
        class="badge bg-success">Done</span>
    <span v-else-if="status == 'Pending'"
        class="badge bg-warning text-dark">Pending</span>
    <span v-else-if="status == 'Completed'"
        class="badge bg-success">Completed</span>
    <span v-else-if="(status == 'R' && tooltip == '')"
        class="badge bg-danger">Rejected</span>
    <span v-else-if="status == 'R'"
        v-tooltip="tooltip"
        class="badge bg-danger">Rejected</span>
    <span v-else-if="(status == 'Rejected' && tooltip == '')"
        class="badge bg-danger">Rejected</span>
    <span v-else-if="status == 'Rejected'"
        v-tooltip="tooltip"
        class="badge bg-danger">Rejected</span>
    <span v-else-if="status == 'A'"
        class="badge bg-success">Approved</span>
    <span v-else-if="status == 'P'"
        class="badge bg-warning text-dark">Pending</span>
    <span v-else-if="status == 'S'"
        class="badge bg-success">Sent</span>
    <span v-else-if="status === null || status == ''"></span>
    <span v-else
        class="badge bg-danger">{{ status }}</span>
</template>

<script>
import VTooltip from 'v-tooltip'

export default {
    components: { VTooltip },
    props: ['status','tooltip']
}
</script>