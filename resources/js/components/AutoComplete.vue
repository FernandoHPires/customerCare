<template>
    <div :id="'auto-complete-' + compId"></div>
</template>

<script>
export default {
    props: {
        modelValue: {
            type: Object,
            default: () => []
        },
        options: {
            type: Array,
            default: () => []
        },
        endpoint: {
            type: String,
            default: ''
        },
        placeholder: {
            type: String,
            default: ''
        },
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
            const autoCompleteObj = document.getElementById('auto-complete-' + this.compId);

            let tempOptionsList = [];

            if (this.modelValue && this.modelValue.fullName && this.modelValue.id) {
                tempOptionsList.push({
                    label: this.modelValue.fullName || '',
                    value: this.modelValue.id?.toString() || ''
                })
            }

            const autoComplete = new this.coreui.Autocomplete(autoCompleteObj, {
                name: 'autocomplete',
                search: ['external', 'global'],
                options: tempOptionsList,
                showHints: true,
                highlightOptionsOnSearch: true,
                placeholder: this.placeholder || 'Type to search...',
                value: this.modelValue.id?.toString() || '',
            })

            let endpoint = this.endpoint

            autoCompleteObj.addEventListener('input.coreui.autocomplete', async event => {
                const query = event.value

                if(query.length < 2) {
                    autoComplete.update({ options: [] })
                    return
                }

                if(this.options.length === 0) {
                    const response = await fetch(`/${endpoint}?q=${query}`)
                    const results = await response.json()
                    this.options = results.data
                } 

                const filtered = this.options.filter(item =>
                    item.fullName.toLowerCase().includes(query.toLowerCase())
                )
                autoComplete.update({
                    options: filtered.map(item => ({
                        label: item.fullName,
                        value: item.id.toString(),
                    }))
                })
            })

            autoCompleteObj.addEventListener('changed.coreui.autocomplete', (event) => {
                this.$emit('update:modelValue', JSON.parse(JSON.stringify(event.value)))
            })
        }
    }
}
</script>