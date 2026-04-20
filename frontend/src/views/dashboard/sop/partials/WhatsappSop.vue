<template>
  <div class="flex flex-col gap-5!">
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6!">
      <BaseInput
        v-model="form.templateTagihan"
        label="Pesan Tagihan"
        type="textarea"
        placeholder="Isi pesan tagihan..."
        :rows="10"
        class="w-full!"
        hint="Gunakan tag {customer}, {desa}, {jumlah_tagihan} sebagai placeholder."
      />
      <BaseInput
        v-model="form.templatePembayaran"
        label="Pesan Pembayaran"
        type="textarea"
        placeholder="Isi pesan pembayaran..."
        :rows="10"
        class="w-full!"
        hint="Gunakan tag {customer}, {desa}, {tagihan} sebagai placeholder."
      />
    </div>

    <div
      class="flex flex-col sm:flex-row! sm:justify-end! items-stretch! sm:items-center! gap-3! mt-6! md:mt-4!"
    >
      <BaseButton
        variant="info-gradient"
        size="md"
        @click="() => {}"
        icon="qrcode"
        class="w-full! sm:w-auto! rounded-xl! shadow-lg! shadow-blue-200!"
      >
        Scan Whatsapp
      </BaseButton>

      <BaseButton
        variant="secondary"
        size="md"
        @click="onSave"
        icon="save"
        class="w-full! sm:w-auto! rounded-xl! shadow-lg! shadow-slate-200!"
      >
        Simpan
      </BaseButton>
    </div>
  </div>
</template>

<script setup>
import BaseInput from '@/components/ui/BaseInput.vue'
import BaseButton from '@/components/ui/BaseButton.vue'

const form = defineModel({ required: true })

defineProps({
  onSave: {
    type: Function,
    required: true,
  },
})
</script>
