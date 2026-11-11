<template>
  <div class="services-page">
    <section class="page-hero">
      <div class="page-hero__bg">
        <img :src="heroImg" alt="Hair services" class="page-hero__img" />
        <div class="page-hero__overlay"></div>
      </div>
      <div class="page-hero__content">
        <span class="section-label" style="color:var(--pink-200)">What We Offer</span>
        <h1 class="page-hero__title">Our Services</h1>
        <p class="page-hero__sub">Premium hair treatments crafted for every occasion.</p>
      </div>
    </section>

    <!-- Category picker -->
    <section class="section" v-if="!selectedCategory">
      <div class="section-inner">
        <div class="section-header">
          <span class="section-label">The Full Menu</span>
          <h2 class="section-title">Choose a Category</h2>
          <div class="gold-divider"></div>
          <p class="section-subtitle">Tap a category below to see every service inside it and its price.</p>
        </div>

        <div v-if="loading" class="cat-grid">
          <div v-for="n in 5" :key="'csk'+n" class="cat-card">
            <div class="cat-card__img-wrap skeleton"></div>
            <div class="cat-card__body">
              <span class="skeleton" style="height:1rem;width:60%;display:block;margin:0 auto;"></span>
            </div>
          </div>
        </div>

        <div v-else-if="error" class="error-state"><p>{{ error }}</p></div>
        <div v-else-if="!categories.length" class="state-note">Our services will appear here soon.</div>

        <div v-else class="cat-grid">
          <button v-for="c in categories" :key="c.id" class="cat-card" @click="selectCategory(c)">
            <div class="cat-card__img-wrap">
              <img :src="c.image_url || fallbackImg" :alt="c.name" class="cat-card__img" loading="lazy" />
              <div class="cat-card__overlay"></div>
            </div>
            <div class="cat-card__body">
              <h3 class="cat-card__name">{{ c.name }}</h3>
              <span class="cat-card__count">{{ c.services_count }} service{{ c.services_count === 1 ? '' : 's' }}</span>
            </div>
          </button>
        </div>
      </div>
    </section>

    <!-- Services within the selected category -->
    <section class="section" v-else>
      <div class="section-inner">
        <button class="srv-back" @click="clearCategory"><ArrowLeft :size="16" aria-hidden="true" /> All Categories</button>

        <div class="section-header">
          <span class="section-label">{{ selectedCategory.name }}</span>
          <h2 class="section-title">Choose Your Treatment</h2>
          <div class="gold-divider"></div>
          <p class="section-subtitle">Every service is performed by our expert stylists using only premium products.</p>
        </div>

        <div v-if="servicesLoading" class="srv-grid">
          <article v-for="n in 4" :key="'sk'+n" class="srv-card">
            <div class="srv-card__img-wrap skeleton"></div>
            <div class="srv-card__body">
              <span class="skeleton" style="height:1.1rem;width:60%;display:block;"></span>
              <span class="skeleton" style="height:0.85rem;width:100%;display:block;"></span>
              <span class="skeleton" style="height:0.85rem;width:75%;display:block;"></span>
            </div>
          </article>
        </div>

        <div v-else-if="serviceError" class="error-state"><p>{{ serviceError }}</p></div>
        <div v-else-if="!services.length" class="state-note">No services in this category yet.</div>

        <div v-else class="srv-grid">
          <article v-for="s in services" :key="s.id" class="srv-card">
            <div class="srv-card__img-wrap">
              <img :src="s.image" :alt="s.name" class="srv-card__img" loading="lazy" />
              <span v-if="s.badge" class="srv-card__badge">{{ s.badge }}</span>
            </div>
            <div class="srv-card__body">
              <div class="srv-card__top">
                <h3 class="srv-card__name">{{ s.name }}</h3>
                <span class="srv-card__duration">
                  <Clock :size="13" aria-hidden="true" /> {{ s.duration }}
                </span>
              </div>
              <p class="srv-card__desc">{{ s.description }}</p>
              <div class="srv-card__footer">
                <span class="srv-card__price">From <strong>{{ s.price }}</strong></span>
                <RouterLink :to="`/booking?service=${s.id}`" class="btn btn-primary srv-card__btn">
                  <CalendarHeart :size="15" aria-hidden="true" /> Book Now
                </RouterLink>
              </div>
            </div>
          </article>
        </div>
      </div>
    </section>

    <!-- Add-ons -->
    <section class="section addons-section">
      <div class="section-inner">
        <div class="section-header">
          <span class="section-label">Extras</span>
          <h2 class="section-title">Add-Ons</h2>
          <div class="gold-divider"></div>
        </div>
        <div class="addons-grid">
          <div v-for="a in addons" :key="a.name" class="addon-card">
            <component :is="a.icon" :size="22" class="addon-card__icon" aria-hidden="true" />
            <div>
              <p class="addon-card__name">{{ a.name }}</p>
              <p class="addon-card__price">{{ a.price }}</p>
            </div>
          </div>
        </div>
      </div>
    </section>

    <!-- CTA -->
    <section class="section cta-strip">
      <div class="section-inner cta-strip__inner">
        <div>
          <h2 class="cta-strip__title">Not sure what to book?</h2>
          <p class="cta-strip__sub">Message us and we'll help you find the perfect treatment.</p>
        </div>
        <div class="cta-strip__actions">
          <RouterLink to="/booking" class="btn btn-primary">
            <CalendarHeart :size="17" aria-hidden="true" /> Book Appointment
          </RouterLink>
          <RouterLink to="/contact" class="btn btn-outline">
            <MessageCircle :size="17" aria-hidden="true" /> Contact Us
          </RouterLink>
        </div>
      </div>
    </section>

  </div>
</template>

<script setup>
import { ref, computed, onMounted, watch } from 'vue'
import { RouterLink, useRoute, useRouter } from 'vue-router'
import { Clock, CalendarHeart, Gem, Sparkles, Scissors, Droplets, Wind, MessageCircle, ArrowLeft } from 'lucide-vue-next'
import { categoriesApi, servicesApi, addOnsApi } from '@/services/catalog'
import { formatCurrency } from '@/utils/format'
import { PAGE_HERO_IMAGE, FALLBACK_SERVICE_IMAGE } from '@/utils/stockImages'

const route = useRoute()
const router = useRouter()

const heroImg = PAGE_HERO_IMAGE
const fallbackImg = FALLBACK_SERVICE_IMAGE

const categories = ref([])
const loading = ref(true)
const error = ref(null)

const services = ref([])
const servicesLoading = ref(false)
const serviceError = ref(null)

const addons = ref([])

// Decorative icon per add-on name; falls back to a generic sparkle.
const addonIcons = {
  'Deep Conditioning': Droplets,
  'Scalp Treatment': Wind,
  'Hair Gloss': Sparkles,
  Trim: Scissors
}

const selectedCategory = computed(() => {
  const id = route.query.category
  if (!id) return null
  return categories.value.find((c) => String(c.id) === String(id)) || null
})

function selectCategory(c) {
  router.push({ query: { ...route.query, category: c.id } })
}
function clearCategory() {
  const q = { ...route.query }
  delete q.category
  router.push({ query: q })
}

async function loadCategories() {
  loading.value = true; error.value = null
  try {
    const res = await categoriesApi.list({ is_active: true })
    categories.value = res.data.data || []
  } catch {
    error.value = 'Failed to load services. Please try again later.'
  } finally {
    loading.value = false
  }
}

async function loadServicesForCategory(categoryId) {
  servicesLoading.value = true; serviceError.value = null
  try {
    const res = await servicesApi.list({ category_id: categoryId, is_active: true })
    services.value = (res.data.data || []).map((s) => ({
      id: s.id,
      name: s.name,
      badge: '',
      image: s.image_url || fallbackImg,
      duration: `${s.base_duration_minutes} min`,
      price: formatCurrency(s.base_price),
      description: s.description
    }))
  } catch {
    serviceError.value = 'Failed to load services. Please try again later.'
  } finally {
    servicesLoading.value = false
  }
}

watch(selectedCategory, (c) => { if (c) loadServicesForCategory(c.id) })

onMounted(async () => {
  await loadCategories()
  if (selectedCategory.value) await loadServicesForCategory(selectedCategory.value.id)

  try {
    const addonsRes = await addOnsApi.list()
    addons.value = (addonsRes.data.data || [])
      .filter((a) => a.is_active)
      .map((a) => ({
        icon: addonIcons[a.name] || Gem,
        name: a.name,
        price: `+${formatCurrency(a.price_modifier)}`
      }))
  } catch { /* add-ons are a bonus section, fine to leave empty on failure */ }
})
</script>

<style scoped>
.cat-grid {
  display: grid;
  grid-template-columns: repeat(5, 1fr);
  gap: 20px;
}
.cat-card {
  background: var(--white);
  border-radius: var(--radius-md);
  overflow: hidden;
  box-shadow: var(--shadow-sm);
  border: 1px solid var(--border);
  display: flex; flex-direction: column;
  transition: transform var(--transition), box-shadow var(--transition);
  cursor: pointer;
  padding: 0;
  font: inherit;
  text-align: left;
}
.cat-card:hover { transform: translateY(-5px); box-shadow: var(--shadow-md); border-color: var(--primary); }

.cat-card__img-wrap { position: relative; aspect-ratio: 4/3; overflow: hidden; }
.cat-card__img { width: 100%; height: 100%; object-fit: cover; object-position: center top; transition: transform 0.5s ease; }
.cat-card:hover .cat-card__img { transform: scale(1.06); }
.cat-card__overlay { position: absolute; inset: 0; background: linear-gradient(to top, rgba(0,0,0,0.35), transparent 50%); }

.cat-card__body { padding: 1rem; text-align: center; }
.cat-card__name { font-family: var(--font-heading); font-size: 1rem; font-weight: 700; color: var(--charcoal); margin-bottom: 0.25rem; }
.cat-card__count { font-size: 0.78rem; color: var(--primary); font-weight: 600; }

.srv-back {
  display: inline-flex; align-items: center; gap: 0.4rem;
  background: none; border: none; cursor: pointer;
  font-size: 0.85rem; font-weight: 600; color: var(--primary);
  padding: 0; margin-bottom: 1.25rem;
}
.srv-back:hover { text-decoration: underline; }

.srv-grid {
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  gap: 24px;
}
.srv-card {
  background: var(--white);
  border-radius: var(--radius-md);
  overflow: hidden;
  box-shadow: var(--shadow-sm);
  border: 1px solid var(--border);
  display: flex; flex-direction: column;
  transition: transform var(--transition), box-shadow var(--transition);
}
.srv-card:hover { transform: translateY(-5px); box-shadow: var(--shadow-md); }

.srv-card__img-wrap { position: relative; aspect-ratio: 16/10; overflow: hidden; }
.srv-card__img { width:100%; height:100%; object-fit:cover; object-position: center top; transition: transform 0.5s ease; }
.srv-card:hover .srv-card__img { transform: scale(1.05); }

.srv-card__badge {
  position: absolute; top: 10px; left: 10px;
  background: var(--primary); color: var(--white);
  font-size: 0.7rem; font-weight: 700; letter-spacing: 0.05em;
  padding: 0.2rem 0.65rem; border-radius: var(--radius-full);
}
.srv-card__body { padding: 1.4rem; display: flex; flex-direction: column; gap: 0.7rem; flex: 1; }
.srv-card__top { display: flex; align-items: flex-start; justify-content: space-between; gap: 0.5rem; }
.srv-card__name { font-family: var(--font-heading); font-size: 1.1rem; font-weight: 600; color: var(--charcoal); }
.srv-card__duration { display: flex; align-items: center; gap: 0.3rem; font-size: 0.76rem; color: var(--gray); white-space: nowrap; flex-shrink: 0; }
.srv-card__desc { font-size: 0.85rem; color: var(--charcoal-lt); line-height: 1.65; flex: 1; }
.srv-card__footer { display: flex; align-items: center; justify-content: space-between; padding-top: 0.75rem; border-top: 1px solid var(--border); flex-wrap: wrap; gap: 0.5rem; }
.srv-card__price { font-size: 0.88rem; color: var(--charcoal-lt); }
.srv-card__price strong { font-size: 1.05rem; color: var(--primary); font-weight: 700; }
.srv-card__btn { padding: 0.5rem 1.1rem; font-size: 0.82rem; }

.addons-section { background: var(--gray-light); }
.addons-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 14px; }
.addon-card {
  background: var(--white); border: 1px solid var(--border);
  border-radius: var(--radius-md); padding: 1.15rem 1.3rem;
  display: flex; align-items: center; gap: 0.9rem;
  transition: var(--transition);
}
.addon-card:hover { border-color: var(--primary); box-shadow: var(--shadow-sm); }
.addon-card__icon { color: var(--primary); flex-shrink: 0; }
.addon-card__name { font-weight: 600; font-size: 0.88rem; color: var(--charcoal); }
.addon-card__price { font-size: 0.8rem; color: var(--primary); font-weight: 600; }

.cta-strip { background: linear-gradient(135deg, var(--pink-50) 0%, var(--pink) 100%); border-top: 1px solid var(--border); }
.cta-strip__inner { display: flex; align-items: center; justify-content: space-between; gap: 2rem; flex-wrap: wrap; }
.cta-strip__title { font-family: var(--font-heading); font-size: clamp(1.3rem, 3vw, 1.6rem); font-weight: 700; color: var(--charcoal); }
.cta-strip__sub { font-size: 0.88rem; color: var(--charcoal-lt); margin-top: 0.3rem; }
.cta-strip__actions { display: flex; gap: 0.85rem; flex-wrap: wrap; }

@media (max-width: 1024px) { .cat-grid { grid-template-columns: repeat(3, 1fr); } }
@media (max-width: 960px) { .srv-grid { grid-template-columns: repeat(2, 1fr); } }
@media (max-width: 640px) { .cat-grid { grid-template-columns: repeat(2, 1fr); } }
@media (max-width: 560px) { .srv-grid { grid-template-columns: 1fr; } }
@media (max-width: 440px) { .cat-grid { grid-template-columns: 1fr; } }
@media (max-width: 768px) { .addons-grid { grid-template-columns: repeat(2, 1fr); } }
@media (max-width: 440px) { .addons-grid { grid-template-columns: 1fr; } }
@media (max-width: 600px) {
  .cta-strip__inner { flex-direction: column; align-items: flex-start; }
  .cta-strip__actions { width: 100%; }
  .cta-strip__actions .btn { flex: 1; justify-content: center; }
}

.loading-state, .error-state {
  text-align: center;
  padding: 3rem 1rem;
  color: var(--charcoal-lt);
}

.error-state {
  color: var(--danger);
}
</style>
