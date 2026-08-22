<template>
  <div class="tagihan-instalasi-root!">
    <div class="flex! flex-col! lg:flex-row! lg:items-end! lg:justify-between! gap-4! mb-6!">
      <div>
        <div class="text-xs! font-bold! text-slate-500! uppercase! tracking-wider! mb-1!">
          Tagihan & Pembayaran
        </div>
        <h1 class="text-2xl! sm:text-3xl! font-extrabold! text-slate-800! tracking-tight!">
          Tagihan Instalasi
        </h1>
        <p class="text-sm! text-slate-500! mt-1! max-w-2xl!">
          Kelola tagihan biaya pasang baru pelanggan. Mendukung pembayaran sebagian (cicilan) dan
          pelunasan. Status tiket akan otomatis lanjut ke Diproses ketika sudah lunas.
        </p>
      </div>

      <div class="flex! flex-wrap! gap-2!">
        <div
          class="px-4! py-2! rounded-xl! bg-white! border! border-amber-200! shadow-sm! flex! items-center! gap-2!"
        >
          <div class="w-2! h-2! rounded-full! bg-amber-500!"></div>
          <div>
            <div class="text-[10px]! font-bold! text-slate-400! uppercase! tracking-wider!">
              Disurvei
            </div>
            <div class="text-sm! font-extrabold! text-amber-600!">{{ counts.surveyed }}</div>
          </div>
        </div>
        <div
          class="px-4! py-2! rounded-xl! bg-white! border! border-orange-200! shadow-sm! flex! items-center! gap-2!"
        >
          <div class="w-2! h-2! rounded-full! bg-orange-500! animate-pulse!"></div>
          <div>
            <div class="text-[10px]! font-bold! text-slate-400! uppercase! tracking-wider!">
              Belum Bayar
            </div>
            <div class="text-sm! font-extrabold! text-orange-600!">{{ counts.unpaid }}</div>
          </div>
        </div>
        <div
          class="px-4! py-2! rounded-xl! bg-white! border! border-sky-200! shadow-sm! flex! items-center! gap-2!"
        >
          <div class="w-2! h-2! rounded-full! bg-sky-500!"></div>
          <div>
            <div class="text-[10px]! font-bold! text-slate-400! uppercase! tracking-wider!">
              Total Piutang
            </div>
            <div class="text-sm! font-extrabold! text-sky-700!">
              Rp {{ formatRibuan(totalRemaining) }}
            </div>
          </div>
        </div>
      </div>
    </div>

    <div class="grid! grid-cols-1! xl:grid-cols-5! gap-4! lg:gap-6!">
      <ContentCard
        variant="bordered"
        padding="none"
        rounded="2xl"
        class="xl:col-span-2! overflow-hidden!"
      >
        <div
          class="px-4! py-3! border-b! border-slate-100! bg-slate-50/50! flex! items-center! justify-between!"
        >
          <div class="flex! items-center! gap-2!">
            <div class="w-7! h-7! rounded-lg! bg-sky-100! flex! items-center! justify-center!">
              <font-awesome-icon icon="list" class="text-sky-600! text-xs!" />
            </div>
            <h3 class="text-sm! font-bold! text-slate-800!">Daftar Tagihan</h3>
          </div>
          <button
            @click="fetchTickets"
            class="text-[10px]! font-bold! text-slate-500! hover:text-sky-600! flex! items-center! gap-1! uppercase! tracking-wider!"
          >
            <font-awesome-icon icon="sync" :class="loading ? 'animate-spin!' : ''" />
            Segarkan
          </button>
        </div>

        <div class="p-3! border-b! border-slate-100!">
          <div class="relative!">
            <font-awesome-icon
              icon="search"
              class="absolute! left-3! top-1/2! -translate-y-1/2! text-slate-400! text-xs!"
            />
            <input
              v-model="search"
              type="text"
              placeholder="Cari nama, NIK, atau kode..."
              class="w-full! pl-9! pr-3! py-2! bg-slate-50! border! border-slate-200! rounded-lg! text-xs! text-slate-700! focus:outline-none! focus:ring-2! focus:ring-sky-100! focus:border-sky-400! transition-all!"
            />
          </div>
        </div>

        <div class="max-h-[640px]! overflow-y-auto!">
          <div
            v-if="loading && tickets.length === 0"
            class="py-16! text-center! text-slate-400! text-xs!"
          >
            <font-awesome-icon icon="spinner" spin class="text-2xl! mb-2!" />
            <p>Memuat data tagihan...</p>
          </div>

          <div
            v-else-if="filteredTickets.length === 0"
            class="py-16! text-center! text-slate-400! text-xs!"
          >
            <div
              class="w-14! h-14! rounded-full! bg-slate-100! mx-auto! mb-3! flex! items-center! justify-center!"
            >
              <font-awesome-icon icon="inbox" class="text-slate-300! text-xl!" />
            </div>
            <p class="font-bold!">Tidak ada tagihan</p>
            <p class="text-[11px]! mt-1!">Belum ada tiket dengan status surveyed / unpaid.</p>
          </div>

          <button
            v-for="t in filteredTickets"
            :key="t.id"
            @click="selectTicket(t)"
            class="w-full! text-left! px-4! py-3! border-b! border-slate-100! hover:bg-sky-50/50! transition-colors! relative!"
            :class="
              selectedTicket?.id === t.id
                ? 'bg-sky-50! border-l-4! border-l-sky-500!'
                : 'border-l-4! border-l-transparent!'
            "
          >
            <div class="flex! items-start! justify-between! gap-2!">
              <div class="flex-1! min-w-0!">
                <p class="text-sm! font-bold! text-slate-800! truncate!">{{ t.applicant_name }}</p>
                <p class="text-[11px]! text-slate-500! font-mono! mt-0.5!">
                  NIK {{ t.nik }} · #INS-{{ String(t.id).padStart(4, '0') }}
                </p>
              </div>
              <span
                class="text-[9px]! font-bold! uppercase! tracking-wider! px-2! py-0.5! rounded-full! shrink-0!"
                :class="
                  t.status === 'surveyed'
                    ? 'bg-amber-100! text-amber-700!'
                    : 'bg-orange-100! text-orange-700!'
                "
              >
                {{ t.status === 'surveyed' ? 'Disurvei' : 'Belum Bayar' }}
              </span>
            </div>

            <div class="mt-2!">
              <div class="flex! items-center! justify-between! text-[10px]! mb-1!">
                <span class="text-slate-500! font-medium!">{{ t.package }}</span>
                <span class="text-slate-700! font-bold!">
                  {{ formatRibuan(Number(t.total_paid) || 0) }} / {{ formatRibuan(t.total_fee) }}
                </span>
              </div>
              <div class="h-1.5! rounded-full! bg-slate-200! overflow-hidden!">
                <div
                  class="h-full! rounded-full! transition-all!"
                  :class="
                    t.is_paid_off
                      ? 'bg-emerald-500!'
                      : 'bg-gradient-to-r! from-orange-400! to-orange-500!'
                  "
                  :style="{ width: `${getProgress(t)}%` }"
                ></div>
              </div>
              <div class="flex! items-center! justify-between! mt-1!">
                <span class="text-[10px]! text-slate-400!"
                  >{{ getProgress(t).toFixed(0) }}% terbayar</span
                >
                <span v-if="!t.is_paid_off" class="text-[10px]! font-bold! text-orange-600!">
                  Sisa: Rp {{ formatRibuan(t.remaining) }}
                </span>
                <span v-else class="text-[10px]! font-bold! text-emerald-600!">LUNAS</span>
              </div>
              <div
                v-if="hasPendingPayments(t)"
                class="mt-1! text-[10px]! font-bold! text-amber-600! flex! items-center! gap-1!"
              >
                <font-awesome-icon icon="clock" />
                {{ pendingCount(t) }} pembayaran menunggu konfirmasi
              </div>
            </div>
          </button>
        </div>
      </ContentCard>

      <div class="xl:col-span-3!">
        <ContentCard
          v-if="!selectedTicket"
          variant="bordered"
          padding="large"
          rounded="2xl"
          class="h-full! flex! items-center! justify-center! min-h-[400px]!"
        >
          <div class="text-center! py-12!">
            <div
              class="w-20! h-20! rounded-2xl! bg-slate-100! mx-auto! mb-4! flex! items-center! justify-center!"
            >
              <font-awesome-icon icon="file-invoice-dollar" class="text-slate-300! text-3xl!" />
            </div>
            <h3 class="text-base! font-bold! text-slate-700!">Pilih tagihan</h3>
            <p class="text-sm! text-slate-400! mt-1! max-w-xs! mx-auto!">
              Pilih tiket instalasi di daftar sebelah kiri untuk melakukan pembayaran.
            </p>
          </div>
        </ContentCard>

        <div v-else class="space-y-4!">
          <div
            class="rounded-2xl! p-5! text-white! relative! overflow-hidden!"
            style="
              background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%);
              box-shadow: 0 20px 25px -5px rgba(15, 23, 42, 0.2);
            "
          >
            <div
              class="absolute! -top-20! -right-20! w-60! h-60! rounded-full!"
              style="
                background: radial-gradient(circle, rgba(56, 189, 248, 0.15) 0%, transparent 70%);
              "
            ></div>

            <div class="relative! z-10!">
              <div class="flex! items-start! justify-between! gap-3! mb-4!">
                <div>
                  <div
                    class="text-[10px]! font-bold! text-white/60! uppercase! tracking-widest! mb-1!"
                  >
                    BILLING STATEMENT
                  </div>
                  <h2 class="text-xl! font-extrabold! tracking-tight!">
                    {{ selectedTicket.applicant_name }}
                  </h2>
                  <div
                    class="flex! flex-wrap! items-center! gap-x-3! gap-y-1! mt-1! text-[11px]! text-white/70!"
                  >
                    <span>NIK: {{ selectedTicket.nik }}</span>
                    <span class="text-white/30!">•</span>
                    <span class="font-mono!"
                      >#INS-{{ String(selectedTicket.id).padStart(4, '0') }}</span
                    >
                    <span class="text-white/30!">•</span>
                    <span>{{ selectedTicket.package }}</span>
                  </div>
                </div>
                <span
                  class="px-3! py-1! rounded-full! text-[10px]! font-bold! uppercase! tracking-wider! shrink-0!"
                  :class="
                    selectedTicket.is_paid_off
                      ? 'bg-emerald-500/20! text-emerald-300! border! border-emerald-400/30!'
                      : 'bg-orange-500/20! text-orange-300! border! border-orange-400/30!'
                  "
                >
                  {{
                    selectedTicket.is_paid_off
                      ? 'LUNAS'
                      : selectedTicket.status === 'surveyed'
                        ? 'SURVEYED'
                        : 'UNPAID'
                  }}
                </span>
              </div>

              <div class="grid! grid-cols-3! gap-2! mt-4!">
                <div class="bg-white/5! backdrop-blur-sm! rounded-lg! p-3!">
                  <div class="text-[10px]! text-white/60! uppercase! font-bold! tracking-wider!">
                    Total Tagihan
                  </div>
                  <div class="text-base! font-extrabold! mt-1!">
                    Rp {{ formatRibuan(selectedTicket.total_fee) }}
                  </div>
                </div>
                <div
                  class="bg-emerald-500/10! backdrop-blur-sm! rounded-lg! p-3! border! border-emerald-400/20!"
                >
                  <div class="text-[10px]! text-emerald-300! uppercase! font-bold! tracking-wider!">
                    Sudah Dibayar
                  </div>
                  <div class="text-base! font-extrabold! mt-1! text-emerald-300!">
                    Rp {{ formatRibuan(Number(selectedTicket.total_paid) || 0) }}
                  </div>
                </div>
                <div
                  class="rounded-lg! p-3! backdrop-blur-sm! border!"
                  :class="
                    selectedTicket.is_paid_off
                      ? 'bg-emerald-500/10! border-emerald-400/20!'
                      : 'bg-orange-500/10! border-orange-400/20!'
                  "
                >
                  <div
                    class="text-[10px]! uppercase! font-bold! tracking-wider!"
                    :class="selectedTicket.is_paid_off ? 'text-emerald-300!' : 'text-orange-300!'"
                  >
                    {{ selectedTicket.is_paid_off ? 'Lunas' : 'Sisa' }}
                  </div>
                  <div
                    class="text-base! font-extrabold! mt-1!"
                    :class="selectedTicket.is_paid_off ? 'text-emerald-300!' : 'text-orange-300!'"
                  >
                    Rp {{ formatRibuan(selectedTicket.remaining) }}
                  </div>
                </div>
              </div>
            </div>
          </div>

          <ContentCard variant="bordered" padding="normal" rounded="2xl">
            <div class="flex! items-center! gap-2! mb-4!">
              <div class="w-7! h-7! rounded-lg! bg-cyan-100! flex! items-center! justify-center!">
                <font-awesome-icon icon="credit-card" class="text-cyan-600! text-xs!" />
              </div>
              <h3 class="text-sm! font-bold! text-slate-800!">Form Pembayaran</h3>
            </div>

            <div v-if="selectedTicket.is_paid_off" class="text-center! py-6!">
              <div
                class="w-16! h-16! rounded-full! bg-emerald-100! mx-auto! mb-3! flex! items-center! justify-center!"
              >
                <font-awesome-icon icon="check" class="text-emerald-600! text-2xl!" />
              </div>
              <p class="text-sm! font-bold! text-emerald-700!">Tagihan sudah lunas</p>
              <p class="text-xs! text-slate-500! mt-1!">
                Tiket ini sudah lanjut ke tahap Diproses.
              </p>
            </div>

            <div v-else class="space-y-4!">
              <div>
                <label
                  class="text-[11px]! font-bold! text-slate-500! uppercase! tracking-wider! mb-1.5! block!"
                >
                  Tanggal Pembayaran
                </label>
                <AppDatePicker v-model="form.tanggalBayar" placeholder="Pilih tanggal" />
              </div>

              <div>
                <label
                  class="text-[11px]! font-bold! text-slate-500! uppercase! tracking-wider! mb-1.5! block!"
                >
                  Nominal Pembayaran
                </label>
                <div class="flex! gap-2! mb-2!">
                  <button
                    v-for="opt in quickOptions"
                    :key="opt.value"
                    @click="setQuickAmount(opt.value)"
                    class="flex-1! px-2! py-1.5! rounded-lg! border! text-[11px]! font-bold! transition-all!"
                    :class="
                      form.amount === opt.value
                        ? 'bg-sky-500! text-white! border-sky-500! shadow-sm!'
                        : 'bg-white! text-slate-600! border-slate-200! hover:border-sky-300!'
                    "
                  >
                    {{ opt.label }}
                  </button>
                </div>
                <div class="relative!">
                  <span
                    class="absolute! left-3! top-1/2! -translate-y-1/2! text-xs! font-bold! text-slate-500!"
                    >Rp</span
                  >
                  <input
                    :value="form.amountDisplay"
                    @input="onAmountInput"
                    type="text"
                    inputmode="numeric"
                    placeholder="0"
                    class="w-full! pl-10! pr-3! py-2.5! text-sm! font-extrabold! text-sky-700! bg-sky-50! border! border-sky-200! rounded-lg! focus:outline-none! focus:ring-2! focus:ring-sky-200! focus:border-sky-400!"
                  />
                </div>
                <div class="flex! items-center! justify-between! mt-1.5! text-[11px]!">
                  <span class="text-slate-500!">Sisa tagihan:</span>
                  <span class="font-bold! text-orange-600!"
                    >Rp {{ formatRibuan(selectedTicket.remaining) }}</span
                  >
                </div>
              </div>

              <div
                v-if="form.amount > 0 && form.amount < selectedTicket.remaining"
                class="bg-amber-50! border! border-amber-200! rounded-lg! p-3! flex! gap-2!"
              >
                <font-awesome-icon icon="info-circle" class="text-amber-600! mt-0.5!" />
                <div class="text-[11px]! text-amber-800!">
                  <p class="font-bold!">Pembayaran Sebagian</p>
                  <p class="mt-0.5!">
                    Setelah ini, status tiket tetap
                    <strong>Belum Bayar</strong> dan masih ada sisa
                    <strong>Rp {{ formatRibuan(selectedTicket.remaining - form.amount) }}</strong>
                    yang harus dilunasi.
                  </p>
                </div>
              </div>

              <div
                v-else-if="form.amount >= selectedTicket.remaining"
                class="bg-emerald-50! border! border-emerald-200! rounded-lg! p-3! flex! gap-2!"
              >
                <font-awesome-icon icon="check-circle" class="text-emerald-600! mt-0.5!" />
                <div class="text-[11px]! text-emerald-800!">
                  <p class="font-bold!">Pelunasan</p>
                  <p class="mt-0.5!">
                    Setelah konfirmasi, tiket otomatis lanjut ke tahap <strong>Diproses</strong>.
                  </p>
                </div>
              </div>

              <div class="flex! gap-2! pt-2!">
                <button
                  @click="handleSubmit"
                  :disabled="
                    submitting || form.amount <= 0 || form.amount > selectedTicket.remaining
                  "
                  class="flex-1! py-2.5! rounded-lg! font-bold! text-sm! flex! items-center! justify-center! gap-2! transition-all! disabled:opacity-50! disabled:cursor-not-allowed!"
                  :class="
                    form.amount >= selectedTicket.remaining
                      ? 'bg-gradient-to-r! from-emerald-500! to-emerald-600! hover:from-emerald-600! hover:to-emerald-700! text-white! shadow-lg! shadow-emerald-200!'
                      : 'bg-gradient-to-r! from-sky-500! to-blue-600! hover:from-sky-600! hover:to-blue-700! text-white! shadow-lg! shadow-sky-200!'
                  "
                >
                  <font-awesome-icon v-if="submitting" icon="spinner" spin />
                  <font-awesome-icon
                    v-else
                    :icon="
                      form.amount >= selectedTicket.remaining ? 'check-circle' : 'money-bill-wave'
                    "
                  />
                  {{
                    submitting
                      ? 'Menyimpan...'
                      : form.amount >= selectedTicket.remaining
                        ? 'Pelunasan & Lanjut Diproses'
                        : 'Simpan Pembayaran'
                  }}
                </button>
              </div>
            </div>
          </ContentCard>

          <ContentCard variant="bordered" padding="normal" rounded="2xl">
            <div class="flex! items-center! gap-2! mb-3!">
              <div class="w-7! h-7! rounded-lg! bg-slate-100! flex! items-center! justify-center!">
                <font-awesome-icon icon="history" class="text-slate-600! text-xs!" />
              </div>
              <h3 class="text-sm! font-bold! text-slate-800!">Riwayat Pembayaran</h3>
            </div>

            <div
              v-if="selectedTicket.payments.length === 0"
              class="text-center! py-6! text-xs! text-slate-400!"
            >
              Belum ada pembayaran
            </div>
            <div v-else class="space-y-2!">
              <div
                v-for="p in [...selectedTicket.payments].reverse()"
                :key="p.id"
                class="flex! items-center! justify-between! px-3! py-2! rounded-lg! border!"
                :class="
                  p.status === 'confirmed'
                    ? 'bg-slate-50! border-slate-100!'
                    : 'bg-amber-50! border-amber-200!'
                "
              >
                <div class="flex! items-center! gap-2!">
                  <div
                    class="w-7! h-7! rounded-full! flex! items-center! justify-center!"
                    :class="p.status === 'confirmed' ? 'bg-emerald-100!' : 'bg-amber-100!'"
                  >
                    <font-awesome-icon
                      :icon="p.status === 'confirmed' ? 'check' : 'clock'"
                      class="text-[10px]!"
                      :class="p.status === 'confirmed' ? 'text-emerald-600!' : 'text-amber-600!'"
                    />
                  </div>
                  <div>
                    <div class="text-[11px]! font-bold! text-slate-700!">
                      Rp {{ formatRibuan(p.amount) }}
                    </div>
                    <div class="text-[10px]! text-slate-400!">
                      {{
                        p.paid_at ? new Date(p.paid_at).toLocaleString('id-ID') : 'Belum dibayar'
                      }}
                    </div>
                  </div>
                </div>
                <span
                  class="text-[10px]! font-bold! uppercase! tracking-wider! px-2! py-0.5! rounded-full!"
                  :class="
                    p.status === 'confirmed'
                      ? 'bg-emerald-100! text-emerald-700!'
                      : 'bg-amber-100! text-amber-700!'
                  "
                >
                  {{ p.status === 'confirmed' ? 'Dikonfirmasi' : 'Menunggu' }}
                </span>
              </div>
            </div>
          </ContentCard>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed, onMounted, reactive, ref, watch } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import Swal from 'sweetalert2'
import ContentCard from '@/presentations/components/ui/ContentCard.vue'
import AppDatePicker from '@/presentations/components/AppDatePicker.vue'
import api from '@/utils/axios'
import ticketService from '@/services/ticket.service'

const route = useRoute()
const router = useRouter()

const loading = ref(false)
const submitting = ref(false)
const tickets = ref([])
const search = ref('')
const selectedTicket = ref(null)

const form = reactive({
  tanggalBayar: new Date().toISOString().split('T')[0],
  amount: 0,
  amountDisplay: '',
})

const fetchTickets = async () => {
  loading.value = true
  try {
    const res = await api.get('/installation-tickets-unpaid')
    if (res.data?.success) {
      tickets.value = res.data.data || []
      const qTicket = parseInt(route.query.ticket, 10)
      if (qTicket) {
        const found = tickets.value.find((t) => t.id === qTicket)
        if (found) selectedTicket.value = found
      }
    }
  } catch (err) {
    console.error('Failed to load unpaid tickets', err)
    Swal.fire('Gagal', 'Tidak dapat memuat daftar tagihan.', 'error')
  } finally {
    loading.value = false
  }
}

const filteredTickets = computed(() => {
  if (!search.value) return tickets.value
  const q = search.value.toLowerCase()
  return tickets.value.filter(
    (t) =>
      t.applicant_name.toLowerCase().includes(q) ||
      t.nik.toLowerCase().includes(q) ||
      String(t.id).includes(q) ||
      (t.package || '').toLowerCase().includes(q),
  )
})

const counts = computed(() => {
  const unpaid = tickets.value.filter((t) => t.status === 'unpaid').length
  const surveyed = tickets.value.filter((t) => t.status === 'surveyed').length
  return { unpaid, surveyed }
})

const totalRemaining = computed(() => {
  return tickets.value.reduce((sum, t) => sum + Number(t.remaining || 0), 0)
})

const getProgress = (t) => {
  if (!t.total_fee || t.total_fee === 0) return 0
  return Math.min(100, (Number(t.total_paid || 0) / Number(t.total_fee)) * 100)
}

const pendingCount = (t) => {
  return (t.payments || []).filter((p) => p.status === 'pending').length
}

const hasPendingPayments = (t) => {
  return pendingCount(t) > 0
}

const selectTicket = (t) => {
  selectedTicket.value = t
  form.amount = 0
  form.amountDisplay = ''
  form.tanggalBayar = new Date().toISOString().split('T')[0]
}

const quickOptions = computed(() => {
  if (!selectedTicket.value) return []
  const rem = Number(selectedTicket.value.remaining)
  return [
    { label: '50%', value: Math.floor(rem * 0.5) },
    { label: '75%', value: Math.floor(rem * 0.75) },
    { label: 'Lunas', value: rem },
  ]
})

const setQuickAmount = (val) => {
  form.amount = val
  form.amountDisplay = formatRibuan(val)
}

const onAmountInput = (e) => {
  const raw = String(e.target.value).replace(/[^\d]/g, '')
  let parsed = parseInt(raw || '0', 10)
  if (isNaN(parsed)) parsed = 0

  const max = Number(selectedTicket.value?.remaining || 0)
  const isOver = parsed > max

  if (isOver && max > 0) {
    parsed = max
    Swal.fire({
      icon: 'warning',
      title: 'Nominal Melebihi Sisa Tagihan',
      html: `Nominal tidak boleh lebih dari sisa tagihan <strong style="color:#ea580c;">Rp ${formatRibuan(max)}</strong>. Nominal otomatis disesuaikan.`,
      confirmButtonColor: '#f59e0b',
      timer: 2500,
      timerProgressBar: true,
      showConfirmButton: false,
      toast: true,
      position: 'top-end',
    })
  }

  form.amount = parsed
  form.amountDisplay = formatRibuan(parsed)
}

const formatRibuan = (val) => {
  if (val === null || val === undefined || val === '') return '0'
  return Number(val).toLocaleString('id-ID')
}

const handleSubmit = async () => {
  if (!selectedTicket.value) return
  if (form.amount <= 0) {
    Swal.fire('Nominal kosong', 'Masukkan nominal pembayaran.', 'warning')
    return
  }
  if (form.amount > selectedTicket.value.remaining) {
    Swal.fire(
      'Nominal terlalu besar',
      `Maksimal pembayaran adalah Rp ${formatRibuan(selectedTicket.value.remaining)}`,
      'warning',
    )
    return
  }

  const isLunas = form.amount >= selectedTicket.value.remaining
  const confirm = await Swal.fire({
    title: isLunas ? 'Konfirmasi Pelunasan' : 'Konfirmasi Pembayaran Sebagian',
    html: `
      <div style="text-align:left; font-size:13px;">
        <div style="display:flex; justify-content:space-between; padding:6px 0; border-bottom:1px solid #e2e8f0;">
          <span style="color:#64748b;">Pelanggan</span>
          <strong>${selectedTicket.value.applicant_name}</strong>
        </div>
        <div style="display:flex; justify-content:space-between; padding:6px 0; border-bottom:1px solid #e2e8f0;">
          <span style="color:#64748b;">Paket</span>
          <strong>${selectedTicket.value.package}</strong>
        </div>
        <div style="display:flex; justify-content:space-between; padding:6px 0; border-bottom:1px solid #e2e8f0;">
          <span style="color:#64748b;">Sisa Tagihan</span>
          <strong style="color:#ea580c;">Rp ${formatRibuan(selectedTicket.value.remaining)}</strong>
        </div>
        <div style="display:flex; justify-content:space-between; padding:10px 0;">
          <span style="color:#64748b; font-weight:600;">Bayar</span>
          <strong style="color:#0284c7; font-size:18px;">Rp ${formatRibuan(form.amount)}</strong>
        </div>
        ${
          !isLunas
            ? `<div style="background:#fef3c7; padding:8px 10px; border-radius:6px; font-size:11px; color:#92400e; margin-top:6px;">
                Sisa setelah bayar: <strong>Rp ${formatRibuan(selectedTicket.value.remaining - form.amount)}</strong> — status tiket tetap <strong>Belum Bayar</strong>
              </div>`
            : `<div style="background:#d1fae5; padding:8px 10px; border-radius:6px; font-size:11px; color:#065f46; margin-top:6px;">
                Status tiket akan otomatis lanjut ke <strong>Diproses</strong>
              </div>`
        }
      </div>
    `,
    icon: 'question',
    showCancelButton: true,
    confirmButtonColor: isLunas ? '#10b981' : '#0284c7',
    cancelButtonColor: '#64748b',
    confirmButtonText: isLunas ? 'Ya, Lunasi' : 'Ya, Bayar',
    cancelButtonText: 'Batal',
    reverseButtons: true,
  })

  if (!confirm.isConfirmed) return

  submitting.value = true
  try {
    const res = await ticketService.confirmTicketPayment(selectedTicket.value.id, form.amount)
    const data = res?.data || {}
    const newRemaining = Number(data.remaining ?? 0)
    const isPaidOff = !!data.is_paid_off

    const kode =
      selectedTicket.value?.customer_code ||
      `#INS-${String(selectedTicket.value.id).padStart(4, '0')}`

    if (isPaidOff) {
      const choice = await Swal.fire({
        title: 'Pelunasan Berhasil!',
        html: `<p style="font-size:13px;">Tiket telah lunas dan otomatis lanjut ke tahap <strong>Diproses</strong>.</p>`,
        icon: 'success',
        showDenyButton: true,
        showCancelButton: false,
        showConfirmButton: true,
        allowEscapeKey: false,
        allowOutsideClick: false,
        confirmButtonText: 'Lihat Detail Diproses',
        denyButtonText: 'Lanjut Tagihan Instalasi',
        buttonsStyling: true,
        confirmButtonColor: '#0284c7',
        denyButtonColor: '#10b981',
        reverseButtons: false,
        customClass: {
          popup: 'rounded-2xl !font-sans',
          title: '!text-base !font-bold !text-slate-900 !pb-2',
          confirmButton: '!rounded-lg !px-4 !py-2 !text-sm !font-semibold',
          denyButton: '!rounded-lg !px-4 !py-2 !text-sm !font-semibold',
          actions: '!gap-2',
        },
      })
      if (choice.isConfirmed) {
        router.push({
          path: `/app/instalasi/status/pasang-baru/${encodeURIComponent(kode)}`,
        })
        return
      }
      if (choice.isDenied) {
        window.location.assign('/app/transaksi/tagihan-instalasi')
        return
      }
    } else {
      await Swal.fire({
        title: 'Pembayaran Berhasil',
        html: `<p style="font-size:13px;">Sisa tagihan: <strong style="color:#ea580c;">Rp ${formatRibuan(newRemaining)}</strong></p>`,
        icon: 'success',
        confirmButtonColor: '#0284c7',
      })
    }

    form.amount = 0
    form.amountDisplay = ''

    await fetchTickets()

    const backendTicket = data?.ticket
      ? {
          ...data.ticket,
          paid: Number(data.paid ?? 0),
          pending: Number(data.pending ?? 0),
          total_paid: Number(data.total_paid ?? data.paid ?? 0),
          remaining: Number(data.remaining ?? 0),
          total_fee: Number(data.total_fee ?? data.ticket?.package?.installation_fee ?? 0),
          is_paid_off: !!data.is_paid_off,
          has_pending: Number(data.pending ?? 0) > 0,
          status: data.ticket?.status,
          package:
            typeof data.ticket?.package === 'object'
              ? data.ticket?.package?.name || '-'
              : data.ticket?.package || '-',
        }
      : null

    if (backendTicket) {
      const listMatch = tickets.value.find((t) => t.id === backendTicket.id)
      selectedTicket.value = {
        ...(listMatch || backendTicket),
        ...backendTicket,
      }
      if (!listMatch) {
        tickets.value = [backendTicket, ...tickets.value]
      }
    } else {
      const updated = tickets.value.find((t) => t.id === selectedTicket.value?.id)
      if (updated) {
        selectedTicket.value = updated
      }
    }
  } catch (err) {
    const msg =
      err.response?.data?.message ||
      err.response?.data?.errors?.amount?.[0] ||
      'Gagal menyimpan pembayaran.'
    Swal.fire('Gagal', msg, 'error')
  } finally {
    submitting.value = false
  }
}

watch(
  () => selectedTicket.value?.id,
  () => {
    form.amount = 0
    form.amountDisplay = ''
  },
)

onMounted(() => {
  fetchTickets()
})
</script>
