<template>
  <div class="dashboard-body">
    <div class="stat-cards-row">
      <statCard label="Instalasi" value="100">
        <font-awesome-icon icon="home" style="color: #2563eb" />
      </statCard>
      <statCard label="Pemakaian" value="100">
        <font-awesome-icon icon="tint" style="color: #0891b2" />
      </statCard>
      <statCard label="Tunggakan" value="100">
        <font-awesome-icon icon="balance-scale" style="color: #ea580c" />
      </statCard>
      <statCard label="Tagihan" value="100">
        <font-awesome-icon icon="file-invoice" style="color: #059669" />
      </statCard>
    </div>

    <div class="financial-section">
      <div class="financial-cards">
        <div class="financial-card">
          <div class="financial-card-header">
            <span class="financial-card-label">PENDAPATAN</span>
            <span class="stat-card-badge badge-danger">
              <font-awesome-icon icon="arrow-down" width="10" height="10" />
              NEGATIVE
            </span>
          </div>
          <div class="financial-card-value">
            <span class="currency-prefix">Rp.</span>
            {{ formattedPendapatan.replace('Rp', '').trim() }}
          </div>
        </div>
        <div class="financial-card beban">
          <div class="financial-card-header">
            <span class="financial-card-label">BEBAN</span>
            <span class="stat-card-badge badge-neutral">← 0% TREND</span>
          </div>
          <div class="financial-card-value">
            <span class="currency-prefix">Rp.</span>
            {{ formattedBeban.replace('Rp', '').trim() }}
          </div>
        </div>
        <div class="financial-card surplus">
          <div class="financial-card-header">
            <span class="financial-card-label">SURPLUS</span>
            <span class="stat-card-badge badge-danger">
              <font-awesome-icon icon="arrow-down" width="10" height="10" />
              NEGATIVE
            </span>
          </div>
          <div class="financial-card-value">
            <span class="currency-prefix">Rp.</span>
            {{ formattedSurplus.replace('Rp', '').trim() }}
          </div>
        </div>
      </div>

      <div class="chart-card">
        <div class="chart-card-header">
          <div>
            <div class="chart-card-title">Pendapatan dan Beban</div>
            <div class="chart-card-sub">Visualisasi finansial kuartal pertama</div>
          </div>
          <div class="chart-legend">
            <div class="chart-legend-item">
              <div class="chart-legend-dot" style="background: #0ea5e9"></div>
              Pendapatan
            </div>
            <div class="chart-legend-item">
              <div class="chart-legend-dot" style="background: #1e3a5f"></div>
              Beban
            </div>
            <div class="chart-legend-item">
              <div class="chart-legend-dot" style="background: #f59e0b"></div>
              Surplus
            </div>
          </div>
        </div>
        <div class="chart-wrapper">
          <svg
            viewBox="0 0 700 220"
            xmlns="http://www.w3.org/2000/svg"
            style="width: 100%; height: 100%"
          >
            <defs>
              <linearGradient id="gradP" x1="0" y1="0" x2="0" y2="1">
                <stop offset="0%" stop-color="#0ea5e9" stop-opacity="0.18" />
                <stop offset="100%" stop-color="#0ea5e9" stop-opacity="0" />
              </linearGradient>
              <linearGradient id="gradB" x1="0" y1="0" x2="0" y2="1">
                <stop offset="0%" stop-color="#1e3a5f" stop-opacity="0.15" />
                <stop offset="100%" stop-color="#1e3a5f" stop-opacity="0" />
              </linearGradient>
              <linearGradient id="gradS" x1="0" y1="0" x2="0" y2="1">
                <stop offset="0%" stop-color="#f59e0b" stop-opacity="0.12" />
                <stop offset="100%" stop-color="#f59e0b" stop-opacity="0" />
              </linearGradient>
            </defs>
            <line x1="40" y1="20" x2="40" y2="185" stroke="#e2e8f0" stroke-width="1" />
            <line x1="40" y1="185" x2="680" y2="185" stroke="#e2e8f0" stroke-width="1" />
            <line
              x1="40"
              y1="140"
              x2="680"
              y2="140"
              stroke="#e2e8f0"
              stroke-width="0.75"
              stroke-dasharray="4 4"
            />
            <line
              x1="40"
              y1="100"
              x2="680"
              y2="100"
              stroke="#e2e8f0"
              stroke-width="0.75"
              stroke-dasharray="4 4"
            />
            <line
              x1="40"
              y1="60"
              x2="680"
              y2="60"
              stroke="#e2e8f0"
              stroke-width="0.75"
              stroke-dasharray="4 4"
            />
            <text x="50" y="200" fill="#94a3b8" font-size="12" font-family="DM Sans,sans-serif">
              Awal Tahun
            </text>
            <text x="230" y="200" fill="#94a3b8" font-size="12" font-family="DM Sans,sans-serif">
              Januari
            </text>
            <text x="410" y="200" fill="#94a3b8" font-size="12" font-family="DM Sans,sans-serif">
              Februari
            </text>
            <text x="630" y="200" fill="#94a3b8" font-size="12" font-family="DM Sans,sans-serif">
              Maret
            </text>
            <path
              d="M50,170 C100,165 150,145 230,120 C310,95 390,75 470,65 C540,56 610,52 680,50 L680,185 L50,185 Z"
              fill="url(#gradP)"
            />
            <path
              d="M50,175 C100,172 150,162 230,148 C310,132 390,118 470,110 C540,103 610,99 680,97 L680,185 L50,185 Z"
              fill="url(#gradB)"
            />
            <path
              d="M50,178 C100,175 150,167 230,155 C310,142 390,130 470,122 C540,116 610,112 680,110 L680,185 L50,185 Z"
              fill="url(#gradS)"
            />
            <path
              d="M50,170 C100,165 150,145 230,120 C310,95 390,75 470,65 C540,56 610,52 680,50"
              fill="none"
              stroke="#0ea5e9"
              stroke-width="2.5"
              stroke-linecap="round"
            />
            <path
              d="M50,175 C100,172 150,162 230,148 C310,132 390,118 470,110 C540,103 610,99 680,97"
              fill="none"
              stroke="#1e3a5f"
              stroke-width="2.5"
              stroke-linecap="round"
            />
            <path
              d="M50,178 C100,175 150,167 230,155 C310,142 390,130 470,122 C540,116 610,112 680,110"
              fill="none"
              stroke="#f59e0b"
              stroke-width="2"
              stroke-linecap="round"
            />
            <circle cx="230" cy="120" r="5" fill="white" stroke="#0ea5e9" stroke-width="2.5" />
            <circle cx="470" cy="65" r="5" fill="white" stroke="#0ea5e9" stroke-width="2.5" />
            <circle cx="230" cy="148" r="5" fill="white" stroke="#1e3a5f" stroke-width="2.5" />
            <circle cx="470" cy="110" r="5" fill="white" stroke="#1e3a5f" stroke-width="2.5" />
          </svg>
        </div>
        <div class="chart-footer">
          <div class="chart-footer-info">
            <font-awesome-icon
              icon="info-circle"
              width="14"
              height="14"
              style="color: var(--color-primary); flex-shrink: 0"
            />
            Data diperbarui setiap 15 menit secara otomatis.
          </div>
          <button class="chart-download-btn">
            Unduh Laporan
            <font-awesome-icon icon="download" width="14" height="14" />
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import statCard from '../../components/stat-card.vue'

const financialData = ref({
  pendapatan: 2300000,
  beban: 0,
  surplus: 2300000,
})

const formattedPendapatan = ref('')
const formattedBeban = ref('')
const formattedSurplus = ref('')

const formatCurrency = (amount) => {
  return new Intl.NumberFormat('id-ID', {
    style: 'currency',
    currency: 'IDR',
    minimumFractionDigits: 2,
  }).format(amount)
}

const loadData = async () => {
  await new Promise((resolve) => setTimeout(resolve, 1000))
  formattedPendapatan.value = formatCurrency(financialData.value.pendapatan)
  formattedBeban.value = formatCurrency(financialData.value.beban)
  formattedSurplus.value = formatCurrency(financialData.value.surplus)
}

onMounted(() => {
  loadData()
})
</script>
