<script setup>
import { Link } from '@inertiajs/vue3';
import AutoAssignButton from './AutoAssignButton.vue';

/**
 * Corpo do formulário de chamado, compartilhado entre as telas de cadastro e
 * de edição. Antes as duas telas mantinham uma cópia de ~50 linhas cada, que
 * divergiam em detalhes (asterisco da data, placeholder do select, destaque de
 * erro do responsável) e por isso precisavam de duas alterações a cada ajuste.
 *
 * O objeto `form` do Inertia é reativo e chega por referência: o componente
 * muta as propriedades, nunca a prop em si, que é a regra do Vue 3.
 */
defineProps({
  form: { type: Object, required: true },
  users: { type: Array, required: true },
  priorities: { type: Array, required: true },
  statuses: { type: Array, required: true },
  submitLabel: { type: String, required: true },
  cancelHref: { type: String, required: true },
  /** No cadastro o responsável começa vazio e pede escolha explícita. */
  assignPlaceholder: { type: Boolean, default: false },
  /** No cadastro a data já vem preenchida pelo servidor e é opcional. */
  openedAtRequired: { type: Boolean, default: false },
});
</script>

<template>
  <form
    @submit.prevent="$emit('submit')"
    class="bg-white shadow rounded-lg p-6 space-y-6 max-w-3xl"
  >
    <div>
      <label class="block text-sm font-medium text-gray-700">Título *</label>
      <input
        v-model="form.title"
        type="text"
        class="mt-1 w-full border rounded-md px-3 py-2 text-sm"
        :class="{ 'border-red-500': form.errors.title }"
      />
      <p v-if="form.errors.title" class="text-sm text-red-600 mt-1">
        {{ form.errors.title }}
      </p>
    </div>

    <div>
      <label class="block text-sm font-medium text-gray-700">Descrição *</label>
      <textarea
        v-model="form.description"
        rows="4"
        class="mt-1 w-full border rounded-md px-3 py-2 text-sm"
        :class="{ 'border-red-500': form.errors.description }"
      ></textarea>
      <p v-if="form.errors.description" class="text-sm text-red-600 mt-1">
        {{ form.errors.description }}
      </p>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
      <div>
        <label class="block text-sm font-medium text-gray-700">Prioridade *</label>
        <select v-model="form.priority" class="mt-1 w-full border rounded-md px-3 py-2 text-sm">
          <option v-for="p in priorities" :key="p.value" :value="p.value">
            {{ p.label }}
          </option>
        </select>
        <p v-if="form.errors.priority" class="text-sm text-red-600 mt-1">
          {{ form.errors.priority }}
        </p>
      </div>
      <div>
        <label class="block text-sm font-medium text-gray-700">Status *</label>
        <select v-model="form.status" class="mt-1 w-full border rounded-md px-3 py-2 text-sm">
          <option v-for="s in statuses" :key="s.value" :value="s.value">
            {{ s.label }}
          </option>
        </select>
        <p v-if="form.errors.status" class="text-sm text-red-600 mt-1">
          {{ form.errors.status }}
        </p>
      </div>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
      <div>
        <label class="block text-sm font-medium text-gray-700">Responsável *</label>
        <select
          v-model="form.assigned_to"
          class="mt-1 w-full border rounded-md px-3 py-2 text-sm"
          :class="{ 'border-red-500': form.errors.assigned_to }"
        >
          <option v-if="assignPlaceholder" value="" disabled>Selecione um responsável</option>
          <option v-for="u in users" :key="u.id" :value="u.id">
            {{ u.name }} ({{ u.email }})
          </option>
        </select>
        <AutoAssignButton @selected="form.assigned_to = $event" />
        <p v-if="form.errors.assigned_to" class="text-sm text-red-600 mt-1">
          {{ form.errors.assigned_to }}
        </p>
      </div>
      <div>
        <label class="block text-sm font-medium text-gray-700">
          Data de abertura<span v-if="openedAtRequired"> *</span>
        </label>
        <input
          v-model="form.opened_at"
          type="datetime-local"
          class="mt-1 w-full border rounded-md px-3 py-2 text-sm"
          :class="{ 'border-red-500': form.errors.opened_at }"
        />
        <p v-if="form.errors.opened_at" class="text-sm text-red-600 mt-1">
          {{ form.errors.opened_at }}
        </p>
      </div>
    </div>

    <div class="flex justify-end gap-3">
      <Link
        :href="cancelHref"
        class="px-4 py-2 border rounded-md text-sm"
      >
        Cancelar
      </Link>
      <button
        type="submit"
        :disabled="form.processing"
        class="px-6 py-2 bg-indigo-600 text-white rounded-md text-sm hover:bg-indigo-700 disabled:opacity-50"
      >
        {{ submitLabel }}
      </button>
    </div>
  </form>
</template>
