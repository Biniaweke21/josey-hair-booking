<template>
  <div class="login-root">

    <!-- Left panel — branding -->
    <div class="login-brand" :style="{ backgroundImage: `url('${PAGE_HERO_IMAGE}')` }" aria-hidden="true">
      <div class="login-brand__inner">
        <div class="login-brand__logo">
          <img src="/images/josey.jpg" :alt="salonName" class="login-brand__logo-img" />
          <span class="login-brand__name">{{ salonName }}</span>
        </div>
        <h2 class="login-brand__tagline">
          Beautiful Hair.<br /><em>Perfect Confidence.</em>
        </h2>
        <p class="login-brand__sub">Admin Portal — Manage your salon from one place.</p>

        <!-- Decorative style images grid -->
        <div class="login-brand__grid">
          <div v-for="(img, n) in adminLoginCollage" :key="n" class="login-brand__cell">
            <img :src="img" :alt="`Hair design ${n + 1}`" />
          </div>
        </div>
      </div>
      <div class="login-brand__overlay"></div>
    </div>

    <!-- Right panel — login / reset form -->
    <div class="login-form-side">
      <div class="login-form-wrap">

        <div class="login-form-logo">
          <img src="/images/josey.jpg" :alt="salonName" class="login-form-logo-img" />
          <span class="login-form-name">{{ salonName }}</span>
        </div>

        <!-- Sign in -->
        <template v-if="mode === 'login'">
          <h1 class="login-form__title">Welcome back</h1>
          <p class="login-form__sub">Sign in to your admin dashboard</p>

          <form class="login-form" @submit.prevent="submit" novalidate>
            <div class="login-field">
              <label for="login-email" class="login-label">Email address</label>
              <div class="login-input-wrap">
                <Mail :size="16" class="login-input-icon" aria-hidden="true" />
                <input
                  id="login-email"
                  v-model="email"
                  type="email"
                  placeholder="admin@example.com"
                  class="login-input"
                  :class="{ 'is-error': !!authStore.error }"
                  autocomplete="email"
                  required
                  :disabled="authStore.loading"
                />
              </div>
            </div>

            <div class="login-field">
              <label for="login-password" class="login-label">
                Password
                <button type="button" class="login-label__link" @click="switchMode('forgot')">Forgot password?</button>
              </label>
              <div class="login-input-wrap">
                <Lock :size="16" class="login-input-icon" aria-hidden="true" />
                <input
                  id="login-password"
                  v-model="password"
                  :type="showPassword ? 'text' : 'password'"
                  placeholder="Enter your password"
                  class="login-input"
                  :class="{ 'is-error': !!authStore.error }"
                  autocomplete="current-password"
                  required
                  :disabled="authStore.loading"
                />
                <button
                  type="button"
                  class="login-eye"
                  @click="showPassword = !showPassword"
                  :aria-label="showPassword ? 'Hide password' : 'Show password'"
                >
                  <component :is="showPassword ? EyeOff : Eye" :size="16" />
                </button>
              </div>
            </div>

            <Transition name="err-fade">
              <p v-if="authStore.error" class="login-error" role="alert">
                <AlertCircle :size="14" aria-hidden="true" />
                {{ authStore.error }}
              </p>
            </Transition>

            <button
              type="submit"
              class="login-submit"
              :disabled="authStore.loading || !email || !password"
            >
              <span v-if="authStore.loading" class="login-spinner" aria-hidden="true"></span>
              <span>{{ authStore.loading ? 'Signing in…' : 'Sign In' }}</span>
            </button>
          </form>
        </template>

        <!-- Forgot password -->
        <template v-else>
          <h1 class="login-form__title">Reset password</h1>
          <p class="login-form__sub">Enter your email and we'll send you a reset link.</p>

          <form class="login-form" @submit.prevent="requestReset" novalidate>
            <div class="login-field">
              <label for="reset-email" class="login-label">Email address</label>
              <div class="login-input-wrap">
                <Mail :size="16" class="login-input-icon" aria-hidden="true" />
                <input
                  id="reset-email"
                  v-model="email"
                  type="email"
                  placeholder="admin@example.com"
                  class="login-input"
                  :class="{ 'is-error': !!resetError }"
                  autocomplete="email"
                  required
                  :disabled="resetLoading"
                />
              </div>
            </div>

            <Transition name="err-fade">
              <p v-if="resetError" class="login-error" role="alert">
                <AlertCircle :size="14" aria-hidden="true" />
                {{ resetError }}
              </p>
            </Transition>

            <Transition name="err-fade">
              <p v-if="resetSent" class="login-success" role="alert">
                <CheckCircle2 :size="14" aria-hidden="true" />
                If that email exists, a reset link is on its way.
              </p>
            </Transition>

            <button type="submit" class="login-submit" :disabled="resetLoading || !email">
              <span v-if="resetLoading" class="login-spinner" aria-hidden="true"></span>
              <span>{{ resetLoading ? 'Sending…' : 'Send reset link' }}</span>
            </button>
          </form>
        </template>

        <p class="login-footer-note">
          <button v-if="mode === 'forgot'" type="button" class="login-back-link" @click="switchMode('login')">
            <ArrowLeft :size="14" aria-hidden="true" />
            Back to sign in
          </button>
          <a v-else href="/" class="login-back-link">
            <ArrowLeft :size="14" aria-hidden="true" />
            Back to website
          </a>
        </p>
      </div>
    </div>

  </div>
</template>

<script setup>
import { ref } from 'vue'
import { useRouter } from 'vue-router'
import { Mail, Lock, Eye, EyeOff, AlertCircle, ArrowLeft, CheckCircle2 } from 'lucide-vue-next'
import { useAuthStore } from '@/stores/auth'
import { useSettings } from '@/composables/useSettings'
import { authApi } from '@/services/auth'
import { apiErrorMessage } from '@/services/api'
import { ADMIN_LOGIN_COLLAGE, PAGE_HERO_IMAGE } from '@/utils/stockImages'

const router = useRouter()
const authStore = useAuthStore()
const { salonName } = useSettings()
const adminLoginCollage = ADMIN_LOGIN_COLLAGE

const mode = ref('login')
const email = ref('')
const password = ref('')
const showPassword = ref(false)

const resetLoading = ref(false)
const resetSent = ref(false)
const resetError = ref(null)

function switchMode(next) {
  mode.value = next
  authStore.error = null
  resetError.value = null
  resetSent.value = false
}

async function submit() {
  const ok = await authStore.login(email.value, password.value)
  if (ok) router.push('/admin')
}

async function requestReset() {
  resetLoading.value = true
  resetError.value = null
  resetSent.value = false
  try {
    await authApi.forgotPassword(email.value)
    resetSent.value = true
  } catch (e) {
    resetError.value = apiErrorMessage(e, 'Could not send reset link. Please try again.')
  } finally {
    resetLoading.value = false
  }
}
</script>

<style scoped>
/* ── Root ── */
.login-root {
  display: flex;
  min-height: 100vh;
  width: 100%;
  font-family: 'Outfit', sans-serif;
  background: #EFF6FF;
}

/* ── Left brand panel ── */
.login-brand {
  display: none;
  position: relative;
  overflow: hidden;
  background-size: cover;
  background-position: center top;
  background-repeat: no-repeat;
}
@media (min-width: 900px) {
  .login-brand { display: flex; flex: 1; }
}

.login-brand__overlay {
  position: absolute; inset: 0;
  background: linear-gradient(160deg, rgba(37,99,235,0.80) 0%, rgba(91,167,255,0.72) 100%);
  z-index: 1;
}
.login-brand__inner {
  position: relative; z-index: 2;
  display: flex; flex-direction: column;
  padding: 3rem;
  justify-content: center;
  gap: 1.5rem;
  width: 100%;
}

.login-brand__logo {
  display: flex; align-items: center; gap: 0.5rem;
}
.login-brand__logo-img {
  width: 40px; height: 40px;
  border-radius: 50%;
  object-fit: cover;
  border: 2px solid rgba(255,255,255,0.7);
}
.login-brand__name {
  font-family: 'Outfit', sans-serif;
  font-size: 1.5rem; font-weight: 700; color: #fff;
}
.login-brand__tagline {
  font-family: 'Outfit', sans-serif;
  font-size: clamp(1.8rem, 3.5vw, 2.8rem);
  font-weight: 700; color: #fff;
  line-height: 1.2;
}
.login-brand__tagline em {
  font-style: italic; color: #BFDBFE;
}
.login-brand__sub {
  font-size: 0.95rem; color: rgba(255,255,255,0.75);
  max-width: 320px;
}

/* Mini style grid */
.login-brand__grid {
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  gap: 8px;
  max-width: 300px;
  border-radius: 16px;
  overflow: hidden;
}
.login-brand__cell {
  aspect-ratio: 1; overflow: hidden;
}
.login-brand__cell img {
  width: 100%; height: 100%; object-fit: cover;
  object-position: center top;
  transition: transform 0.4s ease;
}
.login-brand__cell:hover img { transform: scale(1.08); }

/* ── Right form panel ── */
.login-form-side {
  display: flex; align-items: center; justify-content: center;
  flex: 1; padding: 2rem;
  background: #fff;
}
.login-form-wrap {
  width: 100%; max-width: 400px;
  display: flex; flex-direction: column; gap: 0;
}

/* Form logo (mobile only) */
.login-form-logo {
  display: flex; align-items: center; gap: 0.45rem;
  margin-bottom: 2rem;
}
@media (min-width: 900px) { .login-form-logo { display: none; } }
.login-form-logo-img { width: 34px; height: 34px; border-radius: 50%; object-fit: cover; border: 1.5px solid #E5E7EB; }
.login-form-name  {
  font-family: 'Outfit', sans-serif;
  font-size: 1.25rem; font-weight: 700; color: #1f2937;
}

.login-form__title {
  font-family: 'Outfit', sans-serif;
  font-size: 1.75rem; font-weight: 700; color: #1f2937;
  margin-bottom: 0.3rem;
}
.login-form__sub {
  font-size: 0.9rem; color: #9ca3af;
  margin-bottom: 2rem;
}

.login-form { display: flex; flex-direction: column; gap: 1.1rem; }

.login-field { display: flex; flex-direction: column; gap: 0.35rem; }
.login-label {
  font-size: 0.82rem; font-weight: 600; color: #1f2937;
  display: flex; align-items: center; justify-content: space-between;
}
.login-label__link {
  font-size: 0.72rem; font-weight: 600; color: #5BA7FF;
  background: none; border: none; cursor: pointer; padding: 0;
  transition: color 0.2s;
}
.login-label__link:hover { color: #2563EB; }

.login-input-wrap { position: relative; }
.login-input-icon {
  position: absolute; left: 0.85rem; top: 50%; transform: translateY(-50%);
  color: #64748B; pointer-events: none;
  display: flex; align-items: center;
}
.login-input {
  font-family: 'Outfit', sans-serif; font-size: 0.93rem;
  width: 100%; padding: 0.82rem 2.8rem 0.82rem 2.6rem;
  border: 1.5px solid #DBEAFE; border-radius: 10px;
  color: #1f2937; background: #fff;
  outline: none; transition: border-color 0.2s, box-shadow 0.2s;
}
.login-input:focus {
  border-color: #5BA7FF;
  box-shadow: 0 0 0 3px rgba(91, 167, 255,0.12);
}
.login-input.is-error { border-color: #ef4444; }
.login-input:disabled { opacity: 0.55; cursor: not-allowed; }

.login-eye {
  position: absolute; right: 0.85rem; top: 50%; transform: translateY(-50%);
  background: none; border: none; cursor: pointer;
  color: #9ca3af; display: flex; align-items: center;
  transition: color 0.2s;
}
.login-eye:hover { color: #2563EB; }

.login-error {
  display: flex; align-items: center; gap: 0.5rem;
  font-size: 0.82rem; color: #ef4444;
  background: #fef2f2; border: 1px solid #fecaca;
  padding: 0.65rem 0.9rem; border-radius: 8px;
}
.login-success {
  display: flex; align-items: center; gap: 0.5rem;
  font-size: 0.82rem; color: #16a34a;
  background: #f0fdf4; border: 1px solid #bbf7d0;
  padding: 0.65rem 0.9rem; border-radius: 8px;
}

.login-submit {
  font-family: 'Outfit', sans-serif; font-size: 0.95rem; font-weight: 700;
  padding: 0.88rem;
  background: linear-gradient(135deg, #2563EB, #5BA7FF);
  color: #fff; border: none; border-radius: 10px;
  cursor: pointer; transition: all 0.2s;
  display: flex; align-items: center; justify-content: center; gap: 0.5rem;
  box-shadow: 0 4px 18px rgba(91, 167, 255,0.35);
  margin-top: 0.4rem;
}
.login-submit:hover:not(:disabled) {
  transform: translateY(-2px);
  box-shadow: 0 6px 24px rgba(91, 167, 255,0.45);
}
.login-submit:disabled { opacity: 0.55; cursor: not-allowed; transform: none; }

/* Spinner */
.login-spinner {
  width: 16px; height: 16px;
  border: 2px solid rgba(255,255,255,0.35);
  border-top-color: #fff;
  border-radius: 50%;
  animation: spin 0.7s linear infinite;
  flex-shrink: 0;
}
@keyframes spin { to { transform: rotate(360deg); } }

.login-footer-note {
  margin-top: 1.75rem; text-align: center;
}
.login-back-link {
  display: inline-flex; align-items: center; gap: 0.4rem;
  font-size: 0.83rem; color: #9ca3af; text-decoration: none;
  background: none; border: none; cursor: pointer;
  transition: color 0.2s;
}
.login-back-link:hover { color: #2563EB; }

/* Error transition */
.err-fade-enter-active, .err-fade-leave-active { transition: opacity 0.2s ease, transform 0.2s ease; }
.err-fade-enter-from, .err-fade-leave-to { opacity: 0; transform: translateY(-4px); }
</style>
