<template>
  <header class="navbar" :class="{ 'navbar--scrolled': scrolled }">
    <div class="navbar__inner">

      <RouterLink to="/" class="navbar__logo">
        <img src="/images/josey.jpg" :alt="salonName" class="navbar__logo-img" />
        <span class="logo-text">{{ salonName }}</span>
      </RouterLink>

      <!-- Desktop Nav -->
      <nav class="navbar__nav" role="navigation" aria-label="Main navigation">
        <RouterLink to="/" class="navbar__link">Home</RouterLink>
        <RouterLink to="/services" class="navbar__link">Services</RouterLink>
        <RouterLink to="/gallery" class="navbar__link">Gallery</RouterLink>
        <RouterLink to="/contact" class="navbar__link">Contact</RouterLink>
        <RouterLink to="/booking" class="navbar__book btn btn-primary">
          <CalendarHeart :size="16" aria-hidden="true" />
          Book Now
        </RouterLink>
      </nav>

      <!-- Hamburger -->
      <button
        class="navbar__hamburger"
        :class="{ 'is-open': menuOpen }"
        @click="menuOpen = !menuOpen"
        aria-label="Toggle menu"
        :aria-expanded="String(menuOpen)"
      >
        <component :is="menuOpen ? X : Menu" :size="24" aria-hidden="true" />
      </button>
    </div>

    <!-- Mobile Menu -->
    <Transition name="mobile-menu">
      <div v-if="menuOpen" class="navbar__mobile" role="dialog" aria-label="Mobile navigation">
        <RouterLink to="/" class="navbar__mobile-link" @click="menuOpen = false">
          <Home :size="17" aria-hidden="true" /> Home
        </RouterLink>
        <RouterLink to="/services" class="navbar__mobile-link" @click="menuOpen = false">
          <Sparkles :size="17" aria-hidden="true" /> Services
        </RouterLink>
        <RouterLink to="/gallery" class="navbar__mobile-link" @click="menuOpen = false">
          <Images :size="17" aria-hidden="true" /> Gallery
        </RouterLink>
        <RouterLink to="/contact" class="navbar__mobile-link" @click="menuOpen = false">
          <Phone :size="17" aria-hidden="true" /> Contact
        </RouterLink>
        <RouterLink to="/booking" class="btn btn-primary navbar__mobile-book" @click="menuOpen = false">
          <CalendarHeart :size="17" aria-hidden="true" /> Book Appointment
        </RouterLink>
      </div>
    </Transition>
  </header>
</template>

<script setup>
import { ref, onMounted, onUnmounted } from 'vue'
import { RouterLink } from 'vue-router'
import { Sparkles, CalendarHeart, Menu, X, Home, Images, Phone } from 'lucide-vue-next'
import { useSettings } from '@/composables/useSettings'

const { salonName } = useSettings()
const scrolled  = ref(false)
const menuOpen  = ref(false)

function onScroll() { scrolled.value = window.scrollY > 36 }
onMounted(()  => window.addEventListener('scroll', onScroll, { passive: true }))
onUnmounted(() => window.removeEventListener('scroll', onScroll))
</script>

<style scoped>
.navbar {
  position: fixed;
  top: 0; left: 0; right: 0;
  z-index: 200;
  height: var(--navbar-h);
  transition: background 0.4s ease, box-shadow 0.4s ease;
  /* never wider than viewport */
  width: 100%;
  max-width: 100vw;
}

.navbar--scrolled {
  background: rgba(255,255,255,0.97);
  backdrop-filter: blur(14px);
  box-shadow: 0 2px 20px rgba(91, 167, 255,0.10);
}

.navbar__inner {
  max-width: 1200px;
  margin: 0 auto;
  padding: 0 20px;
  height: 100%;
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 1rem;
}

/* Logo */
.navbar__logo {
  display: flex;
  align-items: center;
  gap: 0.35rem;
  flex-shrink: 0;
}

.logo-text {
  font-family: var(--font-heading);
  font-size: 1.45rem;
  font-weight: 700;
  color: var(--white);
  transition: color var(--transition);
  letter-spacing: -0.01em;
  white-space: nowrap;
}
.navbar__logo-img {
  width: 34px; height: 34px;
  border-radius: 50%;
  object-fit: cover;
  flex-shrink: 0;
  border: 1.5px solid rgba(255,255,255,0.6);
}
.navbar--scrolled .navbar__logo-img { border-color: var(--border); }

.navbar--scrolled .logo-text { color: var(--charcoal); }

/* Desktop nav */
.navbar__nav {
  display: flex;
  align-items: center;
  gap: 1.75rem;
}

.navbar__link {
  font-size: 0.92rem;
  font-weight: 500;
  color: rgba(255,255,255,0.88);
  text-decoration: none;
  transition: color var(--transition);
  position: relative;
  white-space: nowrap;
}
.navbar__link::after {
  content: '';
  position: absolute;
  bottom: -3px; left: 0;
  width: 0; height: 1.5px;
  background: var(--primary);
  transition: width var(--transition);
}
.navbar__link:hover::after,
.navbar__link.router-link-active::after { width: 100%; }
.navbar__link:hover,
.navbar__link.router-link-active { color: var(--white); }

.navbar--scrolled .navbar__link { color: var(--charcoal-lt); }
.navbar--scrolled .navbar__link:hover,
.navbar--scrolled .navbar__link.router-link-active { color: var(--charcoal); }

.navbar__book {
  padding: 0.55rem 1.3rem;
  font-size: 0.87rem;
  flex-shrink: 0;
}

/* Hamburger */
.navbar__hamburger {
  display: none;
  align-items: center;
  justify-content: center;
  width: 40px; height: 40px;
  background: none;
  border: none;
  cursor: pointer;
  color: var(--white);
  flex-shrink: 0;
  transition: color var(--transition);
}
.navbar--scrolled .navbar__hamburger { color: var(--charcoal); }

/* Mobile menu */
.navbar__mobile {
  position: absolute;
  top: var(--navbar-h);
  left: 0; right: 0;
  background: var(--white);
  display: flex;
  flex-direction: column;
  padding: 1.25rem 20px 1.75rem;
  gap: 0;
  box-shadow: 0 8px 32px rgba(91, 167, 255,0.12);
  border-top: 1px solid var(--border);
  /* never exceed viewport */
  width: 100%;
  max-width: 100vw;
}

.navbar__mobile-link {
  display: flex;
  align-items: center;
  gap: 0.65rem;
  font-size: 1rem;
  font-weight: 500;
  color: var(--charcoal);
  padding: 0.9rem 0;
  border-bottom: 1px solid rgba(91, 167, 255,0.08);
  text-decoration: none;
  transition: color var(--transition);
}
.navbar__mobile-link:hover { color: var(--primary); }
.navbar__mobile-link svg { color: var(--primary); flex-shrink: 0; }

.navbar__mobile-book {
  margin-top: 1.25rem;
  justify-content: center;
  width: 100%;
}

/* Transitions */
.mobile-menu-enter-active,
.mobile-menu-leave-active { transition: opacity 0.22s ease, transform 0.22s ease; }
.mobile-menu-enter-from,
.mobile-menu-leave-to { opacity: 0; transform: translateY(-6px); }

@media (max-width: 768px) {
  .navbar__nav     { display: none; }
  .navbar__hamburger { display: flex; }
}

@media (max-width: 360px) {
  .logo-text { font-size: 1.2rem; }
}
</style>
