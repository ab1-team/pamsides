<template>
  <div class="relative! py-3! px-0! overflow-hidden! select-none!">
    <div class="absolute! inset-0! pointer-events-none!">
      <div
        class="absolute! -top-24! -right-12! w-80! h-80! bg-indigo-100/20! rounded-full! blur-3xl! animate-pulse-slow!"
      ></div>
      <div
        class="absolute! top-1/2! -left-12! -translate-y-1/2! w-64! h-64! bg-blue-50/40! rounded-full! blur-3xl!"
      ></div>
    </div>

    <div
      class="absolute! -right-4! md:right-2! top-1/2! -translate-y-1/2! opacity-[0.03]! pointer-events-none! rotate-12! scale-125! md:scale-100! overflow-hidden!"
    >
      <font-awesome-icon icon="droplet" class="text-6xl! md:text-9xl! text-indigo-900!" />
    </div>

    <div
      class="relative! flex! flex-col! md:flex-row! items-center! md:items-start! gap-4! md:gap-10! z-20! px-4! md:pl-8! text-center! md:text-left! animate-reveal-up!"
    >
      <div class="flex-1! space-y-3! md:space-y-4!">
        <div class="flex! flex-col! gap-1.5!">
          <div
            class="inline-flex! items-center! justify-center! md:justify-start! gap-2! text-indigo-600!"
          >
            <div
              class="flex! items-center! justify-center! w-5! h-5! bg-indigo-50! rounded-md! border! border-indigo-100!"
            >
              <font-awesome-icon icon="shield-alt" class="text-[10px]!" />
            </div>
            <span class="text-[9px]! font-black! uppercase! tracking-[0.2em]!"
              >Protocol Standard</span
            >
          </div>

          <h1
            class="text-xl! sm:text-2xl! md:text-3xl! font-black! text-slate-800! tracking-tight! leading-none!"
          >
            Standard Operating <span class="text-indigo-600! drop-shadow-sm!">Procedure</span>
          </h1>

          <p class="text-[11px]! font-bold! text-slate-400! uppercase! tracking-widest!">
            Pusat Kendali & Tata Kelola Digital
          </p>
        </div>

        <div class="max-w-xl! relative! px-5! md:px-0! md:pl-5! py-1!">
          <div
            class="absolute! left-4! md:left-0! top-0! bottom-0! w-1! md:w-1.5! bg-linear-to-b! from-indigo-500! via-blue-400! to-indigo-500! rounded-full!"
          ></div>

          <p class="text-xs! md:text-sm! text-slate-500! leading-relaxed! font-medium!">
            Optimalkan pengelolaan infrastruktur air di
            <span class="text-slate-900! font-bold! border-b-2! border-indigo-100!"
              >Tirto Mulo</span
            >
            melalui konfigurasi sistem yang presisi demi keberlanjutan layanan
            <span class="text-indigo-600! font-bold!">PAMSIMAS</span>.
          </p>
        </div>

        <div class="flex! items-center! justify-center! md:justify-start! gap-5! pt-2!">
          <div
            class="flex! items-center! gap-2! bg-slate-900! py-1.5! px-3.5! rounded-full! shadow-sm!"
          >
            <div class="w-1.5! h-1.5! rounded-full! bg-emerald-400! animate-pulse!"></div>
            <span class="text-[9px]! font-black! text-slate-100! uppercase! tracking-wider!"
              >System v2.0.4</span
            >
          </div>

          <div
            class="flex! items-center! gap-2.5! px-3! py-1.5! bg-white! border! border-slate-100! rounded-full! shadow-xs!"
          >
            <span class="text-[9px]! font-bold! text-slate-400! uppercase! tracking-widest!"
              >Salam Pamsimas</span
            >
            <div class="w-4! h-4! flex! items-center! justify-center! bg-blue-50! rounded-full!">
              <span class="text-indigo-500! text-[10px]! animate-bounce-subtle!">💧</span>
            </div>
          </div>
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
