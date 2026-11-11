import api from './api'

export const authApi = {
  login: (email, password) => api.post('/auth/login', { email, password }),
  logout: () => api.post('/auth/logout'),
  forgotPassword: (email) => api.post('/auth/forgot-password', { email }),
  resetPassword: (payload) => api.post('/auth/reset-password', payload)
}

export const profileApi = {
  show: () => api.get('/profile'),
  update: (payload) => api.put('/profile', payload),
  updatePassword: (payload) => api.put('/profile/password', payload)
}
