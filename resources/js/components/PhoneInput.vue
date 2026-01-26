<template>
    <div class="pb-2" v-if="readOnly">
        <span>{{ tempValue }}</span>
    </div>

    <input
        v-else
        type="text"
        class="form-control"
        :value="tempValue"
        @input="updateValue"
        @blur="formatValue"
        ref="input"
    />
</template>

<script>
import { defineComponent, computed, ref, nextTick } from 'vue'

export default defineComponent({
    name: "PhoneInput",
    props: {
        modelValue: {
            type: [String, Number],
            default: ""
        },
        readOnly: {
            type: Boolean,
            default: false
        }
    },
    setup(props, context) {
        const isInput = ref(false)
        const input = ref(null)

        // Format phone number as (XXX) XXX-XXXX
        const formatPhoneNumber = (value) => {
            if (!value) return ""
            
            // Remove all non-digits
            const digits = value.toString().replace(/\D/g, '')
            
            // Apply formatting based on length
            if (digits.length <= 3) {
                return digits
            } else if (digits.length <= 6) {
                return `(${digits.slice(0, 3)}) ${digits.slice(3)}`
            } else {
                return `(${digits.slice(0, 3)}) ${digits.slice(3, 6)}-${digits.slice(6, 10)}`
            }
        }

        // Computed property for displayed value
        const tempValue = computed(() => {
            if (!isInput.value) {
                // Format for display when not editing
                return formatPhoneNumber(props.modelValue)
            } else {
                // Show raw input during editing
                return props.modelValue
            }
        })

        const updateValue = (event) => {
            const inputEl = event.target
            const oldValue = inputEl.value
            const oldCursorPosition = inputEl.selectionStart
            const nonDigitsBeforeCursor = (oldValue.slice(0, oldCursorPosition).match(/\D/g) || []).length

            // Remove all non-digits for raw value
            let rawValue = oldValue.replace(/\D/g, '').slice(0, 10) // Limit to 10 digits

            isInput.value = true

            // Emit the raw value (digits only)
            context.emit('update:modelValue', rawValue)

            nextTick(() => {
                const newValue = tempValue.value
                let cursorPosition = 0
                let digitCount = 0

                // Calculate new cursor position to maintain relative position
                for (let i = 0; i < newValue.length; i++) {
                    if (/\d/.test(newValue[i])) {
                        digitCount++
                    }
                    if (digitCount >= oldCursorPosition - nonDigitsBeforeCursor) {
                        cursorPosition = i + 1
                        break
                    }
                }

                // Ensure cursor stays within bounds
                cursorPosition = Math.min(cursorPosition, newValue.length)
                inputEl.setSelectionRange(cursorPosition, cursorPosition)
            })
        }

        const formatValue = () => {
            isInput.value = false
            // Emit the raw value (digits only)
            context.emit('update:modelValue', props.modelValue)
        }

        return {
            updateValue,
            formatValue,
            tempValue,
            input
        }
    }
})
</script>