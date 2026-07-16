<template>
  <div>
    <div class="grid! grid-cols-1! lg:grid-cols-[1fr_320px]! gap-4! lg:gap-5! items-start!">
      <ContentCard variant="elevated" padding="large" hoverable>
        <div class="komisi-sps-form flex! flex-col! gap-2!">
        <div class="flex! items-center! gap-3! mb-4!">
          <h1 class="text-base! font-bold! text-gray-800!">Transaksi Komisi SPS</h1>
        </div>

        <div class="grid! grid-cols-1! md:grid-cols-2! gap-4!">
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
              v-model="form.customerIds"
              :options="customerOptions"
              :multiple="true"
              label="Pelanggan"
              placeholder="Pilih satu atau lebih pelanggan"
              icon="users"
              @update:modelValue="onCustomersChange"
            />
          </div>
        </div>

        <BaseInput
          v-model="form.relasi"
          label="Relasi / Nama Pembayar"
          placeholder="Otomatis terisi dari data pelanggan"
          readonly
        />

        <div class="grid! grid-cols-1! md:grid-cols-2! gap-4!">
          <div class="flex! flex-col! gap-0.5!">
            <BaseInput
              :model-value="formatRp(form.totalTagihan)"
              label="Total Tagihan"
              placeholder="Rp. 0,00"
              :readonly="true"
            />
            <span v-if="unpaidInfo" class="text-[11px]! text-slate-500! mt-1!">
              {{ unpaidInfo.bill_count }} tagihan unpaid • Pelanggan
              {{ unpaidInfo.customer?.customer_code || '-' }}
            </span>
          </div>

          <div class="flex! flex-col! gap-0.5!">
            <MaksMoneyInput
              v-model="form.nominal"
              placeholder="0,00"
              label="Nominal Komisi"
              :readonly="true"
              hide-prefix
            />
          </div>
        </div>

        <div class="grid! grid-cols-1! md:grid-cols-2! gap-4!">
          <div class="flex! flex-col! gap-0.5!">
            <SelectSearch
              v-model="form.penerimaId"
              :options="penerimaOptions"
              label="Penerima Komisi"
              placeholder="Pilih penerima komisi"
              icon="user-check"
            />
       
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
        />
        </div>

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
          v-if="unpaidSummary.length > 0"
          variant="minimal"
          padding="normal"
          hoverable
        >
          <div class="text-xs! font-bold! text-slate-700! mb-2!">
            Total ({{ unpaidSummary.length }} Pelanggan)
          </div>
          <div class="max-h-60! overflow-y-auto! -mx-2!">
            <div
              v-for="row in unpaidSummary"
              :key="row.id"
              class="px-2! py-1.5! border-b! border-slate-100! last:border-b-0! flex! justify-between! text-xs!"
            >
              <div class="flex! flex-col!">
                <span class="text-slate-700! font-medium!">
                  {{ row.customer?.customer_code || row.id }}
                </span>
                <span class="text-slate-500!">
                  {{ row.customer?.nama || '-' }} • {{ row.bill_count }} Tagihan
                </span>
              </div>
              <span class="font-mono! font-semibold! text-slate-800!">
                {{ formatRp(row.total_unpaid) }}
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

const KOMISI_RATE = 0.1

const form = reactive({
  tanggal: new Date(),
  customerIds: [],
  relasi: '',
  totalTagihan: 0,
  nominal: 0,
  accountKas: '',
  penerimaId: '',
  keterangan: '',
})
const isProcessing = ref(false)
const isLoadingCustomers = ref(false)
const isLoadingCash = ref(false)
const isLoadingPenerima = ref(false)
const includeAllPenerima = ref(true)

const customers = ref([])
const cashAccounts = ref([])
const penerimaList = ref([])
const unpaidSummary = ref([])

const customerOptions = computed(() =>
  customers.value.map((c) => ({
    id: c.id,
    text: `${c.customer_code || c.id} - ${c.nama || c.name || 'Tanpa Nama'}`,
  })),
)

const cashAccountOptions = computed(() => {
  const opts = [{ id: '', text: 'Pilih Akun Kas' }]
  cashAccounts.value.forEach((a) => {
    opts.push({ id: a.kode_akun, text: `${a.kode_akun} - ${a.nama_akun}` })
  })
  return opts
})

const penerimaOptions = computed(() => {
  const opts = [{ id: '', text: 'Pilih Penerima Komisi' }]
  penerimaList.value.forEach((u) => {
    opts.push({
      id: u.id,
      text: `${u.name}${u.role ? ` (${u.role})` : ''}`,
    })
  })
  return opts
})

const canSubmit = computed(
  () =>
    !!form.tanggal &&
    Array.isArray(form.customerIds) &&
    form.customerIds.length > 0 &&
    !!form.accountKas &&
    !!form.penerimaId &&
    Number(form.nominal) > 0,
)

const formatRp = (val) =>
  Number(val || 0).toLocaleString('id-ID', {
    minimumFractionDigits: 2,
    maximumFractionDigits: 2,
  });

const hitungKomisi = (totalTagihan) => {
  const t = Number(totalTagihan) || 0
  return Math.round(t * KOMISI_RATE * 100) / 100
}

const loadCustomers = async (q = '') => {
  isLoadingCustomers.value = true
  try {
    const res = await komisiSPSService.getPelangganWithUnpaid(q ? { q } : {})
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

const loadPenerimaKomisi = async () => {
  isLoadingPenerima.value = true
  try {
    const res = await komisiSPSService.getPenerimaKomisi({
      include_all: includeAllPenerima.value ? 1 : 0,
    })
    if (res.success) {
      const all = [
        ...(res.data?.default || []),
        ...(res.data?.others || []),
      ]
      penerimaList.value = all
      if (!form.penerimaId && res.data?.default?.length) {
        const teknisi = res.data.default.find((u) => u.role === 'teknisi')
        form.penerimaId = (teknisi || res.data.default[0])?.id ?? ''
      }
    }
  } catch (e) {
    console.error('Gagal memuat penerima komisi:', e)
    uiStore.error(
      e?.response?.data?.message || 'Gagal memuat daftar penerima komisi.',
    )
  } finally {
    isLoadingPenerima.value = false
  }
}

const onCustomersChange = async (customerIds) => {
  const ids = Array.isArray(customerIds)
    ? customerIds.filter((x) => x !== '' && x !== null && x !== undefined)
    : []

  form.customerIds = ids
  form.relasi = ''
  form.totalTagihan = 0
  form.nominal = 0
  unpaidSummary.value = []

  if (ids.length === 0) return

  try {
    const results = await Promise.all(
      ids.map((id) => komisiSPSService.getUnpaidByCustomer(id)),
    )
    const summary = []
    let totalUnpaid = 0
    const relasiNames = []

    results.forEach((res, idx) => {
      const id = ids[idx]
      if (res.success && res.data) {
        const customer = res.data.customer || {}
        const unpaid = Number(res.data.total_unpaid) || 0
        summary.push({
          id,
          customer,
          total_unpaid: unpaid,
          bill_count: res.data.bill_count || 0,
          nominal_komisi: Number(res.data.nominal_komisi) || 0,
          bills: res.data.bills || [],
        })
        totalUnpaid += unpaid
        if (customer.nama || customer.customer_code) {
          relasiNames.push(customer.nama || customer.customer_code)
        }
      }
    })

    unpaidSummary.value = summary
    form.totalTagihan = Math.round(totalUnpaid * 100) / 100
    form.nominal = hitungKomisi(totalUnpaid)
    form.relasi = relasiNames.length
      ? relasiNames.join(', ')
      : `${summary.length} pelanggan`
  } catch (e) {
    console.error('Gagal memuat tagihan:', e)
    uiStore.error(e?.response?.data?.message || 'Gagal memuat tagihan pelanggan.')
  }
}

const simpanTransaksi = async () => {
  if (!canSubmit.value) {
    error(
      'Tidak Valid',
      'Lengkapi data pelanggan, tanggal, penerima komisi, dan akun kas.',
    )
    return
  }

  isProcessing.value = true
  try {
    const payload = {
      tgl_transaksi: form.tanggal,
      customer_ids: form.customerIds,
      account_kas: form.accountKas,
      penerima_komisi_id: form.penerimaId,
      keterangan: form.keterangan || undefined,
    }
    const res = await komisiSPSService.store(payload)
    if (res.success) {
      const nominalKomisi = Number(res.data?.nominal_komisi) || 0
      const processed = res.data?.bill_count || 0
      success(
        'Berhasil!',
        `Transaksi komisi SPS disimpan untuk ${processed} Tagihan (${form.customerIds.length} pelanggan). Nominal komisi: ${formatRp(nominalKomisi)}. Jurnal 5.1.02.04 → 2.1.02.02 terposting otomatis.`,
      )
      form.customerIds = []
      form.relasi = ''
      form.totalTagihan = 0
      form.nominal = 0
      form.accountKas = ''
      form.keterangan = ''
      form.penerimaId = ''
      unpaidSummary.value = []
      await loadPenerimaKomisi()
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
  await Promise.all([
    loadCustomers(),
    loadCashAccounts(),
    loadPenerimaKomisi(),
  ])
})
</script>

<style scoped>
.komisi-sps-form :deep(label.label),
.komisi-sps-form :deep(.select-label),
.komisi-sps-form :deep(.base-input__label),
.komisi-sps-form :deep(.currency-label),
.komisi-sps-form :deep(.base-select__label),
.komisi-sps-form :deep(.komisi-sps-label) {
  display: block !important;
  font-size: 0.875rem !important;
  font-weight: 600 !important;
  color: #334155 !important;
  margin-bottom: 0.375rem !important;
  margin-left: 0.25rem !important;
}

</style>
