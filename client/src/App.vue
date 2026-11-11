<template>
  <div id="app">
    <template v-if="!isAdmin">
      <TheNavbar />
      <main>
        <RouterView />
      </main>
      <TheFooter />
      <button class="sticky-book-btn" @click="$router.push('/booking')">
        <CalendarHeart :size="16" aria-hidden="true" /> Book Now
      </button>
    </template>
    <template v-else>
      <RouterView />
    </template>
  </div>
</template>

<script setup>
import { computed } from 'vue'
import { RouterView, useRoute } from 'vue-router'
import { CalendarHeart } from 'lucide-vue-next'
import TheNavbar from '@/components/TheNavbar.vue'
import TheFooter from '@/components/TheFooter.vue'

const route = useRoute()
const isAdmin = computed(() => route.meta.hideLayout)
</script>
