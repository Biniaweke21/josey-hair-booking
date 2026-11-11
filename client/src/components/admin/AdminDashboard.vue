<template>
  <div class="ap-page">
    <!-- KPI CARDS -->
    <div class="ap-kpi-grid">
      <template v-if="loading">
        <div v-for="n in 4" :key="'ksk'+n" class="ap-card ap-kpi">
          <div class="ap-kpi__icon skeleton" style="border-radius:12px;"></div>
          <div style="flex:1">
            <span class="skeleton" style="height:.75rem;width:60%;display:block;margin-bottom:.5rem;"></span>
            <span class="skeleton" style="height:1.4rem;width:40%;display:block;"></span>
          </div>
        </div>
      </template>
      <template v-else>
        <div v-for="k in kpis" :key="k.label" class="ap-card ap-kpi">
          <div class="ap-kpi__icon" :class="'c-' + k.color"><component :is="k.icon" :size="22" /></div>
          <div>
            <p class="ap-kpi__label">{{ k.label }}</p>
            <p class="ap-kpi__val">{{ k.value }}</p>
            <p class="ap-kpi__sub">{{ k.sub }}</p>
          </div>
        </div>
      </template>
    </div>

    <p v-if="error" class="state-note state-note--error">
      {{ error }} <button class="state-retry" @click="load">Retry</button>
    </p>

    <!-- TODAY'S SERVICES BOOKED -->
    <div class="ap-card">
      <div class="ap-table-hd">
        <div>
          <h3 class="ap-card__title">Today's Appointments</h3>
          <p class="ap-card__sub">{{ todayServices.length }} service{{ todayServices.length === 1 ? '' : 's' }} · {{ todayServiceTotal }} booked today</p>
        </div>
        <button class="ap-btn-outline-sm" @click="$emit('go', 'bookings')">Manage all</button>
      </div>

      <div v-if="loading" class="skeleton" style="height:120px;margin-top:1rem;"></div>
      <p v-else-if="todayServices.length === 0" class="ap-empty">No bookings today.</p>
      <div v-else class="dash-svc-grid">
        <div v-for="s in todayServices" :key="s.name" class="dash-svc-card">
          <div class="dash-svc-card__hd">
            <span v-if="s.category" class="dash-svc-card__cat">{{ s.category }}</span>
            <span class="dash-svc-card__count">×{{ s.count }}</span>
          </div>
          <p class="dash-svc-card__name">{{ s.name }}</p>
          <p class="dash-svc-card__times">{{ s.times.join(' · ') }}</p>
        </div>
      </div>
    </div>

    <!-- STATUS + UPCOMING -->
    <div class="ap-two-col">
      <div class="ap-card">
        <h3 class="ap-card__title">Today's Bookings by Status</h3>
        <p class="ap-card__sub">{{ todayShort }} · Total: {{ statusTotal }}</p>

        <div v-if="statusTotal === 0" class="ap-empty">No bookings today yet.</div>
        <div v-else class="ap-pie-container">
          <svg class="ap-pie-chart" viewBox="0 0 100 100">
            <circle
              v-for="(segment, index) in pieSegments" :key="index"
              class="ap-pie-segment"
              cx="50" cy="50" r="40"
              :stroke="segment.color" stroke-width="20" fill="transparent"
              :stroke-dasharray="`${segment.length} ${circumference}`"
              :stroke-dashoffset="-segment.offset"
              :style="{ transform: 'rotate(-90deg)', transformOrigin: '50% 50%' }"
            />
          </svg>
          <div class="ap-pie-legend">
            <div v-for="s in statusData" :key="s.label" class="ap-legend-item">
              <span class="ap-legend-dot" :style="{ background: s.color }"></span>
              <span class="ap-legend-label">{{ s.label }}</span>
              <span class="ap-legend-val">{{ s.value }} ({{ Math.round(s.value / statusTotal * 100) }}%)</span>
            </div>
          </div>
        </div>
      </div>

      <div class="ap-card">
        <h3 class="ap-card__title">Upcoming Appointments</h3>
        <p class="ap-card__sub">Next {{ upcomingNext.length }} of {{ upcomingCount }} confirmed ahead</p>
        <div class="ap-upcoming-list">
          <div v-if="loading" class="skeleton" style="height:120px;"></div>
          <template v-else>
            <div v-for="a in upcomingNext" :key="a.id" class="ap-upcoming-row">
              <span class="ap-upcoming-time">{{ a.date }}<br />{{ a.time }}</span>
              <div class="ap-upcoming-info">
                <p class="ap-upcoming-client">{{ a.client }}</p>
                <p class="ap-upcoming-svc">{{ a.service }}</p>
              </div>
              <span class="ap-badge" :class="'ap-badge--' + a.status">{{ statusLabel(a.status) }}</span>
            </div>
            <p v-if="upcomingNext.length === 0" class="ap-empty">No upcoming appointments</p>
          </template>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { Calendar, CalendarClock, DollarSign, Users } from 'lucide-vue-next'
import { dashboardApi } from '@/services/admin'
import { formatCurrency, formatTime12 } from '@/utils/format'
import { apiErrorMessage } from '@/services/api'

defineEmits(['go'])

const summary = ref(null)
const loading = ref(true)
const error = ref(null)
const todayShort = new Date().toLocaleDateString('en-US', { month: 'short', day: 'numeric' })

async function load() {
  loading.value = true
  error.value = null
  try {
    const res = await dashboardApi.summary()
    summary.value = res.data.data
  } catch (e) {
    error.value = apiErrorMessage(e)
  } finally {
    loading.value = false
  }
}
onMounted(load)

function statusLabel(s) { return (s || '').replace(/_/g, ' ').replace(/\b\w/g, (c) => c.toUpperCase()) }
function serviceSummary(items) {
  if (!items || !items.length) return '—'
  return items.length === 1 ? items[0].name : `${items[0].name} +${items.length - 1}`
}

const kpis = computed(() => {
  const s = summary.value
  if (!s) return []
  return [
    { label: "Today's Bookings", value: s.today.counts.total, sub: 'scheduled today', color: 'blue', icon: Calendar },
    { label: 'Upcoming', value: s.upcoming.count, sub: 'confirmed ahead', color: 'navy', icon: CalendarClock },
    { label: 'Revenue Today', value: formatCurrency(s.revenue.today), sub: 'from completed', color: 'green', icon: DollarSign },
    { label: 'New Customers', value: s.customers.new_this_month, sub: 'this month', color: 'amber', icon: Users }
  ]
})

// Group every service line across today's bookings so the admin sees, at a
// glance, which services (and how many of each) are on the books today —
// e.g. "Braids & Twists · Box Braids ×3", "Color · Hair Color ×5".
const todayServices = computed(() => {
  const bookings = summary.value?.today.bookings || []
  const map = new Map()
  for (const b of bookings) {
    for (const item of b.items || []) {
      const key = item.name
      if (!map.has(key)) {
        map.set(key, { name: item.name, category: item.category || null, count: 0, times: [] })
      }
      const entry = map.get(key)
      entry.count++
      entry.times.push(formatTime12(b.start_time))
    }
  }
  return [...map.values()].sort((a, b) => b.count - a.count || a.name.localeCompare(b.name))
})
const todayServiceTotal = computed(() => todayServices.value.reduce((sum, s) => sum + s.count, 0))

const upcomingCount = computed(() => summary.value?.upcoming.count || 0)
const upcomingNext = computed(() =>
  (summary.value?.upcoming.next || []).map((b) => ({
    id: b.id,
    date: new Date(b.booking_date + 'T00:00:00').toLocaleDateString('en-US', { month: 'short', day: 'numeric' }),
    time: formatTime12(b.start_time),
    client: b.customer?.name || 'Guest',
    service: serviceSummary(b.items),
    status: b.status
  }))
)

const statusData = computed(() => {
  const c = summary.value?.today.counts
  if (!c) return []
  return [
    { label: 'Confirmed', value: c.confirmed || 0, color: '#5BA7FF' },
    { label: 'Checked In', value: c.checked_in || 0, color: '#1D4ED8' },
    { label: 'Completed', value: c.completed || 0, color: '#22c55e' },
    { label: 'Cancelled', value: c.cancelled || 0, color: '#ef4444' },
    { label: 'No Show', value: c.no_show || 0, color: '#6b7280' }
  ].filter((s) => s.value > 0)
})
const statusTotal = computed(() => statusData.value.reduce((a, b) => a + b.value, 0))

const circumference = 2 * Math.PI * 40
const pieSegments = computed(() => {
  if (statusTotal.value === 0) return []
  let offset = 0
  return statusData.value.map((s) => {
    const length = circumference * (s.value / statusTotal.value)
    const seg = { color: s.color, length, offset }
    offset += length
    return seg
  })
})
</script>

<style scoped>
.dash-svc-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(190px, 1fr));
  gap: 0.85rem;
  margin-top: 1rem;
}
.dash-svc-card {
  background: #F8FAFC;
  border: 1px solid #E5E7EB;
  border-radius: 12px;
  padding: 0.9rem 1rem;
  transition: border-color 0.15s, box-shadow 0.15s;
}
.dash-svc-card:hover {
  border-color: #2563EB;
  box-shadow: 0 2px 10px rgba(37, 99, 235, 0.08);
}
.dash-svc-card__hd {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 0.5rem;
  margin-bottom: 0.4rem;
}
.dash-svc-card__cat {
  font-size: 0.66rem;
  font-weight: 700;
  text-transform: uppercase;
  letter-spacing: 0.04em;
  color: #2563EB;
  background: #EFF6FF;
  padding: 0.15rem 0.45rem;
  border-radius: 999px;
}
.dash-svc-card__count {
  font-size: 1.1rem;
  font-weight: 800;
  color: #1F2937;
  margin-left: auto;
}
.dash-svc-card__name {
  font-weight: 700;
  font-size: 0.95rem;
  color: #1F2937;
  margin-bottom: 0.3rem;
}
.dash-svc-card__times {
  font-size: 0.75rem;
  color: #6B7280;
  line-height: 1.5;
}
</style>
