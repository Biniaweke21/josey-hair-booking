<template>
  <div class="ap-page">
    <div class="ap-card cust-toolbar">
      <div><h3 class="ap-card__title">Variation Types</h3><p class="ap-card__sub">Reusable options (e.g. Hair Length) attached to services.</p></div>
      <button class="ap-btn-primary" @click="openCreateType"><Plus :size="15" /> New Type</button>
    </div>

    <p v-if="error" class="state-note state-note--error">{{ error }} <button class="state-retry" @click="load">Retry</button></p>

    <div v-if="loading" class="ap-card"><div class="skeleton" style="height:140px;"></div></div>
    <p v-else-if="!types.length" class="ap-card ap-empty">No variation types yet.</p>

    <div v-else v-for="t in types" :key="t.id" class="ap-card">
      <div class="var-hd">
        <div><h3 class="ap-card__title">{{ t.name }}</h3><p class="ap-card__sub">{{ t.options?.length || 0 }} options</p></div>
        <div class="ap-td-acts">
          <button class="ap-btn-outline-sm" @click="openEditType(t)">Rename</button>
          <button class="ap-btn-no-sm" @click="removeType(t)">Delete</button>
        </div>
      </div>
      <div class="var-opts">
        <span v-for="o in t.options" :key="o.id" class="var-chip">
          {{ o.name }}
          <button class="var-chip__x" @click="removeOption(o)" aria-label="Delete option"><X :size="12" /></button>
        </span>
        <span v-if="!t.options?.length" class="ap-card__sub">No options yet.</span>
      </div>
      <div class="svc-attach">
        <input v-model="newOption[t.id]" class="ap-input" placeholder="Add option (e.g. Long)" @keyup.enter="addOption(t)" />
        <button class="ap-btn-outline-sm" :disabled="!newOption[t.id]" @click="addOption(t)">Add</button>
      </div>
    </div>

    <!-- Type form -->
    <div v-if="showType" class="ap-modal" @click.self="showType=false">
      <div class="ap-modal-content">
        <div class="ap-modal-header"><h3>{{ typeForm.id ? 'Rename' : 'New' }} Type</h3><button class="ap-modal-close" @click="showType=false"><X :size="18" /></button></div>
        <form class="ap-form" @submit.prevent="saveType">
          <div class="ap-form-group"><label>Name</label><input v-model="typeForm.name" required /></div>
          <p v-if="typeError" class="ap-error">{{ typeError }}</p>
          <div class="ap-modal-actions">
            <button type="button" class="ap-btn-outline" @click="showType=false">Cancel</button>
            <button type="submit" class="ap-btn-primary" :disabled="savingType">{{ savingType ? 'Saving…' : 'Save' }}</button>
          </div>
        </form>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { Plus, X } from 'lucide-vue-next'
import { variationTypesApi, variationOptionsApi } from '@/services/catalog'
import { apiErrorMessage } from '@/services/api'
import { useToast } from '@/composables/useToast'
import { useConfirm } from '@/composables/useConfirm'

const toast = useToast()
const confirm = useConfirm()

const types = ref([])
const loading = ref(true)
const error = ref(null)
const newOption = ref({})

async function load() {
  loading.value = true; error.value = null
  try {
    const res = await variationTypesApi.list()
    // ensure options loaded per type
    const list = res.data.data
    await Promise.all(list.map(async (t) => {
      if (!t.options) t.options = (await variationTypesApi.options(t.id)).data.data
    }))
    types.value = list
  } catch (e) { error.value = apiErrorMessage(e) } finally { loading.value = false }
}
onMounted(load)

const showType = ref(false)
const savingType = ref(false)
const typeError = ref(null)
const typeForm = ref({})
function openCreateType() { typeForm.value = { name: '' }; typeError.value = null; showType.value = true }
function openEditType(t) { typeForm.value = { id: t.id, name: t.name }; typeError.value = null; showType.value = true }
async function saveType() {
  savingType.value = true; typeError.value = null
  try {
    const editing = !!typeForm.value.id
    if (editing) await variationTypesApi.update(typeForm.value.id, { name: typeForm.value.name })
    else await variationTypesApi.create({ name: typeForm.value.name })
    showType.value = false; await load()
    toast.success(editing ? 'Variation type renamed.' : 'Variation type created.')
  } catch (e) { typeError.value = apiErrorMessage(e) } finally { savingType.value = false }
}
async function removeType(t) {
  if (!(await confirm({ title: 'Delete variation type', message: `Delete "${t.name}" and all its options?`, confirmText: 'Delete', danger: true }))) return
  try { await variationTypesApi.remove(t.id); await load(); toast.success('Variation type deleted.') } catch (e) { toast.error(apiErrorMessage(e)) }
}
async function addOption(t) {
  const name = (newOption.value[t.id] || '').trim()
  if (!name) return
  try { await variationTypesApi.createOption(t.id, { name }); newOption.value[t.id] = ''; await load(); toast.success('Option added.') } catch (e) { toast.error(apiErrorMessage(e)) }
}
async function removeOption(o) {
  if (!(await confirm({ title: 'Delete option', message: `Delete the "${o.name}" option?`, confirmText: 'Delete', danger: true }))) return
  try { await variationOptionsApi.remove(o.id); await load(); toast.success('Option deleted.') } catch (e) { toast.error(apiErrorMessage(e)) }
}
</script>

<style scoped>
.var-hd { display:flex; align-items:flex-start; justify-content:space-between; gap:1rem; margin-bottom:.8rem; }
.var-opts { display:flex; flex-wrap:wrap; gap:.5rem; margin-bottom:.8rem; }
.var-chip { display:inline-flex; align-items:center; gap:.35rem; background:var(--pink-50); border:1px solid var(--pink-100); border-radius:999px; padding:.25rem .7rem; font-size:.8rem; color:#1f2937; }
.var-chip__x { background:none; border:none; cursor:pointer; color:#9ca3af; display:flex; padding:0; }
.var-chip__x:hover { color:#ef4444; }
.svc-attach { display:flex; gap:.5rem; align-items:center; }
.cust-toolbar { display:flex; align-items:center; justify-content:space-between; gap:1rem; flex-wrap:wrap; }
</style>
