<template>
  <div class="teknisi-retribusi-root p-2! lg:p-4!">
    <div class="mb-6! lg:mb-8! flex! flex-col! lg:flex-row! lg:items-center! justify-between! gap-4! lg:gap-6!">
      <div>
        <h1 class="text-2xl! lg:text-3xl! font-extrabold! text-slate-800! tracking-tight!">
          Input <span class="text-cyan-500!">Retribusi Sampah</span>
        </h1>
        <p class="text-[11px]! lg:text-xs! text-slate-500! mt-0.5! font-medium!">
          Konfirmasi pengambilan sampah dan catat iuran warga hari ini.
        </p>
      </div>

      <div class="flex! items-center! gap-2! lg:gap-3! bg-white! py-1.5! lg:py-2.5! px-3! lg:px-5! rounded-[2rem]! border! border-slate-100! shadow-sm!">
        <div class="relative!">
          <div class="w-8! lg:w-10! h-8! lg:h-10! rounded-full! bg-gradient-to-br! from-cyan-500! to-cyan-700! text-white! flex! items-center! justify-center! text-[10px]! lg:text-xs! font-black! shadow-inner!">
            AS
          </div>
          <div class="absolute! -bottom-0.5! -right-0.5! w-2.5! lg:w-3.5! h-2.5! lg:h-3.5! bg-emerald-500! border-2! border-white! rounded-full!"></div>
        </div>
        <div>
          <div class="text-[10px]! lg:text-[10px]! font-black! text-cyan-600! uppercase! tracking-widest! opacity-80!">Petugas</div>
          <div class="text-xs! lg:text-sm! font-black! text-slate-800! leading-tight!">Ahmad Subarjo</div>
        </div>
      </div>
    </div>

    <ContentCard variant="elevated" padding="normal" rounded="2xl" class="shadow-[0_20px_25px_-5px_rgba(15,23,42,0.12),0_10px_10px_-5px_rgba(15,23,42,0.04)]! mb-12!">
      <div class="space-y-8! pb-4!">
        <div class="flex! items-center! gap-2! sm:gap-4!">
          <div class="flex! items-center! h-14! bg-white! rounded-2xl! border! border-slate-100! p-1.5! pr-2! sm:pr-4! shadow-sm! w-full! sm:w-auto!">
            <div class="hidden! sm:flex! w-9! h-9! rounded-xl! bg-cyan-100! text-cyan-600! items-center! justify-center! mr-2!">
              <font-awesome-icon icon="calendar-alt" />
            </div>
            <div class="flex! items-center! gap-1! flex-1! sm:flex-none!">
              <div class="flex-1! sm:w-[110px]!">
                <SelectSearch
                  v-model="selectedMonth"
                  :options="bulanOptions.map(b => ({ id: b, text: b }))"
                  placeholder="Bulan"
                  no-margin
                  class="compact-select!"
                />
              </div>
              <div class="w-px! h-4! bg-slate-200! mx-1!"></div>
              <div class="flex-1! sm:w-[85px]!">
                <SelectSearch
                  v-model="selectedYear"
                  :options="tahunOptions.map(y => ({ id: y, text: y }))"
                  placeholder="Tahun"
                  no-margin
                  class="compact-select!"
                />
              </div>
            </div>
          </div>
          
          <button 
            @click="refreshData"
            class="w-14! h-14! rounded-2xl! bg-white! border! border-slate-100! text-slate-400! hover:text-cyan-600! hover:border-cyan-200! shadow-sm! transition-all! flex! items-center! justify-center! active:scale-90!"
            title="Refresh Data"
          >
            <font-awesome-icon icon="redo-alt" />
          </button>
        </div>

        <div class="relative! group!">
          <div class="absolute! inset-0! bg-cyan-500/10! rounded-[2rem]! blur-2xl! opacity-0! group-focus-within:opacity-100! transition-all! duration-500!"></div>
          <div class="relative! bg-slate-50! border! border-slate-100! rounded-[2rem]! p-2! flex! items-center! gap-2! transition-all! duration-300! group-focus-within:bg-white! group-focus-within:border-cyan-300! group-focus-within:shadow-2xl! group-focus-within:shadow-cyan-100!">
            <div class="w-12! h-12! rounded-full! bg-white! flex! items-center! justify-center! text-slate-400! group-focus-within:text-cyan-500! transition-colors! shadow-sm!">
              <font-awesome-icon icon="search" />
            </div>
            <input 
              v-model="searchQuery"
              type="text" 
              placeholder="Cari nama pelanggan atau nomor rumah..." 
              class="hidden! lg:block! flex-1! bg-transparent! border-none! outline-none! focus:outline-none! focus:ring-0! font-bold! text-slate-700! px-2! placeholder:text-slate-400!"
            >
            <input 
              v-model="searchQuery"
              type="text" 
              placeholder="Cari pelanggan..." 
              class="lg:hidden! flex-1! bg-transparent! border-none! outline-none! focus:outline-none! focus:ring-0! font-bold! text-slate-700! px-2! placeholder:text-slate-400!"
            >
            <div class="hidden lg:flex! items-center! gap-2! pr-2!">
              <kbd class="px-2! py-1! bg-white! border! border-slate-200! rounded-lg! text-[10px]! font-black! text-slate-400!">ESC TO CLEAR</kbd>
            </div>
          </div>
        </div>
      </div>
    </ContentCard>

    <div class="space-y-4!">
      <div 
        v-for="item in paginatedData" 
        :key="item.id"
        class="task-card! bg-white! p-2.5! rounded-3xl! border! border-slate-50! shadow-[0_20px_25px_-5px_rgba(15,23,42,0.12),0_10px_10px_-5px_rgba(15,23,42,0.04)]! flex! items-center! gap-4! transition-all! hover:translate-y-[-2px]!"
        :class="{ 'opacity-60! bg-slate-50!': item.status === STATUS_TYPES.PAID }"
      >
        <div 
          class="w-11! h-11! rounded-2xl! flex! items-center! justify-center! shrink-0!"
          :class="item.status === STATUS_TYPES.PAID ? 'bg-emerald-50! text-emerald-500!' : 'bg-cyan-50! text-cyan-600!'"
        >
          <font-awesome-icon :icon="item.status === STATUS_TYPES.PAID ? 'check-double' : 'trash'" size="lg" />
        </div>

        <div class="flex-1! min-w-0!">
          <h3 class="font-black! text-slate-800! text-base! lg:text-base! truncate!">{{ item.nama }}</h3>
          <p class="text-xs! lg:text-xs! font-bold! text-slate-400! tracking-wider!">ID: {{ item.id }} • RT 02/01</p>
          <div class="flex! items-center! gap-1! text-[11px]! lg:text-[10px]! font-black! text-cyan-600! uppercase! tracking-tighter!">
             Rp 25.000 <span class="mx-1! opacity-30!">|</span> <span>Reguler</span>
          </div>
        </div>

        <div class="shrink-0! flex! justify-center! min-w-[60px]! lg:min-w-[100px]!">
          <button 
            v-if="item.status !== STATUS_TYPES.PAID"
            @click="confirmCollection(item)"
            class="px-3! lg:px-5! py-2! bg-cyan-600! text-white! rounded-xl! lg:rounded-2xl! font-black! text-[11px]! lg:text-xs! shadow-lg! shadow-cyan-100! hover:bg-cyan-700! transition-all! active:scale-95!"
          >
            <span class="lg:hidden!">INPUT</span>
            <span class="hidden lg:inline!">INPUT IURAN</span>
          </button>
          <div 
            v-else 
            class="flex! items-center! gap-1! lg:gap-2! text-emerald-600! font-black! text-[11px]! lg:text-xs!"
          >
            <font-awesome-icon icon="check-circle" />
            <span class="hidden sm:inline!">TERCATAT</span>
          </div>
        </div>
      </div>

      <div v-if="totalPages > 1" class="hidden! lg:flex! items-center! justify-center! gap-2! mt-10! pb-10!">
        <button 
          @click="currentPage--" 
          :disabled="currentPage === 1"
          class="w-10! h-10! rounded-xl! bg-white! border! border-slate-100! text-slate-400! disabled:opacity-30! flex! items-center! justify-center! shadow-sm!"
        >
          <font-awesome-icon icon="chevron-left" />
        </button>
        
        <div class="flex! items-center! gap-1!">
          <button 
            v-for="page in visiblePages" 
            :key="page"
            @click="currentPage = page"
            :class="[
              'w-10! h-10! rounded-xl! font-black! text-sm! transition-all!',
              currentPage === page 
                ? 'bg-cyan-600! text-white! shadow-lg! shadow-cyan-100!' 
                : 'bg-white! text-slate-400! hover:bg-slate-50!'
            ]"
          >
            {{ page }}
          </button>
        </div>

        <button 
          @click="currentPage++" 
          :disabled="currentPage === totalPages"
          class="w-10! h-10! rounded-xl! bg-white! border! border-slate-100! text-slate-400! disabled:opacity-30! flex! items-center! justify-center! shadow-sm!"
        >
          <font-awesome-icon icon="chevron-right" />
        </button>
      </div>

      <div v-if="filteredData.length === 0" class="py-20! text-center!">
        <div class="w-20! h-20! bg-slate-100! rounded-full! flex! items-center! justify-center! mx-auto! mb-4! text-slate-400!">
          <font-awesome-icon icon="search" size="2x" />
        </div>
        <h3 class="text-lg! font-black! text-slate-800!">Data tidak ditemukan</h3>
        <p class="text-sm! text-slate-500!">Coba gunakan kata kunci pencarian lain.</p>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed } from 'vue'
import { useRetribusiSampah } from '@/composables/useRetribusiSampah'
import { useUiStore } from '@/stores/uiStore'
import SelectSearch from '@/presentations/components/SelectSearch.vue'
import Swal from 'sweetalert2'

const Toast = Swal.mixin({
  toast: true,
  position: 'top-end',
  showConfirmButton: false,
  timer: 3000,
  timerProgressBar: true,
  customClass: {
    title: 'toast-title-custom',
    popup: 'toast-popup-custom'
  },
  didOpen: (toast) => {
    toast.addEventListener('mouseenter', Swal.stopTimer)
    toast.addEventListener('mouseleave', Swal.resumeTimer)
  }
})

const uiStore = useUiStore()
const {
  searchQuery,
  bulanOptions,
  tahunOptions,
  filteredData,
  STATUS_TYPES,
  currentPage,
  perPage,
  totalPages,
  visiblePages,
} = useRetribusiSampah()

perPage.value = 5

const paginatedData = computed(() => {
  const start = (currentPage.value - 1) * perPage.value
  const end = start + perPage.value
  return filteredData.value.slice(start, end)
})

const selectedYear = ref(new Date().getFullYear().toString())
const selectedMonth = ref(bulanOptions[new Date().getMonth()])

const confirmCollection = (item) => {
  uiStore.setLoading(true)
  setTimeout(() => {
    item.status = STATUS_TYPES.PAID
    Toast.fire({
      icon: 'success',
      title: `Iuran ${item.nama} bulan ${selectedMonth.value} berhasil dicatat.`
    })
    uiStore.setLoading(false)
  }, 800)
}

const refreshData = () => {
  uiStore.setLoading(true)
  setTimeout(() => {
    Toast.fire({
      icon: 'success',
      title: 'Data berhasil diperbarui.'
    })
    uiStore.setLoading(false)
  }, 500)
}
</script>

<style scoped>
@reference "@/assets/css/main.css";

.teknisi-retribusi-root {
  animation: fadeIn 0.5s ease-out;
}

/* SweetAlert Custom Toast Styling */
:deep(.toast-popup-custom) {
  padding: 1.25rem !important;
  border-radius: 1.25rem !important;
  align-items: center !important;
  overflow: visible !important;
}

:deep(.toast-title-custom) {
  margin-left: 1rem !important;
  font-size: 0.9375rem !important;
  font-weight: 700 !important;
  color: #1e293b !important;
  margin-top: 0 !important;
  margin-bottom: 0 !important;
  line-height: 1.4 !important;
}

:deep(.swal2-icon) {
  margin: 0 !important;
  transform: scale(0.9) !important;
}

:deep(.swal2-timer-progress-bar) {
  background: rgba(6, 182, 212, 0.2) !important;
  height: 4px !important;
  border-bottom-left-radius: 1.25rem !important;
  border-bottom-right-radius: 1.25rem !important;
}

:deep(.compact-select .select-display) {
  background-color: transparent !important;
  border-style: none !important;
  box-shadow: none !important;
  height: 2.25rem !important;
  padding-top: 0 !important;
  padding-bottom: 0 !important;
  font-size: 0.75rem !important;
  font-weight: 700 !important;
  color: #475569 !important;
}

:deep(.compact-select .select-icon) {
  font-size: 10px !important;
  color: #94a3b8 !important;
}

.task-card {
  transition: all 0.3s cubic-bezier(0.16, 1, 0.3, 1);
}

.task-card:active {
  transform: scale(0.98);
}

@keyframes fadeIn {
  from { opacity: 0; transform: translateY(10px); }
  to { opacity: 1; transform: translateY(0); }
}

::-webkit-scrollbar {
  width: 6px;
}
::-webkit-scrollbar-track {
  background: transparent;
}
::-webkit-scrollbar-thumb {
  background: #e2e8f0;
  border-radius: 10px;
}
</style>
