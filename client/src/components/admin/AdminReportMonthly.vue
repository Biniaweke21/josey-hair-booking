<template>
  <ReportsPanel title="Monthly Report" group-by="month" :default-from="from" :default-to="to" />
</template>

<script setup>
import ReportsPanel from './ReportsPanel.vue'

const now = new Date()
const from = `${now.getFullYear()}-01-01`
const to = `${now.getFullYear()}-12-31`
</script>
