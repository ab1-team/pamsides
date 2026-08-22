<template>
  <div class="coa-page!">
    <div class="page-header! mb-4! ml-2!">
      <div>
        <h1 class="text-xl! md:text-2xl! font-black! text-slate-800! leading-none! mb-1!">
          Chart of Accounts
        </h1>
        <p class="text-xs! md:text-sm! text-slate-500! font-medium!">
          Manajemen Hierarki Bagan Akun Keuangan
        </p>
      </div>
    </div>

    <ContentCard variant="elevated" padding="none" rounded="2xl" class="mt-4! overflow-hidden!">
      <div class="p-4! md:p-6! relative!">
        <div
          v-if="isLoading"
          class="absolute! inset-0! z-10! flex! items-center! justify-center! bg-white/60! rounded-2xl!"
        >
          <i class="pi pi-spin pi-spinner text-cyan-600!" style="font-size: 1.5rem"></i>
        </div>

        <div
          v-if="!isLoading && !hasData"
          class="text-center! py-10! text-slate-500! text-sm!"
        >
          Belum ada data akun.
        </div>

        <div
          v-show="!isLoading && hasData"
          ref="treeEl"
          class="coajs-tree!"
        ></div>
      </div>
    </ContentCard>
  </div>
</template>

<script setup>
import { ref, onMounted, onBeforeUnmount, nextTick } from 'vue'
import ContentCard from '@/presentations/components/ui/ContentCard.vue'
import coaService from '@/services/coa.service'
import $ from 'jquery'
import 'jstree'
import 'jstree/dist/themes/default/style.min.css'

const isLoading = ref(false)
const hasData = ref(false)
const treeEl = ref(null)

const buildNodesFromGroups = (payload) => {
  const lvl1 = Array.isArray(payload?.level1) ? payload.level1 : []
  const lvl2 = Array.isArray(payload?.level2) ? payload.level2 : []
  const lvl3 = Array.isArray(payload?.level3) ? payload.level3 : []
  const accs = Array.isArray(payload?.accounts) ? payload.accounts : []

  const nodes = []

  for (const r of lvl1) {
    nodes.push({
      id: `l1_${r.id}`,
      parent: '#',
      type: 'level1',
      kode_akun: r.kode_akun,
      nama_akun: r.nama_akun,
      text: `${r.kode_akun} - ${r.nama_akun}`,
    })
  }
  for (const r of lvl2) {
    nodes.push({
      id: `l2_${r.id}`,
      parent: `l1_${r.parent_id}`,
      type: 'level2',
      kode_akun: r.kode_akun,
      nama_akun: r.nama_akun,
      text: `${r.kode_akun} - ${r.nama_akun}`,
    })
  }
  for (const r of lvl3) {
    nodes.push({
      id: `l3_${r.id}`,
      parent: `l2_${r.parent_id}`,
      type: 'level3',
      kode_akun: r.kode_akun,
      nama_akun: r.nama_akun,
      text: `${r.kode_akun} - ${r.nama_akun}`,
    })
  }
  for (const r of accs) {
    nodes.push({
      id: `acc_${r.id}`,
      parent: `l3_${r.parent_id}`,
      type: 'account',
      kode_akun: r.kode_akun,
      nama_akun: r.nama_akun,
      text: `${r.kode_akun} - ${r.nama_akun}`,
    })
  }

  return nodes
}

const decorateNodes = (rows) => {
  return (rows || []).map((n) => {
    const isLeaf = n.type === 'account'
    return {
      id: n.id,
      parent: n.parent || '#',
      text: n.text,
      type: isLeaf ? 'leaf' : 'folder',
      state: { opened: false },
      a_attr: {
        class: `coajs-type-${n.type}`,
        'data-kode': n.kode_akun,
      },
      li_attr: {
        class: `coajs-li-${n.type}`,
      },
      icon: isLeaf ? 'jstree-file' : 'jstree-folder',
    }
  })
}

const fetchAccounts = async () => {
  try {
    isLoading.value = true
    const res = await coaService.getAccounts()
    const payload = res?.data?.data ?? res?.data ?? {}
    const flat = Array.isArray(payload) ? payload : buildNodesFromGroups(payload)
    const nodes = decorateNodes(flat)
    hasData.value = nodes.length > 0

    await nextTick()
    if (!treeEl.value) return

    if ($(treeEl.value).data('jstree')) {
      $(treeEl.value).jstree('destroy')
    }

    $(treeEl.value).jstree({
      core: {
        data: nodes,
        check_callback: false,
        themes: {
          dots: true,
          icons: true,
          stripes: false,
          responsive: true,
        },
        multiple: false,
      },
      plugins: ['wholerow', 'types'],
      types: {
        folder: { icon: 'jstree-folder' },
        leaf: { icon: 'jstree-file' },
        default: { icon: 'jstree-folder' },
      },
    })

    $(treeEl.value).on('click.jstree', '.jstree-anchor', function (e) {
      const inst = $.jstree.reference(this)
      const node = inst && inst.get_node(this)
      if (!node) return
      if (node.children && node.children.length > 0) {
        inst.toggle_node(node)
      }
    })
  } catch (err) {
    console.error('[COA] fetch error:', err)
    hasData.value = false
  } finally {
    isLoading.value = false
  }
}

onMounted(fetchAccounts)

onBeforeUnmount(() => {
  if (treeEl.value && $(treeEl.value).data('jstree')) {
    $(treeEl.value).jstree('destroy')
  }
})
</script>

<style scoped>
@reference "@/assets/css/main.css";

.coajs-tree {
  @apply min-h-[120px]! text-[13px]! font-sans!;
}
</style>

<style>
.coajs-tree .jstree-anchor {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  padding-top: 0.25rem;
  padding-bottom: 0.25rem;
}

.coajs-tree .coajs-type-level1 .jstree-anchor {
  font-weight: 900;
  text-transform: uppercase;
  letter-spacing: 0.05em;
  color: #0f172a;
  font-size: 14px;
}

.coajs-tree .coajs-type-level2 .jstree-anchor {
  font-weight: 700;
  color: #334155;
}

.coajs-tree .coajs-type-level3 .jstree-anchor {
  font-weight: 500;
  color: #475569;
}

.coajs-tree .coajs-type-account .jstree-anchor {
  font-weight: 400;
  color: #64748b;
}

.coajs-tree .jstree-wholerow-clicked,
.coajs-tree .jstree-wholerow-hovered {
  background: rgba(99, 102, 241, 0.08) !important;
}
</style>
