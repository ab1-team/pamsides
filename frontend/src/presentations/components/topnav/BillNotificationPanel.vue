<template>
  <div class="topnav-panel" role="dialog" aria-label="Tagihan yang harus dibayar">
    <header class="topnav-panel-header">
      <div class="topnav-panel-title">
        <font-awesome-icon icon="file-invoice-dollar" class="topnav-panel-title-icon" />
        <div>
          <h3>Tagihan Belum Dibayar</h3>
          <p class="topnav-panel-subtitle">
            <template v-if="store.isStaffRole">
              {{ store.billsSummary.customer_count }} pelanggan menunggak
            </template>
            <template v-else>
              {{ store.billsSummary.unpaid_count }} tagihan menunggu pembayaran
            </template>
          </p>
        </div>
      </div>
      <button type="button" class="topnav-panel-close" aria-label="Tutup" @click="emit('close')">
        <font-awesome-icon icon="times" />
      </button>
    </header>

    <div class="topnav-panel-stats">
      <div class="topnav-panel-stat">
        <span class="topnav-panel-stat-label">Total Tagihan</span>
        <span class="topnav-panel-stat-value">
          {{ formatRupiah(store.billsSummary.unpaid_total) }}
        </span>
      </div>
      <div class="topnav-panel-stat">
        <span class="topnav-panel-stat-label">Tertunggak</span>
        <span class="topnav-panel-stat-value is-danger">{{
          store.billsSummary.overdue_count
        }}</span>
      </div>
    </div>

    <div class="topnav-panel-body">
      <div v-if="store.billsLoading && store.unpaidBills.length === 0" class="topnav-panel-empty">
        <font-awesome-icon icon="spinner" spin />
        <span>Memuat tagihan...</span>
      </div>

      <div v-else-if="store.billsError" class="topnav-panel-empty">
        <font-awesome-icon icon="triangle-exclamation" class="topnav-panel-empty-icon-warn" />
        <span>{{ store.billsError }}</span>
      </div>

      <div v-else-if="store.unpaidBills.length === 0" class="topnav-panel-empty">
        <font-awesome-icon icon="circle-check" class="topnav-panel-empty-icon-ok" />
        <span>Tidak ada tagihan yang perlu dibayar. Semua sudah lunas.</span>
      </div>

      <ul v-else class="topnav-panel-list">
        <li
          v-for="item in store.unpaidBills"
          :key="item.customer_id ?? item.bill_id"
          class="topnav-panel-row"
          @click="emit('close', { routeName: targetRouteName })"
        >
          <div class="topnav-panel-row-main">
            <p class="topnav-panel-row-name">
              {{ store.isStaffRole ? item.name : `Tagihan ${item.period_label}` }}
            </p>
            <p class="topnav-panel-row-meta">
              <template v-if="store.isStaffRole">
                <span>{{ item.code }}</span>
                <span v-if="item.overdue_count > 0" class="dot">·</span>
                <span v-if="item.overdue_count > 0" class="is-danger">
                  {{ item.overdue_count }} tertunggak
                </span>
              </template>
              <span v-if="item.due_date" class="dot">·</span>
              <span>Jatuh tempo {{ formatDate(item.due_date) }}</span>
            </p>
          </div>

          <div class="topnav-panel-row-side">
            <span class="topnav-panel-row-amount">
              {{ formatRupiah(item.total_unpaid ?? item.total_amount) }}
            </span>
            <span v-if="isOverdue(item)" class="topnav-panel-row-badge">Tertunggak</span>
            <span v-else-if="store.isStaffRole" class="topnav-panel-row-badge is-neutral">
              {{ item.unpaid_count }} tagihan
            </span>
          </div>
        </li>
      </ul>
    </div>

    <footer class="topnav-panel-footer">
      <button type="button" class="topnav-panel-refresh" @click="store.fetchBills()">
        <font-awesome-icon icon="arrows-rotate" />
        <span>Segarkan</span>
      </button>
      <button
        type="button"
        class="topnav-panel-link"
        @click="emit('close', { routeName: targetRouteName })"
      >
        <span>Lihat tagihan lengkap</span>
        <font-awesome-icon icon="arrow-right" />
      </button>
    </footer>
  </div>
</template>

<script setup>
import { computed } from 'vue'

import { formatRupiah } from '@/composables/useFormatCurrency'
import { useDateFormat } from '@/composables/useDateFormat'
import { useNotificationStore } from '@/stores/notificationStore'

const emit = defineEmits(['close'])

const store = useNotificationStore()

const formatDate = (value) => useDateFormat(value, { format: 'DD/MM/YYYY' })

const isOverdue = (item) => (item.overdue_count ?? (item.is_overdue ? 1 : 0)) > 0

// Admin & teknisi punya halaman "Daftar Tagihan Teknisi" yang juga bisa
// dibuka admin; pelanggan diarahkan ke riwayat tagihannya sendiri.
const targetRouteName = computed(() =>
  store.isStaffRole ? 'Daftar Tagihan Teknisi' : 'Riwayat Tagihan',
)
</script>
