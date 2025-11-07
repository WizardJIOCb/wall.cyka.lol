import { defineStore } from 'pinia'
import { ref } from 'vue'

export const useUIStore = defineStore('ui', () => {
  // State
  const sidebarOpen = ref(false)
  const loading = ref(false)
  const breakpoint = ref<'mobile' | 'tablet' | 'desktop'>('desktop')
  const createPostModalOpen = ref(false)

  // Actions
  const toggleSidebar = () => {
    sidebarOpen.value = !sidebarOpen.value
  }

  const closeSidebar = () => {
    sidebarOpen.value = false
  }

  const openSidebar = () => {
    sidebarOpen.value = true
  }

  const setLoading = (value: boolean) => {
    loading.value = value
  }

  const updateBreakpoint = () => {
    const width = window.innerWidth
    if (width < 768) {
      breakpoint.value = 'mobile'
    } else if (width < 1024) {
      breakpoint.value = 'tablet'
    } else {
      breakpoint.value = 'desktop'
    }
  }

  const openCreatePostModal = () => {
    createPostModalOpen.value = true
  }

  const closeCreatePostModal = () => {
    createPostModalOpen.value = false
  }

  // Initialize breakpoint
  updateBreakpoint()
  window.addEventListener('resize', updateBreakpoint)

  return {
    // State
    sidebarOpen,
    loading,
    breakpoint,
    createPostModalOpen,
    
    // Actions
    toggleSidebar,
    closeSidebar,
    openSidebar,
    setLoading,
    updateBreakpoint,
    openCreatePostModal,
    closeCreatePostModal
  }
})
