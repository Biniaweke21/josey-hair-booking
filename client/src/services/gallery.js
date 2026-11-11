import api from './api'

export const galleryApi = {
  list: (params = {}) => api.get('/gallery-items', { params }),
  get: (id) => api.get(`/gallery-items/${id}`),
  create: ({ image, title, category_id, sort_order, is_active }) => {
    const form = new FormData()
    form.append('image', image)
    if (title != null) form.append('title', title)
    if (category_id != null) form.append('category_id', String(category_id))
    if (sort_order != null) form.append('sort_order', String(sort_order))
    if (is_active != null) form.append('is_active', is_active ? '1' : '0')
    return api.post('/gallery-items', form, { headers: { 'Content-Type': 'multipart/form-data' } })
  },
  update: (id, { image, title, category_id, sort_order, is_active }) => {
    const form = new FormData()
    if (image) form.append('image', image)
    if (title !== undefined) form.append('title', title ?? '')
    if (category_id != null) form.append('category_id', String(category_id))
    if (sort_order != null) form.append('sort_order', String(sort_order))
    if (is_active != null) form.append('is_active', is_active ? '1' : '0')
    form.append('_method', 'PUT')
    return api.post(`/gallery-items/${id}`, form, { headers: { 'Content-Type': 'multipart/form-data' } })
  },
  remove: (id) => api.delete(`/gallery-items/${id}`)
}
