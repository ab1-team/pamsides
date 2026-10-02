<template>
  <Teleport to="body">
    <div
      v-if="show"
      class="package-modal-overlay"
      @click.self="!loading && $emit('close')"
    >
      <div class="package-modal package-modal--update">
        <!-- Top accent gradient -->
        <div class="package-modal__accent"></div>

        <!-- ============ HEADER ============ -->
        <div class="package-modal__header package-modal__header--brand">
          <span class="package-blob package-blob--brand-1"></span>
          <span class="package-blob package-blob--brand-2"></span>

          <div class="package-modal__inner">
            <div class="flex items-center justify-between">
              <div class="flex items-center gap-3 min-w-0">
                <div class="package-avatar package-avatar--brand">
                  <font-awesome-icon icon="redo-alt" class="text-lg!" />
                </div>
                <div class="min-w-0">
                  <h2 class="package-modal__title package-modal__title--brand">
                    Ubah Paket Pelanggan
                  </h2>
                  <p class="package-modal__subtitle package-modal__subtitle--brand mt-0.5">
                    Pilih paket baru &amp; konfirmasi perubahan
                  </p>
                </div>
              </div>
              <button
                @click="$emit('close')"
                :disabled="loading"
                class="package-modal__close package-modal__close--brand ml-3"
                title="Tutup"
                aria-label="Tutup"
              >
                <font-awesome-icon icon="times" />
              </button>
            </div>
          </div>
        </div>

        <!-- ============ BODY: 2-COLUMN LAYOUT ============ -->
        <div class="package-modal__body">
          <div class="package-modal__body-inner pdm-stack">

            <!-- ===== TOP: Current package banner ===== -->
            <section
              v-if="currentPackage"
              class="current-package-banner"
            >
              <div class="package-icon-square package-icon-square--slate flex-shrink-0">
                <font-awesome-icon icon="box" />
              </div>
              <div class="flex-1 min-w-0">
                <div class="text-[9px] font-bold uppercase tracking-wider text-slate-400">
                  Paket Aktif Saat Ini
                </div>
                <div class="text-sm font-bold text-slate-900 truncate">
                  {{ currentPackage.name }}
                </div>
              </div>
              <div class="text-right flex-shrink-0">
                <div class="text-[9px] font-bold uppercase tracking-wider text-slate-400">
                  Abodemen
                </div>
                <div class="text-xs font-bold text-slate-700">
                  {{ formatRupiah(currentPackage.monthly_abodemen) }}/bln
                </div>
              </div>
              <span class="chip chip--active flex-shrink-0">
                <font-awesome-icon icon="check" class="mr-1" />
                Aktif
              </span>
            </section>

            <!-- ===== MAIN: 2 columns layout ===== -->
            <section class="pdm-update-grid">

              <!-- =========== LEFT COLUMN: Pilih paket =========== -->
              <div class="package-panel">
                <div class="package-panel__header package-panel__header--blue">
                  <div class="package-panel__title">
                    <div class="package-icon-square package-icon-square--blue">
                      <font-awesome-icon icon="boxes" />
                    </div>
                    <div>
                      <div class="package-panel__title-text">Pilih Paket Baru</div>
                      <div class="package-panel__subtitle">
                        {{
                          availablePackages.length
                            ? `${availablePackages.length} paket tersedia`
                            : 'Tidak ada paket lain'
                        }}
                      </div>
                    </div>
                  </div>
                </div>
                <div class="package-panel__body">
                  <div class="pdm-package-grid">
                    <button
                      v-for="pkg in availablePackages"
                      :key="pkg.id"
                      type="button"
                      @click="form.newPackageId = pkg.id"
                      :disabled="loading"
                      :class="[
                        'radio-card',
                        form.newPackageId === pkg.id && 'radio-card--selected',
                      ]"
                    >
                      <div
                        v-if="form.newPackageId === pkg.id"
                        class="radio-card__check"
                      >
                        <font-awesome-icon icon="check" class="text-[10px]!" />
                      </div>

                      <div :class="form.newPackageId === pkg.id ? 'radio-card__title radio-card__title--selected' : 'radio-card__title'">
                        {{ pkg.name }}
                      </div>
                      <div class="radio-card__price">
                        <div class="radio-card__price-row">
                          <span>Abodemen</span>
                          <strong>{{ formatRupiah(pkg.monthly_abodemen) }}<span class="radio-card__price-unit">/bln</span></strong>
                        </div>
                        <div class="radio-card__price-row">
                          <span>Pasang</span>
                          <strong>{{ formatRupiah(pkg.installation_fee) }}</strong>
                        </div>
                        <div class="radio-card__price-row">
                          <span>Denda</span>
                          <strong>{{ formatRupiah(pkg.late_penalty) }}</strong>
                        </div>
                      </div>
                    </button>

                    <div
                      v-if="!availablePackages.length"
                      class="col-span-full text-center py-6 text-[11px] text-slate-400 italic bg-slate-50 rounded-xl border border-dashed border-slate-200"
                    >
                      <font-awesome-icon icon="box" class="text-xl text-slate-300 mb-2 block" />
                      Tidak ada paket lain yang tersedia.
                    </div>
                  </div>
                </div>
              </div>

              <!-- =========== RIGHT COLUMN: Preview + Reason =========== -->
              <div class="pdm-update-right">
                <!-- Preview perubahan (selalu tampil, kasih empty state) -->
                <section
                  class="preview-change"
                  :class="!selectedPackage && 'preview-change--empty'"
                >
                  <div class="preview-change__header">
                    <div class="flex items-center gap-2 min-w-0">
                      <div
                        class="preview-change__icon"
                        :class="selectedPackage ? previewHeaderBg : 'bg-slate-300'"
                      >
                        <font-awesome-icon :icon="selectedPackage ? previewIcon : 'eye'" class="text-white text-sm!" />
                      </div>
                      <div class="min-w-0">
                        <div class="text-[10px] font-bold uppercase tracking-wider text-cyan-700">
                          Preview Perubahan
                        </div>
                        <div class="text-[10px] text-slate-500 truncate">
                          {{ selectedPackage ? previewLabel : 'Pilih paket baru di samping' }}
                        </div>
                      </div>
                    </div>
                    <span v-if="selectedPackage" class="chip" :class="previewBadgeClass">{{ previewTypeLabel }}</span>
                    <span v-else class="chip chip--info">Idle</span>
                  </div>

                  <div v-if="selectedPackage && currentPackage" class="preview-change__body pdm-stack pdm-stack--sm">
                    <!-- Before → After -->
                    <div class="flex items-center gap-2">
                      <div class="flex-1 min-w-0">
                        <div class="text-[9px] font-bold uppercase tracking-wider text-slate-500 mb-1">
                          Dari
                        </div>
                        <div class="preview-change__from">
                          <div class="font-bold text-slate-900 text-xs truncate">
                            {{ currentPackage.name }}
                          </div>
                          <div class="text-[10px] text-slate-500 mt-0.5">
                            {{ formatRupiah(currentPackage.monthly_abodemen) }}/bln
                          </div>
                        </div>
                      </div>
                      <div class="preview-change__arrow">
                        <font-awesome-icon icon="arrow-right" />
                      </div>
                      <div class="flex-1 min-w-0">
                        <div class="text-[9px] font-bold uppercase tracking-wider text-cyan-700 mb-1">
                          Ke
                        </div>
                        <div class="preview-change__to">
                          <div class="font-bold text-cyan-900 text-xs truncate">
                            {{ selectedPackage.name }}
                          </div>
                          <div class="text-[10px] text-cyan-700 mt-0.5">
                            {{ formatRupiah(selectedPackage.monthly_abodemen) }}/bln
                          </div>
                        </div>
                      </div>
                    </div>

                    <!-- Diff stats -->
                    <div class="grid grid-cols-2 gap-2 pt-2.5 border-t border-cyan-200/50">
                      <div class="preview-change__stat">
                        <div class="text-[9px] font-bold uppercase tracking-wider text-slate-500 mb-0.5">
                          Selisih Abodemen
                        </div>
                        <div class="text-xs font-extrabold" :class="diffColor">
                          {{ diffSymbol }}{{ formatRupiah(Math.abs(diff)) }}/bln
                        </div>
                      </div>
                      <div class="preview-change__stat">
                        <div class="text-[9px] font-bold uppercase tracking-wider text-slate-500 mb-0.5">
                          Selisih Biaya Pasang
                        </div>
                        <div
                          class="text-xs font-extrabold"
                          :class="installFeeDiff === 0
                            ? 'text-slate-700'
                            : installFeeDiff > 0
                            ? 'text-emerald-700'
                            : 'text-rose-700'"
                        >
                          {{ installFeeDiff === 0
                            ? '±'
                            : installFeeDiff > 0
                            ? '+'
                            : '−' }}{{ formatRupiah(Math.abs(installFeeDiff)) }}
                        </div>
                      </div>
                    </div>
                  </div>

                  <div v-else class="preview-change__empty">
                    <font-awesome-icon icon="arrow-right" class="text-2xl text-cyan-300 mb-2 block" />
                    <p class="text-[11px] text-slate-500 font-medium">
                      Preview perubahan akan muncul di sini
                    </p>
                  </div>
                </section>

                <!-- Alasan -->
                <section class="package-panel">
                  <div class="package-panel__header package-panel__header--slate">
                    <div class="package-panel__title">
                      <div class="package-icon-square package-icon-square--slate">
                        <font-awesome-icon icon="comment-dots" />
                      </div>
                      <div>
                        <div class="package-panel__title-text">Alasan Perubahan</div>
                        <div class="package-panel__subtitle">Opsional, maks 500 karakter</div>
                      </div>
                    </div>
                    <span class="text-[10px] text-slate-400 font-mono">
                      {{ form.reason?.length || 0 }} / 500
                    </span>
                  </div>
                  <div class="package-panel__body">
                    <textarea
                      v-model="form.reason"
                      :disabled="loading"
                      rows="3"
                      maxlength="500"
                      placeholder="Contoh: Pelanggan pindah ke paket Usaha karena perlu kapasitas lebih..."
                      class="reason-textarea"
                    ></textarea>
                  </div>
                </section>

                <!-- Notice -->
                <div class="notice notice--warn">
                  <font-awesome-icon icon="exclamation-triangle" class="mt-0.5 flex-shrink-0" />
                  <span>
                    <strong>Penting:</strong> Tagihan lama tetap pakai tarif paket lama (snapshot pada
                    kolom abodemen &amp; usage_charge). Hanya tagihan baru ke depan yang memakai tarif
                    paket baru.
                  </span>
                </div>
              </div>
            </section>
          </div>
        </div>

        <!-- ============ FOOTER ============ -->
        <div class="package-modal__footer">
          <div class="package-modal__hint">
            <font-awesome-icon icon="shield-halved" class="text-slate-400" />
            Aksi ini akan tercatat di log audit.
          </div>
          <div class="flex items-center gap-2">
            <button
              type="button"
              @click="$emit('close')"
              :disabled="loading"
              class="btn-secondary-modal"
            >
              Batal
            </button>
            <button
              type="button"
              @click="$emit('submit')"
              :disabled="loading || !form.newPackageId"
              class="btn-primary-modal"
            >
              <font-awesome-icon v-if="loading" icon="spinner" spin />
              <font-awesome-icon v-else icon="check" />
              {{ loading ? 'Menyimpan...' : 'Konfirmasi & Simpan' }}
            </button>
          </div>
        </div>
      </div>
    </div>
  </Teleport>
</template>

<script setup>
import { computed } from 'vue'

const props = defineProps({
  show: { type: Boolean, default: false },
  form: { type: Object, required: true },
  packages: { type: Array, default: () => [] },
  loading: { type: Boolean, default: false },
  formatRupiah: { type: Function, required: true },
})

defineEmits(['close', 'submit'])

const currentPackage = computed(() => {
  return props.packages.find((p) => p.id === props.form.currentPackageId) || null
})

const selectedPackage = computed(() => {
  return props.packages.find((p) => p.id === props.form.newPackageId) || null
})

const availablePackages = computed(() =>
  props.packages.filter((p) => p.id !== props.form.currentPackageId),
)

const diff = computed(() => {
  if (!currentPackage.value || !selectedPackage.value) return 0
  return (
    selectedPackage.value.monthly_abodemen - currentPackage.value.monthly_abodemen
  )
})

const diffSymbol = computed(() => {
  if (diff.value > 0) return '+'
  if (diff.value < 0) return '−'
  return '±'
})

const diffColor = computed(() => {
  if (diff.value > 0) return 'text-emerald-700'
  if (diff.value < 0) return 'text-rose-700'
  return 'text-slate-700'
})

const installFeeDiff = computed(() => {
  if (!currentPackage.value || !selectedPackage.value) return 0
  return (
    selectedPackage.value.installation_fee - currentPackage.value.installation_fee
  )
})

const changeType = computed(() => {
  if (diff.value > 0) return 'upgrade'
  if (diff.value < 0) return 'downgrade'
  return 'reset'
})

const previewIcon = computed(
  () =>
    ({
      upgrade: 'arrow-up',
      downgrade: 'arrow-down',
      reset: 'redo-alt',
    })[changeType.value],
)

const previewLabel = computed(
  () =>
    ({
      upgrade: 'Abodemen akan naik',
      downgrade: 'Abodemen akan turun',
      reset: 'Paket akan diganti (abodemen sama)',
    })[changeType.value],
)

const previewTypeLabel = computed(
  () =>
    ({
      upgrade: '⬆ Naik Paket',
      downgrade: '⬇ Turun Paket',
      reset: '↔ Reset Paket',
    })[changeType.value],
)

const previewHeaderBg = computed(
  () =>
    ({
      upgrade: 'bg-gradient-to-br from-emerald-500 to-cyan-500',
      downgrade: 'bg-gradient-to-br from-amber-500 to-rose-500',
      reset: 'bg-gradient-to-br from-slate-500 to-slate-600',
    })[changeType.value],
)

const previewBadgeClass = computed(
  () =>
    ({
      upgrade: 'chip--success',
      downgrade: 'chip--warning',
      reset: 'chip--info',
    })[changeType.value],
)
</script>
