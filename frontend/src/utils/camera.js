/**
 * Camera Utility - Helper untuk menangani upload foto dan kompresi
 */
export const cameraUtils = {
  /**
   * Kompres gambar sebelum diupload
   * @param {File} file - File gambar asli
   * @param {Object} options - { maxWidth, quality }
   * @returns {Promise<Blob>}
   */
  async compressImage(file, { maxWidth = 1024, quality = 0.7 } = {}) {
    return new Promise((resolve, reject) => {
      const reader = new FileReader()
      reader.readAsDataURL(file)
      reader.onload = (event) => {
        const img = new Image()
        img.src = event.target.result
        img.onload = () => {
          const canvas = document.createElement('canvas')
          let width = img.width
          let height = img.height

          // Hitung rasio aspek
          if (width > maxWidth) {
            height *= maxWidth / width
            width = maxWidth
          }

          canvas.width = width
          canvas.height = height

          const ctx = canvas.getContext('2d')
          ctx.drawImage(img, 0, 0, width, height)

          canvas.toBlob(
            (blob) => {
              resolve(blob)
            },
            'image/jpeg',
            quality,
          )
        }
        img.onerror = reject
      }
      reader.onerror = reject
    })
  },
}

export default cameraUtils
