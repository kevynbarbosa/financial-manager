<template>
  <slot v-if="hasPermission" />
</template>

<script setup lang="ts">
import { computed } from 'vue'
import { usePage } from '@inertiajs/vue3'

interface Props {
  permission?: string
  permissions?: string[]
  role?: string
  roles?: string[]
  requireAll?: boolean
}

const props = withDefaults(defineProps<Props>(), {
  requireAll: false
})

const page = usePage()

const userPermissions = computed(() => {
  return page.props.auth?.user?.permissions || []
})

const userRoles = computed(() => {
  return page.props.auth?.user?.roles || []
})

const hasPermission = computed(() => {
  // Se não há usuário autenticado, não tem permissão
  if (!page.props.auth?.user) {
    return false
  }

  // Verifica permissões
  if (props.permission) {
    return userPermissions.value.includes(props.permission)
  }

  if (props.permissions) {
    if (props.requireAll) {
      return props.permissions.every(permission =>
        userPermissions.value.includes(permission)
      )
    } else {
      return props.permissions.some(permission =>
        userPermissions.value.includes(permission)
      )
    }
  }

  // Verifica roles
  if (props.role) {
    return userRoles.value.includes(props.role)
  }

  if (props.roles) {
    if (props.requireAll) {
      return props.roles.every(role =>
        userRoles.value.includes(role)
      )
    } else {
      return props.roles.some(role =>
        userRoles.value.includes(role)
      )
    }
  }

  // Se nenhuma permissão ou role foi especificada, retorna false
  return false
})
</script>