<template>
  <div class="ap-page">
    <div class="ap-card cust-toolbar">
      <div><h3 class="ap-card__title">Employees</h3><p class="ap-card__sub">{{ employees.length }} total</p></div>
      <button class="ap-btn-primary" @click="openCreate"><Plus :size="15" /> New Employee</button>
    </div>

    <p v-if="error" class="state-note state-note--error">{{ error }} <button class="state-retry" @click="load">Retry</button></p>

    <div v-if="loading" class="ap-card"><div class="skeleton" style="height:140px;"></div></div>
    <p v-else-if="!employees.length" class="ap-card ap-empty">No employees yet.</p>

    <div v-else class="ap-staff-grid">
      <div v-for="e in employees" :key="e.id" class="ap-staff-card">
        <div class="ap-staff-card__avatar">{{ e.name[0] }}</div>
        <div class="ap-staff-card__info">
          <p class="ap-staff-card__name">{{ e.name }}</p>
          <p class="ap-staff-card__role">{{ e.email || 'No email' }}</p>
          <p class="ap-staff-card__phone">{{ e.phone || '—' }}</p>
        </div>
        <span class="ap-badge" :class="e.is_active ? 'ap-badge--available' : 'ap-badge--full'">{{ e.is_active ? 'Active' : 'Inactive' }}</span>
        <div class="ap-td-acts" style="justify-content:center;">
          <button class="ap-btn-outline-sm" @click="openEdit(e)">Edit</button>
          <button class="ap-btn-outline-sm" @click="openSchedule(e)">Working Days</button>
          <button class="ap-btn-no-sm" @click="remove(e)">Delete</button>
        </div>
      </div>
    </div>

    <!-- Employee form -->
    <div v-if="showForm" class="ap-modal" @click.self="showForm=false">
      <div class="ap-modal-content">
        <div class="ap-modal-header"><h3>{{ form.id ? 'Edit' : 'New' }} Employee</h3><button class="ap-modal-close" @click="showForm=false"><X :size="18" /></button></div>
        <form class="ap-form" @submit.prevent="save">
          <div class="ap-form-group"><label>Name</label><input v-model="form.name" required /></div>
          <div class="ap-form-group"><label>Email</label><input v-model="form.email" type="email" /></div>
          <div class="ap-form-group"><label>Phone</label><input v-model="form.phone" type="tel" /></div>
          <label class="cust-check"><input type="checkbox" v-model="form.is_active" /> Active</label>
          <p v-if="formError" class="ap-error">{{ formError }}</p>
          <div class="ap-modal-actions">
            <button type="button" class="ap-btn-outline" @click="showForm=false">Cancel</button>
            <button type="submit" class="ap-btn-primary" :disabled="saving">{{ saving ? 'Saving…' : 'Save' }}</button>
          </div>
        </form>
      </div>
    </div>

    <!-- Schedule modal -->
    <div v-if="scheduleFor" class="ap-modal" @click.self="scheduleFor=null">
      <div class="ap-modal-content">
        <div class="ap-modal-header"><h3>{{ scheduleFor.name }} · Working Days</h3><button class="ap-modal-close" @click="scheduleFor=null"><X :size="18" /></button></div>
        <div style="padding:1.5rem;">
          <p class="ap-card__sub" style="margin-bottom:1rem;">Toggle the days this employee works. Capacity is the count of staff working each day.</p>
          <div v-if="scheduleLoading" class="skeleton" style="height:120px;"></div>
          <div v-else class="emp-days">
            <label v-for="(name, dow) in DAY_NAMES" :key="dow" class="emp-day" :class="{ 'is-on': workingDays[dow] }">
              <input type="checkbox" :checked="!!workingDays[dow]" :disabled="togglingDay===dow" @change="toggleDay(dow)" />
              {{ name }}
            </label>
          </div>
          <p v-if="scheduleError" class="ap-error">{{ scheduleError }}</p>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { Plus, X } from 'lucide-vue-next'
import { employeesApi } from '@/services/admin'
import { DAY_NAMES } from '@/utils/format'
import { apiErrorMessage } from '@/services/api'
import { useToast } from '@/composables/useToast'
import { useConfirm } from '@/composables/useConfirm'

const toast = useToast()
const confirm = useConfirm()

const employees = ref([])
const loading = ref(true)
const error = ref(null)

async function load() {
  loading.value = true; error.value = null
  try { employees.value = (await employeesApi.list()).data.data } catch (e) { error.value = apiErrorMessage(e) } finally { loading.value = false }
}
onMounted(load)

const showForm = ref(false)
const saving = ref(false)
const formError = ref(null)
const form = ref({})
function openCreate() { form.value = { name: '', email: '', phone: '', is_active: true }; formError.value = null; showForm.value = true }
function openEdit(e) { form.value = { ...e }; formError.value = null; showForm.value = true }
async function save() {
  saving.value = true; formError.value = null
  try {
    const editing = !!form.value.id
    const payload = { name: form.value.name, email: form.value.email || null, phone: form.value.phone || null, is_active: form.value.is_active }
    if (editing) await employeesApi.update(form.value.id, payload)
    else await employeesApi.create(payload)
    showForm.value = false; await load()
    toast.success(editing ? 'Employee updated.' : 'Employee added.')
  } catch (e) { formError.value = apiErrorMessage(e) } finally { saving.value = false }
}
async function remove(e) {
  if (!(await confirm({ title: 'Delete employee', message: `Delete "${e.name}"? This cannot be undone.`, confirmText: 'Delete', danger: true }))) return
  try { await employeesApi.remove(e.id); await load(); toast.success('Employee deleted.') } catch (err) { toast.error(apiErrorMessage(err)) }
}

// Schedule
const scheduleFor = ref(null)
const scheduleLoading = ref(false)
const scheduleError = ref(null)
const workingDays = ref({})   // dow -> schedule id
const togglingDay = ref(null)

async function openSchedule(e) {
  scheduleFor.value = e
  scheduleError.value = null
  scheduleLoading.value = true
  try {
    const res = await employeesApi.schedules(e.id)
    const map = {}
    for (const s of res.data.data) if (s.is_active) map[s.day_of_week] = s.id
    workingDays.value = map
  } catch (err) { scheduleError.value = apiErrorMessage(err) } finally { scheduleLoading.value = false }
}
async function toggleDay(dow) {
  togglingDay.value = dow
  scheduleError.value = null
  try {
    if (workingDays.value[dow]) {
      await employeesApi.removeSchedule(scheduleFor.value.id, workingDays.value[dow])
      const m = { ...workingDays.value }; delete m[dow]; workingDays.value = m
      toast.success(`${DAY_NAMES[dow]} removed from roster.`)
    } else {
      const res = await employeesApi.addSchedule(scheduleFor.value.id, { day_of_week: Number(dow), is_active: true })
      workingDays.value = { ...workingDays.value, [dow]: res.data.data.id }
      toast.success(`${DAY_NAMES[dow]} added to roster.`)
    }
  } catch (err) { scheduleError.value = apiErrorMessage(err) } finally { togglingDay.value = null }
}
</script>

<style scoped>
.cust-toolbar { display:flex; align-items:center; justify-content:space-between; gap:1rem; flex-wrap:wrap; }
.cust-check { display:flex; align-items:center; gap:.5rem; font-size:.85rem; color:#1f2937; margin-bottom:.5rem; }
.emp-days { display:flex; flex-direction:column; gap:.5rem; }
.emp-day { display:flex; align-items:center; gap:.6rem; padding:.6rem .9rem; border:1.5px solid var(--pink-100); border-radius:10px; font-size:.88rem; color:#1f2937; cursor:pointer; transition:all .14s; }
.emp-day.is-on { border-color:var(--primary); background:var(--pink-50); font-weight:600; }
</style>
