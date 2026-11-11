<template>
  <footer class="footer">
    <div class="footer__inner">

      <!-- Brand -->
      <div class="footer__brand">
        <div class="footer__logo">
          <img src="/images/josey.jpg" :alt="salonName" class="footer__logo-img" />
          <span class="footer__logo-text">{{ salonName }}</span>
        </div>
        <p class="footer__tagline">Luxury hair care and stunning hair artistry tailored just for you.</p>
        <div class="footer__socials">
          <a v-if="instagram" :href="instagramUrl" class="footer__social" aria-label="Instagram" target="_blank" rel="noopener noreferrer">
            <Instagram :size="17" aria-hidden="true" />
          </a>
          <a v-if="tiktok" :href="tiktokUrl" class="footer__social" aria-label="TikTok" target="_blank" rel="noopener noreferrer">
            <TikTokIcon :size="17" />
          </a>
        </div>
      </div>

      <!-- Company -->
      <div class="footer__col">
        <h4 class="footer__col-title">Company</h4>
        <ul class="footer__links">
          <li><RouterLink to="/">Home</RouterLink></li>
          <li><RouterLink to="/services">Services</RouterLink></li>
          <li><RouterLink to="/gallery">Gallery</RouterLink></li>
          <li><RouterLink to="/contact">Contact</RouterLink></li>
        </ul>
      </div>

      <!-- Services -->
      <div class="footer__col">
        <h4 class="footer__col-title">Services</h4>
        <ul class="footer__links">
          <li v-for="c in categories" :key="c.id">
            <RouterLink to="/services">{{ c.name }}</RouterLink>
          </li>
          <li v-if="!categories.length"><RouterLink to="/services">Our Services</RouterLink></li>
        </ul>
      </div>

      <!-- Contact -->
      <div class="footer__col">
        <h4 class="footer__col-title">Contact</h4>
        <ul class="footer__links footer__links--contact">
          <li v-if="phone">
            <Phone :size="13" aria-hidden="true" />
            <a :href="`tel:${phoneHref}`">{{ phone }}</a>
          </li>
          <li v-if="email">
            <Mail :size="13" aria-hidden="true" />
            <a :href="`mailto:${email}`">{{ email }}</a>
          </li>
          <li v-if="address">
            <MapPin :size="13" aria-hidden="true" />
            <span>{{ address }}</span>
          </li>
        </ul>
      </div>
    </div>

    <div class="footer__bottom">
      <p><a href="https://www.instagram.com/heb_el1" target="_blank" rel="noopener noreferrer" class="footer__year-link">© {{ year }} {{ salonName }}. All rights reserved.</a></p>
      <p class="footer__made">Developed by <a href="#" class="footer__vibey">Vibey Worlds</a></p>
    </div>
  </footer>
</template>

<script setup>
import { computed, onMounted, ref } from 'vue'
import { RouterLink } from 'vue-router'
import { Instagram, Phone, Mail, MapPin } from 'lucide-vue-next'
import TikTokIcon from '@/components/icons/TikTokIcon.vue'
import { useSettings } from '@/composables/useSettings'
import { categoriesApi } from '@/services/catalog'

const { salonName, email, phone, address, instagram, tiktok } = useSettings()
const year = new Date().getFullYear()
const instagramUrl = computed(() => (instagram.value ? `https://www.instagram.com/${instagram.value}` : '#'))
const tiktokUrl = computed(() => (tiktok.value ? `https://www.tiktok.com/@${tiktok.value}` : '#'))
const phoneHref = computed(() => (phone.value || '').replace(/[^0-9+]/g, ''))

const categories = ref([])
onMounted(async () => {
  try {
    const res = await categoriesApi.list()
    categories.value = (res.data.data || []).filter((c) => c.is_active).slice(0, 5)
  } catch {
    categories.value = []
  }
})
</script>

<style scoped>
.footer {
  background: #1F2937; /* dark gray */
  color: rgba(255,255,255,0.7);
  padding-top: 64px;
  /* prevent overflow */
  width: 100%;
  overflow: hidden;
}

.footer__inner {
  max-width: 1200px;
  margin: 0 auto;
  padding: 0 20px 52px;
  display: grid;
  grid-template-columns: 2fr 1fr 1fr 1.4fr;
  gap: 40px;
}

.footer__logo {
  display: flex;
  align-items: center;
  gap: 0.35rem;
  margin-bottom: 0.9rem;
}
.footer__logo-text {
  font-family: var(--font-heading);
  font-size: 1.35rem;
  font-weight: 700;
  color: var(--white);
}
.footer__logo-img {
  width: 34px; height: 34px;
  border-radius: 50%;
  object-fit: cover;
  flex-shrink: 0;
  border: 1.5px solid rgba(255,255,255,0.25);
}

.footer__tagline {
  font-size: 0.875rem;
  line-height: 1.7;
  color: rgba(255,255,255,0.5);
  max-width: 230px;
  margin-bottom: 1.4rem;
}

.footer__socials { display: flex; gap: 0.65rem; }

.footer__social {
  width: 36px; height: 36px;
  border-radius: 50%;
  border: 1px solid rgba(255,255,255,0.15);
  display: flex;
  align-items: center;
  justify-content: center;
  color: rgba(255,255,255,0.6);
  transition: var(--transition);
  flex-shrink: 0;
}
.footer__social:hover {
  border-color: var(--primary);
  color: var(--primary);
  background: rgba(91, 167, 255,0.1);
}

.footer__col-title {
  font-size: 0.7rem;
  font-weight: 700;
  letter-spacing: 0.16em;
  text-transform: uppercase;
  color: var(--accent);
  margin-bottom: 1.1rem;
}

.footer__links {
  list-style: none;
  display: flex;
  flex-direction: column;
  gap: 0.6rem;
}
.footer__links a,
.footer__links span {
  font-size: 0.88rem;
  color: rgba(255,255,255,0.6);
  text-decoration: none;
  transition: color var(--transition);
}
.footer__links a:hover { color: var(--white); }

.footer__links--contact li {
  display: flex;
  align-items: flex-start;
  gap: 0.55rem;
  color: rgba(255,255,255,0.6);
}
.footer__links--contact svg { flex-shrink: 0; margin-top: 3px; opacity: 0.55; }

.footer__bottom {
  border-top: 1px solid rgba(255,255,255,0.08);
  padding: 1.1rem 20px;
  max-width: 1200px;
  margin: 0 auto;
  display: flex;
  align-items: center;
  justify-content: space-between;
  font-size: 0.8rem;
  color: rgba(255,255,255,0.4);
  flex-wrap: wrap;
  gap: 0.5rem;
}

.footer__vibey {
  color: var(--primary);
  text-decoration: none;
  font-weight: 600;
  transition: color var(--transition);
}
.footer__vibey:hover { color: var(--pink-300); }

.footer__year-link {
  color: rgba(255,255,255,0.4);
  text-decoration: none;
  transition: color var(--transition);
}
.footer__year-link:hover {
  color: var(--primary);
}

@media (max-width: 900px) {
  .footer__inner {
    grid-template-columns: 1fr 1fr;
    gap: 32px;
  }
  .footer__brand { grid-column: 1 / -1; }
}

@media (max-width: 480px) {
  .footer__inner { grid-template-columns: 1fr; gap: 28px; }
  .footer__bottom { flex-direction: column; text-align: center; }
  .footer__tagline { max-width: 100%; }
}
</style>
