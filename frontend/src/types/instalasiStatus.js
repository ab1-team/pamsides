// Type definitions for Status Instalasi lifecycle

export const INSTALASI_STATUS_TYPES = {
  PERMOHONAN: 'Permohonan',
  PASANG_BARU: 'Pasang Baru',
  AKTIF: 'Aktif',
  BLOKIR: 'Blokir',
  CABUT: 'Cabut',
}

export const INSTALASI_STATUS_COLORS = {
  [INSTALASI_STATUS_TYPES.PERMOHONAN]: 'bg-blue-50 text-blue-600 border-blue-200',
  [INSTALASI_STATUS_TYPES.PASANG_BARU]: 'bg-sky-50 text-sky-600 border-sky-200',
  [INSTALASI_STATUS_TYPES.AKTIF]: 'bg-green-50 text-green-600 border-green-200',
  [INSTALASI_STATUS_TYPES.BLOKIR]: 'bg-orange-50 text-orange-600 border-orange-200',
  [INSTALASI_STATUS_TYPES.CABUT]: 'bg-red-50 text-red-600 border-red-200',
}

export const INSTALASI_MENU_LIST = [
  { key: 'permohonan', label: 'Permohonan', icon: '📋', variant: 'primary-gradient' },
  { key: 'pasang_baru', label: 'Pasang Baru', icon: '🔵', variant: 'info-gradient' },
  { key: 'aktif', label: 'Aktif', icon: '🟢', variant: 'success-gradient' },
  { key: 'blokir', label: 'Blokir', icon: '🟠', variant: 'warning-gradient' },
  { key: 'cabut', label: 'Cabut', icon: '🔴', variant: 'danger-gradient' },
]
