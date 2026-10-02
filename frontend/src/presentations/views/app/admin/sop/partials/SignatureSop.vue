<template>
  <div class="signature-sop w-full!">
    <!-- Info box: pola sama dengan CalkSop (gradient indigo + badge brand) -->
    <div
      class="mb-6! p-5! bg-linear-to-r! from-indigo-900/5! to-blue-900/5! border! border-indigo-100! rounded-2xl! relative! overflow-hidden!"
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
          <h4
            class="text-xs! font-black! text-indigo-900! uppercase! tracking-widest! mb-1!"
          >
            Tentang Tanda Tangan Laporan
          </h4>
          <p class="text-[11px]! font-medium! text-slate-500! leading-relaxed!">
            Atur gambar dan template tanda tangan per jenis laporan.
            Gunakan token
            <code
              class="bg-white! px-1.5! py-0.5! rounded! font-mono! text-xs! border! border-slate-300!"
              >{ttd_image}</code
            >
            di template HTML untuk posisi image. Jika tidak ada, image otomatis
            disisipkan ke baris kosong pertama. Format image: PNG / JPG / WebP,
            maksimal 2 MB.
          </p>
        </div>
      </div>
    </div>

    <!-- Section: Pilih Jenis Laporan -->
    <!-- (SelectSearch dipindahkan ke dalam header card Template Tanda Tangan di bawah) -->

    <!-- Section: Gambar Tanda Tangan (card style mengikuti CalkSop) -->
    <div
      class="relative! mb-6! p-1! bg-slate-50/50! border! border-slate-100! rounded-3xl! shadow-sm!"
    >
      <div class="grid grid-cols-1! gap-1!">
        <div class="p-5! hover:bg-white! transition-colors! rounded-3xl!">
          <div
            class="flex! flex-col! sm:flex-row! sm:items-center! sm:justify-between! gap-3!"
          >
            <div class="flex! items-center! gap-2.5!">
              <div
                class="w-8! h-8! rounded-lg! bg-blue-600! text-white! flex! items-center! justify-center! text-xs! shadow-lg! shadow-blue-100!"
              >
                <font-awesome-icon icon="pen-nib" />
              </div>
              <div class="flex-1!">
                <h4 class="text-xs! font-black! text-slate-800! leading-tight!">
                  Gambar Tanda Tangan
                </h4>
                <p class="text-[10px]! text-slate-400!">
                  Digunakan untuk jenis laporan
                  <b>{{ activeLabel }}</b>
                </p>
              </div>
            </div>
            <div class="flex! flex-wrap! gap-2!">
              <BaseButton
                type="button"
                variant="secondary"
                size="sm"
                icon="pen-nib"
                @click="showPad = true"
              >
                Gambar
              </BaseButton>
              <BaseButton
                type="button"
                variant="secondary"
                size="sm"
                icon="upload"
                @click="triggerUpload"
              >
                Unggah File
              </BaseButton>
              <BaseButton
                v-if="currentImageUrl"
                type="button"
                variant="danger"
                size="sm"
                icon="trash"
                :disabled="deleting"
                @click="confirmDeleteImage"
              >
                Hapus
              </BaseButton>
              <input
                ref="fileInputRef"
                type="file"
                accept="image/png,image/jpeg,image/webp"
                style="display: none"
                @change="onFileSelected"
              />
            </div>
          </div>

          <div
            v-if="currentImageUrl"
            class="mt-4! flex! justify-center! rounded-xl! border! border-slate-200! bg-white! p-4!"
          >
            <img
              :src="currentImageUrl"
              alt="Tanda Tangan"
              class="max-h-32! object-contain!"
            />
          </div>
          <div
            v-else
            class="mt-4! rounded-xl! border-2! border-dashed! border-slate-300! bg-white! py-8! text-center!"
          >
            <font-awesome-icon
              icon="pen-nib"
              class="text-3xl! text-slate-300!"
            />
            <p class="mt-2! text-sm! text-slate-500!">
              Belum ada tanda tangan untuk jenis laporan ini.
            </p>
          </div>
        </div>
      </div>
    </div>

    <!-- Section: Template HTML Editor (WYSIWYG Quill via PrimeEditor) -->
    <div
      class="relative! mb-6! p-1! bg-slate-50/50! border! border-slate-100! rounded-3xl! shadow-sm!"
    >
      <div class="grid grid-cols-1! gap-1!">
        <div class="p-5! hover:bg-white! transition-colors! rounded-3xl!">
          <div class="flex! flex-col! gap-3!">
            <div
              class="flex! flex-col! md:flex-row! md:items-end! md:gap-3!"
            >
              <!-- Header card: judul diganti dengan SelectSearch Jenis Laporan -->
              <div class="flex-1!">
                <SelectSearch
                  v-model="activeKey"
                  :options="reportOptions"
                  label="Jenis Laporan"
                  placeholder="Pilih jenis laporan..."
                  icon="file-contract"
                  :searchable="true"
                  search-placeholder="Cari jenis laporan..."
                  no-margin
                />
              </div>
              <BaseButton
                type="button"
                variant="info"
                icon="wand-magic-sparkles"
                class="h-11! px-5! rounded-xl! text-sm! font-bold! shadow-md! shadow-blue-500/20! shrink-0!"
                @click="applyStarter"
              >
                Isi Template 1×3 (Default)
              </BaseButton>
            </div>

            <!-- Snippet toolbar: sisipkan placeholder & variabel ke posisi kursor -->
            <div
              class="flex! flex-wrap! gap-2! p-3! bg-white! border! border-slate-200! rounded-xl!"
            >
              <span
                class="text-[10px]! font-bold! text-slate-500! uppercase! tracking-widest! self-center! mr-2!"
              >
                Sisipkan:
              </span>
              <button
                v-for="(s, i) in snippets"
                :key="i"
                type="button"
                class="px-3! py-1! text-[11px]! font-bold! rounded-lg! bg-white! border! border-slate-200! text-slate-700! hover:bg-blue-50! hover:border-blue-300! hover:text-blue-700! transition-colors!"
                :title="`Sisipkan ${s.label} ke posisi kursor`"
                @click="insertSnippet(s.value)"
              >
                <font-awesome-icon :icon="s.icon" class="mr-1!" />
                {{ s.label }}
              </button>
            </div>

            <!-- PrimeVue Editor (Quill-based) -->
            <div class="signature-wysiwyg-wrapper">
              <PrimeEditor
                v-model="templateDraft"
                editor-style="height: 280px; background: #ffffff; color: #1e293b;"
                :pt="{
                  root: { style: 'border-radius: 12px; overflow: hidden; border: 1px solid #cbd5e1; background: white;' },
                  toolbar: { style: 'background: #f8fafc; border-bottom: 1px solid #e2e8f0;' },
                  content: { style: 'background: #ffffff; color: #1e293b;' },
                  formats: { style: 'background: transparent;' },
                }"
                :modules="editorModules"
                @load="onEditorLoad"
                @text-change="onEditorTextChange"
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
                    <button class="ql-table" aria-label="Sisipkan Tabel" title="Sisipkan Tabel">
                      <svg viewBox="0 0 18 18">
                        <rect class="ql-stroke" x="1.5" y="1.5" width="15" height="15" rx="1" fill="none" stroke="currentColor" stroke-width="1.5" />
                        <line class="ql-stroke" x1="1.5" y1="6" x2="16.5" y2="6" stroke="currentColor" stroke-width="1.5" />
                        <line class="ql-stroke" x1="1.5" y1="12" x2="16.5" y2="12" stroke="currentColor" stroke-width="1.5" />
                        <line class="ql-stroke" x1="6" y1="1.5" x2="6" y2="16.5" stroke="currentColor" stroke-width="1.5" />
                        <line class="ql-stroke" x1="12" y1="1.5" x2="12" y2="16.5" stroke="currentColor" stroke-width="1.5" />
                      </svg>
                    </button>
                    <button class="ql-table-add-row" aria-label="Tambah Baris" title="Tambah Baris">
                      <svg viewBox="0 0 18 18">
                        <rect class="ql-stroke" x="1.5" y="4" width="15" height="10" rx="1" fill="none" stroke="currentColor" stroke-width="1.4" />
                        <line class="ql-stroke" x1="1.5" y1="9" x2="16.5" y2="9" stroke="currentColor" stroke-width="1.4" />
                        <line class="ql-stroke" x1="6" y1="4" x2="6" y2="14" stroke="currentColor" stroke-width="1.4" />
                        <line class="ql-stroke" x1="12" y1="4" x2="12" y2="14" stroke="currentColor" stroke-width="1.4" />
                        <line class="ql-stroke" x1="9" y1="15.5" x2="9" y2="17.5" stroke="currentColor" stroke-width="1.4" />
                        <line class="ql-stroke" x1="8" y1="16.5" x2="10" y2="16.5" stroke="currentColor" stroke-width="1.4" />
                      </svg>
                    </button>
                    <button class="ql-table-add-col" aria-label="Tambah Kolom" title="Tambah Kolom">
                      <svg viewBox="0 0 18 18">
                        <rect class="ql-stroke" x="4" y="1.5" width="10" height="15" rx="1" fill="none" stroke="currentColor" stroke-width="1.4" />
                        <line class="ql-stroke" x1="9" y1="1.5" x2="9" y2="16.5" stroke="currentColor" stroke-width="1.4" />
                        <line class="ql-stroke" x1="4" y1="6" x2="14" y2="6" stroke="currentColor" stroke-width="1.4" />
                        <line class="ql-stroke" x1="4" y1="12" x2="14" y2="12" stroke="currentColor" stroke-width="1.4" />
                        <line class="ql-stroke" x1="15.5" y1="9" x2="17.5" y2="9" stroke="currentColor" stroke-width="1.4" />
                        <line class="ql-stroke" x1="16.5" y1="8" x2="16.5" y2="10" stroke="currentColor" stroke-width="1.4" />
                      </svg>
                    </button>
                    <button class="ql-table-delete" aria-label="Hapus Tabel" title="Hapus Tabel">
                      <svg viewBox="0 0 18 18">
                        <rect class="ql-stroke" x="1.5" y="1.5" width="15" height="15" rx="1" fill="none" stroke="currentColor" stroke-width="1.5" />
                        <line class="ql-stroke" x1="1.5" y1="6" x2="16.5" y2="6" stroke="currentColor" stroke-width="1.5" />
                        <line class="ql-stroke" x1="1.5" y1="12" x2="16.5" y2="12" stroke="currentColor" stroke-width="1.5" />
                        <line class="ql-stroke" x1="6" y1="1.5" x2="6" y2="16.5" stroke="currentColor" stroke-width="1.5" />
                        <line class="ql-stroke" x1="12" y1="1.5" x2="12" y2="16.5" stroke="currentColor" stroke-width="1.5" />
                        <line class="ql-stroke" x1="3.5" y1="3.5" x2="14.5" y2="14.5" stroke="#ef4444" stroke-width="1.8" />
                        <line class="ql-stroke" x1="14.5" y1="3.5" x2="3.5" y2="14.5" stroke="#ef4444" stroke-width="1.8" />
                      </svg>
                    </button>
                  </span>
                  <span class="ql-formats">
                    <button class="ql-link" aria-label="Link"></button>
                    <button class="ql-clean" aria-label="Clear Formatting"></button>
                  </span>
                </template>
              </PrimeEditor>
            </div>

            <!-- Preview HTML rendering — pakai class signature-wysiwyg-wrapper
                 yang sama dengan editor agar tabel cell render dengan border,
                 padding, alignment yang konsisten (mirror visual editor). -->
            <div v-if="previewHtml" class="mt-2!">
              <label
                class="block! text-[10px]! font-bold! text-slate-500! uppercase! tracking-widest! mb-2! ml-1!"
                >Preview</label
              >
              <div
                class="signature-wysiwyg-wrapper signature-preview text-sm! text-slate-800!"
                v-html="previewHtml"
              ></div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Save button -->
    <div class="flex! justify-center md:justify-end! mt-8! md:mt-6!">
      <BaseButton
        type="button"
        variant="primary"
        icon="save"
        :disabled="saving"
        class="w-full! md:w-auto! rounded-xl! shadow-lg! shadow-slate-200!"
        @click="saveTemplates"
      >
        {{ saving ? 'Menyimpan…' : 'Simpan Template' }}
      </BaseButton>
    </div>

    <!-- Modal: SignaturePad -->
    <Teleport to="body">
      <div
        v-if="showPad"
        class="signature-modal-backdrop"
        @click.self="showPad = false"
      >
        <div class="signature-modal">
          <div
            class="flex! items-center! justify-between! p-4! border-b! border-slate-200!"
          >
            <h3 class="font-bold! text-slate-800!">Gambar Tanda Tangan</h3>
            <button
              class="text-slate-500! hover:text-slate-800!"
              @click="showPad = false"
            >
              <font-awesome-icon icon="times" />
            </button>
          </div>
          <div class="p-4!">
            <SignaturePad
              :initial-data-uri="currentImageUrl || ''"
              @save="onPadSave"
              @cancel="showPad = false"
            />
          </div>
        </div>
      </div>
    </Teleport>
  </div>
</template>

<script setup>
import { computed, nextTick, onMounted, ref, watch } from 'vue'
import BaseButton from '@/presentations/components/ui/BaseButton.vue'
import SelectSearch from '@/presentations/components/SelectSearch.vue'
import SignaturePad from '@/presentations/components/settings/SignaturePad.vue'
import signatureService from '@/services/signature.service.js'
import { useNotification } from '@/composables/useNotification'

// Register built-in Quill 2 Table module — tanpa ini, modul 'table' tidak ada
// dan quill.getModule('table') akan throw. Dipakai untuk operasi insert/delete
// tabel dari tombol toolbar custom (ql-table, ql-table-add-row, dll.).
import Quill from 'quill'
import TableModule from 'quill/modules/table'
Quill.register(TableModule, true)

const notification = useNotification()

const loading = ref(false)
const saving = ref(false)
const deleting = ref(false)
const uploading = ref(false)

const reportTypes = ref({})
const templates = ref({})
const images = ref({})
const activeKey = ref('default')
const templateDraft = ref('')
const fileInputRef = ref(null)
const showPad = ref(false)
let quillInstance = null

/**
 * Konfigurasi modules untuk Quill: aktifkan built-in table module (Quill 2.x)
 * sehingga toolbar handler bisa panggil quill.getModule('table').
 * Toolbar button custom (ql-table, ql-table-add-row, ql-table-add-col,
 * ql-table-delete) di-handle via handlers di bawah.
 */
const editorModules = {
  table: true,
}

/**
 * Dipanggil oleh PrimeEditor saat instance Quill siap (event @load).
 * Daftarkan handler custom untuk tombol tabel (ql-table, ql-table-add-row,
 * ql-table-add-col, ql-table-delete) — handler ini akan dipanggil oleh Quill
 * toolbar ketika tombol diklik. Kita implementasi pakai Module Table bawaan
 * Quill 2 (modules/table.js) yang sudah aktif via editorModules.table = true.
 */
function onEditorLoad(event) {
  const quill = event?.quill || findQuill()
  if (!quill) return
  quillInstance = quill

  // Daftarkan toolbar handlers
  const toolbar = quill.getModule('toolbar')
  if (!toolbar) return

  // Sisipkan tabel baru (default 2 baris × 3 kolom — match starter 1×3)
  toolbar.addHandler('table', () => insertTablePrompt(quill, 3, 2))

  // Tambah baris/kolom dari posisi kursor
  toolbar.addHandler('table-add-row', () => {
    const tableMod = quill.getModule('table')
    if (!tableMod) return
    try {
      tableMod.insertRowBelow()
      syncTemplateFromQuill(quill)
    } catch (_) {
      /* kursor tidak dalam tabel, abaikan */
    }
  })
  toolbar.addHandler('table-add-col', () => {
    const tableMod = quill.getModule('table')
    if (!tableMod) return
    try {
      tableMod.insertColumnRight()
      syncTemplateFromQuill(quill)
    } catch (_) {
      /* kursor tidak dalam tabel, abaikan */
    }
  })

  // Hapus tabel pada posisi kursor
  toolbar.addHandler('table-delete', () => {
    const tableMod = quill.getModule('table')
    if (!tableMod) return
    try {
      tableMod.deleteTable()
      syncTemplateFromQuill(quill)
    } catch (_) {
      /* kursor tidak dalam tabel, abaikan */
    }
  })
}

/**
 * Sinkronkan templateDraft dari innerHTML instance Quill (Quill tidak
 * otomatis update v-model PrimeEditor saat konten berubah via module API).
 */
function syncTemplateFromQuill(quill) {
  if (!quill) return
  templateDraft.value = quill.root.innerHTML
}

/**
 * Tampilkan prompt sederhana untuk dimensi tabel (rows × cols), lalu insert.
 * Default 3 kolom × 2 baris cocok untuk layout tanda tangan 1×3.
 */
function insertTablePrompt(quill, defaultCols = 3, defaultRows = 2) {
  const tableMod = quill.getModule('table')
  if (!tableMod) return
  // Pakai window.prompt — cukup untuk admin tool ini (PrimeVue Dialog akan
  // terlalu berat untuk use case ini). Validasi numeric 1-20.
  const colsRaw = window.prompt('Jumlah kolom tabel (1–20):', String(defaultCols))
  if (colsRaw === null) return
  const cols = Math.max(1, Math.min(20, parseInt(colsRaw, 10) || defaultCols))
  const rowsRaw = window.prompt('Jumlah baris tabel (1–20):', String(defaultRows))
  if (rowsRaw === null) return
  const rows = Math.max(1, Math.min(20, parseInt(rowsRaw, 10) || defaultRows))

  try {
    // Jika kursor ada di dalam tabel existing, insert baris/kolom saja.
    // Quill Table module hanya insert tabel baru jika kursor di luar tabel.
    const sel = quill.getSelection(true)
    if (sel) {
      const [line] = quill.getLine(sel.index)
      const isInTable = line && line.statics && line.statics.blotName === 'table'
      if (isInTable) {
        const delta = new Array(cols).fill('\n').join('')
        // Sisipkan kolom di posisi cell saat ini
        tableMod.insertColumnRight()
        // Sisipkan baris di posisi cell saat ini
        tableMod.insertRowBelow()
        syncTemplateFromQuill(quill)
        return
      }
    }
    // Insert tabel baru
    quill.focus()
    tableMod.insertTable(rows, cols)
    syncTemplateFromQuill(quill)
  } catch (err) {
    console.warn('[SignatureSop] Gagal insert tabel:', err)
  }
}

/**
 * Dipanggil oleh PrimeEditor setiap konten Quill berubah. Sinkronkan ke
 * templateDraft (PrimeEditor tidak otomatis emit update:modelValue untuk
 * perubahan via module API — hanya untuk user input keyboard).
 */
function onEditorTextChange() {
  const quill = quillInstance || findQuill()
  if (!quill) return
  // Hindari loop: hanya sync jika innerHTML berbeda dari model.
  if (templateDraft.value !== quill.root.innerHTML) {
    templateDraft.value = quill.root.innerHTML
  }
}

const reportOptions = computed(() => {
  return Object.entries(reportTypes.value).map(([value, label]) => ({
    value,
    label,
  }))
})

const activeLabel = computed(() => {
  return reportTypes.value?.[activeKey.value] || activeKey.value
})

const currentImageUrl = computed(() => {
  return images.value?.[activeKey.value] || null
})

/**
 * Snippet yang bisa disisipkan via tombol ke posisi kursor editor.
 * - {ttd_image} → diganti gambar saat render oleh backend
 * - {{ var }} → placeholder naratif yang bisa diisi kemudian
 */
const snippets = [
  { label: '{ttd_image}', value: '{ttd_image}', icon: 'pen-nib' },
  { label: 'Tgl Cetak', value: '{{ tanggal_cetak }}', icon: 'calendar' },
  { label: 'Nama Lembaga', value: '{{ lembagaNama }}', icon: 'building' },
  { label: 'Alamat', value: '{{ lembagaAlamat }}', icon: 'map-marker-alt' },
  { label: 'Peraturan Desa', value: '{{ peraturanDesa }}', icon: 'file-contract' },
  { label: 'SK Kemenkumham', value: '{{ skKemenkumham }}', icon: 'stamp' },
]

/**
 * Preview HTML: backend-side substitution tidak mungkin di sini
 * (image disimpan sebagai path server, bukan data URI). Kita tampilkan
 * template apa adanya, lalu user tahu ini hanya preview struktur.
 */
const previewHtml = computed(() => templateDraft.value || '')

function loadAll() {
  loading.value = true
  signatureService
    .getAll()
    .then((res) => {
      const data = res?.data || {}
      reportTypes.value = data.report_types || {}
      templates.value = data.templates || {}
      images.value = data.images || {}
      // Set initial draft dari server (untuk activeKey saat ini)
      const initial = templates.value[activeKey.value] || ''
      templateDraft.value = initial
      // Sinkronkan ke instance Quill setelah mount DOM
      nextTick(() => {
        const quill = findQuill()
        if (quill && typeof quill.clipboard?.dangerouslyPasteHTML === 'function') {
          quill.clipboard.dangerouslyPasteHTML(initial)
        }
      })
    })
    .catch((err) => {
      notification.error(
        'Gagal memuat',
        err?.response?.data?.message || err.message || 'Terjadi kesalahan.',
      )
    })
    .finally(() => {
      loading.value = false
    })
}

onMounted(loadAll)

// Sync draft ketika ganti jenis laporan
watch(activeKey, async (newKey) => {
  const html = templates.value[newKey] || ''
  templateDraft.value = html
  await nextTick()
  const quill = findQuill()
  if (quill && typeof quill.clipboard?.dangerouslyPasteHTML === 'function') {
    quill.clipboard.dangerouslyPasteHTML(html)
  }
})

function saveTemplates() {
  saving.value = true
  // Kirim SELURUH template (semua report_key), seperti SIUPK-Next
  const payload = { templates: { ...templates.value, [activeKey.value]: templateDraft.value } }
  signatureService
    .saveTemplates(payload)
    .then((res) => {
      const updated = res?.data?.templates
      if (updated) templates.value = updated
      notification.success(
        'Tersimpan',
        'Template tanda tangan berhasil disimpan.',
      )
    })
    .catch((err) => {
      notification.error(
        'Gagal menyimpan',
        err?.response?.data?.message || err.message || 'Terjadi kesalahan.',
      )
    })
    .finally(() => {
      saving.value = false
    })
}

function triggerUpload() {
  fileInputRef.value?.click()
}

function onFileSelected(e) {
  const file = e.target.files?.[0]
  if (!file) return
  const reader = new FileReader()
  reader.onload = (ev) => {
    uploadDataUri(String(ev.target?.result || ''))
  }
  reader.readAsDataURL(file)
  e.target.value = ''
}

function onPadSave(dataUri) {
  showPad.value = false
  uploadDataUri(dataUri)
}

function uploadDataUri(dataUri) {
  if (!dataUri) return
  uploading.value = true
  signatureService
    .uploadImage(activeKey.value, dataUri)
    .then((res) => {
      const url = res?.data?.image_url
      if (url) {
        images.value = { ...images.value, [activeKey.value]: url }
      }
      notification.success(
        'Berhasil',
        'Tanda tangan gambar berhasil diunggah.',
      )
    })
    .catch((err) => {
      notification.error(
        'Gagal unggah',
        err?.response?.data?.message || err.message || 'Terjadi kesalahan.',
      )
    })
    .finally(() => {
      uploading.value = false
    })
}

async function confirmDeleteImage() {
  if (!currentImageUrl.value) return
  const ok = await notification.confirm({
    title: 'Hapus Tanda Tangan',
    message:
      'Hapus tanda tangan gambar untuk jenis laporan ' +
      activeLabel.value +
      '?',
    type: 'warning',
    confirmText: 'Hapus',
    cancelText: 'Batal',
  })
  if (!ok) return

  deleting.value = true
  signatureService
    .deleteImage(activeKey.value)
    .then(() => {
      images.value = { ...images.value, [activeKey.value]: null }
      notification.success('Berhasil', 'Tanda tangan gambar dihapus.')
    })
    .catch((err) => {
      notification.error(
        'Gagal',
        err?.response?.data?.message || err.message || 'Terjadi kesalahan.',
      )
    })
    .finally(() => {
      deleting.value = false
    })
}

function applyStarter() {
  const quill = quillInstance || findQuill()
  if (!quill) {
    setEditorHtml(starterTableHtml())
    return
  }
  // Reset editor
  quill.focus()
  quill.setSelection(0, quill.getLength(), 'user')
  quill.deleteText(0, quill.getLength(), 'user')
  quill.setSelection(0, 0, 'silent')

  const tableMod = quill.getModule('table')
  if (!tableMod) {
    setEditorHtml(starterTableHtml())
    return
  }

  // Insert tabel 1 baris × 3 kolom via Quill Table API
  try {
    tableMod.insertTable(1, 3)
  } catch (err) {
    console.warn('[SignatureSop] Gagal insert tabel starter:', err)
    setEditorHtml(starterTableHtml())
    return
  }

  // Isi tiap cell dengan Delta langsung. Pendekatan ini LEBIH RELIABLE dari
  // pasteHTML karena Quill Table cell adalah Block — multi-line di dalam cell
  // harus berupa SATU Block (paragraph) dengan karakter newline (\n), bukan
  // multiple <p>. Newline di dalam Delta Block akan di-render jadi <br> visual.
  //
  // Layout per cell (3 baris visual dalam 1 Block):
  //   line 1: label jabatan bold center
  //   line 2: <br> kosong untuk tanda tangan
  //   line 3: placeholder nama bold center
  const defaults = [
    { label: 'Mengetahui,', name: '( ........................ )' },
    { label: 'Dibuat oleh,', name: '( ........................ )' },
    { label: 'Disetujui,', name: '( ........................ )' },
  ]

  // Ambil DOM cell dari Quill editor
  const cells = Array.from(quill.root.querySelectorAll('td[data-row]'))
  cells.forEach((cell, idx) => {
    const data = defaults[idx]
    if (!data) return
    // Dapatkan posisi absolute cell di dokumen Quill
    // eslint-disable-next-line no-undef
    const cellBlot = Quill.find(cell)
    if (!cellBlot) return
    const cellStart = cellBlot.offset(quill.scroll)
    if (cellStart == null) return
    const cellLength = cellBlot.length()
    // Hapus konten default cell (1 newline char)
    if (cellLength > 0) {
      quill.deleteText(cellStart, cellLength, 'user')
    }
    // Insert: label (bold) + newline kosong + nama (bold). Format center align.
    // 2 newline = 1 baris kosong di antara label dan nama.
    const fullText = `${data.label}\n\n${data.name}`
    quill.insertText(cellStart, fullText, { bold: true, align: 'center' }, 'user')
  })

  // Sync ke templateDraft
  syncTemplateFromQuill(quill)
  quill.setSelection(0, 0, 'silent')
}

/**
 * Fallback HTML untuk starter template (dipakai kalau Quill Table module
 * belum ready atau runtime error). HTML ini pakai align="center" di <td>
 * yang diizinkan oleh backend cleanAttributes, dan tidak ada style attribute
 * supaya backend tidak drop width constraint.
 */
function starterTableHtml() {
  return `<table>
<tbody>
<tr>
<td align="center"><p><strong>Mengetahui,</strong><br><br><strong>( ........................ )</strong></p></td>
<td align="center"><p><strong>Dibuat oleh,</strong><br><br><strong>( ........................ )</strong></p></td>
<td align="center"><p><strong>Disetujui,</strong><br><br><strong>( ........................ )</strong></p></td>
</tr>
</tbody>
</table>`
}

/**
 * Cari instance Quill yang dipakai oleh PrimeEditor. PrimeEditor menyimpan
 * instance di DOM element container via property private "__quill".
 * Pendekatan ini mirror dari CalkSop.vue (insertVariable).
 */
function findQuill() {
  const wrapper = document.querySelector('.signature-wysiwyg-wrapper .ql-editor')
  if (!wrapper) return null
  const container = wrapper.parentElement
  if (!container) return null
  // PrimeVue Editor attach instance di parent (ql-container) via __quill
  return container.__quill || null
}

async function setEditorHtml(html) {
  templateDraft.value = html || ''
  // Tunggu DOM update lalu sinkronkan ke instance Quill (jika ada)
  await nextTick()
  const quill = findQuill()
  if (quill && typeof quill.clipboard?.dangerouslyPasteHTML === 'function') {
    quill.clipboard.dangerouslyPasteHTML(html || '')
  }
}

/**
 * Sisipkan snippet text pada posisi kursor editor (Quill).
 * Fallback: append ke akhir model jika instance tidak terdeteksi.
 */
function insertSnippet(value) {
  const quill = findQuill()
  if (quill && typeof quill.focus === 'function') {
    quill.focus()
    const range = quill.getSelection(true)
    const index = range ? range.index : quill.getLength() - 1
    quill.insertText(index, ' ' + value + ' ', 'user')
    quill.setSelection(index + value.length + 2)
    // Sinkronkan v-model karena Quill tidak otomatis update model v-model PrimeEditor
    templateDraft.value = quill.root.innerHTML
    return
  }
  // Fallback: append ke akhir
  templateDraft.value = (templateDraft.value || '') + ' ' + value + ' '
}
</script>

<style>
.signature-modal-backdrop {
  position: fixed;
  inset: 0;
  background: rgba(15, 23, 42, 0.5);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 9999;
  padding: 16px;
}

.signature-modal {
  background: #fff;
  border-radius: 12px;
  max-width: 640px;
  width: 100%;
  max-height: 90vh;
  overflow-y: auto;
  box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
}

/* ===== PrimeVue Editor (Quill) tema slate + blue-500 =====
   Pakai <style> biasa (bukan scoped) agar selector descendant .ql-* / .p-editor-*
   kena DOM yang di-render oleh Quill/PrimeVue JS (tidak dapat data-v attribute).
   Class .signature-wysiwyg-wrapper sudah spesifik sehingga tidak konflik global. */
.signature-wysiwyg-wrapper {
  border-radius: 12px !important;
  overflow: hidden !important;
  border: 1px solid #cbd5e1 !important;
  background: #ffffff !important;
}
.signature-wysiwyg-wrapper,
.signature-wysiwyg-wrapper .ql-toolbar.ql-snow,
.signature-wysiwyg-wrapper .ql-container.ql-snow,
.signature-wysiwyg-wrapper .ql-editor,
.signature-wysiwyg-wrapper .p-editor,
.signature-wysiwyg-wrapper .p-editor-content,
.signature-wysiwyg-wrapper .p-editor-toolbar {
  background: #ffffff !important;
  color: #1e293b !important;
}

.signature-wysiwyg-wrapper .ql-toolbar.ql-snow {
  border: 0 !important;
  border-bottom: 1px solid #e2e8f0 !important;
  padding: 8px 12px !important;
  font-family: inherit !important;
  border-top-left-radius: 12px !important;
  border-top-right-radius: 12px !important;
}
.signature-wysiwyg-wrapper .ql-container.ql-snow {
  border: 0 !important;
  font-size: 13px !important;
  font-family: inherit !important;
  border-bottom-left-radius: 12px !important;
  border-bottom-right-radius: 12px !important;
}
.signature-wysiwyg-wrapper .ql-editor {
  min-height: 280px !important;
  padding: 14px 18px !important;
  line-height: 1.6 !important;
}
.signature-wysiwyg-wrapper .ql-editor.ql-blank::before {
  font-style: italic !important;
  color: #94a3b8 !important;
  left: 18px !important;
}
.signature-wysiwyg-wrapper .ql-snow .ql-stroke {
  stroke: #475569 !important;
}
.signature-wysiwyg-wrapper .ql-snow .ql-fill,
.signature-wysiwyg-wrapper .ql-snow .ql-stroke.ql-fill {
  fill: #475569 !important;
}
.signature-wysiwyg-wrapper .ql-snow .ql-picker {
  color: #475569 !important;
}
.signature-wysiwyg-wrapper .ql-snow .ql-picker-label {
  color: #475569 !important;
}
.signature-wysiwyg-wrapper .ql-snow .ql-picker-options {
  background-color: #ffffff !important;
  color: #1e293b !important;
}
.signature-wysiwyg-wrapper .ql-snow.ql-toolbar button:hover,
.signature-wysiwyg-wrapper .ql-snow.ql-toolbar button.ql-active,
.signature-wysiwyg-wrapper .ql-snow.ql-toolbar .ql-picker-label:hover,
.signature-wysiwyg-wrapper .ql-snow.ql-toolbar .ql-picker-label.ql-active,
.signature-wysiwyg-wrapper .ql-snow.ql-toolbar .ql-picker-item:hover,
.signature-wysiwyg-wrapper .ql-snow.ql-toolbar .ql-picker-item.ql-selected {
  color: #2563eb !important;
}
.signature-wysiwyg-wrapper .ql-snow.ql-toolbar button:hover .ql-stroke,
.signature-wysiwyg-wrapper .ql-snow.ql-toolbar button.ql-active .ql-stroke,
.signature-wysiwyg-wrapper .ql-snow.ql-toolbar .ql-picker-label:hover .ql-stroke,
.signature-wysiwyg-wrapper .ql-snow.ql-toolbar .ql-picker-label.ql-active .ql-stroke,
.signature-wysiwyg-wrapper .ql-snow.ql-toolbar .ql-picker-item:hover .ql-stroke,
.signature-wysiwyg-wrapper .ql-snow.ql-toolbar .ql-picker-item.ql-selected .ql-stroke {
  stroke: #2563eb !important;
}
.signature-wysiwyg-wrapper .ql-snow.ql-toolbar button:hover .ql-fill,
.signature-wysiwyg-wrapper .ql-snow.ql-toolbar button.ql-active .ql-fill,
.signature-wysiwyg-wrapper .ql-snow.ql-toolbar .ql-picker-label:hover .ql-fill,
.signature-wysiwyg-wrapper .ql-snow.ql-toolbar .ql-picker-label.ql-active .ql-fill,
.signature-wysiwyg-wrapper .ql-snow.ql-toolbar .ql-picker-item:hover .ql-fill,
.signature-wysiwyg-wrapper .ql-snow.ql-toolbar .ql-picker-item.ql-selected .ql-fill {
  fill: #2563eb !important;
}
.signature-wysiwyg-wrapper .ql-snow.ql-toolbar select:hover {
  border-color: #60a5fa !important;
  color: #2563eb !important;
}
.signature-wysiwyg-wrapper .ql-snow .ql-tooltip {
  background-color: #ffffff !important;
  color: #1e293b !important;
  border: 1px solid #e2e8f0 !important;
  box-shadow: 0 4px 12px rgba(15, 23, 42, 0.1) !important;
}
.signature-wysiwyg-wrapper .ql-snow .ql-tooltip input[type="text"] {
  border: 1px solid #cbd5e1 !important;
  color: #1e293b !important;
  background: #ffffff !important;
}

/* ===== Table styling (Quill 2 table module) =====
   Layout tabel signature: 1 baris × 3 kolom. Editor menampilkan border
   tegas supaya 3 kotak kelihatan jelas sebagai kolom terpisah. */
.signature-wysiwyg-wrapper .ql-editor table,
.signature-wysiwyg-wrapper > table,
.signature-wysiwyg-wrapper table {
  border-collapse: collapse !important;
  width: 100% !important;
  table-layout: fixed !important;
  margin: 0 !important;
}
.signature-wysiwyg-wrapper .ql-editor table,
.signature-wysiwyg-wrapper .ql-editor table td,
.signature-wysiwyg-wrapper .ql-editor table th,
.signature-wysiwyg-wrapper > table,
.signature-wysiwyg-wrapper > table td,
.signature-wysiwyg-wrapper > table th,
.signature-wysiwyg-wrapper table,
.signature-wysiwyg-wrapper table td,
.signature-wysiwyg-wrapper table th {
  border: 1px solid #94a3b8 !important;
  padding: 14px 16px !important;
  vertical-align: top !important;
  word-break: break-word !important;
  overflow-wrap: anywhere !important;
}
.signature-wysiwyg-wrapper .ql-editor table td,
.signature-wysiwyg-wrapper table td {
  min-width: 80px !important;
  height: 140px !important;
}
/* Tampilan preview mirror editor — TANPA border tabel/cell, hanya
   background subtle + typography rapi. Tabel tetap kelihatan sebagai
   layout 3 kolom karena tiap cell punya align center & padding, tapi
   tidak ada garis kotak yang mengganggu. */
.signature-wysiwyg-wrapper.signature-preview,
.signature-wysiwyg-wrapper.signature-preview table,
.signature-wysiwyg-wrapper.signature-preview tbody,
.signature-wysiwyg-wrapper.signature-preview tr,
.signature-wysiwyg-wrapper.signature-preview td,
.signature-wysiwyg-wrapper.signature-preview th {
  border: 0 !important;
}
.signature-wysiwyg-wrapper.signature-preview {
  border-radius: 12px !important;
  background: #f8fafc !important;
  font-size: 13px !important;
  line-height: 1.7 !important;
  min-height: 240px !important;
  padding: 24px !important;
}
.signature-wysiwyg-wrapper.signature-preview p {
  margin: 0 !important;
  padding: 0 !important;
}
.signature-wysiwyg-wrapper.signature-preview table,
.signature-wysiwyg-wrapper.signature-preview tbody,
.signature-wysiwyg-wrapper.signature-preview tr,
.signature-wysiwyg-wrapper.signature-preview td,
.signature-wysiwyg-wrapper.signature-preview th {
  background: transparent !important;
  padding: 4px 12px !important;
}
</style>