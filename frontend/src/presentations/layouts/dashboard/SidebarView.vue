<template>
  <div class="sidebar-panel" :class="{ collapsed: !sidebarOpen, 'mobile-open': mobileSidebarOpen }">
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
        <!-- Single Nav Item -->
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

        <!-- Nav Group (with Submenu) -->
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

          <!-- Submenu -->
          <div class="sidebar-submenu" :class="{ open: openSubmenus[item.label] && sidebarOpen }">
            <template v-for="(sub, subIdx) in item.children" :key="subIdx">
              <!-- Nested Submenu -->
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
                    @click="handleMenuClick"
                  >
                    <span class="sidebar-submenu-bullet"></span>
                    <span>{{ nested.label }}</span>
                  </router-link>
                </div>
              </div>

              <!-- Simple Submenu Item -->
              <router-link
                v-else
                :to="sub.to"
                class="sidebar-submenu-item"
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
        variant="primary-gradient"
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
import { watch, computed, reactive } from 'vue'
import { useUiStore } from '@/stores/uiStore'
import BaseButton from '@/presentations/components/ui/BaseButton.vue'

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

const roleTitle = computed(() => {
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

// Submenu States (Reactive Object)
const openSubmenus = reactive({})

function toggleSubmenu(label) {
  if (props.sidebarOpen) {
    openSubmenus[label] = !openSubmenus[label]
  }
}

const menuItems = [
  {
    label: 'Dashboard',
    icon: 'home',
    to: '/dashboard',
    roles: ['admin', 'surveyor', 'teknisi', 'pelanggan'],
  },
  {
    label: 'Settings',
    icon: 'cog',
    roles: ['admin'],
    children: [
      { label: 'Personalisasi SOP', to: '/settings/personalisasi-sop' },
      { label: 'Cart of Account COA', to: '/settings/coa' },
      { label: 'Kelas Dan Biaya', to: '/kelas-biaya' },
    ],
  },
  {
    label: 'Basis Data',
    icon: 'database',
    roles: ['admin', 'surveyor'],
    children: [
      {
        label: 'Pelanggan',
        icon: 'users',
        children: [
          { label: 'Create Pelanggan', to: '/data-pelanggan/tambah' },
          { label: 'Data Pelanggan', to: '/data-pelanggan' },
        ],
      },
      {
        label: 'Desa',
        icon: 'building',
        children: [
          { label: 'Create Desa', to: '/data-desa/tambah' },
          { label: 'Data Desa', to: '/data-desa' },
        ],
      },
      {
        label: 'Caters',
        icon: 'archive',
        children: [
          { label: 'Create Caters', to: '/data-cater/tambah' },
          { label: 'Data Caters', to: '/data-cater' },
        ],
      },
      { label: 'Daftar Instalasi', icon: 'building', to: '/dataInstalasi' },
    ],
  },
  {
    label: 'Master Instalasi',
    icon: 'chart-bar',
    roles: ['admin', 'surveyor', 'teknisi'],
    children: [
      { label: 'Register Instalasi', to: '/instalasi/register', roles: ['admin', 'surveyor'] },
      { label: 'Status Instalasi', to: '/instalasi/status', roles: ['admin', 'surveyor'] },
      {
        label: 'Pemakaian Air Bersih',
        to: '/instalasi/pemakaian-air',
        roles: ['admin'],
      },
      {
        label: 'Pemakaian Air Bersih',
        to: '/instalasi/teknisiPemakaianAir',
        roles: ['teknisi'],
      },
      {
        label: 'Retribusi Sampah',
        to: '/instalasi/retribusi-sampah',
        roles: ['admin', 'teknisi'],
      },
    ],
  },
  {
    label: 'Transaksi',
    icon: 'assistive-listening-systems',
    roles: ['admin'],
    children: [
      { label: 'Jurnal Umum', to: '/transaksi/jurnal-umum' },
      { label: 'Tagihan Instalasi', to: '/transaksi/tagihan-instalasi' },
      { label: 'Tagihan Bulanan', to: '/transaksi/tagihan-bulanan' },
      { label: 'E-Budgeting', to: '/transaksi/e-budgeting' },
      { label: 'Tutup Buku', to: '/transaksi/tutup-buku' },
      { label: 'Komisi SPS', to: '/transaksi/komisi-sps' },
    ],
  },
  {
    label: 'Pelaporan',
    icon: 'file-powerpoint',
    to: '/pelaporan',
    roles: ['admin'],
  },
  // Pelanggan Specific Items
  {
    label: 'Riwayat Tagihan',
    icon: 'history',
    to: '/riwayat-tagihan',
    roles: ['pelanggan'],
  },
  {
    label: 'Lapor Gangguan',
    icon: 'headset',
    to: '/lapor-gangguan',
    roles: ['pelanggan'],
  },
]

const filteredMenuItems = computed(() => {
  return menuItems
    .filter((item) => item.roles.includes(uiStore.userRole))
    .map((item) => {
      if (item.children) {
        return {
          ...item,
          children: item.children.filter(
            (sub) => !sub.roles || sub.roles.includes(uiStore.userRole),
          ),
        }
      }
      return item
    })
})

function handleMenuClick() {
  if (props.mobileSidebarOpen) {
    emit('close-mobile-sidebar')
  }
}

watch(
  () => props.sidebarOpen,
  (val) => {
    if (!val) {
      // Close all submenus when sidebar is collapsed
      Object.keys(openSubmenus).forEach((key) => {
        openSubmenus[key] = false
      })
    }
  },
)
</script>
