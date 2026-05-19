<template>
  <Teleport to="body">
    <Transition name="modal-fade">
      <div
        v-if="show"
        class="fixed! inset-0! bg-slate-950/60! backdrop-blur-sm! z-[9999]! flex! items-center! justify-center! p-4! overflow-y-auto!"
        @click="closeModal"
      >
        <div
          class="bg-white! rounded-2xl! shadow-2xl! w-full! max-w-4xl! overflow-hidden! transition-all! transform! flex! flex-col! my-8! border! border-slate-100! shadow-slate-950/20!"
          @click.stop
        >
          <div
            class="px-6! py-4! bg-gradient-to-r! from-cyan-600! to-cyan-800! text-white! flex! items-center! justify-between! shrink-0!"
          >
            <div class="flex! items-center! gap-3!">
              <div
                class="w-10! h-10! rounded-xl! bg-white/10! backdrop-blur-md! flex! items-center! justify-center!"
              >
                <font-awesome-icon
                  icon="file-invoice-dollar"
                  class="text-white! text-lg! !m-auto!"
                />
              </div>
              <div>
                <h3 class="text-base md:text-lg! font-bold! leading-tight! mb-0.5!">
                  Rincian Rekening & Profil Pelanggan
                </h3>
                <p
                  class="text-[10px] md:text-xs! text-cyan-100/80! font-semibold! uppercase! tracking-wide!"
                >
                  Invoice ID: INV-{{ bill?.id }} · Periode
                  {{ getMonthName(bill?.billing_period_month) }} {{ bill?.billing_period_year }}
                </p>
              </div>
            </div>
            <button
              @click="closeModal"
              class="w-8! h-8! rounded-xl! bg-white/10! hover:bg-white/20! flex! items-center! justify-center! text-white! hover:scale-105! transition-all! cursor-pointer!"
            >
              <font-awesome-icon icon="times" class="text-sm! !m-auto!" />
            </button>
          </div>

          <div class="p-6! overflow-y-auto! max-h-[calc(85vh-120px)]! bg-slate-50/50! flex-1!">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6!">
              <div class="flex! flex-col! h-full! gap-4!">
                <h4
                  class="text-xs! font-black! text-slate-400! uppercase! tracking-widest! flex! items-center! gap-2! mb-0!"
                >
                  <font-awesome-icon icon="user" class="text-cyan-600!" />
                  Informasi Profil Pelanggan
                </h4>

                <div
                  class="bg-white! border! border-slate-100! rounded-2xl! p-5! shadow-sm! flex-1! flex! flex-col! justify-between! gap-6!"
                >
                  <div class="space-y-4!">
                    <div class="flex! items-center! gap-3.5! pb-4! border-b! border-slate-100!">
                      <div
                        class="w-12! h-12! rounded-xl! bg-gradient-to-br! from-cyan-500! to-blue-600! text-white! flex! items-center! justify-center! text-base! font-bold! shadow-sm!"
                      >
                        {{ getInitials }}
                      </div>
                      <div>
                        <h5 class="text-base! font-black! text-slate-800! tracking-tight!">
                          {{ getCustomerName }}
                        </h5>
                        <span
                          class="inline-flex! items-center! gap-1! px-2! py-0.5! rounded-full! text-[9px]! font-black! tracking-wider! uppercase! bg-emerald-50! text-emerald-600! border! border-emerald-100! mt-1!"
                        >
                          <span class="w-1! h-1! rounded-full! bg-emerald-500!"></span>
                          Status: Aktif
                        </span>
                      </div>
                    </div>

                    <div class="grid grid-cols-1 gap-3.5! text-xs!">
                      <div>
                        <span class="text-slate-400! font-bold! block! mb-0.5!"
                          >Kode Pelanggan</span
                        >
                        <span class="font-bold! text-slate-800! font-mono! text-sm!">
                          {{ bill?.customer?.customer_code || 'PAM-' + bill?.customer_id }}
                        </span>
                      </div>

                      <div>
                        <span class="text-slate-400! font-bold! block! mb-0.5!"
                          >Nomor Telepon / WA</span
                        >
                        <a
                          v-if="bill?.customer?.ticket?.phone"
                          :href="
                            'https://wa.me/' + formatWhatsAppNumber(bill?.customer?.ticket?.phone)
                          "
                          target="_blank"
                          class="font-semibold! text-cyan-600! hover:underline! flex! items-center! gap-1!"
                        >
                          <font-awesome-icon icon="phone" class="text-[10px]!" />
                          {{ bill?.customer?.ticket?.phone }}
                        </a>
                        <span v-else class="text-slate-500! font-medium! italic!"
                          >Tidak ada nomor</span
                        >
                      </div>

                      <div>
                        <span class="text-slate-400! font-bold! block! mb-0.5!">Alamat Rumah</span>
                        <span
                          class="font-medium! text-slate-700! leading-relaxed! flex! items-start! gap-1!"
                        >
                          <font-awesome-icon
                            icon="map-marker-alt"
                            class="text-slate-400! text-[11px]! mt-0.5! shrink-0!"
                          />
                          {{ bill?.customer?.ticket?.address || '-' }}
                        </span>
                      </div>
                    </div>
                  </div>

                  <div class="grid grid-cols-2 gap-4! pt-4! border-t! border-slate-100! text-xs!">
                    <div>
                      <span class="text-slate-400! font-bold! block! mb-0.5!">Stand Awal Awal</span>
                      <span class="font-bold! text-slate-700!">
                        {{ bill?.customer?.initial_meter_reading || 0 }} m³
                      </span>
                    </div>
                    <div>
                      <span class="text-slate-400! font-bold! block! mb-0.5!">Terdaftar Sejak</span>
                      <span class="font-bold! text-slate-700!">
                        {{ formatDate(bill?.customer?.activated_at) }}
                      </span>
                    </div>
                  </div>
                </div>
              </div>

              <div class="flex! flex-col! h-full! gap-4!">
                <h4
                  class="text-xs! font-black! text-slate-400! uppercase! tracking-widest! flex! items-center! gap-2! mb-0!"
                >
                  <font-awesome-icon icon="file-alt" class="text-cyan-600!" />
                  Rincian Rekening Air Bulanan
                </h4>

                <div
                  class="bg-white! border! border-slate-100! rounded-2xl! p-5! shadow-sm! flex-1! flex! flex-col! justify-between! gap-6! relative! overflow-hidden!"
                >
                  <div
                    class="absolute! top-0! right-0! bg-rose-50! text-rose-600! border-b! border-l! border-rose-100! px-3! py-1! rounded-bl-xl! text-[9px]! font-black! tracking-wider! uppercase!"
                  >
                    {{ bill?.status === 'paid' ? 'Lunas' : 'Belum Lunas' }}
                  </div>

                  <div class="space-y-3! flex-1! flex! flex-col! justify-between! gap-4!">
                    <div class="space-y-3!">
                      <div class="grid grid-cols-2 gap-4! pb-3! border-b! border-slate-100!">
                        <div>
                          <span class="text-slate-400! font-bold! block! mb-0.5! text-xs!"
                            >Periode</span
                          >
                          <span class="font-extrabold! text-slate-800! text-sm!">
                            {{ getMonthName(bill?.billing_period_month) }}
                            {{ bill?.billing_period_year }}
                          </span>
                        </div>
                        <div>
                          <span class="text-slate-400! font-bold! block! mb-0.5! text-xs!"
                            >Jatuh Tempo</span
                          >
                          <span
                            class="font-bold! text-rose-600! text-sm! flex! items-center! gap-1!"
                          >
                            <font-awesome-icon icon="calendar-times" />
                            {{ formatDate(bill?.due_date) }}
                          </span>
                        </div>
                      </div>

                      <div class="space-y-2! text-xs! pt-1!">
                        <div class="flex! items-center! justify-between! py-1!">
                          <span class="text-slate-500! font-semibold!">Stand Meter Bulan Ini</span>
                          <span class="font-bold! text-slate-800! text-right!">
                            {{ bill?.meter_reading_start }} m³ → {{ bill?.meter_reading_end }} m³
                          </span>
                        </div>

                        <div
                          class="flex! items-center! justify-between! py-1! bg-slate-50! px-2.5! rounded-xl!"
                        >
                          <span class="text-cyan-700! font-bold!">Total Volume Pemakaian</span>
                          <span class="font-extrabold! text-cyan-700! text-right!">
                            {{ bill?.usage_m3 || 0 }} m³
                          </span>
                        </div>

                        <div
                          class="flex! items-center! justify-between! py-1! pt-3! border-t! border-dashed! border-slate-100!"
                        >
                          <span class="text-slate-500! font-medium!">Biaya Penggunaan Air</span>
                          <span class="font-bold! text-slate-800!">
                            Rp. {{ Number(bill?.usage_charge || 0).toLocaleString('id-ID', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) }}
                          </span>
                        </div>

                        <div class="flex! items-center! justify-between! py-1!">
                          <span class="text-slate-500! font-medium!"
                            >Biaya Beban Tetap (Abodemen)</span
                          >
                          <span class="font-bold! text-slate-800!">
                            Rp. {{ Number(bill?.abodemen || 0).toLocaleString('id-ID', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) }}
                          </span>
                        </div>

                        <div class="flex! items-center! justify-between! py-1!">
                          <span class="text-slate-500! font-medium!">Denda Keterlambatan</span>
                          <span class="font-bold! text-rose-500!">
                            Rp. {{ Number(bill?.penalty_amount || 0).toLocaleString('id-ID', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) }}
                          </span>
                        </div>
                      </div>
                    </div>

                    <div
                      class="flex! items-center! justify-between! py-3! px-2.5! bg-rose-50/50! rounded-xl! border! border-rose-100!"
                    >
                      <span class="text-slate-700! font-black! text-sm!">Total Pembayaran</span>
                      <span class="font-black! text-rose-600! text-base!">
                        Rp. {{ Number(bill?.total_amount || 0).toLocaleString('id-ID', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) }}
                      </span>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <div
            class="px-6! py-4! bg-slate-100! border-t! border-slate-200! flex! flex-col-reverse! sm:flex-row! sm:items-center! sm:justify-between! gap-3! shrink-0!"
          >
            <div class="flex! w-full! sm:w-auto!">
              <BaseButton
                variant="secondary"
                @click="closeModal"
                class="w-full! sm:w-auto! rounded-xl! font-semibold! text-xs md:text-sm border! border-slate-300! hover:bg-slate-200!"
              >
                Tutup Rincian
              </BaseButton>
            </div>

            <div class="flex! items-center! gap-2! w-full! sm:w-auto!">
              <BaseButton
                variant="ghost"
                @click="handlePrint"
                class="flex-1! sm:flex-initial! justify-center! rounded-xl! border! border-slate-300! bg-white! text-slate-700! hover:bg-slate-50! h-10! text-xs md:text-sm font-semibold! shadow-xs!"
                icon="print"
              >
                Cetak Invoice
              </BaseButton>

              <BaseButton
                v-if="bill?.status !== 'paid'"
                variant="info-gradient"
                @click="sendWhatsAppReminder"
                class="flex-1! sm:flex-initial! justify-center! rounded-xl! shadow-md! h-10! text-xs md:text-sm font-bold! bg-gradient-to-r! from-green-500! to-green-600! hover:from-green-600! hover:to-green-700!"
                icon="paper-plane"
              >
                Kirim WA Pengingat
              </BaseButton>
            </div>
          </div>
        </div>
      </div>
    </Transition>
  </Teleport>
</template>

<script setup>
import { computed, watch } from 'vue'
import BaseButton from '@/presentations/components/ui/BaseButton.vue'
import ContentCard from '@/presentations/components/ui/ContentCard.vue'

const props = defineProps({
  show: {
    type: Boolean,
    default: false,
  },
  bill: {
    type: Object,
    default: null,
  },
})

const emit = defineEmits(['close'])

const closeModal = () => {
  emit('close')
}

const getInitials = computed(() => {
  if (!props.bill) return 'PL'
  const name =
    props.bill.customer?.user?.name || props.bill.customer?.ticket?.applicant_name || 'PL'
  return name
    .split(' ')
    .map((word) => word[0])
    .join('')
    .slice(0, 2)
    .toUpperCase()
})

const getCustomerName = computed(() => {
  if (!props.bill) return 'Pelanggan Pamsimas'
  return (
    props.bill.customer?.user?.name ||
    props.bill.customer?.ticket?.applicant_name ||
    'Pelanggan Pamsimas'
  )
})

const formatDate = (dateStr) => {
  if (!dateStr) return '-'
  try {
    const d = new Date(dateStr)
    const months = [
      'Januari',
      'Februari',
      'Maret',
      'April',
      'Mei',
      'Juni',
      'Juli',
      'Agustus',
      'September',
      'Oktober',
      'November',
      'Desember',
    ]
    return `${d.getDate()} ${months[d.getMonth()]} ${d.getFullYear()}`
  } catch (err) {
    return dateStr
  }
}

const getMonthName = (monthNum) => {
  if (!monthNum) return '-'
  const months = [
    'Januari',
    'Februari',
    'Maret',
    'April',
    'Mei',
    'Juni',
    'Juli',
    'Agustus',
    'September',
    'Oktober',
    'November',
    'Desember',
  ]
  return months[monthNum - 1] || '-'
}

const formatWhatsAppNumber = (phone) => {
  if (!phone) return ''
  const cleanPhone = phone.replace(/[^0-9]/g, '')
  return cleanPhone.startsWith('0') ? '62' + cleanPhone.slice(1) : cleanPhone
}

const sendWhatsAppReminder = () => {
  if (!props.bill) return
  const customerName = getCustomerName.value
  const phone = props.bill.customer?.ticket?.phone || ''
  const total = new Intl.NumberFormat('id-ID').format(props.bill.total_amount)
  const month = getMonthName(props.bill.billing_period_month)
  const year = props.bill.billing_period_year

  const text = `Halo Bapak/Ibu *${customerName}*,\n\nKami dari *PAMSIMAS* ingin menginformasikan bahwa tagihan air Anda untuk periode *${month} ${year}* sebesar *Rp. ${total}* belum dilunasi.\n\nMohon untuk segera melakukan pembayaran demi kelancaran distribusi air bersih.\n\nTeria kasih.`

  const formattedPhone = formatWhatsAppNumber(phone)
  const url = `https://wa.me/${formattedPhone}?text=${encodeURIComponent(text)}`
  window.open(url, '_blank')
}

const handlePrint = () => {
  alert('Fitur cetak invoice sedang disiapkan.')
}

const handleEscKey = (event) => {
  if (event.key === 'Escape') {
    closeModal()
  }
}

watch(
  () => props.show,
  (newValue) => {
    if (newValue) {
      document.addEventListener('keydown', handleEscKey)
      document.body.style.overflow = 'hidden'
    } else {
      document.removeEventListener('keydown', handleEscKey)
      document.body.style.overflow = ''
    }
  },
)
</script>

<style scoped>
.modal-fade-enter-active,
.modal-fade-leave-active {
  transition: opacity 0.25s ease;
}

.modal-fade-enter-from,
.modal-fade-leave-to {
  opacity: 0;
}

.modal-fade-enter-active .transform,
.modal-fade-leave-active .transform {
  transition: transform 0.25s cubic-bezier(0.16, 1, 0.3, 1);
}

.modal-fade-enter-from .transform,
.modal-fade-leave-to .transform {
  transform: scale(0.95);
}
</style>
