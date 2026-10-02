<template>
  <div class="flex flex-col gap-6! py-2!">
    <div class="relative! p-1! bg-slate-50/50! border! border-slate-100! rounded-3xl! shadow-sm!">
      <div class="grid grid-cols-1! gap-1!">
        <div class="p-5! hover:bg-white! transition-colors! rounded-3xl!">
          <div class="flex! flex-col! gap-3!">
            <div class="flex! items-center! gap-2.5!">
              <div
                class="w-8! h-8! rounded-lg! bg-blue-600! text-white! flex! items-center! justify-center! text-xs! shadow-lg! shadow-blue-100!"
              >
                <font-awesome-icon icon="book-open" />
              </div>
              <div class="flex-1!">
                <h4 class="text-xs! font-black! text-slate-800! leading-tight!">
                  Peraturan Desa
                </h4>
                <p class="text-[10px]! text-slate-400!">
                  Nomor peraturan bersama kepala desa yang menjadi dasar hukum.
                </p>
              </div>
            </div>
            <BaseInput
              v-model="form.peraturan_desa"
              placeholder="Contoh: 01 TAHUN 2022"
              size="md"
              prefix-icon="file-contract"
              :no-margin="true"
              class="w-full!"
            />
          </div>
        </div>
      </div>
    </div>

    <!-- Bagian Milik Bersama Masyarakat Desa (persentase) -->
    <div
      class="p-5! bg-linear-to-r! from-emerald-100! to-emerald-50! border! border-emerald-200! rounded-2xl!"
    >
      <div class="flex! items-center! gap-2.5! mb-4!">
        <div
          class="w-8! h-8! rounded-lg! bg-emerald-600! text-white! flex! items-center! justify-center! text-xs! shadow-lg! shadow-emerald-100!"
        >
          <font-awesome-icon icon="people-group" />
        </div>
        <div class="flex-1!">
          <h4 class="text-xs! font-black! text-emerald-900! leading-tight!">
            Bagian Milik Bersama Masyarakat Desa (%)
          </h4>
          <p class="text-[10px]! text-emerald-700!">
            Persentase pembagian laba untuk masyarakat desa.
          </p>
        </div>
      </div>

      <div class="grid grid-cols-1 md:grid-cols-3! gap-4!">
        <div>
          <label class="text-[10px]! font-bold! text-slate-500! uppercase! tracking-widest! mb-1! block!">
            Bantuan Rumah Tangga (%)
          </label>
          <BaseInput
            v-model="form.bantuan_rumah_tangga"
            type="number"
            :min="0"
            :max="100"
            placeholder="25"
            size="md"
            :no-margin="true"
            class="w-full!"
          />
        </div>
        <div>
          <label class="text-[10px]! font-bold! text-slate-500! uppercase! tracking-widest! mb-1! block!">
            Pengembangan Kapasitas (%)
          </label>
          <BaseInput
            v-model="form.pengembangan_kapasitas"
            type="number"
            :min="0"
            :max="100"
            placeholder="25"
            size="md"
            :no-margin="true"
            class="w-full!"
          />
        </div>
        <div>
          <label class="text-[10px]! font-bold! text-slate-500! uppercase! tracking-widest! mb-1! block!">
            Pelatihan Masyarakat (%)
          </label>
          <BaseInput
            v-model="form.pelatihan_masyarakat"
            type="number"
            :min="0"
            :max="100"
            placeholder="50"
            size="md"
            :no-margin="true"
            class="w-full!"
          />
        </div>
      </div>
    </div>

    <!-- Laba Ditahan (nominal) -->
    <div
      class="p-5! bg-linear-to-r! from-amber-100! to-amber-50! border! border-amber-200! rounded-2xl!"
    >
      <div class="flex! items-center! gap-2.5! mb-4!">
        <div
          class="w-8! h-8! rounded-lg! bg-amber-600! text-white! flex! items-center! justify-center! text-xs! shadow-lg! shadow-amber-100!"
        >
          <font-awesome-icon icon="piggy-bank" />
        </div>
        <div class="flex-1!">
          <h4 class="text-xs! font-black! text-amber-900! leading-tight!">
            Laba Ditahan (Nominal)
          </h4>
          <p class="text-[10px]! text-amber-700!">
            Akumulasi laba ditahan per kategori (dalam Rupiah).
          </p>
        </div>
      </div>

      <div class="grid grid-cols-1 md:grid-cols-3! gap-4!">
        <div>
          <label class="text-[10px]! font-bold! text-slate-500! uppercase! tracking-widest! mb-1! block!">
            Peningkatan Modal
          </label>
          <BaseInput
            v-model="form.peningkatan_modal"
            type="number"
            :min="0"
            placeholder="0"
            size="md"
            prefix-icon="money-bill"
            :no-margin="true"
            class="w-full!"
          />
        </div>
        <div>
          <label class="text-[10px]! font-bold! text-slate-500! uppercase! tracking-widest! mb-1! block!">
            Penambahan Investasi Usaha
          </label>
          <BaseInput
            v-model="form.penambahan_investasi"
            type="number"
            :min="0"
            placeholder="0"
            size="md"
            prefix-icon="money-bill"
            :no-margin="true"
            class="w-full!"
          />
        </div>
        <div>
          <label class="text-[10px]! font-bold! text-slate-500! uppercase! tracking-widest! mb-1! block!">
            Pendirian Unit Usaha
          </label>
          <BaseInput
            v-model="form.pendirian_unit_usaha"
            type="number"
            :min="0"
            placeholder="0"
            size="md"
            prefix-icon="money-bill"
            :no-margin="true"
            class="w-full!"
          />
        </div>
      </div>
    </div>

    <!-- Custom CALK (Point A - Gambaran Umum) WYSIWYG Editor -->
    <div
      class="p-5! bg-linear-to-r! from-indigo-100! to-blue-50! border! border-indigo-200! rounded-2xl!"
    >
      <div class="flex! items-start! gap-3! mb-4!">
        <div
          class="w-8! h-8! rounded-lg! bg-indigo-600! text-white! flex! items-center! justify-center! text-xs! shadow-lg! shadow-indigo-100! shrink-0!"
        >
          <font-awesome-icon icon="lightbulb" />
        </div>
        <div class="flex-1!">
          <h4 class="text-xs! font-black! text-indigo-900! leading-tight!">
            Custom CALK (Point A - Gambaran Umum)
          </h4>
          <p class="text-[10px]! text-indigo-700!">
            Narasi khusus yang akan menggantikan teks bawaan pada bagian
            "A. Gambaran Umum" di laporan.
            Gunakan editor WYSIWYG untuk memformat teks dengan mudah (bold,
            italic, heading, list, dan link). Kosongkan untuk menggunakan teks
            bawaan.
          </p>
        </div>
      </div>

      <!-- Toolbar Aksi Cepat: Insert Variable Placeholder -->
      <div class="flex! flex-wrap! gap-2! p-3! bg-white! border! border-indigo-100! rounded-xl! mb-3!">
        <span class="text-[10px]! font-bold! text-slate-500! uppercase! tracking-widest! self-center! mr-2!">
          Sisipkan Variabel:
        </span>
        <button
          v-for="(v, i) in variableSnippets"
          :key="i"
          type="button"
          @click="insertVariable(v.value)"
          class="px-3! py-1.5! text-[11px]! font-bold! rounded-lg! bg-white! border! border-slate-200! text-slate-700! hover:bg-indigo-50! hover:border-indigo-300! hover:text-indigo-700! transition-colors!"
          :title="`Sisipkan ${v.label} ke posisi kursor`"
        >
          <font-awesome-icon :icon="v.icon" class="mr-1!" />
          {{ v.label }}
        </button>
        <button
          type="button"
          @click="resetPointA"
          class="px-3! py-1.5! text-[11px]! font-bold! rounded-lg! bg-rose-50! border! border-rose-200! text-rose-700! hover:bg-rose-100! transition-colors! ml-auto!"
        >
          <font-awesome-icon icon="trash-can" class="mr-1!" />
          Kosongkan
        </button>
      </div>

      <!-- PrimeVue WYSIWYG Editor (Quill) -->
      <div class="calk-wysiwyg-wrapper">
        <PrimeEditor
          v-model="form.point_a"
          placeholder="Tulis narasi Point A di sini. Gunakan toolbar untuk format teks. Kosongkan untuk menggunakan teks bawaan."
          editor-style="height: 320px; background: #ffffff; color: #1e293b;"
          :pt="{
            root: { style: 'border-radius: 12px; overflow: hidden; background: #ffffff; color: #1e293b;' },
            toolbar: { style: 'background: #eef2ff; border-bottom: 1px solid #c7d2fe; color: #475569;' },
            content: { style: 'background: #ffffff; color: #1e293b;' },
          }"
          @load="handleEditorLoad"
        >
          <template #toolbar>
            <span class="ql-formats">
              <select class="ql-header" default-value="2">
                <option value="1">Heading 1</option>
                <option value="2">Heading 2</option>
                <option value="3">Heading 3</option>
                <option value="4">Heading 4</option>
                <option value="5">Heading 5</option>
                <option value="6">Heading 6</option>
              </select>
            </span>
            <span class="ql-formats">
              <button class="ql-bold" aria-label="Bold"></button>
              <button class="ql-italic" aria-label="Italic"></button>
              <button class="ql-underline" aria-label="Underline"></button>
              <button class="ql-strike" aria-label="Strike"></button>
            </span>
            <span class="ql-formats">
              <button class="ql-list" value="ordered" aria-label="Ordered List"></button>
              <button class="ql-list" value="bullet" aria-label="Bullet List"></button>
              <button class="ql-indent" value="-1" aria-label="Decrease Indent"></button>
              <button class="ql-indent" value="+1" aria-label="Increase Indent"></button>
            </span>
            <span class="ql-formats">
              <select class="ql-align">
                <option defaultValue></option>
                <option value="center"></option>
                <option value="right"></option>
                <option value="justify"></option>
              </select>
            </span>
            <span class="ql-formats">
              <button class="ql-blockquote" aria-label="Blockquote"></button>
              <button class="ql-code-block" aria-label="Code Block"></button>
            </span>
            <span class="ql-formats">
              <button class="ql-link" aria-label="Link"></button>
              <button class="ql-image" aria-label="Image"></button>
              <button class="ql-clean" aria-label="Clear Formatting"></button>
            </span>
          </template>
        </PrimeEditor>
      </div>

      <!-- Preview Output HTML -->
      <div class="mt-3!">
        <label class="text-[10px]! font-bold! text-slate-500! uppercase! tracking-widest! mb-2! block!">
          Preview HTML (Hasil yang akan tampil di laporan)
        </label>
        <div
          class="min-h-[60px]! p-4! border! border-slate-200! rounded-xl! bg-white! text-[12px]! leading-relaxed! text-slate-700!"
          v-html="form.point_a || '<em class=&quot;text-slate-400!&quot;>(kosong - akan menampilkan teks bawaan)</em>'"
        ></div>
      </div>
    </div>

    <!-- Info box -->
    <div
      class="p-5! bg-linear-to-r! from-indigo-900/5! to-blue-900/5! border! border-indigo-100! rounded-2xl! relative! overflow-hidden!"
    >
      <div
        class="absolute! -top-8! -right-8! w-32! h-32! bg-indigo-600/5! rounded-full! blur-3xl!"
      ></div>

      <div class="flex! items-start! gap-4! relative! z-10!">
        <div
          class="w-10! h-10! rounded-xl! bg-indigo-600! flex! items-center! justify-center! text-white! shadow-lg! shadow-indigo-100! shrink-0!"
        >
          <font-awesome-icon icon="info-circle" class="text-lg!" />
        </div>
        <div>
          <h4 class="text-xs! font-black! text-indigo-900! uppercase! tracking-widest! mb-1!">
            Tentang CALK
          </h4>
          <p class="text-[11px]! font-medium! text-slate-500! leading-relaxed!">
            <strong class="text-indigo-700!">Catatan Atas Laporan Keuangan (CALK)</strong>
            adalah bagian tidak terpisahkan dari laporan keuangan yang menjelaskan
            kebijakan akuntansi dan rincian pembagian laba.
            Pengaturan di sini akan otomatis tampil di
            <strong>Pelaporan &raquo; CALK</strong>.
          </p>
        </div>
      </div>
    </div>

    <div
      class="flex! items-center! justify-center! md:justify-end! pt-4! border-t! border-slate-50!"
    >
      <BaseButton
        variant="secondary"
        size="md"
        @click="onSave"
        icon="save"
        class="w-full! md:w-auto! rounded-xl! px-8! shadow-lg! shadow-indigo-100!"
      >
        Simpan Pengaturan CALK
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

/**
 * Instance Quill yang disediakan oleh PrimeVue Editor via event @load.
 * Disimpan di module-scope agar dapat diakses oleh insertVariable().
 */
let quillInstance = null

/**
 * Dipanggil otomatis oleh PrimeVue Editor saat instance Quill siap.
 * Instance ini kemudian digunakan untuk insert variable di posisi kursor.
 */
function handleEditorLoad(event) {
  quillInstance = event && event.instance ? event.instance : null
}

/**
 * Placeholder variabel yang bisa disisipkan ke editor WYSIWYG.
 * Nilai-nilai ini akan diganti oleh backend PelaporanController saat render.
 */
const variableSnippets = [
  { label: 'Nama Lembaga', value: '{{ lembagaNama }}', icon: 'building' },
  { label: 'Alamat Lembaga', value: '{{ lembagaAlamat }}', icon: 'map-marker-alt' },
  { label: 'Nomor Peraturan Desa', value: '{{ peraturanDesa }}', icon: 'file-contract' },
  { label: 'SK Kemenkumham', value: '{{ skKemenkumham }}', icon: 'stamp' },
  { label: 'Tahun', value: '{{ tahun }}', icon: 'calendar' },
]

/**
 * Sisipkan placeholder variabel pada posisi kursor editor Quill.
 * Menggunakan instance Quill untuk operasi insertText + setSelection.
 * Fallback: append ke akhir konten jika instance belum siap.
 */
function insertVariable(value) {
  if (quillInstance && typeof quillInstance.focus === 'function') {
    quillInstance.focus()
    const range = quillInstance.getSelection(true)
    const index = range ? range.index : quillInstance.getLength() - 1
    const insertion = ' ' + value + ' '
    quillInstance.insertText(index, insertion, 'user')
    quillInstance.setSelection(index + insertion.length)
    // Sinkronkan v-model karena perubahan dari API mungkin belum propagate
    form.point_a = quillInstance.root.innerHTML
    return
  }

  // Fallback: append ke akhir
  form.point_a = (form.point_a || '') + ' ' + value
}

function resetPointA() {
  if (confirm('Kosongkan Custom CALK? Teks bawaan akan digunakan di laporan.')) {
    form.point_a = ''
  }
}
</script>

<style>
/* Styling tambahan untuk PrimeVue Editor (Quill) agar sesuai tema terang aplikasi */
.calk-wysiwyg-wrapper {
  border-radius: 12px !important;
  overflow: hidden !important;
  border: 1px solid #c7d2fe !important;
}

.calk-wysiwyg-wrapper,
.calk-wysiwyg-wrapper .ql-toolbar.ql-snow,
.calk-wysiwyg-wrapper .ql-container.ql-snow,
.calk-wysiwyg-wrapper .ql-editor {
  background: #ffffff !important;
  color: #1e293b !important;
}

/* Toolbar rounded atas, container rounded bawah */
.calk-wysiwyg-wrapper .ql-toolbar.ql-snow {
  border: 0 !important;
  border-bottom: 1px solid #c7d2fe !important;
  padding: 8px 12px !important;
  font-family: inherit !important;
  border-top-left-radius: 12px !important;
  border-top-right-radius: 12px !important;
}

.calk-wysiwyg-wrapper .ql-container.ql-snow {
  border: 0 !important;
  font-size: 13px !important;
  font-family: inherit !important;
  border-bottom-left-radius: 12px !important;
  border-bottom-right-radius: 12px !important;
}

.calk-wysiwyg-wrapper .ql-editor {
  min-height: 320px !important;
  padding: 14px 16px !important;
  line-height: 1.6 !important;
  color: #1e293b !important;
}

/* Placeholder saat kosong */
.calk-wysiwyg-wrapper .ql-editor.ql-blank::before {
  font-style: italic !important;
  color: #94a3b8 !important;
  left: 16px !important;
}

/* Toolbar button: stroke & fill default abu-abu */
.calk-wysiwyg-wrapper .ql-snow .ql-stroke {
  stroke: #475569 !important;
}
.calk-wysiwyg-wrapper .ql-snow .ql-fill {
  fill: #475569 !important;
}
.calk-wysiwyg-wrapper .ql-snow .ql-picker {
  color: #475569 !important;
}
.calk-wysiwyg-wrapper .ql-snow .ql-picker-label {
  color: #475569 !important;
}
.calk-wysiwyg-wrapper .ql-snow .ql-picker-options {
  background-color: #ffffff !important;
  color: #1e293b !important;
}

/* Active / hover */
.calk-wysiwyg-wrapper .ql-snow.ql-toolbar button:hover,
.calk-wysiwyg-wrapper .ql-snow.ql-toolbar button:focus,
.calk-wysiwyg-wrapper .ql-snow.ql-toolbar button.ql-active,
.calk-wysiwyg-wrapper .ql-snow .ql-toolbar button:hover,
.calk-wysiwyg-wrapper .ql-snow .ql-toolbar button:focus,
.calk-wysiwyg-wrapper .ql-snow .ql-toolbar button.ql-active {
  color: #4f46e5 !important;
}
.calk-wysiwyg-wrapper .ql-snow.ql-toolbar button:hover .ql-fill,
.calk-wysiwyg-wrapper .ql-snow.ql-toolbar button:focus .ql-fill,
.calk-wysiwyg-wrapper .ql-snow.ql-toolbar button.ql-active .ql-fill,
.calk-wysiwyg-wrapper .ql-snow .ql-toolbar button:hover .ql-fill,
.calk-wysiwyg-wrapper .ql-snow .ql-toolbar button:focus .ql-fill,
.calk-wysiwyg-wrapper .ql-snow .ql-toolbar button.ql-active .ql-fill {
  fill: #4f46e5 !important;
}
.calk-wysiwyg-wrapper .ql-snow.ql-toolbar button:hover .ql-stroke,
.calk-wysiwyg-wrapper .ql-snow.ql-toolbar button:focus .ql-stroke,
.calk-wysiwyg-wrapper .ql-snow.ql-toolbar button.ql-active .ql-stroke,
.calk-wysiwyg-wrapper .ql-snow .ql-toolbar button:hover .ql-stroke,
.calk-wysiwyg-wrapper .ql-snow .ql-toolbar button:focus .ql-stroke,
.calk-wysiwyg-wrapper .ql-snow .ql-toolbar button.ql-active .ql-stroke {
  stroke: #4f46e5 !important;
}
.calk-wysiwyg-wrapper .ql-snow .ql-picker-label:hover,
.calk-wysiwyg-wrapper .ql-snow .ql-picker-label.ql-active,
.calk-wysiwyg-wrapper .ql-snow .ql-picker-item:hover,
.calk-wysiwyg-wrapper .ql-snow .ql-picker-item.ql-selected {
  color: #4f46e5 !important;
}

/* Tooltip */
.calk-wysiwyg-wrapper .ql-snow .ql-tooltip {
  background-color: #ffffff !important;
  border: 1px solid #cbd5e1 !important;
  color: #1e293b !important;
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08) !important;
}
.calk-wysiwyg-wrapper .ql-snow .ql-tooltip input[type=text] {
  border: 1px solid #cbd5e1 !important;
  color: #1e293b !important;
  background: #ffffff !important;
}
</style>