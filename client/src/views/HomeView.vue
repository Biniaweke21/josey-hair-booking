<template>
  <div class="home">

    <!-- ═══ HERO ═══ -->
    <section class="hero" aria-label="Hero">
      <div class="hero__bg">
        <img 
          v-for="(img, index) in heroImages" 
          :key="index"
          :src="img" 
          :alt="`Beautiful hair design ${index + 1}`"
          class="hero__bg-img" 
          :class="{ 'is-active': currentHeroImage === index }"
        />
        <div class="hero__overlay"></div>
      </div>
      <div class="hero__content">
        <span class="section-label hero__label">Luxury Hair Salon</span>
        <h1 class="hero__title">
          Beautiful Hair.<br />
          <em>Perfect Confidence.</em>
        </h1>
        <p class="hero__sub">Luxury hair care and stunning styles tailored just for you.</p>
        <div class="hero__actions">
          <RouterLink to="/booking" class="btn btn-primary">
            <CalendarHeart :size="18" aria-hidden="true" /> Book Appointment
          </RouterLink>
          <RouterLink to="/gallery" class="btn btn-secondary">
            <Images :size="18" aria-hidden="true" /> View Gallery
          </RouterLink>
        </div>
      </div>
      <div class="hero__scroll" aria-hidden="true">
        <span class="hero__scroll-text">Scroll</span>
        <div class="hero__scroll-line"></div>
      </div>
    </section>

    <!-- ═══ SERVICES ═══ -->
    <section class="section services-section" id="services">
      <div class="section-inner">
        <div class="section-header">
          <span class="section-label">What We Offer</span>
          <h2 class="section-title">Our Services</h2>
          <div class="gold-divider"></div>
          <p class="section-subtitle">From classic cuts to intricate styles — precision and care in every appointment.</p>
        </div>
        <div class="services-grid">
          <!-- Loading skeletons -->
          <template v-if="servicesLoading">
            <article v-for="n in 6" :key="'sk'+n" class="service-card">
              <div class="service-card__img-wrap skeleton"></div>
              <div class="service-card__body">
                <span class="skeleton" style="height:1rem;width:55%;display:block;"></span>
                <span class="skeleton" style="height:0.8rem;width:30%;display:block;"></span>
              </div>
            </article>
          </template>
          <!-- Real services -->
          <article v-else v-for="s in services" :key="s.id" class="service-card">
            <div class="service-card__img-wrap">
              <img :src="s.image" :alt="s.name" class="service-card__img" loading="lazy" />
              <div class="service-card__hover">
                <RouterLink :to="`/booking?service=${s.id}`" class="btn btn-primary service-card__cta">
                  Book Now
                </RouterLink>
              </div>
            </div>
            <div class="service-card__body">
              <h3 class="service-card__name">{{ s.name }}</h3>
              <p class="service-card__price">From {{ s.price }}</p>
            </div>
          </article>
        </div>
        <p v-if="!servicesLoading && !services.length" class="state-note">Our services will appear here soon.</p>
        <div class="services-section__cta">
          <RouterLink to="/services" class="btn btn-outline">
            <ArrowRight :size="17" aria-hidden="true" /> View All Services
          </RouterLink>
        </div>
      </div>
    </section>

    <!-- ═══ GALLERY PREVIEW ═══ -->
    <section class="section gallery-section" id="gallery">
      <div class="section-inner">
        <div class="section-header">
          <span class="section-label">Our Work</span>
          <h2 class="section-title">Hair Inspiration</h2>
          <div class="gold-divider"></div>
          <p class="section-subtitle">Every set tells a story. Browse our latest creations.</p>
        </div>
        <div class="gallery-grid">
          <template v-if="galleryLoading">
            <div v-for="n in 6" :key="'gsk'+n" class="gallery-item skeleton" :class="GALLERY_SPANS[n-1]"></div>
          </template>
          <div v-else v-for="(item, i) in galleryItems" :key="i" class="gallery-item" :class="item.spanClass">
            <img :src="item.src" :alt="item.alt" class="gallery-item__img" loading="lazy" />
            <div class="gallery-item__overlay">
              <span class="gallery-item__tag">{{ item.tag }}</span>
            </div>
          </div>
        </div>
        <p v-if="!galleryLoading && !galleryItems.length" class="state-note">New photos coming soon.</p>
        <div class="gallery-section__cta">
          <RouterLink to="/gallery" class="btn btn-outline">
            <ArrowRight :size="17" aria-hidden="true" /> View Full Gallery
          </RouterLink>
        </div>
      </div>
    </section>

    <!-- ═══ WHY US ═══ -->
    <section class="section why-section" id="why">
      <div class="section-inner">
        <div class="section-header">
          <span class="section-label">Our Promise</span>
          <h2 class="section-title">Why {{ salonName }}?</h2>
          <div class="gold-divider"></div>
        </div>
        <div class="why-grid">
          <div v-for="f in features" :key="f.id" class="why-card">
            <div class="why-card__icon-wrap" aria-hidden="true">
              <component :is="f.icon" :size="26" />
            </div>
            <h3 class="why-card__title">{{ f.title }}</h3>
            <p class="why-card__text">{{ f.text }}</p>
          </div>
        </div>
      </div>
    </section>

    <!-- ═══ TESTIMONIALS ═══ -->
    <section v-if="testimonials.length" class="section testimonials-section" id="testimonials">
      <div class="section-inner">
        <div class="section-header">
          <span class="section-label">Happy Clients</span>
          <h2 class="section-title">What Our Clients Say</h2>
          <div class="gold-divider"></div>
        </div>
        <div class="testimonials-scroll" ref="trackRef">
          <article v-for="(t, i) in testimonials" :key="i" class="testimonial-card">
            <div class="testimonial-card__stars" :aria-label="`${t.stars} out of 5 stars`">
              <Star v-for="s in t.stars" :key="s" :size="16" fill="currentColor" aria-hidden="true" />
            </div>
            <blockquote class="testimonial-card__review">"{{ t.review }}"</blockquote>
            <div class="testimonial-card__author">
              <div class="testimonial-card__avatar-img testimonial-card__avatar-initials" aria-hidden="true">{{ initials(t.name) }}</div>
              <div>
                <p class="testimonial-card__name">{{ t.name }}</p>
                <p class="testimonial-card__service">{{ t.title }} · {{ t.service }}</p>
              </div>
            </div>
          </article>
        </div>
        <div class="testimonials-dots" role="tablist" aria-label="Testimonial navigation">
          <button
            v-for="(_, i) in testimonials" :key="i"
            class="testimonials-dot"
            :class="{ 'is-active': activeTestimonial === i }"
            @click="scrollToTestimonial(i)"
            :aria-label="`Go to testimonial ${i + 1}`"
            :aria-selected="activeTestimonial === i"
            role="tab"
          ></button>
        </div>
      </div>
    </section>

    <!-- ═══ CTA BANNER ═══ -->
    <section class="cta-banner">
      <div class="cta-banner__bg">
        <img :src="ctaImg" alt="" aria-hidden="true" class="cta-banner__img" />
        <div class="cta-banner__overlay"></div>
      </div>
      <div class="cta-banner__content">
        <span class="section-label" style="color: var(--pink-200);">Limited Slots Available</span>
        <h2 class="cta-banner__title">Ready for your<br /><em>perfect hair?</em></h2>
        <p class="cta-banner__sub">Book in under 60 seconds. No account needed.</p>
        <RouterLink to="/booking" class="btn btn-primary cta-banner__btn">
          <CalendarHeart :size="18" aria-hidden="true" /> Book Appointment Now
        </RouterLink>
      </div>
    </section>

    <!-- ═══ INSTAGRAM ═══ -->
    <section class="section instagram-section" id="instagram">
      <div class="section-inner">
        <div class="section-header">
          <span class="section-label">@{{ instagram || 'beautybyjosey' }}</span>
          <h2 class="section-title">Follow Our Work</h2>
          <div class="gold-divider"></div>
          <p class="section-subtitle">Stay inspired. Follow us for daily hair inspo.</p>
        </div>
        <div class="instagram-grid">
          <template v-if="galleryLoading">
            <span v-for="n in 6" :key="'isk'+n" class="insta-post skeleton"></span>
          </template>
          <a v-else v-for="(p, i) in instagramPosts" :key="i" :href="instagramUrl" class="insta-post" :aria-label="`Instagram post ${i + 1}`" target="_blank" rel="noopener noreferrer">
            <img :src="p.src" :alt="p.alt" class="insta-post__img" loading="lazy" />
            <div class="insta-post__hover" aria-hidden="true">
              <Instagram :size="26" />
            </div>
          </a>
        </div>
        <div class="instagram-section__cta">
          <a :href="instagramUrl" class="btn btn-outline" target="_blank" rel="noopener noreferrer">
            <Instagram :size="17" aria-hidden="true" /> Follow on Instagram
          </a>
          <a v-if="tiktok" :href="tiktokUrl" class="btn btn-outline" target="_blank" rel="noopener noreferrer">
            <TikTokIcon :size="17" /> Follow on TikTok
          </a>
        </div>
      </div>
    </section>

  </div>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue'
import { RouterLink } from 'vue-router'
import {
  CalendarHeart, Images, ArrowRight, Star, Instagram,
  Sparkles, ShieldCheck, Clock, Heart
} from 'lucide-vue-next'
import TikTokIcon from '@/components/icons/TikTokIcon.vue'
import { categoriesApi, servicesApi } from '@/services/catalog'
import { galleryApi } from '@/services/gallery'
import { useSettings } from '@/composables/useSettings'
import { formatCurrency } from '@/utils/format'
import { HERO_IMAGES, CTA_IMAGE } from '@/utils/stockImages'

const { salonName, instagram, tiktok } = useSettings()
const instagramUrl = computed(() => (instagram.value ? `https://www.instagram.com/${instagram.value}` : '#'))
const tiktokUrl = computed(() => (tiktok.value ? `https://www.tiktok.com/@${tiktok.value}` : '#'))

// Decorative hero + CTA imagery (real licensed stock photos, not backend data).
const heroImages = HERO_IMAGES
const currentHeroImage = ref(0)
let heroInterval = null
const ctaImg = CTA_IMAGE

// The first six gallery tiles keep their original masonry spans.
const GALLERY_SPANS = ['gallery-item--tall', '', '', 'gallery-item--wide', '', '']

// Services (from API)
const services = ref([])
const servicesLoading = ref(true)

// Gallery preview + Instagram grid (from API)
const galleryItems = ref([])
const instagramPosts = ref([])
const galleryLoading = ref(true)

onMounted(async () => {
  heroInterval = setInterval(() => {
    currentHeroImage.value = (currentHeroImage.value + 1) % heroImages.length
  }, 5000)

  try {
    // Feature the salon's lead category (Braid Styling) in menu order,
    // instead of the first six services alphabetically.
    const cats = (await categoriesApi.list()).data.data || []
    const lead = cats.filter((c) => c.is_active).sort((a, b) => a.sort_order - b.sort_order)[0]
    const res = await servicesApi.list(lead ? { category_id: lead.id } : {})
    services.value = (res.data.data || [])
      .filter((s) => s.is_active)
      .sort((a, b) => a.sort_order - b.sort_order)
      .slice(0, 6)
      .map((s) => ({
        id: s.id,
        name: s.name,
        price: formatCurrency(s.base_price),
        image: s.image_url || ctaImg
      }))
  } catch {
    services.value = []
  } finally {
    servicesLoading.value = false
  }

  try {
    const res = await galleryApi.list()
    const items = (res.data.data || []).filter((g) => g.is_active)
    galleryItems.value = items.slice(0, 6).map((g, i) => ({
      src: g.image_url,
      alt: g.title || g.category?.name || 'Style',
      tag: g.category?.name || g.title || 'Style',
      spanClass: GALLERY_SPANS[i] || ''
    }))
    const igSource = items.slice(6, 12).length ? items.slice(6, 12) : items.slice(0, 6)
    instagramPosts.value = igSource.map((g) => ({ src: g.image_url, alt: g.title || 'Style' }))
  } catch {
    galleryItems.value = []
    instagramPosts.value = []
  } finally {
    galleryLoading.value = false
  }
})

onUnmounted(() => {
  if (heroInterval) clearInterval(heroInterval)
})

// Marketing "why us" copy is part of the approved design, not backend data.
const features = [
  { id: 1, icon: Sparkles,    title: 'Premium Quality',    text: 'Top-tier, skin-safe products for every treatment.'         },
  { id: 2, icon: Heart,       title: 'Expert Artists',     text: 'Years of experience and artistic talent in every set.'     },
  { id: 3, icon: Clock,       title: 'Fast Booking',       text: 'Book your appointment online in under 60 seconds.'         },
  { id: 4, icon: ShieldCheck, title: 'Hygienic & Safe',    text: 'Sterilized tools, clean environment — every single time.'  },
]

// Real client reviews only (with their permission). The template's sample
// reviews were removed because they read as real endorsements; the section
// stays hidden while this list is empty.
const testimonials = []

const activeTestimonial = ref(0)
const trackRef = ref(null)

function scrollToTestimonial(index) {
  activeTestimonial.value = index
  if (!trackRef.value) return
  const card = trackRef.value.children[index]
  if (card) card.scrollIntoView({ behavior: 'smooth', block: 'nearest', inline: 'center' })
}

function initials(name) {
  return name
    .split(' ')
    .map((w) => w[0])
    .slice(0, 2)
    .join('')
    .toUpperCase()
}
</script>

<style scoped>
/* ═══ HERO ═══ */
.hero {
  position: relative;
  height: 100svh;
  min-height: 580px;
  display: flex;
  align-items: center;
  justify-content: center;
  overflow: hidden;
}
.hero__bg { position: absolute; inset: 0; }
.hero__bg-img {
  position: absolute;
  inset: 0;
  width: 100%;
  height: 100%;
  object-fit: cover;
  object-position: center 30%;
  opacity: 0;
  transition: opacity 1.5s ease-in-out;
}
.hero__bg-img.is-active { opacity: 1; }
.hero__overlay {
  position: absolute; inset: 0;
  background: linear-gradient(160deg, rgba(20,10,15,0.65) 0%, rgba(91, 167, 255,0.18) 100%);
}
.hero__content {
  position: relative; z-index: 2;
  text-align: center;
  padding: 0 20px;
  display: flex; flex-direction: column; align-items: center;
  gap: 1.2rem;
  width: 100%; max-width: 760px;
}
.hero__label { color: var(--pink-200); }
.hero__title {
  font-family: var(--font-heading);
  font-size: clamp(2.4rem, 8vw, 4.8rem);
  font-weight: 700; color: var(--white);
  line-height: 1.1; letter-spacing: -0.02em;
  word-break: break-word;
}
.hero__title em { font-style: italic; color: var(--pink-200); }
.hero__sub {
  font-size: clamp(0.95rem, 2.5vw, 1.1rem);
  color: rgba(255,255,255,0.8); line-height: 1.7;
}
.hero__actions {
  display: flex; gap: 0.85rem;
  flex-wrap: wrap; justify-content: center;
  margin-top: 0.4rem;
  width: 100%;
}
.hero__actions .btn {
  flex: 1 1 160px;
  max-width: 240px;
  justify-content: center;
}

/* Scroll hint */
.hero__scroll {
  position: absolute; bottom: 2rem; left: 50%;
  transform: translateX(-50%);
  display: flex; flex-direction: column; align-items: center;
  gap: 0.4rem; z-index: 2;
}
.hero__scroll-text {
  font-size: 0.65rem; letter-spacing: 0.22em;
  text-transform: uppercase; color: rgba(255,255,255,0.45);
}
.hero__scroll-line {
  width: 1px; height: 44px;
  background: linear-gradient(to bottom, rgba(255,255,255,0.4), transparent);
  animation: scrollLine 2s ease-in-out infinite;
}
@keyframes scrollLine {
  0%,100% { transform: scaleY(1); opacity: 1; }
  50%      { transform: scaleY(0.5); opacity: 0.3; }
}

/* ═══ SERVICES ═══ */
.services-section { background: var(--gray-light); }

.services-grid {
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  gap: 22px;
}
.service-card {
  background: var(--white);
  border-radius: var(--radius-md);
  overflow: hidden;
  box-shadow: var(--shadow-sm);
  border: 1px solid var(--border);
  transition: transform var(--transition), box-shadow var(--transition);
}
.service-card:hover { transform: translateY(-5px); box-shadow: var(--shadow-md); }

.service-card__img-wrap {
  position: relative;
  aspect-ratio: 4/3;
  overflow: hidden;
}
.service-card__img {
  width: 100%; height: 100%; object-fit: cover;
  object-position: center top;
  transition: transform 0.5s ease;
}
.service-card:hover .service-card__img { transform: scale(1.06); }

.service-card__hover {
  position: absolute; inset: 0;
  background: rgba(91, 167, 255,0.42);
  display: flex; align-items: center; justify-content: center;
  opacity: 0; transition: opacity var(--transition);
}
.service-card:hover .service-card__hover { opacity: 1; }
.service-card__cta { padding: 0.55rem 1.3rem; font-size: 0.82rem; }

.service-card__body {
  padding: 1rem 1.2rem 1.2rem;
  display: flex; align-items: center; justify-content: space-between;
}
.service-card__name {
  font-family: var(--font-heading);
  font-size: 1rem; font-weight: 600; color: var(--charcoal);
}
.service-card__price {
  font-size: 0.82rem; font-weight: 700; color: var(--primary);
}

/* ═══ GALLERY ═══ */
.gallery-grid {
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  grid-auto-rows: 220px;
  gap: 10px;
}
.gallery-item {
  position: relative;
  border-radius: var(--radius-sm);
  overflow: hidden; cursor: pointer;
}
.gallery-item--tall { grid-row: span 2; }
.gallery-item--wide { grid-column: span 2; }

.gallery-item__img {
  width: 100%; height: 100%; object-fit: cover;
  object-position: center 25%; /* keep faces and braids in frame on wide tiles */
  object-position: center top;
  transition: transform 0.5s ease;
}
.gallery-item:hover .gallery-item__img { transform: scale(1.06); }

.gallery-item__overlay {
  position: absolute; inset: 0;
  background: linear-gradient(to top, rgba(91, 167, 255,0.55) 0%, transparent 55%);
  display: flex; align-items: flex-end; padding: 0.85rem;
  opacity: 0; transition: opacity var(--transition);
}
.gallery-item:hover .gallery-item__overlay { opacity: 1; }
.gallery-item__tag {
  font-size: 0.7rem; font-weight: 700;
  letter-spacing: 0.1em; text-transform: uppercase;
  color: var(--white);
  background: rgba(91, 167, 255,0.75);
  padding: 0.2rem 0.6rem;
  border-radius: var(--radius-full);
}
.gallery-section__cta {
  display: flex; justify-content: center;
  margin-top: 36px;
}
.services-section__cta {
  display: flex; justify-content: center;
  margin-top: 36px;
}

/* ═══ WHY US ═══ */
.why-section {
  background: linear-gradient(160deg, var(--pink-50) 0%, var(--pink) 100%);
}
.why-grid {
  display: grid;
  grid-template-columns: repeat(4, 1fr);
  gap: 20px;
}
.why-card {
  background: var(--white);
  border-radius: var(--radius-md);
  padding: 2rem 1.4rem;
  text-align: center;
  box-shadow: var(--shadow-sm);
  border: 1px solid var(--border);
  transition: transform var(--transition), box-shadow var(--transition);
}
.why-card:hover { transform: translateY(-4px); box-shadow: var(--shadow-md); }

.why-card__icon-wrap {
  width: 56px; height: 56px;
  border-radius: 50%;
  background: var(--pink-50);
  border: 1.5px solid var(--border);
  display: flex; align-items: center; justify-content: center;
  margin: 0 auto 1rem;
  color: var(--primary);
}
.why-card__title {
  font-family: var(--font-heading);
  font-size: 0.98rem; font-weight: 600;
  color: var(--charcoal); margin-bottom: 0.55rem;
}
.why-card__text { font-size: 0.85rem; color: var(--charcoal-lt); line-height: 1.65; }

/* ═══ TESTIMONIALS ═══ */
.testimonials-section { background: var(--white); }

.testimonials-scroll {
  display: grid;
  grid-template-columns: repeat(4, 1fr);
  gap: 18px;
  overflow: hidden;
}
.testimonial-card {
  background: var(--gray-light);
  border-radius: var(--radius-md);
  padding: 1.6rem;
  border: 1px solid var(--border);
  display: flex; flex-direction: column; gap: 0.9rem;
}
.testimonial-card__stars { display: flex; gap: 2px; color: var(--primary); }
.testimonial-card__review {
  font-size: 0.88rem; color: var(--charcoal-lt);
  line-height: 1.7; font-style: italic; flex: 1;
}
.testimonial-card__author { display: flex; align-items: center; gap: 0.7rem; }
.testimonial-card__avatar-img {
  width: 44px; height: 44px;
  border-radius: 50%;
  object-fit: cover;
  object-position: center top;
  flex-shrink: 0;
  border: 2px solid var(--border);
}
.testimonial-card__avatar-initials {
  display: flex; align-items: center; justify-content: center;
  background: var(--primary); color: var(--white);
  font-family: var(--font-heading);
  font-size: 0.9rem; font-weight: 700;
}
.testimonial-card__name { font-weight: 600; font-size: 0.88rem; color: var(--charcoal); }
.testimonial-card__service { font-size: 0.75rem; color: var(--primary); }

.testimonials-dots {
  display: flex; justify-content: center; gap: 7px; margin-top: 28px;
}
.testimonials-dot {
  width: 8px; height: 8px;
  border-radius: 50%;
  background: var(--pink-dark);
  border: none; cursor: pointer;
  transition: var(--transition); padding: 0;
}
.testimonials-dot.is-active {
  background: var(--primary);
  width: 24px; border-radius: 4px;
}

/* ═══ CTA BANNER ═══ */
.cta-banner {
  position: relative;
  padding: 88px 20px;
  display: flex; align-items: center; justify-content: center;
  overflow: hidden; text-align: center;
}
.cta-banner__bg { position: absolute; inset: 0; }
.cta-banner__img { width: 100%; height: 100%; object-fit: cover; object-position: center 30%; }
.cta-banner__overlay {
  position: absolute; inset: 0;
  background: linear-gradient(160deg, rgba(20,10,15,0.82) 0%, rgba(91, 167, 255,0.3) 100%);
}
.cta-banner__content {
  position: relative; z-index: 2;
  display: flex; flex-direction: column; align-items: center;
  gap: 1.1rem; width: 100%; max-width: 580px;
}
.cta-banner__title {
  font-family: var(--font-heading);
  font-size: clamp(1.9rem, 5vw, 3.2rem);
  font-weight: 700; color: var(--white); line-height: 1.15;
}
.cta-banner__title em { font-style: italic; color: var(--pink-200); }
.cta-banner__sub { font-size: 0.98rem; color: rgba(255,255,255,0.75); }
.cta-banner__btn { padding: 0.95rem 2.2rem; font-size: 0.98rem; }

/* ═══ INSTAGRAM ═══ */
.instagram-grid {
  display: grid;
  grid-template-columns: repeat(6, 1fr);
  gap: 7px;
}
.insta-post {
  display: block;
  aspect-ratio: 1;
  position: relative;
  overflow: hidden;
  border-radius: var(--radius-sm);
}
.insta-post__img {
  width: 100%; height: 100%; object-fit: cover;
  object-position: center top;
  transition: transform 0.4s ease;
}
.insta-post:hover .insta-post__img { transform: scale(1.08); }
.insta-post__hover {
  position: absolute; inset: 0;
  background: rgba(91, 167, 255,0.55);
  display: flex; align-items: center; justify-content: center;
  color: var(--white);
  opacity: 0; transition: opacity var(--transition);
}
.insta-post:hover .insta-post__hover { opacity: 1; }

.instagram-section__cta {
  display: flex; justify-content: center;
  gap: 0.85rem; margin-top: 36px; flex-wrap: wrap;
}

/* ═══ RESPONSIVE ═══ */
@media (max-width: 1024px) {
  .testimonials-scroll { grid-template-columns: repeat(2, 1fr); }
}

@media (max-width: 900px) {
  .services-grid    { grid-template-columns: repeat(2, 1fr); }
  .why-grid         { grid-template-columns: repeat(2, 1fr); }
  .instagram-grid   { grid-template-columns: repeat(3, 1fr); }
}

@media (max-width: 768px) {
  .gallery-grid {
    grid-template-columns: repeat(2, 1fr);
    grid-auto-rows: 170px;
  }
  .gallery-item--tall { grid-row: span 1; }
  .gallery-item--wide { grid-column: span 1; }
}

@media (max-width: 600px) {
  .testimonials-scroll {
    display: flex;
    overflow-x: auto;
    scroll-snap-type: x mandatory;
    -webkit-overflow-scrolling: touch;
    gap: 14px;
    padding-bottom: 8px;
    /* hide scrollbar */
    scrollbar-width: none;
  }
  .testimonials-scroll::-webkit-scrollbar { display: none; }
  .testimonial-card {
    min-width: 82vw;
    scroll-snap-align: center;
  }
  .instagram-grid { grid-template-columns: repeat(3, 1fr); }
}

@media (max-width: 540px) {
  .services-grid  { grid-template-columns: 1fr; }
  .why-grid       { grid-template-columns: 1fr; }
  .gallery-grid {
    grid-template-columns: repeat(2, 1fr);
    grid-auto-rows: 150px;
  }
  .instagram-grid { grid-template-columns: repeat(2, 1fr); }
  .hero__actions .btn { max-width: 100%; }
}
</style>
