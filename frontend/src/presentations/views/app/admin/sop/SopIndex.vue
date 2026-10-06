<template>
  <div class="sop-root w-full!">
    <ContentCard
      variant="minimal"
      padding="none"
      class="relative! lg:shadow-lg! lg:variant-default! lg:hoverable!"
    >
      <div class="hidden lg:flex! items-stretch! min-h-[400px]!">
        <ContentCard
          variant="minimal"
          padding="none"
          class="w-60! xl:w-64! flex-shrink-0! self-stretch! bg-slate-900! border-r! border-slate-800! rounded-tr-none! rounded-br-none! overflow-hidden! relative! z-20!"
        >
          <div class="flex flex-col gap-1! py-5! px-3!">
            <p
              class="text-[10px]! font-bold! text-slate-500! uppercase! tracking-widest! mb-2! pl-3! pr-2!"
            >
              Menu Pengaturan
            </p>

            <div class="flex flex-col gap-0.5!">
              <div v-for="menu in menuList" :key="menu.key" class="relative!">
                <BaseButton
                  @click="activeSection = menu.key"
                  variant="ghost"
                  class="w-full! justify-start! pl-3! pr-3! py-2.5! rounded-xl! transition-all! duration-200! relative!"
                  :class="[
                    activeSection === menu.key
                      ? 'bg-[#f8fafc]! text-slate-900! font-bold! shadow-sm! ring-0! focus:ring-0! outline-none!'
                      : 'text-slate-400! hover:text-white! hover:bg-white/10!',
                  ]"
                >
                  <span class="w-full! flex! items-center! justify-between! gap-2!">
                    <span class="flex items-center gap-2.5! min-w-0!">
                      <span class="text-sm! leading-none! shrink-0! w-4! flex! justify-center!">
                        <font-awesome-icon :icon="menu.icon" />
                      </span>
                      <span class="text-[13px]! font-bold! text-left! truncate!">{{
                        menu.label
                      }}</span>
                    </span>
                    <font-awesome-icon
                      v-if="activeSection === menu.key"
                      icon="chevron-right"
                      class="text-[10px]! text-slate-400! shrink-0!"
                    />
                  </span>
                </BaseButton>
              </div>
            </div>
          </div>
        </ContentCard>

        <div class="flex-1! min-w-0! p-6! bg-[#f8fafc]! relative! z-10! rounded-r-xl!">
          <div class="max-w-3xl!">
            <header class="mb-8!">
              <h2 class="text-xl! font-bold! text-slate-800! mb-1!">
                {{ activeLabel }}
              </h2>
            </header>

            <transition name="fade" mode="out-in">
              <div :key="activeSection">
                <WellcomeForm
                  v-if="activeSection === 'wellcome'"
                  v-model="wellcomeForm"
                  :on-save="saveSettings"
                />
                <LembagaForm
                  v-if="activeSection === 'lembaga'"
                  v-model="lembagaForm"
                  :on-save="saveSettings"
                />
                <PasangBaruForm
                  v-if="activeSection === 'pasangBaru'"
                  v-model="pasangBaruForm"
                  :on-save="saveSettings"
                />
                <SistemTagihanForm
                  v-if="activeSection === 'sistemTagihan'"
                  v-model="sistemTagihanForm"
                  :on-save="saveSettings"
                />
                <CalkForm
                  v-if="activeSection === 'calk'"
                  v-model="calkForm"
                  :on-save="saveSettings"
                />
                <LogoForm
                  v-if="activeSection === 'logo'"
                  v-model="logoForm"
                  :on-save="saveSettings"
                />
                <WhatsappForm
                  v-if="activeSection === 'whatsapp'"
                  v-model="whatsappForm"
                  :on-save="saveSettings"
                />
                <!-- Tanda tangan: blok ini self-managed (punya tombol save sendiri),
                 tidak bergantung pada :on-save dari SopIndex. -->
                <SignatureForm
                  v-if="activeSection === 'signature'"
                />
              </div>
            </transition>
          </div>
        </div>
      </div>

      <div class="lg:hidden! flex! flex-col!">
        <div
          class="px-3.5! pt-5! pb-4! border-b! border-slate-800! bg-slate-900! rounded-t-xl! overflow-hidden!"
        >
          <p
            class="text-[10px]! font-black! text-slate-500! uppercase! tracking-[0.2em]! mb-3.5! ml-1.5!"
          >
            Pilih Kategori
          </p>
          <div
            class="flex! items-center! justify-between! gap-1! bg-slate-950/40! p-1.5! rounded-2xl! border! border-slate-700/50! w-full!"
          >
            <div v-for="menu in menuList" :key="menu.key" class="flex-1! flex! justify-center!">
              <button
                @click="activeSection = menu.key"
                class="relative! flex! items-center! justify-center! transition-all! duration-500! outline-none!"
                :class="[
                  activeSection === menu.key
                    ? 'bg-white! text-slate-900! px-2.5! py-1.5! rounded-xl! shadow-lg! shadow-white/10!'
                    : 'text-slate-500! hover:text-slate-300! p-2!',
                ]"
              >
                <div class="flex! items-center! gap-1.5!">
                  <font-awesome-icon
                    :icon="menu.icon"
                    :class="activeSection === menu.key ? 'text-[11px]!' : 'text-sm!'"
                  />
                  <span
                    v-if="activeSection === menu.key"
                    class="text-[10px]! font-black! uppercase! tracking-tight! whitespace-nowrap! animate-item-reveal"
                  >
                    {{ menu.label }}
                  </span>
                </div>

                <div
                  v-if="activeSection !== menu.key"
                  class="absolute! -bottom-1! left-1/2! -translate-x-1/2! w-1! h-1! bg-slate-700! rounded-full! opacity-0!"
                ></div>
              </button>
            </div>
          </div>
        </div>

        <div class="p-4! md:p-6! bg-white! rounded-b-xl!">
          <div class="mb-6! md:mb-8!">
            <h2 class="text-lg! md:text-xl! font-black! text-slate-800! tracking-tight! mb-1!">
              {{ activeLabel }}
            </h2>
            <p
              class="text-[10px]! md:text-[11px]! font-bold! text-slate-400! uppercase! tracking-wider!"
            >
              Atur konfigurasi pada bagian ini.
            </p>
          </div>

          <transition name="fade" mode="out-in">
            <div :key="activeSection">
              <WellcomeForm
                v-if="activeSection === 'wellcome'"
                v-model="wellcomeForm"
                :on-save="saveSettings"
              />
              <LembagaForm
                v-if="activeSection === 'lembaga'"
                v-model="lembagaForm"
                :on-save="saveSettings"
              />
              <PasangBaruForm
                v-if="activeSection === 'pasangBaru'"
                v-model="pasangBaruForm"
                :on-save="saveSettings"
              />
              <SistemTagihanForm
                v-if="activeSection === 'sistemTagihan'"
                v-model="sistemTagihanForm"
                :on-save="saveSettings"
              />
              <CalkForm
                v-if="activeSection === 'calk'"
                v-model="calkForm"
                :on-save="saveSettings"
              />
              <LogoForm
                v-if="activeSection === 'logo'"
                v-model="logoForm"
                :on-save="saveSettings"
              />
              <WhatsappForm
                v-if="activeSection === 'whatsapp'"
                v-model="whatsappForm"
                :on-save="saveSettings"
              />
              <!-- Tanda tangan: blok ini self-managed (punya tombol save sendiri),
               tidak bergantung pada :on-save dari SopIndex. -->
              <SignatureForm
                v-if="activeSection === 'signature'"
              />
            </div>
          </transition>
        </div>
      </div>
    </ContentCard>
  </div>
</template>

<script setup>
import { useSop } from '@/composables/useSop'
import ContentCard from '@/presentations/components/ui/ContentCard.vue'
import BaseButton from '@/presentations/components/ui/BaseButton.vue'
import LembagaForm from './partials/LembagaSop.vue'
import WellcomeForm from './partials/WelcomeSop.vue'
import PasangBaruForm from './partials/pasangBaru.vue'
import SistemTagihanForm from './partials/sistemTagihan.vue'
import CalkForm from './partials/CalkSop.vue'
import LogoForm from './partials/LogoSop.vue'
import WhatsappForm from './partials/WhatsappSop.vue'
import SignatureForm from './partials/SignatureSop.vue'

const {
  activeSection,
  activeLabel,
  menuList,
  lembagaForm,
  logoForm,
  whatsappForm,
  saveSettings,
  wellcomeForm,
  pasangBaruForm,
  sistemTagihanForm,
  calkForm,
} = useSop()
</script>
