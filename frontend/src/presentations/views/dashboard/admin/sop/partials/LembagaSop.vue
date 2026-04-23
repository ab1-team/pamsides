<template>
  <div>
    <div class="grid grid-cols-1 md:grid-cols-2 gap-x-6! gap-y-4! md:gap-y-2!">
      <BaseInput
        v-model="form.nama"
        label="Nama Lembaga"
        placeholder="Masukkan nama lembaga..."
        prefix-icon="building"
      />
      <BaseInput
        v-model="form.website"
        label="Website"
        placeholder="https://..."
        prefix-icon="globe"
      />
      <BaseInput
        v-model="form.email"
        label="Alamat Email"
        type="email"
        placeholder="lembaga@email.com"
        prefix-icon="envelope"
      />
      <BaseInput
        v-model="form.telepon"
        label="Nomor Telepon"
        placeholder="021-xxxx"
        prefix-icon="phone"
      />
      <BaseInput
        v-model="form.alamat"
        label="Alamat Lengkap"
        type="textarea"
        placeholder="Alamat kantor..."
        :rows="3"
        class="md:col-span-2!"
      />
    </div>

    <div class="flex justify-center md:justify-end! mt-8! md:mt-6!">
      <BaseButton
        variant="secondary"
        size="md"
        @click="onSave"
        icon="save"
        class="w-full! md:w-auto! rounded-xl! shadow-lg! shadow-slate-200!"
      >
        Simpan Profil
      </BaseButton>
    </div>
  </div>
</template>

<script setup>
import BaseInput from '@/presentations/components/ui/BaseInput.vue'
import BaseButton from '@/presentations/components/ui/BaseButton.vue'

const form = defineModel({ required: true })

defineProps({
  onSave: {
    type: Function,
    required: true,
  },
})
</script>
