<template>
  <Teleport to="body">
    <Transition name="abt-modal">
      <div v-if="show" class="abt-overlay" @click.self="closeModal">
        <div class="abt-modal" role="dialog" aria-modal="true" aria-labelledby="abt-modal-title">
          <div class="abt-modal__accent"></div>

          <!-- ===== HEADER (brand) ===== -->
          <div class="abt-modal__header">
            <button class="abt-close" type="button" aria-label="Tutup" @click="closeModal">
              <font-awesome-icon icon="times" />
            </button>

            <div class="abt-modal__brand">
              <div class="abt-modal__mark">
                <font-awesome-icon icon="gem" />
              </div>
              <div class="abt-modal__identity">
                <p class="abt-modal__kicker">Pemberitahuan</p>
                <h2 id="abt-modal-title" class="abt-modal__title">Tentang Aplikasi Ini</h2>
                <p class="abt-modal__subtitle">
                  Dikembangkan oleh <strong>Asta Brata Teknologi</strong>
                </p>
              </div>
            </div>
          </div>

          <!-- ===== BODY ===== -->
          <div class="abt-modal__body">
            <div class="abt-modal__row">
              <div class="abt-modal__col">
                <div class="abt-lead">
                  <p class="abt-modal__lead">
                    Aplikasi ini adalah karya
                    <span class="abt-hl">Asta Brata Teknologi</span>. Bukan sekadar perangkat lunak
                    yang dibangun untuk cepat selesai, melainkan sebuah sistem yang dirancang dengan
                    <span class="abt-hl">standar profesional</span> — disusun dengan presisi, diuji
                    secara menyeluruh, dan dirawat dengan konsisten.
                  </p>
                </div>

                <p class="abt-modal__para">
                  Dari layar pertama hingga transaksi terakhir, setiap detail kami perlakukan dengan
                  serius: kecepatan, keakuratan data, hingga kenyamanan bekerja. Karena bagi kami,
                  teknologi terbaik bukan yang paling ramai — melainkan yang paling dapat
                  diandalkan.
                </p>
              </div>

              <div class="abt-modal__col">
                <ul class="abt-points">
                  <li v-for="point in highlights" :key="point.title" class="abt-point">
                    <div class="abt-point__icon">
                      <font-awesome-icon :icon="point.icon" />
                    </div>
                    <div>
                      <p class="abt-point__title">{{ point.title }}</p>
                      <p class="abt-point__text">{{ point.text }}</p>
                    </div>
                  </li>
                </ul>
              </div>
            </div>

            <!-- Kutipan membentang penuh di bawah dua kolom -->
            <div class="abt-quote">
              <font-awesome-icon icon="quote-left" class="abt-quote__mark" />
              <div class="abt-quote__body">
                <p class="abt-quote__text">
                  Setiap hari, sistem ini bekerja tanpa banyak bicara — persis seperti yang
                  seharusnya sebuah sistem yang baik.
                </p>
                <p class="abt-quote__author">Asta Brata Teknologi</p>
              </div>
            </div>
          </div>

          <!-- ===== FOOTER ===== -->
          <div class="abt-modal__footer">
            <div class="abt-modal__meta">
              <font-awesome-icon icon="shield-halved" />
              <span>PAMSIDES · Sistem Manajemen Air Bersih</span>
            </div>
            <BaseButton variant="info-gradient" class="abt-modal__action" @click="closeModal">
              Mengerti, Terima Kasih
            </BaseButton>
          </div>
        </div>
      </div>
    </Transition>
  </Teleport>
</template>

<script setup>
import { watch, onBeforeUnmount } from 'vue'
import { useUiStore } from '@/stores/uiStore'
import BaseButton from './BaseButton.vue'

const props = defineProps({
  show: {
    type: Boolean,
    default: false,
  },
})

const emit = defineEmits(['close'])

const uiStore = useUiStore()

let previousOverflow = ''

const highlights = [
  {
    icon: 'code',
    title: 'Standar Profesional',
    text: 'Dikembangkan mengikuti praktik rekayasa perangkat lunak terbaik: arsitektur yang tertata, kode yang terstruktur, dan mudah dirawat.',
  },
  {
    icon: 'shield-halved',
    title: 'Kualitas Teruji',
    text: 'Setiap fitur melewati pengujian berlapis sebelum diperkenalkan kepada pengguna.',
  },
  {
    icon: 'rocket',
    title: 'Terus Berkembang',
    text: 'Aplikasi diperbarui secara berkala agar selalu relevan, aman, dan siap dipakai.',
  },
]

// Kunci scroll saat popup terbuka dan pulihkan nilai sebelumnya saat ditutup,
// supaya modal lain yang sedang terbuka (PriceDetailModal, CameraModal, dll.)
// tidak ikut kehilangan kunci scroll-nya.
let scrollLocked = false

const lockScroll = () => {
  if (scrollLocked) return
  scrollLocked = true
  previousOverflow = document.body.style.overflow
  document.body.style.overflow = 'hidden'
}

const unlockScroll = () => {
  if (!scrollLocked) return
  scrollLocked = false
  document.body.style.overflow = previousOverflow
  previousOverflow = ''
}

// Penanda apakah popup ini yang menambah activeModalCount, supaya balances
// dengan openModal()/closeModal() pada uiStore tetap konsisten.
let modalCounted = false

const closeModal = () => emit('close')

const handleEscKey = (event) => {
  if (event.key === 'Escape' && props.show) closeModal()
}

watch(
  () => props.show,
  (isShown) => {
    if (isShown) {
      lockScroll()
      document.addEventListener('keydown', handleEscKey)
      if (!modalCounted) {
        uiStore.openModal()
        modalCounted = true
      }
    } else {
      unlockScroll()
      document.removeEventListener('keydown', handleEscKey)
      if (modalCounted) {
        uiStore.closeModal()
        modalCounted = false
      }
    }
  },
)

onBeforeUnmount(() => {
  if (!props.show) return
  unlockScroll()
  document.removeEventListener('keydown', handleEscKey)
  if (modalCounted) {
    uiStore.closeModal()
    modalCounted = false
  }
})
</script>

<style scoped>
@reference "@/assets/css/main.css";

/* ===== Overlay ===== */
.abt-overlay {
  @apply fixed inset-0 z-[3000] flex items-center justify-center p-3 sm:p-6;
  background: rgba(15, 23, 42, 0.55);
  backdrop-filter: blur(8px);
  -webkit-backdrop-filter: blur(8px);
}

/* ===== Shell ===== */
.abt-modal {
  @apply relative flex w-full max-w-4xl flex-col overflow-hidden bg-white;
  max-height: 92vh;
  border-radius: 1.5rem;
  border: 1px solid rgba(255, 255, 255, 0.7);
  box-shadow:
    0 32px 80px -16px rgba(15, 23, 42, 0.35),
    0 0 0 1px rgba(255, 255, 255, 0.5) inset;
}

.abt-modal__accent {
  flex-shrink: 0;
  height: 5px;
  background: linear-gradient(90deg, #22d3ee 0%, #3b82f6 50%, #6366f1 100%);
}

/* ===== Header ===== */
.abt-modal__header {
  @apply relative flex-shrink-0 overflow-hidden px-5 pb-6 pt-5 sm:px-7 sm:pb-7 sm:pt-6;
  background: linear-gradient(135deg, #0891b2 0%, #0e7490 52%, #4338ca 100%);
}

.abt-modal__header::after {
  content: '';
  position: absolute;
  inset: 0;
  pointer-events: none;
  background:
    radial-gradient(circle at 88% 4%, rgba(255, 255, 255, 0.22), transparent 42%),
    radial-gradient(circle at 4% 98%, rgba(255, 255, 255, 0.14), transparent 46%);
}

.abt-modal__header > * {
  position: relative;
  z-index: 1;
}

.abt-modal__brand {
  @apply flex items-center gap-4 pr-10;
}

.abt-modal__mark {
  @apply flex h-14 w-14 flex-shrink-0 items-center justify-center rounded-2xl text-white;
  background: rgba(255, 255, 255, 0.16);
  border: 1px solid rgba(255, 255, 255, 0.28);
  box-shadow:
    0 12px 28px -10px rgba(2, 44, 66, 0.6),
    inset 0 1px 0 rgba(255, 255, 255, 0.3);
  font-size: 1.35rem;
}

.abt-modal__identity {
  @apply min-w-0;
}

.abt-modal__kicker {
  @apply mb-1 text-[10px] font-bold uppercase;
  letter-spacing: 0.32em;
  color: rgba(224, 242, 254, 0.85);
}

.abt-modal__title {
  @apply text-xl font-black leading-tight text-white sm:text-2xl;
  letter-spacing: -0.02em;
}

.abt-modal__subtitle {
  @apply mt-1 text-xs leading-snug;
  color: rgba(207, 250, 254, 0.92);
}

.abt-modal__subtitle strong {
  @apply font-bold text-white;
}

.abt-close {
  @apply absolute right-4 top-4 flex h-9 w-9 items-center justify-center rounded-xl text-white/80 transition-all duration-300;
  background: rgba(255, 255, 255, 0.14);
  border: 1px solid rgba(255, 255, 255, 0.22);
}

.abt-close:hover {
  @apply bg-white/25 text-white;
  transform: rotate(90deg);
}

/* ===== Body =====
   Dua kolom untuk bagian atas (narasi | kartu poin), lalu kutipan
   membentang penuh selebar popup di bawahnya.

   `shrink-0` WAJIB ada di kedua anaknya: body ini flex column, dan
   flex item default-nya `flex-shrink: 1`. Tanpa itu, saat total konten
   melebihi tinggi body, browser memampatkan baris + kutipan supaya muat
   — kutipan jadi pita tipis dan teksnya meluber keluar dari kotak biru. */
.abt-modal__body {
  @apply flex flex-1 flex-col gap-6 overflow-y-auto px-5 py-6 sm:px-7 lg:gap-7;
}

.abt-modal__row {
  @apply grid shrink-0 gap-6 lg:grid-cols-2 lg:gap-8;
}

.abt-modal__col {
  @apply flex flex-col gap-4;
}

.abt-lead {
  @apply relative rounded-2xl border border-slate-100 bg-slate-50 px-4 py-4 sm:px-5;
}

.abt-lead::before {
  content: '';
  position: absolute;
  left: 0;
  top: 0.75rem;
  bottom: 0.75rem;
  width: 4px;
  border-radius: 9999px;
  background: linear-gradient(180deg, #22d3ee 0%, #6366f1 100%);
}

.abt-modal__lead {
  @apply text-[0.9375rem] leading-[1.75] text-slate-700;
}

.abt-hl {
  @apply font-bold text-slate-900;
}

.abt-modal__para {
  @apply text-[0.875rem] leading-[1.75] text-slate-600;
}

/* ===== Highlight cards =====
   Di dalam kolom kanan kartu ditumpuk vertikal; di layar sempit (1 kolom)
   tetap boleh mendatar seperti semula. */
.abt-points {
  @apply grid list-none gap-3 p-0;
}

.abt-point {
  @apply flex items-start gap-3 rounded-2xl border border-slate-100 bg-slate-50/70 p-4 transition-all duration-300;
}

.abt-point:hover {
  @apply -translate-y-0.5 border-sky-200 bg-white;
  box-shadow: 0 12px 24px -12px rgba(14, 165, 233, 0.45);
}

.abt-point__icon {
  @apply flex h-9 w-9 flex-shrink-0 items-center justify-center rounded-xl text-sky-600;
  background: #e0f2fe;
}

.abt-point__title {
  @apply mb-1 text-[0.8125rem] font-bold text-slate-800;
}

.abt-point__text {
  @apply text-xs leading-[1.6] text-slate-500;
}

/* ===== Quote =====
   Sekarang blok selebar popup, jadi ditata mendatar: ikon di kiri,
   teks + nama di kanan. Pada layar sempit kembali tersusun vertikal.

   `shrink-0` + `flex-none` pada ikon mencegah elemen ini ikut terjepit
   di dalam body yang sedang scroll. */
.abt-quote {
  @apply relative flex shrink-0 flex-col gap-3 overflow-hidden rounded-2xl px-6 py-5 text-white sm:flex-row sm:items-center sm:gap-5 sm:px-7;
  background: linear-gradient(135deg, #0891b2 0%, #2563eb 100%);
  box-shadow: 0 18px 34px -18px rgba(37, 99, 235, 0.75);
}

.abt-quote::after {
  content: '';
  position: absolute;
  right: -2.5rem;
  top: -3.5rem;
  height: 11rem;
  width: 11rem;
  border-radius: 9999px;
  background: rgba(255, 255, 255, 0.07);
}

.abt-quote::before {
  content: '';
  position: absolute;
  right: 6rem;
  bottom: -4rem;
  height: 7rem;
  width: 7rem;
  border-radius: 9999px;
  background: rgba(255, 255, 255, 0.05);
}

.abt-quote > * {
  position: relative;
  z-index: 1;
}

.abt-quote__mark {
  @apply flex-none text-2xl;
  color: rgba(255, 255, 255, 0.5);
}

.abt-quote__body {
  @apply min-w-0 flex-1;
}

.abt-quote__text {
  @apply text-[0.9375rem] font-medium leading-[1.7];
}

.abt-quote__author {
  @apply mt-2 block text-[11px] font-bold uppercase;
  letter-spacing: 0.16em;
  color: rgba(207, 250, 254, 0.9);
}

/* ===== Footer ===== */
.abt-modal__footer {
  @apply flex flex-shrink-0 flex-col gap-3 border-t border-slate-100 bg-slate-50 px-5 py-4 sm:flex-row sm:items-center sm:justify-between sm:px-7;
}

.abt-modal__meta {
  @apply flex items-center gap-2 text-[11px] font-medium;
  color: var(--color-text-sub);
}

.abt-modal__meta svg {
  @apply text-sky-500;
}

.abt-modal__action {
  @apply w-full sm:w-auto;
  border-radius: 9999px;
  padding: 0.65rem 1.5rem;
  font-size: var(--text-xs);
  font-weight: 700;
}

/* ===== Transition ===== */
.abt-modal-enter-active,
.abt-modal-leave-active {
  transition: opacity 0.28s ease;
}

.abt-modal-enter-active .abt-modal,
.abt-modal-leave-active .abt-modal {
  transition:
    transform 0.35s cubic-bezier(0.19, 1, 0.22, 1),
    opacity 0.28s ease;
}

.abt-modal-enter-from,
.abt-modal-leave-to {
  opacity: 0;
}

.abt-modal-enter-from .abt-modal,
.abt-modal-leave-to .abt-modal {
  transform: translateY(26px) scale(0.96);
  opacity: 0;
}

/* ===== Responsive ===== */
/* Di bawah `lg` body turun ke satu kolom. Kartu poin kembali mendatar
   hanya di tablet ke atas; di ponsel tetap ditumpuk agar tidak sempit. */
@media (min-width: 640px) and (max-width: 1023px) {
  .abt-points {
    @apply sm:grid-cols-3;
  }

  .abt-point {
    @apply flex-col gap-0;
  }

  .abt-point__icon {
    @apply mb-3;
  }
}

@media (max-width: 640px) {
  .abt-modal {
    border-radius: 1.5rem 1.5rem 0 0;
  }

  .abt-modal__brand {
    @apply gap-3;
  }

  .abt-modal__mark {
    @apply h-12 w-12 rounded-xl;
    font-size: 1.1rem;
  }
}
</style>
