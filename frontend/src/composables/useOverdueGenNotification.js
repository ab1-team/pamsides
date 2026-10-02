import { ref } from 'vue'
import { useRouter } from 'vue-router'
import dashboardService from '@/services/dashboard.service'
import { MySwal } from '@/utils/swal'
import { useUiStore } from '@/stores/uiStore'

/**
 * Composable untuk menampilkan pop up Generate Piutang Tunggakan di dashboard.
 *
 * Aturan pemicu (sesuai SOP Personalisasi / Toleransi Menunggak):
 *   - Field `toleransiTunggakan` di settings SOP adalah TANGGAL (1-28).
 *   - Jika hari ini == tanggal tersebut, backend otomatis menjalankan
 *     command `billing:generate-overdue-transactions` (yang membuat
 *     jurnal piutang/abodemen/denda untuk tagihan menunggak).
 *   - Hasil dikembalikan ke frontend dan ditampilkan via SweetAlert2.
 *   - Backend menjaga idempotensi: tiap (bulan, user) hanya 1x generate.
 *
 * Cara pakai di komponen dashboard:
 *   const { checkOverdueGenOnMount } = useOverdueGenNotification()
 *   onMounted(() => checkOverdueGenOnMount())
 */
export function useOverdueGenNotification() {
  const router = useRouter()
  const uiStore = useUiStore()
  const isChecking = ref(false)
  const lastResult = ref(null)

  /**
   * Render pop up hasil generate piutang tunjak.
   * Dipanggil saat backend mengembalikan `ran=true`.
   */
  const showOverdueGenResult = (data) => {
    const summary = data?.summary || {}
    const abodemenCount = Number(summary.tagihan_dengan_abodemen_tungakan) || 0
    const pemakaianCount = Number(summary.tagihan_dengan_pemakaian_tungakan) || 0
    const totalOverdue = Number(summary.total_overdue) || 0
    const totalUnpaid = Number(summary.total_unpaid) || 0

    const tagihanDiproses = Math.max(abodemenCount, pemakaianCount)
    const isSuccess = data?.exit_code === 0

    const html = `
        <div class="text-left! text-sm! leading-relaxed!">
          <p class="mb-3! text-slate-600!">
                Sesuai pengaturan <strong class="text-slate-800!">Toleransi Menunggak</strong>
                (tanggal <strong>${data?.scheduled_day ?? '-'}</strong>), sistem telah
                menjalankan generate piutang untuk tagihan menunggak hari ini
                (<strong>${data?.date ?? '-'}</strong>).
            </p>

            <div class="grid grid-cols-2! gap-2.5! mb-3!">
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
              <div class="flex! items-center! justify-between!">
                <span><span class="font-mono! font-bold! text-rose-600!">1.1.03.01</span> → <span class="font-mono! font-bold! text-emerald-600!">4.1.01.02</span> (Abodemen)</span>
                <span class="font-bold!">${abodemenCount}</span>
              </div>
              <div class="flex! items-center! justify-between!">
                <span><span class="font-mono! font-bold! text-rose-600!">1.1.03.01</span> → <span class="font-mono! font-bold! text-emerald-600!">4.1.01.03</span> (Pemakaian)</span>
                <span class="font-bold!">${pemakaianCount}</span>
              </div>
              <div class="flex! items-center! justify-between! pt-1! border-t! border-slate-200/60! mt-1!">
                <span class="text-slate-500!">Durasi proses</span>
                <span class="font-bold!">${data?.duration_ms ?? 0} ms</span>
              </div>
            </div>

            ${
              tagihanDiproses === 0
                ? `<p class="mt-3! text-xs! italic! text-emerald-700!">
                    Tidak ada tagihan menunggak baru yang perlu diproses — semua sudah
                    memiliki jurnal piutang, atau belum ada tagihan yang melewati
                    batas toleransi.
                  </p>`
                : `<p class="mt-3! text-xs! italic! text-slate-500!">
                    Jurnal piutang telah otomatis masuk ke transaksi <strong>abodemen</strong>
                    dan <strong>denda</strong>.
                  </p>`
            }
        </div>
      `

    return MySwal.fire({
      icon: isSuccess ? 'success' : 'warning',
      title: 'Generate Piutang Tunggakan',
      html,
      width: 560,
      confirmButtonText: 'Tutup',
      confirmButtonColor: '#0EA5E9',
      // Hanya admin yang boleh membuka halaman /app/arsip/tunggakan
      // (lihat router/index.js role-specific routes). Tombol ini
      // ditampilkan hanya untuk admin supaya teknisi tidak dialihkan
      // ke halaman terlarang.
      showCancelButton: tagihanDiproses > 0 && uiStore.userRole === 'admin',
      cancelButtonText: 'Lihat Arsip Tunggakan',
      cancelButtonColor: '#64748b',
      customClass: {
        popup: 'rounded-2xl!',
        confirmButton: 'rounded-xl! px-4! py-2! font-bold!',
        cancelButton: 'rounded-xl! px-4! py-2! font-bold!',
        title: 'text-lg!',
      },
    }).then((result) => {
      // Tandai dismiss di cache sisi klien supaya tidak muncul lagi di session ini.
      try {
        sessionStorage.setItem('overdue_gen_popup_seen', '1')
      } catch {
        /* ignore */
      }
      // Jika user pilih "Lihat Arsip Tunggakan", arahkan ke halaman arsip.
      if (result.isDismissed && result.dismiss === MySwal.DismissReason.cancel) {
        router.push('/app/arsip/tunggakan')
      }
      return result
    })
  }

  /**
   * Panggil dari `onMounted` di halaman dashboard.
   * Hanya menampilkan UI ketika backend benar-benar menjalankan generate.
   */
  const checkOverdueGenOnMount = async () => {
    if (isChecking.value) return

    // Cegah pop up berulang dalam session browser yang sama.
    try {
      if (sessionStorage.getItem('overdue_gen_popup_seen') === '1') return
    } catch {
      /* sessionStorage tidak tersedia */
    }

    isChecking.value = true
    try {
      const res = await dashboardService.autoGenerateOverdue()
      lastResult.value = res
      if (res?.success && res?.ran === true) {
        await showOverdueGenResult(res)
      }
    } catch (err) {
      // Silent: kesalahan auto-generate tidak boleh menggangu dashboard.
      // eslint-disable-next-line no-console
      console.warn('[overdue-gen] auto generate gagal:', err?.message || err)
    } finally {
      isChecking.value = false
    }
  }

  return {
    isChecking,
    lastResult,
    checkOverdueGenOnMount,
    showOverdueGenResult,
  }
}

export default useOverdueGenNotification