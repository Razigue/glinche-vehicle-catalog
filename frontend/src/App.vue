<script setup lang="ts">
import { computed, useTemplateRef, watch } from 'vue'
import BrandFilter from './components/BrandFilter.vue'
import PaginationNav from './components/PaginationNav.vue'
import logoUrl from './assets/glinche-automobiles-logo.svg'
import VehicleCardSkeleton from './components/VehicleCardSkeleton.vue'
import VehicleList from './components/VehicleList.vue'
import { useBrands } from './composables/useBrands'
import { useUrlQuery } from './composables/useUrlQuery'
import { useVehicles } from './composables/useVehicles'
import { errorMessage } from './utils/errorMessage'

const { brands: brandSlugs, page, replace: replaceQuery } = useUrlQuery()
const { brands, loading: brandsLoading, error: brandsError, reload: reloadBrands } = useBrands()
const { vehicles, pagination, loading, error, reload } = useVehicles(brandSlugs, page)

const selectedBrands = computed({
  get: () => brandSlugs.value,
  set: (slugs) => {
    brandSlugs.value = slugs
    page.value = 1
  },
})

const selectedBrandNames = computed(() =>
  new Intl.ListFormat('fr', { type: 'disjunction' }).format(
    brands.value.filter((candidate) => brandSlugs.value.includes(candidate.slug)).map((candidate) => candidate.name),
  ),
)

const stickyHeader = useTemplateRef('stickyHeader')
const results = useTemplateRef('results')

const isFirstLoad = computed(() => loading.value && pagination.value === null)

const resultsLabel = computed(() => {
  const total = pagination.value?.total ?? 0

  return `${total} ${total > 1 ? 'véhicules disponibles' : 'véhicule disponible'}`
})

watch(brands, (availableBrands) => {
  const knownSlugs = brandSlugs.value.filter((slug) => availableBrands.some((candidate) => candidate.slug === slug))

  if (knownSlugs.length < brandSlugs.value.length) {
    replaceQuery({ brands: knownSlugs, page: 1 })
  }
})

watch(pagination, (meta) => {
  if (meta && page.value > meta.last_page) {
    replaceQuery({ page: meta.last_page })
  }
})

watch(page, () => {
  if (!results.value || !stickyHeader.value) {
    return
  }

  const hiddenBy = results.value.getBoundingClientRect().top - stickyHeader.value.offsetHeight - 24

  if (hiddenBy < 0) {
    window.scrollBy({ top: hiddenBy })
  }
})
</script>

<template>
  <div ref="stickyHeader" class="sticky top-0 z-20">
    <header class="bg-brand text-white">
      <div class="mx-auto flex h-14 max-w-6xl items-center gap-4 px-4 sm:h-16 sm:gap-6">
        <img :src="logoUrl" alt="Glinche Automobiles" class="h-7 w-auto brightness-0 invert sm:h-9" />
        <span class="h-7 w-px bg-white/30" aria-hidden="true" />
        <h1 class="text-base font-bold sm:text-lg">Nos véhicules</h1>
      </div>
    </header>

    <section class="border-b border-line bg-white" aria-label="Filtres">
      <div class="mx-auto flex max-w-6xl flex-col gap-2 px-4 py-3">
        <div class="flex items-center justify-between gap-4">
          <BrandFilter v-if="brands.length > 0" v-model="selectedBrands" :brands="brands" class="w-full sm:w-auto" />
          <div
            v-else-if="brandsLoading"
            class="h-11 w-full animate-pulse rounded-lg bg-line/50 sm:w-56"
            role="status"
            aria-label="Chargement des marques"
          />
          <p v-if="pagination && !error" class="hidden shrink-0 text-sm text-muted sm:block" aria-live="polite">
            {{ resultsLabel }}
          </p>
        </div>
        <p v-if="brandsError" class="text-sm text-red-700">
          Les marques n'ont pas pu être chargées. {{ errorMessage(brandsError) }}
          <button type="button" class="font-bold underline" @click="reloadBrands">Réessayer</button>
        </p>
      </div>
    </section>
  </div>

  <main class="mx-auto flex max-w-6xl flex-col gap-6 px-4 py-6 sm:py-8">

    <div v-if="error" role="alert" class="rounded-lg border border-red-200 bg-red-50 p-6 text-center">
      <p class="font-bold text-red-800">Les véhicules n'ont pas pu être chargés.</p>
      <p class="mt-1 text-sm text-red-700">{{ errorMessage(error) }}</p>
      <button
        type="button"
        class="mt-3 rounded-lg bg-brand px-4 py-2 text-sm font-bold text-white hover:bg-brand-dark"
        @click="reload"
      >
        Réessayer
      </button>
    </div>

    <ul v-else-if="isFirstLoad" class="grid gap-6 sm:grid-cols-2 lg:grid-cols-3" aria-label="Chargement des véhicules">
      <li v-for="placeholder in 6" :key="placeholder">
        <VehicleCardSkeleton class="h-full" />
      </li>
    </ul>

    <p
      v-else-if="vehicles.length === 0"
      class="rounded-lg border border-dashed border-line bg-white p-10 text-center text-muted"
    >
      {{
        selectedBrandNames
          ? `Aucun véhicule ${selectedBrandNames} n'est disponible pour le moment.`
          : "Aucun véhicule n'est disponible pour le moment."
      }}
    </p>

    <section v-else-if="pagination" ref="results" class="flex flex-col gap-6" :aria-busy="loading">
      <p class="-mb-2 text-sm text-muted sm:hidden">{{ resultsLabel }}</p>
      <div class="transition-opacity duration-200" :class="{ 'opacity-50 delay-200': loading }">
        <Transition mode="out-in" enter-from-class="opacity-0" enter-active-class="transition-opacity duration-200 ease-out">
          <VehicleList :key="vehicles.map((vehicle) => vehicle.id).join()" :vehicles="vehicles" />
        </Transition>
      </div>
      <PaginationNav v-model="page" :last-page="pagination.last_page" />
    </section>
  </main>
</template>
