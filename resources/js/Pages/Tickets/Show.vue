<script setup>
import { Link } from '@inertiajs/vue3';
import AppLayout from '../../Layouts/AppLayout.vue';

defineProps({
  ticket: Object,
});
</script>

<template>
  <AppLayout>
    <div class="mb-6 flex justify-between items-start">
      <div>
        <Link href="/tickets" class="text-sm text-indigo-600 hover:underline">&larr; Voltar</Link>
        <h1 class="text-2xl font-bold text-gray-900 mt-2">{{ ticket.title }}</h1>
        <p class="text-sm text-gray-500">Criado em {{ ticket.created_at }} • Atualizado em {{ ticket.updated_at }}</p>
      </div>
      <Link :href="`/tickets/${ticket.id}/edit`" class="px-4 py-2 bg-indigo-600 text-white rounded-md text-sm hover:bg-indigo-700">Editar</Link>
    </div>

    <div class="bg-white shadow rounded-lg p-6 space-y-6 max-w-3xl">
      <div>
        <h3 class="text-sm font-medium text-gray-500">Descrição</h3>
        <p class="mt-1 text-sm text-gray-900 whitespace-pre-wrap">{{ ticket.description }}</p>
      </div>

      <div class="grid grid-cols-2 gap-6 pt-4 border-t">
        <div>
          <h3 class="text-sm font-medium text-gray-500">Prioridade</h3>
          <p class="mt-1"><span class="px-2 py-1 rounded-full text-xs font-medium" :class="{ low: 'bg-green-100 text-green-800', medium: 'bg-yellow-100 text-yellow-800', high: 'bg-red-100 text-red-800' }[ticket.priority]">{{ ticket.priority_label }}</span></p>
        </div>
        <div>
          <h3 class="text-sm font-medium text-gray-500">Status</h3>
          <p class="mt-1 text-sm">{{ ticket.status_label }}</p>
        </div>
        <div>
          <h3 class="text-sm font-medium text-gray-500">Responsável</h3>
          <p class="mt-1 text-sm">{{ ticket.assigned_user?.name }} <span class="text-gray-500">({{ ticket.assigned_user?.email }})</span></p>
        </div>
        <div>
          <h3 class="text-sm font-medium text-gray-500">Abertura</h3>
          <p class="mt-1 text-sm">{{ ticket.opened_at }}</p>
        </div>
      </div>
    </div>
  </AppLayout>
</template>
