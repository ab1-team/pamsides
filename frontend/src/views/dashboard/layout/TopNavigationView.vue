<template>
  <header class="topnav">
    <button class="mobile-menu-toggle" @click="$emit('toggle-mobile-sidebar')">
      <font-awesome-icon icon="bars" width="20" height="20" />
    </button>

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
      <button class="mobile-search-toggle" @click="$emit('toggle-mobile-search')">
        <font-awesome-icon icon="search" width="20" height="20" />
      </button>

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
              <div class="avatar-dropdown-name">Administrator</div>
              <div class="avatar-dropdown-email">admin@mataair.id</div>
            </div>
          </div>
          <div class="avatar-dropdown-divider"></div>
          <button class="avatar-dropdown-item">
            <font-awesome-icon icon="user" width="15" height="15" />
            Profil
          </button>
          <button class="avatar-dropdown-item logout" @click="$emit('logout')">
            <font-awesome-icon icon="sign-out-alt" width="15" height="15" />
            Logout
          </button>
        </div>
      </div>
    </div>
  </header>

  <div class="mobile-search-popup" :class="{ active: mobileSearchOpen }">
    <input
      type="text"
      class="mobile-search-input"
      placeholder="Search data..."
      :value="searchQuery"
      @input="$emit('search', $event)"
      ref="mobileSearchInput"
    />
    <button class="mobile-search-close" @click="$emit('close-mobile-search')">
      <font-awesome-icon icon="times" width="20" height="20" />
    </button>
  </div>
</template>

<script setup>
import { ref, onMounted, onBeforeUnmount } from 'vue'

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
