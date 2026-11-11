<template>
  <div class="adm-root">

    <!-- SIDEBAR -->
    <aside class="adm-sidebar" :class="{ 'is-collapsed': collapsed, 'is-open': mobileOpen }">
      <div class="adm-sidebar__logo">
        <img src="/images/josey.jpg" :alt="salonName" class="adm-logo-img" />
        <span class="adm-logo-text">{{ salonName }}</span>
      </div>

      <nav class="adm-nav">
        <button class="adm-nav__item" :class="{ active: page==='dashboard' }" @click="go('dashboard')">
          <LayoutDashboard :size="18" class="adm-nav__icon" aria-hidden="true" />
          <span class="adm-nav__label">Dashboard</span>
        </button>

        <button class="adm-nav__item" :class="{ active: page==='capacity' }" @click="go('capacity')">
          <Gauge :size="18" class="adm-nav__icon" aria-hidden="true" />
          <span class="adm-nav__label">Capacity</span>
        </button>

        <button class="adm-nav__item" :class="{ active: page==='bookings' }" @click="go('bookings')">
          <CalendarDays :size="18" class="adm-nav__icon" aria-hidden="true" />
          <span class="adm-nav__label">Bookings</span>
        </button>

        <button class="adm-nav__item" :class="{ active: page==='customers' }" @click="go('customers')">
          <Users :size="18" class="adm-nav__icon" aria-hidden="true" />
          <span class="adm-nav__label">Customers</span>
        </button>

        <div class="adm-nav__group" :class="{ 'grp-open': group==='catalog' }">
          <button class="adm-nav__item" @click="toggleGroup('catalog')">
            <FolderTree :size="18" class="adm-nav__icon" aria-hidden="true" />
            <span class="adm-nav__label">Catalog</span>
            <ChevronDown :size="14" class="adm-nav__chevron" aria-hidden="true" />
          </button>
          <div class="adm-nav__sub">
            <button class="adm-nav__sub-item" :class="{ active: page==='categories' }" @click="go('categories')">Categories</button>
            <button class="adm-nav__sub-item" :class="{ active: page==='services' }" @click="go('services')">Services</button>
            <button class="adm-nav__sub-item" :class="{ active: page==='variations' }" @click="go('variations')">Variations</button>
            <button class="adm-nav__sub-item" :class="{ active: page==='addons' }" @click="go('addons')">Add-ons</button>
          </div>
        </div>

        <button class="adm-nav__item" :class="{ active: page==='employees' }" @click="go('employees')">
          <UserCog :size="18" class="adm-nav__icon" aria-hidden="true" />
          <span class="adm-nav__label">Employees</span>
        </button>

        <button class="adm-nav__item" :class="{ active: page==='gallery' }" @click="go('gallery')">
          <Image :size="18" class="adm-nav__icon" aria-hidden="true" />
          <span class="adm-nav__label">Gallery</span>
        </button>

        <button class="adm-nav__item" :class="{ active: page==='notifications' }" @click="go('notifications')">
          <Bell :size="18" class="adm-nav__icon" aria-hidden="true" />
          <span class="adm-nav__label">Notifications</span>
          <span v-if="unreadCount>0" class="adm-nav__pill">{{ unreadBadge }}</span>
        </button>

        <div class="adm-nav__group" :class="{ 'grp-open': group==='reports' }">
          <button class="adm-nav__item" @click="toggleGroup('reports')">
            <BarChart3 :size="18" class="adm-nav__icon" aria-hidden="true" />
            <span class="adm-nav__label">Reports</span>
            <ChevronDown :size="14" class="adm-nav__chevron" aria-hidden="true" />
          </button>
          <div class="adm-nav__sub">
            <button class="adm-nav__sub-item" :class="{ active: page==='daily' }" @click="go('daily')">Daily</button>
            <button class="adm-nav__sub-item" :class="{ active: page==='monthly' }" @click="go('monthly')">Monthly</button>
          </div>
        </div>

        <div class="adm-nav__group" :class="{ 'grp-open': group==='settings' }">
          <button class="adm-nav__item" @click="toggleGroup('settings')">
            <Settings :size="18" class="adm-nav__icon" aria-hidden="true" />
            <span class="adm-nav__label">Settings</span>
            <ChevronDown :size="14" class="adm-nav__chevron" aria-hidden="true" />
          </button>
          <div class="adm-nav__sub">
            <button class="adm-nav__sub-item" :class="{ active: page==='settings-general' }" @click="go('settings-general')">General</button>
            <button class="adm-nav__sub-item" :class="{ active: page==='profile' }" @click="go('profile')">Profile</button>
          </div>
        </div>
      </nav>

      <button class="adm-sidebar__toggle" @click="collapsed=!collapsed" aria-label="Collapse">
        <component :is="collapsed ? ChevronRight : ChevronLeft" :size="18" />
      </button>
    </aside>

    <div v-if="mobileOpen" class="adm-overlay" @click="mobileOpen=false"></div>

    <!-- MAIN -->
    <div class="adm-main">
      <header class="adm-header">
        <div class="adm-header__left">
          <button class="adm-burger" @click="mobileOpen=!mobileOpen" aria-label="Menu">
            <Menu :size="20" />
          </button>
          <a href="/" class="adm-header__brand">{{ salonName }}</a>
        </div>
        <div class="adm-header__right">
          <!-- Bell → notifications -->
          <div class="adm-hdrop" @click.stop="bellOpen=!bellOpen; userOpen=false">
            <button class="adm-icon-btn" aria-label="Notifications">
              <Bell :size="18" />
              <span v-if="unreadCount>0" class="adm-red-dot">{{ unreadBadge }}</span>
            </button>
            <div v-if="bellOpen" class="adm-drop adm-drop--wide">
              <div class="adm-drop__head">
                <span>Notifications</span>
                <button class="adm-txt-btn" @click.stop="go('notifications'); bellOpen=false">View all</button>
              </div>
              <p v-if="!notifPreview.length" class="adm-notif__empty">No notifications yet.</p>
              <div v-for="n in notifPreview" :key="n.id" class="adm-notif" :class="{ unread: n.unread }" @click.stop="openNotification(n)">
                <span class="adm-notif__dot"></span>
                <div>
                  <p class="adm-notif__msg"><span v-if="n.unread" class="adm-notif__new">New</span>{{ n.text }}</p>
                  <p class="adm-notif__time">{{ n.time }}</p>
                </div>
              </div>
            </div>
          </div>

          <!-- User -->
          <div class="adm-hdrop" @click.stop="userOpen=!userOpen; bellOpen=false">
            <button class="adm-user-btn" aria-label="User menu">
              <span class="adm-user-img adm-user-initials" aria-hidden="true">{{ userInitials }}</span>
              <div class="adm-user-info">
                <span class="adm-user-name">{{ firstName }}</span>
                <span class="adm-user-role">{{ userRole }}</span>
              </div>
              <ChevronDown :size="14" />
            </button>
            <div v-if="userOpen" class="adm-drop">
              <div class="adm-drop__head adm-drop__head--col">
                <p class="adm-drop__name">{{ authStore.user?.name ?? 'Admin' }}</p>
                <p class="adm-drop__email">{{ authStore.user?.email ?? '' }}</p>
              </div>
              <button class="adm-drop__item" @click="go('profile'); userOpen=false">
                <User :size="16" /> Profile Settings
              </button>
              <button class="adm-drop__item" @click="go('notifications'); userOpen=false">
                <Bell :size="16" /> Notifications
              </button>
              <button class="adm-drop__item adm-drop__item--red" @click="signOut">
                <LogOut :size="16" /> Sign Out
              </button>
            </div>
          </div>
        </div>
      </header>

      <!-- PAGE CONTENT -->
      <main class="adm-content" @click="bellOpen=false; userOpen=false">
        <div class="adm-page-hd">
          <h1 class="adm-page-hd__title">{{ pageTitle }}</h1>
          <p class="adm-page-hd__date">{{ today }}</p>
        </div>

        <Transition name="page-fade" mode="out-in">
          <component :is="currentComponent" :key="page" @go="go" @notifications-changed="fetchNotifications" />
        </Transition>
      </main>
    </div>

    <ToastHost />
    <ConfirmDialog />
  </div>
</template>

<script setup>
import { ref, computed, markRaw, onMounted, onUnmounted } from 'vue'
import { useRouter } from 'vue-router'
import {
  LayoutDashboard, Gauge, CalendarDays, Users, FolderTree, UserCog,
  Image, BarChart3, Settings, Bell, User, LogOut, Menu,
  ChevronDown, ChevronLeft, ChevronRight
} from 'lucide-vue-next'
import { useAuthStore } from '@/stores/auth'
import { useSettings } from '@/composables/useSettings'
import { notificationsApi } from '@/services/admin'

import AdminDashboard from '@/components/admin/AdminDashboard.vue'
import AdminCapacity from '@/components/admin/AdminCapacity.vue'
import AdminBookings from '@/components/admin/AdminBookings.vue'
import AdminCustomers from '@/components/admin/AdminCustomers.vue'
import AdminCategories from '@/components/admin/AdminCategories.vue'
import AdminServices from '@/components/admin/AdminServices.vue'
import AdminVariations from '@/components/admin/AdminVariations.vue'
import AdminAddons from '@/components/admin/AdminAddons.vue'
import AdminEmployees from '@/components/admin/AdminEmployees.vue'
import AdminGallery from '@/components/admin/AdminGallery.vue'
import AdminReportDaily from '@/components/admin/AdminReportDaily.vue'
import AdminReportMonthly from '@/components/admin/AdminReportMonthly.vue'
import AdminSettings from '@/components/admin/AdminSettings.vue'
import AdminNotifications from '@/components/admin/AdminNotifications.vue'
import AdminProfile from '@/components/admin/AdminProfile.vue'
import ToastHost from '@/components/admin/ToastHost.vue'
import ConfirmDialog from '@/components/admin/ConfirmDialog.vue'

const router = useRouter()
const authStore = useAuthStore()
const { salonName } = useSettings()

const collapsed = ref(false)
const mobileOpen = ref(false)
const page = ref('dashboard')
const group = ref('')
const bellOpen = ref(false)
const userOpen = ref(false)

const today = new Date().toLocaleDateString('en-US', { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' })

const pageLabels = {
  dashboard: 'Dashboard', capacity: 'Capacity Dashboard', bookings: 'Bookings', customers: 'Customers',
  categories: 'Categories', services: 'Services', variations: 'Variations', addons: 'Add-ons',
  employees: 'Employees', gallery: 'Gallery', daily: 'Daily Report', monthly: 'Monthly Report',
  'settings-general': 'Settings', notifications: 'Notifications', profile: 'Profile'
}
const pageTitle = computed(() => pageLabels[page.value] ?? 'Dashboard')

// Which group should auto-open for the active page.
const groupOf = {
  categories: 'catalog', services: 'catalog', variations: 'catalog', addons: 'catalog',
  daily: 'reports', monthly: 'reports',
  'settings-general': 'settings', profile: 'settings'
}

function go(p) {
  page.value = p
  if (groupOf[p]) group.value = groupOf[p]
  mobileOpen.value = false
  bellOpen.value = false
  userOpen.value = false
}
function toggleGroup(g) { group.value = group.value === g ? '' : g }

async function signOut() {
  await authStore.logout()
  router.push('/admin/login')
}

// ── User display ──────────────────────────────────────────
const firstName = computed(() => authStore.user?.name?.split(' ')[0] ?? 'Admin')
const userRole = computed(() => (authStore.user?.roles?.[0] ? capitalize(authStore.user.roles[0]) : 'Staff'))
const userInitials = computed(() => {
  const n = authStore.user?.name ?? 'A'
  return n.split(' ').map((w) => w[0]).slice(0, 2).join('').toUpperCase()
})
function capitalize(s) { return s.charAt(0).toUpperCase() + s.slice(1) }

// ── Notifications bell ────────────────────────────────────
const notifPreview = ref([])
const unreadCount = ref(0)
// Red badge caps at "9+".
const unreadBadge = computed(() => (unreadCount.value > 9 ? '9+' : String(unreadCount.value)))

async function fetchNotifications() {
  try {
    const [logsRes, countRes] = await Promise.all([
      notificationsApi.logs({ type: 'booking_confirmation', per_page: 5 }),
      notificationsApi.unreadCount()
    ])
    const logs = logsRes.data.data || []
    notifPreview.value = logs.slice(0, 5).map((n) => ({
      id: n.id,
      text: bookingSummary(n),
      time: formatRelative(n.created_at),
      unread: !n.read_at
    }))
    unreadCount.value = countRes.data.count ?? 0
  } catch {
    notifPreview.value = []
    unreadCount.value = 0
  }
}

async function openNotification(n) {
  if (n.unread) {
    n.unread = false
    unreadCount.value = Math.max(0, unreadCount.value - 1)
    try { await notificationsApi.markRead(n.id) } catch { /* badge resyncs on next poll */ }
  }
  go('notifications')
  bellOpen.value = false
}
function bookingSummary(n) {
  const b = n.booking
  if (!b) return `${prettyType(n.type)} → ${n.recipient}`
  const services = (b.items || []).map((i) => i.name).join(', ') || 'appointment'
  return `New booking: ${b.customer?.name || 'Guest'} — ${services}`
}
function prettyType(t) { return (t || '').replace(/_/g, ' ').replace(/\b\w/g, (c) => c.toUpperCase()) }
function formatRelative(iso) {
  if (!iso) return ''
  const d = new Date(iso)
  return d.toLocaleDateString('en-US', { month: 'short', day: 'numeric', hour: 'numeric', minute: '2-digit' })
}

// Poll so new bookings surface on the badge without a refresh.
let notifTimer = null
onMounted(() => {
  fetchNotifications()
  notifTimer = setInterval(fetchNotifications, 30000)
})
onUnmounted(() => { if (notifTimer) clearInterval(notifTimer) })

// ── Dynamic component map ─────────────────────────────────
const componentMap = {
  dashboard: markRaw(AdminDashboard),
  capacity: markRaw(AdminCapacity),
  bookings: markRaw(AdminBookings),
  customers: markRaw(AdminCustomers),
  categories: markRaw(AdminCategories),
  services: markRaw(AdminServices),
  variations: markRaw(AdminVariations),
  addons: markRaw(AdminAddons),
  employees: markRaw(AdminEmployees),
  gallery: markRaw(AdminGallery),
  daily: markRaw(AdminReportDaily),
  monthly: markRaw(AdminReportMonthly),
  'settings-general': markRaw(AdminSettings),
  notifications: markRaw(AdminNotifications),
  profile: markRaw(AdminProfile)
}
const currentComponent = computed(() => componentMap[page.value] ?? componentMap.dashboard)
</script>

<style scoped>
.adm-root { display:flex; height:100vh; background:#F8FAFC; font-family:'Outfit',sans-serif; color:#1f2937; overflow:hidden; width:100%; }

/* SIDEBAR (white) */
.adm-sidebar { width:256px; flex-shrink:0; background:#fff; border-right:1px solid #E5E7EB; display:flex; flex-direction:column; position:fixed; top:0; left:0; height:100vh; overflow-y:auto; overflow-x:hidden; transition:width .25s ease; z-index:200; scrollbar-width:none; }
.adm-sidebar::-webkit-scrollbar { display:none; }
.adm-sidebar.is-collapsed { width:68px; }
@media(max-width:768px){ .adm-sidebar{ left:-256px;transition:left .25s ease; } .adm-sidebar.is-open{left:0;} }
.adm-sidebar__logo { display:flex;align-items:center;gap:.7rem;padding:1.4rem 1.2rem 1.2rem;border-bottom:1px solid #E5E7EB;white-space:nowrap;overflow:hidden; }
.adm-logo-img { width:32px;height:32px;border-radius:50%;object-fit:cover;flex-shrink:0;border:1.5px solid #E5E7EB; }
.adm-logo-text { font-family:'Outfit',sans-serif;font-size:1.1rem;font-weight:700;color:#1f2937;transition:opacity .2s; }
.adm-sidebar.is-collapsed .adm-logo-text { display:none; }
.adm-sidebar.is-collapsed .adm-sidebar__logo { justify-content:center; }
.adm-nav { flex:1;padding:.7rem .6rem;display:flex;flex-direction:column;gap:2px;overflow:hidden; }
.adm-nav__item { display:flex;align-items:center;gap:.7rem;padding:.66rem .8rem;border-radius:10px;font-size:.875rem;font-weight:500;color:#64748B;background:none;border:none;cursor:pointer;text-align:left;width:100%;transition:all .15s;white-space:nowrap;overflow:hidden; }
.adm-nav__item:hover { background:#F1F5F9;color:#1f2937; }
.adm-nav__item.active { background:#EFF6FF;color:#2563EB;font-weight:700; }
.adm-nav__icon { flex-shrink:0;width:20px;height:20px;display:flex;align-items:center;justify-content:center; }
.adm-nav__label { flex:1;transition:opacity .2s; }
.adm-nav__chevron { margin-left:auto;opacity:.65;flex-shrink:0;transition:transform .2s; }
.adm-nav__pill { background:#ef4444;color:#fff;font-size:.6rem;font-weight:800;padding:.1rem .42rem;border-radius:999px;flex-shrink:0; }
.adm-sidebar.is-collapsed .adm-nav__label,.adm-sidebar.is-collapsed .adm-nav__chevron,.adm-sidebar.is-collapsed .adm-nav__pill { display:none; }
.adm-nav__group { position:relative; }
.adm-nav__sub { max-height:0;overflow:hidden;transition:max-height .24s ease;padding-left:2.2rem; }
.adm-nav__group.grp-open .adm-nav__sub { max-height:220px; }
.adm-nav__group.grp-open .adm-nav__chevron { transform:rotate(180deg); }
.adm-sidebar.is-collapsed .adm-nav__sub { display:none; }
.adm-sidebar.is-collapsed .adm-nav__item { justify-content:center; }
.adm-nav__sub-item { display:block;width:100%;padding:.42rem .65rem;border-radius:8px;font-size:.82rem;color:#64748B;background:none;border:none;cursor:pointer;text-align:left;transition:all .14s;white-space:nowrap; }
.adm-nav__sub-item:hover { background:#F1F5F9;color:#1f2937; }
.adm-nav__sub-item.active { color:#2563EB;font-weight:600; }
.adm-sidebar__toggle { display:flex;align-items:center;justify-content:center;padding:.85rem;background:none;border:none;color:#94A3B8;cursor:pointer;border-top:1px solid #E5E7EB;transition:color .2s; }
.adm-sidebar__toggle:hover { color:#2563EB; }
@media(max-width:768px){ .adm-sidebar__toggle{display:none;} }
.adm-overlay { position:fixed;inset:0;background:rgba(0,0,0,.38);z-index:150; }

/* MAIN */
.adm-main { flex:1; min-width:0; display:flex; flex-direction:column; overflow-y:auto; overflow-x:hidden; margin-left:256px; transition:margin-left .25s ease; height:100vh; }
.adm-root:has(.adm-sidebar.is-collapsed) .adm-main { margin-left:68px; }
@media(max-width:768px){ .adm-main{ margin-left:0; } }
.adm-header { position:sticky;top:0;z-index:100;background:#fff;border-bottom:1px solid #DBEAFE;height:60px;padding:0 1.5rem;display:flex;align-items:center;justify-content:space-between;gap:1rem;box-shadow:0 1px 8px rgba(91, 167, 255,.07); }
.adm-header__left { display:flex;align-items:center;gap:.85rem; }
.adm-header__right { display:flex;align-items:center;gap:.6rem; }
.adm-header__brand { font-family:'Outfit', sans-serif;font-size:1.1rem;font-weight:700;color:#64748B;text-decoration:none; }
.adm-burger { display:none;width:36px;height:36px;border-radius:8px;background:#EFF6FF;border:none;color:#64748B;align-items:center;justify-content:center;cursor:pointer; }
@media(max-width:768px){ .adm-burger{display:flex;} }
.adm-icon-btn { position:relative;width:36px;height:36px;border-radius:8px;border:1px solid #DBEAFE;background:#fff;color:#64748B;display:flex;align-items:center;justify-content:center;cursor:pointer;transition:all .14s; }
.adm-icon-btn:hover { background:#EFF6FF;border-color:#2563EB; }
.adm-red-dot { position:absolute;top:-3px;right:-3px;min-width:16px;height:16px;padding:0 4px;border-radius:8px;background:#ef4444;color:#fff;font-size:.58rem;font-weight:800;display:flex;align-items:center;justify-content:center;border:2px solid #fff;box-sizing:border-box; }
.adm-hdrop { position:relative; }
.adm-drop { position:absolute;top:calc(100% + 8px);right:0;background:#fff;border:1px solid #DBEAFE;border-radius:12px;box-shadow:0 8px 28px rgba(91, 167, 255,.12);overflow:hidden;z-index:300;min-width:180px; }
.adm-drop--wide { min-width:290px; }
.adm-drop__head { padding:.8rem 1rem;border-bottom:1px solid #DBEAFE;font-size:.87rem;font-weight:700;color:#1f2937;display:flex;align-items:center;justify-content:space-between; }
.adm-drop__head--col { flex-direction:column;align-items:flex-start;gap:1px; }
.adm-drop__name  { font-size:.87rem;font-weight:700;color:#1f2937; }
.adm-drop__email { font-size:.74rem;color:#9ca3af; }
.adm-txt-btn { font-size:.73rem;color:#2563EB;font-weight:600;background:none;border:none;cursor:pointer; }
.adm-notif { display:flex;align-items:flex-start;gap:.7rem;padding:.68rem 1rem;border-bottom:1px solid #EFF6FF;cursor:pointer; }
.adm-notif.unread { background:#fef2f2; }
.adm-notif:hover { background:#EFF6FF; }
.adm-notif__dot { width:8px;height:8px;border-radius:50%;background:#2563EB;flex-shrink:0;margin-top:4px; }
.adm-notif:not(.unread) .adm-notif__dot { background:#d1d5db; }
.adm-notif__new { display:inline-block;background:#ef4444;color:#fff;font-size:.62rem;font-weight:800;text-transform:uppercase;letter-spacing:.03em;border-radius:4px;padding:.1rem .35rem;margin-right:.4rem;vertical-align:middle; }
.adm-notif__msg  { font-size:.81rem;color:#374151;line-height:1.4; }
.adm-notif__time { font-size:.72rem;color:#9ca3af;margin-top:2px; }
.adm-notif__empty { font-size:.82rem;color:#9ca3af;padding:1rem;text-align:center; }
.adm-user-btn { display:flex;align-items:center;gap:.55rem;padding:.32rem .65rem .32rem .32rem;border-radius:10px;border:1px solid #DBEAFE;background:#fff;cursor:pointer;color:#64748B;transition:border-color .14s; }
.adm-user-btn:hover { border-color:#2563EB; }
.adm-user-img  { width:30px;height:30px;border-radius:50%;object-fit:cover;object-position:center top;flex-shrink:0; }
.adm-user-initials { display:flex;align-items:center;justify-content:center;background:linear-gradient(135deg,#2563EB,#5BA7FF);color:#fff;font-size:.72rem;font-weight:700; }
.adm-user-info { display:flex;flex-direction:column;text-align:left; }
.adm-user-name { font-size:.79rem;font-weight:600;color:#1f2937;white-space:nowrap; }
.adm-user-role { font-size:.67rem;color:#9ca3af; }
@media(max-width:480px){ .adm-user-info{display:none;} }
.adm-drop__item { display:flex;align-items:center;gap:.55rem;width:100%;padding:.62rem 1rem;font-family:'Outfit',sans-serif;font-size:.84rem;color:#374151;background:none;border:none;cursor:pointer;transition:background .14s; }
.adm-drop__item:hover { background:#EFF6FF; }
.adm-drop__item--red { color:#ef4444; }
.adm-drop__item--red:hover { background:#fef2f2; }

/* CONTENT */
.adm-content { padding:1.5rem;display:flex;flex-direction:column;gap:1.35rem; }
@media(max-width:640px){ .adm-content{padding:1rem;} }
.adm-page-hd__title { font-family:'Outfit', sans-serif;font-size:1.5rem;font-weight:700;color:#1f2937; }
.adm-page-hd__date  { font-size:.79rem;color:#9ca3af;margin-top:2px; }

/* Page transition */
.page-fade-enter-active,.page-fade-leave-active { transition:opacity .18s ease,transform .18s ease; }
.page-fade-enter-from { opacity:0;transform:translateX(10px); }
.page-fade-leave-to   { opacity:0;transform:translateX(-10px); }
</style>
