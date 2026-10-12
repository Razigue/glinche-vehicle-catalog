<script setup lang="ts">
import { computed, onBeforeUnmount, onMounted, ref, useId, useTemplateRef } from 'vue'
import type { Brand } from '../types'

const props = defineProps<{
  brands: Brand[]
}>()

const selectedSlugs = defineModel<string[]>({ default: () => [] })

const panelId = useId()
const open = ref(false)
const root = useTemplateRef('root')
const trigger = useTemplateRef('trigger')

const selectedBrands = computed(() => props.brands.filter((brand) => selectedSlugs.value.includes(brand.slug)))

const summary = computed(() => {
  if (selectedBrands.value.length === 0) {
    return 'Toutes les marques'
  }

  return selectedBrands.value.length === 1 ? selectedBrands.value[0].name : `${selectedBrands.value.length} marques`
})

function toggle(slug: string) {
  const slugs = new Set(selectedSlugs.value)
  slugs.has(slug) ? slugs.delete(slug) : slugs.add(slug)
  selectedSlugs.value = props.brands.map((brand) => brand.slug).filter((candidate) => slugs.has(candidate))
}

function clear() {
  selectedSlugs.value = []
}

function close({ restoreFocus = false } = {}) {
  open.value = false
  if (restoreFocus) {
    trigger.value?.focus()
  }
}

function closeOnOutsideClick(event: PointerEvent) {
  if (!root.value?.contains(event.target as Node)) {
    close()
  }
}

onMounted(() => document.addEventListener('pointerdown', closeOnOutsideClick))
onBeforeUnmount(() => document.removeEventListener('pointerdown', closeOnOutsideClick))
</script>

<template>
  <div class="flex flex-wrap items-center gap-2">
    <div ref="root" class="relative w-full sm:w-auto" @keydown.esc="close({ restoreFocus: true })">
      <button
        ref="trigger"
        type="button"
        class="inline-flex h-11 w-full min-w-56 items-center justify-between gap-3 rounded-lg border bg-white px-4 text-sm transition-colors hover:border-brand focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-brand"
        :class="open ? 'border-brand' : 'border-line'"
        :aria-expanded="open"
        :aria-controls="panelId"
        @click="open = !open"
      >
        <span class="flex gap-1">
          <span class="text-muted">Marque&nbsp;:</span>
          <span class="font-bold">{{ summary }}</span>
        </span>
        <svg
          class="size-4 text-muted transition-transform"
          :class="{ 'rotate-180': open }"
          viewBox="0 0 20 20"
          fill="currentColor"
          aria-hidden="true"
        >
          <path
            fill-rule="evenodd"
            d="M5.23 7.21a.75.75 0 0 1 1.06.02L10 11.17l3.71-3.94a.75.75 0 1 1 1.08 1.04l-4.25 4.5a.75.75 0 0 1-1.08 0l-4.25-4.5a.75.75 0 0 1 .02-1.06Z"
            clip-rule="evenodd"
          />
        </svg>
      </button>

      <div
        v-show="open"
        :id="panelId"
        class="absolute left-0 z-10 mt-2 w-full min-w-64 rounded-lg border border-line bg-white py-2"
      >
        <fieldset>
          <legend class="sr-only">Filtrer par marque</legend>
          <label
            v-for="brand in brands"
            :key="brand.slug"
            class="flex cursor-pointer items-center gap-3 px-4 py-2 text-sm hover:bg-brand-light"
          >
            <input
              type="checkbox"
              class="size-4 accent-brand"
              :checked="selectedSlugs.includes(brand.slug)"
              @change="toggle(brand.slug)"
            />
            <span class="flex-1">{{ brand.name }}</span>
            <span class="text-xs text-muted">{{ brand.vehicles_count }}</span>
          </label>
        </fieldset>
        <div class="mt-2 border-t border-line px-4 pt-2">
          <button
            type="button"
            class="text-sm font-bold text-brand hover:underline disabled:cursor-not-allowed disabled:text-muted disabled:no-underline"
            :disabled="selectedSlugs.length === 0"
            @click="clear"
          >
            Tout effacer
          </button>
        </div>
      </div>
    </div>

    <ul v-if="selectedBrands.length > 0" class="flex flex-wrap gap-2" aria-label="Marques sélectionnées">
      <li v-for="brand in selectedBrands" :key="brand.slug">
        <button
          type="button"
          class="inline-flex h-8 items-center gap-1.5 rounded-full bg-brand-light pr-2.5 pl-3 text-sm font-bold text-brand transition-colors hover:bg-brand hover:text-white focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-brand"
          :aria-label="`Retirer ${brand.name}`"
          @click="toggle(brand.slug)"
        >
          {{ brand.name }}
          <svg class="size-3.5" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
            <path
              d="M6.28 5.22a.75.75 0 0 0-1.06 1.06L8.94 10l-3.72 3.72a.75.75 0 1 0 1.06 1.06L10 11.06l3.72 3.72a.75.75 0 1 0 1.06-1.06L11.06 10l3.72-3.72a.75.75 0 0 0-1.06-1.06L10 8.94 6.28 5.22Z"
            />
          </svg>
        </button>
      </li>
    </ul>
  </div>
</template>
