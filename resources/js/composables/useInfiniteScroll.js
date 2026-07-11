/**
 * useInfiniteScroll.js — IntersectionObserver-based infinite scroll composable.
 *
 * Watches a sentinel element. When it enters the viewport, calls `onLoadMore`
 * if there are more pages to load.
 *
 * Wave 4 — used by the Explore tab in Services.vue for loading more services.
 *
 * Usage:
 *   const sentinel = ref(null)
 *   const { observe, disconnect } = useInfiniteScroll(sentinel, loadMore, { canLoad })
 *   onMounted(() => observe())
 *   onUnmounted(() => disconnect())
 *
 * @param {Ref<Element|null>} sentinelRef  — the element to observe
 * @param {Function}          onLoadMore   — callback when sentinel becomes visible
 * @param {Object}            options
 * @param {Ref<boolean>}      options.canLoad — reactive: is there another page to load?
 * @param {number}            options.threshold — intersection threshold (default 0.1)
 * @param {string}            options.rootMargin — root margin (default '200px')
 */
import { ref, watch } from 'vue'

export function useInfiniteScroll(sentinelRef, onLoadMore, options = {}) {
  const { canLoad = ref(true), threshold = 0.1, rootMargin = '200px' } = options

  let observer = null
  const isLoading = ref(false)

  function observe() {
    if (!sentinelRef.value) return
    if (observer) observer.disconnect()

    observer = new IntersectionObserver(
      (entries) => {
        const entry = entries[0]
        if (entry.isIntersecting && canLoad.value && !isLoading.value) {
          triggerLoad()
        }
      },
      { threshold, rootMargin }
    )
    observer.observe(sentinelRef.value)
  }

  function disconnect() {
    if (observer) {
      observer.disconnect()
      observer = null
    }
  }

  async function triggerLoad() {
    if (!canLoad.value || isLoading.value) return
    isLoading.value = true
    try {
      await onLoadMore()
    } finally {
      isLoading.value = false
    }
  }

  // Re-observe if sentinel element changes (e.g. v-if re-renders)
  watch(sentinelRef, (el) => {
    if (el) observe()
    else disconnect()
  })

  return {
    observe,
    disconnect,
    isLoading,
  }
}
