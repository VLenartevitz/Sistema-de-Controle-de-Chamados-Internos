<script setup>
import { Link } from '@inertiajs/vue3';
import AppLayout from '../../Layouts/AppLayout.vue';

defineProps({
  tickets: Object,
});

const priorityBadge = (p) => {
  const map = { low: 'bg-green-100 text-green-800', medium: 'bg-yellow-100 text-yellow-800', high: 'bg-red-100 text-red-800' };
  return map[p] || 'bg-gray-100';
};
const statusBadge = (s) => {
  const map = { open: 'bg-blue-100 text-blue-800', in_progress: 'bg-purple-100 text-purple-800', resolved: 'bg-green-100 text-green-800', closed: 'bg-gray-100 text-gray-800' };
  return map[s] || 'bg-gray-100';
};
const priorityLabel = (p) => ({ low: 'Baixa', medium: 'Média', high: 'Alta' }[p] || p);
const statusLabel = (s) => ({ open: 'Aberto', in_progress: 'Em andamento', resolved: 'Resolvido', closed: 'Fechado' }[s] || s);
</script>

<template>
  <AppLayout>
    <div class="flex justify-between items-center mb-6">
      <h1 class="text-2xl font-bold text-gray-900">Chamados</h1>
      <Link href="/tickets/create" class="px-4 py-2 bg-indigo-600 text-white rounded-md text-sm hover:bg-indigo-700">Novo Chamado</Link>
    </div>

    <div v-if="tickets.data.length === 0" class="bg-white rounded-lg shadow p-8 text-center text-gray-500">
      Nenhum chamado cadastrado ainda.
      <div class="mt-4"><Link href="/tickets/create" class="text-indigo-600 hover:underline">Criar primeiro chamado</Link></div>
    </div>

    <div v-else class="bg-white shadow rounded-lg overflow-hidden">
      <table class="min-w-full divide-y divide-gray-200">
        <thead class="bg-gray-50">
          <tr>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Título</th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Prioridade</th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Responsável</th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Abertura</th>
            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Ações</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-gray-200">
          <tr v-for="ticket in tickets.data" :key="ticket.id" class="hover:bg-gray-50">
            <td class="px-6 py-4 text-sm font-medium text-gray-900">{{ ticket.title }}</td>
            <td class="px-6 py-4"><span :class="['px-2 py-1 rounded-full text-xs font-medium', priorityBadge(ticket.priority)]">{{ priorityLabel(ticket.priority) }}</span></td>
            <td class="px-6 py-4"><span :class="['px-2 py-1 rounded-full text-xs font-medium', statusBadge(ticket.status)]">{{ statusLabel(ticket.status) }}</span></td>
            <td class="px-6 py-4 text-sm text-gray-600">{{ ticket.assigned_user?.name }}</td>
            <td class="px-6 py-4 text-sm text-gray-600">{{ new Date(ticket.opened_at).toLocaleDateString('pt-BR') }}</td>
            <td class="px-6 py-4 text-right text-sm space-x-2">
              <Link :href="`/tickets/${ticket.id}`" class="text-indigo-600 hover:underline">Ver</Link>
              <Link :href="`/tickets/${ticket.id}/edit`" class="text-gray-600 hover:underline">Editar</Link>
            </td>
          </tr>
        </tbody>
      </table>

      <div v-if="tickets.links" class="px-6 py-3 bg-gray-50 flex flex-wrap gap-2">
        <template v-for="link in tickets.links" :key="link.label">
          <Link v-if="link.url" :href="link.url" v-html="link.label" :class="['px-3 py-1 rounded text-sm', link.active ? 'bg-indigo-600 text-white' : 'bg-white border']" />
          <span v-else v-html="link.label" class="px-3 py-1 rounded text-sm bg-gray-100 text-gray-400" />
        </template>
      </div>
    </div>
  </AppLayout>
</template>
