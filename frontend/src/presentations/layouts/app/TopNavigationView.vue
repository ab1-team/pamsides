<template>
  <div class="topnav-root">
    <header class="topnav">
      <BaseButton
        variant="ghost"
        class="mobile-menu-toggle"
        @click="$emit('toggle-mobile-sidebar')"
        icon="bars"
      />

      <div
        class="topnav-left"
        :style="{
          width: sidebarOpen ? 'var(--sidebar-width)' : 'var(--sidebar-collapsed-width)',
          padding: sidebarOpen ? '0 0.75rem' : '0 0.5rem',
        }"
      >
        <span class="topnav-brand">Mata Air</span>
      </div>

      <div class="topnav-links">
        <a href="#" class="topnav-link active">Beranda</a>
        <a href="#" class="topnav-link">Laporan</a>
      </div>

      <div class="topnav-right">
        <BaseButton
          variant="ghost"
          class="mobile-search-toggle"
          @click="$emit('toggle-mobile-search')"
          icon="search"
        />

        <div class="topnav-search-modern" ref="searchWrapperRef">
          <font-awesome-icon icon="search" width="12" height="12" />
          <input
            ref="searchInputRef"
            type="text"
            :placeholder="canSearchCustomers ? 'Cari nama pelanggan...' : 'Pencarian tidak tersedia'"
            :disabled="!canSearchCustomers"
            :title="canSearchCustomers ? '' : 'Pencarian pelanggan hanya tersedia untuk admin dan surveyor'"
            :value="searchQuery"
            @input="onSearchInput"
            @focus="searchDropdownOpen = true"
            @keydown.escape="closeSearchDropdown"
            autocomplete="off"
          />
          <button
            v-if="searchQuery && canSearchCustomers"
            class="topnav-search-clear"
            @click="clearSearch"
            aria-label="Bersihkan pencarian"
            type="button"
          >
            <font-awesome-icon icon="times" width="10" height="10" />
          </button>

          <div v-if="searchDropdownOpen && searchQuery" class="topnav-search-dropdown">
            <div v-if="searchLoading" class="topnav-search-empty">
              <font-awesome-icon icon="spinner" spin class="mr-2" />
              <span>Mencari data...</span>
            </div>
            <div
              v-else-if="searchResults.length === 0"
              class="topnav-search-empty"
            >
              <font-awesome-icon icon="search" class="mr-2 text-slate-400" />
              <span>Pelanggan tidak ditemukan</span>
            </div>
            <ul v-else class="topnav-search-list">
              <li
                v-for="item in searchResults"
                :key="`${item.category}-${item.ticketId}`"
                class="topnav-search-item"
                @mousedown.prevent="selectResult(item)"
              >
                <div
                  class="topnav-search-avatar"
                  :style="{ backgroundColor: item.color }"
                >
                  {{ item.initials }}
                </div>
                <div class="topnav-search-info">
                  <p class="topnav-search-name">{{ item.name }}</p>
                  <p class="topnav-search-meta">
                    <span class="topnav-search-id">ID: {{ item.id }}</span>
                    <span class="topnav-search-dot">·</span>
                    <span class="topnav-search-addr">{{ item.address }}</span>
                  </p>
                </div>
                <span
                  class="topnav-search-status"
                  :class="categoryBadgeClass(item.category)"
                >
                  {{ categoryLabel(item.category) }}
                </span>
              </li>
            </ul>
          </div>
        </div>

        <div class="topnav-icon-btn">
          <font-awesome-icon icon="bell" width="15" height="15" />
        </div>

        <div class="topnav-icon-btn">
          <font-awesome-icon icon="question-circle" width="15" height="15" />
        </div>

        <div class="topnav-avatar-wrapper" ref="avatarRef">
          <div class="topnav-avatar" @click="avatarDropdownOpen = !avatarDropdownOpen">
            <font-awesome-icon icon="user" width="20" height="20" style="color: white" />
          </div>

          <div class="avatar-dropdown" v-if="avatarDropdownOpen">
            <div class="avatar-dropdown-header">
              <div class="avatar-dropdown-avatar">
                <font-awesome-icon icon="user" width="22" height="22" />
              </div>
              <div class="avatar-dropdown-info">
                <div class="avatar-dropdown-name">{{ userName }}</div>
                <div class="avatar-dropdown-email">{{ userRoleLabel }}</div>
              </div>
            </div>
            <div class="avatar-dropdown-divider"></div>
            <a href="/profil" class="avatar-dropdown-item" block>
              <font-awesome-icon icon="user" class="mr-3" />
              Profil
            </a>
            <BaseButton
              variant="ghost"
              class="avatar-dropdown-item logout"
              @click="$emit('logout')"
              block
            >
              <font-awesome-icon icon="sign-out-alt" class="mr-3" />
              Logout
            </BaseButton>
          </div>
        </div>
      </div>
    </header>

    <div class="mobile-search-popup" :class="{ active: mobileSearchOpen }">
      <div class="mobile-search-container">
        <font-awesome-icon icon="search" class="mobile-search-inner-icon" />
        <input
          type="text"
          class="mobile-search-input"
          placeholder="Cari nama pelanggan..."
          :value="searchQuery"
          @input="onSearchInput"
          @focus="searchDropdownOpen = true"
          @keydown.escape="closeSearchDropdown"
          ref="mobileSearchInput"
          autocomplete="off"
        />
        <button
          class="mobile-search-close-btn"
          @click="$emit('close-mobile-search')"
          aria-label="Tutup pencarian"
        >
          <font-awesome-icon icon="times" />
        </button>
      </div>

      <div
        v-if="mobileSearchOpen && searchQuery && searchResults.length > 0"
        class="mobile-search-results"
      >
        <ul class="topnav-search-list">
          <li
            v-for="item in searchResults"
            :key="`m-${item.category}-${item.ticketId}`"
            class="topnav-search-item"
            @mousedown.prevent="selectResult(item)"
          >
            <div
              class="topnav-search-avatar"
              :style="{ backgroundColor: item.color }"
            >
              {{ item.initials }}
            </div>
            <div class="topnav-search-info">
              <p class="topnav-search-name">{{ item.name }}</p>
              <p class="topnav-search-meta">
                <span class="topnav-search-id">ID: {{ item.id }}</span>
              </p>
            </div>
            <span
              class="topnav-search-status"
              :class="categoryBadgeClass(item.category)"
            >
              {{ categoryLabel(item.category) }}
            </span>
          </li>
        </ul>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted, onBeforeUnmount, computed, watch } from 'vue'
import BaseButton from '@/presentations/components/ui/BaseButton.vue'
import { useUiStore } from '@/stores/uiStore'
import { useInstalasiStore } from '@/stores/instalasiStore'

const props = defineProps({
  sidebarOpen: {
    type: Boolean,
    default: true,
  },
  searchQuery: {
    type: String,
    default: '',
  },
  mobileSearchOpen: {
    type: Boolean,
    default: false,
  },
})

const emit = defineEmits([
  'toggle-mobile-sidebar',
  'toggle-mobile-search',
  'close-mobile-search',
  'search',
  'select-result',
  'logout',
])

const uiStore = useUiStore()

// Pencarian pelanggan bersumber dari data /installation-tickets yang hanya
// boleh diakses admin & surveyor. Untuk role lain kotak ini diberi disabled,
// bukan dibiarkan "tidak ada hasil" yang menyesatkan.
const canSearchCustomers = computed(() => ['admin', 'surveyor'].includes(uiStore.userRole))
const instalasiStore = useInstalasiStore()

const userData = computed(() => {
  const data = localStorage.getItem('user_data')
  return data ? JSON.parse(data) : null
})

const userName = computed(() => {
  if (userData.value) {
    return userData.value.name
  }
  switch (uiStore.userRole) {
    case 'surveyor':
      return 'Ahmad Surveyor'
    case 'teknisi':
      return 'Dedi Teknisi'
    case 'pelanggan':
      return 'Bambang Susanto'
    default:
      return 'Administrator'
  }
})

const userRoleLabel = computed(() => {
  if (userData.value) {
    return userData.value.role.charAt(0).toUpperCase() + userData.value.role.slice(1)
  }
  switch (uiStore.userRole) {
    case 'surveyor':
      return 'Surveyor Lapangan'
    case 'teknisi':
      return 'Staf Teknisi'
    case 'pelanggan':
      return 'Pelanggan'
    default:
      return 'Administrator Sistem'
  }
})

const avatarDropdownOpen = ref(false)
const avatarRef = ref(null)
const searchWrapperRef = ref(null)
const searchInputRef = ref(null)
const mobileSearchInput = ref(null)
const searchDropdownOpen = ref(false)
const searchLoading = ref(false)
const searchResults = ref([])

const CATEGORY_LABELS = {
  permohonan: 'Permohonan',
  pasang_baru: 'Pasang Baru',
  aktif: 'Aktif',
  blokir: 'Blokir',
  cabut: 'Cabut',
}

const CATEGORY_BADGES = {
  permohonan: 'bg-blue-50 text-blue-600 border-blue-200',
  pasang_baru: 'bg-sky-50 text-sky-600 border-sky-200',
  aktif: 'bg-emerald-50 text-emerald-600 border-emerald-200',
  blokir: 'bg-orange-50 text-orange-600 border-orange-200',
  cabut: 'bg-rose-50 text-rose-600 border-rose-200',
}

const CATEGORY_ROUTES = {
  permohonan: 'Detail Permohonan',
  pasang_baru: 'Detail Pasang Baru',
  aktif: 'Detail Aktif',
  blokir: 'Detail Blokir',
  cabut: 'Detail Cabut',
}

const categoryLabel = (key) => CATEGORY_LABELS[key] || key
const categoryBadgeClass = (key) => CATEGORY_BADGES[key] || 'bg-slate-50 text-slate-600 border-slate-200'

let debounceTimer = null
const DEBOUNCE_MS = 200

const performSearch = (query) => {
  const q = (query || '').trim().toLowerCase()
  if (!q || !canSearchCustomers.value) {
    searchResults.value = []
    searchLoading.value = false
    return
  }

  searchLoading.value = true

  // Data sudah ada di store (hasil instalasiStore.fetchData)
  const dataMap = instalasiStore.dataMap || {}
  const all = []
  Object.keys(dataMap).forEach((category) => {
    const items = dataMap[category] || []
    items.forEach((item) => {
      all.push({ ...item, category })
    })
  })

  const matched = all
    .filter(
      (item) =>
        (item.name || '').toLowerCase().includes(q) ||
        (item.id || '').toLowerCase().includes(q) ||
        (item.address || '').toLowerCase().includes(q),
    )
    .slice(0, 8)

  searchResults.value = matched
  searchLoading.value = false
}

const onSearchInput = (event) => {
  const value = event.target.value
  emit('search', event)
  searchDropdownOpen.value = true

  if (debounceTimer) clearTimeout(debounceTimer)
  debounceTimer = setTimeout(() => {
    performSearch(value)
  }, DEBOUNCE_MS)
}

const clearSearch = () => {
  emit('search', { target: { value: '' } })
  searchResults.value = []
  searchDropdownOpen.value = false
  if (searchInputRef.value) searchInputRef.value.focus()
}

const closeSearchDropdown = () => {
  searchDropdownOpen.value = false
}

const selectResult = (item) => {
  const routeName = CATEGORY_ROUTES[item.category]
  if (!routeName) return
  searchDropdownOpen.value = false
  emit('close-mobile-search')
  emit('select-result', {
    routeName,
    id: item.id,
    ticketId: item.ticketId,
    category: item.category,
  })
}

// Tutup dropdown saat klik di luar area search
const handleClickOutside = (e) => {
  if (avatarRef.value && !avatarRef.value.contains(e.target)) {
    avatarDropdownOpen.value = false
  }
  if (
    searchWrapperRef.value &&
    !searchWrapperRef.value.contains(e.target) &&
    !e.target.closest('.mobile-search-popup')
  ) {
    searchDropdownOpen.value = false
  }
}

// Tutup dropdown saat route berganti / dataMap berubah
watch(
  () => instalasiStore.activeStatus,
  () => {
    if (searchDropdownOpen.value && props.searchQuery) {
      performSearch(props.searchQuery)
    }
  },
)

// Jika searchQuery berubah dari luar (misal dari halaman lain), refresh hasil
watch(
  () => props.searchQuery,
  (newVal) => {
    if (searchDropdownOpen.value && newVal) {
      performSearch(newVal)
    } else if (!newVal) {
      searchResults.value = []
    }
  },
)

onMounted(() => {
  document.addEventListener('click', handleClickOutside)
  // Pastikan data instalasi sudah di-load agar pencarian langsung tersedia.
  // Hanya untuk role yang punya akses ke /installation-tickets
  // (admin & surveyor). Untuk pelanggan & teknisi endpoint itu 403, sehingga
  // pemanggilan di sini menghasilkan error yang ditelan tanpa pesan — gejalanya
  // kotak pencarian selalu "tidak ada hasil" tanpa penjelasan.
  if (canSearchCustomers.value && instalasiStore && typeof instalasiStore.fetchData === 'function') {
    const dataMap = instalasiStore.dataMap || {}
    const total =
      (dataMap.permohonan?.length || 0) +
      (dataMap.pasang_baru?.length || 0) +
      (dataMap.aktif?.length || 0) +
      (dataMap.blokir?.length || 0) +
      (dataMap.cabut?.length || 0)
    if (total === 0) {
      instalasiStore.fetchData()
    }
  }
})

onBeforeUnmount(() => {
  document.removeEventListener('click', handleClickOutside)
  if (debounceTimer) clearTimeout(debounceTimer)
})
</script>
