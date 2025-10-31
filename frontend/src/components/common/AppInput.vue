<template>
  <div class="input-wrapper" :class="{ 'has-error': error }">
    <label v-if="label" :for="inputId" class="input-label">
      {{ label }}
      <span v-if="required" class="required">*</span>
    </label>

    <div class="input-container">
      <input
        :id="inputId"
        v-model="inputValue"
        :type="type"
        :placeholder="placeholder"
        :disabled="disabled"
        :required="required"
        :autocomplete="autocomplete"
        :class="inputClasses"
        @input="handleInput"
        @blur="handleBlur"
        @focus="handleFocus"
      />
    </div>

    <p v-if="error" class="input-error">{{ error }}</p>
    <p v-else-if="help" class="input-help">{{ help }}</p>
  </div>
</template>

<script setup lang="ts">
import { computed, ref } from 'vue'
import { generateId } from '@/utils/helpers'
import type { InputType } from '@/types/components'

interface Props {
  modelValue: string | number
  type?: InputType
  label?: string
  placeholder?: string
  error?: string
  help?: string
  disabled?: boolean
  required?: boolean
  autocomplete?: string
}

const props = withDefaults(defineProps<Props>(), {
  type: 'text',
  disabled: false,
  required: false
})

const emit = defineEmits<{
  'update:modelValue': [value: string | number]
  blur: []
  focus: []
}>()

const inputId = generateId()
const isFocused = ref(false)

const inputValue = computed({
  get: () => props.modelValue,
  set: (value) => emit('update:modelValue', value)
})

const inputClasses = computed(() => [
  'input',
  {
    'input-error': props.error,
    'input-focused': isFocused.value,
    'input-disabled': props.disabled
  }
])

const handleInput = (event: Event) => {
  const target = event.target as HTMLInputElement
  emit('update:modelValue', target.value)
}

const handleBlur = () => {
  isFocused.value = false
  emit('blur')
}

const handleFocus = () => {
  isFocused.value = true
  emit('focus')
}
</script>

<style scoped>
.input-wrapper {
  width: 100%;
}

.input-label {
  display: block;
  margin-bottom: var(--spacing-2);
  font-size: 0.875rem;
  font-weight: 500;
  color: var(--text-primary);
}

.required {
  color: var(--danger);
  margin-left: var(--spacing-1);
}

.input-container {
  position: relative;
}

.input {
  width: 100%;
  padding: var(--spacing-3);
  font-size: 1rem;
  font-family: inherit;
  color: var(--text-primary);
  background: var(--surface);
  border: 1px solid var(--border);
  border-radius: var(--radius-md);
  transition: all 0.2s ease;
}

.input:focus {
  outline: none;
  border-color: var(--primary);
  box-shadow: 0 0 0 3px var(--primary-light);
}

.input:disabled {
  opacity: 0.6;
  cursor: not-allowed;
  background: var(--surface-disabled);
}

.input-error {
  border-color: var(--danger);
}

.input-error:focus {
  box-shadow: 0 0 0 3px var(--danger-light);
}

.input-help {
  margin-top: var(--spacing-2);
  font-size: 0.875rem;
  color: var(--text-secondary);
}

.input-error {
  margin-top: var(--spacing-2);
  font-size: 0.875rem;
  color: var(--danger);
}
</style>
