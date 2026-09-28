<script setup>
import { ref, watch, computed } from 'vue';
import { Link, router } from '@inertiajs/vue3';
import AppLayout from '../../Layouts/AppLayout.vue';

const props = defineProps({
  tickets: Object,
  filters: Object,
  users: Array,
  priorities: Array,
  statuses: Array,
});

const form = ref({
  search: props.filters?.search || '',
  priority: props.filters?.priority || '',
  status: props.filters?.status || '',
  assigned_to: props.filters?.assigned_to ? String(props.filters.assigned_to) : '',
  opened_from: props.filters?.opened_from || '',
  opened_to: props.filters?.opened_to || '',
  sort: props.filters?.sort || 'created_at',
  direction: props.filters?.direction || 'desc',
});

const hasActiveFilters = computed(() => {
  return !!(form.value.search || form.value.priority || form.value.status || form.value.assigned_to || form.value.opened_from || form.value.opened_to);
});

let searchTimeout = null;

const applyFilters = () => {
  const params = {};
  if (form.value.search) params.search = form.value.search;
  if (form.value.priority) params.priority = form.value.priority;
  if (form.value.status) params.status = form.value.status;
  if (form.value.assigned_to) params.assigned_to = form.value.assigned_to;
  if (form.value.opened_from) params.opened_from = form.value.opened_from;
  if (form.value.opened_to) params.opened_to = form.value.opened_to;
  if (form.value.sort && form.value.sort !== 'created_at') params.sort = form.value.sort;
  if (form.value.direction && form.value.direction !== 'desc') params.direction = form.value.direction;

  router.get('/tickets', params, { preserveState: true, preserveScroll: true, replace: true });
};

const onSearchInput = () => {
  clearTimeout(searchTimeout);
  searchTimeout = setTimeout(() => applyFilters(), 350);
};

const onFilterChange = () => {
  applyFilters();
};

const toggleSort = (field) => {
  if (form.value.sort === field) {
    form.value.direction = form.value.direction === 'asc' ? 'desc' : 'asc';
  } else {
    form.value.sort = field;
    form.value.direction = 'desc';
  }
  applyFilters();
};

const sortIcon = (field) => {
  if (form.value.sort !== field) return '↕';
  return form.value.direction === 'asc' ? '↑' : '↓';
};

const clearFilters = () => {
  form.value = { search: '', priority: '', status: '', assigned_to: '', opened_from: '', opened_to: '', sort: 'created_at', direction: 'desc' };
  router.get('/tickets', {}, { preserveState: false, replace: true });
};

const priorityBadge = (p) => {
  const map = { low: 'bg-green-100 text-green-800', medium: 'bg-yellow-100 text-yellow-800', high: 'bg-red-100 text-red-800' };
  return map[p] || 'bg-gray-100';
};
const statusBadge = (s) => {
  const map = { open: 'bg-blue-100 text-blue-800', in_progress: 'bg-purple-100 text-purple-800', resolved: 'bg-green-100 text-green-800', closed: 'bg-gray-100 text-gray-800' };
  return map[s] || 'bg-gray-100';
};
// Os rótulos vêm do servidor (que os tira dos enums), não de uma cópia em JS:
// assim "Alta" ou "Em andamento" existem em um único lugar do projeto e não
// podem divergir entre PHP e JavaScript.
const labelDe = (options, value) =>
  options.find((option) => option.value === value)?.label ?? value;

const priorityLabel = (value) => labelDe(props.priorities, value);
const statusLabel = (value) => labelDe(props.statuses, value);
</script>

<template>
  <AppLayout>
    <div class="flex justify-between items-center mb-6">
      <h1 class="text-2xl font-bold text-gray-900">Chamados</h1>
      <Link href="/tickets/create" class="px-4 py-2 bg-indigo-600 text-white rounded-md text-sm hover:bg-indigo-700">Novo Chamado</Link>
    </div>

    <!-- Filtros -->
    <div class="bg-white shadow rounded-lg p-4 mb-6 space-y-4">
      <div class="grid grid-cols-1 md:grid-cols-12 gap-3">
        <div class="md:col-span-4">
          <label class="block text-xs font-medium text-gray-500 mb-1">Busca</label>
          <input v-model="form.search" @input="onSearchInput" type="text" placeholder="Título ou descrição..." class="w-full border rounded-md px-3 py-2 text-sm" />
        </div>
        <div class="md:col-span-2">
          <label class="block text-xs font-medium text-gray-500 mb-1">Prioridade</label>
          <select v-model="form.priority" @change="onFilterChange" class="w-full border rounded-md px-3 py-2 text-sm">
            <option value="">Todas</option>
            <option v-for="p in priorities" :key="p.value" :value="p.value">{{ p.label }}</option>
          </select>
        </div>
        <div class="md:col-span-2">
          <label class="block text-xs font-medium text-gray-500 mb-1">Status</label>
          <select v-model="form.status" @change="onFilterChange" class="w-full border rounded-md px-3 py-2 text-sm">
            <option value="">Todos</option>
            <option v-for="s in statuses" :key="s.value" :value="s.value">{{ s.label }}</option>
          </select>
        </div>
        <div class="md:col-span-4">
          <label class="block text-xs font-medium text-gray-500 mb-1">Responsável</label>
          <select v-model="form.assigned_to" @change="onFilterChange" class="w-full border rounded-md px-3 py-2 text-sm">
            <option value="">Todos</option>
            <option v-for="u in users" :key="u.id" :value="String(u.id)">{{ u.name }}</option>
          </select>
        </div>
      </div>

      <div class="grid grid-cols-1 md:grid-cols-12 gap-3">
        <div class="md:col-span-3">
          <label class="block text-xs font-medium text-gray-500 mb-1">Abertura de</label>
          <input v-model="form.opened_from" @change="onFilterChange" type="date" class="w-full border rounded-md px-3 py-2 text-sm" />
        </div>
        <div class="md:col-span-3">
          <label class="block text-xs font-medium text-gray-500 mb-1">Abertura até</label>
          <input v-model="form.opened_to" @change="onFilterChange" type="date" class="w-full border rounded-md px-3 py-2 text-sm" />
        </div>
        <div class="md:col-span-3">
          <label class="block text-xs font-medium text-gray-500 mb-1">Ordenar por</label>
          <select v-model="form.sort" @change="onFilterChange" class="w-full border rounded-md px-3 py-2 text-sm">
            <option value="created_at">Criação</option>
            <option value="opened_at">Data de abertura</option>
            <option value="priority">Prioridade</option>
            <option value="status">Status</option>
            <option value="title">Título</option>
          </select>
        </div>
        <div class="md:col-span-3 flex items-end gap-2">
          <select v-model="form.direction" @change="onFilterChange" class="flex-1 border rounded-md px-3 py-2 text-sm">
            <option value="desc">Mais recentes</option>
            <option value="asc">Mais antigos</option>
          </select>
          <button v-if="hasActiveFilters" @click="clearFilters" class="px-4 py-2 border rounded-md text-sm bg-white hover:bg-gray-50 whitespace-nowrap">Limpar filtros</button>
        </div>
      </div>

      <div class="flex justify-between items-center pt-2 border-t text-xs text-gray-500">
        <span>{{ tickets.total }} resultado(s) encontrado(s) <span v-if="hasActiveFilters">• filtros ativos</span></span>
        <span v-if="tickets.total > 0" class="hidden md:inline">Clique no cabeçalho para ordenar</span>
      </div>
    </div>

    <div v-if="tickets.data.length === 0" class="bg-white rounded-lg shadow p-8 text-center text-gray-500">
      <template v-if="hasActiveFilters">
        Nenhum chamado encontrado com os filtros atuais.
        <div class="mt-4 flex justify-center gap-3">
          <button @click="clearFilters" class="px-4 py-2 bg-indigo-600 text-white rounded-md text-sm hover:bg-indigo-700">Limpar filtros</button>
          <Link href="/tickets/create" class="px-4 py-2 border rounded-md text-sm">Novo chamado</Link>
        </div>
      </template>
      <template v-else>
        Nenhum chamado cadastrado ainda.
        <div class="mt-4"><Link href="/tickets/create" class="text-indigo-600 hover:underline">Criar primeiro chamado</Link></div>
      </template>
    </div>

    <div v-else class="bg-white shadow rounded-lg overflow-hidden">
      <table class="min-w-full divide-y divide-gray-200">
        <thead class="bg-gray-50">
          <tr>
            <th @click="toggleSort('title')" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase cursor-pointer select-none hover:text-gray-700">Título {{ sortIcon('title') }}</th>
            <th @click="toggleSort('priority')" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase cursor-pointer select-none hover:text-gray-700">Prioridade {{ sortIcon('priority') }}</th>
            <th @click="toggleSort('status')" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase cursor-pointer select-none hover:text-gray-700">Status {{ sortIcon('status') }}</th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Responsável</th>
            <th @click="toggleSort('opened_at')" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase cursor-pointer select-none hover:text-gray-700">Abertura {{ sortIcon('opened_at') }}</th>
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
