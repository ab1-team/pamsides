<template>
  <div class="cater-pemakaian-root">
    <!-- Desktop Layout -->
    <div class="hidden! lg:block! max-w-7xl mx-auto">
      <div class="flex! justify-between! items-start! mb-10!">
        <div>
          <h1 class="text-4xl! font-black! text-slate-800! tracking-tight! mb-2!">
            Manajemen <span class="text-blue-600!">Pemakaian Air</span>
          </h1>
          <p class="text-slate-500! text-lg! font-medium!">
            Pilih periode pencatatan untuk memulai input meteran pelanggan.
          </p>
        </div>
        <div class="flex! gap-4!">
          <BaseButton
            @click="handleCetakFormInput"
            variant="warning-gradient"
            icon="print"
            class="shadow-lg! shadow-amber-200/50!"
            >Cetak Form</BaseButton
          >
          <BaseButton
            @click="openResultsModal"
            variant="success-gradient"
            icon="file-alt"
            class="shadow-lg! shadow-emerald-200/50!"
            >Hasil Input</BaseButton
          >
        </div>
      </div>

      <div class="grid! grid-cols-12! gap-8!">
        <!-- Left Column: Form -->
        <div class="col-span-7!">
          <ContentCard
            variant="bordered"
            padding="large"
            rounded="2xl"
            class="shadow-lg! shadow-slate-200/50!"
          >
            <div class="mb-8!">
              <h3 class="text-xl! font-bold! text-slate-800! mb-6! flex! items-center! gap-3!">
                <div
                  class="w-10! h-10! rounded-xl! bg-blue-50! text-blue-600! flex! items-center! justify-center!"
                >
                  <font-awesome-icon icon="calendar-check" />
                </div>
                Konfigurasi Periode
              </h3>

              <div class="grid! grid-cols-2! gap-6!">
                <div class="form-group!">
                  <label
                    class="block! text-xs! font-black! text-slate-400! uppercase! tracking-widest! mb-3!"
                    >Tahun Pemakaian</label
                  >
                  <SelectSearch
                    v-model="form.tahun"
                    :options="tahunOptions"
                    placeholder="Pilih Tahun"
                    no-margin
                  />
                </div>
                <div class="form-group!">
                  <label
                    class="block! text-xs! font-black! text-slate-400! uppercase! tracking-widest! mb-3!"
                    >Bulan Pemakaian</label
                  >
                  <SelectSearch
                    v-model="form.bulan"
                    :options="bulanOptions"
                    placeholder="Pilih Bulan"
                    no-margin
                  />
                </div>
              </div>
            </div>

            <div class="form-group! mb-10!">
              <label
                class="block! text-xs! font-black! text-slate-400! uppercase! tracking-widest! mb-3!"
                >Petugas Lapangan (Staff)</label
              >
              <div class="relative! group!">
                <input
                  type="text"
                  :value="staffName"
                  readonly
                  class="w-full! bg-slate-50! border-2! border-slate-100! rounded-2xl! px-6! py-4! text-sm! font-bold! text-slate-700! focus:border-blue-500! focus:outline-none! transition-all!"
                />
                <div class="absolute! right-6! top-1/2! -translate-y-1/2! text-blue-500!">
                  <font-awesome-icon icon="id-badge" />
                </div>
              </div>
            </div>

            <BaseButton
              :to="{
                path: '/app/instalasi/pemakaian-air/input',
                query: { tahun: form.tahun, bulan: form.bulan },
              }"
              variant="primary-gradient"
              size="lg"
              block
              icon="arrow-right"
              class="rounded-2xl! h-16! font-black! text-lg! shadow-xl! shadow-blue-200!"
            >
              MULAI INPUT DATA
            </BaseButton>
          </ContentCard>
        </div>

        <!-- Right Column: Stats & Info -->
        <div class="col-span-5! flex! flex-col! gap-6!">
          <div
            class="bg-gradient-to-br from-blue-600 to-indigo-700! rounded-2xl! p-4! text-white! shadow-lg! shadow-blue-100! relative! overflow-hidden!"
          >
            <div class="relative! z-10!">
              <p class="text-blue-100! text-[9px]! font-black! uppercase! tracking-widest! mb-3!">
                Progres Pencatatan
              </p>

              <div v-if="stats.loading" class="animate-pulse!">
                <div class="h-12! w-24! bg-white/20! rounded-lg! mb-4!"></div>
                <div class="w-full! h-3! bg-white/20! rounded-full! mb-8!"></div>
                <div class="grid! grid-cols-2! gap-6!">
                  <div class="h-10! bg-white/20! rounded-lg!"></div>
                  <div class="h-10! bg-white/20! rounded-lg!"></div>
                </div>
              </div>

              <div v-else>
                <div class="flex! items-end! gap-2! mb-1.5!">
                  <span class="text-3xl! font-black!">{{ stats.percentage }}%</span>
                  <span class="text-blue-100! mb-0.5! text-xs! font-bold!">Selesai</span>
                </div>
                <div class="w-full! h-1.5! bg-white/20! rounded-full! mb-4!">
                  <div
                    :style="{ width: stats.percentage + '%' }"
                    class="h-full! bg-white! rounded-full! shadow-[0_0_15px_rgba(255,255,255,0.5)]!"
                  ></div>
                </div>
                <div class="grid! grid-cols-2! gap-3!">
                  <div class="bg-white/10! border! border-white/10! rounded-xl! p-2.5!">
                    <p
                      class="text-blue-100! text-[9px]! font-black! uppercase! tracking-wider! mb-1!"
                    >
                      Total Pelanggan
                    </p>
                    <p class="text-xl! font-black!">{{ stats.total }}</p>
                  </div>
                  <div class="bg-white/10! border! border-white/10! rounded-xl! p-2.5!">
                    <p
                      class="text-blue-100! text-[9px]! font-black! uppercase! tracking-wider! mb-1!"
                    >
                      Belum Dicatat
                    </p>
                    <p class="text-xl! font-black!">{{ stats.pending }}</p>
                  </div>
                </div>
              </div>
            </div>
            <!-- Decorative circle -->
            <div class="absolute! -right-10! -top-10! w-40! h-40! bg-white/10! rounded-full!"></div>
          </div>

          <ContentCard
            variant="bordered"
            padding="small"
            rounded="2xl"
            hoverable
            class="shadow-lg! shadow-slate-200/40! flex-1!"
          >
            <h4
              class="text-xs! font-black! text-slate-800! uppercase! tracking-widest! mb-3! flex! items-center! gap-2!"
            >
              <font-awesome-icon icon="lightbulb" class="text-amber-500!" />
              Tips Operasional
            </h4>
            <ul class="space-y-2!">
              <li v-for="(tip, i) in tips" :key="i" class="flex! gap-2.5!">
                <div
                  class="w-5! h-5! rounded-full! bg-slate-100! text-slate-500! flex! items-center! justify-center! text-[9px]! font-black! shrink-0!"
                >
                  {{ i + 1 }}
                </div>
                <p class="text-[11px]! text-slate-500! leading-tight! font-medium!">
                  {{ tip }}
                </p>
              </li>
            </ul>
          </ContentCard>
        </div>
      </div>
    </div>

    <!-- Mobile Layout -->
    <div class="lg:hidden! flex! flex-col! gap-6! pb-24!">
      <div class="header! px-2!">
        <h1 class="text-3xl! font-black! text-slate-800! tracking-tight! mb-1!">
          Input <span class="text-blue-600!">Meter</span>
        </h1>
        <p class="text-slate-500! font-bold! text-sm!">Pencatatan Air Bulanan</p>
      </div>

      <!-- Mobile Progress Card -->
      <div
        class="bg-gradient-to-br from-blue-600 to-indigo-700! rounded-2xl! p-4! text-white! shadow-lg! shadow-blue-100!"
      >
        <div v-if="stats.loading" class="animate-pulse!">
          <div class="h-4! w-32! bg-white/20! rounded! mb-4!"></div>
          <div class="h-10! w-20! bg-white/20! rounded! mb-4!"></div>
          <div class="h-2! w-full! bg-white/20! rounded-full!"></div>
        </div>
        <div v-else>
          <div class="flex! items-end! gap-3! mb-4!">
            <div class="flex! flex-col!">
              <span class="text-blue-100! text-[9px]! font-black! uppercase! tracking-widest! mb-1!"
                >Pencatatan</span
              >
              <span class="text-3xl! font-black!">{{ stats.percentage }}%</span>
            </div>
            <div class="h-10! w-px! bg-white/20! mx-2!"></div>
            <div class="flex! flex-col! gap-1!">
              <span
                class="bg-white/20! px-2! py-0.5! rounded-lg! text-[9px]! font-black! flex! items-center! gap-1.5!"
              >
                <div class="w-1.5! h-1.5! rounded-full! bg-white!"></div>
                {{ stats.total }} Total
              </span>
              <span
                class="bg-white/10! px-2! py-0.5! rounded-lg! text-[9px]! font-black! flex! items-center! gap-1.5!"
              >
                <div class="w-1.5! h-1.5! rounded-full! bg-blue-300!"></div>
                {{ stats.pending }} Sisa
              </span>
            </div>
          </div>
          <div class="w-full! h-1.5! bg-white/20! rounded-full!">
            <div
              :style="{ width: stats.percentage + '%' }"
              class="h-full! bg-white! rounded-full!"
            ></div>
          </div>
        </div>
      </div>

      <div class="flex! flex-col! gap-6! px-2!">
        <div class="form-group!">
          <label
            class="block! text-[10px]! font-black! text-slate-400! uppercase! tracking-widest! mb-2!"
            >Petugas Pencatat</label
          >
          <div class="relative!">
            <input
              type="text"
              :value="staffName"
              readonly
              class="w-full! bg-slate-100! border-none! rounded-2xl! px-5! py-4! text-sm! font-bold! text-slate-700!"
            />
            <div class="absolute! right-5! top-1/2! -translate-y-1/2! text-slate-400!">
              <font-awesome-icon icon="user-circle" />
            </div>
          </div>
        </div>

        <div class="grid! grid-cols-2! gap-4!">
          <div class="form-group!">
            <label
              class="block! text-[10px]! font-black! text-slate-400! uppercase! tracking-widest! mb-2!"
              >Tahun</label
            >
            <SelectSearch
              v-model="form.tahun"
              :options="tahunOptions"
              no-margin
              class="rounded-2xl!"
            />
          </div>
          <div class="form-group!">
            <label
              class="block! text-[10px]! font-black! text-slate-400! uppercase! tracking-widest! mb-2!"
              >Bulan</label
            >
            <SelectSearch
              v-model="form.bulan"
              :options="bulanOptions"
              no-margin
              class="rounded-2xl!"
            />
          </div>
        </div>

        <div class="form-group!">
          <label
            class="block! text-[10px]! font-black! text-slate-400! uppercase! tracking-widest! mb-3!"
            >Pilih Periode Cepat</label
          >
          <div class="grid! grid-cols-3! gap-2!">
            <button
              v-for="m in bulanGrid"
              :key="m.value"
              @click="form.bulan = m.value"
              :class="[
                'py-3.5! rounded-xl! text-xs! font-black! transition-all! duration-300!',
                form.bulan === m.value
                  ? 'bg-blue-600! text-white! shadow-lg! shadow-blue-200!'
                  : 'bg-white! text-slate-500! border-2! border-slate-50!',
              ]"
            >
              {{ m.label }}
            </button>
          </div>
        </div>

        <BaseButton
          :to="{
            path: '/app/instalasi/pemakaian-air/input',
            query: { tahun: form.tahun, bulan: form.bulan },
          }"
          variant="primary-gradient"
          size="lg"
          block
          icon="arrow-right"
          class="rounded-2xl! py-5! font-black! text-base! shadow-xl! shadow-blue-200! mt-4!"
        >
          MULAI PENCATATAN
        </BaseButton>

        <BaseButton
          @click="openResultsModal"
          variant="success-gradient"
          size="lg"
          block
          icon="file-alt"
          class="rounded-2xl! py-5! font-black! text-base! mt-3! shadow-lg! shadow-emerald-200/50!"
        >
          HASIL INPUT
        </BaseButton>

        <div class="bg-amber-50! border-2! border-amber-100! rounded-3xl! p-6! mt-4!">
          <div class="flex! items-start! gap-4!">
            <div
              class="w-10! h-10! bg-amber-100! text-amber-600! rounded-xl! flex! items-center! justify-center! shrink-0!"
            >
              <font-awesome-icon icon="exclamation-triangle" />
            </div>
            <div>
              <h4 class="text-amber-800! font-black! text-sm! mb-1! uppercase! tracking-tight!">
                PENTING!
              </h4>
              <p class="text-amber-700/80! text-xs! leading-relaxed! font-bold!">
                Pastikan periode <span class="text-amber-900!">Bulan & Tahun</span> sudah sesuai
                dengan jadwal pencatatan Anda.
              </p>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Hasil Input Modal -->
    <Teleport to="body">
      <Transition name="modal-fade">
        <div
          v-if="showResultsModal"
          class="fixed inset-0! z-50 flex items-center justify-center p-4! md:p-8!"
        >
          <div
            class="absolute inset-0! bg-slate-900/60! backdrop-blur-sm!"
            @click="closeResultsModal"
          ></div>

          <div
            class="relative w-full! h-full! max-w-7xl! bg-white rounded-2xl! shadow-xl! border border-slate-200 flex flex-col overflow-hidden animate-slide-up"
          >
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
                    Periode: {{ form.bulan }} {{ form.tahun }}
                  </p>
                </div>
              </div>
              <button
                @click="closeResultsModal"
                class="w-9! h-9! hover:bg-slate-100! flex items-center! justify-center! text-slate-400! hover:text-slate-600! transition-all active:scale-95 rounded-md!"
              >
                <font-awesome-icon icon="times" />
              </button>
            </div>

            <div
              class="px-6! py-4! bg-slate-50/50! border-b! border-slate-100! flex flex-col! md:flex-row! md:items-center! justify-between! gap-4!"
            >
              <div class="flex flex-row! flex-wrap! items-center! gap-x-8! gap-y-1!">
                <div class="flex items-center! gap-2! text-sm!">
                  <span class="text-slate-500! whitespace-nowrap!">Cater</span>
                  <span class="font-semibold! text-slate-700!">: {{ staffName }}</span>
                </div>
                <div class="flex items-center! gap-2! text-sm!">
                  <span class="text-slate-500! whitespace-nowrap!">Maksimal Bayar</span>
                  <span class="font-semibold! text-slate-700!">: {{ maksimalBayar }}</span>
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
                  placeholder="Cari ..."
                  class="block w-full pl-9! pr-4! py-2! bg-white border border-slate-200 rounded-lg! text-sm! focus:outline-none focus:ring-2 focus:ring-cyan-500/20 focus:border-cyan-500 transition-all shadow-sm!"
                />
              </div>
            </div>

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
                  <template v-if="loadingResults">
                    <tr>
                      <td colspan="10" class="py-16! text-center!">
                        <font-awesome-icon
                          icon="spinner"
                          spin
                          class="text-3xl! text-blue-500! mb-3!"
                        />
                        <p class="text-sm! font-bold! text-slate-500!">
                          Memuat data hasil pencatatan...
                        </p>
                      </td>
                    </tr>
                  </template>
                  <template v-else-if="filteredResultsData.length === 0">
                    <tr>
                      <td colspan="10" class="py-16! text-center!">
                        <div
                          class="w-16! h-16! bg-slate-100! text-slate-400! rounded-full! flex! items-center! justify-center! mx-auto! mb-4! text-2xl!"
                        >
                          <font-awesome-icon icon="frown" />
                        </div>
                        <h4 class="text-base! font-black! text-slate-700! mb-1!">Belum Ada Data</h4>
                        <p class="text-xs! text-slate-400! font-bold!">
                          Tidak ada data meteran yang tercatat untuk periode ini.
                        </p>
                      </td>
                    </tr>
                  </template>
                  <template v-else v-for="(members, dusun) in groupedResultsData" :key="dusun">
                    <tr class="bg-slate-100/80! border-y! border-slate-200!">
                      <td colspan="10" class="py-2.5! px-4! font-bold text-slate-700!">
                        Dusun : {{ dusun }}
                      </td>
                    </tr>

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
                      <td class="py-3! px-4! text-center! text-slate-600!">
                        {{ item.meterAwal }}
                      </td>
                      <td class="py-3! px-4! text-center! text-slate-600! font-semibold!">
                        {{ item.meterAkhir }}
                      </td>
                      <td class="py-3! px-4! text-center! text-slate-600!">
                        {{ item.pemakaian }}
                      </td>
                      <td class="py-3! px-4! text-right font-mono font-semibold text-slate-900!">
                        Rp.
                        {{
                          Number(item.tagihan || 0).toLocaleString('id-ID', {
                            minimumFractionDigits: 0,
                            maximumFractionDigits: 0,
                          })
                        }}
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

            <div
              class="px-6! py-4! bg-slate-50! border-t! border-slate-200! flex justify-end items-center gap-3! min-h-[80px]!"
            >
              <button
                @click="closeResultsModal"
                class="flex items-center! gap-2! bg-white! border! border-slate-300! hover:bg-slate-100! text-slate-700! px-6! py-2.5! font-semibold! transition-all active:scale-95 rounded-lg! shadow-sm!"
              >
                <font-awesome-icon icon="times" />
                Tutup
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
  </div>
</template>

<script setup>
import { reactive, computed, onMounted, watch, ref } from 'vue'
import { useUiStore } from '@/stores/uiStore'
import { useRouter } from 'vue-router'
import api from '@/utils/axios'
import { meterService } from '@/services/meter.service'
import ContentCard from '@/presentations/components/ui/ContentCard.vue'
import BaseButton from '@/presentations/components/ui/BaseButton.vue'
import SelectSearch from '@/presentations/components/SelectSearch.vue'

const uiStore = useUiStore()
const router = useRouter()

const now = new Date()
const currentYear = now.getFullYear().toString()
const monthNames = [
  'Jan',
  'Feb',
  'Mar',
  'Apr',
  'Mei',
  'Jun',
  'Jul',
  'Ags',
  'Sep',
  'Okt',
  'Nov',
  'Des',
]
const currentMonth = monthNames[now.getMonth()]

const stats = reactive({
  total: 0,
  done: 0,
  pending: 0,
  percentage: 0,
  loading: true,
})

const showResultsModal = ref(false)
const loadingResults = ref(false)
const resultsData = ref([])
const searchQuery = ref('')
const selectedIds = ref([])

const monthToNumber = (name) => {
  return monthNames.indexOf(name) + 1
}

const form = reactive({
  tahun: currentYear,
  bulan: currentMonth,
})

// Ambil data dari Backend (Mengikuti struktur backend yang ada)
const fetchStats = async () => {
  try {
    stats.loading = true
    const res = await meterService.getPendingReadings({
      month: monthToNumber(form.bulan),
      year: form.tahun,
    })

    if (res && res.data) {
      stats.pending = Array.isArray(res.data) ? res.data.length : 0
      stats.total = res.total_customers || 0
      stats.done = stats.total - stats.pending
      stats.percentage = stats.total > 0 ? Math.round((stats.done / stats.total) * 100) : 0
    }
  } catch (error) {
    console.error('Gagal mengambil data pending:', error)
  } finally {
    stats.loading = false
  }
}

const openResultsModal = async () => {
  showResultsModal.value = true
  await fetchResults()
}

const closeResultsModal = () => {
  showResultsModal.value = false
}

const handleCetakFormInput = () => {
  const url = router.resolve({
    name: 'Cetak Input',
    query: {
      tahun: form.tahun,
      bulan: form.bulan,
    },
  }).href
  window.open(url, '_blank')
}

const fetchResults = async () => {
  try {
    loadingResults.value = true
    const res = await api.get('/meter-readings/completed', {
      params: {
        month: monthToNumber(form.bulan),
        year: form.tahun,
      },
    })
    if (res.data && res.data.success) {
      resultsData.value = res.data.data
    }
  } catch (error) {
    console.error('Gagal mengambil data hasil input:', error)
  } finally {
    loadingResults.value = false
  }
}

const filteredResultsData = computed(() => {
  if (!searchQuery.value) return resultsData.value

  const query = searchQuery.value.toLowerCase()
  return resultsData.value.filter(
    (item) =>
      item.customer?.user?.name?.toLowerCase().includes(query) ||
      item.customer?.id?.toString().toLowerCase().includes(query),
  )
})

const groupedResultsData = computed(() => {
  const grouped = {}
  filteredResultsData.value.forEach((item) => {
    const dusun = item.customer?.ticket?.village?.name || 'Lainnya'
    if (!grouped[dusun]) {
      grouped[dusun] = []
    }
    grouped[dusun].push({
      id: item.customer?.id || '-',
      nama: item.customer?.user?.name || '-',
      desa: item.customer?.ticket?.village?.name || '-',
      rt: item.customer?.ticket?.rt || '-',
      meterAwal: item.previous_reading || 0,
      meterAkhir: item.meter_value || 0,
      pemakaian: (item.meter_value || 0) - (item.previous_reading || 0),
      tagihan: item.bill_amount || 0,
      status: item.payment_status || 'UNPAID',
    })
  })
  return grouped
})

const allVisibleIds = computed(() => {
  return filteredResultsData.value.map((item) => item.customer?.id).filter(Boolean)
})

const isAllSelected = computed({
  get: () =>
    allVisibleIds.value.length > 0 &&
    allVisibleIds.value.every((id) => selectedIds.value.includes(id)),
  set: (val) => {
    if (val) {
      const newSelections = new Set([...selectedIds.value, ...allVisibleIds.value])
      selectedIds.value = Array.from(newSelections)
    } else {
      selectedIds.value = selectedIds.value.filter((id) => !allVisibleIds.value.includes(id))
    }
  },
})

watch(
  () => [form.bulan, form.tahun],
  () => {
    fetchStats()
    if (showResultsModal.value) {
      fetchResults()
    }
  },
)

onMounted(() => {
  fetchStats()
})

const staffName = computed(() => uiStore.userData?.name || 'Petugas Lapangan')

const maksimalBayar = computed(() => {
  // Maksimal bayar adalah tanggal 5 bulan berikutnya dari periode pencatatan
  const monthIndex = monthToNumber(form.bulan)
  const year = parseInt(form.tahun)

  // Jika bulan Desember, maka tahun depan Januari
  let nextMonth = monthIndex + 1
  let nextYear = year
  if (nextMonth > 12) {
    nextMonth = 1
    nextYear = year + 1
  }

  return `5/${String(nextMonth).padStart(2, '0')}/${nextYear}`
})

const tahunOptions = [
  { id: '2024', text: '2024' },
  { id: '2025', text: '2025' },
  { id: '2026', text: '2026' },
]

const bulanOptions = [
  { id: 'Jan', text: 'Januari' },
  { id: 'Feb', text: 'Februari' },
  { id: 'Mar', text: 'Maret' },
  { id: 'Apr', text: 'April' },
  { id: 'Mei', text: 'Mei' },
  { id: 'Jun', text: 'Juni' },
  { id: 'Jul', text: 'Juli' },
  { id: 'Ags', text: 'Agustus' },
  { id: 'Sep', text: 'September' },
  { id: 'Okt', text: 'Oktober' },
  { id: 'Nov', text: 'November' },
  { id: 'Des', text: 'Desember' },
]

const bulanGrid = [
  { label: 'JAN', value: 'Jan' },
  { label: 'FEB', value: 'Feb' },
  { label: 'MAR', value: 'Mar' },
  { label: 'APR', value: 'Apr' },
  { label: 'MEI', value: 'Mei' },
  { label: 'JUN', value: 'Jun' },
  { label: 'JUL', value: 'Jul' },
  { label: 'AGS', value: 'Ags' },
  { label: 'SEP', value: 'Sep' },
  { label: 'OKT', value: 'Okt' },
  { label: 'NOV', value: 'Nov' },
  { label: 'DES', value: 'Des' },
]

const tips = [
  'Gunakan pencahayaan yang cukup saat mengambil foto meteran.',
  'Pastikan angka meteran terlihat jelas dan tidak buram.',
  'Segera laporkan jika menemukan meteran yang rusak atau segel terbuka.',
  'Data yang sudah disimpan akan diverifikasi oleh Admin Pusat.',
]
</script>

<style scoped>
.cater-pemakaian-root {
  -webkit-font-smoothing: antialiased;
  -moz-osx-font-smoothing: grayscale;
  animation: fadeIn 0.8s cubic-bezier(0.16, 1, 0.3, 1);
}

@keyframes fadeIn {
  from {
    opacity: 0;
    transform: translateY(10px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}

.shadow-blue-200 {
  --tw-shadow-color: rgba(37, 99, 235, 0.15);
}

input:read-only {
  cursor: default;
  background-color: rgba(248, 250, 252, 0.5);
}

@media (max-width: 1024px) {
  :deep(.custom-select-search .select-display) {
    height: 3.5rem !important;
    border-radius: 1rem !important;
    border: 2px solid #f1f5f9 !important;
    background-color: #f8fafc !important;
    font-weight: 700 !important;
    color: rgb(51, 65, 85) !important;
    box-shadow:
      0 10px 15px -3px rgba(148, 163, 184, 0.12),
      0 4px 6px -2px rgba(148, 163, 184, 0.06) !important;
  }
}

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
