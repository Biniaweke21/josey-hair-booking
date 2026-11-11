<template>
  <ReportsPanel title="Daily Report" group-by="day" :default-from="from" :default-to="to" />
</template>

<script setup>
import ReportsPanel from './ReportsPanel.vue'

function iso(d) { return `${d.getFullYear()}-${String(d.getMonth() + 1).padStart(2, '0')}-${String(d.getDate()).padStart(2, '0')}` }
const today = new Date()
const weekAgo = new Date(); weekAgo.setDate(today.getDate() - 29)
const from = iso(weekAgo)
const to = iso(today)
</script>
