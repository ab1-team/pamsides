import { defineStore } from 'pinia'
import { ref } from 'vue'
import { useRoute } from 'vue-router'
import ticketService from '@/services/ticket.service'
import {
  INSTALASI_STATUS_COLORS,
  INSTALASI_MENU_LIST,
  INSTALASI_RAW_STATUS_LABELS,
} from '@/types/instalasiStatus'

const ALLOWED_FILTERS = ['permohonan', 'pasang_baru', 'aktif', 'blokir', 'cabut']

export const useInstalasiStore = defineStore('instalasi', () => {
  const route = useRoute()
  const initialFilter = (() => {
    const q = route?.query?.filter
    return ALLOWED_FILTERS.includes(q) ? q : 'permohonan'
  })()

  const activeStatus = ref(initialFilter)
  const currentPage = ref(1)
  const perPage = ref(10)
  const searchQuery = ref('')
  const isLoading = ref(false)

  const dataMap = ref({
    permohonan: [],
    pasang_baru: [],
    aktif: [],
    blokir: [],
    cabut: [],
  })

  const menuList = INSTALASI_MENU_LIST
  const statusStyle = INSTALASI_STATUS_COLORS

  const fetchData = async () => {
    try {
      isLoading.value = true
      const response = await ticketService.getTickets({ per_page: 5000 })
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
          let category = null

          if (status === 'pending') category = 'permohonan'
          else if (['surveyed', 'unpaid', 'processing'].includes(status)) category = 'pasang_baru'
          else if (status === 'completed') category = 'aktif'
          else if (status === 'suspended') category = 'blokir'
          else if (status === 'terminated') category = 'cabut'

          if (!category) return

          const latestCustomer = [...(ticket.customer || [])].sort(
            (a, b) => (b.id || 0) - (a.id || 0),
          )[0]

          freshMap[category].push({
            id:
              latestCustomer?.customer_code ||
              `#INS-${ticket.id.toString().padStart(4, '0')}`,
            ticketId: ticket.id,
            name: ticket.applicant_name || '-',
            nik: ticket.nik || '-',
            phone: ticket.phone || '-',
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
            packageId: ticket.package_id || null,
            address: ticket.address || '-',
            village: ticket.village?.name || ticket.village?.village_name || '-',
            villageId: ticket.village_id || null,
            lat: ticket.lat || null,
            lng: ticket.lng || null,
            orderDate: ticket.order_date || '-',
            status: INSTALASI_RAW_STATUS_LABELS[status] || status,
            rawStatus: status,
            createdAt: ticket.created_at || '-',
            updatedAt: ticket.updated_at || '-',
            rawData: ticket,
            surveyInfo: ticket.survey?.[0]
              ? {
                  id: ticket.survey[0].id,
                  distance_to_pipe_m: ticket.survey[0].distance_to_pipe_m,
                  material_notes: ticket.survey[0].material_notes,
                  photo_url: ticket.survey[0].photo_url,
                  surveyed_at: ticket.survey[0].surveyed_at,
                  surveyor_name: ticket.survey[0].surveyor?.name || '-',
                  surveyor_id: ticket.survey[0].surveyor_id,
                  ticket: ticket,
                }
              : null,
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

  const getCategoryByStatus = (rawStatus) => {
    if (rawStatus === 'pending') return 'permohonan'
    if (['surveyed', 'unpaid', 'processing'].includes(rawStatus)) return 'pasang_baru'
    if (rawStatus === 'completed') return 'aktif'
    if (rawStatus === 'suspended') return 'blokir'
    if (rawStatus === 'terminated') return 'cabut'
    return null
  }

  return {
    activeStatus,
    currentPage,
    perPage,
    searchQuery,
    isLoading,
    dataMap,
    menuList,
    statusStyle,
    fetchData,
    getCategoryByStatus,
  }
})
