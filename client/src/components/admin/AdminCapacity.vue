<template>
  <div class="ap-page">
    <!-- Date picker -->
    <div class="ap-card cap-toolbar">
      <div>
        <h3 class="ap-card__title">Capacity for {{ prettyDate }}</h3>
        <p class="ap-card__sub">Staff working, capacity reserved, and availability by time.</p>
      </div>
      <input type="date" v-model="date" class="ap-input cap-date" />
    </div>

    <p v-if="error" class="state-note state-note--error">
      {{ error }} <button class="state-retry" @click="loadAll">Retry</button>
    </p>

    <!-- Summary KPIs -->
    <div class="ap-kpi-grid">
      <template v-if="loading">
        <div v-for="n in 5" :key="'csk'+n" class="ap-card ap-kpi">
          <div class="ap-kpi__icon skeleton" style="border-radius:12px;"></div>
          <div style="flex:1"><span class="skeleton" style="height:.75rem;width:60%;display:block;margin-bottom:.5rem;"></span><span class="skeleton" style="height:1.4rem;width:40%;display:block;"></span></div>
        </div>
      </template>
      <template v-else-if="summary">
        <div class="ap-card ap-kpi" title="How many staff are rostered to work this day - the salon's max concurrent bookings."><div class="ap-kpi__icon c-navy"><Users :size="22" /></div><div><p class="ap-kpi__label">Working Staff</p><p class="ap-kpi__val">{{ summary.working_staff }}</p></div></div>
        <div class="ap-card ap-kpi" title="Same as Working Staff: the number of appointments that can run at the same time."><div class="ap-kpi__icon c-blue"><Gauge :size="22" /></div><div><p class="ap-kpi__label">Capacity</p><p class="ap-kpi__val">{{ summary.capacity }}</p></div></div>
        <div class="ap-card ap-kpi" title="The busiest moment today: the most appointments that overlapped at any single point in time."><div class="ap-kpi__icon c-amber"><CalendarCheck :size="22" /></div><div><p class="ap-kpi__label">Peak Reserved</p><p class="ap-kpi__val">{{ summary.reserved }}</p></div></div>
        <div class="ap-card ap-kpi" title="Capacity minus Peak Reserved - open slots left even at the busiest moment today."><div class="ap-kpi__icon c-green"><CircleCheck :size="22" /></div><div><p class="ap-kpi__label">Remaining</p><p class="ap-kpi__val">{{ summary.remaining }}</p></div></div>
        <div class="ap-card ap-kpi" title="Total confirmed/checked-in appointments scheduled today, regardless of overlap."><div class="ap-kpi__icon c-navy"><CalendarDays :size="22" /></div><div><p class="ap-kpi__label">Total Bookings</p><p class="ap-kpi__val">{{ summary.total_bookings }}</p></div></div>
      </template>
    </div>

    <div class="ap-two-col">
      <!-- Timeline -->
      <div class="ap-card">
        <h3 class="ap-card__title">Capacity Timeline</h3>
        <p class="ap-card__sub">Click a slot to see which bookings fill it.</p>

        <div v-if="loading" class="cap-timeline">
          <div v-for="n in 8" :key="'tl'+n" class="skeleton" style="height:34px;margin-bottom:6px;"></div>
        </div>
        <p v-else-if="!timeline.length" class="ap-empty">Closed or no capacity on this day.</p>
        <div v-else class="cap-timeline">
          <button
            v-for="row in timeline" :key="row.time"
            class="cap-row" :class="{ 'is-active': selectedTime === row.time }"
            @click="selectInterval(row.time)"
          >
            <span class="cap-row__time">{{ formatTime12(row.time) }}</span>
            <span class="ap-track cap-row__track">
              <span class="ap-fill" :style="{ width: fillPct(row) + '%', background: statusColor(row.status) }"></span>
            </span>
            <span class="cap-row__nums">{{ row.reserved }}/{{ row.capacity }}</span>
            <span class="ap-badge" :class="statusBadge(row.status)">{{ row.status }}</span>
          </button>
        </div>
      </div>

      <!-- Analytics + interval detail -->
      <div class="ap-card">
        <h3 class="ap-card__title">Analytics</h3>
        <p class="ap-card__sub">{{ prettyDate }}</p>
        <div v-if="analytics" class="cap-analytics">
          <div class="cap-stat"><span class="cap-stat__lbl">Peak Hour</span><span class="cap-stat__val">{{ analytics.peak_hour ? formatTime12(analytics.peak_hour) : '—' }}</span></div>
          <div class="cap-stat"><span class="cap-stat__lbl">Least Busy</span><span class="cap-stat__val">{{ analytics.least_busy_hour ? formatTime12(analytics.least_busy_hour) : '—' }}</span></div>
          <div class="cap-stat"><span class="cap-stat__lbl">Max Concurrent</span><span class="cap-stat__val">{{ analytics.max_concurrent_bookings }}</span></div>
          <div class="cap-stat"><span class="cap-stat__lbl">Utilization</span><span class="cap-stat__val">{{ analytics.capacity_utilization_percent }}%</span></div>
        </div>

        <template v-if="selectedTime">
          <h3 class="ap-card__title" style="margin-top:1.25rem;">Bookings at {{ formatTime12(selectedTime) }}</h3>
          <div v-if="intervalLoading" class="cap-timeline"><div v-for="n in 2" :key="'il'+n" class="skeleton" style="height:40px;margin-bottom:6px;"></div></div>
          <p v-else-if="!intervalBookings.length" class="ap-empty">No bookings in this slot.</p>
          <div v-else class="ap-upcoming-list">
            <div v-for="b in intervalBookings" :key="b.id" class="ap-upcoming-row">
              <div class="ap-upcoming-info">
                <p class="ap-upcoming-client">#{{ b.id }} · {{ b.customer_name }}</p>
                <p class="ap-upcoming-svc">{{ b.duration_label }}</p>
              </div>
              <span class="ap-badge" :class="'ap-badge--' + b.status">{{ statusLabel(b.status) }}</span>
            </div>
          </div>
        </template>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, watch, onMounted } from 'vue'
import { Users, Gauge, CalendarCheck, CircleCheck, CalendarDays } from 'lucide-vue-next'
import { capacityApi } from '@/services/admin'
import { formatTime12 } from '@/utils/format'
import { apiErrorMessage } from '@/services/api'

function todayIso() {
  const d = new Date()
  return `${d.getFullYear()}-${String(d.getMonth() + 1).padStart(2, '0')}-${String(d.getDate()).padStart(2, '0')}`
}

const date = ref(todayIso())
const summary = ref(null)
const timeline = ref([])
const analytics = ref(null)
const loading = ref(true)
const error = ref(null)

const selectedTime = ref(null)
const intervalBookings = ref([])
const intervalLoading = ref(false)

const prettyDate = computed(() => new Date(date.value + 'T00:00:00').toLocaleDateString('en-US', { weekday: 'long', month: 'long', day: 'numeric' }))

async function loadAll() {
  loading.value = true
  error.value = null
  selectedTime.value = null
  try {
    const [s, t, a] = await Promise.all([
      capacityApi.summary(date.value),
      capacityApi.timeline(date.value),
      capacityApi.analytics(date.value)
    ])
    summary.value = s.data.data
    timeline.value = t.data.data
    analytics.value = a.data.data
  } catch (e) {
    error.value = apiErrorMessage(e)
  } finally {
    loading.value = false
  }
}

async function selectInterval(time) {
  selectedTime.value = time
  intervalLoading.value = true
  try {
    const res = await capacityApi.bookingsAtInterval(date.value, time)
    intervalBookings.value = res.data.data
  } catch {
    intervalBookings.value = []
  } finally {
    intervalLoading.value = false
  }
}

watch(date, loadAll)
onMounted(loadAll)

function fillPct(row) { return row.capacity ? Math.round((row.reserved / row.capacity) * 100) : 0 }
function statusColor(s) { return s === 'Full' ? '#ef4444' : s === 'Limited' ? '#d97706' : '#22c55e' }
function statusBadge(s) { return s === 'Full' ? 'ap-badge--full' : s === 'Limited' ? 'ap-badge--limited' : 'ap-badge--available' }
function statusLabel(s) { return (s || '').replace(/_/g, ' ').replace(/\b\w/g, (c) => c.toUpperCase()) }
</script>

<style scoped>
.cap-toolbar { display:flex; align-items:center; justify-content:space-between; gap:1rem; flex-wrap:wrap; }
.cap-date { max-width:180px; }
.cap-timeline { margin-top:1rem; display:flex; flex-direction:column; gap:6px; }
.cap-row { display:flex; align-items:center; gap:.75rem; width:100%; background:#fff; border:1px solid var(--pink-100); border-radius:8px; padding:.4rem .7rem; cursor:pointer; transition:all .14s; }
.cap-row:hover { border-color:var(--primary); background:var(--pink-50); }
.cap-row.is-active { border-color:var(--primary); box-shadow:0 0 0 1px var(--primary); }
.cap-row__time { font-size:.78rem; font-weight:700; color:#1f2937; min-width:64px; text-align:left; }
.cap-row__track { flex:1; }
.cap-row__nums { font-size:.75rem; color:#6b7280; min-width:36px; text-align:right; }
.cap-analytics { display:grid; grid-template-columns:1fr 1fr; gap:.9rem; margin-top:1rem; }
.cap-stat { background:var(--pink-50); border:1px solid var(--pink-100); border-radius:10px; padding:.8rem .9rem; display:flex; flex-direction:column; gap:.2rem; }
.cap-stat__lbl { font-size:.7rem; text-transform:uppercase; letter-spacing:.05em; color:#9ca3af; font-weight:600; }
.cap-stat__val { font-size:1.1rem; font-weight:700; color:#1f2937; }
</style>
