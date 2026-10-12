<script setup lang="ts">
import { computed } from 'vue'
import { pageItems } from '../utils/pagination'

const props = defineProps<{
  lastPage: number
}>()

const currentPage = defineModel<number>({ required: true })

const items = computed(() => pageItems(currentPage.value, props.lastPage))

function goTo(page: number) {
  currentPage.value = Math.min(Math.max(page, 1), props.lastPage)
}
</script>

<template>
  <nav v-if="lastPage > 1" aria-label="Pagination" class="flex items-center justify-center gap-1 sm:gap-2">
    <button
      type="button"
      class="page-button"
      aria-label="Page précédente"
      :disabled="currentPage <= 1"
      @click="goTo(currentPage - 1)"
    >
      <svg class="size-5" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
        <path
          fill-rule="evenodd"
          d="M12.79 5.23a.75.75 0 0 1-.02 1.06L8.83 10l3.94 3.71a.75.75 0 1 1-1.04 1.08l-4.5-4.25a.75.75 0 0 1 0-1.08l4.5-4.25a.75.75 0 0 1 1.06.02Z"
          clip-rule="evenodd"
        />
      </svg>
    </button>

    <ol class="flex items-center gap-1 sm:gap-2">
      <li v-for="(item, index) in items" :key="item ?? `gap-${index}`">
        <span v-if="item === null" class="flex size-10 items-center justify-center text-muted" aria-hidden="true">…</span>
        <button
          v-else
          type="button"
          class="page-button"
          :aria-label="`Page ${item}`"
          :aria-current="item === currentPage ? 'page' : undefined"
          @click="goTo(item)"
        >
          {{ item }}
        </button>
      </li>
    </ol>

    <button
      type="button"
      class="page-button"
      aria-label="Page suivante"
      :disabled="currentPage >= lastPage"
      @click="goTo(currentPage + 1)"
    >
      <svg class="size-5" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
        <path
          fill-rule="evenodd"
          d="M7.21 14.77a.75.75 0 0 1 .02-1.06L11.17 10 7.23 6.29a.75.75 0 1 1 1.04-1.08l4.5 4.25a.75.75 0 0 1 0 1.08l-4.5 4.25a.75.75 0 0 1-1.06-.02Z"
          clip-rule="evenodd"
        />
      </svg>
    </button>
  </nav>
</template>

<style scoped>
@reference '../style.css';

.page-button {
  @apply flex size-10 items-center justify-center rounded-lg border border-line bg-white text-sm font-bold transition-colors hover:border-brand hover:text-brand focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-brand disabled:cursor-not-allowed disabled:opacity-40 disabled:hover:border-line disabled:hover:text-ink aria-[current=page]:border-brand aria-[current=page]:bg-brand aria-[current=page]:text-white aria-[current=page]:hover:text-white aria-[current=page]:cursor-default;
}
</style>
