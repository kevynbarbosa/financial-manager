<script setup lang="ts">
import { cn } from '@/lib/utils';
import type { HTMLAttributes } from 'vue';

const props = withDefaults(defineProps<{
  class?: HTMLAttributes['class']
  gradientBorder?: boolean
}>(), {
  gradientBorder: true,
})
</script>

<template>
  <div
    data-slot="card"
    :class="
      cn(
        'bg-card text-card-foreground flex flex-col gap-6 rounded-xl border py-6 shadow-sm',
        props.gradientBorder && 'card-gradient-border',
        props.class,
      )
    "
  >
    <slot />
  </div>
</template>
