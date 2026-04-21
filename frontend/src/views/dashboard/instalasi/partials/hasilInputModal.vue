<template>
  <Teleport to="body">
    <Transition name="modal-fade">
      <div v-if="show" class="fixed inset-0! z-50 flex items-center justify-center p-4! md:p-8!">
        <div class="absolute inset-0! bg-slate-900/60! backdrop-blur-sm!" @click="close"></div>

        <div
          class="relative w-full! h-full! max-w-7xl! bg-white rounded-2xl! shadow-xl! border border-slate-200 flex flex-col overflow-hidden animate-slide-up"
        >
          <!-- Modal Header -->
          <div
            class="flex items-center! justify-between! px-6! py-4! border-b! border-slate-200! bg-white!"
          >
            <div class="flex items-center gap-3!">
              <div
                class="w-10! h-10! rounded-full! bg-cyan-600! text-white! flex items-center! justify-center!"
              >
                <font-awesome-icon icon="file-alt" />
              </div>
              <div>
                <h2 class="text-lg! font-semibold! text-slate-800 leading-tight">
                  Hasil Input Pemakaian Air
                </h2>
                <p class="text-xs! text-slate-500! font-medium!">
                  Periode: {{ filter.bulan }} {{ filter.tahun }}
                </p>
              </div>
            </div>
            <button
              @click="close"
              class="w-9! h-9! hover:bg-slate-100! flex items-center! justify-center! text-slate-400! hover:text-slate-600! transition-all active:scale-95 rounded-md!"
            >
              <font-awesome-icon icon="times" />
            </button>
          </div>

          <!-- Filter & Search Bar -->
          <div
            class="px-6! py-4! bg-slate-50/50! border-b! border-slate-100! flex flex-col! md:flex-row! md:items-center! justify-between! gap-4!"
          >
            <div class="flex flex-row! flex-wrap! items-center! gap-x-8! gap-y-1!">
              <div class="flex items-center! gap-2! text-sm!">
                <span class="text-slate-500! whitespace-nowrap!">Cater</span>
                <span class="font-semibold! text-slate-700!">: {{ filter.cater || 'Fandi' }}</span>
              </div>
              <div class="flex items-center! gap-2! text-sm!">
                <span class="text-slate-500! whitespace-nowrap!">Maksimal Bayar</span>
                <span class="font-semibold! text-slate-700!">: 5/04/2026</span>
              </div>
            </div>

            <div class="relative! w-full! md:w-72!">
              <div
                class="absolute! inset-y-0! left-0! pl-3! flex! items-center! pointer-events-none!"
              >
                <font-awesome-icon icon="search" class="text-slate-400! text-xs!" />
              </div>
              <input
                type="text"
                v-model="searchQuery"
                placeholder="Search ..."
                class="block w-full pl-9! pr-4! py-2! bg-white border border-slate-200 rounded-lg! text-sm! focus:outline-none focus:ring-2 focus:ring-cyan-500/20 focus:border-cyan-500 transition-all shadow-sm!"
              />
            </div>
          </div>

          <!-- Table Content -->
          <div class="flex-1 overflow-auto px-0! py-0! scrollbar-custom">
            <table class="w-full border-collapse text-xs md:text-sm!">
              <thead>
                <tr class="bg-slate-700! text-white! sticky! top-0! z-20!">
                  <th class="py-3! px-4! text-center! w-12!">
                    <input
                      type="checkbox"
                      v-model="isAllSelected"
                      class="w-4! h-4! rounded! border-slate-300! text-cyan-600! focus:ring-cyan-500! cursor-pointer! transition-all! active:scale-90!"
                    />
                  </th>
                  <th class="py-3! px-4! text-left! font-semibold! uppercase! tracking-wider!">
                    Nama
                  </th>
                  <th class="py-3! px-4! text-left! font-semibold! uppercase! tracking-wider!">
                    Desa
                  </th>
                  <th class="py-3! px-4! text-center! font-semibold! uppercase! tracking-wider!">
                    RT
                  </th>
                  <th
                    class="py-3! px-4! text-left! font-semibold! uppercase! tracking-wider! whitespace-nowrap!"
                  >
                    No. Induk
                  </th>
                  <th
                    class="py-3! px-4! text-center! font-semibold! uppercase! tracking-wider! whitespace-nowrap!"
                  >
                    Meter Awal
                  </th>
                  <th
                    class="py-3! px-4! text-center! font-semibold! uppercase! tracking-wider! whitespace-nowrap!"
                  >
                    Meter Akhir
                  </th>
                  <th class="py-3! px-4! text-center! font-semibold! uppercase! tracking-wider!">
                    Pemakaian
                  </th>
                  <th
                    class="py-3! px-4! text-right! font-semibold! uppercase! tracking-wider! whitespace-nowrap!"
                  >
                    Tagihan Air
                  </th>
                  <th class="py-3! px-4! text-center! font-semibold! uppercase! tracking-wider!">
                    Status
                  </th>
                </tr>
              </thead>
              <tbody>
                <template v-for="(members, dusun) in filteredGroups" :key="dusun">
                  <!-- Dusun Group Header -->
                  <tr class="bg-slate-100/80! border-y! border-slate-200!">
                    <td colspan="10" class="py-2.5! px-4! font-bold text-slate-700!">
                      Dusun : {{ dusun }}
                    </td>
                  </tr>
                  <!-- Rows for this Dusun -->
                  <tr
                    v-for="item in members"
                    :key="item.id"
                    class="hover:bg-slate-50! transition-colors border-b! border-slate-100!"
                  >
                    <td class="py-3! px-4! text-center!">
                      <input
                        type="checkbox"
                        :value="item.id"
                        v-model="selectedIds"
                        class="w-4! h-4! rounded! border-slate-300! text-cyan-600! focus:ring-cyan-500! cursor-pointer! transition-all! active:scale-90!"
                      />
                    </td>
                    <td class="py-3! px-4! font-medium text-slate-900!">{{ item.nama }}</td>
                    <td class="py-3! px-4! text-slate-600!">{{ item.desa }}</td>
                    <td class="py-3! px-4! text-center! text-slate-600!">{{ item.rt || '-' }}</td>
                    <td class="py-3! px-4! font-mono! text-slate-500! text-xs!">{{ item.id }}</td>
                    <td class="py-3! px-4! text-center! text-slate-600!">{{ item.meterAwal }}</td>
                    <td class="py-3! px-4! text-center! text-slate-600! font-semibold!">
                      {{ item.meterAkhir }}
                    </td>
                    <td class="py-3! px-4! text-center! text-slate-600!">{{ item.pemakaian }}</td>
                    <td class="py-3! px-4! text-right font-mono font-semibold text-slate-900!">
                      {{ item.tagihan.toLocaleString('id-ID', { minimumFractionDigits: 2 }) }}
                    </td>
                    <td class="py-3! px-4! text-center!">
                      <span
                        class="inline-flex! items-center! px-2! py-0.5! rounded text-[10px]! font-bold! uppercase! tracking-wider!"
                        :class="
                          item.status === 'PAID'
                            ? 'bg-emerald-100! text-emerald-700!'
                            : 'bg-slate-100! text-slate-600!'
                        "
                      >
                        {{ item.status }}
                      </span>
                    </td>
                  </tr>
                </template>
              </tbody>
            </table>
          </div>

          <!-- Modal Footer -->
          <div
            class="px-6! py-4! bg-slate-50! border-t! border-slate-200! flex justify-end items-center gap-3! min-h-[80px]!"
          >
            <button
              @click="close"
              class="flex items-center! gap-2! bg-white! border! border-slate-300! hover:bg-slate-100! text-slate-700! px-6! py-2.5! font-semibold! transition-all active:scale-95 rounded-lg! shadow-sm!"
            >
              <font-awesome-icon icon="times" />
              Close
            </button>
            <button
              class="flex items-center! gap-2! bg-amber-500! hover:bg-amber-600! text-white! px-6! py-2.5! font-semibold! transition-all active:scale-95 rounded-lg! shadow-md! shadow-amber-200!"
            >
              <font-awesome-icon icon="receipt" />
              Cetak Struk
            </button>
            <button
              class="flex items-center! gap-2! bg-cyan-600! hover:bg-cyan-700! text-white! px-6! py-2.5! font-semibold! transition-all active:scale-95 rounded-lg! shadow-md! shadow-cyan-200!"
            >
              <font-awesome-icon icon="print" />
              Cetak Daftar Tagihan
            </button>
          </div>
        </div>
      </div>
    </Transition>
  </Teleport>
</template>

<script setup>
import { ref, computed, watch, onMounted, onUnmounted } from 'vue'

const props = defineProps({
  show: {
    type: Boolean,
    default: false,
  },
  groupedData: {
    type: Object,
    default: () => ({}),
  },
  filter: {
    type: Object,
    default: () => ({}),
  },
})

const emit = defineEmits(['close'])

const searchQuery = ref('')
const selectedIds = ref([])

const close = () => {
  emit('close')
}

const allVisibleIds = computed(() => {
  return Object.values(filteredGroups.value)
    .flat()
    .map((item) => item.id)
})

const isAllSelected = computed({
  get: () =>
    allVisibleIds.value.length > 0 &&
    allVisibleIds.value.every((id) => selectedIds.value.includes(id)),
  set: (val) => {
    if (val) {
      // Add only currently visible IDs that aren't already selected
      const newSelections = new Set([...selectedIds.value, ...allVisibleIds.value])
      selectedIds.value = Array.from(newSelections)
    } else {
      // Remove only currently visible IDs from selection
      selectedIds.value = selectedIds.value.filter((id) => !allVisibleIds.value.includes(id))
    }
  },
})

const filteredGroups = computed(() => {
  if (!searchQuery.value) return props.groupedData

  const query = searchQuery.value.toLowerCase()
  const result = {}

  Object.entries(props.groupedData).forEach(([dusun, members]) => {
    const matched = members.filter(
      (item) => item.nama.toLowerCase().includes(query) || item.id.toLowerCase().includes(query),
    )
    if (matched.length > 0) {
      result[dusun] = matched
    }
  })

  return result
})

const handleKeydown = (e) => {
  if (e.key === 'Escape' && props.show) {
    close()
  }
}

watch(
  () => props.show,
  (val) => {
    if (val) {
      document.body.style.overflow = 'hidden'
    } else {
      document.body.style.overflow = ''
    }
  },
)

onMounted(() => {
  window.addEventListener('keydown', handleKeydown)
})

onUnmounted(() => {
  window.removeEventListener('keydown', handleKeydown)
  document.body.style.overflow = ''
})
</script>

<style scoped>
.modal-fade-enter-active,
.modal-fade-leave-active {
  transition: opacity 0.4s ease;
}

.modal-fade-enter-from,
.modal-fade-leave-to {
  opacity: 0;
}

@keyframes slide-up {
  from {
    transform: translateY(30px) scale(0.98);
    opacity: 0;
  }
  to {
    transform: translateY(0) scale(1);
    opacity: 1;
  }
}

.animate-slide-up {
  animation: slide-up 0.5s cubic-bezier(0.16, 1, 0.3, 1);
}

.scrollbar-custom::-webkit-scrollbar {
  width: 6px;
  height: 6px;
}

.scrollbar-custom::-webkit-scrollbar-track {
  background: transparent;
}

.scrollbar-custom::-webkit-scrollbar-thumb {
  background: #e2e8f0;
  border-radius: 10px;
}

.scrollbar-custom::-webkit-scrollbar-thumb:hover {
  background: #cbd5e1;
}
</style>
