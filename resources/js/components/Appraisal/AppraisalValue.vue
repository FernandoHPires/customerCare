<template>
    <i
        v-if="value !== undefined && value.confidence < 1"
        class="bi bi-exclamation-triangle-fill text-warning me-1"
        v-tooltip="{ content: getTooltipContent(value), html: true }">
    </i>

    <template v-if="value !== undefined">
        <template v-if="value.type == 'number'">{{ formatNumber(value.value) }}</template>
        <template v-else>{{ value.value }}</template>
    </template>
</template>

<script>
import { util } from '../../mixins/util'

export default {
    mixins: [util],
    components: { },
    props: ['value'],
    methods: {
        getTooltipContent: function(field) {
            let content = '<p>Mismatch values found, double check the PDF file</p>';

            content += '<table class="table table-hover">'
            content += '<tr>'
            content += `<td style="padding-left: 5px">${field.answers[0].source}</td><td>${field.answers[0].value}</td>`;
            content += '</tr>'
            content += '<tr>'
            content += `<td style="padding-left: 5px">${field.answers[1].source}</td><td>${field.answers[1].value}</td>`;
            content += '</tr>'
            content += '</table>'
            
            return content
        },
    }
}
</script>
