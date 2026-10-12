<template>
  <div class="flex animate-pulse flex-col overflow-hidden rounded-lg border border-line bg-white" aria-hidden="true">
    <div class="aspect-4/3 w-full bg-line/50" />

    <div class="flex flex-1 flex-col gap-3 p-5">
      <div class="flex flex-col gap-2 py-0.5">
        <div class="h-3 w-16 rounded bg-line/70" />
        <div class="h-5 w-2/3 rounded bg-line/70" />
        <div class="h-4 w-1/2 rounded bg-line/50" />
      </div>

      <div class="flex gap-1.5">
        <div v-for="width in ['w-10', 'w-16', 'w-20', 'w-14']" :key="width" class="h-6 rounded bg-brand-light" :class="width" />
      </div>

      <div class="mt-auto flex items-end justify-between gap-2 border-t border-line pt-4">
        <div class="h-7 w-28 rounded bg-line/70" />
        <div class="h-3 w-20 rounded bg-line/50" />
      </div>
    </div>
  </div>
</template>
