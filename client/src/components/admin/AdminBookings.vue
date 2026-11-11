<template>
  <div class="ap-page">
    <!-- Toolbar -->
    <div class="ap-card bk-toolbar">
      <div class="bk-views">
        <button class="bk-view-btn" :class="{ 'is-active': view==='table' }" @click="view='table'"><Table2 :size="15" /> Table</button>
        <button class="bk-view-btn" :class="{ 'is-active': view==='calendar' }" @click="view='calendar'"><CalendarDays :size="15" /> Calendar</button>
      </div>
      <div class="bk-filters">
        <select v-model="statusFilter" class="ap-select" @change="reload">
          <option value="">All statuses</option>
          <option value="confirmed">Confirmed</option>
          <option value="checked_in">Checked In</option>
          <option value="completed">Completed</option>
          <option value="cancelled">Cancelled</option>
          <option value="no_show">No Show</option>
        </select>
        <input type="date" v-model="dateFilter" class="ap-input bk-date" @change="reload" />
        <button v-if="dateFilter || statusFilter" class="ap-btn-outline-sm" @click="clearFilters">Clear</button>
      </div>
    </div>

    <p v-if="error" class="state-note state-note--error">{{ error }} <button class="state-retry" @click="reload">Retry</button></p>

    <!-- TABLE VIEW -->
    <div v-if="view==='table'" class="ap-card ap-table-card">
      <div class="ap-table-scroll">
        <table class="ap-table">
          <thead><tr><th>Date / Time</th><th>Customer</th><th>Services</th><th>Staff</th><th>Total</th><th>Status</th><th>Actions</th></tr></thead>
          <tbody>
            <tr v-if="loading"><td colspan="7"><div class="skeleton" style="height:120px;"></div></td></tr>
            <tr v-else-if="!bookings.length"><td colspan="7" class="ap-empty">No bookings found.</td></tr>
            <tr v-for="b in bookings" :key="b.id">
              <td><span class="ap-td-date">{{ shortDate(b.booking_date) }}</span><span class="ap-td-time">{{ formatTime12(b.start_time) }}</span></td>
              <td><div class="ap-td-client"><div class="ap-td-avatar">{{ (b.customer?.name || '?')[0] }}</div>{{ b.customer?.name || 'Guest' }}</div></td>
              <td>{{ serviceSummary(b.items) }}</td>
              <td>{{ b.employees?.length ? b.employees.map(e=>e.name).join(', ') : 'Unassigned' }}</td>
              <td><strong>{{ formatCurrency(b.total_price) }}</strong></td>
              <td><span class="ap-badge" :class="'ap-badge--'+b.status">{{ statusLabel(b.status) }}</span></td>
              <td><button class="ap-btn-outline-sm" @click="openDetails(b)">Manage</button></td>
            </tr>
          </tbody>
        </table>
      </div>
      <div v-if="meta.last_page > 1" class="bk-pager">
        <button class="ap-btn-outline-sm" :disabled="meta.current_page<=1" @click="goPage(meta.current_page-1)">Prev</button>
        <span class="bk-pager__info">Page {{ meta.current_page }} of {{ meta.last_page }}</span>
        <button class="ap-btn-outline-sm" :disabled="meta.current_page>=meta.last_page" @click="goPage(meta.current_page+1)">Next</button>
      </div>
    </div>

    <!-- CALENDAR VIEW -->
    <div v-else class="ap-card">
      <div class="cal-header">
        <button class="cal-nav" @click="calShift(-1)" aria-label="Previous month"><ChevronLeft :size="18" /></button>
        <span class="cal-month-label">{{ calLabel }}</span>
        <button class="cal-nav" @click="calShift(1)" aria-label="Next month"><ChevronRight :size="18" /></button>
      </div>
      <div class="cal-dow-row" aria-hidden="true"><span v-for="d in dow" :key="d" class="cal-dow">{{ d }}</span></div>
      <div class="cal-grid">
        <div v-for="n in calOffset" :key="'e'+n" class="cal-cell cal-cell--empty"></div>
        <button v-for="day in calDays" :key="day.iso" class="bk-cal-cell" :class="{ 'has': day.count>0 }" @click="pickCalDay(day.iso)">
          <span class="bk-cal-num">{{ day.num }}</span>
          <span v-if="day.count>0" class="bk-cal-count">{{ day.count }}</span>
        </button>
      </div>
      <p class="ap-card__sub" style="margin-top:.75rem;">Click a day to view its bookings in the table.</p>
    </div>

    <!-- DETAILS MODAL -->
    <div v-if="detail" class="ap-modal" @click.self="closeDetails">
      <div class="ap-modal-content bk-modal">
        <div class="ap-modal-header">
          <h3>Booking #{{ detail.id }} · {{ statusLabel(detail.status) }}</h3>
          <button class="ap-modal-close" @click="closeDetails"><X :size="18" /></button>
        </div>

        <div class="bk-modal__body">
          <div v-if="detailLoading" class="skeleton" style="height:200px;"></div>
          <template v-else>
            <!-- Summary -->
            <div class="bk-summary">
              <div><span class="bk-k">Customer</span><span class="bk-v">{{ detail.customer?.name }}</span></div>
              <div><span class="bk-k">Contact</span><span class="bk-v">{{ detail.customer?.phone || detail.customer?.email || '—' }}</span></div>
              <div><span class="bk-k">Date</span><span class="bk-v">{{ shortDate(detail.booking_date) }} · {{ formatTime12(detail.start_time) }}–{{ formatTime12(detail.end_time) }}</span></div>
              <div><span class="bk-k">Total</span><span class="bk-v">{{ formatCurrency(detail.total_price) }} · {{ formatDuration(detail.total_duration_minutes) }}</span></div>
              <div v-if="detail.notes" class="bk-summary__full"><span class="bk-k">Notes</span><span class="bk-v">{{ detail.notes }}</span></div>
            </div>

            <!-- Per-service employee assignment -->
            <h4 class="bk-sec">Services &amp; Staff</h4>
            <div v-for="item in detail.items" :key="item.id" class="bk-item">
              <div class="bk-item__info">
                <p class="bk-item__name">{{ item.name }}</p>
                <p class="bk-item__meta">{{ formatCurrency(item.price) }} · {{ formatDuration(item.duration_minutes) }}</p>
              </div>
              <select
                class="ap-select"
                :value="item.employee?.id || ''"
                :disabled="isClosed(detail.status) || assigningItem===item.id"
                @change="assignEmployee(item, $event.target.value)"
              >
                <option value="">Unassigned</option>
                <option v-for="e in assignableEmployees" :key="e.id" :value="e.id">{{ e.name }}</option>
              </select>
              <p v-if="!assignableEmployees.length" style="font-size:.75rem;color:#9ca3af;margin-top:.25rem;">No staff available for this time slot.</p>
            </div>

            <p v-if="actionError" class="state-note state-note--error">{{ actionError }}</p>

            <!-- Lifecycle actions -->
            <h4 class="bk-sec">Actions</h4>
            <div class="bk-actions">
              <button v-if="detail.status==='confirmed'" class="ap-btn-primary" :disabled="acting" @click="doCheckIn"><LogIn :size="15" /> Check In</button>
              <button v-if="detail.status==='checked_in'" class="ap-btn-primary" :disabled="acting" @click="doCheckOut"><LogOut :size="15" /> Check Out</button>
              <button v-if="canReschedule" class="ap-btn-outline" :disabled="acting" @click="openReschedule"><CalendarClock :size="15" /> Reschedule</button>
              <button v-if="!isClosed(detail.status)" class="ap-btn-no-sm" :disabled="acting" @click="doCancel"><Ban :size="15" /> Cancel</button>
              <button v-if="detail.status==='confirmed'" class="ap-btn-no-sm" :disabled="acting" @click="doNoShow">No Show</button>
            </div>

            <!-- Reschedule inline -->
            <div v-if="rescheduling" class="bk-reschedule">
              <input type="date" v-model="rescheduleDate" class="ap-input" />
              <input type="time" v-model="rescheduleTime" class="ap-input" />
              <button class="ap-btn-primary" :disabled="acting || !rescheduleDate || !rescheduleTime" @click="doReschedule">Confirm</button>
              <button class="ap-btn-outline-sm" @click="rescheduling=false">Cancel</button>
            </div>

            <!-- Timeline -->
            <h4 class="bk-sec">Timeline</h4>
            <div v-if="timelineLoading" class="skeleton" style="height:80px;"></div>
            <ul v-else-if="timeline.length" class="bk-timeline">
              <li v-for="ev in timeline" :key="ev.id" class="bk-tl">
                <span class="bk-tl__dot"></span>
                <div><p class="bk-tl__desc">{{ ev.description }}</p><p class="bk-tl__time">{{ dateTime(ev.created_at) }}<span v-if="ev.caused_by"> · {{ ev.caused_by.name }}</span></p></div>
              </li>
            </ul>
            <p v-else class="ap-empty">No history yet.</p>
          </template>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, reactive, computed, onMounted } from 'vue'
import { Table2, CalendarDays, ChevronLeft, ChevronRight, X, LogIn, LogOut, CalendarClock, Ban } from 'lucide-vue-next'
import { bookingsApi } from '@/services/admin'
import { formatCurrency, formatTime12, formatDuration } from '@/utils/format'
import { apiErrorMessage } from '@/services/api'
import { useToast } from '@/composables/useToast'
import { useConfirm } from '@/composables/useConfirm'

const toast = useToast()
const confirm = useConfirm()

const view = ref('table')
const bookings = ref([])
const meta = reactive({ current_page: 1, last_page: 1, total: 0 })
const loading = ref(true)
const error = ref(null)
const statusFilter = ref('')
const dateFilter = ref('')

// ── List ──────────────────────────────────────────────────
async function fetchBookings(page = 1) {
  loading.value = true
  error.value = null
  try {
    const params = { page }
    if (statusFilter.value) params.status = statusFilter.value
    if (dateFilter.value) params.booking_date = dateFilter.value
    const res = await bookingsApi.list(params)
    bookings.value = res.data.data
    Object.assign(meta, res.data.meta || { current_page: 1, last_page: 1, total: bookings.value.length })
  } catch (e) {
    error.value = apiErrorMessage(e)
  } finally {
    loading.value = false
  }
}
function reload() { fetchBookings(1) }
function goPage(p) { fetchBookings(p) }
function clearFilters() { statusFilter.value = ''; dateFilter.value = ''; reload() }

onMounted(async () => {
  await fetchBookings()
  buildCalendar()
})

// ── Details modal ─────────────────────────────────────────
const detail = ref(null)
const detailLoading = ref(false)
const timeline = ref([])
const timelineLoading = ref(false)
const actionError = ref(null)
const acting = ref(false)
const assigningItem = ref(null)
const availableStaff = ref([])

// Staff picker options: whoever's actually free for this slot, plus
// whoever's already assigned (so the dropdown never shows a blank value).
const assignableEmployees = computed(() => {
  const list = [...availableStaff.value]
  for (const item of detail.value?.items || []) {
    if (item.employee && !list.some((e) => e.id === item.employee.id)) list.push(item.employee)
  }
  return list
})

async function openDetails(b) {
  detail.value = b
  detailLoading.value = true
  actionError.value = null
  rescheduling.value = false
  try {
    const res = await bookingsApi.get(b.id)
    detail.value = res.data.data
  } catch (e) {
    actionError.value = apiErrorMessage(e)
  } finally {
    detailLoading.value = false
  }
  loadTimeline(b.id)
  loadAvailableStaff(b.id)
}
function closeDetails() { detail.value = null; timeline.value = []; availableStaff.value = [] }

async function loadTimeline(id) {
  timelineLoading.value = true
  try { timeline.value = (await bookingsApi.timeline(id)).data.data } catch { timeline.value = [] } finally { timelineLoading.value = false }
}

async function loadAvailableStaff(id) {
  try { availableStaff.value = (await bookingsApi.availableEmployees(id)).data.data } catch { availableStaff.value = [] }
}

async function refreshDetail() {
  const res = await bookingsApi.get(detail.value.id)
  detail.value = res.data.data
  await fetchBookings(meta.current_page)
  loadTimeline(detail.value.id)
  loadAvailableStaff(detail.value.id)
}

async function assignEmployee(item, employeeId) {
  assigningItem.value = item.id
  actionError.value = null
  try {
    await bookingsApi.assignItemEmployee(detail.value.id, item.id, employeeId ? Number(employeeId) : null)
    await refreshDetail()
    toast.success(employeeId ? 'Staff assigned.' : 'Staff unassigned.')
  } catch (e) {
    actionError.value = apiErrorMessage(e)
  } finally {
    assigningItem.value = null
  }
}

async function runAction(fn, successMsg) {
  acting.value = true
  actionError.value = null
  try { await fn(); await refreshDetail(); if (successMsg) toast.success(successMsg) } catch (e) { actionError.value = apiErrorMessage(e) } finally { acting.value = false }
}
const doCheckIn = () => runAction(() => bookingsApi.checkIn(detail.value.id), 'Customer checked in.')
const doCheckOut = () => runAction(() => bookingsApi.checkOut(detail.value.id), 'Customer checked out.')
const doCancel = async () => {
  const reason = await confirm({
    title: 'Cancel booking', message: 'This will cancel the booking and email the customer.',
    confirmText: 'Cancel booking', danger: true, withInput: true, inputLabel: 'Reason (optional)', inputPlaceholder: 'e.g. Customer request'
  })
  if (reason === false) return
  runAction(() => bookingsApi.updateStatus(detail.value.id, 'cancelled', reason || ''), 'Booking cancelled.')
}
const doNoShow = async () => {
  if (!(await confirm({ title: 'Mark as no-show', message: 'Mark this booking as a no-show?', confirmText: 'Mark no-show', danger: true }))) return
  runAction(() => bookingsApi.updateStatus(detail.value.id, 'no_show'), 'Marked as no-show.')
}

// Reschedule
const rescheduling = ref(false)
const rescheduleDate = ref('')
const rescheduleTime = ref('')
const canReschedule = computed(() => detail.value && detail.value.status === 'confirmed')
function openReschedule() {
  rescheduleDate.value = detail.value.booking_date
  rescheduleTime.value = (detail.value.start_time || '').slice(0, 5)
  rescheduling.value = true
}
async function doReschedule() {
  await runAction(() => bookingsApi.reschedule(detail.value.id, { booking_date: rescheduleDate.value, start_time: rescheduleTime.value }), 'Booking rescheduled.')
  rescheduling.value = false
}

// ── Calendar view ─────────────────────────────────────────
const dow = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat']
const MONTHS = ['January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December']
const calYear = ref(new Date().getFullYear())
const calMonth = ref(new Date().getMonth())
const calCounts = ref({})
const calLabel = computed(() => `${MONTHS[calMonth.value]} ${calYear.value}`)
const calOffset = computed(() => new Date(calYear.value, calMonth.value, 1).getDay())
const calDays = computed(() => {
  const days = []
  const n = new Date(calYear.value, calMonth.value + 1, 0).getDate()
  for (let i = 1; i <= n; i++) {
    const iso = `${calYear.value}-${String(calMonth.value + 1).padStart(2, '0')}-${String(i).padStart(2, '0')}`
    days.push({ num: i, iso, count: calCounts.value[iso] || 0 })
  }
  return days
})
async function buildCalendar() {
  // Pull the month's bookings once to build per-day counts.
  try {
    const from = `${calYear.value}-${String(calMonth.value + 1).padStart(2, '0')}-01`
    const res = await bookingsApi.list({ per_page: 500 })
    const counts = {}
    for (const b of res.data.data) counts[b.booking_date] = (counts[b.booking_date] || 0) + 1
    calCounts.value = counts
  } catch { calCounts.value = {} }
}
function calShift(d) {
  let m = calMonth.value + d, y = calYear.value
  if (m < 0) { m = 11; y-- } else if (m > 11) { m = 0; y++ }
  calMonth.value = m; calYear.value = y
  buildCalendar()
}
function pickCalDay(iso) { dateFilter.value = iso; view.value = 'table'; reload() }

// ── Helpers ───────────────────────────────────────────────
function serviceSummary(items) { if (!items?.length) return '—'; return items.length === 1 ? items[0].name : `${items[0].name} +${items.length - 1}` }
function statusLabel(s) { return (s || '').replace(/_/g, ' ').replace(/\b\w/g, (c) => c.toUpperCase()) }
function isClosed(s) { return ['completed', 'cancelled', 'no_show'].includes(s) }
function shortDate(d) { return d ? new Date(d + 'T00:00:00').toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' }) : '' }
function dateTime(iso) { return iso ? new Date(iso).toLocaleString('en-US', { month: 'short', day: 'numeric', hour: 'numeric', minute: '2-digit' }) : '' }
</script>

<style scoped>
.bk-toolbar { display:flex; align-items:center; justify-content:space-between; gap:1rem; flex-wrap:wrap; }
.bk-views { display:flex; gap:.4rem; }
.bk-view-btn { display:flex; align-items:center; gap:.35rem; font-family:'Outfit',sans-serif; font-size:.82rem; font-weight:600; padding:.45rem .9rem; border:1.5px solid var(--pink-100); border-radius:8px; background:#fff; color:#64748b; cursor:pointer; transition:all .14s; }
.bk-view-btn.is-active { background:var(--primary); border-color:var(--primary); color:#fff; }
.bk-filters { display:flex; gap:.5rem; align-items:center; flex-wrap:wrap; }
.bk-date { max-width:160px; }
.bk-pager { display:flex; align-items:center; justify-content:center; gap:1rem; padding:1rem; }
.bk-pager__info { font-size:.8rem; color:#6b7280; }

.bk-modal { max-width:640px; }
.bk-modal__body { padding:1.5rem; }
.bk-summary { display:grid; grid-template-columns:1fr 1fr; gap:.9rem; background:var(--pink-50); border:1px solid var(--pink-100); border-radius:10px; padding:1rem; }
.bk-summary__full { grid-column:1/-1; }
.bk-k { display:block; font-size:.68rem; text-transform:uppercase; letter-spacing:.06em; color:#9ca3af; font-weight:700; margin-bottom:.15rem; }
.bk-v { font-size:.85rem; color:#1f2937; font-weight:600; }
.bk-sec { font-family:'Outfit', sans-serif; font-size:1rem; font-weight:700; color:#1f2937; margin:1.4rem 0 .7rem; }
.bk-item { display:flex; align-items:center; justify-content:space-between; gap:1rem; padding:.7rem .9rem; border:1px solid var(--pink-100); border-radius:10px; margin-bottom:.5rem; }
.bk-item__name { font-size:.86rem; font-weight:600; color:#1f2937; }
.bk-item__meta { font-size:.75rem; color:#9ca3af; }
.bk-actions { display:flex; flex-wrap:wrap; gap:.5rem; }
.bk-reschedule { display:flex; flex-wrap:wrap; gap:.5rem; align-items:center; margin-top:.75rem; }
.bk-timeline { list-style:none; display:flex; flex-direction:column; gap:.75rem; }
.bk-tl { display:flex; gap:.6rem; align-items:flex-start; }
.bk-tl__dot { width:9px; height:9px; border-radius:50%; background:var(--primary); margin-top:5px; flex-shrink:0; }
.bk-tl__desc { font-size:.83rem; color:#374151; }
.bk-tl__time { font-size:.72rem; color:#9ca3af; margin-top:1px; }

.bk-cal-cell { aspect-ratio:1; border:1.5px solid var(--pink-100); border-radius:8px; background:#fff; cursor:pointer; display:flex; flex-direction:column; align-items:center; justify-content:center; gap:.2rem; transition:all .14s; position:relative; }
.bk-cal-cell:hover { border-color:var(--primary); background:var(--pink-50); }
.bk-cal-cell.has { border-color:var(--primary); }
.bk-cal-num { font-size:.82rem; font-weight:600; color:#1f2937; }
.bk-cal-count { font-size:.62rem; font-weight:800; color:#fff; background:var(--primary); border-radius:999px; padding:.05rem .4rem; }
</style>
