// Component prop types and emits

export type ButtonVariant = 'primary' | 'secondary' | 'ghost' | 'danger' | 'success'
export type ButtonSize = 'sm' | 'md' | 'lg'

export type InputType = 'text' | 'email' | 'password' | 'number' | 'tel' | 'url' | 'search'

export type ToastType = 'success' | 'error' | 'warning' | 'info'

export interface ToastMessage {
  id: string
  type: ToastType
  message: string
  duration?: number
}

export interface ModalProps {
  modelValue: boolean
  title?: string
  size?: 'sm' | 'md' | 'lg' | 'xl'
  closeable?: boolean
}

export interface TabItem {
  key: string
  label: string
  disabled?: boolean
}

export type Theme = 'light' | 'dark' | 'green' | 'cream' | 'blue' | 'high-contrast'
