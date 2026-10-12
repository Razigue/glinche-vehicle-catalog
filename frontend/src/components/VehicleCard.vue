<script setup lang="ts">
import { computed, ref } from 'vue'
import placeholderUrl from '../assets/vehicle-placeholder.svg'
import type { Vehicle } from '../types'
import { formatMileage, formatPrice } from '../utils/format'

const props = defineProps<{
  vehicle: Vehicle
}>()

const hasPhotoFailed = ref(false)

const photoUrl = computed(() =>
  props.vehicle.photo_url && !hasPhotoFailed.value ? props.vehicle.photo_url : placeholderUrl,
)

const price = computed(() => formatPrice(props.vehicle.price_in_cents))

const specifications = computed(() =>
  [
    props.vehicle.year,
    formatMileage(props.vehicle.mileage),
    props.vehicle.energy,
    props.vehicle.gearbox,
  ].filter((specification) => specification !== null),
)
</script>

<template>
  <article class="flex flex-col overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
    <img
      :src="photoUrl"
      :alt="`${vehicle.brand.name} ${vehicle.model}`"
      class="aspect-4/3 w-full bg-slate-100 object-cover"
      loading="lazy"
      @error="hasPhotoFailed = true"
    />

    <div class="flex flex-1 flex-col gap-3 p-4">
      <header>
        <p class="text-xs font-semibold tracking-wide text-blue-700 uppercase">{{ vehicle.brand.name }}</p>
        <h2 class="text-lg font-semibold">{{ vehicle.model }}</h2>
        <p v-if="vehicle.version" class="text-sm text-slate-600">{{ vehicle.version }}</p>
      </header>

      <ul class="flex flex-wrap gap-1.5 text-xs text-slate-600">
        <li v-for="specification in specifications" :key="specification" class="rounded bg-slate-100 px-2 py-1">
          {{ specification }}
        </li>
      </ul>

      <footer class="mt-auto flex items-end justify-between gap-2 pt-2">
        <p class="text-xl font-bold">
          <template v-if="price">
            {{ price }} <span class="text-sm font-normal text-slate-500">TTC</span>
          </template>
          <span v-else class="text-base font-medium text-slate-500">Prix sur demande</span>
        </p>
        <p class="text-xs text-slate-400">Réf. {{ vehicle.reference }}</p>
      </footer>
    </div>
  </article>
</template>
