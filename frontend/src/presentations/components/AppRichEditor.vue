<script setup>
/**
 * Rich editor generik berbasis TipTap (ProseMirror).
 *
 * Dipakai untuk template tanda tangan laporan. Editor sengaja dibuat
 * "kotak-saja": tabel 1 baris x 3 kolom bisa menampung beberapa paragraf di
 * dalam satu sel (berbeda dengan TableCell di Quill yang hanya bisa satu
 * baris teks), sehingga jabatan + ruang tanda tangan + nama bisa berada di
 * dalam satu kotak dan tetap bisa diedit langsung.
 *
 * Konfigurasi meniru AppRichEditor.vue pada aplikasi siupk-next:
 * heading dimatikan, table resizable: false, toolbar tombol tabel menyisipkan
 * tabel 1x3 tanpa prompt.
 */
import { onBeforeUnmount, watch } from 'vue'
import { EditorContent, useEditor } from '@tiptap/vue-3'
import StarterKit from '@tiptap/starter-kit'
import Underline from '@tiptap/extension-underline'
import TextAlign from '@tiptap/extension-text-align'
import Placeholder from '@tiptap/extension-placeholder'
import { Table, TableRow, TableCell, TableHeader } from '@tiptap/extension-table'

const props = defineProps({
  modelValue: { type: String, default: '' },
  placeholder: { type: String, default: 'Tulis konten...' },
  disabled: { type: Boolean, default: false },
})

const emit = defineEmits(['update:modelValue'])

const editor = useEditor({
  content: props.modelValue || '',
  editable: !props.disabled,
  extensions: [
    StarterKit.configure({ heading: false }),
    Underline,
    TextAlign.configure({ types: ['heading', 'paragraph'] }),
    Placeholder.configure({ placeholder: props.placeholder }),
    Table.configure({ resizable: false }),
    TableRow,
    TableHeader,
    TableCell,
  ],
  onUpdate: ({ editor: ed }) => {
    emit('update:modelValue', ed.getHTML())
  },
  editorProps: {
    attributes: {
      class: 'rich-editor__content',
    },
  },
})

watch(
  () => props.modelValue,
  (value) => {
    if (!editor.value) return
    const current = editor.value.getHTML()
    if (value !== current) {
      editor.value.commands.setContent(value || '', false)
    }
  },
)

watch(
  () => props.disabled,
  (disabled) => {
    editor.value?.setEditable(!disabled)
  },
)

onBeforeUnmount(() => {
  editor.value?.destroy()
})

function run(fn) {
  if (!editor.value || props.disabled) return
  fn(editor.value.chain().focus())
}

/**
 * Sisipkan tabel tanda tangan 1 BARIS x 3 KOLOM dalam satu klik.
 * Bila kursor sudah di dalam sebuah tabel, tombol ini menambah satu kolom
 * di sebelah kanan (tidak membuat tabel bersarang).
 */
function insertSignatureTable() {
  if (!editor.value || props.disabled) return
  if (editor.value.isActive('table')) {
    editor.value.chain().focus().addColumnAfter().run()
    return
  }
  editor.value
    .chain()
    .focus()
    .insertTable({ rows: 1, cols: 3, withHeaderRow: false })
    .run()
}
</script>

<template>
  <div class="rich-editor" :class="{ 'is-disabled': disabled }">
    <div
      v-if="editor"
      class="rich-editor__toolbar"
      role="toolbar"
      aria-label="Format teks"
    >
      <button
        type="button"
        class="rich-editor__btn"
        :class="{ 'is-active': editor.isActive('bold') }"
        title="Tebal"
        :disabled="disabled"
        @click="run((c) => c.toggleBold().run())"
      >
        <font-awesome-icon icon="bold" class="text-base!" />
      </button>
      <button
        type="button"
        class="rich-editor__btn"
        :class="{ 'is-active': editor.isActive('italic') }"
        title="Miring"
        :disabled="disabled"
        @click="run((c) => c.toggleItalic().run())"
      >
        <font-awesome-icon icon="italic" class="text-base!" />
      </button>
      <button
        type="button"
        class="rich-editor__btn"
        :class="{ 'is-active': editor.isActive('underline') }"
        title="Garis bawah"
        :disabled="disabled"
        @click="run((c) => c.toggleUnderline().run())"
      >
        <font-awesome-icon icon="underline" class="text-base!" />
      </button>
      <span class="rich-editor__sep" />

      <button
        type="button"
        class="rich-editor__btn"
        :class="{ 'is-active': editor.isActive({ textAlign: 'left' }) }"
        title="Rata kiri"
        :disabled="disabled"
        @click="run((c) => c.setTextAlign('left').run())"
      >
        <font-awesome-icon icon="align-left" class="text-base!" />
      </button>
      <button
        type="button"
        class="rich-editor__btn"
        :class="{ 'is-active': editor.isActive({ textAlign: 'center' }) }"
        title="Rata tengah"
        :disabled="disabled"
        @click="run((c) => c.setTextAlign('center').run())"
      >
        <font-awesome-icon icon="align-center" class="text-base!" />
      </button>
      <button
        type="button"
        class="rich-editor__btn"
        :class="{ 'is-active': editor.isActive({ textAlign: 'right' }) }"
        title="Rata kanan"
        :disabled="disabled"
        @click="run((c) => c.setTextAlign('right').run())"
      >
        <font-awesome-icon icon="align-right" class="text-base!" />
      </button>
      <span class="rich-editor__sep" />

      <button
        type="button"
        class="rich-editor__btn"
        :class="{ 'is-active': editor.isActive('bulletList') }"
        title="Daftar"
        :disabled="disabled"
        @click="run((c) => c.toggleBulletList().run())"
      >
        <font-awesome-icon icon="list-ul" class="text-base!" />
      </button>
      <button
        type="button"
        class="rich-editor__btn"
        :class="{ 'is-active': editor.isActive('orderedList') }"
        title="Daftar bernomor"
        :disabled="disabled"
        @click="run((c) => c.toggleOrderedList().run())"
      >
        <font-awesome-icon icon="list-ol" class="text-base!" />
      </button>
      <span class="rich-editor__sep" />

      <!-- Tabel tanda tangan 1x3 -->
      <button
        type="button"
        class="rich-editor__btn"
        title="Sisipkan tabel tanda tangan (1x3)"
        :disabled="disabled"
        @click="insertSignatureTable"
      >
        <font-awesome-icon icon="table" class="text-base!" />
      </button>
      <button
        type="button"
        class="rich-editor__btn"
        title="Tambah baris"
        :disabled="disabled || !editor.can().addRowAfter()"
        @click="run((c) => c.addRowAfter().run())"
      >
        <font-awesome-icon icon="plus" class="text-base!" />
      </button>
      <button
        type="button"
        class="rich-editor__btn"
        title="Hapus tabel"
        :disabled="disabled || !editor.can().deleteTable()"
        @click="run((c) => c.deleteTable().run())"
      >
        <font-awesome-icon icon="trash" class="text-base!" />
      </button>
      <span class="rich-editor__sep" />

      <button
        type="button"
        class="rich-editor__btn"
        title="Undo"
        :disabled="disabled || !editor.can().undo()"
        @click="run((c) => c.undo().run())"
      >
        <font-awesome-icon icon="rotate-left" class="text-base!" />
      </button>
      <button
        type="button"
        class="rich-editor__btn"
        title="Redo"
        :disabled="disabled || !editor.can().redo()"
        @click="run((c) => c.redo().run())"
      >
        <font-awesome-icon icon="rotate-right" class="text-base!" />
      </button>
    </div>
    <EditorContent :editor="editor" />
  </div>
</template>

<style>
/* ===== TipTap rich editor =====
   Meniru AppRichEditor.vue pada aplikasi siupk-next. Class .rich-editor
   dipakai juga untuk preview HTML hasil template di section tanda tangan. */
.rich-editor {
  overflow: hidden;
  border: 1px solid #cbd5e1;
  border-radius: 12px;
  background: #ffffff;
}

.rich-editor.is-disabled {
  opacity: 0.65;
  pointer-events: none;
}

.rich-editor__toolbar {
  display: flex;
  flex-wrap: wrap;
  gap: 4px;
  border-bottom: 1px solid #e2e8f0;
  background: #f8fafc;
  padding: 8px;
}

.rich-editor__btn {
  display: grid;
  place-items: center;
  width: 32px;
  height: 32px;
  border-radius: 8px;
  color: #64748b;
  cursor: pointer;
  transition:
    background-color 120ms ease,
    color 120ms ease;
}
.rich-editor__btn:hover:not(:disabled) {
  background: #e2e8f0;
  color: #2563eb;
}
.rich-editor__btn.is-active {
  background: #dbeafe;
  color: #2563eb;
}
.rich-editor__btn:disabled {
  cursor: not-allowed;
  opacity: 0.4;
}

.rich-editor__sep {
  width: 1px;
  margin: 0 4px;
  align-self: stretch;
  background: #e2e8f0;
}

.rich-editor .tiptap,
.rich-editor__content {
  min-height: 220px;
  max-height: 420px;
  overflow: auto;
  padding: 16px;
  color: #1e293b;
  outline: none;
}

/* Placeholder */
.rich-editor .tiptap p.is-editor-empty:first-child::before,
.rich-editor .tiptap p.has-focus.is-empty::before,
.rich-editor .tiptap .is-empty::before {
  float: left;
  height: 0;
  color: #94a3b8;
  content: attr(data-placeholder);
  pointer-events: none;
}

/* Tabel tanda tangan: 3 kotak selebar sama, semua garis terlihat. */
.rich-editor .tiptap table {
  width: 100%;
  border-collapse: collapse;
  table-layout: fixed;
  margin: 8px 0;
}

.rich-editor .tiptap td,
.rich-editor .tiptap th {
  border: 1px solid #94a3b8;
  padding: 8px;
  vertical-align: top;
}

.rich-editor .tiptap th {
  background: #f8fafc;
  font-weight: 700;
}

.rich-editor .tiptap ul,
.rich-editor .tiptap ol {
  padding-left: 20px;
  margin: 8px 0;
}

.rich-editor .tiptap p {
  margin: 4px 0;
}

/* Handle resize kolom sengaja disembunyikan: Table resizable: false. */
.rich-editor .tiptap .column-resize-handle {
  display: none;
}

/* ===== Preview HTML hasil template (dipakai di section tanda tangan) =====
   Meniru tampilan laporan/PDF: TIDAK ada garis kotak di tabel, hanya
   susunan 3 kolom yang rata tengah. */
.signature-preview {
  border: 1px solid #e2e8f0;
  border-radius: 12px;
  background: #ffffff;
  padding: 16px;
  font-size: 13px;
  line-height: 1.7;
  color: #1e293b;
  overflow-x: auto;
}
.signature-preview table {
  width: 100%;
  border-collapse: collapse;
  table-layout: fixed;
  margin: 0;
}
.signature-preview td,
.signature-preview th {
  border: 0;
  padding: 4px 10px;
  vertical-align: top;
  text-align: center;
}
.signature-preview p {
  margin: 4px 0;
}
.signature-preview ul,
.signature-preview ol {
  padding-left: 20px;
  margin: 8px 0;
}
.signature-preview img {
  max-width: 100%;
  height: auto;
}
</style>