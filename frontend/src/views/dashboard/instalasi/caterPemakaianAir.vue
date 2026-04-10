<template>
  <div class="cater-pemakaian-root p-4! lg:p-6!">
    <!-- DESKTOP LAYOUT (lg and up) -->
    <div class="hidden! lg:block! max-w-7xl mx-auto">
      <div class="flex! justify-between! items-start! mb-8!">
        <div>
          <h1 class="text-3xl! font-extrabold! text-slate-800! tracking-tight!">
            Input Data Pemakaian
          </h1>
          <p class="text-slate-500! mt-1!">
            Silakan lengkapi formulir di bawah ini untuk mencatat penggunaan air bulanan pelanggan
            dengan presisi.
          </p>
        </div>
        <div class="flex! gap-3!">
          <BaseButton variant="success-gradient" icon="file-alt" class="shadow-sm!"
            >Hasil Input</BaseButton
          >
          <BaseButton variant="warning-gradient" icon="print" class="shadow-sm!"
            >Cetak Form</BaseButton
          >
        </div>
      </div>

      <div class="grid! grid-cols-12! gap-6!">
        <!-- Main Form Column -->
        <div class="col-span-8!">
          <ContentCard variant="elevated" padding="large" rounded="2xl" class="h-full! shadow-xl!">
            <div class="grid! grid-cols-2! gap-6! mb-6!">
              <div class="form-group!">
                <label class="block! text-sm! font-bold! text-slate-700! mb-2!"
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
                <label class="block! text-sm! font-bold! text-slate-700! mb-2!"
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

            <div class="form-group! mb-8!">
              <label class="block! text-sm! font-bold! text-slate-700! mb-2!"
                >Petugas Cater (Staff)</label
              >
              <div class="relative!">
                <input
                  type="text"
                  v-model="form.staff"
                  readonly
                  class="w-full! bg-slate-50! border! border-slate-200! rounded-xl! px-4! py-3! text-sm! font-bold! text-blue-800! tracking-wider! uppercase! focus:outline-none!"
                />
                <div class="absolute! right-4! top-1/2! -translate-y-1/2! text-blue-600!">
                  <font-awesome-icon icon="shield-halved" />
                </div>
              </div>
            </div>

            <BaseButton
              variant="info-gradient"
              size="lg"
              block
              icon="save"
              class="rounded-xl! shadow-lg! shadow-indigo-200!"
            >
              Input Pemakaian
            </BaseButton>
          </ContentCard>
        </div>

        <!-- Info Column -->
        <div class="col-span-4! flex! flex-col! gap-6!">
          <!-- Session Info Card -->
          <ContentCard variant="elevated" padding="large" rounded="2xl" class="shadow-xl!">
            <template #header>
              <div
                class="flex! items-center! gap-2! text-blue-600! font-bold! text-xs! uppercase! tracking-widest!"
              >
                <font-awesome-icon icon="info-circle" />
                <span>Informasi Sesi</span>
              </div>
            </template>
            <div class="flex! flex-col! gap-4! mt-2!">
              <div class="flex! justify-between! items-center!">
                <span class="text-slate-500! text-sm!">Status Input</span>
                <span
                  class="bg-orange-100! text-orange-600! px-3! py-1! rounded-full! text-[10px]! font-bold! uppercase! tracking-wider!"
                  >Aktif</span
                >
              </div>
              <div class="flex! justify-between! items-center! border-t! border-slate-100! pt-3!">
                <span class="text-slate-500! text-sm!">Data Masuk</span>
                <span class="text-blue-700! font-extrabold! text-lg!">128 Unit</span>
              </div>
            </div>
          </ContentCard>

          <!-- Decorative Card -->
          <div
            class="relative! overflow-hidden! rounded-3xl! h-full! min-h-[220px]! shadow-xl! group!"
          >
            <img
              src="@/assets/digital-curating.png"
              alt="Workstation"
              class="absolute! inset-0! w-full! h-full! object-cover! transition-transform! duration-700! group-hover:scale-110!"
            />
            <div
              class="absolute! inset-0! bg-gradient-to-t! from-slate-900/80! via-slate-900/40! to-transparent!"
            ></div>
            <div class="absolute! bottom-6! left-6! right-6!">
              <p class="text-white! text-sm! font-medium! opacity-90! leading-tight!">
                Digital Curating for Water Systems
              </p>
            </div>
          </div>
        </div>
      </div>

      <!-- Desktop Warning -->
      <ContentCard
        variant="elevated"
        padding="large"
        rounded="2xl"
        class="mt-8! border-l-8! border-red-500! bg-red-50/30! shadow-lg!"
      >
        <div class="flex! gap-4!">
          <div
            class="w-12! h-12! rounded-xl! bg-red-100! flex! items-center! justify-center! text-red-600! shrink-0!"
          >
            <font-awesome-icon icon="exclamation-triangle" class="text-xl!" />
          </div>
          <div class="flex-1!">
            <h3 class="text-red-800! font-extrabold! text-lg! mb-4!">PERHATIAN!</h3>
            <div class="grid! grid-cols-2! gap-8! text-slate-600! text-sm! leading-relaxed!">
              <div class="flex! gap-2!">
                <span class="font-bold! text-red-600!">1.</span>
                <p>
                  Pilih Bulan dan Tahun Pemakaian Terlebih Dahulu Sebelum Melakukan Input data
                  pemakaian.
                </p>
              </div>
              <div class="flex! gap-2!">
                <span class="font-bold! text-red-600!">2.</span>
                <p>
                  Kesalahan pada pemilihan bulan dan tahun dapat menyebabkan duplikasi atau
                  hilangnya record data lama.
                </p>
              </div>
            </div>
          </div>
        </div>
      </ContentCard>
    </div>

    <!-- MOBILE LAYOUT (Default, hidden on lg) -->
    <div class="lg:hidden! flex! flex-col! gap-6! pb-20!">
      <div class="header!">
        <h1 class="text-2xl! font-extrabold! text-blue-800! tracking-tight! leading-none! mb-1!">
          Input Data Pemakaian Air
        </h1>
        <p class="text-slate-400! italic! text-sm!">Water Management Editorial Control</p>
      </div>

      <div class="mobile-form! flex! flex-col! gap-6!">
        <!-- Staff Input -->
        <div class="form-group!">
          <label class="block! text-sm! font-bold! text-slate-600! mb-2!"
            >Petugas Pencatat (Staff)</label
          >
          <div class="relative! shadow-md! rounded-2xl!">
            <input
              type="text"
              v-model="form.staff"
              readonly
              class="w-full! bg-slate-200! border-none! rounded-2xl! px-5! py-4! text-sm! font-bold! text-slate-700! focus:ring-0!"
            />
            <div class="absolute! right-5! top-1/2! -translate-y-1/2! text-slate-500!">
              <font-awesome-icon icon="user" />
            </div>
          </div>
        </div>

        <!-- Tahun Dropdown -->
        <div class="form-group!">
          <label class="block! text-sm! font-bold! text-slate-600! mb-2!">Tahun Anggaran</label>
          <SelectSearch
            v-model="form.tahun"
            :options="tahunOptions"
            placeholder="Pilih Tahun"
            icon="calendar"
            no-margin
            class="shadow-md! rounded-2xl!"
          />
        </div>

        <!-- Bulan Grid -->
        <div class="form-group!">
          <label class="block! text-sm! font-bold! text-slate-600! mb-2!">Bulan Periode</label>
          <div class="grid! grid-cols-3! gap-2!">
            <button
              v-for="m in bulanGrid"
              :key="m.value"
              @click="form.bulan = m.value"
              :class="[
                'py-4! rounded-xl! text-sm! font-bold! transition-all! duration-300! shadow-md!',
                form.bulan === m.value
                  ? 'bg-blue-800! text-white! shadow-lg! shadow-blue-200! border-none!'
                  : 'bg-white! text-slate-500! border! border-slate-100!',
              ]"
            >
              {{ m.label }}
            </button>
          </div>
        </div>

        <!-- Mobile Buttons -->
        <div class="flex! flex-col! gap-3! mt-4!">
          <BaseButton
            variant="info-gradient"
            size="lg"
            block
            icon="save"
            class="rounded-2xl! py-5! font-extrabold! text-base! shadow-xl! shadow-blue-200!"
          >
            Quick Input Data
          </BaseButton>

          <div class="flex! gap-3! mt-6!">
            <BaseButton
              variant="secondary"
              size="lg"
              block
              icon="print"
              class="bg-slate-200! text-slate-700! border-none! rounded-2xl! py-4! shadow-lg!"
            >
              Cetak Form
            </BaseButton>
            <BaseButton
              variant="secondary"
              size="lg"
              block
              icon="history"
              class="bg-slate-200! text-slate-700! border-none! rounded-2xl! py-4! shadow-lg!"
              @click="handleHistory"
            >
              History
            </BaseButton>
          </div>
        </div>

        <div
          class="bg-red-50! border! border-red-100! rounded-2xl! p-6! mt-4! shadow-lg! relative! overflow-hidden!"
        >
          <div class="flex! items-start! gap-3! relative! z-10!">
            <font-awesome-icon icon="exclamation-triangle" class="text-red-700! mt-1!" />
            <div>
              <h4 class="text-red-700! font-extrabold! text-sm! mb-2! uppercase! tracking-tight!">
                PERHATIAN!
              </h4>
              <p class="text-slate-600! text-xs! leading-relaxed! font-medium!">
                Mohon pastikan data <span class="text-red-600! font-bold!">Tahun</span> dan
                <span class="text-red-600! font-bold!">Bulan</span> sudah benar sebelum melakukan
                input data pemakaian air pelanggan. Kesalahan input akan berdampak pada laporan
                tahunan.
              </p>
            </div>
          </div>
          <div
            class="text-[10px]! text-slate-300! font-bold! uppercase! tracking-widest! text-right! mt-4!"
          >
            System Version 2.0.4
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { reactive } from 'vue'
import ContentCard from '@/components/ui/ContentCard.vue'
import BaseButton from '@/components/ui/BaseButton.vue'
import SelectSearch from '@/components/SelectSearch.vue'

const form = reactive({
  tahun: '2023',
  bulan: 'Mar',
  staff: 'ADMINISTRATOR PRINCIPAL',
})

// Options for selection fields
const tahunOptions = [
  { id: '2023', text: '2023' },
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
  { label: 'Jan', value: 'Jan' },
  { label: 'Feb', value: 'Feb' },
  { label: 'Mar', value: 'Mar' },
  { label: 'Apr', value: 'Apr' },
  { label: 'Mei', value: 'Mei' },
  { label: 'Jun', value: 'Jun' },
  { label: 'Jul', value: 'Jul' },
  { label: 'Ags', value: 'Ags' },
  { label: 'Sep', value: 'Sep' },
  { label: 'Okt', value: 'Okt' },
  { label: 'Nov', value: 'Nov' },
  { label: 'Des', value: 'Des' },
]

const handleHistory = () => {
  // Navigation or logic here
}
</script>

<style scoped>
@reference "@/assets/main.css";

/* Any additional custom styles to fine-tune the layouts */
.cater-pemakaian-root {
  -webkit-font-smoothing: antialiased;
  -moz-osx-font-smoothing: grayscale;
}

/* Custom shadow for mobile buttons if needed */
.shadow-blue-200 {
  --tw-shadow-color: rgba(191, 219, 254, 0.4);
}

/* Specific styling for the staff input in mobile */
input:read-only {
  @apply cursor-default;
}

/* Matching SelectSearch height to input fields in mobile */
@media (max-width: 1024px) {
  :deep(.custom-select-search .select-display) {
    @apply !h-auto !py-4 !rounded-2xl;
  }
}
</style>
