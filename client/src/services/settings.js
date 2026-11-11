import api from './api'

export const settingsApi = {
  // Public read; returns settings grouped by group: { general: [...], booking: [...] }
  list: () => api.get('/settings'),
  update: (pairs) => api.put('/settings', { settings: pairs }),
  uploadLogo: (file) => {
    const form = new FormData()
    form.append('logo', file)
    return api.post('/settings/logo', form, { headers: { 'Content-Type': 'multipart/form-data' } })
  }
}

export const businessHoursApi = {
  list: () => api.get('/business-hours'),
  update: (hours) => api.put('/business-hours', { hours }),
  exceptions: () => api.get('/business-hour-exceptions'),
  createException: (payload) => api.post('/business-hour-exceptions', payload),
  updateException: (id, payload) => api.put(`/business-hour-exceptions/${id}`, payload),
  removeException: (id) => api.delete(`/business-hour-exceptions/${id}`)
}
