<script setup lang="ts">
import { computed, watch } from 'vue'
import BrandFilter from './components/BrandFilter.vue'
import PaginationNav from './components/PaginationNav.vue'
import VehicleList from './components/VehicleList.vue'
import { useBrands } from './composables/useBrands'
import { useUrlQuery } from './composables/useUrlQuery'
import { useVehicles } from './composables/useVehicles'

const { brand, page } = useUrlQuery()
const { brands, error: brandsError, reload: reloadBrands } = useBrands()
const { vehicles, pagination, loading, error, reload } = useVehicles(brand, page)

const selectedBrand = computed({
  get: () => brand.value,
  set: (slug) => {
    brand.value = slug
    page.value = 1
  },
})

const selectedBrandName = computed(
  () => brands.value.find((candidate) => candidate.slug === brand.value)?.name,
)

const isFirstLoad = computed(() => loading.value && pagination.value === null)

const resultsLabel = computed(() => {
  const total = pagination.value?.total ?? 0

  return `${total} ${total > 1 ? 'véhicules disponibles' : 'véhicule disponible'}`
})

watch(brands, (availableBrands) => {
  if (brand.value && !availableBrands.some((candidate) => candidate.slug === brand.value)) {
    selectedBrand.value = null
  }
})

watch(pagination, (meta) => {
  if (meta && page.value > meta.last_page) {
    page.value = meta.last_page
  }
})

watch(page, () => window.scrollTo({ top: 0, behavior: 'smooth' }))
</script>

<template>
  <header class="border-b border-slate-200 bg-white">
    <div class="mx-auto max-w-6xl px-4 py-6">
      <p class="text-sm font-semibold tracking-wide text-blue-700 uppercase">Glinche Automobiles</p>
      <h1 class="text-2xl font-bold sm:text-3xl">Nos véhicules</h1>
    </div>
  </header>

  <main class="mx-auto flex max-w-6xl flex-col gap-6 px-4 py-8">
    <section class="flex flex-col gap-2">
      <BrandFilter v-if="brands.length > 0" v-model="selectedBrand" :brands="brands" />
      <p v-if="brandsError" class="text-sm text-red-700">
        Les marques n'ont pas pu être chargées.
        <button type="button" class="font-medium underline" @click="reloadBrands">Réessayer</button>
      </p>
    </section>

    <div v-if="error" role="alert" class="rounded-xl border border-red-200 bg-red-50 p-6 text-center">
      <p class="font-medium text-red-800">Les véhicules n'ont pas pu être chargés.</p>
      <button
        type="button"
        class="mt-3 rounded-lg bg-red-700 px-4 py-2 text-sm font-medium text-white hover:bg-red-800"
        @click="reload"
      >
        Réessayer
      </button>
    </div>

    <ul v-else-if="isFirstLoad" class="grid gap-6 sm:grid-cols-2 lg:grid-cols-3" aria-label="Chargement des véhicules">
      <li v-for="placeholder in 6" :key="placeholder" class="h-96 animate-pulse rounded-xl bg-slate-200" />
    </ul>

    <p
      v-else-if="vehicles.length === 0"
      class="rounded-xl border border-dashed border-slate-300 bg-white p-10 text-center text-slate-600"
    >
      {{
        selectedBrandName
          ? `Aucun véhicule ${selectedBrandName} n'est disponible pour le moment.`
          : "Aucun véhicule n'est disponible pour le moment."
      }}
    </p>

    <section v-else-if="pagination" class="flex flex-col gap-6" :aria-busy="loading">
      <p class="text-sm text-slate-600" aria-live="polite">{{ resultsLabel }}</p>
      <VehicleList :vehicles="vehicles" class="transition-opacity" :class="{ 'opacity-50': loading }" />
      <PaginationNav v-model="page" :last-page="pagination.last_page" />
    </section>
  </main>
</template>
