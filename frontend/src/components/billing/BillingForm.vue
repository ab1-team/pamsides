<template>
  <div class="billing-form" @click.stop>
    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
      <!-- Baris 1: Tanggal Transaksi & Kode Instalasi -->
      <div class="flex flex-col gap-0.5">
        <label class="text-sm mb-1">TANGGAL TRANSAKSI</label>
        <AppDatePicker
          v-model="formData.tanggal"
          placeholder="Pilih tanggal transaksi"
          @date-select="(date) => (formData.tanggal = date)"
        />
      </div>

      <BaseInput
        id="kode-instalasi"
        v-model="formData.kode"
        label="Kode Instalasi"
        type="text"
        placeholder="1.01.0002"
        @click.stop
        @focus.stop
      />

      <!-- Baris 2: Meter Awal, Meter Akhir, Pemakaian -->
      <div class="col-span-1 sm:col-span-2 grid grid-cols-1 sm:grid-cols-3 gap-4">
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

      <!-- Baris 3: Tagihan & Abodemen -->
      <div class="flex flex-col gap-0.5">
        <label class="text-sm mb-1">Tagihan</label>
        <MaksMoneyInput
          v-model="formData.tagihan"
          placeholder="0,00"
          :show-helper="true"
          helper-text="Masukkan jumlah tagihan"
        />
      </div>

      <div class="flex flex-col gap-0.5">
        <label class="text-sm mb-1">Abodemen</label>
        <MaksMoneyInput
          v-model="formData.abodemen"
          placeholder="0,00"
          :show-helper="true"
          helper-text="Masukkan jumlah abodemen"
        />
      </div>

      <!-- Baris 4: Denda & Pembayaran -->
      <div class="flex flex-col gap-0.5">
        <label class="text-sm mb-1">Denda</label>
        <MaksMoneyInput
          v-model="formData.denda"
          placeholder="0,00"
          :show-helper="true"
          helper-text="Masukkan jumlah denda"
        />
      </div>

      <div class="flex flex-col gap-0.5">
        <label class="text-sm mb-1">Pembayaran</label>
        <MaksMoneyInput
          v-model="formData.pembayaran"
          placeholder="0,00"
          :show-helper="true"
          helper-text="Total pembayaran"
        />
      </div>
    </div>

    <!-- Tombol Simpan Pembayaran -->
    <div class="flex justify-end mt-6">
      <BaseButton variant="primary" icon="plus" @click="handleSave" @click.stop>
        Simpan Pembayaran
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

// Props untuk initial data
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

// Emit events
const emit = defineEmits(['save', 'change'])

// Form data reaktif
const formData = reactive({
  tanggal: props.initialData.tanggal ? new Date(props.initialData.tanggal) : new Date(),
  kode: props.initialData.kode || props.customerInfo.installationCode || '',
  meterAwal: props.initialData.meterAwal || 0,
  meterAkhir: props.initialData.meterAkhir || 0,
  pemakaian: props.initialData.pemakaian || 0,
  tagihan: props.initialData.tagihan || 0,
  abodemen: props.initialData.abodemen || 10000,
  denda: props.initialData.denda || 0,
  pembayaran: props.initialData.pembayaran || 0,
})

// Auto calculate pemakaian
watch([() => formData.meterAwal, () => formData.meterAkhir], ([awal, akhir]) => {
  if (awal && akhir && akhir >= awal) {
    formData.pemakaian = akhir - awal
  }
})

// Auto calculate pembayaran
watch(
  [() => formData.tagihan, () => formData.abodemen, () => formData.denda],
  ([tagihan, abodemen, denda]) => {
    formData.pembayaran = tagihan + abodemen + denda
  },
)

// Emit change event
watch(
  formData,
  (newData) => {
    emit('change', newData)
  },
  { deep: true },
)

// Handle save
const handleSave = () => {
  // Validation
  if (!formData.tanggal || !formData.kode) {
    alert('Mohon lengkapi data tanggal dan kode instalasi')
    return
  }

  if (formData.meterAkhir < formData.meterAwal) {
    alert('Meter akhir tidak boleh kurang dari meter awal')
    return
  }

  // Emit save event
  emit('save', { ...formData })
}

// Expose form data for parent access
defineExpose({
  formData,
  resetForm: () => {
    Object.assign(formData, {
      tanggal: new Date(),
      kode: props.customerInfo.installationCode || '',
      meterAwal: 0,
      meterAkhir: 0,
      pemakaian: 0,
      tagihan: 0,
      abodemen: 10000,
      denda: 0,
      pembayaran: 0,
    })
  },
})
</script>

<style scoped>
/* =============================================
   BILLING FORM - DYNAMIC COMPONENT
   ============================================= */

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
