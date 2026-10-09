<template>
  <div class="topnav-panel" role="dialog" aria-label="Bantuan teknikal support">
    <header class="topnav-panel-header">
      <div class="topnav-panel-title">
        <font-awesome-icon icon="headset" class="topnav-panel-title-icon" />
        <div>
          <h3>Teknikal Support</h3>
          <p class="topnav-panel-subtitle">Ada kendala? Hubungi tim kami di bawah ini.</p>
        </div>
      </div>
      <button type="button" class="topnav-panel-close" aria-label="Tutup" @click="$emit('close')">
        <font-awesome-icon icon="times" />
      </button>
    </header>

    <div class="topnav-panel-body">
      <!-- Kartu kontak utama -->
      <div class="support-contact-card">
        <div class="support-contact-avatar">
          <font-awesome-icon icon="headset" />
        </div>
        <div class="support-contact-info">
          <p class="support-contact-label">Nomor Teknikal Support</p>
          <p class="support-contact-number">{{ SUPPORT_PHONE_DISPLAY }}</p>
        </div>
      </div>

      <!-- Daftar jenis kendala yang perlu menghubungi support -->
      <p class="support-section-title">Kendala yang perlu menghubungi support</p>
      <ul class="support-issue-list">
        <li v-for="issue in SUPPORT_ISSUES" :key="issue.title" class="support-issue">
          <font-awesome-icon :icon="issue.icon" class="support-issue-icon" />
          <div>
            <p class="support-issue-title">{{ issue.title }}</p>
            <p class="support-issue-desc">{{ issue.desc }}</p>
          </div>
        </li>
      </ul>

      <div class="support-note">
        <font-awesome-icon icon="circle-info" />
        <span>Layanan tersedia setiap hari, 08.00–17.00 WIB.</span>
      </div>
    </div>

    <footer class="topnav-panel-footer">
      <button type="button" class="support-action-btn is-primary" @click="openWhatsApp">
        <font-awesome-icon :icon="['fab', 'whatsapp']" />
        <span>WhatsApp</span>
      </button>
      <button type="button" class="support-action-btn" @click="makeCall">
        <font-awesome-icon icon="phone" />
        <span>Telepon</span>
      </button>
    </footer>
  </div>
</template>

<script setup>
/**
 * Panel bantuan untuk icon tanda tanya (?) di navbar.
 *
 * Sengaja TIDAK membaca dari `settings.telepon`. Nomor ini adalah nomor
 * Teknikal Support yang khusus, berbeda dari nomor kantor lembaga. Kalau
 * ikut membaca setting, nomor di sini akan ikut berubah setiap admin
 * mengedit Profil Lembaga, padahal itu bukan nomor yang dipakai teknisi.
 *
 * Mengubah nomor support dilakukan di satu tempat: `SUPPORT_PHONE_DIGITS`
 * di bawah. Format internasional tanpa "+" dan spasi.
 */
defineEmits(['close'])

// 62 882-0066-44656
const SUPPORT_PHONE_DIGITS = '62882006644656'
const SUPPORT_PHONE_DISPLAY = '+62 882-0066-44656'

const SUPPORT_ISSUES = [
  {
    icon: 'triangle-exclamation',
    title: 'Kendala saat transaksi',
    desc: 'Transaksi gagal, error saat menyimpan, atau nominal tidak sesuai.',
  },
  {
    icon: 'file-invoice-dollar',
    title: 'Kendala tagihan & pembayaran',
    desc: 'Tagihan tidak sesuai, pembayaran tidak tercatat, atau bukti bayar bermasalah.',
  },
  {
    icon: 'sliders',
    title: 'Kendala setting & data',
    desc: 'Data pelanggan, meter, atau pengaturan yang tidak bisa diubah.',
  },
  {
    icon: 'bolt',
    title: 'Gangguan sistem',
    desc: 'Aplikasi lambat, tidak bisa masuk, atau halaman tidak terbuka.',
  },
]

const openWhatsApp = () => {
  const text = encodeURIComponent(
    'Halo Teknikal Support, saya sedang mengalami kendala saat menggunakan aplikasi PAMSIDES. Mohon bantuan.',
  )

  window.open(`https://wa.me/${SUPPORT_PHONE_DIGITS}?text=${text}`, '_blank', 'noopener')
}

const makeCall = () => {
  window.location.href = `tel:+${SUPPORT_PHONE_DIGITS}`
}
</script>
