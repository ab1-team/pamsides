import { computed, watch } from 'vue'

import { useRoute, useRouter } from 'vue-router'

import { useInstalasiStore } from '@/stores/instalasiStore'
import { INSTALASI_MENU_LIST } from '@/types/instalasiStatus'

const ALLOWED_FILTERS = INSTALASI_MENU_LIST.map((m) => m.key)

export function useInstalasiStatus() {
  const store = useInstalasiStore()
  const route = useRoute()
  const router = useRouter()

  // const fetchData = async () => {
  //   try {
  //     isLoading.value = true
  //     const response = await ticketService.getTickets({ per_page: 150 })
  //     if (response?.success && response?.data?.data) {
  //       const freshMap = {
  //         permohonan: [],
  //         pasang_baru: [],
  //         aktif: [],
  //         blokir: [],
  //         cabut: [],
  //       }

  //       response.data.data.forEach((ticket) => {
  //         const status = ticket.status
  //         let category = null
  //         let mappedStatusLabel = ''

  //         if (status === 'pending') {
  //           category = 'permohonan'
  //           mappedStatusLabel = 'Permohonan'
  //         } else if (['surveyed', 'unpaid', 'processing'].includes(status)) {
  //           category = 'pasang_baru'
  //           mappedStatusLabel = 'Pasang Baru'
  //         } else if (status === 'completed') {
  //           category = 'aktif'
  //           mappedStatusLabel = 'Aktif'
  //         } else if (status === 'suspended') {
  //           category = 'blokir'
  //           mappedStatusLabel = 'Blokir'
  //         } else if (status === 'terminated') {
  //           category = 'cabut'
  //           mappedStatusLabel = 'Cabut'
  //         }

  //         if (!category) return

  //         freshMap[category].push({
  //           id:
  //             ticket.customer?.[0]?.customer_code ||
  //             `#INS-${ticket.id.toString().padStart(4, '0')}`,
  //           ticketId: ticket.id,
  //           name: ticket.applicant_name || '-',
  //           nik: ticket.nik || '-',
  //           phone: ticket.phone || '-',
  //           initials: ticket.applicant_name
  //             ? ticket.applicant_name
  //                 .split(' ')
  //                 .map((n) => n[0])
  //                 .join('')
  //                 .toUpperCase()
  //                 .substring(0, 2)
  //             : '?',
  //           color: ['#3B82F6', '#8B5CF6', '#10B981', '#F59E0B', '#EF4444'][ticket.id % 5],
  //           type: ticket.package?.name || '-',
  //           packageId: ticket.package_id || null,
  //           address: ticket.address || '-',
  //           village: ticket.village?.name || '-',
  //           villageId: ticket.village_id || null,
  //           lat: ticket.lat || null,
  //           lng: ticket.lng || null,
  //           orderDate: ticket.order_date || '-',
  //           category: mappedStatusLabel,
  //           status: INSTALASI_RAW_STATUS_LABELS[status] || status,
  //           rawStatus: status,
  //           createdAt: ticket.created_at || '-',
  //           updatedAt: ticket.updated_at || '-',
  //           rawData: ticket,
  //           surveyInfo: ticket.survey?.[0]
  //             ? {
  //                 id: ticket.survey[0].id,
  //                 distance_to_pipe_m: ticket.survey[0].distance_to_pipe_m,
  //                 material_notes: ticket.survey[0].material_notes,
  //                 photo_url: ticket.survey[0].photo_url,
  //                 surveyed_at: ticket.survey[0].surveyed_at,
  //                 surveyor_name: ticket.survey[0].surveyor?.name || '-',
  //                 surveyor_id: ticket.survey[0].surveyor_id,
  //                 ticket: ticket,
  //               }
  //             : null,
  //         })
  //       })
  //       dataMap.value = freshMap
  //     }
  //   } catch (error) {
  //     console.error('Failed to fetch installation statuses:', error)
  //   } finally {
  //     isLoading.value = false
  //   }
  // }

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

  watch([() => store.activeStatus, () => store.searchQuery], () => {
    store.currentPage = 1
  })

  watch(
    () => store.activeStatus,
    (val) => {
      if (route.path === '/app/instalasi/status' && route.query.filter !== val) {
        router.replace({ path: '/app/instalasi/status', query: { filter: val } })
      }
    },
  )

  watch(
    () => route.query.filter,
    (val) => {
      if (ALLOWED_FILTERS.includes(val) && store.activeStatus !== val) {
        store.activeStatus = val
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
