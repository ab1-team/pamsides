<template>
  <div class="hasil-survey-root p-4 lg:p-6">
    <div class="flex flex-col lg:flex-row lg:items-start lg:justify-between gap-4! mb-6!">
      <div class="flex-1!">
        <h1 class="text-xl md:text-2xl font-bold text-cyan-600! tracking-tight mb-1!">
          Hasil Survey Lapangan
        </h1>
        <p class="text-xs md:text-sm text-slate-500! leading-relaxed">
          Kelola dan verifikasi hasil survey dari surveyor lapangan untuk permohonan instalasi baru.
        </p>
      </div>

      <div class="flex flex-wrap gap-2 md:gap-3! w-full lg:w-auto!">
        <BaseButton
          variant="info-gradient"
          size="md"
          @click="fetchSurveys"
          class="w-full! lg:w-auto! rounded-xl! shadow-md! text-xs md:text-sm"
          icon="redo-alt"
        >
          Muat Ulang Data
        </BaseButton>
      </div>
    </div>

    <ContentCard variant="elevated" padding="none" class="overflow-hidden!">
      <DataTable
        :data="filteredSurveys"
        :columns="tableColumns"
        v-model:current-page="currentPage"
        v-model:per-page="perPage"
        :total-pages="totalPages"
        :visible-pages="visiblePages"
        :total-entries="filteredSurveys.length"
        v-model="searchQuery"
        search-placeholder="Cari nama pemohon, NIK, atau alamat..."
        empty-title="Hasil Survey Tidak Ditemukan"
        empty-message="Belum ada hasil survey atau mohon lakukan pencarian kembali."
        no-card
        row-clickable
        @row-click="handleOpenDetail"
      >
        <template #column-pemohon="{ row }">
          <div class="flex items-center gap-3!">
            <div
              class="w-9! h-9! rounded-xl! bg-gradient-to-br! from-orange-500! to-orange-600! flex! items-center! justify-center! text-white! text-xs! font-bold! shrink-0! shadow-sm!"
            >
              {{ getInitials(row) }}
            </div>
            <div>
              <div class="font-bold! text-sm! text-slate-800! mb-0.5!">
                {{ row.ticket?.applicant_name || '-' }}
              </div>
              <div class="text-[10px]! font-black! text-slate-400! uppercase! tracking-wide!">
                NIK: {{ row.ticket?.nik || '-' }}
              </div>
            </div>
          </div>
        </template>

        <template #column-jarak="{ row }">
          <div class="flex items-center gap-2!">
            <span class="text-sm! font-bold! text-cyan-600!">
              {{ row.distance_to_pipe_m || 0 }}
            </span>
            <span class="text-xs! text-slate-400! font-medium!">meter</span>
          </div>
        </template>

        <template #column-surveyor="{ row }">
          <div class="text-sm! font-medium! text-slate-700!">
            {{ row.surveyor?.name || '-' }}
          </div>
        </template>

        <template #column-tanggal="{ row }">
          <div class="text-xs! font-bold! text-slate-600!">
            {{ formatDate(row.surveyed_at) }}
          </div>
        </template>

        <template #column-aksi="{ row }">
          <div class="flex items-center gap-2!" @click.stop>
            <BaseButton
              variant="ghost"
              size="sm"
              @click="handleEdit(row)"
              class="w-8! h-8! p-0! rounded-lg! border! border-slate-100! hover:border-orange-200! hover:bg-orange-50! text-slate-600! hover:text-orange-600! shadow-sm!"
              title="Edit"
              icon="edit"
            />
            <BaseButton
              variant="ghost"
              size="sm"
              @click="handleDelete(row)"
              class="w-8! h-8! p-0! rounded-lg! border! border-slate-100! hover:border-red-200! hover:bg-red-50! text-slate-600! hover:text-red-600! shadow-sm!"
              title="Delete"
              icon="trash"
            />
          </div>
        </template>
      </DataTable>
    </ContentCard>

    <DetailSurveyModal
      :show="showDetailModal"
      :survey="selectedSurvey"
      @close="showDetailModal = false"
    />

    <EditSurveyModal
      :show="showEditModal"
      :survey="selectedSurvey"
      @close="showEditModal = false"
      @save="handleSaveEdit"
    />
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import ticketService from '@/services/ticket.service'
import { useUiStore } from '@/stores/uiStore'
import BaseButton from '@/presentations/components/ui/BaseButton.vue'
import ContentCard from '@/presentations/components/ui/ContentCard.vue'
import DataTable from '@/presentations/components/ui/DataTable.vue'
import DetailSurveyModal from './partials/DetailSurveyModal.vue'
import EditSurveyModal from './partials/EditSurveyModal.vue'
import Swal from 'sweetalert2'

const uiStore = useUiStore()

const surveys = ref([])
const isLoading = ref(false)
const searchQuery = ref('')
const currentPage = ref(1)
const perPage = ref(10)

const selectedSurvey = ref(null)
const showDetailModal = ref(false)
const showEditModal = ref(false)

const tableColumns = [
  { key: 'pemohon', title: 'PEMOHON / NIK' },
  { key: 'jarak', title: 'JARAK PIPA' },
  { key: 'surveyor', title: 'SURVEYOR' },
  { key: 'tanggal', title: 'TANGGAL SURVEY' },
  { key: 'aksi', title: 'AKSI' },
]

const fetchSurveys = async () => {
  isLoading.value = true
  try {
    const res = await ticketService.getTickets({ status: 'surveyed' })
    if (res.data && res.data.data) {
      // Data dari API adalah array of tickets yang sudah di-survey
      // Kita perlu mapping untuk menampilkan data survey
      surveys.value = res.data.data.map(ticket => {
        // Ambil survey result dari relasi ticket
        const survey = Array.isArray(ticket.survey) ? ticket.survey[0] : ticket.survey
        
        // Perbaiki URL foto agar sesuai dengan backend
        let photoUrl = survey?.photo_url
        if (photoUrl) {
          // Ganti URL localhost yang salah dengan URL backend yang benar
          photoUrl = photoUrl.replace(
            'http://localhost/pamsides-v2/backend/public',
            'http://localhost:8000'
          )
        }
        
        return {
          id: survey?.id || ticket.id,
          ticket_id: ticket.id,
          ticket: ticket,
          surveyor_id: survey?.surveyor_id,
          surveyor: survey?.surveyor,
          distance_to_pipe_m: survey?.distance_to_pipe_m || 0,
          material_notes: survey?.material_notes || '-',
          photo_url: photoUrl,
          surveyed_at: survey?.surveyed_at || survey?.created_at,
        }
      })
    }
  } catch (err) {
    console.error('Gagal memuat data survey:', err)
    uiStore.error('Gagal memuat data survey')
  } finally {
    isLoading.value = false
  }
}

const getInitials = (row) => {
  const name = row.ticket?.applicant_name || 'S'
  return name
    .split(' ')
    .map((word) => word[0])
    .join('')
    .slice(0, 2)
    .toUpperCase()
}

const formatDate = (dateStr) => {
  if (!dateStr) return '-'
  try {
    const d = new Date(dateStr)
    const months = [
      'Jan',
      'Feb',
      'Mar',
      'Apr',
      'Mei',
      'Jun',
      'Jul',
      'Agu',
      'Sep',
      'Okt',
      'Nov',
      'Des',
    ]
    return `${d.getDate()} ${months[d.getMonth()]} ${d.getFullYear()}`
  } catch (err) {
    return dateStr
  }
}

const filteredSurveys = computed(() => {
  if (!searchQuery.value) return surveys.value
  const query = searchQuery.value.toLowerCase()
  return surveys.value.filter((s) => {
    const name = (s.ticket?.applicant_name || '').toLowerCase()
    const textNik = (s.ticket?.nik || '').toLowerCase()
    const address = (s.ticket?.address || '').toLowerCase()
    return name.includes(query) || textNik.includes(query) || address.includes(query)
  })
})

const totalPages = computed(() => {
  return Math.max(1, Math.ceil(filteredSurveys.value.length / perPage.value))
})

const visiblePages = computed(() => {
  const pages = []
  for (let i = 1; i <= Math.min(3, totalPages.value); i++) {
    pages.push(i)
  }
  return pages
})

const handleOpenDetail = (row) => {
  selectedSurvey.value = row
  showDetailModal.value = true
}

const handleEdit = (row) => {
  selectedSurvey.value = row
  showEditModal.value = true
}

const handleSaveEdit = async (updatedData) => {
  try {
    uiStore.setLoading(true)
    
    // Buat FormData untuk upload foto
    const formData = new FormData()
    formData.append('distance_to_pipe_m', updatedData.distance_to_pipe_m)
    formData.append('material_notes', updatedData.material_notes)
    formData.append('_method', 'PUT')
    
    // Tambahkan foto baru jika ada
    if (updatedData.photo) {
      formData.append('photo', updatedData.photo)
    }
    
    await ticketService.updateSurvey(updatedData.id, formData)
    uiStore.success('Survey berhasil diupdate')
    showEditModal.value = false
    await fetchSurveys()
  } catch (err) {
    console.error(err)
    uiStore.error('Gagal update survey')
  } finally {
    uiStore.setLoading(false)
  }
}

const handleDelete = async (row) => {
  const result = await Swal.fire({
    title: 'Hapus Survey?',
    text: `Hapus hasil survey untuk ${row.ticket?.applicant_name}? Data tidak dapat dikembalikan.`,
    icon: 'warning',
    showCancelButton: true,
    confirmButtonColor: '#ef4444',
    cancelButtonColor: '#6b7280',
    confirmButtonText: 'Ya, Hapus',
    cancelButtonText: 'Batal',
  })

  if (result.isConfirmed) {
    try {
      uiStore.setLoading(true)
      await ticketService.deleteSurvey(row.id)
      uiStore.success('Survey berhasil dihapus')
      await fetchSurveys()
    } catch (err) {
      console.error(err)
      uiStore.error('Gagal menghapus survey')
    } finally {
      uiStore.setLoading(false)
    }
  }
}

onMounted(() => {
  fetchSurveys()
})
</script>

<style scoped>
.hasil-survey-root {
  width: 100%;
}
</style>
