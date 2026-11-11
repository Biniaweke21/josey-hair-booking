<template>
  <div class="ap-page">
    <p v-if="error" class="state-note state-note--error">{{ error }} <button class="state-retry" @click="load">Retry</button></p>

    <div v-if="loading" class="ap-card"><div class="skeleton" style="height:180px;"></div></div>

    <template v-else>
      <!-- Profile -->
      <form class="ap-card" @submit.prevent="saveProfile">
        <div class="ap-profile-top">
          <div class="pf-avatar">{{ initials }}</div>
          <div><p class="ap-profile-name">{{ form.name }}</p><p class="ap-profile-role">{{ role }}</p></div>
        </div>
        <div class="ap-form-grid" style="margin-top:1.25rem;">
          <div class="ap-form-group"><label>Name</label><input v-model="form.name" required /></div>
          <div class="ap-form-group"><label>Email</label><input v-model="form.email" type="email" /></div>
          <div class="ap-form-group"><label>Phone</label><input v-model="form.phone" type="tel" /></div>
        </div>
        <p v-if="profileSaved" class="ap-success">Profile updated.</p>
        <p v-if="profileError" class="ap-error">{{ profileError }}</p>
        <div class="ap-form-actions"><button type="submit" class="ap-btn-primary" :disabled="savingProfile">{{ savingProfile ? 'Saving…' : 'Save Profile' }}</button></div>
      </form>

      <!-- Password -->
      <form class="ap-card" @submit.prevent="savePassword">
        <h3 class="ap-card__title">Change Password</h3>
        <div class="ap-form-grid" style="margin-top:1rem;">
          <div class="ap-form-group"><label>Current password</label><input v-model="pw.current_password" type="password" autocomplete="current-password" /></div>
          <div class="ap-form-group"><label>New password</label><input v-model="pw.password" type="password" autocomplete="new-password" /></div>
          <div class="ap-form-group"><label>Confirm new password</label><input v-model="pw.password_confirmation" type="password" autocomplete="new-password" /></div>
        </div>
        <p v-if="pwSaved" class="ap-success">Password changed.</p>
        <p v-if="pwError" class="ap-error">{{ pwError }}</p>
        <div class="ap-form-actions"><button type="submit" class="ap-btn-primary" :disabled="savingPw">{{ savingPw ? 'Saving…' : 'Change Password' }}</button></div>
      </form>
    </template>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { profileApi } from '@/services/auth'
import { useAuthStore } from '@/stores/auth'
import { apiErrorMessage } from '@/services/api'
import { useToast } from '@/composables/useToast'

const toast = useToast()

const auth = useAuthStore()
const loading = ref(true)
const error = ref(null)
const form = ref({ name: '', email: '', phone: '' })
const role = ref('')

const initials = computed(() => (form.value.name || 'A').split(' ').map((w) => w[0]).slice(0, 2).join('').toUpperCase())

async function load() {
  loading.value = true; error.value = null
  try {
    const res = await profileApi.show()
    const u = res.data.data || res.data.user || res.data
    form.value = { name: u.name || '', email: u.email || '', phone: u.phone || '' }
    role.value = u.roles?.[0] ? u.roles[0].charAt(0).toUpperCase() + u.roles[0].slice(1) : 'Staff'
  } catch (e) { error.value = apiErrorMessage(e) } finally { loading.value = false }
}
onMounted(load)

const savingProfile = ref(false)
const profileSaved = ref(false)
const profileError = ref(null)
async function saveProfile() {
  savingProfile.value = true; profileSaved.value = false; profileError.value = null
  try {
    const res = await profileApi.update({ name: form.value.name, email: form.value.email, phone: form.value.phone })
    const u = res.data.data || res.data.user
    if (u) { auth.user = u; localStorage.setItem('auth_user', JSON.stringify(u)) }
    profileSaved.value = true; toast.success('Profile updated.'); setTimeout(() => { profileSaved.value = false }, 3000)
  } catch (e) { profileError.value = apiErrorMessage(e); toast.error(apiErrorMessage(e)) } finally { savingProfile.value = false }
}

const pw = ref({ current_password: '', password: '', password_confirmation: '' })
const savingPw = ref(false)
const pwSaved = ref(false)
const pwError = ref(null)
async function savePassword() {
  savingPw.value = true; pwSaved.value = false; pwError.value = null
  try {
    await profileApi.updatePassword({ ...pw.value })
    pw.value = { current_password: '', password: '', password_confirmation: '' }
    pwSaved.value = true; toast.success('Password changed.'); setTimeout(() => { pwSaved.value = false }, 3000)
  } catch (e) { pwError.value = apiErrorMessage(e); toast.error(apiErrorMessage(e)) } finally { savingPw.value = false }
}
</script>

<style scoped>
.ap-profile-top { display:flex; align-items:center; gap:1.25rem; flex-wrap:wrap; }
.pf-avatar { width:64px; height:64px; border-radius:50%; background:linear-gradient(135deg,#2563EB,#5BA7FF); color:#fff; display:flex; align-items:center; justify-content:center; font-size:1.4rem; font-weight:700; }
.ap-form-actions { margin-top:1rem; }
</style>
