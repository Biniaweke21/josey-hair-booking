<template>
  <div class="ap-page">
    <p v-if="error" class="state-note state-note--error">{{ error }} <button class="state-retry" @click="load">Retry</button></p>

    <div v-if="loading" class="ap-card"><div class="skeleton" style="height:220px;"></div></div>

    <template v-else>
      <!-- Settings groups -->
      <form class="ap-card" @submit.prevent="saveSettings">
        <h3 class="ap-card__title">Salon Settings</h3>
        <p class="ap-card__sub">Business details, booking rules and capacity thresholds.</p>

        <div v-for="(rows, groupName) in editableGroups" :key="groupName" class="set-group">
          <h4 class="bk-sec">{{ prettyGroup(groupName) }}</h4>
          <div class="ap-form-grid">
            <div v-for="s in rows" :key="s.key" class="ap-form-group">
              <label>{{ prettyKey(s.key) }}</label>
              <input v-if="s.type === 'boolean'" type="checkbox" v-model="model[s.key]" class="set-check" />
              <input v-else-if="s.type === 'number'" type="number" v-model.number="model[s.key]" />
              <input v-else type="text" v-model="model[s.key]" />
            </div>
          </div>
        </div>

        <p v-if="settingsSaved" class="ap-success">Settings saved.</p>
        <p v-if="settingsError" class="ap-error">{{ settingsError }}</p>
        <div class="ap-form-actions"><button type="submit" class="ap-btn-primary" :disabled="savingSettings">{{ savingSettings ? 'Saving…' : 'Save Settings' }}</button></div>
      </form>

      <!-- Business hours -->
      <form class="ap-card" @submit.prevent="saveHours">
        <h3 class="ap-card__title">Business Hours</h3>
        <p class="ap-card__sub">Opening times per day. Closed days have no availability.</p>
        <div class="bh-list">
          <div v-for="h in hours" :key="h.day_of_week" class="bh-row">
            <span class="bh-day">{{ DAY_NAMES[h.day_of_week] }}</span>
            <label class="bh-closed"><input type="checkbox" v-model="h.is_closed" /> Closed</label>
            <input type="time" v-model="h.open_time" :disabled="h.is_closed" class="ap-input bh-time" />
            <span class="bh-sep">–</span>
            <input type="time" v-model="h.close_time" :disabled="h.is_closed" class="ap-input bh-time" />
          </div>
        </div>
        <p v-if="hoursSaved" class="ap-success">Business hours saved.</p>
        <p v-if="hoursError" class="ap-error">{{ hoursError }}</p>
        <div class="ap-form-actions"><button type="submit" class="ap-btn-primary" :disabled="savingHours">{{ savingHours ? 'Saving…' : 'Save Hours' }}</button></div>
      </form>
    </template>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { settingsApi, businessHoursApi } from '@/services/settings'
import { useSettings } from '@/composables/useSettings'
import { DAY_NAMES } from '@/utils/format'
import { apiErrorMessage } from '@/services/api'
import { useToast } from '@/composables/useToast'

const toast = useToast()

const grouped = ref({})
const model = ref({})
const hours = ref([])
const loading = ref(true)
const error = ref(null)
const { reload: reloadSettings } = useSettings()

// Hide file-path settings (logo handled separately) from the text editor.
const HIDDEN = ['salon_logo_path']

const editableGroups = computed(() => {
  const out = {}
  for (const [g, rows] of Object.entries(grouped.value)) {
    const filtered = rows.filter((r) => !HIDDEN.includes(r.key))
    if (filtered.length) out[g] = filtered
  }
  return out
})

async function load() {
  loading.value = true; error.value = null
  try {
    const [s, h] = await Promise.all([settingsApi.list(), businessHoursApi.list()])
    grouped.value = s.data.data
    const m = {}
    for (const rows of Object.values(grouped.value)) for (const r of rows) m[r.key] = r.value
    model.value = m
    // ensure all 7 days present
    const byDay = {}
    for (const row of h.data.data) byDay[row.day_of_week] = { ...row, open_time: (row.open_time || '09:00').slice(0, 5), close_time: (row.close_time || '17:00').slice(0, 5) }
    hours.value = Array.from({ length: 7 }, (_, d) => byDay[d] || { day_of_week: d, is_closed: true, open_time: '09:00', close_time: '17:00' })
  } catch (e) { error.value = apiErrorMessage(e) } finally { loading.value = false }
}
onMounted(load)

const savingSettings = ref(false)
const settingsSaved = ref(false)
const settingsError = ref(null)
async function saveSettings() {
  savingSettings.value = true; settingsSaved.value = false; settingsError.value = null
  try {
    const pairs = Object.entries(model.value).map(([key, value]) => ({ key, value: typeof value === 'boolean' ? (value ? 1 : 0) : value }))
    await settingsApi.update(pairs)
    await reloadSettings()
    settingsSaved.value = true
    toast.success('Settings saved.')
    setTimeout(() => { settingsSaved.value = false }, 3000)
  } catch (e) { settingsError.value = apiErrorMessage(e); toast.error(apiErrorMessage(e)) } finally { savingSettings.value = false }
}

const savingHours = ref(false)
const hoursSaved = ref(false)
const hoursError = ref(null)
async function saveHours() {
  savingHours.value = true; hoursSaved.value = false; hoursError.value = null
  try {
    const payload = hours.value.map((h) => ({ day_of_week: h.day_of_week, is_closed: h.is_closed, open_time: h.open_time, close_time: h.close_time }))
    await businessHoursApi.update(payload)
    hoursSaved.value = true
    toast.success('Business hours saved.')
    setTimeout(() => { hoursSaved.value = false }, 3000)
  } catch (e) { hoursError.value = apiErrorMessage(e); toast.error(apiErrorMessage(e)) } finally { savingHours.value = false }
}

function prettyGroup(g) { return g.replace(/_/g, ' ').replace(/\b\w/g, (c) => c.toUpperCase()) }
function prettyKey(k) { return k.replace(/^salon_/, '').replace(/_/g, ' ').replace(/\b\w/g, (c) => c.toUpperCase()) }
</script>

<style scoped>
.set-group { margin-top:.5rem; }
.set-check { width:20px; height:20px; }
.bh-list { display:flex; flex-direction:column; gap:.6rem; margin:1rem 0; }
.bh-row { display:flex; align-items:center; gap:.75rem; flex-wrap:wrap; }
.bh-day { min-width:90px; font-weight:600; font-size:.85rem; color:#1f2937; }
.bh-closed { display:flex; align-items:center; gap:.35rem; font-size:.8rem; color:#6b7280; }
.bh-time { max-width:120px; }
.bh-sep { color:#9ca3af; }
.ap-form-actions { margin-top:1rem; }
</style>
