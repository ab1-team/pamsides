<template>
  <div class="billing-form" @click.stop>
    <!-- Baris 1: Tanggal, Abodemen, Denda -->
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-5">
      <AppDatePicker
        v-model="formData.tanggal"
        placeholder="Pilih tanggal transaksi"
        label="Tanggal Transaksi"
        @date-select="(date) => (formData.tanggal = date)"
      />

      <MaksMoneyInput
        v-model="formData.abodemen"
        placeholder="0,00"
        :show-helper="false"
        label="Abodemen"
      />

      <MaksMoneyInput
        v-model="formData.denda"
        placeholder="0,00"
        :show-helper="false"
        label="Denda"
      />
    </div>

    <!-- Baris 2: Meter Awal, Meter Akhir, Pemakaian -->
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-5">
      <BaseInput
        id="meter-awal"
        v-model="formData.meterAwal"
        label="Meter Awal"
        type="number"
        placeholder="0"
        @click.stop
        @focus.stop
      />

      <BaseInput
        id="meter-akhir"
        v-model="formData.meterAkhir"
        label="Meter Akhir"
        type="number"
        placeholder="0"
        @click.stop
        @focus.stop
      />

      <BaseInput
        id="pemakaian"
        v-model="formData.pemakaian"
        label="Pemakaian"
        type="number"
        placeholder="0"
        @click.stop
        @focus.stop
      />
    </div>

    <!-- Baris 3: Total Pembayaran (Full width) -->
    <div class="mb-8">
      <MaksMoneyInput
        v-model="formData.pembayaran"
        placeholder="0,00"
        :show-helper="false"
        label="Total Pembayaran"
      />
    </div>

    <!-- Tombol Simpan Pembayaran -->
    <div class="flex justify-end mt-4! pt-4! pb-2! border-t! border-slate-200/60!">
      <BaseButton
        variant="secondary"
        icon="save"
        size="md"
        class="px-8! rounded-xl shadow-lg shadow-emerald-200/50 bg-emerald-500! hover:bg-emerald-600! text-white! border-emerald-500!"
        @click="handleSave"
        @click.stop
      >
        Konfirmasi Pembayaran
      </BaseButton>
    </div>
  </div>
</template>

<script setup>
import { reactive, watch } from 'vue'
import BaseInput from '../ui/BaseInput.vue'
import BaseButton from '../ui/BaseButton.vue'
import AppDatePicker from '../AppDatePicker.vue'
import MaksMoneyInput from '../MaksMoneyInput.vue'
import { formatRupiah } from '@/composables/useFormatCurrency'

// Properti untuk data awal
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

// Daftar event emit
const emit = defineEmits(['save', 'change'])

// Data formulir reaktif
const formData = reactive({
  tanggal: props.initialData.tanggal ? new Date(props.initialData.tanggal) : new Date(),
  periodId: props.initialData.periodId,
  meterAwal: props.initialData.meterAwal || 0,
  meterAkhir: props.initialData.meterAkhir || 0,
  pemakaian: props.initialData.pemakaian || 0,
  tagihan: props.initialData.tagihan || 0,
  abodemen: props.initialData.abodemen || 0,
  denda: props.initialData.denda || 0,
  pembayaran: props.initialData.pembayaran || 0,
})

// Hitung otomatis pemakaian
watch([() => formData.meterAwal, () => formData.meterAkhir], ([awal, akhir]) => {
  if (awal !== undefined && akhir !== undefined && akhir >= awal) {
    formData.pemakaian = akhir - awal
  }
})

// Hitung otomatis pembayaran
watch(
  [() => formData.tagihan, () => formData.abodemen, () => formData.denda],
  ([tagihan, abodemen, denda]) => {
    formData.pembayaran = (Number(tagihan) || 0) + (Number(abodemen) || 0) + (Number(denda) || 0)
  },
)

// Emit event perubahan
watch(
  formData,
  (newData) => {
    emit('change', newData)
  },
  { deep: true },
)

// Fungsi penanganan simpan
const handleSave = () => {
  // Validasi
  if (!formData.tanggal) {
    alert('Mohon lengkapi data tanggal transaksi')
    return
  }

  // Emit event simpan
  emit('save', { ...formData })
}

// Mengekspos data form untuk diakses komponen induk
defineExpose({
  formData,
  resetForm: () => {
    Object.assign(formData, {
      tanggal: new Date(),
      meterAwal: 0,
      meterAkhir: 0,
      pemakaian: 0,
      tagihan: 0,
      abodemen: 0,
      denda: 0,
      pembayaran: 0,
    })
  },
})
</script>

<style scoped>
.billing-form {
  padding: 1rem 1.25rem 1rem 1.25rem;
  border-top: 1px solid #e2e8f0;
  background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%);
  position: relative;
  overflow: hidden;
  border-radius: 1rem;
}

.billing-form::before {
  content: '';
  position: absolute;
  bottom: -50px;
  left: -50px;
  width: 100px;
  height: 100px;
  background: radial-gradient(circle, rgba(11, 122, 158, 0.1) 0%, transparent 70%);
  border-radius: 50%;
}

.billing-form::after {
  content: '';
  position: absolute;
  bottom: -30px;
  left: -30px;
  width: 60px;
  height: 60px;
  background: radial-gradient(circle, rgba(59, 130, 246, 0.08) 0%, transparent 60%);
  border-radius: 50%;
}

.rotate-180 {
  transform: rotate(180deg);
}
</style>
