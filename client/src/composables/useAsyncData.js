import { ref, onMounted } from 'vue'
import { apiErrorMessage } from '@/services/api'

/**
 * Standard loading / error / data plumbing for a single fetch on mount,
 * with a reload() escape hatch for retry buttons and refreshes.
 *
 * @template T
 * @param {() => Promise<T>} fetcher
 * @param {T} initial
 * @param {{ immediate?: boolean }} [opts]
 */
export function useAsyncData(fetcher, initial, opts = {}) {
  const { immediate = true } = opts
  const data = ref(initial)
  const loading = ref(immediate)
  const error = ref(null)

  async function load() {
    loading.value = true
    error.value = null
    try {
      data.value = await fetcher()
    } catch (e) {
      error.value = apiErrorMessage(e)
    } finally {
      loading.value = false
    }
  }

  if (immediate) onMounted(load)

  return { data, loading, error, reload: load }
}
