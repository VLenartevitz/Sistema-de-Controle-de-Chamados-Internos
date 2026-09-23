<script setup>
import { useForm, Link } from '@inertiajs/vue3';
import AppLayout from '../../Layouts/AppLayout.vue';

const props = defineProps({
  ticket: Object,
  users: Array,
  priorities: Array,
  statuses: Array,
});

const form = useForm({
  title: props.ticket.title,
  description: props.ticket.description,
  priority: props.ticket.priority,
  status: props.ticket.status,
  assigned_to: props.ticket.assigned_to,
  opened_at: props.ticket.opened_at,
});

const submit = () => {
  form.put(`/tickets/${props.ticket.id}`);
};
</script>

<template>
  <AppLayout>
    <div class="mb-6">
      <Link href="/tickets" class="text-sm text-indigo-600 hover:underline">&larr; Voltar</Link>
      <h1 class="text-2xl font-bold text-gray-900 mt-2">Editar Chamado #{{ ticket.id }}</h1>
    </div>

    <form @submit.prevent="submit" class="bg-white shadow rounded-lg p-6 space-y-6 max-w-3xl">
      <div>
        <label class="block text-sm font-medium text-gray-700">Título *</label>
        <input v-model="form.title" type="text" class="mt-1 w-full border rounded-md px-3 py-2 text-sm" :class="{ 'border-red-500': form.errors.title }" />
        <p v-if="form.errors.title" class="text-sm text-red-600 mt-1">{{ form.errors.title }}</p>
      </div>

      <div>
        <label class="block text-sm font-medium text-gray-700">Descrição *</label>
        <textarea v-model="form.description" rows="4" class="mt-1 w-full border rounded-md px-3 py-2 text-sm" :class="{ 'border-red-500': form.errors.description }"></textarea>
        <p v-if="form.errors.description" class="text-sm text-red-600 mt-1">{{ form.errors.description }}</p>
      </div>

      <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <div>
          <label class="block text-sm font-medium text-gray-700">Prioridade *</label>
          <select v-model="form.priority" class="mt-1 w-full border rounded-md px-3 py-2 text-sm">
            <option v-for="p in priorities" :key="p.value" :value="p.value">{{ p.label }}</option>
          </select>
          <p v-if="form.errors.priority" class="text-sm text-red-600 mt-1">{{ form.errors.priority }}</p>
        </div>
        <div>
          <label class="block text-sm font-medium text-gray-700">Status *</label>
          <select v-model="form.status" class="mt-1 w-full border rounded-md px-3 py-2 text-sm">
            <option v-for="s in statuses" :key="s.value" :value="s.value">{{ s.label }}</option>
          </select>
          <p v-if="form.errors.status" class="text-sm text-red-600 mt-1">{{ form.errors.status }}</p>
        </div>
      </div>

      <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <div>
          <label class="block text-sm font-medium text-gray-700">Responsável *</label>
          <select v-model="form.assigned_to" class="mt-1 w-full border rounded-md px-3 py-2 text-sm">
            <option v-for="u in users" :key="u.id" :value="u.id">{{ u.name }} ({{ u.email }})</option>
          </select>
          <p v-if="form.errors.assigned_to" class="text-sm text-red-600 mt-1">{{ form.errors.assigned_to }}</p>
        </div>
        <div>
          <label class="block text-sm font-medium text-gray-700">Data de abertura *</label>
          <input v-model="form.opened_at" type="datetime-local" class="mt-1 w-full border rounded-md px-3 py-2 text-sm" />
          <p v-if="form.errors.opened_at" class="text-sm text-red-600 mt-1">{{ form.errors.opened_at }}</p>
        </div>
      </div>

      <div class="flex justify-end gap-3">
        <Link :href="`/tickets/${ticket.id}`" class="px-4 py-2 border rounded-md text-sm">Cancelar</Link>
        <button type="submit" :disabled="form.processing" class="px-6 py-2 bg-indigo-600 text-white rounded-md text-sm hover:bg-indigo-700 disabled:opacity-50">Salvar alterações</button>
      </div>
    </form>
  </AppLayout>
</template>
