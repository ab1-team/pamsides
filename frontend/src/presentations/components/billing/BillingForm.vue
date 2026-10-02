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

    <!-- Warning: Pembayaran sebelum tanggal generate tunggakan (advance / lupa catat) -->
    <div
      v-if="paidBeforeDueDate"
      class="mb-5! p-3! rounded-xl! border-2! border-amber-400! bg-amber-50! flex! items-start! gap-2.5!"
    >
      <div
        class="w-8! h-8! rounded-lg! bg-amber-500! text-white! flex! items-center! justify-center! flex-shrink-0! shadow-sm!"
      >
        <font-awesome-icon icon="exclamation-triangle" class="text-sm!" />
      </div>
      <div class="flex-1! min-w-0! text-xs! text-amber-900!">
        <div class="font-extrabold! text-amber-800! mb-1.5! flex! items-center! gap-1.5!">
          <span>Pembayaran Sebelum Tanggal Generate Tunggakan</span>
          <span class="px-1.5! py-0.5! rounded! bg-amber-200! text-amber-900! text-[9px]! font-extrabold! uppercase! tracking-wide!">
            Advance
          </span>
        </div>

        <!-- Konsekuensi: piutang dihapus, disimpan sbg pemakaian bulan tsb -->
        <div class="space-y-1!">
          <div class="flex! items-start! gap-1.5!">
            <font-awesome-icon
              icon="trash-alt"
              class="text-rose-600! text-[10px]! mt-0.5! flex-shrink-0!"
            />
            <span>
              Jurnal piutang
              <span class="font-mono! font-bold!">overdue_bill</span>
              yang pernah tercatat untuk tagihan ini akan
              <strong class="text-rose-700!">dihapus permanen</strong>.
            </span>
          </div>
          <div class="flex! items-start! gap-1.5!">
            <font-awesome-icon
              icon="file-invoice-dollar"
              class="text-emerald-600! text-[10px]! mt-0.5! flex-shrink-0!"
            />
            <span>
              Pembayaran akan disimpan sebagai
              <strong class="text-emerald-700!">pemakaian bulan
              {{ formatDueDate(formData.dueDate, true) }}</strong>
              dengan tanggal
              <strong class="font-extrabold!">{{ formatDueDate(tanggalStr) }}</strong>
              (sesuai tanggal uang benar-benar diterima).
            </span>
          </div>
        </div>
      </div>
    </div>

    <!-- Warning: Ada tagihan lebih lama yang belum dibayar (harus bayar dari yang terlama dulu) -->
    <div
      v-if="hasOlderUnpaid"
      class="mb-5! p-3! rounded-xl! border! border-orange-300! bg-orange-50! flex! items-start! gap-2.5!"
    >
      <div
        class="w-7! h-7! rounded-lg! bg-orange-500! text-white! flex! items-center! justify-center! flex-shrink-0! shadow-sm!"
      >
        <font-awesome-icon icon="exclamation-triangle" class="text-xs!" />
      </div>
      <div class="flex-1! min-w-0! text-xs! text-orange-900!">
        <div class="font-extrabold! text-orange-800! mb-0.5!">
          Selesaikan Tagihan Lebih Lama Dulu
        </div>
        <div class="leading-relaxed!">
          Masih ada tagihan lebih lama yang belum dibayar untuk pelanggan ini.
          Pembayaran harus dilakukan urut dari tagihan paling lama.
          Tombol konfirmasi saat ini <strong>nonaktif</strong> &mdash;
          bayar dulu tagihan tertunggak di atas.
        </div>
      </div>
    </div>

    <!-- Baris 1: Tanggal Pembayaran -->
    <div class="grid! grid-cols-1! sm:grid-cols-3! gap-4! mb-5! items-end!">
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
          class="w-full! px-3! h-11! text-sm! font-semibold! text-slate-700! bg-slate-50! border! border-slate-200! rounded-lg! cursor-not-allowed!"
        />
      </div>
      <div>
        <label class="block! text-xs! font-bold! text-slate-500! mb-1.5!">Meter Akhir (m³)</label>
        <input
          type="text"
          :value="formatMeter(formData.meterAkhir)"
          disabled
          class="w-full! px-3! h-11! text-sm! font-semibold! text-slate-700! bg-slate-50! border! border-slate-200! rounded-lg! cursor-not-allowed!"
        />
      </div>
    </div>

    <!-- Baris 2: Pemakaian, Tagihan, Abodemen, Denda -->
    <div class="grid! grid-cols-2! sm:grid-cols-4! gap-4! mb-5! items-end!">
      <div>
        <label class="block! text-xs! font-bold! text-slate-500! mb-1.5!">Pemakaian (m³)</label>
        <input
          type="text"
          :value="formatMeter(formData.pemakaian)"
          disabled
          class="w-full! px-3! h-11! text-sm! font-semibold! text-cyan-700! bg-slate-50! border! border-slate-200! rounded-lg! cursor-not-allowed!"
        />
      </div>
      <div>
        <label class="block! text-xs! font-bold! text-slate-500! mb-1.5!">Tagihan Air</label>
        <input
          type="text"
          :value="formatRupiah(formData.tagihan)"
          disabled
          class="w-full! px-3! h-11! text-sm! font-semibold! text-slate-700! bg-slate-50! border! border-slate-200! rounded-lg! cursor-not-allowed!"
        />
      </div>
      <div>
        <label class="block! text-xs! font-bold! text-slate-500! mb-1.5!">Abodemen</label>
        <input
          type="text"
          :value="formatRupiah(formData.abodemen)"
          disabled
          class="w-full! px-3! h-11! text-sm! font-semibold! text-slate-700! bg-slate-50! border! border-slate-200! rounded-lg! cursor-not-allowed!"
        />
      </div>
      <div>
        <label class="block! text-xs! font-bold! text-slate-500! mb-1.5!">Denda</label>
        <input
          type="text"
          :value="formatRupiah(formData.denda)"
          disabled
          class="w-full! px-3! h-11! text-sm! font-semibold! text-red-500! bg-slate-50! border! border-slate-200! rounded-lg! cursor-not-allowed!"
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
        class="w-full! px-3! h-11! text-base! font-extrabold! text-cyan-700! bg-cyan-50/50! border! border-cyan-200! rounded-lg! cursor-not-allowed!"
      />
    </div>

    <!-- Metode Pembayaran: Tunai / Transfer ke Bank -->
    <div class="mb-5!">
      <label class="block! text-xs! font-bold! text-slate-500! mb-2!">
        Metode Pembayaran <span class="text-rose-500!">*</span>
      </label>
      <div class="grid! grid-cols-1! sm:grid-cols-2! gap-2.5!">
        <!-- Tunai -->
        <label
          :class="[
            'group! relative! flex! items-start! gap-3! p-3! rounded-xl! border-2! cursor-pointer! transition-all! select-none!',
            paymentMethod === 'cash'
              ? 'border-emerald-500! bg-emerald-50! shadow-sm!'
              : 'border-slate-200! bg-white! hover:border-slate-300! hover:bg-slate-50/50!',
          ]"
        >
          <input
            type="checkbox"
            :checked="paymentMethod === 'cash'"
            @change="setPaymentMethod('cash')"
            class="sr-only! peer!"
          />
          <!-- Custom checkbox indicator -->
          <div
            :class="[
              'flex-shrink-0! w-5! h-5! rounded-md! border-2! flex! items-center! justify-center! transition-all! mt-0.5!',
              paymentMethod === 'cash'
                ? 'border-emerald-500! bg-emerald-500!'
                : 'border-slate-300! bg-white! group-hover:border-slate-400!',
            ]"
          >
            <font-awesome-icon
              v-if="paymentMethod === 'cash'"
              icon="check"
              class="text-white! text-[10px]!"
            />
          </div>
          <div class="flex-1! min-w-0!">
            <div class="flex! items-center! gap-1.5!">
              <font-awesome-icon
                icon="money-bill-wave"
                :class="paymentMethod === 'cash' ? 'text-emerald-600!' : 'text-slate-400!'"
                class="text-sm!"
              />
              <span
                :class="paymentMethod === 'cash' ? 'text-emerald-900!' : 'text-slate-700!'"
                class="text-sm! font-extrabold!"
              >
                Tunai
              </span>
            </div>
            <p
              :class="paymentMethod === 'cash' ? 'text-emerald-700/80!' : 'text-slate-500!'"
              class="text-[10px]! mt-0.5! leading-tight!"
            >
              Kas Tunai <span class="font-mono!">(1.1.01.01)</span>
            </p>
          </div>
        </label>

        <!-- Transfer ke Bank -->
        <label
          :class="[
            'group! relative! flex! items-start! gap-3! p-3! rounded-xl! border-2! cursor-pointer! transition-all! select-none!',
            paymentMethod === 'transfer_bri'
              ? 'border-sky-500! bg-sky-50! shadow-sm!'
              : 'border-slate-200! bg-white! hover:border-slate-300! hover:bg-slate-50/50!',
          ]"
        >
          <input
            type="checkbox"
            :checked="paymentMethod === 'transfer_bri'"
            @change="setPaymentMethod('transfer_bri')"
            class="sr-only! peer!"
          />
          <!-- Custom checkbox indicator -->
          <div
            :class="[
              'flex-shrink-0! w-5! h-5! rounded-md! border-2! flex! items-center! justify-center! transition-all! mt-0.5!',
              paymentMethod === 'transfer_bri'
                ? 'border-sky-500! bg-sky-500!'
                : 'border-slate-300! bg-white! group-hover:border-slate-400!',
            ]"
          >
            <font-awesome-icon
              v-if="paymentMethod === 'transfer_bri'"
              icon="check"
              class="text-white! text-[10px]!"
            />
          </div>
          <div class="flex-1! min-w-0!">
            <div class="flex! items-center! gap-1.5!">
              <font-awesome-icon
                icon="university"
                :class="paymentMethod === 'transfer_bri' ? 'text-sky-600!' : 'text-slate-400!'"
                class="text-sm!"
              />
              <span
                :class="paymentMethod === 'transfer_bri' ? 'text-sky-900!' : 'text-slate-700!'"
                class="text-sm! font-extrabold!"
              >
                Transfer ke Bank
              </span>
            </div>
            <p
              :class="paymentMethod === 'transfer_bri' ? 'text-sky-700/80!' : 'text-slate-500!'"
              class="text-[10px]! mt-0.5! leading-tight!"
            >
              Kas di Bank BRI <span class="font-mono!">(1.1.01.03)</span>
            </p>
          </div>
        </label>
      </div>
      <p
        v-if="!paymentMethod"
        class="mt-2! text-[10px]! text-amber-600! font-semibold! flex! items-center! gap-1!"
      >
        <font-awesome-icon icon="exclamation-circle" class="text-[11px]!" />
        Pilih metode pembayaran terlebih dahulu.
      </p>
    </div>

    <!-- Tombol Konfirmasi -->
    <div class="flex! justify-end! pt-4! pb-2! border-t! border-slate-200/60!">
      <button
        :disabled="hasOlderUnpaid || !paymentMethod"
        :class="hasOlderUnpaid || !paymentMethod
          ? 'px-6! py-2.5! text-sm! font-bold! text-slate-500! bg-slate-200! cursor-not-allowed! rounded-xl! flex! items-center! gap-2!'
          : 'px-6! py-2.5! text-sm! font-bold! text-white! bg-emerald-500! hover:bg-emerald-600! rounded-xl! shadow-lg! shadow-emerald-200/50! transition-all! flex! items-center! gap-2!'"
        @click="handleSave"
        @click.stop
      >
        <font-awesome-icon :icon="paymentMethod === 'transfer_bri' ? 'university' : 'check-circle'" />
        Konfirmasi Pembayaran
      </button>
    </div>
  </div>
</template>

<script setup>
import { reactive, ref, computed, onMounted } from 'vue'
import AppDatePicker from '../AppDatePicker.vue'
import { formatRupiah } from '@/composables/useFormatCurrency.js'
import { useSettingsStore } from '@/stores/settingsStore.js'
import { MySwal, Toast } from '@/utils/swal.js'

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
const settingsStore = useSettingsStore()

// Pastikan settings.toleransiTunggakan sudah di-load dari backend SEBELUM
// computed `paidBeforeDueDate` & `thresholdDateStr` dievaluasi.
// Sebelumnya store default = 0 → notifikasi ADVANCE tidak pernah muncul kalau
// user belum pernah ke halaman SOP (yang sebelumnya satu-satunya pemanggil loadSettings).
// Idempotent: store punya flag `isLoaded`, jadi aman dipanggil berulang.
onMounted(() => {
  settingsStore.loadSettings()
})

// Flag dari parent: true kalau masih ada tagihan LEBIH LAMA yang belum lunas.
// Tombol konfirmasi di-disable supaya teknisi bayar dari yang terlama dulu.
const hasOlderUnpaid = computed(() => !!props.initialData?.hasOlderUnpaid)

// Metode pembayaran: 'cash' (Tunai) atau 'transfer_bri' (Transfer ke kas BRI).
// Nilai 'transfer_bri' adalah kode internal FE; akan diterjemahkan ke 'transfer'
// saat dikirim ke backend (lihat emit('save') di bawah).
const paymentMethod = ref(props.initialData?.paymentMethod || '')

const setPaymentMethod = (method) => {
  // Toggle: kalau user klik checkbox yang sama → uncheck (kosongkan).
  // Kalau klik yang lain → pindah.
  const nextValue = paymentMethod.value === method ? '' : method
  paymentMethod.value = nextValue

  // Notifikasi toast singkat setiap kali user memilih metode bayar,
  // supaya yakin rekening/tujuan kas yang dicatat sudah benar.
  // Pakai `Toast` mixin dari utils/swal.js.
  //
  // PENTING: SweetAlert2 inject HTML toast ke DOM di LUAR Vue tree, jadi tag
  // kustom Vue seperti <font-awesome-icon> TIDAK ter-resolve di sana
  // (render jadi tag kosong). Solusinya: pakai inline SVG manual yang pasti
  // render di mana saja, tanpa butuh Vue component resolution.
  if (nextValue === 'cash') {
    Toast.fire({
      html: `
        <div class="flex! items-start! gap-2.5! text-left!">
          <div class="w-8! h-8! rounded-lg! bg-emerald-500! flex! items-center! justify-center! flex-shrink-0! shadow-sm!">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="white" class="w-4! h-4!">
              <path d="M2 6a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v2H2V6Zm0 4h20v8a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2v-8Zm10 5a3 3 0 1 0 0-6 3 3 0 0 0 0 6Z"/>
            </svg>
          </div>
          <div class="flex-1! min-w-0!">
            <div class="font-extrabold! text-emerald-700! text-sm! leading-tight!">
              Tunai
            </div>
            <div class="text-xs! text-slate-600! leading-tight! mt-0.5!">
              Dicatat ke <strong class="text-slate-700!">Kas Tunai</strong>
              <span class="font-mono! text-[10px]! text-slate-500!">(1.1.01.01)</span>.
            </div>
          </div>
        </div>
      `,
      timer: 3500,
    })
  } else if (nextValue === 'transfer_bri') {
    Toast.fire({
      html: `
        <div class="flex! items-start! gap-2.5! text-left!">
          <div class="w-8! h-8! rounded-lg! bg-sky-500! flex! items-center! justify-center! flex-shrink-0! shadow-sm!">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="white" class="w-4! h-4!">
              <path d="M2 9.5 12 3l10 6.5V20a1 1 0 0 1-1 1h-5v-7h-8v7H3a1 1 0 0 1-1-1V9.5Zm10 2.5a2 2 0 1 0 0-4 2 2 0 0 0 0 4Z"/>
            </svg>
          </div>
          <div class="flex-1! min-w-0!">
            <div class="font-extrabold! text-sky-700! text-sm! leading-tight!">
              Transfer ke Bank BRI
            </div>
            <div class="text-xs! text-slate-600! leading-tight! mt-0.5!">
              Dicatat ke <strong class="text-slate-700!">Kas di Bank BRI</strong>
              <span class="font-mono! text-[10px]! text-slate-500!">(1.1.01.03)</span>.
            </div>
            <div class="text-[10px]! italic! text-slate-500! leading-tight! mt-1!">
              Pastikan pelanggan sudah transfer ke rekening BRI PAMSIMAS
              sebelum konfirmasi.
            </div>
          </div>
        </div>
      `,
      timer: 4500,
    })
  }
}

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
  // BULAN PEMAKAIAN tagihan (= bulan label periode). Dipakai untuk hitung
  // threshold ADVANCE payment: tahun-bulan dari nilai ini, hari = toleransi_tunggakan.
  billingPeriodMonth: props.initialData.billingPeriodMonth ?? null,
  billingPeriodYear: props.initialData.billingPeriodYear ?? null,
})

const formatMeter = (val) => {
  const num = Number(val)
  if (isNaN(num)) return val
  return Number.isInteger(num) ? num.toString() : num.toFixed(2)
}

/**
 * Parse tanggal dari string `YYYY-MM-DD` jadi local Date (jam 00:00 LOCAL).
 * `new Date('YYYY-MM-DD')` di JS di-interpret sebagai UTC midnight, bukan
 * local midnight. Untuk pembandingan tanggal, parse eksplisit sebagai LOCAL.
 */
const parseLocalDate = (val) => {
  if (!val) return null
  if (val instanceof Date) {
    if (Number.isNaN(val.getTime())) return null
    return new Date(val.getFullYear(), val.getMonth(), val.getDate())
  }
  if (typeof val === 'string') {
    const m = val.match(/^(\d{4})-(\d{2})-(\d{2})/)
    if (m) {
      const y = Number(m[1])
      const mo = Number(m[2]) - 1
      const d = Number(m[3])
      return new Date(y, mo, d)
    }
    const dt = new Date(val)
    if (Number.isNaN(dt.getTime())) return null
    return new Date(dt.getFullYear(), dt.getMonth(), dt.getDate())
  }
  return null
}

const formatDueDate = (dateStr, monthOnly = false) => {
  if (!dateStr) return '-'
  const d = parseLocalDate(dateStr) || new Date(dateStr)
  if (monthOnly) {
    return d.toLocaleDateString('id-ID', { month: 'long', year: 'numeric' })
  }
  return d.toLocaleDateString('id-ID', { day: 'numeric', month: 'long', year: 'numeric' })
}

const isOverdue = computed(() => {
  if (!formData.dueDate) return false
  const due = parseLocalDate(formData.dueDate)
  if (!due) return false
  const today = new Date()
  const todayLocal = new Date(today.getFullYear(), today.getMonth(), today.getDate())
  return todayLocal.getTime() > due.getTime()
})

const overdueDays = computed(() => {
  if (!formData.dueDate) return 0
  const due = parseLocalDate(formData.dueDate)
  if (!due) return 0
  const today = new Date()
  const todayLocal = new Date(today.getFullYear(), today.getMonth(), today.getDate())
  const diff = todayLocal.getTime() - due.getTime()
  return Math.max(0, Math.floor(diff / (1000 * 60 * 60 * 24)))
})

/**
 * Hitung & format tanggal threshold (hari toleransi_tunggakan di BULAN
 * PEMAKAIAN tagihan = billing_period_month/year).
 * Return string `YYYY-MM-DD` atau null kalau tidak bisa dihitung.
 */
const thresholdDateStr = computed(() => {
  const tgl = Number(settingsStore.toleransiTunggakan || 0)
  if (tgl < 1) return null
  let year, month
  if (formData.billingPeriodMonth && formData.billingPeriodYear) {
    year = Number(formData.billingPeriodYear)
    month = Number(formData.billingPeriodMonth) - 1
  } else if (formData.dueDate) {
    const due = parseLocalDate(formData.dueDate)
    if (!due) return null
    year = due.getFullYear()
    month = due.getMonth()
  } else {
    return null
  }
  if (Number.isNaN(year) || Number.isNaN(month)) return null
  const daysInMonth = new Date(year, month + 1, 0).getDate()
  const effectiveDay = Math.min(tgl, daysInMonth)
  const yyyy = String(year)
  const mm = String(month + 1).padStart(2, '0')
  const dd = String(effectiveDay).padStart(2, '0')
  return `${yyyy}-${mm}-${dd}`
})

/**
 * TRUE kalau tanggal pembayaran LEBIH AWAL dari threshold.
 * Logika SAMA dengan backend `MonthlyBillController::pay`:
 *   - threshold = hari `toleransi_tunggakan` di BULAN PEMAKAIAN tagihan
 *   - bayar < threshold → ADVANCE (piutang overdue_bill akan di-hapus)
 *   - bayar >= threshold → NORMAL
 */
const paidBeforeDueDate = computed(() => {
  try {
    if (!tanggalStr.value) return false
    const tgl = Number(settingsStore.toleransiTunggakan || 0)
    if (tgl < 1) return false

    const paid = parseLocalDate(tanggalStr.value)
    if (!paid) return false

    let year, month
    if (formData.billingPeriodMonth && formData.billingPeriodYear) {
      year = Number(formData.billingPeriodYear)
      month = Number(formData.billingPeriodMonth) - 1
    } else if (formData.dueDate) {
      const due = parseLocalDate(formData.dueDate)
      if (!due) return false
      year = due.getFullYear()
      month = due.getMonth()
    } else {
      return false
    }
    if (Number.isNaN(year) || Number.isNaN(month)) return false

    const daysInMonth = new Date(year, month + 1, 0).getDate()
    const effectiveDay = Math.min(tgl, daysInMonth)
    const threshold = new Date(year, month, effectiveDay)

    return paid.getTime() < threshold.getTime()
  } catch {
    return false
  }
})

const handleSave = () => {
  // Cegah klik kalau ada tagihan lebih lama yang belum lunas.
  if (hasOlderUnpaid.value) {
    MySwal.fire({
      icon: 'warning',
      title: 'Bayar dari Tagihan Terlama',
      text: 'Masih ada tagihan lebih lama yang belum dibayar. Selesaikan tagihan paling lama dulu sebelum membayar tagihan ini.',
      confirmButtonColor: '#0EA5E9',
    })
    return
  }

  // Wajib pilih metode pembayaran dulu.
  if (!paymentMethod.value) {
    MySwal.fire({
      icon: 'warning',
      title: 'Pilih Metode Pembayaran',
      text: 'Pilih Tunai atau Transfer Bank BRI terlebih dahulu.',
      confirmButtonColor: '#0EA5E9',
    })
    return
  }

  if (!tanggalStr.value) {
    MySwal.fire({
      icon: 'warning',
      title: 'Tanggal belum dipilih',
      text: 'Mohon pilih tanggal pembayaran terlebih dahulu.',
      confirmButtonColor: '#0EA5E9',
    })
    return
  }

  // Advance payment: tampilkan konfirmasi bahwa piutang overdue_bill akan dihapus.
  if (paidBeforeDueDate.value) {
    MySwal.fire({
      icon: 'warning',
      iconColor: '#F59E0B',
      title: 'Hapus Piutang & Catat Sebagai Pemakaian Bulan ' + formatDueDate(formData.dueDate, true),
      html: `
        <div class="text-left! text-sm! leading-relaxed!">
          <div class="p-3! rounded-lg! border! border-rose-200! bg-rose-50/60! space-y-2!">
            <div class="flex! items-start! gap-2!">
              <div class="w-5! h-5! rounded! bg-rose-500! text-white! flex! items-center! justify-center! flex-shrink-0!">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="white" class="w-3! h-3!">
                  <path d="M9 3h6l1 2h4v2H4V5h4l1-2Zm-3 6h12l-1 12H7L6 9Zm3 2v8h2v-8H9Zm4 0v8h2v-8h-2Z"/>
                </svg>
              </div>
              <div class="flex-1!">
                <div class="font-extrabold! text-rose-800! text-xs! uppercase! tracking-wide!">
                  Piutang akan dihapus
                </div>
                <div class="text-xs! text-rose-900! leading-snug! mt-0.5!">
                  Jurnal <span class="font-mono! font-bold!">overdue_bill</span>
                  yang pernah tercatat untuk tagihan ini akan
                  <strong>dihapus permanen</strong>.
                </div>
              </div>
            </div>
            <div class="flex! items-start! gap-2!">
              <div class="w-5! h-5! rounded! bg-emerald-500! text-white! flex! items-center! justify-center! flex-shrink-0!">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="white" class="w-3! h-3!">
                  <path d="M5 3h11l5 5v13a1 1 0 0 1-1 1H5a1 1 0 0 1-1-1V4a1 1 0 0 1 1-1Zm10 1.5V8h3.5L15 4.5ZM7 13h10v2H7v-2Zm0 4h10v2H7v-2Z"/>
                </svg>
              </div>
              <div class="flex-1!">
                <div class="font-extrabold! text-emerald-800! text-xs! uppercase! tracking-wide!">
                  Disimpan sebagai pemakaian bulan tersebut
                </div>
                <div class="text-xs! text-emerald-900! leading-snug! mt-0.5!">
                  Pembayaran akan disimpan sebagai
                  <strong class="text-emerald-700!">pemakaian bulan
                  ${formatDueDate(formData.dueDate, true)}</strong>
                  dengan tanggal
                  <strong>${formatDueDate(tanggalStr.value)}</strong>
                  (sesuai tanggal uang benar-benar diterima).
                </div>
              </div>
            </div>
          </div>
        </div>
      `,
      showCancelButton: true,
      confirmButtonText: 'Ya, Hapus Piutang & Lanjutkan',
      cancelButtonText: 'Batal, Kembali ke Form',
      confirmButtonColor: '#F59E0B',
      cancelButtonColor: '#94A3B8',
      customClass: {
        popup: 'rounded-2xl!',
        confirmButton: 'rounded-xl! px-4! py-2! font-bold!',
        cancelButton: 'rounded-xl! px-4! py-2! font-bold!',
        title: 'text-base! leading-tight!',
      },
    }).then((result) => {
      if (result.isConfirmed) {
        emit('save', { ...formData, tanggal: tanggalStr.value, paymentMethod: paymentMethod.value })
      }
    })
  } else {
    emit('save', { ...formData, tanggal: tanggalStr.value, paymentMethod: paymentMethod.value })
  }
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
