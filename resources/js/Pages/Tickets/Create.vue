<script setup>
import { Link, useForm } from '@inertiajs/vue3';
import AppLayout from '../../Layouts/AppLayout.vue';
import TicketForm from '../../Components/TicketForm.vue';

const props = defineProps({
  users: Array,
  priorities: Array,
  statuses: Array,
  default_opened_at: String,
});

const form = useForm({
  title: '',
  description: '',
  priority: 'medium',
  status: 'open',
  assigned_to: '',
  opened_at: props.default_opened_at,
});

const submit = () => {
  form.post('/tickets');
};
</script>

<template>
  <AppLayout>
    <div class="mb-6">
      <Link href="/tickets" class="text-sm text-indigo-600 hover:underline">&larr; Voltar para lista</Link>
      <h1 class="text-2xl font-bold text-gray-900 mt-2">Novo Chamado</h1>
    </div>

    <TicketForm
      :form="form"
      :users="users"
      :priorities="priorities"
      :statuses="statuses"
      submit-label="Criar chamado"
      cancel-href="/tickets"
      assign-placeholder
      @submit="submit"
    />
  </AppLayout>
</template>
