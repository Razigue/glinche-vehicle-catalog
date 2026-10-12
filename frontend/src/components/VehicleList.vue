<script setup>
import VehicleCard from './VehicleCard.vue'

defineProps({
  vehicles: { type: Array, required: true },
})
</script>

<template>
  <ul class="grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
    <li v-for="vehicle in vehicles" :key="vehicle.id">
      <VehicleCard :vehicle="vehicle" class="h-full" />
    </li>
  </ul>
</template>
