import { ref, computed, watch, onMounted } from 'vue'
import { INSTALASI_STATUS_COLORS, INSTALASI_MENU_LIST } from '@/types/instalasiStatus'
import ticketService from '@/services/ticket.service'

export function useInstalasiStatus() {
  const activeStatus = ref('permohonan')
  const currentPage = ref(1)
  const perPage = ref(10)
  const searchQuery = ref('')
  const isLoading = ref(false)

  const menuList = INSTALASI_MENU_LIST

  const activeLabel = computed(() => {
    return menuList.find((m) => m.key === activeStatus.value)?.label || ''
  })

  // Peta data dinamis dari database
  const dataMap = ref({
    permohonan: [],
    pasang_baru: [],
    aktif: [],
    blokir: [],
    cabut: [],
  })

  const fetchData = async () => {
    try {
      isLoading.value = true
      const response = await ticketService.getTickets({ per_page: 150 })
      if (response?.success && response?.data?.data) {
        const freshMap = {
          permohonan: [],
          pasang_baru: [],
          aktif: [],
          blokir: [],
          cabut: [],
        }

        response.data.data.forEach((ticket) => {
          const status = ticket.status
          let category = 'permohonan'
          let mappedStatusLabel = 'Permohonan'

          if (['surveyed', 'unpaid', 'processing'].includes(status)) {
            category = 'pasang_baru'
            mappedStatusLabel = 'Pasang Baru'
          } else if (status === 'completed') {
            category = 'aktif'
            mappedStatusLabel = 'Aktif'
          }

          freshMap[category].push({
            id: ticket.customer?.[0]?.customer_code || `#INS-${ticket.id.toString().padStart(4, '0')}`,
            name: ticket.applicant_name || '-',
            initials: ticket.applicant_name
              ? ticket.applicant_name
                  .split(' ')
                  .map((n) => n[0])
                  .join('')
                  .toUpperCase()
                  .substring(0, 2)
              : '?',
            color: ['#3B82F6', '#8B5CF6', '#10B981', '#F59E0B', '#EF4444'][ticket.id % 5],
            type: ticket.package?.name || '-',
            address: ticket.address || '-',
            status: mappedStatusLabel,
          })
        })
        dataMap.value = freshMap
      }
    } catch (error) {
      console.error('Failed to fetch installation statuses:', error)
    } finally {
      isLoading.value = false
    }
  }

  onMounted(() => {
    fetchData()
  })

  const currentData = computed(() => dataMap.value[activeStatus.value] || [])

  const filteredData = computed(() => {
    if (!searchQuery.value) return currentData.value
    const q = searchQuery.value.toLowerCase()
    return currentData.value.filter(
      (item) =>
        item.name.toLowerCase().includes(q) ||
        item.id.toLowerCase().includes(q) ||
        item.address.toLowerCase().includes(q),
    )
  })

  const totalPages = computed(() =>
    Math.max(1, Math.ceil(filteredData.value.length / perPage.value)),
  )

  const visiblePages = computed(() => {
    const pages = []
    for (let i = 1; i <= totalPages.value; i++) {
      pages.push(i)
    }
    return pages
  })

  const paginatedData = computed(() => {
    const start = (currentPage.value - 1) * perPage.value
    return filteredData.value.slice(start, start + perPage.value)
  })

  const prevPage = () => {
    if (currentPage.value > 1) currentPage.value--
  }
  const nextPage = () => {
    if (currentPage.value < totalPages.value) currentPage.value++
  }
  const goToPage = (page) => {
    currentPage.value = page
  }

  // Reset halaman saat status atau pencarian berubah
  watch([activeStatus, searchQuery], () => {
    currentPage.value = 1
  })

  const exportData = () => console.log('Export Excel for', activeLabel.value)
  const printData = () => console.log('Print Table for', activeLabel.value)

  return {
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
    statusStyle: INSTALASI_STATUS_COLORS,
    prevPage,
    nextPage,
    goToPage,
    exportData,
    printData,
  }
}
