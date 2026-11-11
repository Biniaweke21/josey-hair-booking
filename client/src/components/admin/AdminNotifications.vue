<template>
  <div class="ap-page">
    <div class="ap-card cust-toolbar">
      <div><h3 class="ap-card__title">Notifications</h3><p class="ap-card__sub">{{ meta.total }} new bookings · {{ unreadTotal }} unread</p></div>
      <button v-if="unreadTotal" class="ap-btn-outline-sm" @click="markAllRead">Mark all as read</button>
    </div>

    <p v-if="error" class="state-note state-note--error">{{ error }} <button class="state-retry" @click="reload">Retry</button></p>

    <div v-if="loading" class="notif-list">
      <div class="skeleton" style="height:140px;"></div>
      <div class="skeleton" style="height:140px;"></div>
    </div>

    <p v-else-if="!logs.length" class="ap-empty">No new bookings yet.</p>

    <div v-else class="notif-list">
      <div
        v-for="n in logs" :key="n.id"
        class="notif-card" :class="{ 'notif-card--unread': !n.read_at }"
        @click="markRead(n)"
      >
        <div class="notif-card__head">
          <span class="notif-card__icon">
            <CalendarPlus :size="16" />
          </span>
          <div class="notif-card__title-wrap">
            <strong><span v-if="!n.read_at" class="notif-card__new">New</span>New Booking</strong>
            <span class="notif-card__time">{{ dateTime(n.created_at) }}</span>
          </div>
        </div>

        <div v-if="n.booking" class="notif-card__body">
          <div class="notif-card__row">
            <User :size="14" />
            <span><strong>{{ n.booking.customer?.name || 'Guest' }}</strong></span>
            <span v-if="n.booking.customer?.email" class="notif-card__muted">{{ n.booking.customer.email }}</span>
            <span v-if="n.booking.customer?.phone" class="notif-card__muted">{{ n.booking.customer.phone }}</span>
          </div>
          <div class="notif-card__row">
            <Calendar :size="14" />
            <span>{{ dateOnly(n.booking.booking_date) }} at {{ formatTime12(n.booking.start_time) }}</span>
            <span class="notif-card__muted">({{ formatDuration(n.booking.total_duration_minutes) }})</span>
          </div>
          <div class="notif-card__row notif-card__services">
            <Scissors :size="14" />
            <div class="notif-card__svc-list">
              <div v-for="item in n.booking.items" :key="item.id" class="notif-card__svc">
                <span>{{ item.name }}</span>
                <span v-if="item.variation_options?.length" class="notif-card__muted">
                  ({{ item.variation_options.map(v => v.name).join(', ') }})
                </span>
                <span v-if="item.add_ons?.length" class="notif-card__muted">
                  + {{ item.add_ons.map(a => a.name).join(', ') }}
                </span>
                <span v-if="item.employee" class="notif-card__muted">— {{ item.employee.name }}</span>
              </div>
            </div>
          </div>
          <div v-if="n.booking.notes" class="notif-card__row">
            <StickyNote :size="14" />
            <span class="notif-card__muted">{{ n.booking.notes }}</span>
          </div>
          <div class="notif-card__row notif-card__total">
            <span>Total</span>
            <strong>{{ formatCurrency(n.booking.total_price) }}</strong>
          </div>
        </div>
      </div>
    </div>

    <div v-if="meta.last_page > 1" class="bk-pager">
      <button class="ap-btn-outline-sm" :disabled="meta.current_page<=1" @click="fetchLogs(meta.current_page-1)">Prev</button>
      <span class="bk-pager__info">Page {{ meta.current_page }} of {{ meta.last_page }}</span>
      <button class="ap-btn-outline-sm" :disabled="meta.current_page>=meta.last_page" @click="fetchLogs(meta.current_page+1)">Next</button>
    </div>
  </div>
</template>

<script setup>
import { ref, reactive, onMounted } from 'vue'
import { CalendarPlus, User, Calendar, Scissors, StickyNote } from 'lucide-vue-next'
import { notificationsApi } from '@/services/admin'
import { apiErrorMessage } from '@/services/api'
import { formatCurrency, formatTime12, formatDuration } from '@/utils/format'

const emit = defineEmits(['notifications-changed'])

const logs = ref([])
const meta = reactive({ current_page: 1, last_page: 1, total: 0 })
const loading = ref(true)
const error = ref(null)
const unreadTotal = ref(0)

async function fetchLogs(page = 1) {
  loading.value = true; error.value = null
  try {
    const [res, countRes] = await Promise.all([
      notificationsApi.logs({ page, type: 'booking_confirmation' }),
      notificationsApi.unreadCount()
    ])
    logs.value = res.data.data
    Object.assign(meta, res.data.meta)
    unreadTotal.value = countRes.data.count ?? 0
  } catch (e) { error.value = apiErrorMessage(e) } finally { loading.value = false }
}
function reload() { fetchLogs(1) }
onMounted(() => fetchLogs())

async function markRead(n) {
  if (n.read_at) return
  n.read_at = new Date().toISOString()
  unreadTotal.value = Math.max(0, unreadTotal.value - 1)
  try { await notificationsApi.markRead(n.id) } finally { emit('notifications-changed') }
}

async function markAllRead() {
  for (const n of logs.value) n.read_at = n.read_at || new Date().toISOString()
  unreadTotal.value = 0
  try { await notificationsApi.markAllRead() } finally { emit('notifications-changed') }
}

function dateTime(iso) { return iso ? new Date(iso).toLocaleString('en-US', { month: 'short', day: 'numeric', hour: 'numeric', minute: '2-digit' }) : '' }
function dateOnly(d) { return d ? new Date(d + 'T00:00:00').toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' }) : '' }
</script>

<style scoped>
.cust-toolbar { display:flex; align-items:center; justify-content:space-between; gap:1rem; flex-wrap:wrap; }

.notif-list { display:flex; flex-direction:column; gap:1rem; }

.notif-card { background:#fff; border:1px solid var(--border, #E5E7EB); border-radius:14px; padding:1.1rem 1.25rem; cursor:pointer; transition:border-color .14s; }
.notif-card--unread { border-left:3px solid #2563EB; background:#F8FAFF; }
.notif-card:hover { border-color:#2563EB; }

.notif-card__head { display:flex; align-items:center; gap:.6rem; margin-bottom:.75rem; }
.notif-card__icon { width:32px; height:32px; border-radius:50%; display:flex; align-items:center; justify-content:center; flex-shrink:0; background:#EFF6FF; color:#2563EB; }
.notif-card__title-wrap { display:flex; flex-direction:column; flex:1; min-width:0; }
.notif-card__time { font-size:.75rem; color:#6B7280; }
.notif-card__new { display:inline-block; background:#ef4444; color:#fff; font-size:.62rem; font-weight:800; text-transform:uppercase; letter-spacing:.03em; border-radius:4px; padding:.1rem .35rem; margin-right:.4rem; vertical-align:middle; }

.notif-card__body { display:flex; flex-direction:column; gap:.55rem; padding-left:.1rem; }
.notif-card__row { display:flex; align-items:flex-start; gap:.5rem; font-size:.88rem; color:#1F2937; }
.notif-card__row svg { flex-shrink:0; margin-top:.15rem; color:#9CA3AF; }
.notif-card__muted { color:#6B7280; }
.notif-card__services { align-items:flex-start; }
.notif-card__svc-list { display:flex; flex-direction:column; gap:.25rem; }
.notif-card__svc { display:flex; flex-wrap:wrap; gap:.35rem; }
.notif-card__total { justify-content:space-between; border-top:1px dashed var(--border, #E5E7EB); padding-top:.5rem; margin-top:.15rem; }
</style>
