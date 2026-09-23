<template>
  <div class="min-h-screen bg-gray-50">
    <nav class="bg-white border-b border-gray-200">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between h-16">
          <div class="flex items-center gap-8">
            <a href="/tickets" class="text-xl font-bold text-indigo-600">Chamados Internos</a>
            <div class="hidden md:flex gap-4">
              <a href="/tickets" class="text-sm text-gray-700 hover:text-indigo-600" :class="{ 'font-semibold text-indigo-600': $page.url.startsWith('/tickets') }">Chamados</a>
            </div>
          </div>
          <div class="flex items-center gap-3">
            <a href="/tickets/create" class="inline-flex items-center px-4 py-2 bg-indigo-600 text-white text-sm rounded-md hover:bg-indigo-700">Novo Chamado</a>
          </div>
        </div>
      </div>
    </nav>
    <div v-if="$page.props.flash.success" class="max-w-7xl mx-auto mt-4 px-4">
      <div class="p-3 bg-green-100 border border-green-200 text-green-800 rounded-md text-sm">{{ $page.props.flash.success }}</div>
    </div>
    <div v-if="$page.props.flash.error" class="max-w-7xl mx-auto mt-4 px-4">
      <div class="p-3 bg-red-100 border border-red-200 text-red-800 rounded-md text-sm">{{ $page.props.flash.error }}</div>
    </div>
    <main class="max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8">
      <slot />
    </main>
  </div>
</template>
