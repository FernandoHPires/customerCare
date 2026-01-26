<template>
    <div class="pb-2" v-if="readOnly">
        <span>{{ tempValue }}<span v-if="isPercentage">%</span></span>
    </div>

    <div class="d-flex flex-row align-items-center" v-else-if="isPercentage">
        <input
            type="text"
            :disabled="isDisabled"
            class="form-control"
            :value="tempValue"
            @input="updateValue"
            @blur="formatValue"
            ref="input"
            :placeholder="placeholder"
        />%
    </div>

    <input v-else
        type="text"
        :disabled="isDisabled"
        class="form-control"
        :value="tempValue"
        @input="updateValue"
        @blur="formatValue"
        ref="input"
        :placeholder="placeholder"
    />    
</template>

<script>
import { defineComponent, computed, ref, nextTick } from 'vue'

export default defineComponent({
    name: "currency-input",
    props: {
        modelValue: "",
        readOnly: Boolean,
        isDisabled: false,
        isPercentage: Boolean,
        decimals: {
            type: Number,
            required: false,
            default: 2
        },
        placeholder: String
    },
    setup(props, context) {
        const isInput = ref(false)

        const tempValue = computed(() => {
            const decimals = props.decimals

            if(props.placeholder && !props.modelValue) {
                return
            }

            if (!isInput.value) {
                let val = 0;
                if (typeof props.modelValue !== "undefined" && props.modelValue !== "") {
                    if (isNaN(props.modelValue)) {
                        val = val.toFixed(decimals)
                    } else {
                        val = Number(props.modelValue).toFixed(decimals).replace(/\B(?=(\d{3})+(?!\d))/g, ",");
                    }
                } else {
                    val = val.toFixed(decimals)
                }
                return val
            } else {
                return props.modelValue
            }
        })

        const updateValue = (event) => {
            const input = event.target;
            const oldValue = input.value;
            const oldCursorPosition = input.selectionStart;
            const commasBeforeCursor = (oldValue.slice(0, oldCursorPosition).match(/,/g) || []).length;

            let rawValue = oldValue.replace(/,/g, '');

            if ((rawValue.match(/\./g) || []).length > 1) {
                rawValue = 0;
            }
             
            isInput.value = true;
 
            context.emit('update:modelValue', rawValue);
 
            nextTick(() => {
                const newValue = tempValue.value;
 
                let cursorPosition = 0;
                let nonCommaCount = 0;
 
                for (let i = 0; i < newValue.length; i++) {
                    if (newValue[i] !== ',') {
                        nonCommaCount++;
                    }
 
                    if (nonCommaCount >= oldCursorPosition - commasBeforeCursor) {
                        cursorPosition = i + 1;
                        break;
                    }
                }
 
                input.setSelectionRange(cursorPosition, cursorPosition);
            });
        }
 
        const formatValue = () => {
            isInput.value = false;
            context.emit('update:modelValue', props.modelValue);
        }
       
        return {
            updateValue,
            formatValue,
            tempValue
        }
    },
})
</script>