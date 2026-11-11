// Shared formatting helpers.

/**
 * Format a numeric/string price as USD currency.
 * @param {string|number} value
 */
export function formatCurrency(value, currency = 'USD') {
  const n = typeof value === 'string' ? parseFloat(value) : value
  if (n == null || Number.isNaN(n)) return ''
  return new Intl.NumberFormat('en-US', { style: 'currency', currency }).format(n)
}

/**
 * Convert a 24h "HH:MM" or "HH:MM:SS" time to a 12h label like "9:00 AM".
 * @param {string} time
 */
export function formatTime12(time) {
  if (!time) return ''
  const [h, m] = time.split(':').map(Number)
  const period = h >= 12 ? 'PM' : 'AM'
  const hour12 = h % 12 === 0 ? 12 : h % 12
  return m === 0 ? `${hour12}:00 ${period}` : `${hour12}:${String(m).padStart(2, '0')} ${period}`
}

/**
 * Render one BusinessHour row's range, e.g. "9:00 AM – 6:00 PM" or "Closed".
 * @param {{ is_closed: boolean, open_time: string|null, close_time: string|null }} hour
 */
export function formatHourRange(hour) {
  if (!hour || hour.is_closed || !hour.open_time || !hour.close_time) return 'Closed'
  return `${formatTime12(hour.open_time)} – ${formatTime12(hour.close_time)}`
}

/** Minutes → "1h 30m" / "45m" / "2h". */
export function formatDuration(minutes) {
  const m = Number(minutes) || 0
  const h = Math.floor(m / 60)
  const rem = m % 60
  if (h && rem) return `${h}h ${rem}m`
  if (h) return `${h}h`
  return `${rem}m`
}

export const DAY_NAMES = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday']
