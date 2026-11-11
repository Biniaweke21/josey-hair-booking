import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import { authApi, profileApi } from '@/services/auth'
import { apiErrorMessage } from '@/services/api'

export const useAuthStore = defineStore('auth', () => {
  // Restore session from localStorage on load.
  const token = ref(localStorage.getItem('auth_token') || null)
  const user = ref(JSON.parse(localStorage.getItem('auth_user') || 'null'))
  const error = ref(null)
  const loading = ref(false)

  const isAuthenticated = computed(() => !!token.value)

  async function login(email, password) {
    loading.value = true
    error.value = null
    try {
      const res = await authApi.login(email, password)
      token.value = res.data.token
      user.value = res.data.user
      localStorage.setItem('auth_token', token.value)
      localStorage.setItem('auth_user', JSON.stringify(user.value))
      return true
    } catch (e) {
      error.value =
        e.response?.status === 401
          ? 'Invalid email or password'
          : apiErrorMessage(e, 'Login failed. Please try again.')
      return false
    } finally {
      loading.value = false
    }
  }

  async function logout() {
    try {
      await authApi.logout()
    } catch {
      // ignore — clear the client session regardless
    } finally {
      token.value = null
      user.value = null
      localStorage.removeItem('auth_token')
      localStorage.removeItem('auth_user')
    }
  }

  async function checkAuth() {
    if (!token.value) return false
    try {
      const res = await profileApi.show()
      user.value = res.data.data
      localStorage.setItem('auth_user', JSON.stringify(user.value))
      return true
    } catch {
      await logout()
      return false
    }
  }

  /** @param {string} permission e.g. 'bookings.update' */
  function can(permission) {
    return user.value?.permissions?.includes(permission) ?? false
  }

  return { token, user, error, loading, isAuthenticated, login, logout, checkAuth, can }
})
