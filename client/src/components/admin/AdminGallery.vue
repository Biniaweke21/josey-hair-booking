<template>
  <div class="ap-page">
    <div class="ap-card cust-toolbar">
      <div><h3 class="ap-card__title">Gallery</h3><p class="ap-card__sub">{{ items.length }} photos</p></div>
      <button class="ap-btn-primary" @click="openUpload"><Plus :size="15" /> Add Photo</button>
    </div>

    <p v-if="error" class="state-note state-note--error">{{ error }} <button class="state-retry" @click="load">Retry</button></p>

    <div v-if="loading" class="ap-card"><div class="skeleton" style="height:200px;"></div></div>
    <p v-else-if="!items.length" class="ap-card ap-empty">No photos yet. Add your first one.</p>

    <div v-else class="gal-grid">
      <div v-for="g in items" :key="g.id" class="gal-item">
        <img :src="g.image_url" :alt="g.title || 'Gallery photo'" class="gal-img" />
        <div class="gal-overlay">
          <span v-if="g.category" class="gal-tag">{{ g.category.name }}</span>
          <button class="gal-del" @click="remove(g)" aria-label="Delete photo"><Trash2 :size="16" /></button>
        </div>
        <p v-if="g.title" class="gal-title">{{ g.title }}</p>
      </div>
    </div>

    <!-- Upload modal -->
    <div v-if="showForm" class="ap-modal" @click.self="showForm=false">
      <div class="ap-modal-content">
        <div class="ap-modal-header"><h3>Add Photo</h3><button class="ap-modal-close" @click="showForm=false"><X :size="18" /></button></div>
        <form class="ap-form" @submit.prevent="save">
          <div class="ap-form-group"><label>Image</label><input type="file" accept="image/*" required @change="onFile" /></div>
          <div class="ap-form-group"><label>Title (optional)</label><input v-model="form.title" /></div>
          <div class="ap-form-group"><label>Category (optional)</label>
            <select v-model="form.category_id"><option value="">None</option><option v-for="c in categories" :key="c.id" :value="c.id">{{ c.name }}</option></select>
          </div>
          <label class="cust-check"><input type="checkbox" v-model="form.is_active" /> Visible on site</label>
          <p v-if="formError" class="ap-error">{{ formError }}</p>
          <div class="ap-modal-actions">
            <button type="button" class="ap-btn-outline" @click="showForm=false">Cancel</button>
            <button type="submit" class="ap-btn-primary" :disabled="saving || !imageFile">{{ saving ? 'Uploading…' : 'Upload' }}</button>
          </div>
        </form>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { Plus, X, Trash2 } from 'lucide-vue-next'
import { galleryApi } from '@/services/gallery'
import { categoriesApi } from '@/services/catalog'
import { apiErrorMessage } from '@/services/api'
import { useToast } from '@/composables/useToast'
import { useConfirm } from '@/composables/useConfirm'

const toast = useToast()
const confirm = useConfirm()

const items = ref([])
const categories = ref([])
const loading = ref(true)
const error = ref(null)

async function load() {
  loading.value = true; error.value = null
  try {
    const [g, c] = await Promise.all([galleryApi.list(), categoriesApi.list()])
    items.value = g.data.data
    categories.value = c.data.data
  } catch (e) { error.value = apiErrorMessage(e) } finally { loading.value = false }
}
onMounted(load)

const showForm = ref(false)
const saving = ref(false)
const formError = ref(null)
const form = ref({ title: '', category_id: '', is_active: true })
let imageFile = null
function openUpload() { form.value = { title: '', category_id: '', is_active: true }; imageFile = null; formError.value = null; showForm.value = true }
function onFile(e) { imageFile = e.target.files[0] || null }

async function save() {
  if (!imageFile) return
  saving.value = true; formError.value = null
  try {
    await galleryApi.create({ image: imageFile, title: form.value.title || undefined, category_id: form.value.category_id || undefined, is_active: form.value.is_active })
    showForm.value = false; await load()
    toast.success('Photo uploaded.')
  } catch (e) { formError.value = apiErrorMessage(e) } finally { saving.value = false }
}
async function remove(g) {
  if (!(await confirm({ title: 'Delete photo', message: 'Remove this photo from the gallery?', confirmText: 'Delete', danger: true }))) return
  try { await galleryApi.remove(g.id); await load(); toast.success('Photo deleted.') } catch (e) { toast.error(apiErrorMessage(e)) }
}
</script>

<style scoped>
.cust-toolbar { display:flex; align-items:center; justify-content:space-between; gap:1rem; flex-wrap:wrap; }
.cust-check { display:flex; align-items:center; gap:.5rem; font-size:.85rem; color:#1f2937; margin-bottom:.5rem; }
.gal-grid { display:grid; grid-template-columns:repeat(auto-fill,minmax(180px,1fr)); gap:14px; }
.gal-item { position:relative; border-radius:12px; overflow:hidden; background:#fff; border:1px solid var(--pink-100); }
.gal-img { width:100%; aspect-ratio:1; object-fit:cover; display:block; }
.gal-overlay { position:absolute; top:0; left:0; right:0; display:flex; align-items:flex-start; justify-content:space-between; padding:.5rem; }
.gal-tag { font-size:.62rem; font-weight:700; text-transform:uppercase; letter-spacing:.05em; color:#fff; background:rgba(15,23,42,.7); padding:.15rem .5rem; border-radius:999px; }
.gal-del { width:28px; height:28px; border-radius:8px; border:none; background:rgba(255,255,255,.9); color:#ef4444; cursor:pointer; display:flex; align-items:center; justify-content:center; }
.gal-del:hover { background:#ef4444; color:#fff; }
.gal-title { font-size:.78rem; color:#374151; padding:.5rem .6rem; }
</style>
