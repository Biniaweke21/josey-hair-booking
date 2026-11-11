<template>
  <div class="ap-page">
    <!-- Range -->
    <div class="ap-card cust-toolbar">
      <div><h3 class="ap-card__title">{{ title }}</h3><p class="ap-card__sub">{{ from }} → {{ to }}</p></div>
      <div class="bk-filters rp-filters">
        <label class="rp-filter"><span>From</span><input type="date" v-model="from" class="ap-input bk-date" /></label>
        <label class="rp-filter"><span>To</span><input type="date" v-model="to" class="ap-input bk-date" /></label>
        <button class="ap-btn-primary" @click="load" title="Reload every report below for this date range">Apply date range</button>
      </div>
    </div>

    <p v-if="error" class="state-note state-note--error">{{ error }} <button class="state-retry" @click="load">Retry</button></p>

    <template v-if="loading">
      <div class="ap-card"><div class="skeleton" style="height:160px;"></div></div>
    </template>
    <template v-else>
      <!-- Revenue -->
      <div class="ap-card">
        <h3 class="ap-card__title">Revenue</h3>
        <p class="ap-card__sub">Total: {{ formatCurrency(totalRevenue) }} · {{ revenue.length }} periods</p>
        <div v-if="!revenue.length" class="ap-empty">No revenue in this range.</div>
        <div v-else class="ap-rev-list">
          <div v-for="r in revenue" :key="r.period" class="ap-rev-row">
            <span class="ap-rev-name">{{ label(r.period) }}</span>
            <div class="ap-rev-bar-wrap"><div class="ap-rev-bar" :style="{ width: revPct(r.revenue) + '%' }"></div></div>
            <span class="ap-rev-amt">{{ formatCurrency(r.revenue) }}</span>
          </div>
        </div>
      </div>

      <div class="ap-two-col">
        <!-- Popular services -->
        <div class="ap-card ap-table-card">
          <div class="ap-table-hd"><div><h3 class="ap-card__title">Popular Services</h3></div></div>
          <div class="ap-table-scroll">
            <table class="ap-table">
              <thead><tr><th>Service</th><th>Bookings</th><th>Revenue</th></tr></thead>
              <tbody>
                <tr v-for="s in popular" :key="s.service_id"><td>{{ s.service_name }}</td><td>{{ s.bookings_count }}</td><td><strong>{{ formatCurrency(s.revenue) }}</strong></td></tr>
                <tr v-if="!popular.length"><td colspan="3" class="ap-empty">No data.</td></tr>
              </tbody>
            </table>
          </div>
        </div>

        <!-- Employee workload -->
        <div class="ap-card ap-table-card">
          <div class="ap-table-hd"><div><h3 class="ap-card__title">Employee Workload</h3></div></div>
          <div class="ap-table-scroll">
            <table class="ap-table">
              <thead><tr><th>Employee</th><th>Services</th><th>Hours</th></tr></thead>
              <tbody>
                <tr v-for="w in workload" :key="w.employee_id"><td>{{ w.employee_name }}</td><td>{{ w.services_count }}</td><td>{{ w.total_hours }}h</td></tr>
                <tr v-if="!workload.length"><td colspan="3" class="ap-empty">No data.</td></tr>
              </tbody>
            </table>
          </div>
        </div>
      </div>

      <!-- Top customers + bookings breakdown -->
      <div class="ap-two-col">
        <div class="ap-card ap-table-card">
          <div class="ap-table-hd"><div><h3 class="ap-card__title">Top Customers</h3></div></div>
          <div class="ap-table-scroll">
            <table class="ap-table">
              <thead><tr><th>Customer</th><th>Bookings</th><th>Spent</th></tr></thead>
              <tbody>
                <tr v-for="c in topCustomers" :key="c.customer_id"><td>{{ c.customer_name }}</td><td>{{ c.bookings_count }}</td><td><strong>{{ formatCurrency(c.total_spent) }}</strong></td></tr>
                <tr v-if="!topCustomers.length"><td colspan="3" class="ap-empty">No data.</td></tr>
              </tbody>
            </table>
          </div>
        </div>

        <div class="ap-card ap-table-card">
          <div class="ap-table-hd"><div><h3 class="ap-card__title">Bookings by Period</h3></div></div>
          <div class="ap-table-scroll">
            <table class="ap-table">
              <thead><tr><th>Period</th><th>Total</th><th>Completed</th><th>Cancelled</th></tr></thead>
              <tbody>
                <tr v-for="b in bookingsSeries" :key="b.period"><td>{{ label(b.period) }}</td><td>{{ b.total }}</td><td>{{ b.completed }}</td><td>{{ b.cancelled }}</td></tr>
                <tr v-if="!bookingsSeries.length"><td colspan="4" class="ap-empty">No data.</td></tr>
              </tbody>
            </table>
          </div>
        </div>
      </div>
    </template>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { reportsApi } from '@/services/admin'
import { formatCurrency } from '@/utils/format'
import { apiErrorMessage } from '@/services/api'

const props = defineProps({
  title: { type: String, default: 'Report' },
  groupBy: { type: String, default: 'day' },
  defaultFrom: { type: String, required: true },
  defaultTo: { type: String, required: true }
})

const from = ref(props.defaultFrom)
const to = ref(props.defaultTo)
const loading = ref(true)
const error = ref(null)

const revenue = ref([])
const bookingsSeries = ref([])
const popular = ref([])
const workload = ref([])
const topCustomers = ref([])

async function load() {
  loading.value = true; error.value = null
  const params = { from: from.value, to: to.value, group_by: props.groupBy }
  try {
    const [rev, bk, pop, wl, tc] = await Promise.all([
      reportsApi.revenueTimeSeries(params),
      reportsApi.bookingsTimeSeries(params),
      reportsApi.popularServices(params),
      reportsApi.employeeWorkload(params),
      reportsApi.topCustomers(params)
    ])
    revenue.value = rev.data.data
    bookingsSeries.value = bk.data.data
    popular.value = pop.data.data
    workload.value = wl.data.data
    topCustomers.value = tc.data.data
  } catch (e) { error.value = apiErrorMessage(e) } finally { loading.value = false }
}
onMounted(load)

const totalRevenue = computed(() => revenue.value.reduce((a, r) => a + Number(r.revenue), 0))
const maxRevenue = computed(() => Math.max(1, ...revenue.value.map((r) => Number(r.revenue))))
function revPct(v) { return Math.round((Number(v) / maxRevenue.value) * 100) }
function label(period) {
  if (props.groupBy === 'month') return new Date(period + 'T00:00:00').toLocaleDateString('en-US', { month: 'short', year: 'numeric' })
  return new Date(period + 'T00:00:00').toLocaleDateString('en-US', { month: 'short', day: 'numeric' })
}
</script>

<style scoped>
.rp-filters { align-items: flex-end; }
.rp-filter { display: flex; flex-direction: column; gap: .25rem; font-size: .72rem; font-weight: 700; color: #9ca3af; text-transform: uppercase; letter-spacing: .03em; }
</style>
