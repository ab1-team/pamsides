import { computed, watch } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useInstalasiStore } from '@/stores/instalasiStore'

export function useInstalasiStatus() {
  const store = useInstalasiStore()
  const route = useRoute()
  const router = useRouter()

  if (route.query.filter && route.query.filter !== store.activeStatus) {
    store.activeStatus = route.query.filter
  }

  const activeLabel = computed(() => {
    return store.menuList.find((m) => m.key === store.activeStatus)?.label || ''
  })

  const currentData = computed(() => store.dataMap[store.activeStatus] || [])

  const filteredData = computed(() => {
    if (!store.searchQuery) return currentData.value
    const q = store.searchQuery.toLowerCase()
    return currentData.value.filter(
      (item) =>
        item.name.toLowerCase().includes(q) ||
        item.id.toLowerCase().includes(q) ||
        item.address.toLowerCase().includes(q),
    )
  })

  const totalPages = computed(() =>
    Math.max(1, Math.ceil(filteredData.value.length / store.perPage)),
  )

  const visiblePages = computed(() => {
    const pages = []
    for (let i = 1; i <= totalPages.value; i++) pages.push(i)
    return pages
  })

  const paginatedData = computed(() => {
    const start = (store.currentPage - 1) * store.perPage
    return filteredData.value.slice(start, start + store.perPage)
  })

  const prevPage = () => {
    if (store.currentPage > 1) store.currentPage--
  }
  const nextPage = () => {
    if (store.currentPage < totalPages.value) store.currentPage++
  }
  const goToPage = (page) => {
    store.currentPage = page
  }

  watch(
    [() => store.activeStatus, () => store.searchQuery],
    () => {
      store.currentPage = 1
    },
  )

  watch(
    () => store.activeStatus,
    (val) => {
      if (route.path === '/instalasi/status' && route.query.filter !== val) {
        router.replace({ path: '/instalasi/status', query: { filter: val } })
      }
    },
  )

  const exportData = () => console.log('Export Excel for', activeLabel.value)
  const printData = () => console.log('Print Table for', activeLabel.value)

  return {
    activeStatus: computed({
      get: () => store.activeStatus,
      set: (v) => {
        store.activeStatus = v
      },
    }),
    activeLabel,
    currentPage: computed({
      get: () => store.currentPage,
      set: (v) => {
        store.currentPage = v
      },
    }),
    perPage: computed({
      get: () => store.perPage,
      set: (v) => {
        store.perPage = v
      },
    }),
    searchQuery: computed({
      get: () => store.searchQuery,
      set: (v) => {
        store.searchQuery = v
      },
    }),
    menuList: store.menuList,
    dataMap: computed(() => store.dataMap),
    filteredData,
    totalPages,
    visiblePages,
    paginatedData,
    statusStyle: store.statusStyle,
    prevPage,
    nextPage,
    goToPage,
    exportData,
    printData,
    fetchData: store.fetchData,
    isLoading: computed(() => store.isLoading),
    getCategoryByStatus: store.getCategoryByStatus,
  }
}

