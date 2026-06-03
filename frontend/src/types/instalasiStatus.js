// Type definitions for Status Instalasi lifecycle

export const INSTALASI_STATUS_TYPES = {
  PERMOHONAN: 'Permohonan',
  PASANG_BARU: 'Pasang Baru',
  AKTIF: 'Aktif',
  BLOKIR: 'Blokir',
  CABUT: 'Cabut',
}

export const INSTALASI_RAW_STATUS_LABELS = {
  draft: 'Draft',
  pending: 'Pending',
  surveyed: 'Surveyed',
  unpaid: 'Unpaid',
  processing: 'Processing',
  completed: 'Completed',
  suspended: 'Suspended',
  terminated: 'Terminated',
}

export const INSTALASI_STATUS_COLORS = {
  draft: 'bg-slate-50 text-slate-600 border-slate-200',
  pending: 'bg-blue-50 text-blue-600 border-blue-200',
  surveyed: 'bg-amber-50 text-amber-600 border-amber-200',
  unpaid: 'bg-orange-50 text-orange-600 border-orange-200',
  processing: 'bg-sky-50 text-sky-600 border-sky-200',
  completed: 'bg-green-50 text-green-600 border-green-200',
  suspended: 'bg-rose-50 text-rose-600 border-rose-200',
  terminated: 'bg-red-50 text-red-600 border-red-200',
}

export const INSTALASI_MENU_LIST = [
  { key: 'permohonan', label: 'Permohonan', icon: '📋', variant: 'primary-gradient' },
  { key: 'pasang_baru', label: 'Pasang Baru', icon: '🔵', variant: 'info-gradient' },
  { key: 'aktif', label: 'Aktif', icon: '🟢', variant: 'success-gradient' },
  { key: 'blokir', label: 'Blokir', icon: '🟠', variant: 'warning-gradient' },
  { key: 'cabut', label: 'Cabut', icon: '🔴', variant: 'danger-gradient' },
]
