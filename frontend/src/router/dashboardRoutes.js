/**
 * Sumber tunggal untuk "ke mana user dengan role ini harus diarahkan".
 *
 * Sebelumnya fungsi ini diduplikasi di router/index.js dan LoginView.vue
 * dengan isi yang BERBEDA: router menulis surveyor -> '/app' sementara
 * LoginView menulis surveyor -> '/app/surveyor'. Akibatnya surveyor punya dua
 * URL untuk satu halaman yang sama, dan state aktif di sidebar tidak pernah
 * cocok saat keduanya dipakai.
 *
 * Sekarang keduanya mengimpor dari sini.
 */
export const DASHBOARD_ROUTES = {
  admin: '/app',
  surveyor: '/app/surveyor',
  teknisi: '/app/teknisi',
  pelanggan: '/app',
}

export function getDashboardRoute(role) {
  return DASHBOARD_ROUTES[role] || DASHBOARD_ROUTES.admin
}