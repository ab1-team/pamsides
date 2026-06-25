import { ref } from 'vue'
import { jsPDF } from 'jspdf'
import html2canvas from 'html2canvas'

const A4 = {
  width: 210,
  height: 297,
}

const MARGIN = 15

const bulanNama = (val) => {
  const map = {
    '01': 'Januari',
    '02': 'Februari',
    '03': 'Maret',
    '04': 'April',
    '05': 'Mei',
    '06': 'Juni',
    '07': 'Juli',
    '08': 'Agustus',
    '09': 'September',
    10: 'Oktober',
    11: 'November',
    12: 'Desember',
  }
  return map[String(val).padStart(2, '0')] ?? ''
}

const formatCurrency = (val) => {
  const n = Number(val) || 0
  return 'Rp ' + n.toLocaleString('id-ID')
}

const formatTanggal = (val) => {
  if (!val) return '-'
  const d = new Date(val)
  if (Number.isNaN(d.getTime())) return val
  return d.toLocaleDateString('id-ID', {
    day: '2-digit',
    month: 'long',
    year: 'numeric',
  })
}

export function usePdfReport() {
  const isGenerating = ref(false)
  const lastError = ref(null)

  const buildPdfFromPages = async (pages, { filename = 'laporan.pdf' } = {}) => {
    if (!Array.isArray(pages) || pages.length === 0) {
      throw new Error('Tidak ada halaman yang akan dicetak')
    }

    isGenerating.value = true
    lastError.value = null
    try {
      const pdf = new jsPDF({
        orientation: 'portrait',
        unit: 'mm',
        format: 'a4',
      })

      const h2c = html2canvas
      const pageW = A4.width
      const pageH = A4.height
      const usableW = pageW - MARGIN * 2

      for (let i = 0; i < pages.length; i++) {
        const el = pages[i]
        if (!el) continue

        const canvas = await h2c(el, {
          scale: 2,
          useCORS: true,
          backgroundColor: '#ffffff',
          windowWidth: el.scrollWidth,
          windowHeight: el.scrollHeight,
        })

        const imgData = canvas.toDataURL('image/jpeg', 0.95)
        const ratio = canvas.height / canvas.width
        const imgW = usableW
        const imgH = imgW * ratio

        if (i > 0) pdf.addPage()
        let yPos = MARGIN
        if (imgH < pageH - MARGIN * 2) {
          yPos = MARGIN
        }
        pdf.addImage(imgData, 'JPEG', MARGIN, yPos, imgW, imgH, undefined, 'FAST')
      }

      const blob = pdf.output('blob')
      const url = URL.createObjectURL(blob)
      return { url, blob, filename }
    } catch (err) {
      lastError.value = err
      throw err
    } finally {
      isGenerating.value = false
    }
  }

  const openPdfInNewTab = async (pages, options = {}) => {
    const { url, filename } = await buildPdfFromPages(pages, options)
    const win = window.open(url, '_blank')
    if (win) {
      win.document.title = filename
    }
    setTimeout(() => URL.revokeObjectURL(url), 60_000)
    return { url }
  }

  const downloadPdf = async (pages, options = {}) => {
    const { blob, filename } = await buildPdfFromPages(pages, options)
    const link = document.createElement('a')
    link.href = URL.createObjectURL(blob)
    link.download = filename
    document.body.appendChild(link)
    link.click()
    link.remove()
    setTimeout(() => URL.revokeObjectURL(link.href), 60_000)
  }

  return {
    isGenerating,
    lastError,
    buildPdfFromPages,
    openPdfInNewTab,
    downloadPdf,
    bulanNama,
    formatCurrency,
    formatTanggal,
    A4,
    MARGIN,
  }
}

export default usePdfReport
