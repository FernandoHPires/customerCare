<template>
    <select :id="'multi-select-' + compId"></select>
</template>

<script>
export default {
    props: {
        modelValue: {
            type: Array,
            default: () => []
        },
        options: {
            type: Array,
            default: () => []
        }   
    },
    emits: ['update:modelValue'],
    data() {
        return {
            compId: Math.random().toString(36).substring(2, 15)
        }
    },
    mounted() {
        setTimeout(() => {
            this.initialize()
        }, 300);
    },
    methods: {
        
        initialize: function() {
            let options = this.options.map(option => ({
                value: option.id,
                text: option.name,
                selected: this.isSelected(option)
            }))

            const selectElement = document.getElementById('multi-select-' + this.compId)

            new this.coreui.MultiSelect(selectElement, {
                name: 'multi-select-' + this.compId,
                search: true,
                selectionType: 'text',
                options: options
            })

            selectElement.addEventListener('changed.coreui.multi-select', event => {
                this.$emit('update:modelValue', JSON.parse(JSON.stringify(event.value)))
            })
        },
        isSelected: function(option) {
            if (!this.modelValue || !Array.isArray(this.modelValue)) {
                return false
            }
            return this.modelValue.some(item => item.value == option.id)
        }
    }
}
</script>
