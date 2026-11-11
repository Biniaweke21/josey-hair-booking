import axios from 'axios'

const api = axios.create({
  baseURL: import.meta.env.VITE_API_BASE_URL || 'http://localhost:8000/api',
  headers: {
    'Content-Type': 'application/json',
    Accept: 'application/json'
  }
})

// Attach the Sanctum bearer token to every request.
api.interceptors.request.use((config) => {
  const token = localStorage.getItem('auth_token')
  if (token) {
    config.headers.Authorization = `Bearer ${token}`
  }
  return config
})

// On 401 from an admin page, clear the session and bounce to login.
api.interceptors.response.use(
  (response) => response,
  (error) => {
    if (error.response?.status === 401) {
      localStorage.removeItem('auth_token')
      localStorage.removeItem('auth_user')
      const path = window.location.pathname
      if (path.startsWith('/admin') && path !== '/admin/login') {
        window.location.href = '/admin/login'
      }
    }
    return Promise.reject(error)
  }
)

/**
 * Human-readable message from a failed axios call. Understands Laravel's
 * validation shape ({ message, errors }) and network failures.
 * @param {unknown} error
 * @param {string} [fallback]
 * @returns {string}
 */
export function apiErrorMessage(error, fallback = 'Something went wrong. Please try again.') {
  const data = error?.response?.data
  if (data?.errors) {
    return Object.values(data.errors).flat().join(' ')
  }
  if (data?.message) {
    return data.message
  }
  if (error?.code === 'ERR_NETWORK') {
    return 'Cannot reach the server. Check your connection and try again.'
  }
  return fallback
}

/**
 * Field-level Laravel validation errors, keyed as the API returns them
 * (dot notation, e.g. "customer.email").
 * @param {unknown} error
 * @returns {Record<string, string[]>}
 */
export function apiFieldErrors(error) {
  return error?.response?.data?.errors ?? {}
}

export default api
