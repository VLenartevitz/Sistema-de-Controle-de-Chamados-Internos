<script setup>
import { ref } from 'vue';

/**
 * Botão que consulta a sugestão de distribuição automática e mostra a
 * justificativa de carga. O endpoint `GET /tickets/next-assignee` é somente
 * leitura: devolve o responsável com menos chamados em aberto, mas quem decide
 * o que salvar é o formulário, por isso o componente emite o id em vez de
 * escrever direto no form.
 */
const emit = defineEmits(['selected']);

const busy = ref(false);
const preview = ref(null);

const consultarSugestao = async () => {
  busy.value = true;
  preview.value = null;

  try {
    const resposta = await fetch('/tickets/next-assignee', {
      headers: { Accept: 'application/json' },
    });
    const dados = await resposta.json().catch(() => ({}));

    if (!resposta.ok) {
      throw new Error(dados.message || '');
    }

    preview.value = dados;
    emit('selected', dados.id);
  } catch (erro) {
    preview.value = {
      error:
        erro.message ||
        'Não foi possível atribuir automaticamente. Verifique se há responsáveis cadastrados.',
    };
  } finally {
    busy.value = false;
  }
};
</script>

<template>
  <div class="mt-2">
    <button
      type="button"
      @click="consultarSugestao"
      :disabled="busy"
      class="w-full px-3 py-2 border rounded-md text-sm bg-gray-50 hover:bg-gray-100 disabled:opacity-50"
      title="Atribui ao responsável com menor carga (OPEN/IN_PROGRESS) com desempate por prioridade alta > média > baixa > ID"
    >
      {{ busy ? '...' : 'Atribuir automaticamente' }}
    </button>

    <p v-if="preview && !preview.error" class="text-xs text-green-700 mt-1">
      Sugestão: {{ preview.name }} — {{ preview.reason.open }} abertos
      ({{ preview.reason.high }} alta, {{ preview.reason.medium }} média,
      {{ preview.reason.low }} baixa)
    </p>

    <p v-if="preview && preview.error" class="text-xs text-red-600 mt-1">
      {{ preview.error }}
    </p>
  </div>
</template>
