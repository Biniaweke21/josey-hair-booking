import api from './api'

// Public catalog reads (no auth) + admin writes. Endpoints match routes/api.php.

export const categoriesApi = {
  list: (params = {}) => api.get('/categories', { params }),
  get: (id) => api.get(`/categories/${id}`),
  create: (payload) => api.post('/categories', payload),
  update: (id, payload) => api.put(`/categories/${id}`, payload),
  remove: (id) => api.delete(`/categories/${id}`),
  uploadImage: (id, file) => {
    const form = new FormData()
    form.append('image', file)
    return api.post(`/categories/${id}/image`, form, { headers: { 'Content-Type': 'multipart/form-data' } })
  }
}

export const servicesApi = {
  list: (params = {}) => api.get('/services', { params }),
  get: (id) => api.get(`/services/${id}`),
  create: (payload) => api.post('/services', payload),
  update: (id, payload) => api.put(`/services/${id}`, payload),
  remove: (id) => api.delete(`/services/${id}`),
  uploadImage: (id, file) => {
    const form = new FormData()
    form.append('image', file)
    return api.post(`/services/${id}/image`, form, { headers: { 'Content-Type': 'multipart/form-data' } })
  },

  // Per-service configuration used by the booking flow
  variationTypes: (serviceId) => api.get(`/services/${serviceId}/variation-types`),
  variationOptions: (serviceId) => api.get(`/services/${serviceId}/variation-options`),
  addOns: (serviceId) => api.get(`/services/${serviceId}/add-ons`),

  attachVariationType: (serviceId, variationTypeId) =>
    api.post(`/services/${serviceId}/variation-types`, { variation_type_id: variationTypeId }),
  detachVariationType: (serviceId, variationTypeId) =>
    api.delete(`/services/${serviceId}/variation-types/${variationTypeId}`),
  setVariationOption: (serviceId, variationOptionId, payload) =>
    api.put(`/services/${serviceId}/variation-options/${variationOptionId}`, payload),
  removeVariationOption: (serviceId, variationOptionId) =>
    api.delete(`/services/${serviceId}/variation-options/${variationOptionId}`),
  attachAddOn: (serviceId, addOnId) => api.post(`/services/${serviceId}/add-ons`, { add_on_id: addOnId }),
  detachAddOn: (serviceId, addOnId) => api.delete(`/services/${serviceId}/add-ons/${addOnId}`)
}

export const variationTypesApi = {
  list: (params = {}) => api.get('/variation-types', { params }),
  get: (id) => api.get(`/variation-types/${id}`),
  create: (payload) => api.post('/variation-types', payload),
  update: (id, payload) => api.put(`/variation-types/${id}`, payload),
  remove: (id) => api.delete(`/variation-types/${id}`),
  options: (typeId) => api.get(`/variation-types/${typeId}/options`),
  createOption: (typeId, payload) => api.post(`/variation-types/${typeId}/options`, payload)
}

export const variationOptionsApi = {
  update: (id, payload) => api.put(`/variation-options/${id}`, payload),
  remove: (id) => api.delete(`/variation-options/${id}`)
}

export const addOnsApi = {
  list: (params = {}) => api.get('/add-ons', { params }),
  get: (id) => api.get(`/add-ons/${id}`),
  create: (payload) => api.post('/add-ons', payload),
  update: (id, payload) => api.put(`/add-ons/${id}`, payload),
  remove: (id) => api.delete(`/add-ons/${id}`)
}
