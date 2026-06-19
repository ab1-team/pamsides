<template>
  <div class="billing-form" @click.stop>
    <!-- Info Jatuh Tempo -->
    <div
      v-if="formData.dueDate"
      class="mb-4! flex! items-center! gap-2! px-3! py-2! rounded-lg! text-xs! font-bold!"
      :class="isOverdue ? 'bg-red-50! text-red-700!' : 'bg-blue-50! text-blue-700!'"
    >
      <font-awesome-icon :icon="isOverdue ? 'exclamation-triangle' : 'calendar-alt'" />
      <span>
        Jatuh Tempo: {{ formatDueDate(formData.dueDate) }}
        <template v-if="isOverdue"> — Terlambat {{ overdueDays }} hari</template>
      </span>
    </div>

    <!-- Baris 1: Tanggal Pembayaran -->
    <div class="grid! grid-cols-1! sm:grid-cols-3! gap-4! mb-5!">
      <AppDatePicker
        v-model="tanggalStr"
        label="Tanggal Pembayaran"
        placeholder="Pilih tanggal pembayaran"
        noMargin
      />
      <div>
        <label class="block! text-xs! font-bold! text-slate-500! mb-1.5!">Meter Awal (m³)</label>
        <input
          type="text"
          :value="formatMeter(formData.meterAwal)"
          disabled
          class="w-full! px-3! py-2! text-sm! font-semibold! text-slate-700! bg-slate-50! border! border-slate-200! rounded-lg! cursor-not-allowed!"
        />
      </div>
      <div>
        <label class="block! text-xs! font-bold! text-slate-500! mb-1.5!">Meter Akhir (m³)</label>
        <input
          type="text"
          :value="formatMeter(formData.meterAkhir)"
          disabled
          class="w-full! px-3! py-2! text-sm! font-semibold! text-slate-700! bg-slate-50! border! border-slate-200! rounded-lg! cursor-not-allowed!"
        />
      </div>
    </div>

    <!-- Baris 2: Pemakaian, Tagihan, Abodemen, Denda -->
    <div class="grid! grid-cols-2! sm:grid-cols-4! gap-4! mb-5!">
      <div>
        <label class="block! text-xs! font-bold! text-slate-500! mb-1.5!">Pemakaian (m³)</label>
        <input
          type="text"
          :value="formatMeter(formData.pemakaian)"
          disabled
          class="w-full! px-3! py-2! text-sm! font-semibold! text-cyan-700! bg-slate-50! border! border-slate-200! rounded-lg! cursor-not-allowed!"
        />
      </div>
      <div>
        <label class="block! text-xs! font-bold! text-slate-500! mb-1.5!">Tagihan Air</label>
        <input
          type="text"
          :value="formatRupiah(formData.tagihan)"
          disabled
          class="w-full! px-3! py-2! text-sm! font-semibold! text-slate-700! bg-slate-50! border! border-slate-200! rounded-lg! cursor-not-allowed!"
        />
      </div>
      <div>
        <label class="block! text-xs! font-bold! text-slate-500! mb-1.5!">Abodemen</label>
        <input
          type="text"
          :value="formatRupiah(formData.abodemen)"
          disabled
          class="w-full! px-3! py-2! text-sm! font-semibold! text-slate-700! bg-slate-50! border! border-slate-200! rounded-lg! cursor-not-allowed!"
        />
      </div>
      <div>
        <label class="block! text-xs! font-bold! text-slate-500! mb-1.5!">Denda</label>
        <input
          type="text"
          :value="formatRupiah(formData.denda)"
          disabled
          class="w-full! px-3! py-2! text-sm! font-semibold! text-red-500! bg-slate-50! border! border-slate-200! rounded-lg! cursor-not-allowed!"
        />
      </div>
    </div>

    <!-- Baris 3: Total Pembayaran -->
    <div class="mb-5!">
      <label class="block! text-xs! font-bold! text-cyan-600! mb-1.5!">Total Pembayaran</label>
      <input
        type="text"
        :value="formatRupiah(formData.pembayaran)"
        disabled
        class="w-full! px-3! py-2.5! text-base! font-extrabold! text-cyan-700! bg-cyan-50/50! border! border-cyan-200! rounded-lg! cursor-not-allowed!"
      />
    </div>

    <!-- Tombol Konfirmasi -->
    <div class="flex! justify-end! pt-4! pb-2! border-t! border-slate-200/60!">
      <button
        class="px-6! py-2.5! text-sm! font-bold! text-white! bg-emerald-500! hover:bg-emerald-600! rounded-xl! shadow-lg! shadow-emerald-200/50! transition-all! flex! items-center! gap-2!"
        @click="handleSave"
        @click.stop
      >
        <font-awesome-icon icon="check-circle" />
        Konfirmasi Pembayaran
      </button>
    </div>
  </div>
</template>

<script setup>
import { reactive, ref, computed } from 'vue'
import AppDatePicker from '../AppDatePicker.vue'
import { formatRupiah } from '@/composables/useFormatCurrency.js'

const props = defineProps({
  initialData: {
    type: Object,
    default: () => ({}),
  },
  customerInfo: {
    type: Object,
    default: () => ({}),
  },
})

const emit = defineEmits(['save'])

const toDateString = (d) => {
  if (!d) return ''
  const date = new Date(d)
  const y = date.getFullYear()
  const m = String(date.getMonth() + 1).padStart(2, '0')
  const day = String(date.getDate()).padStart(2, '0')
  return `${y}-${m}-${day}`
}

const tanggalStr = ref(toDateString(props.initialData.tanggal) || toDateString(new Date()))

const formData = reactive({
  periodId: props.initialData.periodId,
  meterAwal: props.initialData.meterAwal || 0,
  meterAkhir: props.initialData.meterAkhir || 0,
  pemakaian: props.initialData.pemakaian || 0,
  tagihan: props.initialData.tagihan || 0,
  abodemen: props.initialData.abodemen || 0,
  denda: props.initialData.denda || 0,
  pembayaran: props.initialData.pembayaran || 0,
  dueDate: props.initialData.dueDate || null,
})

const formatMeter = (val) => {
  const num = Number(val)
  if (isNaN(num)) return val
  return Number.isInteger(num) ? num.toString() : num.toFixed(2)
}

const formatDueDate = (dateStr) => {
  if (!dateStr) return '-'
  const d = new Date(dateStr)
  return d.toLocaleDateString('id-ID', { day: 'numeric', month: 'long', year: 'numeric' })
}

const isOverdue = computed(() => {
  if (!formData.dueDate) return false
  return new Date(formData.dueDate) < new Date()
})

const overdueDays = computed(() => {
  if (!formData.dueDate) return 0
  const diff = new Date() - new Date(formData.dueDate)
  return Math.max(0, Math.floor(diff / (1000 * 60 * 60 * 24)))
})

const handleSave = () => {
  if (!tanggalStr.value) {
    alert('Mohon pilih tanggal pembayaran')
    return
  }
  emit('save', { ...formData, tanggal: tanggalStr.value })
}

defineExpose({
  formData,
  tanggalStr,
  resetForm: () => {
    tanggalStr.value = toDateString(new Date())
    Object.assign(formData, {
      meterAwal: 0,
      meterAkhir: 0,
      pemakaian: 0,
      tagihan: 0,
      abodemen: 0,
      denda: 0,
      pembayaran: 0,
      dueDate: null,
    })
  },
})
</script>

<style scoped>
.billing-form {
  padding: 1rem 1.25rem;
  border-top: 1px solid #e2e8f0;
  background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%);
  border-radius: 1rem;
}
</style>
