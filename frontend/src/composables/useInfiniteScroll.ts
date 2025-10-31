import { ref, onMounted, onUnmounted } from 'vue'

export const useInfiniteScroll = (callback: () => void, options = { threshold: 0.8 }) => {
  const targetRef = ref<HTMLElement>()
  const isLoading = ref(false)

  const handleScroll = () => {
    if (!targetRef.value || isLoading.value) return
    
    const { scrollTop, scrollHeight, clientHeight } = targetRef.value
    const scrollPercentage = (scrollTop + clientHeight) / scrollHeight
    
    if (scrollPercentage >= options.threshold) {
      isLoading.value = true
      callback()
      setTimeout(() => { isLoading.value = false }, 1000)
    }
  }

  onMounted(() => {
    targetRef.value?.addEventListener('scroll', handleScroll)
    window.addEventListener('scroll', handleScroll)
  })

  onUnmounted(() => {
    targetRef.value?.removeEventListener('scroll', handleScroll)
    window.removeEventListener('scroll', handleScroll)
  })

  return { targetRef, isLoading }
}
