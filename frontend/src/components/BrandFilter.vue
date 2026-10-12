<script setup>
import { computed } from 'vue'

const props = defineProps({
  brands: { type: Array, required: true },
})

const selectedBrand = defineModel({ type: String, default: null })

const totalVehicles = computed(() =>
  props.brands.reduce((total, brand) => total + brand.vehicles_count, 0),
)

const options = computed(() => [
  { slug: null, name: 'Toutes les marques', count: totalVehicles.value },
  ...props.brands.map((brand) => ({ slug: brand.slug, name: brand.name, count: brand.vehicles_count })),
])
</script>

<template>
  <nav aria-label="Filtrer par marque">
    <ul class="flex flex-wrap gap-2">
      <li v-for="option in options" :key="option.slug ?? 'all'">
        <button
          type="button"
          class="inline-flex items-center gap-2 rounded-full border px-4 py-2 text-sm font-medium transition-colors focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-blue-700"
          :class="
            option.slug === selectedBrand
              ? 'border-blue-700 bg-blue-700 text-white'
              : 'border-slate-300 bg-white text-slate-700 hover:border-slate-400 hover:bg-slate-100'
          "
          :aria-pressed="option.slug === selectedBrand"
          @click="selectedBrand = option.slug"
        >
          {{ option.name }}
          <span
            class="rounded-full px-2 text-xs"
            :class="option.slug === selectedBrand ? 'bg-white/20' : 'bg-slate-100 text-slate-500'"
          >
            {{ option.count }}
          </span>
        </button>
      </li>
    </ul>
  </nav>
</template>
