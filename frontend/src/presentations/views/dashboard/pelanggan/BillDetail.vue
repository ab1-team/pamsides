<template>
  <div class="bill-detail-view p-4! lg:p-8!">
    <div class="max-w-4xl! mx-auto!">
      <!-- Actions -->
      <div class="mb-8! flex! items-center! justify-between!">
        <BaseButton variant="ghost" icon="arrow-left" @click="$router.back()">Kembali</BaseButton>
        <div class="flex! gap-3!">
          <BaseButton variant="secondary" icon="print" @click="printInvoice">Cetak PDF</BaseButton>
          <BaseButton variant="primary-gradient" icon="share-alt">Bagikan</BaseButton>
        </div>
      </div>

      <!-- Invoice Content -->
      <div
        id="printable-invoice"
        class="bg-white! rounded-[2.5rem]! shadow-2xl! shadow-slate-200/50! overflow-hidden! border! border-slate-100!"
      >
        <!-- Header -->
        <div class="p-10! bg-slate-900! text-white! flex! justify-between! items-start!">
          <div>
            <div class="flex! items-center! gap-3! mb-6!">
              <div
                class="w-12! h-12! bg-indigo-500! rounded-2xl! flex! items-center! justify-center!"
              >
                <font-awesome-icon icon="droplet" class="text-2xl!" />
              </div>
              <h2 class="text-2xl! font-black! tracking-tighter!">
                PAMSIDES <span class="text-indigo-400!">DIGITAL</span>
              </h2>
            </div>
            <p class="text-sm! opacity-60! font-medium! max-w-xs!">
              Sistem Manajemen Air Bersih Desa Mandiri Terpadu.
            </p>
          </div>
          <div class="text-right!">
            <h1 class="text-4xl! font-black! tracking-tight! mb-2!">INVOICE</h1>
            <p class="text-xs! font-bold! opacity-50! tracking-widest! uppercase!">
              No. #INV/2025/05/882109
            </p>
          </div>
        </div>

        <!-- Info Grid -->
        <div class="p-10! grid! grid-cols-1! md:grid-cols-2! gap-12!">
          <div>
            <h4 class="text-[10px]! font-black! text-slate-400! uppercase! tracking-widest! mb-4!">
              PELANGGAN
            </h4>
            <div class="space-y-1!">
              <p class="text-xl! font-black! text-slate-800!">Bambang Susanto</p>
              <p class="text-sm! text-slate-500! font-medium!">ID Pelanggan: #MA-882109</p>
              <p class="text-sm! text-slate-500! font-medium!">
                Alamat: Southern Spring, Blok B/12
              </p>
              <p class="text-sm! text-slate-500! font-medium!">Dusun: III (Kulon Progo)</p>
            </div>
          </div>
          <div class="text-left! md:text-right!">
            <h4 class="text-[10px]! font-black! text-slate-400! uppercase! tracking-widest! mb-4!">
              DETAIL TAGIHAN
            </h4>
            <div class="space-y-1!">
              <p class="text-sm! text-slate-500! font-medium!">
                Periode: <span class="text-slate-800! font-bold!">Mei 2025</span>
              </p>
              <p class="text-sm! text-slate-500! font-medium!">
                Tanggal Cetak: <span class="text-slate-800! font-bold!">05 Mei 2025</span>
              </p>
              <p class="text-sm! text-slate-500! font-medium!">
                Jatuh Tempo: <span class="text-red-600! font-bold!">20 Mei 2025</span>
              </p>
              <div
                class="mt-4! inline-block! px-4! py-1.5! bg-red-50! text-red-600! rounded-full! text-[10px]! font-black! border! border-red-100!"
              >
                BELUM LUNAS
              </div>
            </div>
          </div>
        </div>

        <!-- Meter Reading Section -->
        <div class="px-10! mb-10!">
          <div class="bg-slate-50! rounded-3xl! p-8! grid! grid-cols-1! sm:grid-cols-3! gap-8!">
            <div class="text-center!">
              <p class="text-[10px]! font-black! text-slate-400! uppercase! mb-1!">METER LALU</p>
              <p class="text-2xl! font-black! text-slate-700!">
                1.240 <span class="text-xs! opacity-40!">m³</span>
              </p>
            </div>
            <div class="text-center! border-x! border-slate-200!">
              <p class="text-[10px]! font-black! text-slate-400! uppercase! mb-1!">METER KINI</p>
              <p class="text-2xl! font-black! text-slate-900!">
                1.265 <span class="text-xs! opacity-40!">m³</span>
              </p>
            </div>
            <div class="text-center!">
              <p class="text-[10px]! font-black! text-indigo-400! uppercase! mb-1!">TOTAL PAKAI</p>
              <p class="text-2xl! font-black! text-indigo-600!">
                25 <span class="text-xs! opacity-40!">m³</span>
              </p>
            </div>
          </div>
        </div>

        <!-- Items Table -->
        <div class="px-10! mb-10!">
          <table class="w-full!">
            <thead>
              <tr class="border-b! border-slate-100!">
                <th class="py-4! text-left! text-[10px]! font-black! text-slate-400! uppercase!">
                  Rincian Biaya
                </th>
                <th class="py-4! text-center! text-[10px]! font-black! text-slate-400! uppercase!">
                  Volume
                </th>
                <th class="py-4! text-right! text-[10px]! font-black! text-slate-400! uppercase!">
                  Subtotal
                </th>
              </tr>
            </thead>
            <tbody class="text-sm!">
              <tr class="border-b! border-slate-50!">
                <td class="py-6!">
                  <div class="font-bold! text-slate-800!">Blok I (0 - 10 m³)</div>
                  <p class="text-xs! text-slate-400!">Tarif Dasar @ Rp. 2.500</p>
                </td>
                <td class="py-6! text-center! font-bold! text-slate-600!">10 m³</td>
                <td class="py-6! text-right! font-bold! text-slate-800!">Rp. 25.000</td>
              </tr>
              <tr class="border-b! border-slate-50!">
                <td class="py-6!">
                  <div class="font-bold! text-slate-800!">Blok II (11 - 20 m³)</div>
                  <p class="text-xs! text-slate-400!">Tarif Menengah @ Rp. 3.000</p>
                </td>
                <td class="py-6! text-center! font-bold! text-slate-600!">10 m³</td>
                <td class="py-6! text-right! font-bold! text-slate-800!">Rp. 30.000</td>
              </tr>
              <tr class="border-b! border-slate-50!">
                <td class="py-6!">
                  <div class="font-bold! text-slate-800!">Blok III (> 20 m³)</div>
                  <p class="text-xs! text-slate-400!">Tarif Progresif @ Rp. 4.000</p>
                </td>
                <td class="py-6! text-center! font-bold! text-slate-600!">5 m³</td>
                <td class="py-6! text-right! font-bold! text-slate-800!">Rp. 20.000</td>
              </tr>
              <tr>
                <td class="py-6!">
                  <div class="font-bold! text-slate-800!">Biaya Abodemen</div>
                  <p class="text-xs! text-slate-400!">Biaya Pemeliharaan & Admin</p>
                </td>
                <td class="py-6! text-center! font-bold! text-slate-600!">1 Bln</td>
                <td class="py-6! text-right! font-bold! text-slate-800!">Rp. 10.000</td>
              </tr>
            </tbody>
          </table>
        </div>

        <!-- Total -->
        <div class="p-10! bg-slate-50! flex! justify-between! items-center!">
          <div class="flex! items-center! gap-4!">
            <div
              class="w-16! h-16! bg-white! rounded-2xl! border! border-slate-200! flex! items-center! justify-center!"
            >
              <font-awesome-icon icon="qrcode" class="text-3xl! text-slate-800!" />
            </div>
            <p
              class="text-[9px]! font-bold! text-slate-400! max-w-[120px]! uppercase! leading-relaxed!"
            >
              Scan QR ini untuk verifikasi keaslian dokumen.
            </p>
          </div>
          <div class="text-right!">
            <p class="text-xs! font-black! text-slate-400! uppercase! tracking-widest! mb-1!">
              TOTAL TAGIHAN
            </p>
            <h2 class="text-5xl! font-black! text-indigo-600! tracking-tighter!">Rp. 85.000</h2>
          </div>
        </div>
      </div>

      <div
        class="mt-8! p-6! bg-amber-50! border! border-amber-100! rounded-3xl! flex! items-start! gap-4!"
      >
        <font-awesome-icon icon="circle-exclamation" class="text-amber-500! mt-1!" />
        <p class="text-xs! text-amber-800! font-medium! leading-relaxed!">
          <strong>Informasi Penting:</strong> Pembayaran dapat dilakukan melalui Kantor Desa atau
          Transfer Bank. Harap sertakan No. Invoice saat melakukan pembayaran. Denda 10% berlaku
          setelah tanggal jatuh tempo.
        </p>
      </div>
    </div>
  </div>
</template>

<script setup>
import BaseButton from '@/presentations/components/ui/BaseButton.vue'

const printInvoice = () => {
  window.print()
}
</script>

<style scoped>
.bill-detail-view {
  animation: fadeIn 0.8s ease-out;
}

@keyframes fadeIn {
  from {
    opacity: 0;
    transform: scale(0.98);
  }
  to {
    opacity: 1;
    transform: scale(1);
  }
}

@media print {
  .bill-detail-view {
    padding: 0 !important;
  }
  .max-w-4xl {
    max-width: 100% !important;
  }
  button,
  .flex.gap-3 {
    display: none !important;
  }
  #printable-invoice {
    box-shadow: none !important;
    border: none !important;
  }
  .bill-detail-view .mb-8 {
    display: none !important;
  }
}
</style>
