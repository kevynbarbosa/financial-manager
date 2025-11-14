import { computed } from 'vue'
import { usePage } from '@inertiajs/vue3'

export function usePermissions() {
  const page = usePage()

  const user = computed(() => page.props.auth?.user)
  const permissions = computed(() => user.value?.permissions || [])
  const roles = computed(() => user.value?.roles || [])

  const hasPermission = (permission: string): boolean => {
    return permissions.value.includes(permission)
  }

  const hasAnyPermission = (permissionList: string[]): boolean => {
    return permissionList.some(permission => permissions.value.includes(permission))
  }

  const hasAllPermissions = (permissionList: string[]): boolean => {
    return permissionList.every(permission => permissions.value.includes(permission))
  }

  const hasRole = (role: string): boolean => {
    return roles.value.includes(role)
  }

  const hasAnyRole = (roleList: string[]): boolean => {
    return roleList.some(role => roles.value.includes(role))
  }

  const hasAllRoles = (roleList: string[]): boolean => {
    return roleList.every(role => roles.value.includes(role))
  }

  const isSuperAdmin = computed(() => {
    return hasRole('Super Admin')
  })

  const isAdmin = computed(() => {
    return hasRole('Admin') || hasRole('Super Admin')
  })

  const canAccess = (options: {
    permission?: string
    permissions?: string[]
    role?: string
    roles?: string[]
    requireAll?: boolean
  }): boolean => {
    const { permission, permissions, role, roles, requireAll = false } = options

    if (!user.value) return false

    if (permission) {
      return hasPermission(permission)
    }

    if (permissions) {
      return requireAll ? hasAllPermissions(permissions) : hasAnyPermission(permissions)
    }

    if (role) {
      return hasRole(role)
    }

    if (roles) {
      return requireAll ? hasAllRoles(roles) : hasAnyRole(roles)
    }

    return false
  }

  return {
    user,
    permissions,
    roles,
    hasPermission,
    hasAnyPermission,
    hasAllPermissions,
    hasRole,
    hasAnyRole,
    hasAllRoles,
    isSuperAdmin,
    isAdmin,
    canAccess
  }
}