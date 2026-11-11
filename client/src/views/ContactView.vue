<template>
  <div class="contact-page">
    <section class="page-hero">
      <div class="page-hero__bg">
        <img :src="heroImg" alt="Contact us" class="page-hero__img" />
        <div class="page-hero__overlay"></div>
      </div>
      <div class="page-hero__content">
        <span class="section-label" style="color:var(--pink-200)">Get in Touch</span>
        <h1 class="page-hero__title">Contact Us</h1>
        <p class="page-hero__sub">We'd love to hear from you. Reach out anytime.</p>
      </div>
    </section>

    <section class="section contact-section">
      <div class="section-inner contact-inner">

        <!-- Info -->
        <div class="contact-info">
          <div>
            <span class="section-label">{{ salonName }}</span>
            <h2 class="contact-info__title">Let's Connect</h2>
            <div class="gold-divider" style="margin: 1rem 0;"></div>
            <p class="contact-info__text">Have a question about services, pricing, or availability? Drop us a message or call — we're always happy to help.</p>
          </div>

          <div class="contact-details">
            <div v-for="d in details" :key="d.label" class="contact-detail">
              <div class="contact-detail__icon-wrap">
                <component :is="d.icon" :size="18" aria-hidden="true" />
              </div>
              <div>
                <p class="contact-detail__label">{{ d.label }}</p>
                <component :is="d.href ? 'a' : 'p'" :href="d.href || undefined" class="contact-detail__value">{{ d.value }}</component>
              </div>
            </div>
          </div>

          <!-- Hours -->
          <div class="hours-card">
            <h3 class="hours-card__title">
              <Clock :size="16" aria-hidden="true" /> Working Hours
            </h3>
            <ul class="hours-list">
              <li v-for="h in hours" :key="h.day" class="hours-item">
                <span class="hours-item__day">{{ h.day }}</span>
                <span class="hours-item__time" :class="{ 'is-closed': h.closed }">
                  {{ h.closed ? 'Closed' : h.time }}
                </span>
              </li>
            </ul>
          </div>

          <div class="contact-socials">
            <a v-if="instagram" :href="instagramUrl" class="contact-social-btn" aria-label="Instagram" target="_blank" rel="noopener noreferrer">
              <Instagram :size="18" aria-hidden="true" /> Instagram
            </a>
            <a v-if="tiktok" :href="tiktokUrl" class="contact-social-btn" aria-label="TikTok" target="_blank" rel="noopener noreferrer">
              <TikTokIcon :size="18" /> TikTok
            </a>
          </div>
        </div>

        <!-- Form -->
        <div class="contact-form-wrap">
          <form class="contact-form" @submit.prevent="submitForm" novalidate>
            <h3 class="contact-form__title">Send a Message</h3>
            <div class="contact-form__fields">
              <div class="form-field">
                <label for="cf-name" class="form-label">Your Name</label>
                <input id="cf-name" v-model="form.name" type="text" class="form-input" placeholder="Abel Tewodros" autocomplete="name" required />
              </div>
              <div class="form-field">
                <label for="cf-phone" class="form-label">Phone Number</label>
                <input id="cf-phone" v-model="form.phone" type="tel" class="form-input" placeholder="+1 (240) 462-7931" autocomplete="tel" />
              </div>
              <div class="form-field form-field--full">
                <label for="cf-email" class="form-label">Email Address</label>
                <input id="cf-email" v-model="form.email" type="email" class="form-input" placeholder="you@example.com" autocomplete="email" />
              </div>
              <div class="form-field form-field--full">
                <label for="cf-subject" class="form-label">Subject</label>
                <select id="cf-subject" v-model="form.subject" class="form-input form-select">
                  <option value="" disabled>Select a topic...</option>
                  <option>Appointment Inquiry</option>
                  <option>Pricing &amp; Services</option>
                  <option>Group Booking</option>
                  <option>Feedback</option>
                  <option>Other</option>
                </select>
              </div>
              <div class="form-field form-field--full">
                <label for="cf-message" class="form-label">Message</label>
                <textarea id="cf-message" v-model="form.message" class="form-input form-textarea" placeholder="Tell us what's on your mind..." rows="5" required></textarea>
              </div>
            </div>

            <Transition name="fade">
              <div v-if="error" class="form-error" role="alert">
                {{ error }}
              </div>
            </Transition>

            <Transition name="fade">
              <div v-if="submitted" class="form-success" role="alert">
                <CheckCircle2 :size="22" aria-hidden="true" />
                <div>
                  <p class="form-success__title">Message sent!</p>
                  <p class="form-success__sub">We'll get back to you within 24 hours.</p>
                </div>
              </div>
            </Transition>

            <button type="submit" class="btn btn-primary contact-form__submit" :disabled="submitting || !form.name || !form.message">
              <Send :size="16" aria-hidden="true" />
              {{ submitting ? 'Sending...' : (submitted ? 'Message Sent ✓' : 'Send Message') }}
            </button>
          </form>
        </div>

      </div>
    </section>

    <!-- Map placeholder -->
    <section class="map-section" aria-label="Salon location map">
      <div class="map-placeholder">
        <MapPin :size="40" class="map-placeholder__pin" aria-hidden="true" />
        <p class="map-placeholder__text">{{ salonName }}</p>
        <p class="map-placeholder__sub">{{ address || 'Visit us in-salon — we can\'t wait to see you.' }}</p>
      </div>
    </section>
  </div>
</template>

<script setup>
import { ref, computed } from 'vue'
import { Phone, Mail, MapPin, Clock, Instagram, CheckCircle2, Send } from 'lucide-vue-next'
import TikTokIcon from '@/components/icons/TikTokIcon.vue'
import { useSettings } from '@/composables/useSettings'
import { useBusinessHours } from '@/composables/useBusinessHours'
import { formatHourRange, DAY_NAMES } from '@/utils/format'
import { PAGE_HERO_IMAGE } from '@/utils/stockImages'

const heroImg = PAGE_HERO_IMAGE
const submitted = ref(false)
const submitting = ref(false)
const error = ref(null)
const form = ref({ name: '', phone: '', email: '', subject: '', message: '' })

const { salonName, email, phone, address, instagram, tiktok } = useSettings()
const { hours: businessHours } = useBusinessHours()

const instagramUrl = computed(() => (instagram.value ? `https://www.instagram.com/${instagram.value}` : '#'))
const tiktokUrl = computed(() => (tiktok.value ? `https://www.tiktok.com/@${tiktok.value}` : '#'))
const phoneHref = computed(() => (phone.value || '').replace(/[^0-9+]/g, ''))

// No /contact endpoint exists on the backend, so "Send Message" opens the
// visitor's email client pre-filled to the salon inbox — a real action with
// no fabricated API call.
function submitForm() {
  if (!form.value.name || !form.value.message) {
    error.value = 'Please fill in required fields'
    return
  }
  submitting.value = true
  error.value = null

  const subject = `${form.value.subject || 'Website enquiry'} — ${form.value.name}`
  const body = [
    `Name: ${form.value.name}`,
    form.value.phone ? `Phone: ${form.value.phone}` : null,
    form.value.email ? `Email: ${form.value.email}` : null,
    '',
    form.value.message
  ]
    .filter((l) => l !== null)
    .join('\n')

  window.location.href = `mailto:${email.value || ''}?subject=${encodeURIComponent(subject)}&body=${encodeURIComponent(body)}`

  submitted.value = true
  submitting.value = false
  form.value = { name: '', phone: '', email: '', subject: '', message: '' }
  setTimeout(() => { submitted.value = false }, 5000)
}

const details = computed(() =>
  [
    phone.value ? { icon: Phone, label: 'Phone', value: phone.value, href: `tel:${phoneHref.value}` } : null,
    email.value ? { icon: Mail, label: 'Email', value: email.value, href: `mailto:${email.value}` } : null,
    address.value ? { icon: MapPin, label: 'Address', value: address.value, href: null } : null
  ].filter(Boolean)
)

// Map backend business hours to the template's { day, time, closed } shape.
const hours = computed(() =>
  (businessHours.value || []).map((h) => ({
    day: h.day_name || DAY_NAMES[h.day_of_week],
    time: formatHourRange(h),
    closed: h.is_closed || !h.open_time
  }))
)
</script>

<style scoped>
.contact-section { background: var(--gray-light); }
.contact-inner { display: grid; grid-template-columns: 1fr 1.4fr; gap: 44px; align-items: start; }

.contact-info { display: flex; flex-direction: column; gap: 1.85rem; }
.contact-info__title { font-family: var(--font-heading); font-size: clamp(1.5rem,4vw,2rem); font-weight: 700; color: var(--charcoal); margin-top: 0.4rem; }
.contact-info__text { font-size: 0.88rem; color: var(--charcoal-lt); line-height: 1.7; }

.contact-details { display: flex; flex-direction: column; gap: 1rem; }
.contact-detail { display: flex; align-items: flex-start; gap: 0.9rem; }
.contact-detail__icon-wrap {
  width: 40px; height: 40px; border-radius: 50%;
  background: var(--pink-50); border: 1px solid var(--border);
  display: flex; align-items: center; justify-content: center;
  color: var(--primary); flex-shrink: 0;
}
.contact-detail__label { font-size: 0.7rem; font-weight: 700; letter-spacing: 0.12em; text-transform: uppercase; color: var(--gray); margin-bottom: 0.15rem; }
.contact-detail__value { font-size: 0.88rem; font-weight: 600; color: var(--charcoal); text-decoration: none; transition: color var(--transition); }
a.contact-detail__value:hover { color: var(--primary); }

.hours-card { background: var(--white); border: 1px solid var(--border); border-radius: var(--radius-md); padding: 1.4rem; }
.hours-card__title { font-family: var(--font-heading); font-size: 1rem; font-weight: 600; color: var(--charcoal); margin-bottom: 0.9rem; padding-bottom: 0.7rem; border-bottom: 1px solid var(--border); display: flex; align-items: center; gap: 0.4rem; }
.hours-card__title svg { color: var(--primary); }
.hours-list { list-style: none; display: flex; flex-direction: column; gap: 0.5rem; }
.hours-item { display: flex; justify-content: space-between; align-items: center; font-size: 0.85rem; }
.hours-item__day { color: var(--charcoal); font-weight: 500; }
.hours-item__time { color: var(--charcoal-lt); }
.hours-item__time.is-closed { color: var(--primary); font-weight: 600; }

.contact-socials { display: flex; gap: 0.7rem; flex-wrap: wrap; }
.contact-social-btn {
  display: inline-flex; align-items: center; gap: 0.5rem;
  padding: 0.55rem 1.15rem; border-radius: var(--radius-full);
  border: 1.5px solid var(--border); font-size: 0.85rem; font-weight: 600;
  color: var(--charcoal); text-decoration: none; background: var(--white);
  transition: var(--transition);
}
.contact-social-btn:hover { border-color: var(--primary); color: var(--primary); }

.contact-form-wrap { position: sticky; top: calc(var(--navbar-h) + 20px); }
.contact-form { background: var(--white); border-radius: var(--radius-lg); padding: 2.25rem; box-shadow: var(--shadow-md); border: 1px solid var(--border); }
.contact-form__title { font-family: var(--font-heading); font-size: 1.35rem; font-weight: 700; color: var(--charcoal); margin-bottom: 1.5rem; }
.contact-form__fields { display: grid; grid-template-columns: 1fr 1fr; gap: 0.9rem; }
.form-field { display: flex; flex-direction: column; gap: 0.35rem; }
.form-field--full { grid-column: 1 / -1; }
.form-label { font-size: 0.8rem; font-weight: 600; color: var(--charcoal); }
.form-input {
  font-family: var(--font-body); font-size: 0.9rem;
  padding: 0.76rem 1rem; border: 1.5px solid var(--border);
  border-radius: var(--radius-sm); color: var(--charcoal);
  background: var(--white); transition: border-color var(--transition);
  outline: none; width: 100%; appearance: none;
}
.form-input:focus { border-color: var(--primary); box-shadow: 0 0 0 3px var(--primary-shadow); }
.form-select {
  background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 24 24' fill='none' stroke='%23888' stroke-width='2'%3E%3Cpolyline points='6 9 12 15 18 9'/%3E%3C/svg%3E");
  background-repeat: no-repeat; background-position: right 1rem center; padding-right: 2.5rem; cursor: pointer;
}
.form-textarea { resize: vertical; min-height: 110px; }
.form-success { display: flex; align-items: center; gap: 0.85rem; background: var(--pink-50); border: 1px solid var(--pink-dark); border-radius: var(--radius-sm); padding: 0.9rem 1.1rem; margin-bottom: 1.1rem; color: var(--primary); }
.form-success__title { font-weight: 700; font-size: 0.88rem; color: var(--primary); }
.form-success__sub { font-size: 0.8rem; color: var(--pink-deep); }
.form-error { background: #fee; border: 1px solid #fcc; color: #c33; padding: 0.9rem 1.1rem; border-radius: var(--radius-sm); font-size: 0.85rem; margin-bottom: 1.1rem; }
.contact-form__submit { width: 100%; justify-content: center; margin-top: 1.35rem; }

.map-section { height: 340px; background: var(--pink-50); display: flex; align-items: center; justify-content: center; border-top: 1px solid var(--border); }
.map-placeholder { text-align: center; display: flex; flex-direction: column; align-items: center; gap: 0.5rem; }
.map-placeholder__pin { color: var(--primary); }
.map-placeholder__text { font-family: var(--font-heading); font-size: 1.15rem; font-weight: 600; color: var(--charcoal); }
.map-placeholder__sub { font-size: 0.8rem; color: var(--gray); }

.fade-enter-active,.fade-leave-active { transition: opacity 0.22s; }
.fade-enter-from,.fade-leave-to { opacity: 0; }

@media (max-width: 860px) {
  .contact-inner { grid-template-columns: 1fr; }
  .contact-form-wrap { position: static; }
}
@media (max-width: 480px) {
  .contact-form { padding: 1.4rem; }
  .contact-form__fields { grid-template-columns: 1fr; }
}
</style>
