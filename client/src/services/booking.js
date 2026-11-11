import api from './api'

/**
 * Public booking flow (no auth). Shapes match CheckAvailabilityRequest and
 * StoreBookingRequest on the backend.
 *
 * A service selection is:
 *   { service_id: number, variation_option_ids?: number[], add_on_ids?: number[] }
 */
export const publicBookingApi = {
  /** @param {{ booking_date: string, services: object[] }} payload */
  checkAvailability: (payload) => api.post('/booking/availability', payload),

  /**
   * @param {{
   *   customer: { name: string, email?: string, phone?: string },
   *   booking_date: string,
   *   start_time: string,
   *   services: object[],
   *   notes?: string
   * }} payload
   */
  create: (payload) => api.post('/booking', payload)
}
