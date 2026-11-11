<template>
  <div class="ap-page">
    <div class="ap-card cust-toolbar">
      <div><h3 class="ap-card__title">Add-ons</h3><p class="ap-card__sub">{{ addons.length }} total</p></div>
      <button class="ap-btn-primary" @click="openCreate"><Plus :size="15" /> New Add-on</button>
    </div>

    <p v-if="error" class="state-note state-note--error">{{ error }} <button class="state-retry" @click="load">Retry</button></p>

    <div class="ap-card ap-table-card">
      <div class="ap-table-scroll">
        <table class="ap-table">
          <thead><tr><th>Name</th><th>Price</th><th>Extra time</th><th>Status</th><th>Actions</th></tr></thead>
          <tbody>
            <tr v-if="loading"><td colspan="5"><div class="skeleton" style="height:100px;"></div></td></tr>
            <tr v-else-if="!addons.length"><td colspan="5" class="ap-empty">No add-ons yet.</td></tr>
            <tr v-for="a in addons" :key="a.id">
              <td><strong>{{ a.name }}</strong><span v-if="a.description" class="ap-td-time"> · {{ a.description }}</span></td>
              <td>+{{ formatCurrency(a.price_modifier) }}</td>
              <td>+{{ a.duration_modifier_minutes }} min</td>
              <td><span class="ap-badge" :class="a.is_active ? 'ap-badge--available' : 'ap-badge--full'">{{ a.is_active ? 'Active' : 'Hidden' }}</span></td>
              <td><div class="ap-td-acts"><button class="ap-btn-outline-sm" @click="openEdit(a)">Edit</button><button class="ap-btn-no-sm" @click="remove(a)">Delete</button></div></td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <div v-if="showForm" class="ap-modal" @click.self="showForm=false">
      <div class="ap-modal-content">
        <div class="ap-modal-header"><h3>{{ form.id ? 'Edit' : 'New' }} Add-on</h3><button class="ap-modal-close" @click="showForm=false"><X :size="18" /></button></div>
        <form class="ap-form" @submit.prevent="save">
          <div class="ap-form-group"><label>Name</label><input v-model="form.name" required /></div>
          <div class="ap-form-group"><label>Description</label><input v-model="form.description" /></div>
          <div class="ap-form-grid">
            <div class="ap-form-group"><label>Price (USD)</label><input v-model.number="form.price_modifier" type="number" min="0" step="0.01" required /></div>
            <div class="ap-form-group"><label>Extra time (min)</label><input v-model.number="form.duration_modifier_minutes" type="number" min="0" required /></div>
          </div>
          <label class="cust-check"><input type="checkbox" v-model="form.is_active" /> Active</label>
          <p v-if="formError" class="ap-error">{{ formError }}</p>
          <div class="ap-modal-actions">
            <button type="button" class="ap-btn-outline" @click="showForm=false">Cancel</button>
            <button type="submit" class="ap-btn-primary" :disabled="saving">{{ saving ? 'Saving…' : 'Save' }}</button>
          </div>
        </form>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { Plus, X } from 'lucide-vue-next'
import { addOnsApi } from '@/services/catalog'
import { formatCurrency } from '@/utils/format'
import { apiErrorMessage } from '@/services/api'
import { useToast } from '@/composables/useToast'
import { useConfirm } from '@/composables/useConfirm'

const toast = useToast()
const confirm = useConfirm()

const addons = ref([])
const loading = ref(true)
const error = ref(null)

async function load() {
  loading.value = true; error.value = null
  try { addons.value = (await addOnsApi.list()).data.data } catch (e) { error.value = apiErrorMessage(e) } finally { loading.value = false }
}
onMounted(load)

const showForm = ref(false)
const saving = ref(false)
const formError = ref(null)
const form = ref({})
function openCreate() { form.value = { name: '', description: '', price_modifier: 0, duration_modifier_minutes: 0, is_active: true }; formError.value = null; showForm.value = true }
function openEdit(a) { form.value = { ...a }; formError.value = null; showForm.value = true }

async function save() {
  saving.value = true; formError.value = null
  try {
    const payload = { name: form.value.name, description: form.value.description, price_modifier: form.value.price_modifier || 0, duration_modifier_minutes: form.value.duration_modifier_minutes || 0, is_active: form.value.is_active }
    const editing = !!form.value.id
    if (editing) await addOnsApi.update(form.value.id, payload)
    else await addOnsApi.create(payload)
    showForm.value = false; await load()
    toast.success(editing ? 'Add-on updated.' : 'Add-on created.')
  } catch (e) { formError.value = apiErrorMessage(e) } finally { saving.value = false }
}
async function remove(a) {
  if (!(await confirm({ title: 'Delete add-on', message: `Delete "${a.name}"? This cannot be undone.`, confirmText: 'Delete', danger: true }))) return
  try { await addOnsApi.remove(a.id); await load(); toast.success('Add-on deleted.') } catch (e) { toast.error(apiErrorMessage(e)) }
}
</script>

<style scoped>
.cust-toolbar { display:flex; align-items:center; justify-content:space-between; gap:1rem; flex-wrap:wrap; }
.cust-check { display:flex; align-items:center; gap:.5rem; font-size:.85rem; color:#1f2937; margin-bottom:.5rem; }
</style>
