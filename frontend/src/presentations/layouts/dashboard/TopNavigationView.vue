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
        <a href="#" class="topnav-link active">Dashboard</a>
        <a href="#" class="topnav-link">Reports</a>
      </div>

      <div class="topnav-right">
        <BaseButton
          variant="ghost"
          class="mobile-search-toggle"
          @click="$emit('toggle-mobile-search')"
          icon="search"
        />

        <div class="topnav-search-modern">
          <font-awesome-icon icon="search" width="12" height="12" />
          <input
            type="text"
            placeholder="Search data..."
            :value="searchQuery"
            @input="$emit('search', $event)"
          />
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
          placeholder="Cari data..."
          :value="searchQuery"
          @input="$emit('search', $event)"
          ref="mobileSearchInput"
        />
        <button
          class="mobile-search-close-btn"
          @click="$emit('close-mobile-search')"
          aria-label="Close search"
        >
          <font-awesome-icon icon="times" />
        </button>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted, onBeforeUnmount, computed } from 'vue'
import BaseButton from '@/presentations/components/ui/BaseButton.vue'
import { useUiStore } from '@/stores/uiStore'

defineProps({
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

defineEmits([
  'toggle-mobile-sidebar',
  'toggle-mobile-search',
  'close-mobile-search',
  'search',
  'logout',
])

const uiStore = useUiStore()

const userName = computed(() => {
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
  switch (uiStore.userRole) {
    case 'surveyor':
      return 'Field Surveyor'
    case 'teknisi':
      return 'Technical Staff'
    case 'pelanggan':
      return 'Customer User'
    default:
      return 'System Administrator'
  }
})

const avatarDropdownOpen = ref(false)
const avatarRef = ref(null)
const mobileSearchInput = ref(null)

const handleClickOutside = (e) => {
  if (avatarRef.value && !avatarRef.value.contains(e.target)) {
    avatarDropdownOpen.value = false
  }
}

onMounted(() => {
  document.addEventListener('click', handleClickOutside)
})

onBeforeUnmount(() => {
  document.removeEventListener('click', handleClickOutside)
})
</script>
