<template>
  <div class="coa-page! p-2! md:p-4!">
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
      <div class="p-4! md:p-6! overflow-x-auto!">
        <div id="coa-tree" class="coa-tree-wrapper!"></div>
      </div>
    </ContentCard>
  </div>
</template>

<script setup>
import { onMounted } from 'vue'
import ContentCard from '@/components/ui/ContentCard.vue'
import 'jstree/dist/themes/default/style.min.css'

onMounted(() => {
  const loadScript = (src) => {
    return new Promise((resolve) => {
      const script = document.createElement('script')
      script.src = src
      script.onload = resolve
      document.head.appendChild(script)
    })
  }

  const initTree = async () => {
    if (!window.jQuery) {
      await loadScript('https://code.jquery.com/jquery-3.7.1.min.js')
    }

    await loadScript('https://cdnjs.cloudflare.com/ajax/libs/jstree/3.3.12/jstree.min.js')

    const treeData = [
      {
        id: '1',
        text: '1.0.00.00. Aset',
        children: [
          {
            id: '1.1',
            text: '1.1.00.00. Aset Lancar',
            children: [
              {
                id: '1.1.01',
                text: '1.1.01.00. Kas dan Setara Kas',
                children: [
                  { id: '1.1.01.01', text: '1.1.01.01. Kas Kecil' },
                  { id: '1.1.01.02', text: '1.1.01.02. Bank Mandiri' },
                ],
              },
              { id: '1.1.02', text: '1.1.02.00. Piutang Usaha' },
              { id: '1.1.03', text: '1.1.03.00. Persediaan Barang' },
            ],
          },
          {
            id: '1.2',
            text: '1.2.00.00. Aset Tetap',
            children: [
              { id: '1.2.01', text: '1.2.01.00. Peralatan Kantor' },
              { id: '1.2.02', text: '1.2.02.00. Kendaraan' },
            ],
          },
        ],
      },
      {
        id: '2',
        text: '2.0.00.00. Utang',
        children: [
          {
            id: '2.1',
            text: '2.1.00.00. Utang Jangka Pendek',
            children: [
              {
                id: '2.1.01',
                text: '2.1.01.00. Utang Dividen',
                children: [
                  { id: '2.1.01.01', text: '2.1.01.01. Utang Dividen Pemdes' },
                  { id: '2.1.01.02', text: '2.1.01.02. Utang Dividen Masy Penyerta Modal' },
                  { id: '2.1.01.03', text: '2.1.01.03. Bantuan Sosial' },
                  { id: '2.1.01.04', text: '2.1.01.04. Utang Bonus' },
                ],
              },
              { id: '2.1.02', text: '2.1.02.00. Utang Biaya Operasional' },
              { id: '2.1.03', text: '2.1.03.00. Utang Pajak' },
            ],
          },
          { id: '2.2', text: '2.2.00.00. Utang Jangka Panjang' },
        ],
      },
      {
        id: '3',
        text: '3.0.00.00. Modal',
        children: [
          { id: '3.1', text: '3.1.00.00. Modal Desa' },
          { id: '3.2', text: '3.2.00.00. Laba Ditahan' },
        ],
      },
      {
        id: '4',
        text: '4.0.00.00. Pendapatan',
        children: [
          { id: '4.1', text: '4.1.00.00. Pendapatan Jasa Air' },
          {
            id: '4.2',
            text: '4.2.00.00. Pendapatan Non-Air',
            children: [
              { id: '4.2.01', text: '4.2.01.00. Denda Keterlambatan' },
              { id: '4.2.02', text: 'Biaya Pasang Baru' },
            ],
          },
        ],
      },
      {
        id: '5',
        text: '5.0.00.00. Beban',
        children: [
          {
            id: '5.1',
            text: '5.1.00.00. Beban Operasional',
            children: [
              { id: '5.1.01', text: '5.1.01.00. Beban Gaji Karyawan' },
              { id: '5.1.02', text: '5.1.02.00. Beban Listrik & Air Kantor' },
            ],
          },
          { id: '5.2', text: '5.2.00.00. Beban Administrasi Umum' },
        ],
      },
    ]

    window.jQuery('#coa-tree').jstree({
      core: {
        data: treeData,
        check_callback: true,
        themes: {
          name: 'default',
          responsive: true,
          variant: 'large',
          dots: true,
          icons: true,
        },
      },
      plugins: ['types', 'wholerow'],
    })
  }

  initTree()
})
</script>

<style scoped>
@reference "../../../assets/main.css";

.coa-tree-wrapper {
  @apply font-sans text-[13px]!;
}

:deep(.jstree-default .jstree-node) {
  @apply ml-4!;
}

:deep(.jstree-default .jstree-ocl) {
  @apply mr-1!;
}

:deep(.jstree-default .jstree-anchor) {
  @apply font-bold! text-slate-700! h-auto! leading-7! transition-colors!;
}

:deep(.jstree-default .jstree-hovered) {
  @apply bg-blue-50/50! shadow-none! border-none! rounded-sm!;
}

:deep(.jstree-default .jstree-clicked) {
  @apply bg-blue-100/50! shadow-none! border-none! rounded-sm!;
}

:deep(.jstree-default .jstree-icon) {
  @apply opacity-90!;
}
</style>
