<template>
  <div>
    <div class="relative! mb-6!">
      <CustomSearch
        v-model="billingStore.searchQuery"
        placeholder="Search customer name, ID, or installation code..."
        button-text="Detail Transaksi"
        @input="billingStore.searchCustomers($event)"
        @search="handleSearch"
      />

      <DetailModal :show="showDetailModal" @close="showDetailModal = false" />

      <div
        v-if="billingStore.searchResults.length > 0"
        class="absolute! top-full! left-0! right-0! bg-white! border! border-gray-200! rounded-lg! shadow-lg! z-50! max-h-60! overflow-y-auto! mx-2! sm:mx-4!"
      >
        <div
          v-for="customer in billingStore.searchResults"
          :key="customer.id"
          class="px-3! py-2! sm:px-4! hover:bg-gray-50! cursor-pointer! border-b! border-gray-100! last:border-b-0!"
          @click="selectCustomer(customer)"
        >
          <div class="flex! flex-col! sm:flex-row! sm:items-center! sm:justify-between! gap-2!">
            <div class="flex-1!">
              <div class="font-semibold! text-gray-900! text-sm! sm:text-base!">
                {{ customer.name }}
              </div>
              <div class="text-xs! sm:text-sm! text-gray-600!">
                {{ customer.id }} • {{ customer.installationCode }}
              </div>
            </div>
            <div
              class="text-xs! bg-green-100! text-green-800! px-2! py-1! rounded-full! w-fit! sm:w-auto!"
            >
              {{ customer.status }}
            </div>
          </div>
        </div>
      </div>
    </div>

    <div v-if="billingStore.selectedCustomer" class="flex! flex-col! lg:flex-row! gap-4! lg:gap-6!">
      <div class="flex-1! min-w-0!">
        <div class="grid! grid-cols-1! sm:grid-cols-2! lg:grid-cols-3! gap-2! mb-6!">
          <MeterDisplay
            type="initial"
            title="INITIAL METER"
            :value="currentPeriod?.meterAwal || 1240"
            unit="m³"
          />
          <MeterDisplay
            type="final"
            title="DENDA PENUNGGAKAN"
            :value="currentPeriod?.meterAkhir || 1265"
            unit="m³"
          />
          <MeterDisplay
            type="total"
            title="HARGA METER"
            :value="currentPeriod?.pemakaian || 25"
            unit="m³"
            :show-decoration="true"
          />
        </div>

        <div class="space-y-3!">
          <ContentCard
            v-for="period in billingStore.filteredBillingPeriods.slice(1)"
            :key="period.id"
            variant="bordered"
            padding="normal"
            hoverable
            clickable
            @click="billingStore.togglePeriod(period.id)"
          >
            <div class="flex! flex-col! lg:flex-row! lg:items-center! justify-between! gap-3!">
              <div class="flex! items-center! gap-3! flex-1! min-w-0!">
                <div
                  :class="[
                    'w-8! h-8! sm:w-9! sm:h-9! rounded-xl! flex! items-center! justify-center! flex-shrink-0!',
                    period.type === 'overdue'
                      ? 'bg-red-50!'
                      : period.type === 'processing'
                        ? 'bg-amber-50!'
                        : 'bg-slate-100!',
                  ]"
                >
                  <font-awesome-icon
                    :icon="
                      period.type === 'overdue'
                        ? 'exclamation-triangle'
                        : period.type === 'processing'
                          ? 'hourglass-half'
                          : 'check-circle'
                    "
                    :class="
                      period.type === 'overdue'
                        ? 'text-red-500!'
                        : period.type === 'processing'
                          ? 'text-amber-500!'
                          : 'text-green-500!'
                    "
                    class="text-xs! sm:text-sm! mx-auto!"
                  />
                </div>
                <div class="flex-1! min-w-0!">
                  <div class="text-sm! sm:text-base! font-bold! text-slate-900! truncate!">
                    {{ period.period }}
                  </div>
                  <div
                    :class="[
                      'text-[10px]! sm:text-[11px]! font-bold! mt-0.5! uppercase! tracking-wide!',
                      period.type === 'overdue'
                        ? 'text-red-500!'
                        : period.type === 'processing'
                          ? 'text-amber-600!'
                          : 'text-slate-400!',
                    ]"
                  >
                    {{ period.status }}{{ period.statusDate ? ' • ' + period.statusDate : '' }}
                  </div>
                </div>
              </div>
              <div class="flex! items-center! gap-2! sm:gap-3! mt-2! lg:mt-0!">
                <div class="text-right!">
                  <div class="text-[10px]! sm:text-[11px]! text-slate-400! font-medium!">
                    {{
                      period.type === 'overdue'
                        ? 'Amount Due'
                        : period.type === 'processing'
                          ? 'Amount Processing'
                          : 'Paid Amount'
                    }}
                  </div>
                  <div
                    :class="[
                      'text-sm! sm:text-base! font-bold!',
                      period.type === 'overdue'
                        ? 'text-red-500!'
                        : period.type === 'processing'
                          ? 'text-amber-600!'
                          : 'text-slate-600!',
                    ]"
                  >
                    {{ billingStore.formatAmount(period.amount) }}
                  </div>
                </div>
                <font-awesome-icon
                  :icon="period.isExpanded ? 'chevron-up' : 'chevron-down'"
                  class="text-slate-400! text-xs!"
                />
              </div>
            </div>

            <template v-if="period.isExpanded">
              <div class="border-t! border-slate-100! pt-4! mt-4!">
                <BillingForm
                  :initial-data="getInitialFormData(period)"
                  :customer-info="billingStore.selectedCustomer"
                  @save="handleSavePayment"
                />
              </div>
            </template>
          </ContentCard>
        </div>
      </div>

      <div class="w-full! lg:w-[320px]! flex-shrink-0!">
        <ContentCard variant="elevated" padding="large">
          <div
            class="bg-gradient-to-br! from-[#0B7A9E]! to-[#094e67]! rounded-2xl! shadow-lg! p-4! sm:p-6! text-white! relative! overflow-hidden! -m-4! sm:-m-6!"
          >
            <div
              class="absolute! top-0! right-0! w-24! h-24! sm:w-32! sm:h-32! bg-white/5! rounded-full! -mr-12! -mt-12! sm:-mr-16! sm:-mt-16!"
            ></div>
            <div
              class="absolute! bottom-0! left-0! w-20! h-20! sm:w-24! sm:h-24! bg-white/5! rounded-full! -ml-10! -mb-10! sm:-ml-12! sm:-mb-12!"
            ></div>

            <div class="relative! z-10!">
              <div class="flex! items-center! gap-3! sm:gap-4! mb-4! sm:mb-6!">
                <div
                  class="w-12! h-12! sm:w-16! sm:h-16! rounded-full! bg-white/20! backdrop-blur-sm! flex! items-center! justify-center! text-white! text-lg! sm:text-xl! font-bold! border-2! border-white/30!"
                >
                  {{ getCustomerInitials() }}
                </div>
                <div class="flex-1! min-w-0!">
                  <div class="flex! items-center! gap-2! mb-2!">
                    <span class="text-lg! sm:text-xl! font-bold! text-white! truncate!">{{
                      billingStore.selectedCustomer?.name || 'Bambang Susanto'
                    }}</span>
                    <div
                      class="w-2.5! h-2.5! bg-emerald-400! rounded-full! animate-pulse! flex-shrink-0!"
                    ></div>
                  </div>
                  <span
                    class="inline-block! px-2! sm:px-3! py-1! bg-emerald-500/20! backdrop-blur-sm! text-emerald-100! text-xs! font-bold! rounded-full! border! border-emerald-400/30!"
                  >
                    <font-awesome-icon icon="check-circle" class="mr-1!" />
                    AKTIF
                  </span>
                </div>
              </div>

              <div
                class="bg-white/10! backdrop-blur-sm! rounded-xl! p-3! sm:p-4! border! border-white/20!"
              >
                <div class="grid! grid-cols-1! gap-2! sm:gap-3!">
                  <div class="flex! items-center! gap-2! sm:gap-3!">
                    <div
                      class="w-6! h-6! sm:w-8! sm:h-8! rounded-lg! bg-white/20! backdrop-blur-sm! flex! items-center! justify-center! flex-shrink-0!"
                    >
                      <font-awesome-icon icon="id-card" class="text-white! text-xs!" />
                    </div>
                    <div class="flex-1! min-w-0!">
                      <div class="text-xs! text-white/70!">Customer ID</div>
                      <div class="text-xs! sm:text-sm! font-bold! text-white! truncate!">
                        {{ billingStore.selectedCustomer?.id || 'PAM-2025-09821' }}
                      </div>
                    </div>
                  </div>

                  <div class="flex! items-center! gap-2! sm:gap-3!">
                    <div
                      class="w-6! h-6! sm:w-8! sm:h-8! rounded-lg! bg-white/20! backdrop-blur-sm! flex! items-center! justify-center! flex-shrink-0!"
                    >
                      <font-awesome-icon icon="map-marker-alt" class="text-white! text-xs!" />
                    </div>
                    <div class="flex-1! min-w-0!">
                      <div class="text-xs! text-white/70!">Alamat</div>
                      <div class="text-xs! sm:text-sm! font-bold! text-white!">
                        {{ billingStore.selectedCustomer?.village || 'Mojosari' }},
                        {{ billingStore.selectedCustomer?.hamlet || 'Mojosari Kulon' }}, RT{{
                          billingStore.selectedCustomer?.rt || '04'
                        }}/RW{{ billingStore.selectedCustomer?.rw || '02' }}
                      </div>
                    </div>
                  </div>

                  <div class="flex! items-center! gap-2! sm:gap-3!">
                    <div
                      class="w-6! h-6! sm:w-8! sm:h-8! rounded-lg! bg-white/20! backdrop-blur-sm! flex! items-center! justify-center! flex-shrink-0!"
                    >
                      <font-awesome-icon icon="archive" class="text-white! text-xs!" />
                    </div>
                    <div class="flex-1! min-w-0!">
                      <div class="text-xs! text-white/70!">Cater</div>
                      <div class="text-xs! sm:text-sm! font-bold! text-white! truncate!">
                        {{ billingStore.selectedCustomer?.cater || 'Cater-001' }}
                      </div>
                    </div>
                  </div>
                </div>

                <div class="mt-3! sm:mt-4! pt-3! sm:pt-4! border-t! border-white/20!">
                  <div class="flex! items-center! justify-between!">
                    <div class="flex! items-center! gap-2! sm:gap-4!">
                      <div class="relative! group!">
                        <div
                          class="absolute! inset-0! bg-gradient-to-br! from-emerald-400/20! to-blue-400/20! rounded-xl! blur-xl! group-hover:blur-2xl! transition-all! duration-300!"
                        ></div>

                        <div
                          class="relative! w-10! h-10! sm:w-14! sm:h-14! rounded-xl! bg-gradient-to-br! from-white/90! to-white/70! backdrop-blur-md! p-1.5! sm:p-2! shadow-lg! border! border-white/30!"
                        >
                          <img
                            :src="getQRCodeUrl()"
                            alt="QR Code"
                            class="w-full! h-full! rounded-md!"
                          />

                          <div
                            class="absolute! top-0! left-0! w-1.5! h-1.5! sm:w-2! sm:h-2! border-t-2! border-l-2! border-emerald-400! rounded-tl!"
                          ></div>
                          <div
                            class="absolute! top-0! right-0! w-1.5! h-1.5! sm:w-2! sm:h-2! border-t-2! border-r-2! border-blue-400! rounded-tr!"
                          ></div>
                          <div
                            class="absolute! bottom-0! left-0! w-1.5! h-1.5! sm:w-2! sm:h-2! border-b-2! border-l-2! border-blue-400! rounded-bl!"
                          ></div>
                          <div
                            class="absolute! bottom-0! right-0! w-1.5! h-1.5! sm:w-2! sm:h-2! border-b-2! border-r-2! border-emerald-400! rounded-br!"
                          ></div>

                          <div
                            class="absolute! inset-0! rounded-xl! bg-gradient-to-t! from-transparent! via-emerald-400/10! to-transparent! animate-pulse!"
                          ></div>
                        </div>

                        <div
                          class="absolute! -top-1! -right-1! w-3! h-3! sm:w-4! sm:h-4! bg-emerald-400! rounded-full! flex! items-center! justify-center! shadow-lg!"
                        >
                          <font-awesome-icon icon="check" class="text-white! text-xs!" />
                        </div>
                      </div>

                      <div class="flex-1! min-w-0!">
                        <div class="text-xs! text-white/70! font-medium!">QR Verification</div>
                        <div
                          class="text-xs! sm:text-sm! font-mono! font-bold! text-white! truncate!"
                        >
                          {{ billingStore.selectedCustomer?.id || 'PAM-2025-09821' }}
                        </div>
                        <div class="flex! items-center! gap-1! mt-1!">
                          <div
                            class="w-1.5! h-1.5! bg-emerald-400! rounded-full! animate-pulse!"
                          ></div>
                          <div class="text-xs! text-white/60!">Ready to scan</div>
                        </div>
                      </div>
                    </div>

                    <div
                      class="w-5! h-5! sm:w-7! sm:h-7! rounded-full! bg-gradient-to-br! from-emerald-400/20! to-blue-400/20! backdrop-blur-sm! flex! items-center! justify-center! border! border-white/30!"
                    >
                      <font-awesome-icon icon="qrcode" class="text-white! text-xs!" />
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </ContentCard>
      </div>
    </div>

    <div v-else class="flex! flex-col! items-center! justify-center! py-12! sm:py-16! px-4!">
      <div class="text-center! max-w-md!">
        <div
          class="w-16! h-16! sm:w-20! sm:h-20! mx-auto! mb-4! sm:mb-6! rounded-full! bg-sky-50! flex! items-center! justify-center!"
        >
          <font-awesome-icon icon="search" class="text-sky-600! text-2xl! sm:text-3xl!" />
        </div>
        <h3 class="text-lg! sm:text-xl! font-semibold! text-slate-900! mb-2!">
          Pilih Pelanggan Terlebih Dahulu
        </h3>
        <p class="text-slate-600! text-sm! leading-relaxed!">
          Masukkan nama pelanggan, ID, atau kode instalasi untuk menampilkan data billing dan
          informasi pelanggan.
        </p>
      </div>
    </div>

    <div class="text-center py-8 text-[10px] text-slate-400 font-semibold tracking-[2px] uppercase">
      PAMSIMAS · LAYANAN AIR BERSIH MASYARAKAT
    </div>
  </div>
</template>

<script setup>
import { onMounted, computed } from 'vue'
import { useBillingStore } from '@/stores/billingStore.js'
import CustomSearch from '@/presentations/components/ui/CustomSearch.vue'
import ContentCard from '@/presentations/components/ui/ContentCard.vue'
import MeterDisplay from '@/presentations/components/ui/MeterDisplay.vue'
import BillingForm from '@/presentations/components/billing/BillingForm.vue'
import DetailModal from './partials/BillingDetail.vue'
import { ref } from 'vue'

const billingStore = useBillingStore()

const currentPeriod = computed(() => billingStore.currentPeriod)

const showDetailModal = ref(false)

const handleSearch = () => {
  showDetailModal.value = true
}

const selectCustomer = async (customer) => {
  await billingStore.selectCustomer(customer)
}

const handleSavePayment = async (paymentData) => {
  const result = await billingStore.savePayment(paymentData)
  if (result.success) {
    console.log('Payment saved successfully:', result)
  } else {
    console.error('Payment save failed:', result)
  }
}

const getInitialFormData = (period) => {
  return {
    periodId: period.id,
    tanggal: new Date(),
    kode: billingStore.selectedCustomer?.installationCode || '1.01.0002',
    meterAwal: period.meterAwal || 352,
    meterAkhir: period.meterAkhir || 368,
    pemakaian: period.pemakaian || 16,
    tagihan: 64000,
    abodemen: 10000,
    denda: period.type === 'overdue' ? 5000 : 0,
    pembayaran: 74000,
  }
}

const getCustomerInitials = () => {
  const name = billingStore.selectedCustomer?.name || 'Bambang Susanto'
  return name
    .split(' ')
    .map((n) => n[0])
    .join('')
    .toUpperCase()
}

const getQRCodeUrl = () => {
  const customerId = billingStore.selectedCustomer?.id || 'PAM-2025-09821'
  const period = 'MEI2025'
  return `https://api.qrserver.com/v1/create-qr-code/?size=120x120&data=${customerId}-${period}&color=0B7A9E`
}

onMounted(async () => {
  await billingStore.initializeStore()
})
</script>
