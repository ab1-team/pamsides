<template>
  <div class="relative! py-2! px-0! overflow-hidden! select-none! flex! flex-col! justify-between!">
    <!-- Glowing Tech Backdrops -->
    <div class="absolute! inset-0! pointer-events-none!">
      <div
        class="absolute! -top-24! -right-12! w-96! h-96! bg-indigo-500/10! rounded-full! blur-[80px]! animate-pulse-slow!"
      ></div>
      <div
        class="absolute! bottom-12! -left-12! w-80! h-80! bg-cyan-500/10! rounded-full! blur-[80px]!"
      ></div>
    </div>

    <!-- Header Section -->
    <div class="relative! z-20! px-4! md:px-6! animate-reveal-up!">
      <div
        class="inline-flex! items-center! gap-2! bg-indigo-50/80! border! border-indigo-100! rounded-full! px-3! py-1! mb-2! backdrop-blur-xs!"
      >
        <span class="flex! h-2! w-2! relative!">
          <span
            class="animate-ping! absolute! inline-flex! h-full! w-full! rounded-full! bg-indigo-400! opacity-75!"
          ></span>
          <span class="relative! inline-flex! rounded-full! h-2! w-2! bg-indigo-500!"></span>
        </span>
        <span class="text-[9px]! font-black! text-indigo-600! uppercase! tracking-[0.15em]!"
          >Standard Operating Procedure</span
        >
      </div>

      <h1
        class="text-2xl! sm:text-3xl! md:text-4xl! font-black! text-slate-800! tracking-tight! leading-tight! mb-1!"
      >
        Selamat Datang di
        <span
          class="bg-gradient-to-r! from-indigo-600! via-blue-600! to-cyan-500! bg-clip-text! text-transparent! drop-shadow-xs!"
          >Pusat Kendali SOP</span
        >
      </h1>

      <p class="text-xs! md:text-sm! text-slate-500! font-medium! max-w-xl! leading-relaxed!">
        Sistem manajemen konfigurasi sentral untuk menyelaraskan operasional, biaya, serta siklus
        tagihan air bersih <span class="text-indigo-600! font-bold!">Tirto Mulo</span> demi
        keberlanjutan layanan.
      </p>
    </div>

    <!-- Features Overview Grid -->
    <div class="grid! grid-cols-1! md:grid-cols-3! gap-4! my-2! px-4! md:px-6! z-20! relative!">
      <!-- Card 1: Identitas Lembaga -->
      <div
        class="group! relative! bg-white! border! border-slate-100! rounded-2xl! p-4! shadow-[0_4px_20px_rgba(15,23,42,0.02)]! hover:shadow-xl! hover:shadow-indigo-100/30! hover:-translate-y-1! transition-all! duration-300!"
      >
        <div
          class="w-8! h-8! bg-indigo-50! group-hover:bg-indigo-600! text-indigo-600! group-hover:text-white! rounded-xl! flex! items-center! justify-center! shadow-sm! transition-all! duration-300! mb-2!"
        >
          <font-awesome-icon icon="building" class="text-sm!" />
        </div>
        <h3
          class="text-sm! font-bold! text-slate-800! mb-1.5! group-hover:text-indigo-600! transition-colors!"
        >
          Identitas Lembaga
        </h3>
        <p class="text-[11px]! text-slate-400! leading-relaxed! font-medium!">
          Kelola profil resmi, alamat, kontak, deskripsi, serta unggah branding logo resmi PAMSIMAS
          Anda.
        </p>
      </div>

      <!-- Card 2: Aturan Pasang Baru -->
      <div
        class="group! relative! bg-white! border! border-slate-100! rounded-2xl! p-4! shadow-[0_4px_20px_rgba(15,23,42,0.02)]! hover:shadow-xl! hover:shadow-cyan-100/30! hover:-translate-y-1! transition-all! duration-300!"
      >
        <div
          class="w-8! h-8! bg-cyan-50! group-hover:bg-cyan-500! text-cyan-600! group-hover:text-white! rounded-xl! flex! items-center! justify-center! shadow-sm! transition-all! duration-300! mb-2!"
        >
          <font-awesome-icon icon="user-plus" class="text-sm!" />
        </div>
        <h3
          class="text-sm! font-bold! text-slate-800! mb-1.5! group-hover:text-cyan-600! transition-colors!"
        >
          Aturan Pasang Baru
        </h3>
        <p class="text-[11px]! text-slate-400! leading-relaxed! font-medium!">
          Atur skema pembayaran pendaftaran pelanggan baru, baik sistem wajib lunas maupun tidak
          lunas.
        </p>
      </div>

      <!-- Card 3: Siklus Pembayaran -->
      <div
        class="group! relative! bg-white! border! border-slate-100! rounded-2xl! p-4! shadow-[0_4px_20px_rgba(15,23,42,0.02)]! hover:shadow-xl! hover:shadow-emerald-100/30! hover:-translate-y-1! transition-all! duration-300!"
      >
        <div
          class="w-8! h-8! bg-emerald-50! group-hover:bg-emerald-500! text-emerald-600! group-hover:text-white! rounded-xl! flex! items-center! justify-center! shadow-sm! transition-all! duration-300! mb-2!"
        >
          <font-awesome-icon icon="file-invoice-dollar" class="text-sm!" />
        </div>
        <h3
          class="text-sm! font-bold! text-slate-800! mb-1.5! group-hover:text-emerald-600! transition-colors!"
        >
          Sistem Penagihan
        </h3>
        <p class="text-[11px]! text-slate-400! leading-relaxed! font-medium!">
          Kelola limit toleransi tunggakan bulanan, denda keterlambatan, jatuh tempo, serta
          integrasi WhatsApp API.
        </p>
      </div>
    </div>

    <!-- Live Status Banner -->
    <div class="relative! z-20! mt-4! px-4! md:px-6! animate-reveal-up! duration-500!">
      <div
        class="bg-slate-900! text-white! rounded-2xl! py-2.5! px-4! md:py-3! md:px-5! flex! flex-col! md:flex-row! items-center! justify-between! gap-4! shadow-lg! shadow-slate-900/10!"
      >
        <div class="flex! items-center! gap-3! text-center! md:text-left!">
          <div
            class="w-8! h-8! rounded-lg! bg-white/10! flex! items-center! justify-center! text-indigo-400! shrink-0!"
          >
            <font-awesome-icon icon="shield-alt" class="text-xs!" />
          </div>
          <div>
            <div
              class="text-[9px]! font-black! text-indigo-400! uppercase! tracking-widest! leading-none! mb-1!"
            >
              Status Sistem Berjalan
            </div>
            <p class="text-xs! font-bold! text-slate-300! mb-0!">
              PAMSIDES V2 • Seluruh Modul Terintegrasi Optimal
            </p>
          </div>
        </div>

        <div
          class="flex! items-center! gap-2.5! bg-white/5! border! border-white/10! px-3! py-1.5! rounded-xl!"
        >
          <div class="w-1.5! h-1.5! rounded-full! bg-emerald-400! animate-pulse!"></div>
          <span class="text-[9px]! font-black! uppercase! tracking-widest! text-slate-300!"
            >Aktif & Terlindungi</span
          >
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
defineModel({ required: true })

defineProps({
  onSave: {
    type: Function,
    required: true,
  },
})
</script>

<style scoped>
@keyframes revealUp {
  from {
    opacity: 0;
    transform: translateY(20px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}
.animate-reveal-up {
  animation: revealUp 0.8s cubic-bezier(0.16, 1, 0.3, 1) forwards;
}
.animate-pulse-slow {
  animation: pulse 4s cubic-bezier(0.4, 0, 0.6, 1) infinite;
}
</style>
