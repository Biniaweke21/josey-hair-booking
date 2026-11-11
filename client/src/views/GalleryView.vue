<template>
  <div class="gallery-page">
    <section class="page-hero">
      <div class="page-hero__bg">
        <img :src="heroImg" alt="Hair gallery" class="page-hero__img" />
        <div class="page-hero__overlay"></div>
      </div>
      <div class="page-hero__content">
        <span class="section-label" style="color:var(--pink-200)">Our Portfolio</span>
        <h1 class="page-hero__title">Hair Gallery</h1>
        <p class="page-hero__sub">Browse our latest creations and get inspired.</p>
      </div>
    </section>

    <section class="section">
      <div class="section-inner">
        <!-- Filters -->
        <div v-if="!loading && allItems.length" class="gallery-filters" role="tablist" aria-label="Filter gallery">
          <button
            v-for="tab in tabs" :key="tab.id"
            class="gallery-filter-btn"
            :class="{ 'is-active': activeTab === tab.id }"
            @click="activeTab = tab.id"
            role="tab" :aria-selected="activeTab === tab.id"
          >{{ tab.label }}</button>
        </div>

        <!-- Loading skeletons -->
        <div v-if="loading" class="gallery-masonry">
          <div v-for="n in 9" :key="'gsk'+n" class="gallery-masonry__item skeleton" :style="{ height: skeletonHeights[n % skeletonHeights.length] }"></div>
        </div>

        <!-- Empty state -->
        <p v-else-if="!allItems.length" class="state-note">Our gallery is being updated — check back soon.</p>

        <!-- Grid -->
        <Transition v-else name="fade" mode="out-in">
          <div class="gallery-masonry" :key="activeTab">
            <div
              v-for="(item, i) in filteredItems" :key="i"
              class="gallery-masonry__item"
              @click="openLightbox(item)"
              role="button"
              :aria-label="`View ${item.alt}`"
              tabindex="0"
              @keydown.enter="openLightbox(item)"
            >
              <img :src="item.src" :alt="item.alt" class="gallery-masonry__img" loading="lazy" />
              <div class="gallery-masonry__overlay">
                <span v-if="item.category" class="gallery-masonry__tag">{{ item.category }}</span>
                <ZoomIn :size="26" color="white" aria-hidden="true" />
              </div>
            </div>
          </div>
        </Transition>
      </div>
    </section>

    <!-- Lightbox -->
    <Teleport to="body">
      <Transition name="lb">
        <div v-if="lightboxItem" class="lightbox" @click.self="closeLightbox" role="dialog" aria-modal="true">
          <button class="lb-close" @click="closeLightbox" aria-label="Close">
            <X :size="20" />
          </button>
          <button class="lb-nav lb-nav--prev" @click="prevItem" aria-label="Previous">
            <ChevronLeft :size="28" />
          </button>
          <div class="lb-img-wrap">
            <img :src="lightboxItem.src" :alt="lightboxItem.alt" class="lb-img" />
            <div class="lb-caption">
              <span v-if="lightboxItem.category" class="lb-tag">{{ lightboxItem.category }}</span>
              <p class="lb-alt">{{ lightboxItem.alt }}</p>
            </div>
          </div>
          <button class="lb-nav lb-nav--next" @click="nextItem" aria-label="Next">
            <ChevronRight :size="28" />
          </button>
        </div>
      </Transition>
    </Teleport>

    <!-- CTA -->
    <section class="section gallery-cta">
      <div class="section-inner gallery-cta__inner">
        <h2 class="gallery-cta__title">Love what you see?</h2>
        <p class="gallery-cta__sub">Book your appointment and let us create your perfect set.</p>
        <RouterLink to="/booking" class="btn btn-primary">
          <CalendarHeart :size="17" aria-hidden="true" /> Book Appointment
        </RouterLink>
      </div>
    </section>

  </div>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue'
import { RouterLink } from 'vue-router'
import { ZoomIn, X, ChevronLeft, ChevronRight, CalendarHeart } from 'lucide-vue-next'
import { galleryApi } from '@/services/gallery'
import { PAGE_HERO_IMAGE } from '@/utils/stockImages'

const heroImg = PAGE_HERO_IMAGE

const activeTab = ref('all')
const allItems = ref([])
const loading = ref(true)

// Filter tabs are built from whatever categories the gallery photos use.
const tabs = computed(() => {
  const names = [...new Set(allItems.value.map((i) => i.category).filter(Boolean))]
  return [{ id: 'all', label: 'All' }, ...names.map((n) => ({ id: n, label: n }))]
})

const filteredItems = computed(() =>
  activeTab.value === 'all' ? allItems.value : allItems.value.filter((i) => i.category === activeTab.value)
)

// Varied heights so loading skeletons read as a masonry.
const skeletonHeights = ['220px', '300px', '180px', '260px', '200px', '320px', '240px', '190px', '280px']

onMounted(async () => {
  try {
    const res = await galleryApi.list()
    allItems.value = (res.data.data || [])
      .filter((g) => g.is_active && g.image_url)
      .map((g) => ({
        src: g.image_url,
        alt: g.title || g.category?.name || 'Gallery photo',
        category: g.category?.name || null
      }))
  } catch {
    allItems.value = []
  } finally {
    loading.value = false
  }
})

const lightboxItem  = ref(null)
const lightboxIndex = ref(0)

function openLightbox(item) {
  lightboxIndex.value = filteredItems.value.indexOf(item)
  lightboxItem.value  = item
  document.body.style.overflow = 'hidden'
}
function closeLightbox() { lightboxItem.value = null; document.body.style.overflow = '' }
function nextItem() {
  const items = filteredItems.value
  lightboxIndex.value = (lightboxIndex.value + 1) % items.length
  lightboxItem.value  = items[lightboxIndex.value]
}
function prevItem() {
  const items = filteredItems.value
  lightboxIndex.value = (lightboxIndex.value - 1 + items.length) % items.length
  lightboxItem.value  = items[lightboxIndex.value]
}
function onKeydown(e) {
  if (!lightboxItem.value) return
  if (e.key === 'Escape') closeLightbox()
  if (e.key === 'ArrowRight') nextItem()
  if (e.key === 'ArrowLeft')  prevItem()
}
onMounted(()  => window.addEventListener('keydown', onKeydown))
onUnmounted(() => window.removeEventListener('keydown', onKeydown))
</script>

<style scoped>
.gallery-filters {
  display: flex; gap: 0.5rem;
  justify-content: center; flex-wrap: wrap;
  margin-bottom: 44px;
}
.gallery-filter-btn {
  font-family: var(--font-body); font-size: 0.85rem; font-weight: 600;
  padding: 0.5rem 1.3rem;
  border-radius: var(--radius-full);
  border: 1.5px solid var(--border);
  background: var(--white); color: var(--charcoal-lt);
  cursor: pointer; transition: var(--transition);
}
.gallery-filter-btn:hover { border-color: var(--primary); color: var(--primary); }
.gallery-filter-btn.is-active {
  background: var(--primary); border-color: var(--primary);
  color: var(--white); box-shadow: 0 4px 14px var(--primary-shadow);
}

.gallery-masonry { columns: 3; column-gap: 12px; }
.gallery-masonry__item {
  break-inside: avoid; margin-bottom: 12px;
  position: relative;
  border-radius: var(--radius-sm);
  overflow: hidden; cursor: pointer;
}
.gallery-masonry__item:focus-visible { outline: 2px solid var(--primary); }
.gallery-masonry__img { width: 100%; display: block; transition: transform 0.45s ease; }
.gallery-masonry__item:hover .gallery-masonry__img { transform: scale(1.04); }
.gallery-masonry__overlay {
  position: absolute; inset: 0;
  background: rgba(91, 167, 255,0.48);
  display: flex; flex-direction: column;
  align-items: center; justify-content: center; gap: 0.7rem;
  opacity: 0; transition: opacity var(--transition);
}
.gallery-masonry__item:hover .gallery-masonry__overlay { opacity: 1; }
.gallery-masonry__tag {
  font-size: 0.7rem; font-weight: 700; letter-spacing: 0.1em;
  text-transform: uppercase; color: var(--white);
  background: rgba(91, 167, 255,0.75);
  padding: 0.2rem 0.7rem; border-radius: var(--radius-full);
}

.fade-enter-active, .fade-leave-active { transition: opacity 0.22s ease; }
.fade-enter-from, .fade-leave-to { opacity: 0; }

/* Lightbox */
.lightbox {
  position: fixed; inset: 0; z-index: 500;
  background: rgba(10,5,8,0.95);
  display: flex; align-items: center; justify-content: center;
  padding: 1rem;
}
.lb-close {
  position: absolute; top: 1.2rem; right: 1.2rem;
  width: 42px; height: 42px; border-radius: 50%;
  background: rgba(255,255,255,0.1); border: none;
  color: var(--white); cursor: pointer;
  display: flex; align-items: center; justify-content: center;
  transition: background var(--transition);
}
.lb-close:hover { background: rgba(255,255,255,0.22); }

.lb-nav {
  position: absolute; top: 50%; transform: translateY(-50%);
  background: rgba(255,255,255,0.1); border: none;
  color: var(--white);
  width: 48px; height: 48px; border-radius: 50%;
  cursor: pointer; display: flex; align-items: center; justify-content: center;
  transition: background var(--transition); z-index: 2;
}
.lb-nav:hover { background: rgba(255,255,255,0.22); }
.lb-nav--prev { left: 1rem; }
.lb-nav--next { right: 1rem; }

.lb-img-wrap { max-width: 88vw; max-height: 88vh; display: flex; flex-direction: column; align-items: center; gap: 0.9rem; }
.lb-img { max-width: 100%; max-height: 76vh; object-fit: contain; border-radius: var(--radius-sm); }
.lb-caption { display: flex; align-items: center; gap: 0.7rem; }
.lb-tag {
  font-size: 0.7rem; font-weight: 700; letter-spacing: 0.1em;
  text-transform: uppercase; background: var(--primary);
  color: var(--white); padding: 0.18rem 0.6rem; border-radius: var(--radius-full);
}
.lb-alt { font-size: 0.88rem; color: rgba(255,255,255,0.6); }

.lb-enter-active, .lb-leave-active { transition: opacity 0.22s ease; }
.lb-enter-from, .lb-leave-to { opacity: 0; }

/* Gallery CTA */
.gallery-cta { background: linear-gradient(135deg, var(--pink-50) 0%, var(--pink) 100%); }
.gallery-cta__inner { display: flex; flex-direction: column; align-items: center; text-align: center; gap: 0.9rem; }
.gallery-cta__title { font-family: var(--font-heading); font-size: clamp(1.6rem, 4vw, 2rem); font-weight: 700; color: var(--charcoal); }
.gallery-cta__sub { font-size: 0.98rem; color: var(--charcoal-lt); }

@media (max-width: 768px) {
  .gallery-masonry { columns: 2; }
  .lb-nav--prev { left: 0.4rem; }
  .lb-nav--next { right: 0.4rem; }
}
@media (max-width: 440px) {
  .gallery-masonry { columns: 1; }
}
</style>
