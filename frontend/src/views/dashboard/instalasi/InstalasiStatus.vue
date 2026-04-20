<template>
  <div class="instalasi-status-root w-full!">
    <div class="flex flex-col lg:flex-row lg:items-start lg:justify-between gap-4! mb-6!">
      <div class="flex-1!">
        <h1 class="text-2xl font-bold text-indigo-600! tracking-tight mb-1!">Status Instalasi</h1>
        <p class="text-sm text-slate-500! leading-relaxed">
          Manage and monitor the lifecycle of water service installations.
        </p>
      </div>

      <div class="grid grid-cols-2 lg:flex lg:flex-wrap gap-3! w-full lg:w-auto!">
        <BaseButton
          variant="success-gradient"
          size="md"
          @click="exportData"
          class="w-full! lg:w-auto! rounded-xl! shadow-lg! shadow-emerald-200/50!"
          icon="file-export"
        >
          Export Excel
        </BaseButton>

        <BaseButton
          variant="primary-gradient"
          size="md"
          @click="handlePrintData"
          class="w-full! lg:w-auto! rounded-xl! shadow-lg! shadow-indigo-200/50!"
          icon="print"
        >
          Cetak Table
        </BaseButton>
      </div>
    </div>

    <ContentCard variant="default" padding="none" hoverable class="overflow-hidden!">
      <!-- DESKTOP -->
      <div class="hidden lg:flex! h-full!">
        <ContentCard
          variant="minimal"
          padding="normal"
          class="w-56! xl:w-64! flex-shrink-0! border-r! border-slate-100! !rounded-tr-none !rounded-br-none"
        >
          <div class="flex flex-col gap-2.5!">
            <p
              class="text-[10px]! font-bold! text-slate-400! uppercase! tracking-widest! mb-2! px-1!"
            >
              Filter Status
            </p>

            <BaseButton
              v-for="menu in menuList"
              :key="menu.key"
              @click="activeStatus = menu.key"
              :variant="activeStatus === menu.key ? menu.variant : 'ghost'"
              class="w-full! px-4! py-3! rounded-full! transition-all! duration-300!"
            >
              <span class="w-full! flex! items-center! justify-between! gap-3!">
                <span class="flex items-center gap-3!">
                  <span class="text-lg! leading-none! shrink-0!">{{ menu.icon }}</span>
                  <span class="text-sm! font-medium! text-left! truncate!">{{ menu.label }}</span>
                </span>

                <span
                  :class="[
                    'text-xs! font-bold! px-2! py-0.5! rounded-full! min-w-[22px]! text-center! shrink-0! transition-colors! duration-300!',
                    activeStatus === menu.key
                      ? 'bg-white/25! text-white!'
                      : 'bg-slate-200! text-slate-500!',
                  ]"
                >
                  {{ dataMap[menu.key]?.length || 0 }}
                </span>
              </span>
            </BaseButton>
          </div>
        </ContentCard>

        <div class="flex-1! min-w-0!">
          <DataTable
            :data="paginatedData"
            :columns="tableColumns"
            :current-page="currentPage"
            :per-page="perPage"
            :total-pages="totalPages"
            :visible-pages="visiblePages"
            :total-entries="filteredData.length"
            :show-toolbar="true"
            :search-query="searchQuery"
            search-placeholder="Cari customer..."
            v-model="searchQuery"
            @prev-page="prevPage"
            @next-page="nextPage"
            @go-to-page="goToPage"
            :no-card="true"
            :row-clickable="true"
            @row-click="handleRowClick"
          >
            <template #toolbar-actions>
              <h2 class="text-base! font-bold! text-slate-800!">
                {{ activeLabel }}
                <span class="text-slate-400! font-normal! text-sm!">· Data Table</span>
              </h2>
            </template>

            <!-- columns -->
            <template #column-id="{ row }">
              <span class="text-xs! font-bold! text-blue-600! font-mono!">{{ row.id }}</span>
            </template>

            <template #column-name="{ row }">
              <div class="flex items-center gap-2.5!">
                <div
                  class="w-8! h-8! rounded-full! flex! items-center! justify-center! text-white! text-xs! font-bold! shrink-0!"
                  :style="{ backgroundColor: row.color }"
                >
                  {{ row.initials }}
                </div>
                <div>
                  <p class="text-sm! font-semibold! text-slate-800! leading-tight!">
                    {{ row.name }}
                  </p>
                  <p class="text-xs! text-slate-400!">{{ row.type }}</p>
                </div>
              </div>
            </template>

            <template #column-address="{ row }">
              <p class="text-sm! text-slate-500! max-w-[200px]! truncate!">
                {{ row.address }}
              </p>
            </template>

            <template #column-status="{ row }">
              <span
                class="text-xs! font-semibold! px-2.5! py-1! rounded-full! border!"
                :class="statusStyle[row.status]"
              >
                {{ row.status }}
              </span>
            </template>

            <template #column-action="{}">
              <div class="flex! items-center! justify-center!">
                <span
                  class="w-7! h-7! rounded-full! bg-slate-100! hover:bg-indigo-100! flex! items-center! justify-center! transition-colors!"
                >
                  <font-awesome-icon
                    icon="chevron-right"
                    class="text-xs! text-slate-400! group-hover:text-indigo-500!"
                  />
                </span>
              </div>
            </template>
          </DataTable>
        </div>
      </div>

      <!-- MOBILE -->
      <div class="lg:hidden! flex! flex-col!">
        <div class="px-4! pt-4! pb-3! border-b! border-slate-100! bg-white!">
          <p class="text-[10px]! font-bold! text-slate-400! uppercase! tracking-widest! mb-2.5!">
            Filter Status
          </p>

          <div class="grid grid-cols-1 sm:grid-cols-3 md:grid-cols-5 gap-3!">
            <BaseButton
              v-for="menu in menuList"
              :key="menu.key"
              @click="activeStatus = menu.key"
              :variant="activeStatus === menu.key ? menu.variant : 'ghost'"
              class="px-3! py-2! rounded-full! transition-all! duration-300!"
            >
              <span class="w-full! flex! items-center! justify-between! gap-2!">
                <div class="flex items-center gap-2!">
                  <span class="text-sm! leading-none! shrink-0!">{{ menu.icon }}</span>
                  <span class="truncate! text-[11px]! font-semibold! text-left!">
                    {{ menu.label }}
                  </span>
                </div>

                <span
                  :class="[
                    'text-[10px]! font-bold! px-1.5! py-0.5! rounded-full! min-w-[18px]! text-center! shrink-0! transition-colors! duration-300!',
                    activeStatus === menu.key
                      ? 'bg-white/25! text-white!'
                      : 'bg-slate-300! text-slate-600!',
                  ]"
                >
                  {{ dataMap[menu.key]?.length || 0 }}
                </span>
              </span>
            </BaseButton>
          </div>
        </div>

        <div class="px-4! py-3! border-b! border-slate-100! bg-white!">
          <div class="flex! items-center! justify-between! mb-3!">
            <h2 class="text-sm! font-bold! text-slate-800!">{{ activeLabel }}</h2>
          </div>

          <input
            v-model="searchQuery"
            type="text"
            placeholder="Cari customer..."
            class="w-full! pl-9! pr-4! py-2! bg-slate-50! border! border-slate-200! rounded-xl! text-sm! text-slate-700! focus:outline-none! focus:ring-2! focus:ring-blue-500/20! focus:border-blue-400! transition-all!"
          />
        </div>

        <div class="divide-y! divide-slate-50! bg-white!">
          <div
            v-for="row in paginatedData"
            :key="row.id"
            @click="handleRowClick(row)"
            class="flex! items-center! gap-3! px-4! py-3! hover:bg-slate-50/80! transition-colors! cursor-pointer!"
          >
            <div
              class="w-10! h-10! rounded-full! flex! items-center! justify-center! text-white! text-sm! font-bold! shrink-0! shadow-sm!"
              :style="{ backgroundColor: row.color }"
            >
              {{ row.initials }}
            </div>

            <div class="flex-1! min-w-0!">
              <div class="flex! items-center! justify-between! mb-0.5!">
                <p class="text-sm! font-semibold! text-slate-800! truncate!">{{ row.name }}</p>
                <span class="text-[10px]! font-bold! text-blue-600! font-mono!">{{ row.id }}</span>
              </div>
              <div class="flex! items-center! justify-between!">
                <p class="text-xs! text-slate-400! truncate!">{{ row.type }}</p>
                <span
                  class="text-[9px]! font-semibold! px-1.5! py-0.5! rounded-full! border! shrink-0!"
                  :class="statusStyle[row.status]"
                >
                  {{ row.status }}
                </span>
              </div>
            </div>
          </div>
        </div>

        <!-- Mobile Pagination -->
        <div
          class="px-4! py-4! bg-slate-50/50! border-t! border-slate-100! flex! items-center! justify-between!"
        >
          <span class="text-xs! text-slate-500! font-medium!">
            Page {{ currentPage }} of {{ totalPages }}
          </span>
          <div class="flex! items-center! gap-2!">
            <BaseButton
              variant="ghost"
              size="sm"
              :disabled="currentPage === 1"
              @click="prevPage"
              class="w-8! h-8! p-0! rounded-lg! border! border-slate-200! bg-white!"
            >
              ‹
            </BaseButton>
            <BaseButton
              variant="ghost"
              size="sm"
              :disabled="currentPage === totalPages"
              @click="nextPage"
              class="w-8! h-8! p-0! rounded-lg! border! border-slate-200! bg-white!"
            >
              ›
            </BaseButton>
          </div>
        </div>
      </div>
    </ContentCard>
  </div>
</template>

<script setup>
import { useInstalasiStatus } from '@/composables/useInstalasiStatus'
import { useRouter } from 'vue-router'
import DataTable from '@/components/ui/DataTable.vue'
import ContentCard from '@/components/ui/ContentCard.vue'
import BaseButton from '@/components/ui/BaseButton.vue'

const {
  activeStatus,
  activeLabel,
  currentPage,
  perPage,
  searchQuery,
  menuList,
  dataMap,
  filteredData,
  totalPages,
  visiblePages,
  paginatedData,
  statusStyle,
  prevPage,
  nextPage,
  goToPage,
  exportData,
} = useInstalasiStatus()

const router = useRouter()

const routeMap = {
  permohonan: 'Detail Permohonan',
  pasang_baru: 'Detail Pasang Baru',
  aktif: 'Detail Aktif',
  blokir: 'Detail Blokir',
  cabut: 'Detail Cabut',
}

const handleRowClick = (row) => {
  const routeName = routeMap[activeStatus.value]
  if (routeName) {
    // encodeURIComponent handles special chars like # in IDs
    router.push({ name: routeName, params: { id: encodeURIComponent(row.id) } })
  }
}

const tableColumns = [
  { key: 'id', title: 'Customer ID', tdClass: '' },
  { key: 'name', title: 'Full Name', tdClass: '' },
  { key: 'address', title: 'Address', tdClass: '' },
  { key: 'status', title: 'Status', tdClass: '' },
  { key: 'action', title: '', tdClass: 'w-12 text-center' },
]

const handlePrintData = () => {
  const printWindow = window.open('', '_blank')
  printWindow.document.write(`
    <!DOCTYPE html><html><head>
    <title>Cetak Data ${activeLabel.value}</title>
    <style>
      body{font-family:Arial,sans-serif;margin:20px}
      h1{text-align:center;margin-bottom:20px}
      table{width:100%;border-collapse:collapse}
      th,td{border:1px solid #ddd;padding:8px;text-align:left}
      th{background:#f2f2f2;font-weight:bold}
      tr:nth-child(even){background:#f9f9f9}
      @media print{body{margin:10px}}
    </style></head><body>
    <h1>Data ${activeLabel.value}</h1>
    <table><thead><tr><th>Customer ID</th><th>Full Name</th><th>Type</th><th>Address</th><th>Status</th></tr></thead>
    <tbody>${filteredData.value
      .map(
        (r) =>
          `<tr><td>${r.id}</td><td>${r.name}</td><td>${r.type}</td><td>${r.address}</td><td>${r.status}</td></tr>`,
      )
      .join('')}</tbody>
    </table></body></html>
  `)
  printWindow.document.close()
  printWindow.print()
}
</script>
