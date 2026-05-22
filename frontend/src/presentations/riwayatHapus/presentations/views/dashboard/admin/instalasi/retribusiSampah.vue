<template>
  <div class="admin-retribusi-root p-2! lg:p-4!">
    <!-- Header Section -->
    <div class="mb-6! lg:mb-8! flex! flex-col! lg:flex-row! lg:items-center! justify-between! gap-4! lg:gap-6!">
      <div>
        <h1 class="text-2xl! lg:text-3xl! font-extrabold! text-slate-800! tracking-tight!">
          Manajemen <span class="text-cyan-500!">Retribusi Sampah</span>
        </h1>
        <p class="text-[11px]! lg:text-xs! text-slate-500! mt-0.5! font-medium!">
          Kelola data iuran, monitor status pembayaran, dan edit transaksi pelanggan.
        </p>
      </div>

    </div>


    <div class="mb-6!">
      <ContentCard variant="elevated" padding="normal" rounded="2xl" class="rounded-3xl! shadow-2xl! border-none! bg-linear-to-br! from-white! to-slate-50/50!">
        <div class="grid! grid-cols-1! lg:grid-cols-12! gap-4! items-center!">
          <div class="lg:col-span-9! grid! grid-cols-1! sm:grid-cols-12! gap-3! items-center!">
            <div class="sm:col-span-3! lg:col-span-2!">
              <SelectSearch
                v-model="filter.tahun"
                :options="tahunOptions.map(y => ({ id: y, text: y }))"
                placeholder="Tahun"
                no-margin
                class="premium-filter-select!"
              />
            </div>

            <div class="sm:col-span-4! lg:col-span-3!">
              <SelectSearch
                v-model="filter.bulan"
                :options="bulanOptions.map(b => ({ id: b, text: b }))"
                placeholder="Bulan"
                no-margin
                class="premium-filter-select!"
              />
            </div>

            <div class="sm:col-span-5! lg:col-span-5!">
              <SelectSearch
                v-model="filter.cater"
                :options="[
                  { id: 'cater-001', text: 'Ahmad Subarjo' },
                  { id: 'cater-002', text: 'Bambang Irawan' },
                  { id: 'cater-003', text: 'Siti Aminah' },
                ]"
                placeholder="Pilih Petugas"
                no-margin
                class="premium-filter-select!"
              />
            </div>

            <div class="sm:col-span-12! lg:col-span-2! flex! items-center! justify-center! lg:justify-start!">
              <BaseButton
                variant="secondary-gradient"
                @click="handleApplyFilter"
                class="h-12! w-full! lg:w-12! p-0! rounded-2xl! lg:rounded-full! shadow-xl! shadow-slate-200! transition-all! hover:scale-105! active:scale-95!"
                icon="sliders"
              >
                <span class="lg:hidden! ml-2! font-black! uppercase! text-[11px]!">Terapkan Filter</span>
              </BaseButton>
            </div>
          </div>

          <div class="lg:col-span-3! flex! items-center!">
            <div class="relative! w-full!">
              <input 
                v-model="searchQuery"
                type="text" 
                placeholder="Cari Nama / ID..." 
                class="w-full! h-12! bg-white! border! border-slate-200! rounded-2xl! pl-10! pr-4! text-sm! font-bold! text-slate-700! focus:border-cyan-500! focus:ring-4! focus:ring-cyan-500/10! outline-none! transition-all! shadow-sm!"
              >
              <font-awesome-icon icon="search" class="absolute! left-4! top-1/2! -translate-y-1/2! text-slate-400!" />
            </div>
          </div>
        </div>
      </ContentCard>
    </div>

    <div class="space-y-4!">
      <div 
        v-for="item in paginatedData" 
        :key="item.id"
        class="admin-record-card! bg-white! p-4! lg:p-5! rounded-3xl! border! border-slate-50! shadow-[0_15px_30px_-5px_rgba(0,0,0,0.05)]! flex! flex-col! lg:flex-row! lg:items-center! gap-5! lg:gap-6! transition-all! hover:shadow-2xl! hover:shadow-cyan-500/10!"
      >
        <div class="flex! items-center! gap-4! w-full! lg:w-auto! lg:flex-1!">
          <div 
            class="w-12! h-12! lg:w-14! h-14! shrink-0! rounded-2xl! flex! items-center! justify-center! text-white! font-black! text-sm! lg:text-base! shadow-lg!"
            :style="{ background: item.avatarColor }"
          >
            {{ item.initials }}
          </div>
          <div class="min-w-0!">
            <div class="flex! items-center! gap-2! mb-1!">
              <h3 class="font-black! text-slate-800! text-base! lg:text-lg! truncate! leading-tight!">{{ item.nama }}</h3>
              <span class="px-2! py-0.5! bg-blue-50! text-blue-500! border! border-blue-100! rounded-lg! text-[9px]! font-black! uppercase! tracking-wider! shrink-0!">
                {{ item.jenis || 'Reguler' }}
              </span>
            </div>
            <div class="flex! flex-wrap! items-center! gap-x-3! gap-y-1!">
              <span class="px-2! py-0.5! bg-slate-100! text-slate-500! rounded-md! text-[10px]! font-black! tracking-wider!">ID: {{ item.id }}</span>
              <span class="text-[11px]! font-bold! text-slate-400!">Krajan, RT 02</span>
            </div>
          </div>
        </div>

        <div class="flex! flex-row! items-center! justify-between! sm:justify-start! gap-6! lg:gap-16! py-3! lg:py-0! border-y! border-slate-50! lg:border-y-0! lg:border-l! lg:border-slate-100! lg:pl-12! w-full! lg:w-auto!">
          <div class="flex! flex-col! gap-1! w-28! lg:w-32!">
            <div class="text-[9px]! font-black! text-slate-400! uppercase! tracking-[0.2em]!">Tagihan</div>
            <div class="text-sm! lg:text-base! font-black! text-slate-800! whitespace-nowrap!">
              Rp {{ item.tagihan?.toLocaleString('id-ID') || '0' }}
            </div>
          </div>

          <div class="flex! flex-col! gap-2! w-28! lg:w-32!">
            <div class="text-[9px]! font-black! text-slate-400! uppercase! tracking-[0.2em]!">Status</div>
            <div 
              class="inline-flex! items-center! gap-1.5! px-2.5! py-1! rounded-lg! text-[10px]! font-black! uppercase! tracking-widest! w-fit!"
              :style="{ 
                backgroundColor: STATUS_COLORS[item.status] + '15', 
                color: STATUS_COLORS[item.status]
              }"
            >
              <span class="w-1.5! h-1.5! rounded-full!" :style="{ backgroundColor: STATUS_COLORS[item.status] }"></span>
              {{ item.status }}
            </div>
          </div>
        </div>

        <div class="flex! items-center! justify-end! gap-2! w-full! lg:w-auto! lg:ml-4!">
          <BaseButton
            variant="ghost"
            size="sm"
            @click="handleEdit(item)"
            class="w-10! h-10! p-0! rounded-xl! bg-slate-50! text-slate-600! hover:bg-blue-50! hover:text-blue-600! transition-all!"
            title="Edit Data"
            icon="edit"
          />
          <BaseButton
            variant="ghost"
            size="sm"
            @click="confirmDelete(item)"
            class="w-10! h-10! p-0! rounded-xl! bg-slate-50! text-slate-600! hover:bg-red-50! hover:text-red-600! transition-all!"
            title="Hapus Data"
            icon="trash"
          />
        </div>
      </div>

      <div v-if="filteredData.length === 0" class="py-20! text-center! bg-white! rounded-3xl! border! border-dashed! border-slate-200!">
        <div class="w-20! h-20! bg-slate-50! rounded-full! flex! items-center! justify-center! mx-auto! mb-4! text-slate-300!">
          <font-awesome-icon icon="folder-open" size="2x" />
        </div>
        <h3 class="text-xl! font-black! text-slate-800!">Data Tidak Tersedia</h3>
        <p class="text-sm! text-slate-500! max-w-xs! mx-auto!">Tidak ada data retribusi yang sesuai dengan filter atau pencarian Anda.</p>
      </div>

      <div v-if="totalPages > 1" class="flex! items-center! justify-between! bg-white! p-4! rounded-3xl! shadow-sm! border! border-slate-50! mt-8!">
        <p class="text-xs! font-bold! text-slate-400! hidden md:block!">Menampilkan {{ perPage }} dari {{ tableData.length }} entri</p>
        <div class="flex! items-center! gap-2! mx-auto md:mx-0!">
          <button 
            @click="currentPage--" 
            :disabled="currentPage === 1"
            class="w-9! h-9! rounded-xl! bg-slate-50! text-slate-400! disabled:opacity-30! flex! items-center! justify-center! transition-all! hover:bg-white! hover:shadow-md!"
          >
            <font-awesome-icon icon="chevron-left" />
          </button>
          
          <div class="flex! items-center! gap-1!">
            <button 
              v-for="page in visiblePages" 
              :key="page"
              @click="currentPage = page"
              :class="[
                'w-9! h-9! rounded-xl! font-black! text-[11px]! transition-all!',
                currentPage === page 
                  ? 'bg-cyan-600! text-white! shadow-lg! shadow-cyan-100!' 
                  : 'bg-slate-50! text-slate-400! hover:bg-white! hover:shadow-md!'
              ]"
            >
              {{ page }}
            </button>
          </div>

          <button 
            @click="currentPage++" 
            :disabled="currentPage === totalPages"
            class="w-9! h-9! rounded-xl! bg-slate-50! text-slate-400! disabled:opacity-30! flex! items-center! justify-center! transition-all! hover:bg-white! hover:shadow-md!"
          >
            <font-awesome-icon icon="chevron-right" />
          </button>
        </div>
      </div>
    </div>

    <!-- Modals -->
    <EditRetribusiModal
      :show="showEditModal"
      :data="selectedRow"
      @close="showEditModal = false"
      @save="handleSaveEdit"
    />
  </div>
</template>

<script setup>
import { useRetribusiSampah } from '@/composables/useRetribusiSampah'
import SelectSearch from '@/presentations/components/SelectSearch.vue'
import ContentCard from '@/presentations/components/ui/ContentCard.vue'
import BaseButton from '@/presentations/components/ui/BaseButton.vue'
import EditRetribusiModal from './editRetribusiSampah.vue'
import Swal from 'sweetalert2'
import { computed } from 'vue'

const {
  filter,
  searchQuery,
  currentPage,
  perPage,
  tahunOptions,
  bulanOptions,
  tableData,
  filteredData,
  totalPages,
  visiblePages,
  STATUS_COLORS,
  showEditModal,
  selectedRow,
  handleApplyFilter,
  handleCetakFormInput,
  handleInputPemakaian,
  handleEdit,
  handleSaveEdit,
  handleDelete,
} = useRetribusiSampah()

// Adjust perPage for card layout
perPage.value = 6

const paginatedData = computed(() => {
  const start = (currentPage.value - 1) * perPage.value
  const end = start + perPage.value
  return filteredData.value.slice(start, end)
})

const confirmDelete = (item) => {
  Swal.fire({
    title: 'Hapus Data?',
    text: `Anda akan menghapus data retribusi ${item.nama}. Tindakan ini tidak dapat dibatalkan!`,
    icon: 'warning',
    showCancelButton: true,
    confirmButtonColor: '#ef4444',
    cancelButtonColor: '#64748b',
    confirmButtonText: 'Ya, Hapus!',
    cancelButtonText: 'Batal',
    reverseButtons: true,
    customClass: {
      popup: 'rounded-[2rem]!',
      title: 'font-black!',
      confirmButton: 'rounded-xl! px-6! py-2.5!',
      cancelButton: 'rounded-xl! px-6! py-2.5!'
    }
  }).then((result) => {
    if (result.isConfirmed) {
      handleDelete(item)
      Swal.fire({
        icon: 'success',
        title: 'Terhapus!',
        text: 'Data retribusi berhasil dihapus.',
        toast: true,
        position: 'top-end',
        showConfirmButton: false,
        timer: 3000,
        timerProgressBar: true
      })
    }
  })
}
</script>

<style scoped>
@reference "@/assets/css/main.css";

.admin-retribusi-root {
  animation: slideUp 0.6s cubic-bezier(0.16, 1, 0.3, 1);
}

.admin-record-card {
  transition: all 0.4s cubic-bezier(0.16, 1, 0.3, 1);
}

.admin-record-card:hover {
  transform: translateY(-4px) scale(1.005);
}

:deep(.premium-filter-select .select-display) {
  height: 3rem! !important;
  border-radius: 1rem! !important;
  background-color: #fff! !important;
  border: 1px solid #e2e8f0! !important;
  font-weight: 700! !important;
  font-size: 0.875rem! !important;
  color: #1e293b! !important;
  transition: all 0.3s! !important;
  box-shadow: 0 1px 2px 0 rgba(0, 0, 0, 0.05)! !important;
}

:deep(.premium-filter-select .select-display:hover) {
  border-color: #cbd5e1! !important;
}

:deep(.premium-filter-select .select-display:focus) {
  border-color: #06b6d4! !important;
  box-shadow: 0 0 0 4px rgba(6, 182, 212, 0.1)! !important;
}

@keyframes slideUp {
  from { opacity: 0; transform: translateY(20px); }
  to { opacity: 1; transform: translateY(0); }
}
</style>
