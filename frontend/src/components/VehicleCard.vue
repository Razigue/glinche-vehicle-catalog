<script setup>
import { computed, ref } from 'vue'
import placeholderUrl from '../assets/vehicle-placeholder.svg'
import { formatMileage, formatPrice } from '../utils/format'

const props = defineProps({
  vehicle: { type: Object, required: true },
})

const failedPhotoUrl = ref(null)

const photoUrl = computed(() =>
  props.vehicle.photo_url && props.vehicle.photo_url !== failedPhotoUrl.value
    ? props.vehicle.photo_url
    : placeholderUrl,
)

const price = computed(() => formatPrice(props.vehicle.price_in_cents))

const specifications = computed(() =>
  [
    props.vehicle.year,
    formatMileage(props.vehicle.mileage),
    props.vehicle.energy,
    props.vehicle.gearbox,
  ].filter(Boolean),
)
</script>

<template>
  <article class="flex flex-col overflow-hidden rounded-lg border border-line bg-white transition-colors hover:border-brand">
    <img
      :src="photoUrl"
      :alt="`${vehicle.brand.name} ${vehicle.model}`"
      class="aspect-4/3 w-full bg-surface object-cover"
      loading="lazy"
      @error="failedPhotoUrl = vehicle.photo_url"
    />

    <div class="flex flex-1 flex-col gap-3 p-5">
      <header>
        <p class="text-xs font-bold tracking-widest text-brand uppercase">{{ vehicle.brand.name }}</p>
        <h2 class="text-lg font-bold">{{ vehicle.model }}</h2>
        <p v-if="vehicle.version" class="text-sm text-muted">{{ vehicle.version }}</p>
      </header>

      <ul class="flex flex-wrap gap-1.5 text-xs text-ink">
        <li v-for="specification in specifications" :key="specification" class="rounded bg-brand-light px-2 py-1">
          {{ specification }}
        </li>
      </ul>

      <footer class="mt-auto flex items-end justify-between gap-2 border-t border-line pt-4">
        <p class="text-xl font-black text-brand">
          <template v-if="price">
            {{ price }} <span class="text-sm font-normal text-muted">TTC</span>
          </template>
          <span v-else class="text-base font-bold text-muted">Prix sur demande</span>
        </p>
        <p class="text-xs text-muted">Réf. {{ vehicle.reference }}</p>
      </footer>
    </div>
  </article>
</template>
