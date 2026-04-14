<template>
    <div class="searchable-select" ref="wrapper">
        <input
            type="text"
            class="form-control"
            :class="inputClass"
            :placeholder="placeholder || 'Pesquisar...'"
            v-model="searchText"
            @focus="onFocus"
            @input="open = true"
            @blur="onBlur"
            autocomplete="off"
        />
        <div class="ss-dropdown" v-show="open">
            <div
                v-if="filtered.length === 0"
                class="ss-no-results"
            >
                Nenhum resultado encontrado
            </div>
            <div
                v-for="opt in filtered"
                :key="opt.id"
                class="ss-option"
                :class="{ active: opt.id === modelValue }"
                @mousedown.prevent="select(opt)"
            >
                {{ opt.label }}
            </div>
        </div>
    </div>
</template>

<script>
export default {
    name: 'searchable-select',
    props: {
        modelValue:  { default: null },
        options:     { type: Array, default: () => [] },
        placeholder: { type: String, default: 'Pesquisar...' },
        inputClass:  { type: String, default: '' },
    },
    emits: ['update:modelValue'],
    data() {
        return {
            searchText: '',
            open: false,
        };
    },
    computed: {
        filtered() {
            const q = (this.searchText || '').toLowerCase().trim();
            if (!q) return this.options;
            return this.options.filter((o) =>
                o.label.toLowerCase().includes(q)
            );
        },
    },
    watch: {
        modelValue: {
            immediate: true,
            handler(val) {
                if (val == null) {
                    this.searchText = '';
                    return;
                }
                const found = this.options.find((o) => o.id === val);
                this.searchText = found ? found.label : '';
            },
        },
        options() {
            // Re-sync label when options load asynchronously
            if (this.modelValue != null) {
                const found = this.options.find((o) => o.id === this.modelValue);
                if (found) this.searchText = found.label;
            }
        },
    },
    methods: {
        onFocus() {
            this.open = true;
            this.searchText = '';
        },
        onBlur() {
            setTimeout(() => {
                this.open = false;
                // Restore label if no change was made
                if (this.modelValue != null) {
                    const found = this.options.find((o) => o.id === this.modelValue);
                    this.searchText = found ? found.label : '';
                } else {
                    this.searchText = '';
                }
            }, 150);
        },
        select(opt) {
            this.searchText = opt.label;
            this.open = false;
            this.$emit('update:modelValue', opt.id);
        },
        clear() {
            this.searchText = '';
            this.open = false;
            this.$emit('update:modelValue', null);
        },
    },
};
</script>

<style scoped>
.searchable-select {
    position: relative;
}

.ss-dropdown {
    position: absolute;
    top: calc(100% + 2px);
    left: 0;
    right: 0;
    background: #fff;
    border: 1px solid #ced4da;
    border-radius: 0.375rem;
    max-height: 220px;
    overflow-y: auto;
    z-index: 9999;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
}

.ss-option {
    padding: 8px 12px;
    cursor: pointer;
    font-size: 0.9rem;
    transition: background 0.15s;
}

.ss-option:hover,
.ss-option.active {
    background: #e9f0ff;
    color: #0d6efd;
}

.ss-no-results {
    padding: 10px 12px;
    color: #6c757d;
    font-size: 0.875rem;
    font-style: italic;
}
</style>
