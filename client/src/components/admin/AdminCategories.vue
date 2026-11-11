<template>
  <div class="ap-page">
    <div class="ap-card cust-toolbar">
      <div><h3 class="ap-card__title">Categories</h3><p class="ap-card__sub">{{ categories.length }} total</p></div>
      <button class="ap-btn-primary" @click="openCreate"><Plus :size="15" /> New Category</button>
    </div>

    <p v-if="error" class="state-note state-note--error">{{ error }} <button class="state-retry" @click="load">Retry</button></p>

    <div class="ap-card ap-table-card">
      <div class="ap-table-scroll">
        <table class="ap-table">
          <thead><tr><th>Name</th><th>Slug</th><th>Services</th><th>Status</th><th>Actions</th></tr></thead>
          <tbody>
            <tr v-if="loading"><td colspan="5"><div class="skeleton" style="height:100px;"></div></td></tr>
            <tr v-else-if="!categories.length"><td colspan="5" class="ap-empty">No categories yet.</td></tr>
            <tr v-for="c in categories" :key="c.id">
              <td><div class="ap-td-client"><img v-if="c.image_url" :src="c.image_url" class="cat-thumb" alt="" /><div v-else class="ap-td-avatar">{{ c.name[0] }}</div>{{ c.name }}</div></td>
              <td>{{ c.slug }}</td>
              <td>{{ c.services_count ?? 0 }}</td>
              <td><span class="ap-badge" :class="c.is_active ? 'ap-badge--available' : 'ap-badge--full'">{{ c.is_active ? 'Active' : 'Hidden' }}</span></td>
              <td><div class="ap-td-acts"><button class="ap-btn-outline-sm" @click="openEdit(c)">Edit</button><button class="ap-btn-no-sm" @click="remove(c)">Delete</button></div></td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <div v-if="showForm" class="ap-modal" @click.self="showForm=false">
      <div class="ap-modal-content">
        <div class="ap-modal-header"><h3>{{ form.id ? 'Edit' : 'New' }} Category</h3><button class="ap-modal-close" @click="showForm=false"><X :size="18" /></button></div>
        <form class="ap-form" @submit.prevent="save">
          <div class="ap-form-group"><label>Name</label><input v-model="form.name" required /></div>
          <div class="ap-form-group"><label>Description</label><textarea v-model="form.description" rows="2"></textarea></div>
          <div class="ap-form-group"><label>Sort order</label><input v-model.number="form.sort_order" type="number" min="0" /></div>
          <div class="ap-form-group"><label>Image</label><input type="file" accept="image/*" @change="onFile" /></div>
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
import { categoriesApi } from '@/services/catalog'
import { apiErrorMessage } from '@/services/api'
import { useToast } from '@/composables/useToast'
import { useConfirm } from '@/composables/useConfirm'

const toast = useToast()
const confirm = useConfirm()

const categories = ref([])
const loading = ref(true)
const error = ref(null)

async function load() {
  loading.value = true; error.value = null
  try { categories.value = (await categoriesApi.list()).data.data } catch (e) { error.value = apiErrorMessage(e) } finally { loading.value = false }
}
onMounted(load)

const showForm = ref(false)
const saving = ref(false)
const formError = ref(null)
const form = ref({})
let imageFile = null

function openCreate() { form.value = { name: '', description: '', sort_order: 0, is_active: true }; imageFile = null; formError.value = null; showForm.value = true }
function openEdit(c) { form.value = { ...c }; imageFile = null; formError.value = null; showForm.value = true }
function onFile(e) { imageFile = e.target.files[0] || null }

async function save() {
  saving.value = true; formError.value = null
  try {
    const payload = { name: form.value.name, description: form.value.description, sort_order: form.value.sort_order || 0, is_active: form.value.is_active }
    let saved
    if (form.value.id) saved = (await categoriesApi.update(form.value.id, payload)).data.data
    else saved = (await categoriesApi.create(payload)).data.data
    if (imageFile) await categoriesApi.uploadImage(saved.id, imageFile)
    showForm.value = false
    await load()
    toast.success(form.value.id ? 'Category updated.' : 'Category created.')
  } catch (e) { formError.value = apiErrorMessage(e) } finally { saving.value = false }
}

async function remove(c) {
  if (!(await confirm({ title: 'Delete category', message: `Delete "${c.name}"? This cannot be undone.`, confirmText: 'Delete', danger: true }))) return
  try { await categoriesApi.remove(c.id); await load(); toast.success('Category deleted.') } catch (e) { toast.error(apiErrorMessage(e)) }
}
</script>

<style scoped>
.cust-toolbar { display:flex; align-items:center; justify-content:space-between; gap:1rem; flex-wrap:wrap; }
.cat-thumb { width:30px; height:30px; border-radius:6px; object-fit:cover; }
.cust-check { display:flex; align-items:center; gap:.5rem; font-size:.85rem; color:#1f2937; margin-bottom:.5rem; }
</style>
