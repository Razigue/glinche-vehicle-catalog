<script setup lang="ts">
const props = defineProps<{
  lastPage: number
}>()

const currentPage = defineModel<number>({ required: true })

function goTo(page: number) {
  currentPage.value = Math.min(Math.max(page, 1), props.lastPage)
}
</script>

<template>
  <nav v-if="lastPage > 1" aria-label="Pagination" class="flex items-center justify-center gap-4">
    <button
      type="button"
      class="rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-medium hover:bg-slate-100 disabled:cursor-not-allowed disabled:opacity-40"
      :disabled="currentPage <= 1"
      @click="goTo(currentPage - 1)"
    >
      Précédent
    </button>
    <p class="text-sm text-slate-600" aria-live="polite">Page {{ currentPage }} sur {{ lastPage }}</p>
    <button
      type="button"
      class="rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-medium hover:bg-slate-100 disabled:cursor-not-allowed disabled:opacity-40"
      :disabled="currentPage >= lastPage"
      @click="goTo(currentPage + 1)"
    >
      Suivant
    </button>
  </nav>
</template>
