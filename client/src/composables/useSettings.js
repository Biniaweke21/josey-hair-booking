import { ref, computed } from 'vue'
import { settingsApi } from '@/services/settings'

// Module-level cache so the navbar, footer, contact page, etc. share a single
// /settings fetch instead of each hitting the API on mount.
const flat = ref({})
const loading = ref(false)
const error = ref(null)
let loaded = false

async function load(force = false) {
  if ((loaded || loading.value) && !force) return
  loading.value = true
  error.value = null
  try {
    const res = await settingsApi.list()
    const map = {}
    for (const group of Object.values(res.data.data || {})) {
      for (const setting of group) map[setting.key] = setting.value
    }
    flat.value = map
    loaded = true
  } catch (e) {
    error.value = e
  } finally {
    loading.value = false
  }
}

export function useSettings() {
  if (!loaded && !loading.value) load()

  const setting = (key, fallback = '') => computed(() => flat.value[key] ?? fallback)

  return {
    flat,
    loading,
    error,
    reload: () => load(true),
    salonName: setting('salon_name', 'Beauty By Josey'),
    tagline: setting('salon_tagline', ''),
    email: setting('salon_email', ''),
    phone: setting('salon_phone', ''),
    address: setting('salon_address', ''),
    instagram: setting('salon_instagram', ''),
    tiktok: setting('salon_tiktok', ''),
    currency: setting('salon_currency', 'USD'),
    logoPath: setting('salon_logo_path', '')
  }
}
