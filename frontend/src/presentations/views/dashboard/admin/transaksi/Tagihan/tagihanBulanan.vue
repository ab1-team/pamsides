<template>
  <div>
    <div class="sticky! top-[56px]! z-30! bg-white/20! backdrop-blur-md! pt-1! pb-4! mb-6! -mx-4! px-4! md:-mx-10! md:px-10! border-b! border-slate-200/20!">
      <div class="relative!">
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
    </div>

    <div v-if="billingStore.selectedCustomer" class="flex! flex-col! xl:flex-row! gap-4! xl:gap-6!">
      <div class="flex-1! min-w-0!">
        <div class="mb-4!">
          <div class="relative! overflow-hidden! rounded-xl! border! border-blue-100/85! bg-gradient-to-br! from-slate-50! via-blue-50/20! to-white! p-2.5! sm:p-3! md:pr-5! shadow-sm! transition-all! hover:shadow-md!">
            <div class="absolute! -right-8! -top-8! w-24! h-24! rounded-full! bg-gradient-to-br! from-blue-400/10! to-sky-400/5! blur-xl!"></div>
            
            <div class="relative! flex! flex-col! md:flex-row! md:items-center! justify-between! gap-3!">
              <div class="flex! items-center! gap-2.5! flex-1!">
                <div class="w-9! h-9! rounded-lg! bg-gradient-to-tr! from-blue-600! to-sky-400! flex! items-center! justify-center! text-white! shadow-md! shadow-blue-500/20! flex-shrink-0! transform! transition-transform! hover:scale-105!">
                  <font-awesome-icon icon="box" class="text-sm!" />
                </div>
                
                <div class="space-y-0.5!">
                  <div class="flex! items-center! gap-1.5!">
                    <span class="text-[8px]! font-extrabold! tracking-widest! text-blue-600! uppercase! bg-blue-100/60! px-1.5! py-0.5! rounded-md!">Informasi Paket</span>
                    <span class="w-1! h-1! rounded-full! bg-emerald-500! animate-pulse!"></span>
                  </div>
                  
                  <h3 class="text-sm! sm:text-base! font-extrabold! text-slate-800! tracking-tight! leading-tight!">
                    {{ billingStore.selectedCustomer?.packageName || 'Paket Standar' }}
                  </h3>
                  
                  <div class="flex! items-center! gap-1.5! text-[10px]!">
                    <span class="inline-flex! items-center! whitespace-nowrap! px-1.5! py-0.5! rounded-md! bg-slate-100! text-slate-605! font-medium! border! border-slate-200/50!">
                      Abodemen: <strong class="ml-1! text-slate-850!">{{ billingStore.formatAmount(billingStore.selectedCustomer?.abodemen || 10000) }}</strong>
                    </span>
                    <span class="inline-flex! items-center! whitespace-nowrap! px-1.5! py-0.5! rounded-md! bg-red-50! text-red-650! font-medium! border! border-red-100/50!">
                      Denda: <strong class="ml-1! text-red-850!">{{ billingStore.formatAmount(billingStore.selectedCustomer?.penalty || 5000) }}</strong>
                    </span>
                  </div>
                </div>
              </div>
              
              <div class="hidden! md:block! w-px! h-9! bg-gradient-to-b! from-transparent! via-slate-200! to-transparent!"></div>
              
              <div class="flex-shrink-0! bg-white/60! backdrop-blur-sm! p-2! sm:p-2.5! rounded-lg! border! border-slate-105! shadow-sm! md:min-w-[250px]!">
                <h4 class="text-[8px]! font-extrabold! text-slate-500! uppercase! tracking-wider! mb-1! flex! items-center! gap-1.5!">
                  <span class="w-1! h-1! rounded-full! bg-blue-500!"></span>
                  Tarif Pemakaian Air
                </h4>
                
                <div class="space-y-0.5!">
                  <div 
                    v-for="(block, idx) in (billingStore.selectedCustomer?.tariffBlocks || [])" 
                    :key="idx"
                    class="text-[10px]! flex! items-center! justify-between! gap-4!"
                  >
                    <span class="font-medium! text-slate-500!">
                      Blok {{ idx + 1 }} <span class="text-[8px]! text-slate-400! font-normal!">({{ block.min }}-{{ block.max }} m³)</span>
                    </span>
                    <span class="font-extrabold! text-blue-600!">
                      {{ billingStore.formatAmount(block.price) }}/m³
                    </span>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>

        <div class="space-y-3!">
          <ContentCard
            v-for="period in billingStore.filteredBillingPeriods"
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

      <div class="w-full! xl:w-[280px]! flex-shrink-0!">
        <ContentCard variant="elevated" padding="large" class="overflow-hidden!">
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
import { ref, onMounted, computed } from 'vue'
import { useBillingStore } from '@/stores/billingStore.js'
import CustomSearch from '@/presentations/components/ui/CustomSearch.vue'
import ContentCard from '@/presentations/components/ui/ContentCard.vue'
import BillingForm from '@/presentations/components/billing/BillingForm.vue'
import DetailModal from './partials/BillingDetail.vue'
import { MySwal } from '@/utils/swal.js'

const billingStore = useBillingStore()

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
    MySwal.fire({
      title: 'Pembayaran Berhasil',
      text: 'Tagihan bulan ini berhasil dikonfirmasi dan lunas.',
      icon: 'success',
      confirmButtonText: 'OK',
      confirmButtonColor: '#10B981',
    })
    
    if (billingStore.selectedCustomer) {
      await billingStore.fetchBillingPeriods(billingStore.selectedCustomer.customer_id)
    }
  } else {
    MySwal.fire({
      title: 'Pembayaran Gagal',
      text: result.message || 'Terjadi kesalahan saat memproses pembayaran.',
      icon: 'error',
      confirmButtonText: 'OK',
      confirmButtonColor: '#EF4444',
    })
  }
}

const getInitialFormData = (period) => {
  return {
    periodId: period.id,
    tanggal: new Date(),
    meterAwal: period.meterAwal || 0,
    meterAkhir: period.meterAkhir || 0,
    pemakaian: period.pemakaian || 0,
    tagihan: period.usage_charge || 0,
    abodemen: period.abodemen || 0,
    denda: period.denda || 0,
    pembayaran: period.amount || 0,
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



onMounted(async () => {
  await billingStore.initializeStore()
})
</script>
