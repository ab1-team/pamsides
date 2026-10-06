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

    <!-- Section: Template Tanda Tangan (editor tabel tanda tangan) -->
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
                Isi Template 1├ù3 (Default)
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

            <!-- Editor tabel tanda tangan (TipTap).
                 :key wajib supaya instance di-destroy & dibuat ulang saat
                 ganti jenis laporan — konten TipTap hanya di-init sekali. -->
            <AppRichEditor
              :key="activeKey"
              v-model="templateDraft"
              placeholder="Sisipkan tabel penandatangan (ikon tabel di toolbar)…"
            />

            <!-- Preview HTML hasil template — mirror styling editor. -->
            <div v-if="previewHtml" class="mt-2!">
              <label
                class="block! text-[10px]! font-bold! text-slate-500! uppercase! tracking-widest! mb-2! ml-1!"
                >Preview</label
              >
              <div class="signature-preview" v-html="previewHtml"></div>
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
        {{ saving ? 'MenyimpanΓÇª' : 'Simpan Template' }}
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
import { computed, onMounted, ref, watch } from 'vue'
import BaseButton from '@/presentations/components/ui/BaseButton.vue'
import SelectSearch from '@/presentations/components/SelectSearch.vue'
import AppRichEditor from '@/presentations/components/AppRichEditor.vue'
import SignaturePad from '@/presentations/components/settings/SignaturePad.vue'
import signatureService from '@/services/signature.service.js'
// Notifikasi section ini memakai SweetAlert2 toast (pojok kanan atas),
// sama seperti section SOP lainnya — bukan modal AppNotification di tengah.
import { MySwal, showErrorToast, showSuccessToast } from '@/utils/swal'

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
 * Snippet yang bisa disisipkan ke posisi kursor editor.
 * - {ttd_image} → diganti gambar tanda tangan oleh backend saat render
 * - {{ var }} → placeholder naratif
 */
const snippets = [
  { label: 'Tgl Cetak', value: '{{ tanggal_cetak }}', icon: 'calendar' },
  { label: 'Nama Lembaga', value: '{{ lembagaNama }}', icon: 'building' },
  { label: 'Alamat', value: '{{ lembagaAlamat }}', icon: 'map-marker-alt' },
  { label: 'Peraturan Desa', value: '{{ peraturanDesa }}', icon: 'file-contract' },
  { label: 'SK Kemenkumham', value: '{{ skKemenkumham }}', icon: 'stamp' },
]

/**
 * Template tanda tangan bawaan: tabel 1 BARIS x 3 KOLOM (3 kotak).
 * Tiap kotak berisi jabatan → ruang tanda tangan → nama, dipisah paragraf.
 *
 * Paragraf kosong `<p><br><br><br></p>` di tengah setiap kotak adalah target
 * penyisipan gambar tanda tangan oleh backend
 * (SignatureService::renderForReport). Menyesuaikan dengan starter HTML
 * aplikasi siupk-next (SignatureTemplateService::starterHtml).
 *
 * Catatan perbedaan dari siupk-next: sanitizer backend pamsides-v2
 * (SignatureService::cleanAttributes) membuang seluruh atribut style, jadi
 * width 33% + text-align:center diganti memakai align="center" yang
 * memang diizinkan backend. Editor tetap menampilkan 3 kotak sama
 * lebar lewat CSS table-layout: fixed. */
function starterHtml() {
  const box = (label) =>
    `<td align="center"><p>${label}</p><p><br><br><br></p><p><strong>( ........................ )</strong></p></td>`
  return `<table><tbody><tr>${box('Mengetahui,')}${box(
    'Dibuat oleh,',
  )}${box('Disetujui,')}</tr></tbody></table>`
}

/** Isi ulang template dengan layout 3 kotak bawaan. */
function applyStarter() {
  templateDraft.value = starterHtml()
}

/**
 * Sisipkan snippet di posisi kursor di dalam editor.
 * Editor TipTap bukan komponen yang bisa diimpor instance-nya lewat ref,
 * jadi sisipkan lewat Selection + execCommand agar menyatu dengan undo stack
 * ProseMirror. Falls back ke menyisipkan di akhir template.
 */
function insertSnippet(value) {
  const editorEl = document.querySelector('.rich-editor .tiptap')
  if (!editorEl) {
    templateDraft.value = (templateDraft.value || '') + ' ' + value + ' '
    return
  }

  editorEl.focus()
  const selection = window.getSelection()
  if (!selection || selection.rangeCount === 0 || !editorEl.contains(selection.anchorNode)) {
    // Kursor tidak ada di dalam editor -> taruh di akhir isi editor.
    const range = document.createRange()
    range.selectNodeContents(editorEl)
    range.collapse(false)
    selection.removeAllRanges()
    selection.addRange(range)
  }
  selection.collapseToEnd()
  document.execCommand('insertText', false, ' ' + value + ' ')
  editorEl.dispatchEvent(new Event('input', { bubbles: true }))
}

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
      const current = templates.value[activeKey.value] || ''
      templateDraft.value = current || starterHtml()
    })
    .catch((err) => {
      showErrorToast(err)
    })
    .finally(() => {
      loading.value = false
    })
}

onMounted(loadAll)

// Muat template milik jenis laporan yang dipilih. Instance editor di-recreate
// oleh :key="activeKey" pada AppRichEditor.
watch(activeKey, (newKey) => {
  const current = templates.value[newKey] || ''
  templateDraft.value = current || starterHtml()
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
      showSuccessToast('Template tanda tangan berhasil disimpan.')
    })
    .catch((err) => {
      showErrorToast(err)
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
      showSuccessToast('Tanda tangan gambar berhasil diunggah.')
    })
    .catch((err) => {
      showErrorToast(err)
    })
    .finally(() => {
      uploading.value = false
    })
}

async function confirmDeleteImage() {
  if (!currentImageUrl.value) return
  const result = await MySwal.fire({
    title: 'Hapus Tanda Tangan',
    text: 'Hapus tanda tangan gambar untuk jenis laporan ' + activeLabel.value + '?',
    icon: 'warning',
    showCancelButton: true,
    confirmButtonText: 'Hapus',
    cancelButtonText: 'Batal',
    confirmButtonColor: '#ef4444',
  })
  if (!result.isConfirmed) return

  deleting.value = true
  signatureService
    .deleteImage(activeKey.value)
    .then(() => {
      images.value = { ...images.value, [activeKey.value]: null }
      showSuccessToast('Tanda tangan gambar dihapus.')
    })
    .catch((err) => {
      showErrorToast(err)
    })
    .finally(() => {
      deleting.value = false
    })
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
</style>
