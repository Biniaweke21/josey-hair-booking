<template>
  <div class="ap-page">
    <div class="ap-card cust-toolbar">
      <div>
        <h3 class="ap-card__title">Customers</h3>
        <p class="ap-card__sub">{{ meta.total }} total</p>
      </div>
      <div class="cust-search">
        <Search :size="16" class="cust-search__icon" aria-hidden="true" />
        <input v-model="search" type="search" placeholder="Search name, email, phone…" class="ap-input cust-search__input" @input="onSearch" />
      </div>
    </div>

    <p v-if="error" class="state-note state-note--error">{{ error }} <button class="state-retry" @click="reload">Retry</button></p>

    <div class="ap-card ap-table-card">
      <div class="ap-table-scroll">
        <table class="ap-table">
          <thead><tr><th>Customer</th><th>Email</th><th>Phone</th><th>Bookings</th><th></th></tr></thead>
          <tbody>
            <tr v-if="loading"><td colspan="5"><div class="skeleton" style="height:120px;"></div></td></tr>
            <tr v-else-if="!customers.length"><td colspan="5" class="ap-empty">No customers found.</td></tr>
            <tr v-for="c in customers" :key="c.id">
              <td><div class="ap-td-client"><div class="ap-td-avatar">{{ (c.name||'?')[0] }}</div>{{ c.name }}</div></td>
              <td>{{ c.email || '—' }}</td>
              <td>{{ c.phone || '—' }}</td>
              <td><strong>{{ c.bookings_count ?? 0 }}</strong></td>
              <td><button class="ap-btn-outline-sm" @click="openDetail(c)">View</button></td>
            </tr>
          </tbody>
        </table>
      </div>
      <div v-if="meta.last_page > 1" class="bk-pager">
        <button class="ap-btn-outline-sm" :disabled="meta.current_page<=1" @click="fetchCustomers(meta.current_page-1)">Prev</button>
        <span class="bk-pager__info">Page {{ meta.current_page }} of {{ meta.last_page }}</span>
        <button class="ap-btn-outline-sm" :disabled="meta.current_page>=meta.last_page" @click="fetchCustomers(meta.current_page+1)">Next</button>
      </div>
    </div>

    <!-- Detail modal -->
    <div v-if="detail" class="ap-modal" @click.self="detail=null">
      <div class="ap-modal-content">
        <div class="ap-modal-header">
          <h3>{{ detail.name }}</h3>
          <button class="ap-modal-close" @click="detail=null"><X :size="18" /></button>
        </div>
        <div style="padding:1.5rem;">
          <div class="bk-summary">
            <div><span class="bk-k">Email</span><span class="bk-v">{{ detail.email || '—' }}</span></div>
            <div><span class="bk-k">Phone</span><span class="bk-v">{{ detail.phone || '—' }}</span></div>
            <div><span class="bk-k">Total Bookings</span><span class="bk-v">{{ detail.bookings_count ?? detail.bookings?.length ?? 0 }}</span></div>
            <div v-if="detail.notes" class="bk-summary__full"><span class="bk-k">Notes</span><span class="bk-v">{{ detail.notes }}</span></div>
          </div>
          <h4 class="bk-sec">Booking History</h4>
          <div v-if="detailLoading" class="skeleton" style="height:100px;"></div>
          <div v-else-if="detail.bookings?.length" class="ap-upcoming-list">
            <div v-for="b in detail.bookings" :key="b.id" class="ap-upcoming-row">
              <span class="ap-upcoming-time">{{ shortDate(b.booking_date) }}</span>
              <div class="ap-upcoming-info">
                <p class="ap-upcoming-client">{{ formatCurrency(b.total_price) }}</p>
                <p class="ap-upcoming-svc">{{ formatTime12(b.start_time) }}</p>
              </div>
              <span class="ap-badge" :class="'ap-badge--'+b.status">{{ statusLabel(b.status) }}</span>
            </div>
          </div>
          <p v-else class="ap-empty">No bookings yet.</p>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, reactive, onMounted } from 'vue'
import { Search, X } from 'lucide-vue-next'
import { customersApi } from '@/services/admin'
import { formatCurrency, formatTime12 } from '@/utils/format'
import { apiErrorMessage } from '@/services/api'

const customers = ref([])
const meta = reactive({ current_page: 1, last_page: 1, total: 0 })
const loading = ref(true)
const error = ref(null)
const search = ref('')
let searchTimer = null

async function fetchCustomers(page = 1) {
  loading.value = true
  error.value = null
  try {
    const params = { page }
    if (search.value) params.search = search.value
    const res = await customersApi.list(params)
    customers.value = res.data.data
    Object.assign(meta, res.data.meta)
  } catch (e) {
    error.value = apiErrorMessage(e)
  } finally {
    loading.value = false
  }
}
function reload() { fetchCustomers(1) }
function onSearch() { clearTimeout(searchTimer); searchTimer = setTimeout(reload, 300) }
onMounted(() => fetchCustomers())

const detail = ref(null)
const detailLoading = ref(false)
async function openDetail(c) {
  detail.value = c
  detailLoading.value = true
  try { detail.value = (await customersApi.get(c.id)).data.data } catch { /* keep summary */ } finally { detailLoading.value = false }
}

function statusLabel(s) { return (s || '').replace(/_/g, ' ').replace(/\b\w/g, (x) => x.toUpperCase()) }
function shortDate(d) { return d ? new Date(d + 'T00:00:00').toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' }) : '' }
</script>

<style scoped>
.cust-toolbar { display:flex; align-items:center; justify-content:space-between; gap:1rem; flex-wrap:wrap; }
.cust-search { position:relative; }
.cust-search__icon { position:absolute; left:.7rem; top:50%; transform:translateY(-50%); color:#9ca3af; }
.cust-search__input { padding-left:2.3rem; min-width:260px; }
</style>
