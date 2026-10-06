import { ref } from 'vue'
import { useRouter } from 'vue-router'
import dashboardService from '@/services/dashboard.service'
import { MySwal } from '@/utils/swal'
import { useUiStore } from '@/stores/uiStore'

/**
 * Composable untuk menampilkan pop up Generate Piutang Tunggakan.
 *
 * PEMICUNYA HANYA SATU: `LoginView.vue`, tepat setelah `router.push()`.
 *
 * Dulu ada pemicu kedua di `DashboardHome.vue` (`onMounted`). Itu dihapus
 * karena `onMounted` berjalan SETIAP KALI komponen di-mount, sehingga
 * memetik menu Dashboard — atau kembali ke dashboard dari halaman lain —
 * ikut menjalankan generate. Yang diminta adalah generate jalan setiap kali
 * login BERHASIL, bukan setiap kali dashboard dibuka.
 *
 * Efek samping yang sama berlaku untuk me-refresh halaman: dashboard yang
 * di-refresh tidak lagi memicu apa pun, karena tidak ada login yang terjadi.
 *
 * Yang tersisa: lock `inFlight` di level modul, sebagai jaring pengaman
 * kalau `LoginView` somehow terpanggil dua kali berdekatan.
 *
 * Aturan pemicu (sesuai SOP Personalisasi / Toleransi Menunggak):
 *   - Field `toleransiTunggakan` di settings SOP adalah TANGGAL (1-28).
 *   - Jika hari ini == tanggal tersebut, backend menjalankan ulang command
 *     `billing:generate-overdue-transactions` (membuat jurnal piutang,
 *     abodemen, dan denda untuk tagihan menunggak).
 *   - Selama proses berjalan, modal loading tampil tanpa tombol apa pun.
 *   - Setelah selesai, hasil ditampilkan via SweetAlert2 lengkap dengan
 *     tombol "Tutup" / "Lihat Arsip Tunggakan".
 *   - Tidak ada lagi gate "sudah pernah jalan hari ini" di frontend maupun
 *     backend: setiap login pada tanggal tersebut menghitung ulang.
 *     Duplikasi dicegah di level transaksi oleh command backend.
 *
 * Cara pakai di komponen:
 *   const { checkOverdueGenOnMount } = useOverdueGenNotification()
 *   // panggil dari handleLogin(), BUKAN dari onMounted dashboard
 *
 * Nama fungsi masih `...OnMounted` walau dipanggil di dalam `handleLogin()`
 * — mengubah nama hanya menambah diff tanpa manfaat.
 */

// ── State bersama antar instance composable ──────────────────
const isChecking = ref(false)
const lastResult = ref(null)

// Lock proses: mencegah dua request generate berjalan bersamaan.
let inFlight = null

// Key `sessionStorage` penanda popup sudah tampil sudah DIHAPUS. Dulu nilainya
// membuat generate hanya dipicu sekali per hari — bertentangan dengan
// permintaan bahwa setiap login di tanggal generate harus menghitung ulang.
// Keamanannya sekarang ada di dedup transaksi milik command backend, bukan di
// flag browser.

export function useOverdueGenNotification() {
  const router = useRouter()
  const uiStore = useUiStore()

  /**
   * Tampilkan modal loading selama proses generate berjalan di backend.
   *
   * Generate jurnal bisa memakan waktu beberapa detik (puluhan ribu
   * tagihan + trigger `amount` per insert), jadi user perlu diberi
   * penanda bahwa proses masih berjalan — bukan dianggap diam saja.
   *
   * PENTING: modal ini TIDAK punya tombol apa pun (bukan confirm, bukan
   * cancel, bukan tombol close "X"). Semua opsi non-interaktif dipaksa
   * false supaya satu-satunya jalan keluar adalah proses yang selesai.
   * Setelah selesai, `showOverdueGenResult()` yang menampilkan notifikasi
   * berhasil lengkap dengan tombolnya ("Tutup" / "Lihat Arsip Tunggakan").
   */
  const showGeneratingLoader = () => {
    const startedAt = Date.now()
    const elapsedEl = document.createElement('span')

    const tick = () => {
      const secs = Math.floor((Date.now() - startedAt) / 1000)
      elapsedEl.textContent = secs > 0 ? ` (${secs} detik)` : ''
    }
    const timer = setInterval(tick, 1000)

    return MySwal.fire({
      title: 'Sedang Generate Piutang...',
      html: `
        <div class="text-left! text-sm! leading-relaxed!">
          <p class="mb-4! text-slate-600!">
            Sistem sedang membuat jurnal piutang untuk seluruh tagihan yang
            melewati batas toleransi menunggak.
          </p>
          <div class="flex! items-center! justify-center! gap-3! py-3!">
            <div class="w-6! h-6! rounded-full! border-[3px]! border-sky-500! border-t-transparent! animate-spin!"></div>
            <span class="text-xs! font-medium! text-sky-700!">
              Memproses tagihan menunggak<span data-elapsed></span> ...
            </span>
          </div>
          <p class="mt-2! text-[11px]! italic! text-slate-400! text-center!">
            Mohon tunggu, proses sedang berjalan.
          </p>
        </div>
      `,
      width: 480,
      // ── Tanpa tombol & tanpa jalan keluar manual ──
      showConfirmButton: false,
      showCancelButton: false,
      showDenyButton: false,
      showCloseButton: false,
      allowOutsideClick: false,
      allowEscapeKey: false,
      backdrop: 'rgba(15, 23, 42, 0.45)',
      customClass: {
        popup: 'rounded-2xl!',
        title: 'text-lg! font-bold!',
      },
      didOpen: (popup) => {
        popup.querySelector('[data-elapsed]')?.appendChild(elapsedEl)

        // Proteksi lapis kedua:SweetAlert2 sudah menyembunyikan tombol
        // lewat `display: none`, tapi beberapa tema CSS di proyek ini
        // pernah memakai `!important` yang bisa membatalkannya. Pastikan
        // baris Actions benar-benar hilang agar tidak ada "OK"/"Batal".
        const actions = popup.querySelector('.swal2-actions')
        if (actions) {
          actions.style.setProperty('display', 'none', 'important')
        }
        popup.querySelectorAll('.swal2-confirm, .swal2-cancel, .swal2-deny').forEach((btn) => {
          btn.style.setProperty('display', 'none', 'important')
        })
      },
      willClose: () => {
        clearInterval(timer)
      },
    })
  }

  /**
   * Render pop up hasil generate piutang tunggakan.
   *
   * Dipanggil saat backend mengembalikan `ran=true` (proses baru dijalankan)
   * ATAU `already_ran=true` (sudah dijalankan admin lain hari ini — ringkasan
   * yang sama tetap ditampilkan supaya semua admin tahu hasilnya).
   */
  const showOverdueGenResult = (data) => {
    const summary = data?.summary || {}
    const abodemenCount = Number(summary.tagihan_dengan_abodemen_tungakan) || 0
    const pemakaianCount = Number(summary.tagihan_dengan_pemakaian_tungakan) || 0
    const totalOverdue = Number(summary.total_overdue) || 0
    const totalUnpaid = Number(summary.total_unpaid) || 0
    const alreadyRan = data?.already_ran === true

    // Angka ini dikirim backend langsung dari loop pemrosesan
    // (`processed`), jadi tidak pernah nol palsu seperti hasil query
    // jurnal "bertanggal hari ini" yang lama. Bila field ini belum ada
    // (server lama / cache lama), jatuh ke estimasi dari dua komponen.
    const diproses =
      summary.tagihan_diproses !== undefined
        ? Number(summary.tagihan_diproses) || 0
        : Math.max(abodemenCount, pemakaianCount)
    const dilewati = Number(summary.tagihan_dilewati) || 0
    const tagihanDiproses = diproses
    const isSuccess = data?.exit_code === 0

    const html = `
        <div class="text-left! text-sm! leading-relaxed!">
          <p class="mb-3! text-slate-600!">
                Sesuai pengaturan <strong class="text-slate-800!">Toleransi Menunggak</strong>
                (tanggal <strong>${data?.scheduled_day ?? '-'}</strong>), sistem
                ${alreadyRan ? 'sudah' : 'telah'} menjalankan generate piutang untuk
                tagihan menunggak hari ini (<strong>${data?.date ?? '-'}</strong>).
            </p>

            <div class="overdue-stat-grid">
              <div class="p-3! rounded-xl! border! border-sky-100! bg-sky-50/60!">
                <div class="text-[10px]! font-bold! text-sky-700! tracking-wider! uppercase!">Tagihan Diproses</div>
                <div class="text-xl! font-extrabold! text-sky-700! mt-0.5!">${tagihanDiproses}</div>
                <div class="text-[10px]! text-sky-600! mt-0.5!">berhasil dibuatkan jurnal</div>
              </div>
              <div class="p-3! rounded-xl! border! border-amber-100! bg-amber-50/60!">
                <div class="text-[10px]! font-bold! text-amber-700! tracking-wider! uppercase!">Total Tunggakan</div>
                <div class="text-xl! font-extrabold! text-amber-700! mt-0.5!">${totalOverdue}</div>
                <div class="text-[10px]! text-amber-600! mt-0.5!">dari ${totalUnpaid} unpaid</div>
              </div>
            </div>

            <div class="p-3! rounded-xl! border! border-slate-100! bg-slate-50/60! text-xs! text-slate-600! space-y-1!">
              <div class="flex! items-center! justify-between! overdue-legend-row">
                <span><span class="font-mono! font-bold! text-rose-600!">1.1.03.01</span> → <span class="font-mono! font-bold! text-emerald-600!">4.1.01.02</span> (Abodemen)</span>
                <span class="font-bold!">${abodemenCount}</span>
              </div>
              <div class="flex! items-center! justify-between! overdue-legend-row">
                <span><span class="font-mono! font-bold! text-rose-600!">1.1.03.01</span> → <span class="font-mono! font-bold! text-emerald-600!">4.1.01.03</span> (Pemakaian)</span>
                <span class="font-bold!">${pemakaianCount}</span>
              </div>
              ${
                dilewati > 0
                  ? `<div class="flex! items-center! justify-between! overdue-legend-row">
                <span class="text-slate-500!">Dilewati (sudah ada jurnal)</span>
                <span class="font-bold!">${dilewati}</span>
              </div>`
                  : ''
              }
              <div class="flex! items-center! justify-between! overdue-legend-row pt-1! border-t! border-slate-200/60! mt-1!">
                <span class="text-slate-500!">Durasi proses</span>
                <span class="font-bold!">${data?.duration_ms ?? 0} ms</span>
              </div>
            </div>

            ${
              tagihanDiproses === 0
                ? `<p class="mt-3! text-xs! italic! text-emerald-700!">
                    Perhitungan sudah dijalankan hari ini. Tidak ada tagihan
                    menunggak baru yang perlu dibuatkan jurnal — semua sudah
                    memiliki jurnal piutang, atau belum ada tagihan yang
                    melewati batas toleransi${
                      dilewati > 0 ? ` (${dilewati} tagihan dilewati karena sudah ada jurnal)` : ''
                    }.
                  </p>`
                : `<p class="mt-3! text-xs! italic! text-slate-500!">
                    Jurnal piutang telah otomatis masuk ke transaksi <strong>abodemen</strong>
                    dan <strong>denda</strong>.
                  </p>`
            }
        </div>
      `

    return MySwal.fire({
      // Judul tetap sama supaya user konsisten mengenali popup ini,
      // tapi ikon menyesuaikan: nol hasil bukan kegagalan.
      icon: isSuccess ? (tagihanDiproses === 0 ? 'info' : 'success') : 'warning',
      title: 'Generate Piutang Tunggakan',
      html,
      width: 560,
      confirmButtonText: 'Tutup',
      confirmButtonColor: '#0EA5E9',
      // Hanya admin yang boleh membuka halaman /app/arsip/tunggakan
      // (lihat router/index.js role-specific routes). Tombol ini
      // ditampilkan hanya untuk admin supaya teknisi tidak dialihkan
      // ke halaman terlarang.
      //
      // Tombol ini tetap muncul walau `tagihanDiproses === 0`: arsip
      // tunggakan tetap relevan untuk dilihat walau tidak ada jurnal
      // baru hari ini.
      showCancelButton: uiStore.userRole === 'admin',
      cancelButtonText: 'Lihat Arsip Tunggakan',
      cancelButtonColor: '#64748b',
      customClass: {
        popup: 'rounded-2xl!',
        confirmButton: 'rounded-xl! px-4! py-2! font-bold!',
        cancelButton: 'rounded-xl! px-4! py-2! font-bold!',
        title: 'text-lg!',
      },
    }).then((result) => {
      // Tidak ada lagi penanda "sudah tampil" di `sessionStorage`. Dulu ada,
      // dan justru itu yang membuat popup hanya muncul sekali per hari —
      // padahal backend sekarang sengaja menjalankan ulang di setiap login
      // dan mengandalkan dedup transaksinya sendiri.

      // Jika user pilih "Lihat Arsip Tunggakan", arahkan ke halaman arsip.
      if (result.isDismissed && result.dismiss === MySwal.DismissReason.cancel) {
        router.push('/app/arsip/tunggakan')
      }
      return result
    })
  }

  /**
   * Notifikasi bahwa generate piutang GAGAL.
   *
   * Popup loading yang ditutup tanpa penjelasan membuat user mengira aplikasi
   * hang. Setelah menunggu proses generate, yang paling membantu adalah
   * memberitahu apa yang terjadi dan apa yang bisa dilakukan.
   *
   * `detail` dipakai untuk pesan teknis dari backend (bila ada).
   */
  const showOverdueGenError = (pesan, detail) => {
    return MySwal.fire({
      icon: 'error',
      title: 'Generate Piutang Gagal',
      html: `
        <div class="text-left! text-sm! leading-relaxed!">
          <p class="mb-2! text-slate-600!">${escapeHtml(pesan)}</p>
          ${
            detail
              ? `<p class="p-2.5! rounded-lg! bg-slate-50! border! border-slate-100! text-[11px]! text-slate-500! font-mono! break-words!">${escapeHtml(detail)}</p>`
              : ''
          }
          <p class="mt-3! text-xs! italic! text-slate-400!">
            Tidak ada jurnal piutang yang berubah. Anda dapat mencoba lagi
            kapan saja, atau hubungi administrator.
          </p>
        </div>
      `,
      width: 480,
      confirmButtonText: 'Mengerti',
      confirmButtonColor: '#ef4444',
      customClass: {
        popup: 'rounded-2xl!',
        confirmButton: 'rounded-xl! px-4! py-2! font-bold!',
        title: 'text-lg!',
      },
    })
  }

  /**
   * Cegah HTML dari string server ditampilkan sebagai markup.
   * Pesan `reason`/`message` berasal dari backend, jadi tidak boleh
   * disisipkan mentah ke `html:` SweetAlert2.
   */
  const escapeHtml = (value) => {
    return String(value ?? '')
      .replace(/&/g, '&amp;')
      .replace(/</g, '&lt;')
      .replace(/>/g, '&gt;')
      .replace(/"/g, '&quot;')
      .replace(/'/g, '&#039;')
  }

  /**
   * Picu generate piutang tunggakan untuk hari ini.
   *
   * HANYA dipanggil dari `LoginView.vue`, di dalam `handleLogin()` setelah
   * `router.push()`. Jangan pernah dipanggil dari `onMounted` dashboard:
   * `onMounted` berjalan tiap kali komponen di-mount, sehingga membuka
   * dashboard akan memicu generate berulang. Yang diminta adalah generate
   * per login, bukan per pembukaan halaman.
   *
   * Alur (penting — urutannya menentukan apakah user melihat proses):
   *   1. Kalau role BUKAN admin → berhenti. Tidak ada request sama sekali,
   *      teknisi tidak melihat apa-apa.
   *   2. Kalau ada proses di tab ini yang masih jalan → tunggu yang sama,
   *      jangan picu dua kali.
   *   3. GET  /auto-generate-overdue/check   → apakah hari ini tanggal generate?
   *   4. Kalau BUKAN → berhenti diam-diam, tanpa popup sama sekali.
   *   5. Kalau YA    → buka popup loading (spinner + timer) SEKARANG.
   *   6. POST /auto-generate-overdue        → proses sungguhan berjalan.
   *   7. Ganti popup loading dengan popup hasil (punya tombol) instan.
   *
   * Popup dibuka pada langkah 5, BUKAN setelah langkah 6. Ini yang membuat
   * user melihat progres nyata — generate jurnal butuh beberapa detik
   * untuk puluhan ribu tagihan, dan tanpa popup selama itu user hanya
   * melihat dashboard diam lalu tiba-tiba popup hasil muncul.
   *
   * POPUP MUNCUL DI SETIAP LOGIN pada tanggal generate, dan backend setiap
   * kali menjalankan ulang perhitungannya. Ini disengaja: tagihan baru bisa
   * saja masuk setelah login pertama, jadi hasil di login berikutnya bisa
   * berbeda. Tidak ada data ganda karena command melewati tagihan yang sudah
   * punya jurnal `overdue_bill`.
   */
  const checkOverdueGenOnMount = async () => {
    // Hanya ADMIN yang menjalankan generate piutang (lihat
    // `DashboardController::autoGenerateOverdue`, yang membalas 403 untuk
    // selain admin).
    //
    // Penjagaan ini HARUS jadi pemeriksaan pertama: endpoint `check`
    // sengaja mengizinkan teknisi demi statistik dashboard, jadi kalau
    // teknisi tidak dihentikan di sini ia akan melihat popup loading
    // terbuka lalu hilang tanpa penjelasan karena generate ditolak 403.
    //
    // Dihentikan SEBELUM request apa pun dikirim, jadi teknisi benar-benar
    // melihat tidak ada popup dan tidak membuang satu request sia-sia.
    if (uiStore.userRole !== 'admin') return

    // Kalau proses di tab ini masih jalan, jangan jalankan dua kali. Ini
    // HANYA mencegah double-trigger di tab yang sama (mis. `router.push`
    // dari login DAN `onMounted` dashboard pada saat yang sama).
    if (inFlight) return inFlight

    // SENGAJA TIDAK ada penanda "sudah tampil hari ini" di sini.
    //
    // Sebelumnya ada `sessionStorage[SEEN_KEY]`, dan itu justru membuat
    // popup hanya muncul sekali per hari. Setelah penanda itu terisi, semua
    // login berikutnya di tanggal yang sama dilewati tanpa mengirim request
    // sama sekali — popup tidak muncul dan tidak ada jejak di Network tab,
    // sehingga terlihat seperti hang.
    //
    // Sekarang setiap login pada tanggal generate memanggil backend lagi.
    // Yang mencegah data ganda bukan penanda ini, melainkan dedup di dalam
    // command: tagihan yang sudah punya jurnal `overdue_bill` dilewati.

    isChecking.value = true

    // True selama popup loading aktif. Dipakai supaya `closeLoader()` tidak
    // menutup popup yang sudah digantikan oleh popup hasil.
    let loaderOpened = false

    const closeLoader = async () => {
      if (!loaderOpened) return
      loaderOpened = false
      await MySwal.close()
    }

    inFlight = (async () => {
      // ── Langkah 1: pra-cek apakah hari ini memang ada generate ──
      // Tanpa langkah ini, popup loading akan berkedip di hari biasa:
      // endpoint generate membalas instan `ran=false` saat bukan tanggal
      // generate, sehingga modal muncul-hilang dalam ~100ms.
      const shouldShowLoader = await (async () => {
        try {
          const check = await dashboardService.checkAutoGenerateOverdue()
          return check?.success === true && check?.will_run === true
        } catch {
          // Kalau pra-cek gagal (offline/jaringan), tetap coba jalankan
          // generate. Popup loading tetap dibuka supaya tidak diam-diam.
          return true
        }
      })()

      if (!shouldShowLoader) {
        isChecking.value = false
        return
      }

      // ── Langkah 2: buka popup loading SEBELUM proses jalan ──
      loaderOpened = true
      showGeneratingLoader()

      try {
        // ── Langkah 3: proses generate sungguhan ──
        const res = await dashboardService.autoGenerateOverdue()
        lastResult.value = res

        // Tampilkan popup kalau proses di backend sudah selesai:
        //   - `ran === true`         → command baru dijalankan untuk user ini.
        //   - `busy === true`        → sedang jalan di tab lain (lock backend).
        //                              Ringkasannya tetap ditampilkan.
        //   - `already_ran === true` → field lama; tidak dipakai lagi karena
        //                              backend sekarang selalu menjalankan ulang.
        //
        // Intinya: di setiap login pada tanggal generate, backend SELALU
        // menjalankan ulang command. Backend yang menentukan mana yang perlu
        // dibuat (tagihan yang sudah punya jurnal dilewati), jadi popup ini
        // selalu menampilkan ringkasan terbaru tanpa risiko data ganda.
        if (res?.success && (res?.ran === true || res?.busy === true || res?.already_ran === true)) {
          // SweetAlert2 menutup instance sebelumnya otomatis saat `fire()`
          // baru dipanggil, jadi popup hasil langsung menggantikan popup
          // loading tanpa jeda.
          loaderOpened = false

          // Popup hasil langsung tampil — tanpa jeda, tanpa tombol
          // "loading" yang berdiri sendiri lebih dulu.
          showOverdueGenResult(res)
        } else {
          await closeLoader()

          // Backend menolak dengan `ran=false` beserta `reason` yang jelas
          // (mis. bukan tanggal generate). Kalau `reason` ada, tampilkan —
          // jangan hilangkan modal begitu saja setelah user sempat menunggu.
          if (res?.reason) {
            showOverdueGenError(res.reason, res.message)
          }
        }
      } catch (err) {
        await closeLoader()

        // Generate gagal (timeout / server error / token habis). User TIDAK
        // boleh dibiarkan popup loading-nya hilang begitu saja tanpa
        // penjelasan — itu terlihat seperti aplikasi hang.
        const pesan =
          err?.response?.data?.message ||
          err?.message ||
          'Terjadi kesalahan saat generate piutang.'

        showOverdueGenError(pesan, null)
        console.warn('[overdue-gen] auto generate gagal:', err?.message || err)
      } finally {
        isChecking.value = false
      }
    })()

    try {
      await inFlight
    } finally {
      inFlight = null
    }
  }

  return {
    isChecking,
    lastResult,
    checkOverdueGenOnMount,
    showGeneratingLoader,
    showOverdueGenResult,
  }
}

export default useOverdueGenNotification
