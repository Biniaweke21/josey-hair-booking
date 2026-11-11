import { ref } from 'vue'
import { businessHoursApi } from '@/services/settings'

const hours = ref([])
const loading = ref(false)
const error = ref(null)
let loaded = false

async function load(force = false) {
  if ((loaded || loading.value) && !force) return
  loading.value = true
  error.value = null
  try {
    const res = await businessHoursApi.list()
    hours.value = (res.data.data || []).slice().sort((a, b) => a.day_of_week - b.day_of_week)
    loaded = true
  } catch (e) {
    error.value = e
  } finally {
    loading.value = false
  }
}

export function useBusinessHours() {
  if (!loaded && !loading.value) load()
  return { hours, loading, error, reload: () => load(true) }
}
