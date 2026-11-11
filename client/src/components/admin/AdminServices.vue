<template>
  <div class="ap-page">
    <div class="ap-card cust-toolbar">
      <div><h3 class="ap-card__title">Services</h3><p class="ap-card__sub">{{ services.length }} total</p></div>
      <button class="ap-btn-primary" @click="openCreate"><Plus :size="15" /> New Service</button>
    </div>

    <p v-if="error" class="state-note state-note--error">{{ error }} <button class="state-retry" @click="load">Retry</button></p>

    <div class="ap-card ap-table-card">
      <div class="ap-table-scroll">
        <table class="ap-table">
          <thead><tr><th>Service</th><th>Category</th><th>Price</th><th>Duration</th><th>Status</th><th>Actions</th></tr></thead>
          <tbody>
            <tr v-if="loading"><td colspan="6"><div class="skeleton" style="height:120px;"></div></td></tr>
            <tr v-else-if="!services.length"><td colspan="6" class="ap-empty">No services yet.</td></tr>
            <tr v-for="s in services" :key="s.id">
              <td><div class="ap-td-client"><img v-if="s.image_url" :src="s.image_url" class="cat-thumb" alt="" /><div v-else class="ap-td-avatar">{{ s.name[0] }}</div>{{ s.name }}</div></td>
              <td>{{ s.category?.name || '—' }}</td>
              <td><strong>{{ formatCurrency(s.base_price) }}</strong></td>
              <td>{{ formatDuration(s.base_duration_minutes) }}</td>
              <td><span class="ap-badge" :class="s.is_active ? 'ap-badge--available' : 'ap-badge--full'">{{ s.is_active ? 'Active' : 'Hidden' }}</span></td>
              <td><div class="ap-td-acts"><button class="ap-btn-outline-sm" @click="openEdit(s)">Edit</button><button class="ap-btn-no-sm" @click="remove(s)">Delete</button></div></td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <div v-if="showForm" class="ap-modal" @click.self="showForm=false">
      <div class="ap-modal-content bk-modal">
        <div class="ap-modal-header"><h3>{{ form.id ? 'Edit' : 'New' }} Service</h3><button class="ap-modal-close" @click="showForm=false"><X :size="18" /></button></div>
        <form class="ap-form" @submit.prevent="save">
          <div class="ap-form-grid">
            <div class="ap-form-group"><label>Name</label><input v-model="form.name" required /></div>
            <div class="ap-form-group"><label>Category</label><select v-model.number="form.category_id" required><option value="" disabled>Select…</option><option v-for="c in categories" :key="c.id" :value="c.id">{{ c.name }}</option></select></div>
            <div class="ap-form-group"><label>Base price (USD)</label><input v-model.number="form.base_price" type="number" min="0" step="0.01" required /></div>
            <div class="ap-form-group"><label>Duration (min)</label><input v-model.number="form.base_duration_minutes" type="number" min="1" required /></div>
          </div>
          <div class="ap-form-group"><label>Description</label><textarea v-model="form.description" rows="2"></textarea></div>
          <div class="ap-form-group"><label>Image</label><input type="file" accept="image/*" @change="onFile" /></div>
          <label class="cust-check"><input type="checkbox" v-model="form.is_active" /> Active</label>

          <!-- Per-service config (only after the service exists) -->
          <template v-if="form.id">
            <h4 class="bk-sec">Variation Types</h4>
            <p class="ap-card__sub">Attach a variation type, then set price/duration for each option.</p>
            <div class="svc-attach">
              <select v-model.number="attachTypeId" class="ap-select"><option value="">Add variation type…</option><option v-for="t in unattachedTypes" :key="t.id" :value="t.id">{{ t.name }}</option></select>
              <button type="button" class="ap-btn-outline-sm" :disabled="!attachTypeId" @click="attachType">Attach</button>
            </div>
            <div v-for="t in svcVariationTypes" :key="t.id" class="svc-vtype">
              <div class="svc-vtype__hd"><span>{{ t.name }}</span><button type="button" class="ap-btn-no-sm" @click="detachType(t)">Remove</button></div>
              <div class="svc-opt svc-opt--head">
                <span class="svc-opt__name"></span>
                <span class="svc-opt__lbl">Extra price</span>
                <span class="svc-opt__lbl">Extra minutes</span>
                <span></span>
              </div>
              <div v-for="opt in t.options" :key="opt.id" class="svc-opt">
                <span class="svc-opt__name">{{ opt.name }}</span>
                <input class="svc-opt__in" type="number" step="0.01" title="How much extra this option adds to the base price ($)" placeholder="$0.00" v-model.number="optAdj[opt.id].price" />
                <input class="svc-opt__in" type="number" title="How many extra minutes this option adds to the base duration" placeholder="0 min" v-model.number="optAdj[opt.id].duration" />
                <button type="button" class="ap-btn-outline-sm" @click="saveOption(opt)">Set</button>
              </div>
            </div>

            <h4 class="bk-sec">Add-ons</h4>
            <div class="svc-attach">
              <select v-model.number="attachAddOnId" class="ap-select"><option value="">Add add-on…</option><option v-for="a in unattachedAddOns" :key="a.id" :value="a.id">{{ a.name }}</option></select>
              <button type="button" class="ap-btn-outline-sm" :disabled="!attachAddOnId" @click="attachAddOn">Attach</button>
            </div>
            <div v-for="a in svcAddOns" :key="a.id" class="svc-opt">
              <span class="svc-opt__name">{{ a.name }} <span style="color:#9ca3af">+{{ formatCurrency(a.price_modifier) }}</span></span>
              <button type="button" class="ap-btn-no-sm" @click="detachAddOn(a)">Remove</button>
            </div>
          </template>

          <p v-if="formError" class="ap-error">{{ formError }}</p>
          <div class="ap-modal-actions">
            <button type="button" class="ap-btn-outline" @click="showForm=false">Close</button>
            <button type="submit" class="ap-btn-primary" :disabled="saving">{{ saving ? 'Saving…' : 'Save' }}</button>
          </div>
        </form>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { Plus, X } from 'lucide-vue-next'
import { servicesApi, categoriesApi, variationTypesApi, addOnsApi } from '@/services/catalog'
import { formatCurrency, formatDuration } from '@/utils/format'
import { apiErrorMessage } from '@/services/api'
import { useToast } from '@/composables/useToast'
import { useConfirm } from '@/composables/useConfirm'

const toast = useToast()
const confirm = useConfirm()

const services = ref([])
const categories = ref([])
const allTypes = ref([])
const allAddOns = ref([])
const loading = ref(true)
const error = ref(null)

async function load() {
  loading.value = true; error.value = null
  try {
    const [s, c, t, a] = await Promise.all([servicesApi.list(), categoriesApi.list(), variationTypesApi.list(), addOnsApi.list()])
    services.value = s.data.data
    categories.value = c.data.data
    allTypes.value = t.data.data
    allAddOns.value = a.data.data
  } catch (e) { error.value = apiErrorMessage(e) } finally { loading.value = false }
}
onMounted(load)

const showForm = ref(false)
const saving = ref(false)
const formError = ref(null)
const form = ref({})
let imageFile = null

// per-service config state
const svcVariationTypes = ref([])
const svcAddOns = ref([])
const optAdj = ref({})
const attachTypeId = ref('')
const attachAddOnId = ref('')

const unattachedTypes = computed(() => allTypes.value.filter((t) => !svcVariationTypes.value.some((x) => x.id === t.id)))
const unattachedAddOns = computed(() => allAddOns.value.filter((a) => !svcAddOns.value.some((x) => x.id === a.id)))

function openCreate() {
  form.value = { name: '', category_id: categories.value[0]?.id ?? '', base_price: 0, base_duration_minutes: 30, description: '', is_active: true }
  imageFile = null; formError.value = null; svcVariationTypes.value = []; svcAddOns.value = []; showForm.value = true
}
async function openEdit(s) {
  form.value = { ...s, category_id: s.category_id }
  imageFile = null; formError.value = null; showForm.value = true
  await loadConfig(s.id)
}
function onFile(e) { imageFile = e.target.files[0] || null }

async function loadConfig(serviceId) {
  try {
    const [types, opts, addons] = await Promise.all([
      servicesApi.variationTypes(serviceId), servicesApi.variationOptions(serviceId), servicesApi.addOns(serviceId)
    ])
    // adjustments map by option id
    const adj = {}
    for (const svo of opts.data.data || []) {
      if (svo.variation_option) adj[svo.variation_option.id] = { price: parseFloat(svo.price_adjustment) || 0, duration: svo.duration_adjustment_minutes || 0 }
    }
    svcVariationTypes.value = (types.data.data || []).map((t) => ({ id: t.id, name: t.name, options: (t.options || []) }))
    // ensure every option has an editable adjustment entry
    for (const t of svcVariationTypes.value) for (const o of t.options) if (!adj[o.id]) adj[o.id] = { price: 0, duration: 0 }
    optAdj.value = adj
    svcAddOns.value = addons.data.data || []
  } catch { svcVariationTypes.value = []; svcAddOns.value = []; optAdj.value = {} }
}

async function save() {
  saving.value = true; formError.value = null
  try {
    const editing = !!form.value.id
    const payload = { category_id: form.value.category_id, name: form.value.name, description: form.value.description, base_price: form.value.base_price, base_duration_minutes: form.value.base_duration_minutes, is_active: form.value.is_active }
    let saved
    if (editing) saved = (await servicesApi.update(form.value.id, payload)).data.data
    else saved = (await servicesApi.create(payload)).data.data
    if (imageFile) await servicesApi.uploadImage(saved.id, imageFile)
    if (!editing) { form.value.id = saved.id; imageFile = null; await loadConfig(saved.id) } // keep modal open to configure
    else showForm.value = false
    await load()
    toast.success(editing ? 'Service updated.' : 'Service created — now add options & add-ons.')
  } catch (e) { formError.value = apiErrorMessage(e) } finally { saving.value = false }
}

async function remove(s) {
  if (!(await confirm({ title: 'Delete service', message: `Delete "${s.name}"? This cannot be undone.`, confirmText: 'Delete', danger: true }))) return
  try { await servicesApi.remove(s.id); await load(); toast.success('Service deleted.') } catch (e) { toast.error(apiErrorMessage(e)) }
}

async function attachType() {
  try { await servicesApi.attachVariationType(form.value.id, attachTypeId.value); attachTypeId.value = ''; await loadConfig(form.value.id); toast.success('Variation type attached.') } catch (e) { formError.value = apiErrorMessage(e) }
}
async function detachType(t) {
  try { await servicesApi.detachVariationType(form.value.id, t.id); await loadConfig(form.value.id); toast.success('Variation type removed.') } catch (e) { formError.value = apiErrorMessage(e) }
}
async function saveOption(opt) {
  const adj = optAdj.value[opt.id]
  try { await servicesApi.setVariationOption(form.value.id, opt.id, { price_adjustment: adj.price || 0, duration_adjustment_minutes: adj.duration || 0, is_active: true }); toast.success(`"${opt.name}" pricing saved.`) } catch (e) { formError.value = apiErrorMessage(e) }
}
async function attachAddOn() {
  try { await servicesApi.attachAddOn(form.value.id, attachAddOnId.value); attachAddOnId.value = ''; await loadConfig(form.value.id); toast.success('Add-on attached.') } catch (e) { formError.value = apiErrorMessage(e) }
}
async function detachAddOn(a) {
  try { await servicesApi.detachAddOn(form.value.id, a.id); await loadConfig(form.value.id); toast.success('Add-on removed.') } catch (e) { formError.value = apiErrorMessage(e) }
}
</script>

<style scoped>
.cust-toolbar { display:flex; align-items:center; justify-content:space-between; gap:1rem; flex-wrap:wrap; }
.cat-thumb { width:30px; height:30px; border-radius:6px; object-fit:cover; }
.cust-check { display:flex; align-items:center; gap:.5rem; font-size:.85rem; color:#1f2937; margin-bottom:.5rem; }
.svc-attach { display:flex; gap:.5rem; align-items:center; margin:.5rem 0; }
.svc-vtype { border:1px solid var(--pink-100); border-radius:10px; padding:.7rem .9rem; margin-bottom:.6rem; }
.svc-vtype__hd { display:flex; align-items:center; justify-content:space-between; font-weight:700; font-size:.85rem; color:#1f2937; margin-bottom:.5rem; }
.svc-opt { display:flex; align-items:center; gap:.5rem; padding:.35rem 0; }
.svc-opt__name { flex:1; font-size:.82rem; color:#374151; }
.svc-opt__in { width:80px; font-family:'Outfit',sans-serif; font-size:.82rem; padding:.35rem .5rem; border:1.5px solid var(--pink-100); border-radius:6px; }
.svc-opt--head { padding-bottom:.15rem; }
.svc-opt__lbl { width:80px; font-size:.68rem; font-weight:700; color:#9ca3af; text-transform:uppercase; letter-spacing:.03em; }
</style>
