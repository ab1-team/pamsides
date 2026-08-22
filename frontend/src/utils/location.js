/**
 * Location Utility - Helper untuk mengambil koordinat GPS
 */
export const locationUtils = {
  /**
   * Mendapatkan lokasi saat ini
   * @returns {Promise} Resolves dengan { lat, lng }
   */
  getCurrentLocation() {
    return new Promise((resolve, reject) => {
      if (!navigator.geolocation) {
        reject(new Error('Geolocation tidak didukung oleh browser ini.'))
        return
      }

      navigator.geolocation.getCurrentPosition(
        (position) => {
          resolve({
            lat: position.coords.latitude,
            lng: position.coords.longitude,
            accuracy: position.coords.accuracy,
          })
        },
        (error) => {
          let message = 'Gagal mengambil lokasi.'
          switch (error.code) {
            case error.PERMISSION_DENIED:
              message = 'Izin lokasi ditolak oleh pengguna.'
              break
            case error.POSITION_UNAVAILABLE:
              message = 'Informasi lokasi tidak tersedia.'
              break
            case error.TIMEOUT:
              message = 'Waktu pengambilan lokasi habis.'
              break
          }
          reject(new Error(message))
        },
        {
          enableHighAccuracy: true,
          timeout: 5000,
          maximumAge: 0,
        },
      )
    })
  },
}

export default locationUtils
