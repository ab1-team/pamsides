import { ref } from 'vue'

export function useA4Paginator() {
  const isMeasuring = ref(false)
  const lastMeasurement = ref(null)

  const measure = async (_target, _options = {}) => {
    isMeasuring.value = true
    try {
      const result = {
        pageWidth: 210,
        pageHeight: 297,
        pageCount: 1,
        breakPoints: [],
      }
      lastMeasurement.value = result
      return result
    } finally {
      isMeasuring.value = false
    }
  }

  return {
    isMeasuring,
    lastMeasurement,
    measure,
  }
}

export default useA4Paginator