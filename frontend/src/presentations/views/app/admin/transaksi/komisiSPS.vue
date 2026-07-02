<template>
  <div>
    <div class="grid! grid-cols-1! lg:grid-cols-[1fr_320px]! gap-4! lg:gap-5! items-start!">
      <ContentCard variant="elevated" padding="large" hoverable>
        <div class="flex! items-center! gap-3! mb-2!">
          <div
            class="w-9! h-9! bg-blue-400! rounded-full! flex! items-center! justify-center! flex-shrink-0!"
          >
            <span class="text-lg!">💧</span>
          </div>
          <h1 class="text-base! font-bold! text-gray-800!">Transaksi Komisi SPS</h1>
        </div>

        <div class="grid! grid-cols-1! md:grid-cols-2! gap-4! mb-2!">
          <div class="flex! flex-col! gap-0.5!">
            <AppDatePicker
              v-model="form.tanggal"
              label="Tanggal Transaksi"
              placeholder="Pilih tanggal transaksi"
              @date-select="(date) => (form.tanggal = date)"
            />
          </div>

          <div class="flex! flex-col! gap-0.5!">
            <SelectSearch
              v-model="form.customerId"
              :options="customerOptions"
              label="Pelanggan"
              placeholder="Cari & pilih pelanggan"
              icon="user"
              @update:modelValue="onCustomerChange"
            />
          </div>
        </div>

        <BaseInput
          v-model="form.relasi"
          label="Relasi / Nama Pembayar"
          placeholder="Otomatis terisi dari data pelanggan"
          class="mb-2!"
          readonly
        />

        <div class="grid! grid-cols-1! md:grid-cols-2! gap-4! mb-2!">
          <div class="flex! flex-col! gap-0.5!">
            <MaksMoneyInput
              v-model="form.nominal"
              placeholder="0,00"
              label="Nominal Tagihan (Otomatis)"
              :readonly="true"
            />
            <span v-if="unpaidInfo" class="text-[11px]! text-slate-500! mt-1!">
              {{ unpaidInfo.bill_count }} tagihan unpaid • Pelanggan
              {{ unpaidInfo.customer?.customer_code || '-' }}
            </span>
          </div>

          <div class="flex! flex-col! gap-0.5!">
            <SelectSearch
              v-model="form.accountKas"
              :options="cashAccountOptions"
              label="Metode Pembayaran (Akun Kas)"
              placeholder="Pilih Akun Kas"
              icon="credit-card"
            />
          </div>
        </div>

        <BaseInput
          v-model="form.keterangan"
          label="Keterangan (Opsional)"
          placeholder="Catatan tambahan untuk transaksi"
          class="mb-2!"
        />

        <div class="flex! justify-end! mt-4!">
          <BaseButton
            variant="secondary"
            size="md"
            @click="simpanTransaksi"
            :disabled="isProcessing || !canSubmit"
            :loading="isProcessing"
            class="ml-auto! px-5! py-2! rounded-xl! shadow-lg! shadow-blue-200/50!"
          >
            Simpan Komisi SPS
          </BaseButton>
        </div>
      </ContentCard>

      <div class="flex! flex-col! gap-4! lg:sticky! lg:top-8!">
        <ContentCard variant="minimal" padding="normal" hoverable>
          <div class="flex! gap-3! items-start!">
            <div class="text-base! flex-shrink-0! mt-0.5!">ℹ️</div>
            <div class="text-xs! text-slate-600! leading-relaxed!">
              <strong>Bantuan Komisi SPS</strong><br />
              Pilih pelanggan untuk menampilkan total tagihan unpaid. Nominal akan
              terisi otomatis dari tagihan yang belum dibayar. Pastikan akun kas
              sudah benar sebelum menyimpan transaksi.
            </div>
          </div>
        </ContentCard>

        <ContentCard
          v-if="unpaidInfo && unpaidInfo.bills?.length"
          variant="minimal"
          padding="normal"
          hoverable
        >
          <div class="text-xs! font-bold! text-slate-700! mb-2!">
            Daftar Tagihan ({{ unpaidInfo.bill_count }})
          </div>
          <div class="max-h-48! overflow-y-auto! -mx-2!">
            <div
              v-for="bill in unpaidInfo.bills"
              :key="bill.id"
              class="px-2! py-1.5! border-b! border-slate-100! last:border-b-0! flex! justify-between! text-xs!"
            >
              <span class="text-slate-600!">
                {{ String(bill.billing_period_month).padStart(2, '0') }}/{{
                  bill.billing_period_year
                }}
              </span>
              <span class="font-mono! font-semibold! text-slate-800!">
                {{ formatRp(bill.total_amount) }}
              </span>
            </div>
          </div>
        </ContentCard>
      </div>
    </div>

    <AppNotification
      v-bind="notificationState"
      @close="handleClose"
      @confirm="handleConfirm"
      @cancel="handleCancel"
    />
  </div>
</template>

<script setup>
import { computed, onMounted, reactive, ref } from 'vue'
import { useUiStore } from '@/stores/uiStore'
import { useNotification } from '@/composables/useNotification'
import AppNotification from '@/presentations/components/ui/AppNotification.vue'
import BaseInput from '@/presentations/components/ui/BaseInput.vue'
import SelectSearch from '@/presentations/components/SelectSearch.vue'
import AppDatePicker from '@/presentations/components/AppDatePicker.vue'
import MaksMoneyInput from '@/presentations/components/MaksMoneyInput.vue'
import ContentCard from '@/presentations/components/ui/ContentCard.vue'
import BaseButton from '@/presentations/components/ui/BaseButton.vue'
import komisiSPSService from '@/services/komisiSPS.service'
import customerService from '@/services/customer.service'

const uiStore = useUiStore()
const {
  notificationState,
  success,
  error,
  warning,
  handleConfirm,
  handleCancel,
  handleClose,
} = useNotification()

const form = reactive({
  tanggal: new Date(),
  customerId: '',
  relasi: '',
  nominal: 0,
  accountKas: '',
  keterangan: '',
})
const isProcessing = ref(false)
const isLoadingCustomers = ref(false)
const isLoadingCash = ref(false)

const customers = ref([])
const cashAccounts = ref([])
const unpaidInfo = ref(null)

const customerOptions = computed(() => {
  const opts = [{ id: '', text: 'Pilih Pelanggan' }]
  customers.value.forEach((c) => {
    opts.push({
      id: c.id,
      text: `${c.customer_code || c.id} - ${c.nama || c.name || 'Tanpa Nama'}`,
    })
  })
  return opts
})

const cashAccountOptions = computed(() => {
  const opts = [{ id: '', text: 'Pilih Akun Kas' }]
  cashAccounts.value.forEach((a) => {
    opts.push({ id: a.kode_akun, text: `${a.kode_akun} - ${a.nama_akun}` })
  })
  return opts
})

const canSubmit = computed(
  () =>
    !!form.tanggal &&
    !!form.customerId &&
    !!form.accountKas &&
    Number(form.nominal) > 0,
)

const formatRp = (val) =>
  'Rp. ' +
  Number(val || 0).toLocaleString('id-ID', {
    minimumFractionDigits: 0,
    maximumFractionDigits: 0,
  })

const loadCustomers = async () => {
  isLoadingCustomers.value = true
  try {
    const res = await customerService.searchActive({ status: 'active' })
    if (res.success) {
      customers.value = res.data || []
    }
  } catch (e) {
    console.error('Gagal memuat pelanggan:', e)
    uiStore.error(e?.response?.data?.message || 'Gagal memuat daftar pelanggan.')
  } finally {
    isLoadingCustomers.value = false
  }
}

const loadCashAccounts = async () => {
  isLoadingCash.value = true
  try {
    const res = await komisiSPSService.getCashAccounts()
    if (res.success) {
      cashAccounts.value = res.data || []
    }
  } catch (e) {
    console.error('Gagal memuat akun kas:', e)
    uiStore.error(e?.response?.data?.message || 'Gagal memuat akun kas.')
  } finally {
    isLoadingCash.value = false
  }
}

const onCustomerChange = async (customerId) => {
  form.customerId = customerId
  form.relasi = ''
  form.nominal = 0
  unpaidInfo.value = null

  if (!customerId) return

  try {
    const res = await komisiSPSService.getUnpaidByCustomer(customerId)
    if (res.success) {
      unpaidInfo.value = res.data
      form.relasi =
        res.data?.customer?.nama || res.data?.customer?.customer_code || ''
      form.nominal = Number(res.data?.total_unpaid) || 0
    } else {
      unpaidInfo.value = null
      warning('Pelanggan', res.message || 'Pelanggan tidak ditemukan.')
    }
  } catch (e) {
    console.error('Gagal memuat tagihan:', e)
    uiStore.error(e?.response?.data?.message || 'Gagal memuat tagihan pelanggan.')
  }
}

const simpanTransaksi = async () => {
  if (!canSubmit.value) {
    error('Tidak Valid', 'Lengkapi data pelanggan, tanggal, dan akun kas.')
    return
  }

  isProcessing.value = true
  try {
    const payload = {
      tgl_transaksi: form.tanggal,
      customer_id: form.customerId,
      account_kas: form.accountKas,
      keterangan: form.keterangan || undefined,
    }
    const res = await komisiSPSService.store(payload)
    if (res.success) {
      success(
        'Berhasil!',
        `Transaksi komisi SPS sebesar ${formatRp(res.data?.total_nominal)} berhasil disimpan.`,
      )
      form.customerId = ''
      form.relasi = ''
      form.nominal = 0
      form.accountKas = ''
      form.keterangan = ''
      unpaidInfo.value = null
    } else {
      error('Gagal', res.message || 'Gagal menyimpan transaksi.')
    }
  } catch (e) {
    console.error('Error menyimpan transaksi:', e)
    error(
      'Kesalahan',
      e?.response?.data?.message || 'Gagal menyimpan transaksi.',
    )
  } finally {
    isProcessing.value = false
  }
}

onMounted(async () => {
  await Promise.all([loadCustomers(), loadCashAccounts()])
})
</script>