import api from './api'

// ── Admin booking management ──────────────────────────────────────────────
export const bookingsApi = {
  list: (params = {}) => api.get('/bookings', { params }),
  get: (id) => api.get(`/bookings/${id}`),
  timeline: (id) => api.get(`/bookings/${id}/timeline`),
  availableEmployees: (id) => api.get(`/bookings/${id}/available-employees`),
  assignItemEmployee: (bookingId, bookingItemId, employeeId) =>
    api.patch(`/bookings/${bookingId}/services/${bookingItemId}/employee`, { employee_id: employeeId }),
  reschedule: (bookingId, payload) => api.patch(`/bookings/${bookingId}/reschedule`, payload),
  checkIn: (bookingId) => api.patch(`/bookings/${bookingId}/check-in`),
  checkOut: (bookingId) => api.patch(`/bookings/${bookingId}/check-out`),
  updateStatus: (bookingId, status, cancelReason) =>
    api.patch(`/bookings/${bookingId}/status`, { status, cancel_reason: cancelReason })
}

// ── Customers (read-only) ─────────────────────────────────────────────────
export const customersApi = {
  list: (params = {}) => api.get('/customers', { params }),
  get: (id) => api.get(`/customers/${id}`)
}

// ── Employees + weekly roster + leave ─────────────────────────────────────
export const employeesApi = {
  list: (params = {}) => api.get('/employees', { params }),
  get: (id) => api.get(`/employees/${id}`),
  create: (payload) => api.post('/employees', payload),
  update: (id, payload) => api.put(`/employees/${id}`, payload),
  remove: (id) => api.delete(`/employees/${id}`),

  schedules: (employeeId) => api.get(`/employees/${employeeId}/schedules`),
  addSchedule: (employeeId, payload) => api.post(`/employees/${employeeId}/schedules`, payload),
  updateSchedule: (employeeId, scheduleId, payload) => api.put(`/employees/${employeeId}/schedules/${scheduleId}`, payload),
  removeSchedule: (employeeId, scheduleId) => api.delete(`/employees/${employeeId}/schedules/${scheduleId}`),

  leaves: (employeeId) => api.get(`/employees/${employeeId}/leaves`),
  addLeave: (employeeId, payload) => api.post(`/employees/${employeeId}/leaves`, payload),
  updateLeave: (employeeId, leaveId, payload) => api.put(`/employees/${employeeId}/leaves/${leaveId}`, payload),
  removeLeave: (employeeId, leaveId) => api.delete(`/employees/${employeeId}/leaves/${leaveId}`)
}

// ── Dashboard + reports + capacity ────────────────────────────────────────
export const dashboardApi = {
  summary: () => api.get('/dashboard')
}

export const reportsApi = {
  bookingsTimeSeries: (params = {}) => api.get('/reports/bookings-timeseries', { params }),
  revenueTimeSeries: (params = {}) => api.get('/reports/revenue-timeseries', { params }),
  popularServices: (params = {}) => api.get('/reports/popular-services', { params }),
  employeeWorkload: (params = {}) => api.get('/reports/employee-workload', { params }),
  topCustomers: (params = {}) => api.get('/reports/top-customers', { params })
}

export const capacityApi = {
  summary: (date) => api.get(`/capacity/${date}/summary`),
  timeline: (date) => api.get(`/capacity/${date}/timeline`),
  bookingsAtInterval: (date, time) => api.get(`/capacity/${date}/bookings`, { params: { time } }),
  analytics: (date) => api.get(`/capacity/${date}/analytics`)
}

// ── Notifications ─────────────────────────────────────────────────────────
export const notificationsApi = {
  logs: (params = {}) => api.get('/notification-logs', { params }),
  unreadCount: () => api.get('/notification-logs/unread-count'),
  markAllRead: () => api.post('/notification-logs/mark-read'),
  markRead: (id) => api.post(`/notification-logs/${id}/read`),
  log: (id) => api.get(`/notification-logs/${id}`),
  resend: (id) => api.post(`/notification-logs/${id}/resend`),
  templates: () => api.get('/notification-templates'),
  template: (id) => api.get(`/notification-templates/${id}`),
  updateTemplate: (id, payload) => api.put(`/notification-templates/${id}`, payload)
}

// ── Roles / permissions / users ───────────────────────────────────────────
export const rolesApi = {
  list: () => api.get('/roles'),
  get: (id) => api.get(`/roles/${id}`),
  create: (payload) => api.post('/roles', payload),
  update: (id, payload) => api.put(`/roles/${id}`, payload),
  remove: (id) => api.delete(`/roles/${id}`)
}

export const usersApi = {
  list: () => api.get('/users'),
  get: (id) => api.get(`/users/${id}`),
  assignRole: (id, role) => api.patch(`/users/${id}/role`, { role })
}

export const permissionsApi = {
  list: () => api.get('/permissions')
}
