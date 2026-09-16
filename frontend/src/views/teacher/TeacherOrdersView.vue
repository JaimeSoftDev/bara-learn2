<script setup lang="ts">
import { onMounted, ref } from 'vue'
import { api } from '@/api/client'
import type { Order } from '@/types'

const orders = ref<Order[]>([])
const loading = ref(true)

const methodLabels: Record<string, string> = { stripe: 'Stripe', cash: 'Efectivo' }
const statusLabels: Record<string, string> = { paid: 'Pagado', pending: 'Pendiente', failed: 'Fallido', refunded: 'Reembolsado' }
const statusColors: Record<string, string> = {
  paid: 'bg-emerald-100 text-emerald-700',
  pending: 'bg-amber-100 text-amber-700',
  failed: 'bg-red-100 text-red-700',
  refunded: 'bg-gray-100 text-gray-600',
}

onMounted(async () => {
  try {
    const { data } = await api.get('/teacher/orders')
    orders.value = data.data
  } finally {
    loading.value = false
  }
})
</script>

<template>
  <div>
    <h1 class="text-xl font-bold mb-6">Pedidos e ingresos</h1>
    <div v-if="loading" class="text-gray-400">Cargando...</div>
    <div v-else-if="orders.length === 0" class="text-gray-500">Todavía no hay pedidos.</div>
    <table v-else class="w-full text-sm bg-white border border-gray-200 rounded-xl overflow-hidden">
      <thead class="bg-gray-50 text-gray-500 text-xs uppercase">
        <tr>
          <th class="text-left px-4 py-3">Curso</th>
          <th class="text-left px-4 py-3">Alumno</th>
          <th class="text-left px-4 py-3">Importe</th>
          <th class="text-left px-4 py-3">Método</th>
          <th class="text-left px-4 py-3">Estado</th>
          <th class="text-left px-4 py-3">Fecha</th>
        </tr>
      </thead>
      <tbody class="divide-y divide-gray-100">
        <tr v-for="order in orders" :key="order.id">
          <td class="px-4 py-3">{{ order.course.title }}</td>
          <td class="px-4 py-3">
            <p>{{ order.user.name }}</p>
            <p class="text-xs text-gray-400">{{ order.user.email }}</p>
          </td>
          <td class="px-4 py-3 font-medium">{{ order.amount.toFixed(2) }} {{ order.currency.toUpperCase() }}</td>
          <td class="px-4 py-3">
            {{ methodLabels[order.payment_method] }}
            <p v-if="order.created_by" class="text-xs text-gray-400">registrado por {{ order.created_by.name }}</p>
          </td>
          <td class="px-4 py-3">
            <span :class="['px-2 py-0.5 rounded-full text-xs', statusColors[order.status]]">
              {{ statusLabels[order.status] }}
            </span>
          </td>
          <td class="px-4 py-3 text-gray-500">{{ new Date(order.created_at).toLocaleDateString() }}</td>
        </tr>
      </tbody>
    </table>
  </div>
</template>
