<template>
  <Teleport to="body">
    <div
      v-if="show"
      class="package-modal-overlay"
      @click.self="$emit('close')"
    >
      <div class="package-modal package-modal--detail">
        <!-- Loading state -->
        <div
          v-if="loading"
          class="flex flex-col items-center justify-center min-h-[480px] py-24 text-slate-500"
        >
          <!-- Spinner ring dengan icon di tengah -->
          <div class="pdm-loading__ring">
            <span class="pdm-loading__ring-spinner"></span>
            <font-awesome-icon
              icon="spinner"
              spin
              class="text-2xl! text-cyan-600 relative z-10"
            />
          </div>

          <!-- Pesan -->
          <div class="mt-5 text-center">
            <div class="text-sm font-extrabold uppercase tracking-wider text-slate-700">
              Memuat Detail Instalasi
            </div>
            <div class="text-xs text-slate-400 mt-1.5 max-w-xs leading-relaxed">
              Menyiapkan data paket, tagihan &amp; riwayat perubahan pelanggan.
            </div>
          </div>

          <!-- Progress dots -->
          <div class="flex items-center gap-1.5 mt-5">
            <span class="pdm-loading__dot"></span>
            <span class="pdm-loading__dot"></span>
            <span class="pdm-loading__dot"></span>
          </div>
        </div>

        <!-- ================================================== -->
        <!-- CONTENT -->
        <!-- ================================================== -->
        <template v-else-if="data">
          <!-- Top accent gradient (cyan → blue → indigo) -->
          <div class="package-modal__accent"></div>

          <!-- ============ HEADER ============ -->
          <div
            class="package-modal__header"
            :class="hasOutstanding
              ? 'package-modal__header--warning'
              : 'package-modal__header--success'"
          >
            <span
              class="package-blob"
              :class="hasOutstanding ? 'package-blob--warning-1' : 'package-blob--success-1'"
            ></span>

            <div class="package-modal__inner">
              <div class="flex items-start justify-between gap-4">
                <!-- Identity -->
                <div class="flex items-start gap-4 flex-1 min-w-0">
                  <!-- Avatar gradient dengan status ring -->
                  <div class="relative flex-shrink-0">
                    <div
                      class="package-avatar"
                      :class="hasOutstanding ? 'package-avatar--warning' : 'package-avatar--success'"
                    >
                      <font-awesome-icon
                        :icon="hasOutstanding ? 'exclamation-triangle' : 'check'"
                        class="text-xl!"
                      />
                    </div>
                    <div
                      class="package-avatar__badge"
                      :class="hasOutstanding ? 'package-avatar__badge--warning' : 'package-avatar__badge--success'"
                    >
                      <font-awesome-icon :icon="hasOutstanding ? 'clock' : 'check'" />
                    </div>
                  </div>

                  <div class="flex-1 min-w-0 pt-1">
                    <!-- Badges row -->
                    <div class="flex items-center gap-1.5 mb-2 flex-wrap">
                      <span class="chip chip--mono">
                        {{ data.ticket?.kode_instalasi || '-' }}
                      </span>
                      <span class="chip" :class="statusBadgeClass">
                        • {{ data.ticket?.status || '-' }}
                      </span>
                      <span
                        class="chip"
                        :class="hasOutstanding ? 'chip--warning' : 'chip--success'"
                      >
                        <font-awesome-icon
                          :icon="hasOutstanding ? 'exclamation-triangle' : 'check-circle'"
                          class="mr-1"
                        />
                        {{ hasOutstanding ? 'Ada Tunggakan' : 'Tidak Ada Tunggakan' }}
                      </span>
                    </div>
                    <h2 class="package-modal__title package-modal__title--lg">
                      {{ data.ticket?.nama || '-' }}
                    </h2>
                    <p class="package-modal__subtitle mt-1">
                      <font-awesome-icon icon="map-marker-alt" class="mr-1 text-slate-400" />
                      {{ data.ticket?.alamat || '-' }}
                    </p>
                  </div>
                </div>

                <!-- Close button -->
                <button
                  @click="$emit('close')"
                  class="package-modal__close ml-3"
                  title="Tutup"
                  aria-label="Tutup"
                >
                  <font-awesome-icon icon="times" />
                </button>
              </div>
            </div>
          </div>

          <!-- ============ BODY ============ -->
          <div class="package-modal__body">
            <div class="package-modal__body-inner pdm-stack pdm-stack--detail">

              <!-- ============================================ -->
              <!-- SECTION 1: STATUS TAGIHAN (adaptif) -->
              <!-- ============================================ -->
              <section v-if="hasOutstanding" class="package-panel package-panel--warning">
                <div class="package-panel__header package-panel__header--warning">
                  <div class="package-panel__title">
                    <div class="package-icon-square package-icon-square--rose">
                      <font-awesome-icon icon="file-invoice-dollar" />
                    </div>
                    <div>
                      <div class="package-panel__title-text">Tagihan Belum Bayar</div>
                      <div class="package-panel__subtitle">
                        {{ data.billing_summary.unpaid_bills_count }} tagihan belum dibayar
                      </div>
                    </div>
                  </div>
                  <span class="chip chip--warning font-bold">
                    {{ formatRupiah(data.billing_summary.outstanding) }}
                  </span>
                </div>
                <div class="package-panel__body space-y-2.5">
                  <div
                    v-if="data.billing_summary.latest_bill"
                    class="latest-bill-card latest-bill-card--warning"
                  >
                    <div class="min-w-0">
                      <div class="text-[10px] font-bold uppercase tracking-wider text-rose-600 mb-0.5">
                        Tagihan Terakhir
                      </div>
                      <div class="text-sm font-bold text-rose-900">
                        {{ data.billing_summary.latest_bill.period }}
                      </div>
                      <div class="text-[10px] text-rose-700">
                        Tempo:
                        {{
                          data.billing_summary.latest_bill.due_date
                            ? new Date(data.billing_summary.latest_bill.due_date).toLocaleDateString(
                                'id-ID',
                                { day: '2-digit', month: 'short', year: 'numeric' },
                              )
                            : '-'
                        }}
                      </div>
                    </div>
                    <div class="text-right flex-shrink-0">
                      <div class="text-sm font-extrabold text-rose-900">
                        {{ formatRupiah(data.billing_summary.latest_bill.total_amount) }}
                      </div>
                      <span class="chip chip--warning mt-1">Belum Bayar</span>
                    </div>
                  </div>

                  <div class="notice notice--warn">
                    <font-awesome-icon icon="lightbulb" class="mt-0.5 flex-shrink-0" />
                    <span>
                      <strong>Catatan:</strong> Tagihan terkait paket aktif
                      ({{ data.current_package?.name || '-' }}). Selesaikan tunggakan sebelum ubah
                      paket agar tercatat rapi di histori.
                    </span>
                  </div>
                </div>
              </section>

              <section v-else class="package-panel package-panel--success">
                <div class="package-panel__header package-panel__header--success">
                  <div class="package-panel__title">
                    <div class="package-icon-square package-icon-square--emerald">
                      <font-awesome-icon icon="check-double" />
                    </div>
                    <div>
                      <div class="package-panel__title-text">Status Tagihan</div>
                      <div class="package-panel__subtitle text-emerald-600">
                        Semua tagihan telah lunas
                      </div>
                    </div>
                  </div>
                  <span class="chip chip--success font-bold">Lunas ✓</span>
                </div>
                <div class="package-panel__body">
                  <div class="notice notice--success">
                    <font-awesome-icon icon="info-circle" class="mt-0.5 flex-shrink-0" />
                    <span>
                      Pelanggan tidak memiliki tunggakan. Aman untuk mengubah paket tanpa
                      mengganggu pembayaran berjalan.
                    </span>
                  </div>
                </div>
              </section>

              <!-- ============================================ -->
              <!-- SECTION 2: PAKET AKTIF + RINGKASAN TAGIHAN (2 kolom) -->
              <!-- ============================================ -->
              <section class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <!-- Paket Aktif -->
                <div class="package-panel">
                  <div class="package-panel__header package-panel__header--indigo">
                    <div class="package-panel__title">
                      <div class="package-icon-square package-icon-square--indigo">
                        <font-awesome-icon icon="box" />
                      </div>
                      <div>
                        <div class="package-panel__title-text">Paket Aktif</div>
                        <div class="package-panel__subtitle">
                          Tarif &amp; blok yang berlaku saat ini
                        </div>
                      </div>
                    </div>
                    <span v-if="data.current_package" class="chip chip--active">
                      <font-awesome-icon icon="check" class="mr-1" />
                      Aktif
                    </span>
                  </div>
                  <div class="package-panel__body">
                    <div v-if="data.current_package" class="pdm-panel-flow">
                      <!-- Paket name prominent -->
                      <div class="package-name-card package-name-card--indigo">
                        <div
                          class="text-[10px] font-bold uppercase tracking-wider text-indigo-500 mb-0.5"
                        >
                          Paket Saat Ini
                        </div>
                        <div class="text-base font-extrabold text-indigo-900 leading-tight">
                          {{ data.current_package.name }}
                        </div>
                      </div>

                      <!-- 3 stats compact -->
                      <div class="grid grid-cols-3 gap-1.5">
                        <div class="stat-tile stat-tile--slate">
                          <div class="stat-tile__label">Biaya Pasang</div>
                          <div class="stat-tile__value">
                            {{ formatRupiah(data.current_package.installation_fee) }}
                          </div>
                        </div>
                        <div class="stat-tile stat-tile--cyan">
                          <div class="stat-tile__label">Abodemen</div>
                          <div class="stat-tile__value">
                            {{ formatRupiah(data.current_package.monthly_abodemen) }}
                          </div>
                        </div>
                        <div class="stat-tile stat-tile--amber">
                          <div class="stat-tile__label">Denda</div>
                          <div class="stat-tile__value">
                            {{ formatRupiah(data.current_package.late_penalty) }}
                          </div>
                        </div>
                      </div>

                      <!-- Tarif blok -->
                      <div
                        v-if="data.current_package.tariff_blocks?.length"
                        class="pdm-panel-section"
                      >
                        <div
                          class="text-[10px] font-bold uppercase tracking-wider text-slate-400 flex items-center gap-1.5"
                        >
                          <font-awesome-icon icon="bars" class="text-slate-400" />
                          Blok Tarif
                        </div>
                        <div class="space-y-1">
                          <div
                            v-for="(b, i) in data.current_package.tariff_blocks"
                            :key="b.id || i"
                            class="tariff-row"
                          >
                            <div class="flex items-center gap-2 min-w-0">
                              <span class="tariff-row__index">
                                {{ i + 1 }}
                              </span>
                              <span class="text-[11px] font-semibold text-slate-700 truncate">
                                {{ b.min_m3 }}–{{ b.max_m3 ?? '∞' }} m³
                              </span>
                            </div>
                            <span class="text-[11px] font-bold text-slate-900 flex-shrink-0 ml-2">
                              {{ formatRupiah(b.price_per_m3) }}/m³
                            </span>
                          </div>
                        </div>
                      </div>
                    </div>
                    <div
                      v-else
                      class="text-center py-6 text-xs text-slate-400 italic"
                    >
                      Belum ada paket aktif.
                    </div>
                  </div>
                </div>

                <!-- Ringkasan Tagihan -->
                <div class="package-panel">
                  <div class="package-panel__header package-panel__header--blue">
                    <div class="package-panel__title">
                      <div class="package-icon-square package-icon-square--blue">
                        <font-awesome-icon icon="chart-pie" />
                      </div>
                      <div>
                        <div class="package-panel__title-text">Ringkasan Tagihan</div>
                        <div class="package-panel__subtitle">
                          Total, dibayar &amp; sisa
                        </div>
                      </div>
                    </div>
                  </div>
                  <div class="package-panel__body space-y-2">
                    <!-- Row: total -->
                    <div class="billing-row">
                      <div class="flex items-center gap-2 min-w-0">
                        <span class="billing-dot billing-dot--blue"></span>
                        <span class="text-xs text-slate-600">Total Tagihan</span>
                      </div>
                      <span class="text-sm font-bold text-slate-900">
                        {{ formatRupiah(data.billing_summary.total_billed) }}
                      </span>
                    </div>
                    <div class="h-px bg-slate-100"></div>

                    <!-- Row: dibayar -->
                    <div class="billing-row">
                      <div class="flex items-center gap-2 min-w-0">
                        <span class="billing-dot billing-dot--emerald"></span>
                        <span class="text-xs text-slate-600">Sudah Dibayar</span>
                      </div>
                      <span class="text-sm font-bold text-emerald-700">
                        {{ formatRupiah(data.billing_summary.total_paid) }}
                      </span>
                    </div>
                    <div class="h-px bg-slate-100"></div>

                    <!-- Row: sisa -->
                    <div class="billing-row">
                      <div class="flex items-center gap-2 min-w-0">
                        <span
                          class="billing-dot"
                          :class="hasOutstanding ? 'billing-dot--rose' : 'billing-dot--slate'"
                        ></span>
                        <span class="text-xs text-slate-600">Sisa</span>
                      </div>
                      <span
                        class="text-sm font-bold"
                        :class="hasOutstanding ? 'text-rose-700' : 'text-slate-500'"
                      >
                        {{ formatRupiah(data.billing_summary.outstanding) }}
                      </span>
                    </div>

                    <!-- Progress -->
                    <div class="pt-2.5 mt-1 border-t border-slate-100">
                      <div class="flex items-center justify-between mb-1.5">
                        <span class="text-[10px] font-bold uppercase tracking-wider text-slate-500">
                          Progress Pembayaran
                        </span>
                        <span
                          class="text-[11px] font-bold"
                          :class="hasOutstanding ? 'text-rose-600' : 'text-emerald-600'"
                        >
                          {{ paymentProgress }}%
                        </span>
                      </div>
                      <div class="progress-track">
                        <div
                          class="h-full rounded-full transition-all"
                          :class="hasOutstanding
                            ? 'progress-fill--warning'
                            : 'progress-fill--success'"
                          :style="{ width: paymentProgress + '%' }"
                        ></div>
                      </div>
                    </div>

                    <!-- Customer info mini -->
                    <div
                      v-if="data.customer"
                      class="pt-2.5 mt-1 border-t border-slate-100 grid grid-cols-2 gap-3"
                    >
                      <div>
                        <div class="text-[9px] font-bold uppercase tracking-wider text-slate-400">
                          Kode Pelanggan
                        </div>
                        <div class="font-mono font-bold text-slate-900 text-[11px] truncate">
                          {{ data.customer.code }}
                        </div>
                      </div>
                      <div>
                        <div class="text-[9px] font-bold uppercase tracking-wider text-slate-400">
                          Tgl Aktivasi
                        </div>
                        <div class="font-bold text-slate-900 text-[11px]">
                          {{
                            data.customer.activated_at
                              ? new Date(data.customer.activated_at).toLocaleDateString(
                                  'id-ID',
                                  { day: '2-digit', month: 'short', year: 'numeric' },
                                )
                              : '-'
                          }}
                        </div>
                      </div>
                    </div>
                  </div>
                </div>
              </section>

              <!-- ============================================ -->
              <!-- SECTION 3: PROFIL PELANGGAN (Info Lengkap) -->
              <!-- ============================================ -->
              <section class="package-panel">
                <div class="package-panel__header package-panel__header--cyan">
                  <div class="package-panel__title">
                    <div class="package-icon-square package-icon-square--cyan">
                      <font-awesome-icon icon="user" />
                    </div>
                    <div>
                      <div class="package-panel__title-text">Informasi Pelanggan</div>
                      <div class="package-panel__subtitle">
                        Detail kontak &amp; data pelanggan
                      </div>
                    </div>
                  </div>
                </div>
                <div class="package-panel__body">
                  <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
                    <div class="info-tile">
                      <div class="info-tile__icon">
                        <font-awesome-icon icon="id-badge" />
                      </div>
                      <div class="min-w-0">
                        <div class="info-tile__label">Kode Pelanggan</div>
                        <div class="info-tile__value info-tile__value--mono">
                          {{ data.customer?.code || '-' }}
                        </div>
                      </div>
                    </div>
                    <div class="info-tile">
                      <div class="info-tile__icon">
                        <font-awesome-icon icon="phone" />
                      </div>
                      <div class="min-w-0">
                        <div class="info-tile__label">No. Telepon</div>
                        <div class="info-tile__value">
                          {{ data.customer?.phone || '-' }}
                        </div>
                      </div>
                    </div>
                    <div class="info-tile">
                      <div class="info-tile__icon">
                        <font-awesome-icon icon="calendar-check" />
                      </div>
                      <div class="min-w-0">
                        <div class="info-tile__label">Tgl Aktivasi</div>
                        <div class="info-tile__value">
                          {{
                            data.customer?.activated_at
                              ? new Date(data.customer.activated_at).toLocaleDateString(
                                  'id-ID',
                                  { day: '2-digit', month: 'short', year: 'numeric' },
                                )
                              : '-'
                          }}
                        </div>
                      </div>
                    </div>
                    <div class="info-tile md:col-span-3">
                      <div class="info-tile__icon">
                        <font-awesome-icon icon="map-marker-alt" />
                      </div>
                      <div class="min-w-0 flex-1">
                        <div class="info-tile__label">Alamat Lengkap</div>
                        <div class="info-tile__value">{{ data.ticket?.alamat || '-' }}</div>
                      </div>
                    </div>
                  </div>
                </div>
              </section>

              <!-- ============================================ -->
              <!-- SECTION 4: HISTORY TIMELINE -->
              <!-- ============================================ -->
              <section class="package-panel">
                <div class="package-panel__header package-panel__header--violet">
                  <div class="package-panel__title">
                    <div class="package-icon-square package-icon-square--violet">
                      <font-awesome-icon icon="history" />
                    </div>
                    <div>
                      <div class="package-panel__title-text">Riwayat Perubahan Paket</div>
                      <div class="package-panel__subtitle">
                        {{
                          data.history?.length
                            ? `${data.history.length} perubahan tercatat`
                            : 'Belum ada perubahan paket'
                        }}
                      </div>
                    </div>
                  </div>
                </div>
                <div class="package-panel__body">
                  <!-- Empty state -->
                  <div
                    v-if="!data.history || data.history.length === 0"
                    class="empty-state"
                  >
                    <font-awesome-icon
                      icon="inbox"
                      class="text-3xl! text-slate-300! mb-2 block"
                    />
                    <p class="text-xs text-slate-500 font-medium">
                      Belum pernah ada perubahan paket.
                    </p>
                    <p class="text-[10px] text-slate-400 mt-1">
                      Riwayat akan tercatat otomatis setiap paket diubah.
                    </p>
                  </div>

                  <!-- Timeline -->
                  <div v-else class="timeline">
                    <div class="timeline__line"></div>
                    <div class="pdm-timeline-list">
                      <div
                        v-for="(h, idx) in data.history"
                        :key="h.id"
                        class="timeline-item"
                      >
                        <!-- Numbered node -->
                        <div
                          class="timeline__node"
                          :class="`timeline__node--${h.change_type}`"
                        >
                          {{ data.history.length - idx }}
                        </div>

                        <!-- Card -->
                        <div class="timeline-item__card">
                          <!-- Header row -->
                          <div
                            class="flex items-center justify-between gap-2 mb-2 flex-wrap"
                          >
                            <span
                              class="text-[13px] font-extrabold text-slate-900 truncate"
                              :title="h.new_package?.name"
                            >
                              {{ h.new_package?.name || '-' }}
                            </span>
                            <span class="text-[10px] text-slate-400 font-medium">
                              {{ formatDateTime(h.created_at) }}
                            </span>
                          </div>

                          <!-- Paket Lama → Baru -->
                          <div class="timeline-item__flow">
                            <div class="flex-1 min-w-0">
                              <div
                                class="text-[9px] font-bold uppercase tracking-wider text-slate-400 mb-0.5"
                              >
                                Paket Lama
                              </div>
                              <div
                                class="text-xs font-bold text-slate-900 truncate"
                                :title="h.package?.name"
                              >
                                {{ h.package?.name || '-' }}
                              </div>
                            </div>
                            <div class="timeline-item__arrow">
                              <font-awesome-icon icon="arrow-right" class="text-[10px]!" />
                            </div>
                            <div class="flex-1 min-w-0">
                              <div
                                class="text-[9px] font-bold uppercase tracking-wider text-emerald-600 mb-0.5"
                              >
                                Paket Baru
                              </div>
                              <div
                                class="text-xs font-bold text-emerald-700 truncate"
                                :title="h.new_package?.name"
                              >
                                {{ h.new_package?.name || '-' }}
                              </div>
                            </div>
                          </div>

                          <!-- Reason + changer -->
                          <div
                            v-if="h.reason || h.changed_by"
                            class="mt-2 flex items-center justify-between gap-2 text-[10px] text-slate-500 flex-wrap"
                          >
                            <span
                              v-if="h.reason"
                              class="italic truncate flex-1 min-w-0"
                              :title="h.reason"
                            >
                              <font-awesome-icon icon="comment-dots" class="mr-1" />
                              {{ h.reason }}
                            </span>
                            <span
                              v-if="h.changed_by"
                              class="flex items-center gap-1 flex-shrink-0 font-medium"
                            >
                              <font-awesome-icon icon="user-edit" />
                              {{ h.changed_by.name }}
                            </span>
                          </div>
                        </div>
                      </div>
                    </div>
                  </div>
                </div>
              </section>
            </div>
          </div>

          <!-- ============ FOOTER ============ -->
          <div class="package-modal__footer">
            <div class="package-modal__hint">
              <font-awesome-icon icon="info-circle" class="text-slate-400" />
              Riwayat paket tercatat otomatis setiap perubahan.
            </div>
            <div class="flex items-center gap-2">
              <button
                type="button"
                @click="$emit('close')"
                class="btn-secondary-modal"
              >
                Tutup
              </button>
              <button
                type="button"
                @click="
                  $emit('open-update', {
                    ticketId: data.ticket?.id,
                    currentPackageId: data.current_package?.id,
                  })
                "
                class="btn-primary-modal"
              >
                <font-awesome-icon icon="edit" />
                Ubah Paket
              </button>
            </div>
          </div>
        </template>
      </div>
    </div>
  </Teleport>
</template>

<script setup>
import { computed } from 'vue'

const props = defineProps({
  show: { type: Boolean, default: false },
  loading: { type: Boolean, default: false },
  data: { type: Object, default: null },
  formatRupiah: { type: Function, required: true },
  formatDateTime: { type: Function, required: true },
})

defineEmits(['close', 'open-update'])

const STATUS_BADGE = {
  draft: 'chip--info',
  pending: 'chip--info',
  surveyed: 'chip--info',
  unpaid: 'chip--warning',
  processing: 'chip--info',
  completed: 'chip--success',
  suspended: 'chip--warning',
  terminated: 'chip--warning',
}

const statusBadgeClass = computed(
  () => STATUS_BADGE[props.data?.ticket?.status] || 'chip--info',
)

const hasOutstanding = computed(
  () => (props.data?.billing_summary?.outstanding ?? 0) > 0,
)

const paymentProgress = computed(() => {
  const b = props.data?.billing_summary?.total_billed ?? 0
  const p = props.data?.billing_summary?.total_paid ?? 0
  if (b <= 0) return 0
  return Math.min(100, Math.round((p / b) * 100))
})
</script>
