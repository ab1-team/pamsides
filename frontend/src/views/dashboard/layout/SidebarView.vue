<template>
  <div class="sidebar-panel" :class="{ collapsed: !sidebarOpen, 'mobile-open': mobileSidebarOpen }">
    <div class="sidebar-header">
      <div class="sidebar-header-content">
        <div class="sidebar-header-title" v-show="sidebarOpen || mobileSidebarOpen">
          Admin Portal
        </div>
        <div class="sidebar-header-sub" v-show="sidebarOpen || mobileSidebarOpen">
          MANAGEMENT SUITE
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
      <router-link
        to="/dashboard"
        class="sidebar-nav-item active"
        :title="!sidebarOpen ? 'Dashboard' : ''"
        @click="handleMenuClick"
      >
        <font-awesome-icon icon="home" class="sidebar-nav-icon" />
        <span class="sidebar-nav-label">Dashboard</span>
      </router-link>

      <div class="sidebar-nav-group">
        <router-link
          to="#"
          class="sidebar-nav-item"
          :class="{ 'submenu-open': settingsOpen }"
          :title="!sidebarOpen ? 'Settings' : ''"
          @click.prevent="toggleSettings"
        >
          <font-awesome-icon icon="cog" class="sidebar-nav-icon" />
          <span class="sidebar-nav-label">Settings</span>
          <font-awesome-icon
            v-if="sidebarOpen"
            icon="chevron-down"
            class="sidebar-nav-chevron"
            :class="{ rotated: settingsOpen }"
          />
        </router-link>

        <!-- Submenu -->
        <div class="sidebar-submenu" :class="{ open: settingsOpen && sidebarOpen }">
          <router-link
            to="/settings/personalisasi-sop"
            class="sidebar-submenu-item"
            @click="handleMenuClick"
          >
            <span class="sidebar-submenu-bullet"></span>
            <span>Personalisasi SOP</span>
          </router-link>
          <router-link to="/settings/coa" class="sidebar-submenu-item" @click="handleMenuClick">
            <span class="sidebar-submenu-bullet"></span>
            <span>Cart of Account COA</span>
          </router-link>
          <router-link to="/kelas-biaya" class="sidebar-submenu-item" @click="handleMenuClick">
            <span class="sidebar-submenu-bullet"></span>
            <span>Kelas Dan Biaya</span>
          </router-link>
        </div>
      </div>

      <div class="sidebar-nav-group">
        <router-link
          to="#"
          class="sidebar-nav-item"
          :class="{ 'submenu-open': BasisDataOpen }"
          :title="!sidebarOpen ? 'Basis Data' : ''"
          @click.prevent="toggleBasisData"
        >
          <font-awesome-icon icon="database" class="sidebar-nav-icon" />
          <span class="sidebar-nav-label">Basis Data</span>
          <font-awesome-icon
            v-if="sidebarOpen"
            icon="chevron-down"
            class="sidebar-nav-chevron"
            :class="{ rotated: BasisDataOpen }"
          />
        </router-link>

        <!-- Submenu -->
        <div class="sidebar-submenu" :class="{ open: BasisDataOpen && sidebarOpen }">
          <div class="sidebar-nav-group">
            <router-link
              to="#"
              class="sidebar-submenu-item"
              :class="{ 'submenu-open': pelangganOpen }"
              @click.prevent="togglePelanggan"
            >
              <font-awesome-icon icon="users" class="sidebar-submenu-icon" width="16" height="16" />
              <span>Pelanggan</span>
              <font-awesome-icon
                v-if="sidebarOpen"
                icon="chevron-down"
                class="sidebar-nav-chevron"
                :class="{ rotated: pelangganOpen }"
                width="12"
                height="12"
              />
            </router-link>

            <!-- Nested Submenu -->
            <div class="sidebar-submenu nested" :class="{ open: pelangganOpen && sidebarOpen }">
              <router-link
                to="/data-pelanggan/tambah"
                class="sidebar-submenu-item nested-item"
                @click="handleMenuClick"
              >
                <span class="sidebar-submenu-bullet"></span>
                <span>Create Pelanggan</span>
              </router-link>
              <router-link
                to="/data-pelanggan"
                class="sidebar-submenu-item nested-item"
                @click="handleMenuClick"
              >
                <span class="sidebar-submenu-bullet"></span>
                <span>Data Pelanggan</span>
              </router-link>
            </div>
          </div>
          <div class="sidebar-nav-group">
            <router-link
              to="#"
              class="sidebar-submenu-item"
              :class="{ 'submenu-open': desaOpen }"
              @click.prevent="toggleDesa"
            >
              <font-awesome-icon
                icon="building"
                class="sidebar-submenu-icon"
                width="16"
                height="16"
              />
              <span>Desa</span>
              <font-awesome-icon
                v-if="sidebarOpen"
                icon="chevron-down"
                class="sidebar-nav-chevron"
                :class="{ rotated: desaOpen }"
                width="12"
                height="12"
              />
            </router-link>

            <!-- Nested Submenu -->
            <div class="sidebar-submenu nested" :class="{ open: desaOpen && sidebarOpen }">
              <router-link
                to="/data-desa/tambah"
                class="sidebar-submenu-item nested-item"
                @click="handleMenuClick"
              >
                <span class="sidebar-submenu-bullet"></span>
                <span>Create Desa</span>
              </router-link>
              <router-link
                to="/data-desa"
                class="sidebar-submenu-item nested-item"
                @click="handleMenuClick"
              >
                <span class="sidebar-submenu-bullet"></span>
                <span>Data Desa</span>
              </router-link>
            </div>
          </div>
          <div class="sidebar-nav-group">
            <router-link
              to="#"
              class="sidebar-submenu-item"
              :class="{ 'submenu-open': masterDataOpen }"
              @click.prevent="toggleMasterData"
            >
              <font-awesome-icon
                icon="archive"
                class="sidebar-submenu-icon"
                width="16"
                height="16"
              />
              <span>Caters</span>
              <font-awesome-icon
                v-if="sidebarOpen"
                icon="chevron-down"
                class="sidebar-nav-chevron"
                :class="{ rotated: masterDataOpen }"
                width="12"
                height="12"
              />
            </router-link>

            <!-- Nested Submenu -->
            <div class="sidebar-submenu nested" :class="{ open: masterDataOpen && sidebarOpen }">
              <router-link
                to="/data/caters/create"
                class="sidebar-submenu-item nested-item"
                @click="handleMenuClick"
              >
                <span class="sidebar-submenu-bullet"></span>
                <span>Create Caters</span>
              </router-link>
              <router-link
                to="/data-cater"
                class="sidebar-submenu-item nested-item"
                @click="handleMenuClick"
              >
                <span class="sidebar-submenu-bullet"></span>
                <span>Data Caters</span>
              </router-link>
            </div>
          </div>
          <router-link to="/dataInstalasi" class="sidebar-submenu-item" @click="handleMenuClick">
            <font-awesome-icon
              icon="building"
              class="sidebar-submenu-icon"
              width="16"
              height="16"
            />
            <span>Daftar Instalasi</span>
          </router-link>
        </div>
      </div>

      <div class="sidebar-nav-group">
        <router-link
          to="#"
          class="sidebar-nav-item"
          :class="{ 'submenu-open': instalasiOpen }"
          :title="!sidebarOpen ? 'Pengelolaan Instalasi' : ''"
          @click.prevent="toggleInstalasi"
        >
          <font-awesome-icon icon="chart-bar" class="sidebar-nav-icon" />
          <span class="sidebar-nav-label">Master Instalasi</span>
          <font-awesome-icon
            v-if="sidebarOpen"
            icon="chevron-down"
            class="sidebar-nav-chevron"
            :class="{ rotated: instalasiOpen }"
          />
        </router-link>

        <!-- Submenu -->
        <div class="sidebar-submenu" :class="{ open: instalasiOpen && sidebarOpen }">
          <router-link
            to="/instalasi/register"
            class="sidebar-submenu-item"
            @click="handleMenuClick"
          >
            <span class="sidebar-submenu-bullet"></span>
            <span>Register Instalasi</span>
          </router-link>
          <router-link to="/instalasi/status" class="sidebar-submenu-item" @click="handleMenuClick">
            <span class="sidebar-submenu-bullet"></span>
            <span>Status Instalasi</span>
          </router-link>
          <router-link
            to="/instalasi/pemakaian-air"
            class="sidebar-submenu-item"
            @click="handleMenuClick"
          >
            <span class="sidebar-submenu-bullet"></span>
            <span>Pemakaian Air Bersih</span>
          </router-link>
          <router-link
            to="/instalasi/retribusi-sampah"
            class="sidebar-submenu-item"
            @click="handleMenuClick"
          >
            <span class="sidebar-submenu-bullet"></span>
            <span>Retribusi Sampah</span>
          </router-link>
        </div>
      </div>

      <div class="sidebar-nav-group">
        <router-link
          to="#"
          class="sidebar-nav-item"
          :class="{ 'submenu-open': transaksiOpen }"
          :title="!sidebarOpen ? 'Transaksi' : ''"
          @click.prevent="toggleTransaksi"
        >
          <font-awesome-icon icon="assistive-listening-systems" class="sidebar-nav-icon" />
          <span class="sidebar-nav-label">Transaksi</span>
          <font-awesome-icon
            v-if="sidebarOpen"
            icon="chevron-down"
            class="sidebar-nav-chevron"
            :class="{ rotated: transaksiOpen }"
          />
        </router-link>

        <!-- Submenu -->
        <div class="sidebar-submenu" :class="{ open: transaksiOpen && sidebarOpen }">
          <router-link
            to="/transaksi/jurnal-umum"
            class="sidebar-submenu-item"
            @click="handleMenuClick"
          >
            <span class="sidebar-submenu-bullet"></span>
            <span>Jurnal Umum </span>
          </router-link>
          <router-link
            to="/transaksi/tagihan-instalasi"
            class="sidebar-submenu-item"
            @click="handleMenuClick"
          >
            <span class="sidebar-submenu-bullet"></span>
            <span>Tagihan Instalasi</span>
          </router-link>
          <router-link
            to="/transaksi/tagihan-bulanan"
            class="sidebar-submenu-item"
            @click="handleMenuClick"
          >
            <span class="sidebar-submenu-bullet"></span>
            <span>Tagihan Bulanan</span>
          </router-link>
          <router-link
            to="/transaksi/e-budgeting"
            class="sidebar-submenu-item"
            @click="handleMenuClick"
          >
            <span class="sidebar-submenu-bullet"></span>
            <span>E-Budgeting</span>
          </router-link>
          <router-link
            to="/transaksi/tutup-buku"
            class="sidebar-submenu-item"
            @click="handleMenuClick"
          >
            <span class="sidebar-submenu-bullet"></span>
            <span>Tutup Buku</span>
          </router-link>
          <router-link
            to="/transaksi/komisi-sps"
            class="sidebar-submenu-item"
            @click="handleMenuClick"
          >
            <span class="sidebar-submenu-bullet"></span>
            <span>Komisi SPS</span>
          </router-link>
        </div>
      </div>

      <router-link
        to="/pelaporan"
        class="sidebar-nav-item"
        :title="!sidebarOpen ? 'Pelaporan' : ''"
        @click="handleMenuClick"
      >
        <font-awesome-icon icon="file-powerpoint" class="sidebar-nav-icon" />
        <span class="sidebar-nav-label">Pelaporan</span>
      </router-link>
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
import { ref, watch } from 'vue'
import BaseButton from '@/components/ui/BaseButton.vue'

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

const settingsOpen = ref(false)
const BasisDataOpen = ref(false)
const pelangganOpen = ref(false)
const desaOpen = ref(false)
const masterDataOpen = ref(false)
const instalasiOpen = ref(false)
const transaksiOpen = ref(false)

function toggleSettings() {
  if (props.sidebarOpen) {
    settingsOpen.value = !settingsOpen.value
  }
}

function toggleBasisData() {
  if (props.sidebarOpen) {
    BasisDataOpen.value = !BasisDataOpen.value
  }
}

function togglePelanggan() {
  if (props.sidebarOpen) {
    pelangganOpen.value = !pelangganOpen.value
  }
}

function toggleDesa() {
  if (props.sidebarOpen) {
    desaOpen.value = !desaOpen.value
  }
}

function toggleMasterData() {
  if (props.sidebarOpen) {
    masterDataOpen.value = !masterDataOpen.value
  }
}

function toggleInstalasi() {
  if (props.sidebarOpen) {
    instalasiOpen.value = !instalasiOpen.value
  }
}

function toggleTransaksi() {
  if (props.sidebarOpen) {
    transaksiOpen.value = !transaksiOpen.value
  }
}

function handleMenuClick() {
  if (props.mobileSidebarOpen) {
    emit('close-mobile-sidebar')
  }
}

watch(
  () => props.sidebarOpen,
  (val) => {
    if (!val) {
      settingsOpen.value = false
      BasisDataOpen.value = false
      pelangganOpen.value = false
      desaOpen.value = false
      masterDataOpen.value = false
      instalasiOpen.value = false
      transaksiOpen.value = false
    }
  },
)
</script>
