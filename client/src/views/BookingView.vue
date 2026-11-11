<template>
  <div class="booking-page">
    <section class="page-hero">
      <div class="page-hero__bg">
        <img :src="heroImg" alt="Book appointment" class="page-hero__img" />
        <div class="page-hero__overlay"></div>
      </div>
      <div class="page-hero__content">
        <span class="section-label" style="color:var(--pink-200)">Online Booking</span>
        <h1 class="page-hero__title">Book Your Appointment</h1>
        <p class="page-hero__sub">Quick, easy, done in under 60 seconds.</p>
      </div>
    </section>

    <section class="section booking-section">
      <div class="section-inner booking-inner">

        <!-- Step indicator -->
        <div class="steps" role="list" aria-label="Booking steps">
          <div
            v-for="(step, i) in steps" :key="step.key"
            class="step"
            :class="{ 'is-active': currentIndex === i, 'is-done': currentIndex > i }"
            role="listitem"
          >
            <div class="step__bubble">
              <Check v-if="currentIndex > i" :size="14" aria-hidden="true" />
              <span v-else>{{ i+1 }}</span>
            </div>
            <span class="step__label">{{ step.label }}</span>
            <div v-if="i < steps.length - 1" class="step__line"></div>
          </div>
        </div>

        <!-- Selected service banner – visible once a service is chosen -->
        <div v-if="selectedService && activeKey !== 'category' && activeKey !== 'service' && !confirmed" class="ssb">
          <img :src="selectedService.image" :alt="selectedService.name" class="ssb__img" />
          <div class="ssb__info">
            <p class="ssb__eyebrow">Selected Service</p>
            <h3 class="ssb__name">{{ selectedService.name }}</h3>
            <div class="ssb__meta">
              <span class="ssb__price">{{ formatCurrency(totalPrice) }}</span>
              <span aria-hidden="true">·</span>
              <Clock :size="13" aria-hidden="true" />
              <span>{{ formatDuration(totalDuration) }}</span>
            </div>
            <p class="ssb__desc">{{ selectedService.description }}</p>
          </div>
          <button class="ssb__change" @click="restart" aria-label="Change service">
            <X :size="14" aria-hidden="true" /> Change
          </button>
        </div>

        <!-- Step content -->
        <Transition name="step-slide" mode="out-in">

          <!-- Step: choose category -->
          <div v-if="activeKey === 'category'" key="category" class="booking-card">
            <h2 class="booking-card__title">Choose a Category</h2>
            <p class="booking-card__sub">What kind of service are you looking for?</p>

            <div v-if="catalogLoading" class="service-options">
              <span v-for="n in 4" :key="'csk'+n" class="service-option skeleton" style="height:52px;border:none;"></span>
            </div>
            <p v-else-if="!categories.length" class="state-note">No services are available for booking yet.</p>
            <div v-else class="service-options">
              <label
                v-for="c in categories" :key="c.id"
                class="service-option"
                :class="{ 'is-selected': booking.category === c.id }"
              >
                <input type="radio" name="category" :value="c.id" v-model="booking.category" class="sr-only" @change="onCategoryChange" />
                <Sparkles :size="22" class="service-option__icon" aria-hidden="true" />
                <span class="service-option__name">{{ c.name }}</span>
                <CheckCircle2 v-if="booking.category === c.id" :size="16" class="service-option__check" aria-hidden="true" />
              </label>
            </div>

            <div class="booking-card__footer">
              <span></span>
              <button class="btn btn-primary" :disabled="!booking.category" @click="next">
                Next: Choose Service <ArrowRight :size="16" aria-hidden="true" />
              </button>
            </div>
          </div>

          <!-- Step: choose service -->
          <div v-else-if="activeKey === 'service'" key="service" class="booking-card">
            <h2 class="booking-card__title">Select a Service</h2>
            <p class="booking-card__sub">Choose from our {{ selectedCategory?.name }} services.</p>
            <div class="service-options">
              <label
                v-for="s in servicesInCategory" :key="s.id"
                class="service-option"
                :class="{ 'is-selected': booking.service === s.id }"
              >
                <input type="radio" name="service" :value="s.id" v-model="booking.service" class="sr-only" @change="onServiceChange" />
                <component :is="s.icon" :size="22" class="service-option__icon" aria-hidden="true" />
                <span class="service-option__name">{{ s.name }}</span>
                <span class="service-option__price">{{ s.price }}</span>
                <CheckCircle2 v-if="booking.service === s.id" :size="16" class="service-option__check" aria-hidden="true" />
              </label>
            </div>
            <p v-if="!servicesInCategory.length" class="state-note">No services in this category yet.</p>
            <div class="booking-card__footer">
              <button class="btn btn-outline" @click="prev"><ChevronLeft :size="16" aria-hidden="true" /> Back</button>
              <button class="btn btn-primary" :disabled="!booking.service || configLoading" @click="next">
                {{ configLoading ? 'Loading…' : 'Next' }} <ArrowRight :size="16" aria-hidden="true" />
              </button>
            </div>
          </div>

          <!-- Step: choose variations -->
          <div v-else-if="activeKey === 'variations'" key="variations" class="booking-card">
            <h2 class="booking-card__title">Choose Your Options</h2>
            <p class="booking-card__sub">Customise your {{ selectedService?.name }}.</p>

            <div v-for="type in variationTypes" :key="type.id" class="var-group">
              <p class="var-group__label">{{ type.name }}</p>
              <div class="service-options">
                <label
                  v-for="opt in type.options" :key="opt.id"
                  class="service-option"
                  :class="{ 'is-selected': booking.variationSelections[type.id] === opt.id }"
                >
                  <input type="radio" :name="`vt-${type.id}`" :value="opt.id" v-model="booking.variationSelections[type.id]" class="sr-only" />
                  <span class="service-option__name">{{ opt.name }}</span>
                  <span class="service-option__price">{{ adjustmentLabel(opt.id) }}</span>
                  <CheckCircle2 v-if="booking.variationSelections[type.id] === opt.id" :size="16" class="service-option__check" aria-hidden="true" />
                </label>
              </div>
            </div>

            <div class="booking-card__footer">
              <button class="btn btn-outline" @click="prev"><ChevronLeft :size="16" aria-hidden="true" /> Back</button>
              <button class="btn btn-primary" :disabled="!allVariationsChosen" @click="next">
                Next <ArrowRight :size="16" aria-hidden="true" />
              </button>
            </div>
          </div>

          <!-- Step: choose add-ons -->
          <div v-else-if="activeKey === 'addons'" key="addons" class="booking-card">
            <h2 class="booking-card__title">Add-Ons</h2>
            <p class="booking-card__sub">Enhance your visit — optional extras.</p>
            <div class="service-options">
              <label
                v-for="a in serviceAddOns" :key="a.id"
                class="service-option"
                :class="{ 'is-selected': booking.addOnIds.includes(a.id) }"
              >
                <input type="checkbox" :value="a.id" v-model="booking.addOnIds" class="sr-only" />
                <span class="service-option__name">{{ a.name }}</span>
                <span class="service-option__price">+{{ formatCurrency(a.price_modifier) }}</span>
                <CheckCircle2 v-if="booking.addOnIds.includes(a.id)" :size="16" class="service-option__check" aria-hidden="true" />
              </label>
            </div>
            <div class="booking-card__footer">
              <button class="btn btn-outline" @click="prev"><ChevronLeft :size="16" aria-hidden="true" /> Back</button>
              <button class="btn btn-primary" @click="next">
                Next: Pick a Date <ArrowRight :size="16" aria-hidden="true" />
              </button>
            </div>
          </div>

          <!-- Step: choose date (calendar) -->
          <div v-else-if="activeKey === 'date'" key="date" class="booking-card">
            <h2 class="booking-card__title">Select a Date</h2>
            <p class="booking-card__sub">
              {{ booking.date ? `Selected: ${selectedDateLabel}` : 'Pick any available day below.' }}
            </p>

            <div class="cal-header">
              <button class="cal-nav" @click="prevMonth" :disabled="!canGoPrev" aria-label="Previous month">
                <ChevronLeft :size="18" />
              </button>
              <span class="cal-month-label">{{ calMonthLabel }}</span>
              <button class="cal-nav" @click="nextMonth" :disabled="!canGoNext" aria-label="Next month">
                <ChevronRight :size="18" />
              </button>
            </div>

            <div class="cal-dow-row" aria-hidden="true">
              <span v-for="d in dowLabels" :key="d" class="cal-dow">{{ d }}</span>
            </div>

            <div class="cal-grid" role="grid" :aria-label="calMonthLabel">
              <div v-for="n in calStartOffset" :key="'e'+n" class="cal-cell cal-cell--empty"></div>
              <button
                v-for="day in calDays" :key="day.iso"
                class="cal-cell"
                :class="{
                  'is-selected':  booking.date === day.iso,
                  'is-today':     day.isToday,
                  'is-disabled':  day.disabled,
                  'is-sunday':    day.isSunday,
                }"
                :disabled="day.disabled"
                @click="booking.date = day.iso"
                role="gridcell"
                :aria-label="day.label"
                :aria-pressed="String(booking.date === day.iso)"
                :aria-disabled="String(day.disabled)"
              >{{ day.num }}</button>
            </div>

            <p class="cal-legend">
              <span class="cal-legend__dot cal-legend__dot--closed"></span> Closed (Sunday)
              <span class="cal-legend__dot cal-legend__dot--past" style="margin-left:0.75rem"></span> Unavailable
            </p>

            <div class="booking-card__footer">
              <button class="btn btn-outline" @click="prev"><ChevronLeft :size="16" aria-hidden="true" /> Back</button>
              <button class="btn btn-primary" :disabled="!booking.date" @click="next">
                Next: Pick a Time <ArrowRight :size="16" aria-hidden="true" />
              </button>
            </div>
          </div>

          <!-- Step: choose time -->
          <div v-else-if="activeKey === 'time'" key="time" class="booking-card">
            <h2 class="booking-card__title">Select a Time</h2>
            <p class="booking-card__sub">Available slots for {{ selectedDateLabel }}</p>

            <div v-if="slotsLoading" class="time-grid">
              <span v-for="n in 12" :key="'tsk'+n" class="time-btn skeleton" style="border:none;height:40px;"></span>
            </div>
            <p v-else-if="slotsError" class="state-note state-note--error">{{ slotsError }}</p>
            <p v-else-if="!timeSlots.length" class="state-note">No availability on this day. Please pick another date.</p>
            <div v-else class="time-grid">
              <button
                v-for="slot in timeSlots" :key="slot.value"
                class="time-btn"
                :class="{ 'is-selected': booking.time === slot.value }"
                @click="booking.time = slot.value"
              >{{ slot.label }}</button>
            </div>

            <div class="booking-card__footer">
              <button class="btn btn-outline" @click="prev"><ChevronLeft :size="16" aria-hidden="true" /> Back</button>
              <button class="btn btn-primary" :disabled="!booking.time" @click="next">
                Next: Your Details <ArrowRight :size="16" aria-hidden="true" />
              </button>
            </div>
          </div>

          <!-- Step: contact details -->
          <div v-else-if="activeKey === 'details'" key="details" class="booking-card">
            <h2 class="booking-card__title">Your Details</h2>
            <p class="booking-card__sub">Almost there! We'll email your confirmation and reminders.</p>

            <div v-if="error" class="booking-error">{{ error }}</div>

            <div class="booking-form">
              <div class="booking-form__field">
                <label for="bk-name" class="booking-form__label">Full Name</label>
                <div class="booking-form__input-wrap">
                  <User :size="16" class="booking-form__input-icon" aria-hidden="true" />
                  <input
                    id="bk-name" v-model="booking.name" type="text"
                    placeholder="Abel Tewodros"
                    class="booking-form__input" autocomplete="name" required
                  />
                </div>
              </div>
              <div class="booking-form__field">
                <label for="bk-email" class="booking-form__label">Email Address</label>
                <div class="booking-form__input-wrap">
                  <Mail :size="16" class="booking-form__input-icon" aria-hidden="true" />
                  <input
                    id="bk-email" v-model="booking.email" type="email"
                    placeholder="you@example.com"
                    class="booking-form__input" autocomplete="email" required
                  />
                </div>
              </div>
              <div class="booking-form__field">
                <label for="bk-phone" class="booking-form__label">
                  Phone Number <span class="optional">(optional)</span>
                </label>
                <div class="booking-form__input-wrap">
                  <Phone :size="16" class="booking-form__input-icon" aria-hidden="true" />
                  <input
                    id="bk-phone" v-model="booking.phone" type="tel"
                    placeholder="+1 (240) 462-7931"
                    class="booking-form__input" autocomplete="tel"
                  />
                </div>
              </div>
              <div class="booking-form__field">
                <label for="bk-notes" class="booking-form__label">
                  Special Requests <span class="optional">(optional)</span>
                </label>
                <textarea
                  id="bk-notes" v-model="booking.notes"
                  placeholder="Any specific style, allergies, or notes..."
                  class="booking-form__input booking-form__textarea" rows="3"
                ></textarea>
              </div>
            </div>
            <div class="booking-card__footer">
              <button class="btn btn-outline" @click="prev"><ChevronLeft :size="16" aria-hidden="true" /> Back</button>
              <button class="btn btn-primary" :disabled="!booking.name || !validEmail" @click="next">
                Review Booking <ArrowRight :size="16" aria-hidden="true" />
              </button>
            </div>
          </div>

          <!-- Step: review + confirm -->
          <div v-else-if="activeKey === 'review'" key="review" class="booking-card">
            <div v-if="!confirmed">
              <h2 class="booking-card__title">Review Your Booking</h2>
              <p class="booking-card__sub">Please review your details before confirming.</p>

              <div v-if="error" class="booking-error">{{ error }}</div>

              <div class="summary">
                <div class="summary__row" v-for="row in summaryRows" :key="row.label">
                  <span class="summary__label">{{ row.label }}</span>
                  <span class="summary__value">{{ row.value }}</span>
                </div>
              </div>
              <div class="booking-card__footer">
                <button class="btn btn-outline" @click="prev" :disabled="submitting">
                  <ChevronLeft :size="16" aria-hidden="true" /> Edit
                </button>
                <button class="btn btn-primary" @click="confirmBooking" :disabled="submitting">
                  <Heart :size="16" aria-hidden="true" />
                  {{ submitting ? 'Booking...' : 'Confirm Booking' }}
                </button>
              </div>
            </div>
            <div v-else class="booking-success">
              <div class="booking-success__icon" aria-hidden="true">
                <CheckCircle2 :size="56" />
              </div>
              <h2 class="booking-success__title">You're all set!</h2>
              <p class="booking-success__text">
                Your appointment is <strong>confirmed</strong> for
                <strong>{{ selectedDateLabel }}</strong> at <strong>{{ formatTime12(booking.time) }}</strong>.
                A confirmation has been sent to you.
              </p>
              <RouterLink to="/" class="btn btn-primary">
                <Home :size="16" aria-hidden="true" /> Back to Home
              </RouterLink>
            </div>
          </div>

        </Transition>
      </div>
    </section>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, watch } from 'vue'
import { RouterLink, useRoute } from 'vue-router'
import {
  Check, CheckCircle2, ArrowRight, ChevronLeft, ChevronRight,
  User, Phone, Mail, Heart, Home, Clock, X, Sparkles, Scissors
} from 'lucide-vue-next'
import { servicesApi, categoriesApi } from '@/services/catalog'
import { publicBookingApi } from '@/services/booking'
import { formatCurrency, formatDuration, formatTime12 } from '@/utils/format'
import { apiErrorMessage } from '@/services/api'
import { PAGE_HERO_IMAGE, FALLBACK_SERVICE_IMAGE } from '@/utils/stockImages'

const route = useRoute()
const heroImg = PAGE_HERO_IMAGE

const confirmed = ref(false)
const submitting = ref(false)
const error = ref(null)

// ── Catalog data ──────────────────────────────────────────
const categories = ref([])
const allServices = ref([])
const catalogLoading = ref(true)

// ── Per-service config (variations + add-ons) ─────────────
const variationTypes = ref([])          // [{ id, name, options: [{id,name}] }]
const serviceVarOptMap = ref({})        // variation_option_id -> { price, duration }
const serviceAddOns = ref([])           // [{ id, name, price_modifier, duration_modifier_minutes }]
const configLoading = ref(false)

const booking = ref({
  category: null,
  service: null,
  variationSelections: {},   // { [variationTypeId]: variationOptionId }
  addOnIds: [],
  date: null,
  time: null,
  name: '', phone: '', email: '', notes: ''
})

// ── Dynamic steps (variations/add-ons only appear when relevant) ──
const hasVariations = computed(() => variationTypes.value.length > 0)
const hasAddOns = computed(() => serviceAddOns.value.length > 0)

const steps = computed(() => {
  const s = [
    { key: 'category', label: 'Category' },
    { key: 'service', label: 'Service' }
  ]
  if (hasVariations.value) s.push({ key: 'variations', label: 'Options' })
  if (hasAddOns.value) s.push({ key: 'addons', label: 'Add-ons' })
  s.push({ key: 'date', label: 'Date' })
  s.push({ key: 'time', label: 'Time' })
  s.push({ key: 'details', label: 'Details' })
  s.push({ key: 'review', label: 'Review' })
  return s
})

const currentIndex = ref(0)
const activeKey = computed(() => steps.value[currentIndex.value]?.key ?? 'category')

function next() { if (currentIndex.value < steps.value.length - 1) currentIndex.value++ }
function prev() { if (currentIndex.value > 0) currentIndex.value-- }

function restart() {
  booking.value = { category: null, service: null, variationSelections: {}, addOnIds: [], date: null, time: null, name: '', phone: '', email: '', notes: '' }
  variationTypes.value = []
  serviceAddOns.value = []
  serviceVarOptMap.value = {}
  currentIndex.value = 0
}

// ── Derived selections ────────────────────────────────────
const selectedCategory = computed(() => categories.value.find((c) => c.id === booking.value.category) ?? null)
const selectedService = computed(() => allServices.value.find((s) => s.id === booking.value.service) ?? null)
const validEmail = computed(() => /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(booking.value.email || ''))
const servicesInCategory = computed(() => allServices.value.filter((s) => s.category_id === booking.value.category))

function onCategoryChange() {
  booking.value.service = null
  variationTypes.value = []
  serviceAddOns.value = []
  serviceVarOptMap.value = {}
}

async function onServiceChange() {
  booking.value.variationSelections = {}
  booking.value.addOnIds = []
  await loadServiceConfig(booking.value.service)
}

async function loadServiceConfig(serviceId) {
  if (!serviceId) return
  configLoading.value = true
  try {
    const [typesRes, optsRes, addOnsRes] = await Promise.all([
      servicesApi.variationTypes(serviceId),
      servicesApi.variationOptions(serviceId),
      servicesApi.addOns(serviceId)
    ])

    // Per-service price/duration adjustment for each variation option.
    const map = {}
    for (const svo of optsRes.data.data || []) {
      if (svo.variation_option) {
        map[svo.variation_option.id] = {
          price: parseFloat(svo.price_adjustment) || 0,
          duration: svo.duration_adjustment_minutes || 0
        }
      }
    }
    serviceVarOptMap.value = map

    // Only show variation types that have configured options for this service.
    variationTypes.value = (typesRes.data.data || [])
      .map((t) => ({
        id: t.id,
        name: t.name,
        options: (t.options || []).filter((o) => o.is_active && map[o.id] !== undefined)
      }))
      .filter((t) => t.options.length > 0)

    serviceAddOns.value = (addOnsRes.data.data || []).filter((a) => a.is_active)
  } catch {
    variationTypes.value = []
    serviceAddOns.value = []
    serviceVarOptMap.value = {}
  } finally {
    configLoading.value = false
  }
}

const allVariationsChosen = computed(() =>
  variationTypes.value.every((t) => booking.value.variationSelections[t.id] != null)
)

function adjustmentLabel(optionId) {
  const adj = serviceVarOptMap.value[optionId]
  if (!adj || adj.price === 0) return 'Included'
  return `+${formatCurrency(adj.price)}`
}

// ── Local pricing (authoritative total re-confirmed by the API on submit) ──
const totalPrice = computed(() => {
  if (!selectedService.value) return 0
  let total = parseFloat(selectedService.value.base_price) || 0
  for (const optId of Object.values(booking.value.variationSelections)) {
    total += serviceVarOptMap.value[optId]?.price || 0
  }
  for (const id of booking.value.addOnIds) {
    total += parseFloat(serviceAddOns.value.find((a) => a.id === id)?.price_modifier) || 0
  }
  return total
})

const totalDuration = computed(() => {
  if (!selectedService.value) return 0
  let total = selectedService.value.base_duration_minutes || 0
  for (const optId of Object.values(booking.value.variationSelections)) {
    total += serviceVarOptMap.value[optId]?.duration || 0
  }
  for (const id of booking.value.addOnIds) {
    total += serviceAddOns.value.find((a) => a.id === id)?.duration_modifier_minutes || 0
  }
  return total
})

// ── Load catalog + honour ?service= deep link ─────────────
onMounted(async () => {
  try {
    const [catsRes, svcRes] = await Promise.all([categoriesApi.list(), servicesApi.list()])
    categories.value = (catsRes.data.data || []).filter((c) => c.is_active)
    allServices.value = (svcRes.data.data || [])
      .filter((s) => s.is_active)
      .map((s) => ({
        ...s,
        icon: Scissors,
        image: s.image_url || FALLBACK_SERVICE_IMAGE,
        price: formatCurrency(s.base_price),
        duration: `${s.base_duration_minutes} min`
      }))
  } catch {
    error.value = 'Failed to load services. Please refresh the page.'
  } finally {
    catalogLoading.value = false
  }

  const qid = parseInt(route.query.service)
  const svc = allServices.value.find((s) => s.id === qid)
  if (svc) {
    booking.value.category = svc.category_id
    booking.value.service = svc.id
    await loadServiceConfig(svc.id)
    currentIndex.value = steps.value.findIndex((s) => s.key === (hasVariations.value ? 'variations' : hasAddOns.value ? 'addons' : 'date'))
    if (currentIndex.value < 0) currentIndex.value = 2
  }
})

// ── Calendar logic ─────────────────────────────────────────
const MONTH_NAMES = ['January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December']
const dowLabels = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat']

const today = new Date()
today.setHours(0, 0, 0, 0)
const maxDate = new Date(today)
maxDate.setMonth(maxDate.getMonth() + 3)

const calYear = ref(today.getFullYear())
const calMonth = ref(today.getMonth())

const calMonthLabel = computed(() => `${MONTH_NAMES[calMonth.value]} ${calYear.value}`)
const calStartOffset = computed(() => new Date(calYear.value, calMonth.value, 1).getDay())
const calDaysInMonth = computed(() => new Date(calYear.value, calMonth.value + 1, 0).getDate())

const canGoPrev = computed(() =>
  calYear.value > today.getFullYear() || (calYear.value === today.getFullYear() && calMonth.value > today.getMonth())
)
const canGoNext = computed(() =>
  calYear.value < maxDate.getFullYear() || (calYear.value === maxDate.getFullYear() && calMonth.value < maxDate.getMonth())
)

function prevMonth() {
  if (!canGoPrev.value) return
  if (calMonth.value === 0) { calMonth.value = 11; calYear.value-- } else calMonth.value--
  booking.value.date = null
}
function nextMonth() {
  if (!canGoNext.value) return
  if (calMonth.value === 11) { calMonth.value = 0; calYear.value++ } else calMonth.value++
  booking.value.date = null
}

const calDays = computed(() => {
  const days = []
  for (let n = 1; n <= calDaysInMonth.value; n++) {
    const d = new Date(calYear.value, calMonth.value, n)
    const mm = String(calMonth.value + 1).padStart(2, '0')
    const dd = String(n).padStart(2, '0')
    const iso = `${calYear.value}-${mm}-${dd}`
    const isSunday = d.getDay() === 0
    const isPast = d < today
    const isFuture = d > maxDate
    days.push({
      num: n, iso,
      isToday: d.getTime() === today.getTime(),
      isSunday,
      disabled: isPast || isSunday || isFuture,
      label: d.toLocaleDateString('en-US', { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' })
    })
  }
  return days
})

// ── Availability (real slots from the booking engine) ─────
const timeSlots = ref([])
const slotsLoading = ref(false)
const slotsError = ref(null)

function selectionPayload() {
  return [{
    service_id: booking.value.service,
    variation_option_ids: Object.values(booking.value.variationSelections),
    add_on_ids: booking.value.addOnIds
  }]
}

async function loadSlots() {
  if (!booking.value.date || !booking.value.service) return
  slotsLoading.value = true
  slotsError.value = null
  booking.value.time = null
  try {
    const res = await publicBookingApi.checkAvailability({
      booking_date: booking.value.date,
      services: selectionPayload()
    })
    timeSlots.value = (res.data.data.slots || []).map((slot) => ({
      value: slot.start_time,
      label: formatTime12(slot.start_time)
    }))
  } catch (e) {
    slotsError.value = apiErrorMessage(e, 'Could not load available times.')
    timeSlots.value = []
  } finally {
    slotsLoading.value = false
  }
}

// Reload slots whenever we land on the time step (date/selection may have changed).
watch(activeKey, (key) => { if (key === 'time') loadSlots() })
watch(() => booking.value.date, () => { if (activeKey.value === 'time') loadSlots() })

const selectedDateLabel = computed(() => {
  if (!booking.value.date) return '—'
  const d = new Date(booking.value.date + 'T00:00:00')
  return d.toLocaleDateString('en-US', { weekday: 'short', month: 'short', day: 'numeric', year: 'numeric' })
})

const summaryRows = computed(() => {
  const rows = [
    { label: 'Service', value: selectedService.value?.name ?? '—' }
  ]
  for (const t of variationTypes.value) {
    const optId = booking.value.variationSelections[t.id]
    const opt = t.options.find((o) => o.id === optId)
    if (opt) rows.push({ label: t.name, value: opt.name })
  }
  if (booking.value.addOnIds.length) {
    const names = booking.value.addOnIds.map((id) => serviceAddOns.value.find((a) => a.id === id)?.name).filter(Boolean)
    rows.push({ label: 'Add-ons', value: names.join(', ') })
  }
  rows.push({ label: 'Date', value: selectedDateLabel.value })
  rows.push({ label: 'Time', value: formatTime12(booking.value.time) })
  rows.push({ label: 'Duration', value: formatDuration(totalDuration.value) })
  rows.push({ label: 'Total', value: formatCurrency(totalPrice.value) })
  rows.push({ label: 'Name', value: booking.value.name })
  rows.push({ label: 'Email', value: booking.value.email })
  if (booking.value.phone) rows.push({ label: 'Phone', value: booking.value.phone })
  if (booking.value.notes) rows.push({ label: 'Notes', value: booking.value.notes })
  return rows
})

async function confirmBooking() {
  submitting.value = true
  error.value = null
  try {
    await publicBookingApi.create({
      customer: { name: booking.value.name, email: booking.value.email, phone: booking.value.phone || undefined },
      booking_date: booking.value.date,
      start_time: booking.value.time,
      services: selectionPayload(),
      notes: booking.value.notes || undefined
    })
    confirmed.value = true
  } catch (e) {
    error.value = apiErrorMessage(e, 'Failed to create booking. Please try again.')
  } finally {
    submitting.value = false
  }
}
</script>

<style scoped>
.booking-section { background: var(--gray-light); }
.booking-inner   { max-width: 700px; }

/* ── Steps ── */
.steps {
  display: flex; align-items: center; justify-content: center;
  margin-bottom: 32px; overflow-x: auto;
  padding-bottom: 28px; scrollbar-width: none;
}
.steps::-webkit-scrollbar { display: none; }

.step { display: flex; align-items: center; position: relative; padding-bottom: 24px; flex-shrink: 0; }

.step__bubble {
  width: 34px; height: 34px; border-radius: 50%;
  background: var(--white); border: 2px solid var(--border);
  display: flex; align-items: center; justify-content: center;
  font-size: 0.82rem; font-weight: 700; color: var(--gray);
  transition: var(--transition); flex-shrink: 0; z-index: 1;
}
.step.is-active .step__bubble {
  background: var(--primary); border-color: var(--primary);
  color: var(--white); box-shadow: 0 4px 14px var(--primary-shadow);
}
.step.is-done .step__bubble { background: var(--charcoal); border-color: var(--charcoal); color: var(--white); }

.step__label {
  font-size: 0.67rem; font-weight: 600; letter-spacing: 0.05em;
  color: var(--gray); position: absolute; top: 40px;
  white-space: nowrap; left: 50%; transform: translateX(-50%);
}
.step__line { width: 52px; height: 2px; background: var(--border); margin: 0 3px; transition: background var(--transition); }
.step.is-done .step__line { background: var(--charcoal); }

/* ── Selected service banner ── */
.ssb {
  display: flex; align-items: center; gap: 1rem;
  background: var(--white); border: 1.5px solid var(--primary);
  border-radius: var(--radius-md); padding: 1rem 1.25rem;
  margin-bottom: 1.5rem; box-shadow: var(--shadow-sm);
  flex-wrap: wrap;
}
.ssb__img {
  width: 72px; height: 72px; border-radius: var(--radius-sm);
  object-fit: cover; object-position: center top; flex-shrink: 0;
}
.ssb__info { flex: 1; min-width: 0; }
.ssb__eyebrow {
  font-size: 0.65rem; font-weight: 700; letter-spacing: 0.15em;
  text-transform: uppercase; color: var(--primary); margin-bottom: 0.15rem;
}
.ssb__name {
  font-family: var(--font-heading); font-size: 1.05rem; font-weight: 700;
  color: var(--charcoal); margin-bottom: 0.25rem;
}
.ssb__meta {
  display: flex; align-items: center; gap: 0.4rem;
  font-size: 0.82rem; color: var(--charcoal-lt); flex-wrap: wrap;
}
.ssb__price { font-weight: 700; color: var(--primary); }
.ssb__meta svg { color: var(--gray); flex-shrink: 0; }
.ssb__desc { font-size: 0.8rem; color: var(--gray); margin-top: 0.3rem; line-height: 1.5; }
.ssb__change {
  display: flex; align-items: center; gap: 0.3rem;
  font-family: var(--font-body); font-size: 0.78rem; font-weight: 600;
  color: var(--charcoal-lt); background: var(--gray-light);
  border: 1px solid var(--border); border-radius: var(--radius-full);
  padding: 0.35rem 0.85rem; cursor: pointer; transition: var(--transition);
  flex-shrink: 0; white-space: nowrap;
}
.ssb__change:hover { border-color: var(--primary); color: var(--primary); }

/* ── Booking card ── */
.booking-card {
  background: var(--white); border-radius: var(--radius-lg);
  padding: 2.25rem; box-shadow: var(--shadow-md); border: 1px solid var(--border);
}
.booking-card__title { font-family: var(--font-heading); font-size: 1.5rem; font-weight: 700; color: var(--charcoal); margin-bottom: 0.3rem; }
.booking-card__sub   { font-size: 0.88rem; color: var(--charcoal-lt); margin-bottom: 1.5rem; }
.booking-card__footer {
  display: flex; align-items: center; justify-content: space-between;
  margin-top: 1.75rem; padding-top: 1.35rem;
  border-top: 1px solid var(--border); gap: 0.75rem; flex-wrap: wrap;
}
.booking-card__footer .btn { flex-shrink: 0; }

/* ── Service / option list ── */
.service-options { display: grid; grid-template-columns: repeat(2,1fr); gap: 10px; margin-top: 0.4rem; }
.service-option {
  display: flex; align-items: center; gap: 0.7rem;
  padding: 0.9rem 1rem;
  border: 1.5px solid var(--border); border-radius: var(--radius-md);
  cursor: pointer; transition: var(--transition); position: relative;
}
.service-option:hover { border-color: var(--primary); }
.service-option.is-selected { border-color: var(--primary); background: var(--pink-50); box-shadow: 0 0 0 1px var(--primary); }
.service-option__icon  { color: var(--primary); flex-shrink: 0; }
.service-option__name  { font-weight: 600; font-size: 0.88rem; color: var(--charcoal); flex: 1; min-width: 0; }
.service-option__price { font-size: 0.8rem; color: var(--primary); font-weight: 600; white-space: nowrap; }
.service-option__check { position: absolute; top: 6px; right: 8px; color: var(--primary); }

/* ── Variation groups (same visual language as options) ── */
.var-group { margin-bottom: 1.4rem; }
.var-group__label {
  font-size: 0.75rem; font-weight: 700; letter-spacing: 0.07em;
  text-transform: uppercase; color: var(--gray); margin-bottom: 0.5rem;
}

/* ── Date grid ── */
.date-grid { display: grid; grid-template-columns: repeat(5,1fr); gap: 8px; }
.date-btn {
  display: flex; flex-direction: column; align-items: center;
  padding: 0.85rem 0.4rem;
  border: 1.5px solid var(--border); border-radius: var(--radius-md);
  background: var(--white); cursor: pointer; transition: var(--transition); gap: 0.1rem;
}
.date-btn:hover:not(.is-disabled) { border-color: var(--primary); }
.date-btn.is-selected  { background: var(--primary); border-color: var(--primary); box-shadow: 0 4px 14px var(--primary-shadow); }
.date-btn.is-disabled  { opacity: 0.38; cursor: not-allowed; }
.date-btn__day   { font-size: 0.64rem; font-weight: 700; letter-spacing: 0.05em; text-transform: uppercase; color: var(--gray); }
.date-btn__num   { font-family: var(--font-heading); font-size: 1.3rem; font-weight: 700; color: var(--charcoal); line-height: 1; }
.date-btn__month { font-size: 0.64rem; color: var(--charcoal-lt); }
.date-btn.is-selected .date-btn__day,
.date-btn.is-selected .date-btn__num,
.date-btn.is-selected .date-btn__month { color: var(--white); }

/* ── Time grid ── */
.time-grid { display: grid; grid-template-columns: repeat(4,1fr); gap: 8px; }
.time-btn {
  padding: 0.65rem; border: 1.5px solid var(--border); border-radius: var(--radius-sm);
  background: var(--white); font-family: var(--font-body); font-size: 0.85rem;
  font-weight: 600; color: var(--charcoal); cursor: pointer; transition: var(--transition);
}
.time-btn:hover:not(.is-unavailable) { border-color: var(--primary); color: var(--primary); }
.time-btn.is-selected   { background: var(--primary); border-color: var(--primary); color: var(--white); }
.time-btn.is-unavailable { opacity: 0.3; cursor: not-allowed; text-decoration: line-through; }

/* ── Booking form ── */
.booking-form { display: flex; flex-direction: column; gap: 1.15rem; }
.booking-form__field { display: flex; flex-direction: column; gap: 0.35rem; }
.booking-form__label { font-size: 0.82rem; font-weight: 600; color: var(--charcoal); }
.optional { font-weight: 400; color: var(--gray); }
.booking-form__input-wrap { position: relative; }
.booking-form__input-icon {
  position: absolute; left: 0.85rem; top: 50%; transform: translateY(-50%);
  color: var(--gray); pointer-events: none;
}
.booking-form__input {
  font-family: var(--font-body); font-size: 0.92rem;
  padding: 0.78rem 1rem 0.78rem 2.5rem;
  border: 1.5px solid var(--border); border-radius: var(--radius-sm);
  color: var(--charcoal); background: var(--white);
  transition: border-color var(--transition); outline: none; width: 100%;
}
.booking-form__input:focus { border-color: var(--primary); box-shadow: 0 0 0 3px var(--primary-shadow); }
.booking-form__textarea { padding-left: 1rem; resize: vertical; min-height: 88px; }

/* ── Summary ── */
.summary { background: var(--gray-light); border-radius: var(--radius-md); padding: 1.35rem; margin-top: 0.9rem; display: flex; flex-direction: column; gap: 0.75rem; }
.summary__row { display: flex; justify-content: space-between; align-items: center; padding-bottom: 0.75rem; border-bottom: 1px solid var(--border); gap: 1rem; }
.summary__row:last-child { border-bottom: none; padding-bottom: 0; }
.summary__label { font-size: 0.75rem; font-weight: 700; letter-spacing: 0.07em; text-transform: uppercase; color: var(--gray); white-space: nowrap; }
.summary__value { font-weight: 600; font-size: 0.9rem; color: var(--charcoal); text-align: right; }

/* ── Success ── */
.booking-success { text-align: center; padding: 0.5rem 0; display: flex; flex-direction: column; align-items: center; gap: 1.15rem; }
.booking-success__icon  { color: var(--primary); animation: pop 0.4s cubic-bezier(0.175,0.885,0.32,1.275); }
@keyframes pop { from { transform:scale(0); } to { transform:scale(1); } }
.booking-success__title { font-family: var(--font-heading); font-size: 1.9rem; font-weight: 700; color: var(--charcoal); }
.booking-success__text  { font-size: 0.92rem; color: var(--charcoal-lt); line-height: 1.7; max-width: 380px; }

/* ── Transitions ── */
.step-slide-enter-active, .step-slide-leave-active { transition: opacity 0.2s ease, transform 0.2s ease; }
.step-slide-enter-from { opacity:0; transform:translateX(16px); }
.step-slide-leave-to   { opacity:0; transform:translateX(-16px); }

/* ── sr-only ── */
.sr-only { position:absolute; width:1px; height:1px; padding:0; margin:-1px; overflow:hidden; clip:rect(0,0,0,0); white-space:nowrap; border:0; }

/* ── Calendar ── */
.cal-header {
  display: flex; align-items: center; justify-content: space-between;
  margin-bottom: 1rem;
}
.cal-month-label {
  font-family: var(--font-heading); font-size: 1.05rem; font-weight: 700; color: var(--charcoal);
}
.cal-nav {
  width: 34px; height: 34px; border-radius: 50%;
  background: var(--gray-light); border: 1px solid var(--border);
  display: flex; align-items: center; justify-content: center;
  color: var(--charcoal); cursor: pointer; transition: var(--transition);
}
.cal-nav:hover:not(:disabled) { background: var(--pink-50); border-color: var(--primary); color: var(--primary); }
.cal-nav:disabled { opacity: 0.3; cursor: not-allowed; }

.cal-dow-row {
  display: grid; grid-template-columns: repeat(7,1fr);
  gap: 2px; margin-bottom: 4px;
}
.cal-dow {
  text-align: center; font-size: 0.68rem; font-weight: 700;
  letter-spacing: 0.06em; text-transform: uppercase; color: var(--gray); padding: 0.3rem 0;
}
.cal-grid { display: grid; grid-template-columns: repeat(7,1fr); gap: 4px; }

.cal-cell {
  aspect-ratio: 1; border-radius: var(--radius-sm);
  background: var(--white); border: 1.5px solid var(--border);
  font-family: var(--font-body); font-size: 0.88rem; font-weight: 600;
  color: var(--charcoal); cursor: pointer;
  display: flex; align-items: center; justify-content: center;
  transition: var(--transition);
}
.cal-cell--empty { background: transparent; border-color: transparent; pointer-events: none; }
.cal-cell:hover:not(.is-disabled):not(.cal-cell--empty) {
  border-color: var(--primary); color: var(--primary); background: var(--pink-50);
}
.cal-cell.is-today  { border-color: var(--pink-dark); color: var(--primary); font-weight: 800; }
.cal-cell.is-selected { background: var(--primary); border-color: var(--primary); color: var(--white); box-shadow: 0 4px 12px var(--primary-shadow); }
.cal-cell.is-disabled,
.cal-cell.is-sunday  { opacity: 0.28; cursor: not-allowed; text-decoration: line-through; }

.cal-legend {
  display: flex; align-items: center; gap: 0.4rem; flex-wrap: wrap;
  font-size: 0.72rem; color: var(--gray); margin-top: 0.75rem;
}
.cal-legend__dot { width: 10px; height: 10px; border-radius: 50%; display: inline-block; flex-shrink: 0; }
.cal-legend__dot--closed { background: var(--border); }
.cal-legend__dot--past   { background: var(--gray-light); border: 1px solid var(--border); }

.booking-error {
  background: #fee;
  border: 1px solid #fcc;
  color: #c33;
  padding: 0.75rem 1rem;
  border-radius: var(--radius-sm);
  font-size: 0.85rem;
  margin-bottom: 1rem;
}

/* ── Responsive ── */
@media (max-width: 600px) {
  .booking-card { padding: 1.35rem; }
  .service-options { grid-template-columns: 1fr; }
  .time-grid { grid-template-columns: repeat(3,1fr); }
  .booking-card__footer { flex-direction: column-reverse; }
  .booking-card__footer .btn { width: 100%; justify-content: center; }
  .ssb__img { width: 56px; height: 56px; }
  .cal-cell { font-size: 0.78rem; }
}
@media (max-width: 380px) {
  .time-grid { grid-template-columns: repeat(2,1fr); }
  .cal-cell  { font-size: 0.68rem; }
}
</style>
