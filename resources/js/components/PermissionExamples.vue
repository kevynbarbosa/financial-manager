<template>
  <div class="space-y-6">
    <h2 class="text-2xl font-bold">Exemplos de Uso de Permissões</h2>

    <!-- Usando o componente CanAccess -->
    <div class="space-y-4">
      <h3 class="text-lg font-semibold">Componente CanAccess</h3>

      <!-- Exemplo com permissão única -->
      <CanAccess permission="create-users">
        <button class="px-4 py-2 bg-blue-500 text-white rounded">
          Criar Usuário (permissão: create-users)
        </button>
      </CanAccess>

      <!-- Exemplo com múltiplas permissões (qualquer uma) -->
      <CanAccess :permissions="['edit-users', 'delete-users']">
        <button class="px-4 py-2 bg-yellow-500 text-white rounded">
          Gerenciar Usuários (qualquer permissão de edição/exclusão)
        </button>
      </CanAccess>

      <!-- Exemplo com múltiplas permissões (todas necessárias) -->
      <CanAccess :permissions="['view-reports', 'export-data']" :require-all="true">
        <button class="px-4 py-2 bg-green-500 text-white rounded">
          Exportar Relatórios (precisa de ambas permissões)
        </button>
      </CanAccess>

      <!-- Exemplo com role -->
      <CanAccess role="Admin">
        <button class="px-4 py-2 bg-red-500 text-white rounded">
          Painel Admin (role: Admin)
        </button>
      </CanAccess>

      <!-- Exemplo com múltiplas roles -->
      <CanAccess :roles="['Admin', 'Super Admin']">
        <button class="px-4 py-2 bg-purple-500 text-white rounded">
          Configurações Avançadas (Admin ou Super Admin)
        </button>
      </CanAccess>
    </div>

    <!-- Usando o composable usePermissions -->
    <div class="space-y-4">
      <h3 class="text-lg font-semibold">Composable usePermissions</h3>

      <div class="bg-gray-100 p-4 rounded">
        <p><strong>Usuário:</strong> {{ user?.name || 'Não autenticado' }}</p>
        <p><strong>Roles:</strong> {{ roles.join(', ') || 'Nenhuma' }}</p>
        <p><strong>Permissões:</strong> {{ permissions.slice(0, 5).join(', ') }}{{ permissions.length > 5 ? '...' : '' }}</p>
        <p><strong>É Super Admin:</strong> {{ isSuperAdmin ? 'Sim' : 'Não' }}</p>
        <p><strong>É Admin:</strong> {{ isAdmin ? 'Sim' : 'Não' }}</p>
      </div>

      <!-- Botões condicionais usando o composable -->
      <div class="space-x-2">
        <button
          v-if="hasPermission('create-posts')"
          class="px-4 py-2 bg-blue-500 text-white rounded"
        >
          Criar Post
        </button>

        <button
          v-if="hasAnyRole(['Editor', 'Admin'])"
          class="px-4 py-2 bg-green-500 text-white rounded"
        >
          Editor/Admin Action
        </button>

        <button
          v-if="canAccess({ permissions: ['manage-settings', 'system-config'], requireAll: true })"
          class="px-4 py-2 bg-orange-500 text-white rounded"
        >
          Configurações do Sistema
        </button>
      </div>
    </div>

    <!-- Exemplo programático -->
    <div class="space-y-4">
      <h3 class="text-lg font-semibold">Uso Programático</h3>

      <div class="bg-gray-100 p-4 rounded">
        <pre class="text-sm"><code>// No script setup
import { usePermissions } from '@/composables/usePermissions'

const { hasPermission, hasRole, canAccess } = usePermissions()

// Verificar permissão específica
if (hasPermission('delete-posts')) {
  // fazer algo
}

// Verificar role
if (hasRole('Admin')) {
  // fazer algo
}

// Verificação complexa
if (canAccess({
  permissions: ['view-analytics', 'export-data'],
  requireAll: true
})) {
  // fazer algo
}</code></pre>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import CanAccess from './CanAccess.vue'
import { usePermissions } from '@/composables/usePermissions'

const {
  user,
  permissions,
  roles,
  hasPermission,
  hasAnyRole,
  isSuperAdmin,
  isAdmin,
  canAccess
} = usePermissions()
</script>