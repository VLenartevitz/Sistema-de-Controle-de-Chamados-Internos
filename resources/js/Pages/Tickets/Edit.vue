<script setup>
import { Link, useForm } from '@inertiajs/vue3';
import AppLayout from '../../Layouts/AppLayout.vue';
import TicketForm from '../../Components/TicketForm.vue';

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

    <TicketForm
      :form="form"
      :users="users"
      :priorities="priorities"
      :statuses="statuses"
      submit-label="Salvar alterações"
      :cancel-href="`/tickets/${ticket.id}`"
      opened-at-required
      @submit="submit"
    />
  </AppLayout>
</template>
