<script setup lang="ts">
import type { HTMLAttributes } from 'vue'
import { cn } from '@/lib/utils'
import { Link } from '@inertiajs/vue3'

interface Props {
  href?: string
  active?: boolean
  disabled?: boolean
  class?: HTMLAttributes['class']
  only?: string[]
  preserveState?: boolean
  preserveScroll?: boolean
  replace?: boolean
  data?: Record<string, unknown>
}

const props = defineProps<Props>()
</script>

<template>
  <li>
    <Link
      v-if="href && !disabled"
      :href="href"
      :only="only"
      :data="data"
      :preserve-state="preserveState"
      :preserve-scroll="preserveScroll"
      :replace="replace"
      :class="cn(
        'inline-flex items-center justify-center whitespace-nowrap rounded-md text-sm font-medium ring-offset-background transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:pointer-events-none disabled:opacity-50',
        'hover:bg-accent hover:text-accent-foreground h-10 px-4 py-2',
        active && 'bg-primary text-primary-foreground hover:bg-primary/90',
        props.class
      )"
    >
      <slot />
    </Link>
    <span
      v-else
      :class="cn(
        'inline-flex items-center justify-center whitespace-nowrap rounded-md text-sm font-medium ring-offset-background transition-colors',
        'h-10 px-4 py-2',
        disabled && 'pointer-events-none opacity-50',
        active && 'bg-primary text-primary-foreground',
        !active && !disabled && 'hover:bg-accent hover:text-accent-foreground',
        props.class
      )"
    >
      <slot />
    </span>
  </li>
</template>
