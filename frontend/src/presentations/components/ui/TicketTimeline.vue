<template>
  <div class="ticket-timeline py-4!">
    <div v-for="(step, idx) in steps" :key="idx" class="relative! pl-8! pb-8! last:pb-0!">
      <!-- Line -->
      <div
        v-if="idx < steps.length - 1"
        class="absolute! left-[11px]! top-[22px]! bottom-0! w-[2px]!"
        :class="step.completed ? 'bg-indigo-500!' : 'bg-slate-200!'"
      ></div>

      <!-- Circle Icon -->
      <div
        class="absolute! left-0! top-0! w-6! h-6! rounded-full! flex! items-center! justify-center! z-10!"
        :class="[
          step.completed
            ? 'bg-indigo-600! text-white!'
            : step.current
              ? 'bg-white! border-2! border-indigo-600! text-indigo-600!'
              : 'bg-slate-100! text-slate-400!',
        ]"
      >
        <font-awesome-icon :icon="step.completed ? 'check' : step.icon" class="text-[10px]!" />
      </div>

      <!-- Content -->
      <div :class="{ 'opacity-40!': !step.completed && !step.current }">
        <div class="flex! items-center! justify-between! mb-1!">
          <h4
            class="text-sm! font-bold! text-slate-800!"
            :class="{ 'text-indigo-600!': step.current }"
          >
            {{ step.title }}
          </h4>
          <span v-if="step.date" class="text-[10px]! font-medium! text-slate-400!">{{
            step.date
          }}</span>
        </div>
        <p class="text-xs! text-slate-500! leading-relaxed!">{{ step.desc }}</p>

        <!-- Action Preview (Mock) -->
        <div
          v-if="step.photo"
          class="mt-3! relative! w-24! h-24! rounded-xl! overflow-hidden! border! border-slate-200!"
        >
          <img :src="step.photo" class="w-full! h-full! object-cover!" />
          <div
            class="absolute! inset-0! bg-black/20! flex! items-center! justify-center! opacity-0! hover:opacity-100! transition-opacity! cursor-pointer!"
          >
            <font-awesome-icon icon="eye" class="text-white!" />
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
defineProps({
  steps: {
    type: Array,
    required: true,
  },
})
</script>

<style scoped>
.ticket-timeline {
  animation: fadeIn 0.5s ease-out;
}
@keyframes fadeIn {
  from {
    opacity: 0;
  }
  to {
    opacity: 1;
  }
}
</style>
