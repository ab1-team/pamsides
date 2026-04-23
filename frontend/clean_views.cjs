const fs = require('fs');
const path = require('path');

const files = [
  "C:\\laragon\\www\\pamsides-v2\\frontend\\src\\views\\auth\\LoginView.vue",
  "C:\\laragon\\www\\pamsides-v2\\frontend\\src\\views\\dashboard\\arsipDashbord\\ArsipInstalasi.vue",
  "C:\\laragon\\www\\pamsides-v2\\frontend\\src\\views\\dashboard\\arsipDashbord\\ArsipPemakaian.vue",
  "C:\\laragon\\www\\pamsides-v2\\frontend\\src\\views\\dashboard\\arsipDashbord\\ArsipTagihan.vue",
  "C:\\laragon\\www\\pamsides-v2\\frontend\\src\\views\\dashboard\\arsipDashbord\\ArsipTunggakan.vue",
  "C:\\laragon\\www\\pamsides-v2\\frontend\\src\\views\\dashboard\\instalasi\\partials\\view\\cetakInput.vue",
  "C:\\laragon\\www\\pamsides-v2\\frontend\\src\\views\\dashboard\\instalasi\\partials\\aktif.vue",
  "C:\\laragon\\www\\pamsides-v2\\frontend\\src\\views\\dashboard\\instalasi\\partials\\blokir.vue",
  "C:\\laragon\\www\\pamsides-v2\\frontend\\src\\views\\dashboard\\instalasi\\partials\\cabut.vue",
  "C:\\laragon\\www\\pamsides-v2\\frontend\\src\\views\\dashboard\\instalasi\\partials\\detailPemakaianAir.vue",
  "C:\\laragon\\www\\pamsides-v2\\frontend\\src\\views\\dashboard\\instalasi\\partials\\hasilInputModal.vue",
  "C:\\laragon\\www\\pamsides-v2\\frontend\\src\\views\\dashboard\\instalasi\\partials\\pasangBaru.vue",
  "C:\\laragon\\www\\pamsides-v2\\frontend\\src\\views\\dashboard\\instalasi\\partials\\permohonan.vue",
  "C:\\laragon\\www\\pamsides-v2\\frontend\\src\\views\\dashboard\\instalasi\\caterPemakaianAir.vue",
  "C:\\laragon\\www\\pamsides-v2\\frontend\\src\\views\\dashboard\\instalasi\\InstalasiStatus.vue",
  "C:\\laragon\\www\\pamsides-v2\\frontend\\src\\views\\dashboard\\instalasi\\registrasi.vue",
  "C:\\laragon\\www\\pamsides-v2\\frontend\\src\\views\\dashboard\\kelas\\KelasCreate.vue",
  "C:\\laragon\\www\\pamsides-v2\\frontend\\src\\views\\dashboard\\kelas\\KelasEdit.vue",
  "C:\\laragon\\www\\pamsides-v2\\frontend\\src\\views\\dashboard\\kelas\\KelasIndex.vue",
  "C:\\laragon\\www\\pamsides-v2\\frontend\\src\\views\\dashboard\\layout\\SidebarView.vue",
  "C:\\laragon\\www\\pamsides-v2\\frontend\\src\\views\\dashboard\\pelanggan\\PelangganEdit.vue",
  "C:\\laragon\\www\\pamsides-v2\\frontend\\src\\views\\dashboard\\profil\\ProfilIndex.vue",
  "C:\\laragon\\www\\pamsides-v2\\frontend\\src\\views\\dashboard\\sop\\partials\\LogoSop.vue",
  "C:\\laragon\\www\\pamsides-v2\\frontend\\src\\views\\dashboard\\sop\\SopIndex.vue",
  "C:\\laragon\\www\\pamsides-v2\\frontend\\src\\views\\dashboard\\transaksi\\arsip\\alokasiLaba.vue",
  "C:\\laragon\\www\\pamsides-v2\\frontend\\src\\views\\dashboard\\transaksi\\Tagihan\\tagihanBulanan.vue",
  "C:\\laragon\\www\\pamsides-v2\\frontend\\src\\views\\dashboard\\transaksi\\Tagihan\\tagihanInstalasi.vue",
  "C:\\laragon\\www\\pamsides-v2\\frontend\\src\\views\\dashboard\\transaksi\\komisiSPS.vue",
  "C:\\laragon\\www\\pamsides-v2\\frontend\\src\\views\\dashboard\\transaksi\\tutupBuku.vue",
  "C:\\laragon\\www\\pamsides-v2\\frontend\\src\\views\\dashboard\\DashboardMain.vue"
];

let totalFilesModified = 0;

files.forEach(file => {
  if (fs.existsSync(file)) {
    let original = fs.readFileSync(file, 'utf8');
    let content = original;
    
    // Remove HTML comments except eslint
    content = content.replace(/<!--(?!\s*eslint)([\s\S]*?)-->/g, '');
    
    // Protect mime types that use /*
    content = content.replace(/image\/\*/g, '___IMAGE_STAR___');
    content = content.replace(/video\/\*/g, '___VIDEO_STAR___');
    content = content.replace(/audio\/\*/g, '___AUDIO_STAR___');
    content = content.replace(/application\/\*/g, '___APP_STAR___');

    // Remove CSS and JS multiline comments
    content = content.replace(/\/\*[\s\S]*?\*\//g, '');
    
    // Restore mime types
    content = content.replace(/___IMAGE_STAR___/g, 'image/*');
    content = content.replace(/___VIDEO_STAR___/g, 'video/*');
    content = content.replace(/___AUDIO_STAR___/g, 'audio/*');
    content = content.replace(/___APP_STAR___/g, 'application/*');
    
    // Remove JS single line comments (not preceded by :)
    content = content.replace(/(?<!:)\/\/.*$/gm, '');
    
    // Clean up resulting empty lines (multiple consecutive empty lines to single)
    content = content.replace(/^\s*[\r\n]/gm, '\n');
    content = content.replace(/\n{3,}/g, '\n\n');
    
    if (content !== original) {
      fs.writeFileSync(file, content, 'utf8');
      totalFilesModified++;
      console.log('Cleaned:', file);
    }
  }
});

console.log(`\nSuccess: Modified ${totalFilesModified} files.`);
