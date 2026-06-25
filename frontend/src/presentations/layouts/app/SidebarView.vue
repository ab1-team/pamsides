<template>
  <div
    class="sidebar-panel"
    :class="{
      collapsed: !sidebarOpen,
      'mobile-open': mobileSidebarOpen,
      blurred: uiStore.activeModalCount > 0,
    }"
  >
    <div class="sidebar-header">
      <div class="sidebar-header-content">
        <div class="sidebar-header-title" v-show="sidebarOpen || mobileSidebarOpen">
          {{ roleTitle }}
        </div>
        <div class="sidebar-header-sub" v-show="sidebarOpen || mobileSidebarOpen">
          {{ roleSubtitle }}
        </div>
      </div>
      <BaseButton
        variant="ghost"
        class="sidebar-toggle-btn-sidebar"
        @click="mobileSidebarOpen ? $emit('close-mobile-sidebar') : $emit('toggle-sidebar')"
        v-show="sidebarOpen || mobileSidebarOpen"
        icon="bars"
      />
    </div>

    <div class="sidebar-toggle-collapsed" v-show="!sidebarOpen">
      <BaseButton
        variant="ghost"
        class="sidebar-toggle-btn-collapsed"
        @click="$emit('toggle-sidebar')"
        icon="bars"
      />
    </div>

    <div class="sidebar-divider" v-show="sidebarOpen"></div>

    <nav class="sidebar-nav">
      <template v-for="(item, index) in filteredMenuItems" :key="index">
        <router-link
          v-if="!item.children"
          :to="item.to"
          class="sidebar-nav-item"
          :class="{ active: $route.path === item.to }"
          :title="!sidebarOpen ? item.label : ''"
          @click="handleMenuClick"
        >
          <font-awesome-icon :icon="item.icon" class="sidebar-nav-icon" />
          <span class="sidebar-nav-label">{{ item.label }}</span>
        </router-link>

        <div v-else class="sidebar-nav-group">
          <router-link
            to="#"
            class="sidebar-nav-item"
            :class="{ 'submenu-open': openSubmenus[item.label] }"
            :title="!sidebarOpen ? item.label : ''"
            @click.prevent="toggleSubmenu(item.label)"
          >
            <font-awesome-icon :icon="item.icon" class="sidebar-nav-icon" />
            <span class="sidebar-nav-label">{{ item.label }}</span>
            <font-awesome-icon
              v-if="sidebarOpen"
              icon="chevron-down"
              class="sidebar-nav-chevron"
              :class="{ rotated: openSubmenus[item.label] }"
            />
          </router-link>

          <div class="sidebar-submenu" :class="{ open: openSubmenus[item.label] && sidebarOpen }">
            <template v-for="(sub, subIdx) in item.children" :key="subIdx">
              <div v-if="sub.children" class="sidebar-nav-group">
                <router-link
                  to="#"
                  class="sidebar-submenu-item"
                  :class="{ 'submenu-open': openSubmenus[sub.label] }"
                  @click.prevent="toggleSubmenu(sub.label)"
                >
                  <font-awesome-icon :icon="sub.icon" class="sidebar-submenu-icon" width="16" />
                  <span>{{ sub.label }}</span>
                  <font-awesome-icon
                    v-if="sidebarOpen"
                    icon="chevron-down"
                    class="sidebar-nav-chevron"
                    :class="{ rotated: openSubmenus[sub.label] }"
                    width="12"
                  />
                </router-link>
                <div
                  class="sidebar-submenu nested"
                  :class="{ open: openSubmenus[sub.label] && sidebarOpen }"
                >
                  <router-link
                    v-for="(nested, nestedIdx) in sub.children"
                    :key="nestedIdx"
                    :to="nested.to"
                    class="sidebar-submenu-item nested-item"
                    :class="{ active: isActive(nested.to) }"
                    @click="handleMenuClick"
                  >
                    <span class="sidebar-submenu-bullet"></span>
                    <span>{{ nested.label }}</span>
                  </router-link>
                </div>
              </div>

              <router-link
                v-else
                :to="sub.to"
                class="sidebar-submenu-item"
                :class="{ active: isActive(sub.to) }"
                @click="handleMenuClick"
              >
                <span v-if="!sub.icon" class="sidebar-submenu-bullet"></span>
                <font-awesome-icon
                  v-if="sub.icon"
                  :icon="sub.icon"
                  class="sidebar-submenu-icon"
                  width="16"
                />
                <span>{{ sub.label }}</span>
              </router-link>
            </template>
          </div>
        </div>
      </template>
    </nav>

    <div class="sidebar-new-entry-area">
      <BaseButton
        variant="info"
        class="new-entry-btn"
        :class="{ 'icon-only': !sidebarOpen }"
        size="md"
      >
        <span v-if="sidebarOpen">AstaBrata Teknologi</span>
      </BaseButton>
    </div>
  </div>
</template>

<script setup>
import { onMounted, watch, computed, reactive } from 'vue'
import { useRoute } from 'vue-router'
import { useUiStore } from '@/stores/uiStore'
import BaseButton from '@/presentations/components/ui/BaseButton.vue'
import sopService from '@/services/sop.service'

const props = defineProps({
  sidebarOpen: {
    type: Boolean,
    default: true,
  },
  mobileSidebarOpen: {
    type: Boolean,
    default: false,
  },
})

const emit = defineEmits(['toggle-sidebar', 'close-mobile-sidebar'])
const uiStore = useUiStore()
const route = useRoute()

const roleTitle = computed(() => {
  if (uiStore.lembagaName) {
    return uiStore.lembagaName
  }
  switch (uiStore.userRole) {
    case 'surveyor':
      return 'Surveyor Portal'
    case 'teknisi':
      return 'Technician Hub'
    case 'pelanggan':
      return 'Customer App'
    default:
      return 'Admin Portal'
  }
})

const roleSubtitle = computed(() => {
  if (uiStore.lembagaName) {
    return uiStore.userRole?.toUpperCase() || 'PORTAL'
  }
  switch (uiStore.userRole) {
    case 'surveyor':
      return 'FIELD OPERATIONS'
    case 'teknisi':
      return 'TECHNICAL SUITE'
    case 'pelanggan':
      return 'USER SERVICE'
    default:
      return 'MANAGEMENT SUITE'
  }
})

const loadLembagaName = async () => {
  try {
    const res = await sopService.getAll()
    const data = res?.data ?? res
    const name = data?.lembaga?.nama?.trim()
    uiStore.setLembagaName(name || '')
  } catch {
    // silent: fallback to default role title
  }
}

onMounted(() => {
  loadLembagaName()
})

const openSubmenus = reactive({})

function toggleSubmenu(label) {
  if (props.sidebarOpen) {
    openSubmenus[label] = !openSubmenus[label]
  }
}

function isActive(to) {
  if (!to) return false
  return route.path === to
}

function isActiveOrChild(to) {
  if (!to) return false
  return route.path === to || route.path.startsWith(to + '/')
}

function syncOpenSubmenus() {
  menuItems.forEach((item) => {
    if (!item.children) return
    const childActive = item.children.some((sub) => {
      if (sub.children) {
        return sub.children.some((n) => isActiveOrChild(n.to))
      }
      return isActiveOrChild(sub.to)
    })
    if (childActive) {
      openSubmenus[item.label] = true
    }
    item.children.forEach((sub) => {
      if (sub.children) {
        const nestedActive = sub.children.some((n) => isActiveOrChild(n.to))
        if (nestedActive) openSubmenus[sub.label] = true
      }
    })
  })
}

const menuItems = [
  {
    label: 'Dashboard',
    icon: 'home',
    to: '/app',
    roles: ['admin', 'surveyor', 'teknisi', 'pelanggan'],
  },
  {
    label: 'Create Survey Baru',
    icon: 'plus-circle',
    to: '/app/survey/create',
    roles: ['surveyor'],
  },
  {
    label: 'Settings',
    icon: 'cog',
    roles: ['admin'],
    children: [
      { label: 'Personalisasi SOP', to: '/app/settings/personalisasi-sop' },
      { label: 'Chart of Account COA', to: '/app/settings/coa' },
      { label: 'Paket & Tarif Layanan', to: '/app/kelas-biaya' },
    ],
  },
  {
    label: 'Basis Data',
    icon: 'database',
    roles: ['admin'],
    children: [
      {
        label: 'Pelanggan',
        icon: 'users',
        children: [
          { label: 'Create Pelanggan', to: '/app/data-pelanggan/tambah' },
          { label: 'Data Pelanggan', to: '/app/data-pelanggan' },
        ],
      },
      {
        label: 'Desa',
        icon: 'map-marker-alt',
        children: [
          { label: 'Create Desa', to: '/app/data-desa/tambah' },
          { label: 'Data Desa', to: '/app/data-desa' },
        ],
      },
      { label: 'Daftar Instalasi', icon: 'building', to: '/app/dataInstalasi' },
    ],
  },
  {
    label: 'Master Instalasi',
    icon: 'chart-bar',
    roles: ['admin', 'teknisi'],
    children: [
      { label: 'Register Instalasi', to: '/app/instalasi/register', roles: ['admin'] },
      { label: 'Status Instalasi', to: '/app/instalasi/status', roles: ['admin'] },
      { label: 'Hasil Survey', to: '/app/instalasi/hasil-survey', roles: ['admin'] },
      {
        label: 'Input Pemakaian Air',
        to: '/app/instalasi/teknisiPemakaianAir',
        roles: ['teknisi'],
      },
    ],
  },
  {
    label: 'Pencatatan Tagihan',
    icon: 'file-invoice-dollar',
    roles: ['admin'],
    children: [
      { label: 'Input Tagihan', to: '/app/instalasi/pemakaian-air' },
      { label: 'Daftar Tagihan', to: '/app/instalasi/daftar-tagihan' },
    ],
  },
  {
    label: 'Transaksi',
    icon: 'money-bill-wave',
    roles: ['admin'],
    children: [
      { label: 'Jurnal Umum', to: '/app/transaksi/jurnal-umum' },
      { label: 'Tagihan Bulanan', to: '/app/transaksi/tagihan-bulanan' },
      { label: 'Tagihan Instalasi', to: '/app/transaksi/tagihan-instalasi' },
      { label: 'E-Budgeting', to: '/app/transaksi/E-budgeting' },
      { label: 'Tutup Buku', to: '/app/transaksi/tutup-buku' },
      { label: 'Komisi SPS', to: '/app/transaksi/komisi-sps' },
    ],
  },
  {
    label: 'Pelaporan',
    icon: 'file-alt',
    roles: ['admin'],
    to: '/app/pelaporan',
  },
]

watch(
  () => route.path,
  (newPath) => {
    menuItems.forEach((item) => {
      if (!item.children) return
      const hasActive = item.children.some(
        (c) => c.to === newPath || (c.to && newPath.startsWith(c.to)),
      )
      if (hasActive) openSubmenus[item.label] = true
    })
  },
  { immediate: true },
)

const filteredMenuItems = computed(() => {
  return menuItems
    .filter((item) => {
      if (item.roles && !item.roles.includes(uiStore.userRole)) return false
      return true
    })
    .map((item) => {
      if (!item.children) return item
      const visibleChildren = item.children.filter((child) => {
        if (child.roles && !child.roles.includes(uiStore.userRole)) return false
        return true
      })
      if (visibleChildren.length === 0) return null
      return { ...item, children: visibleChildren }
    })
    .filter(Boolean)
})

syncOpenSubmenus()

function handleMenuClick() {
  if (window.innerWidth < 1024) {
    emit('close-mobile-sidebar')
  }
}

watch(
  () => props.sidebarOpen,
  (newVal) => {
    if (!newVal) {
      Object.keys(openSubmenus).forEach((key) => {
        openSubmenus[key] = false
      })
    }
  },
)

watch(
  () => route.path,
  () => {
    syncOpenSubmenus()
  },
)

watch(
  () => uiStore.settingsVersion,
  () => {
    loadLembagaName()
  },
)
</script>

<style scoped>
.sidebar-panel.blurred {
  backdrop-filter: blur(8px);
  -webkit-backdrop-filter: blur(8px);
  pointer-events: none;
  user-select: none;
}
</style>
