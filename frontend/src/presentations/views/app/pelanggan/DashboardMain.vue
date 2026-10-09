<template>
  <div class="pelanggan-dashboard">
    <div class="max-w-7xl! mx-auto!">
      <div
        class="mb-10! lg:mb-14! flex! flex-col! lg:flex-row! lg:items-center! justify-between! gap-8!"
      >
        <div class="flex! flex-col! items-center! lg:items-start! w-full! lg:w-auto!">
          <div class="flex! justify-center! lg:justify-start! mb-4!">
            <div
              class="inline-flex! items-center! gap-2! bg-indigo-50! px-4! py-1.5! rounded-full! border! border-indigo-100!"
            >
              <div class="w-1.5! h-1.5! bg-indigo-600! rounded-full! animate-pulse!"></div>
              <span
                class="text-[9px]! lg:text-[10px]! font-black! text-indigo-600! uppercase! tracking-[0.2em]!"
                >Ringkasan Layanan</span
              >
            </div>
          </div>
          <div class="text-center! lg:text-left!">
            <h1
              class="text-2xl! sm:text-3xl! lg:text-5xl! font-black! text-slate-800! tracking-tighter! mb-2!"
            >
              Selamat Datang,<br v-if="isMobile" />
              <span
                class="bg-gradient-to-r! from-indigo-600! to-blue-500! bg-clip-text! text-transparent!"
                >{{ dashboardData.user.name }}</span
              >
            </h1>
            <p class="text-slate-500! font-medium! text-sm! lg:text-lg! max-w-md! lg:mx-0!">
              Kode Pelanggan:
              <span class="font-black! text-indigo-600!">{{
                dashboardData.user.customer_code
              }}</span>
            </p>
          </div>
        </div>
      </div>

      <!-- Error: jangan tampilkan "Rp 0 / LUNAS" sebagai nilai default saat gagal -->
      <div
        v-if="loadError"
        class="mb-8! rounded-3xl! bg-rose-50! border! border-rose-200! p-4! lg:p-5! flex! flex-col! sm:flex-row! sm:items-center! justify-between! gap-3!"
      >
        <div class="flex! items-start! gap-3!">
          <font-awesome-icon
            icon="exclamation-triangle"
            class="text-rose-600! mt-0.5!"
          />
          <div>
            <p class="text-xs! font-black! text-rose-800!">{{ loadError }}</p>
            <p class="text-[10px]! text-rose-600! mt-0.5!">
              Data tagihan &amp; tunggakan di bawah belum dapat ditampilkan.
            </p>
          </div>
        </div>
        <button
          type="button"
          class="shrink-0! rounded-full! bg-rose-600! text-white! px-5! py-2! text-[10px]! font-black! uppercase! hover:bg-rose-700! transition-colors!"
          @click="fetchDashboardData"
        >
          Muat Ulang
        </button>
      </div>

      <!-- Info tunggakan -->
      <div
        v-if="arrears.overdue_count > 0"
        class="mb-8! rounded-3xl! bg-gradient-to-br! from-rose-600! to-rose-700! text-white! p-5! lg:p-7! shadow-xl! shadow-rose-200! relative! overflow-hidden!"
      >
        <div
          class="absolute! -right-10! -top-10! w-40! h-40! bg-white/10! rounded-full! blur-2xl! pointer-events-none!"
        ></div>
        <div class="relative! z-10! flex! flex-col! md:flex-row! md:items-center! justify-between! gap-5!">
          <div class="flex! items-start! gap-4!">
            <div
              class="w-12! h-12! rounded-2xl! bg-white/15! flex! items-center! justify-center! flex-shrink-0! backdrop-blur-sm!"
            >
              <font-awesome-icon icon="exclamation-triangle" class="text-xl!" />
            </div>
            <div>
              <h2 class="text-base! lg:text-xl! font-black! tracking-tight!">
                Anda memiliki {{ arrears.overdue_count }} tagihan lewat jatuh tempo
              </h2>
              <p class="text-[11px]! lg:text-sm! font-medium! text-rose-100! mt-1!">
                Total tagihan yang tertunda
                <span class="font-black!">Rp. {{ formatNumber(arrears.overdue_amount) }}</span>
                <template v-if="arrears.max_overdue_days > 0">
                  • terlama <span class="font-black!">{{ arrears.max_overdue_days }} hari</span>
                </template>
              </p>
            </div>
          </div>
          <BaseButton
            variant="ghost"
            block
            class="md:flex-shrink-0! md:w-auto! rounded-full! bg-white! text-rose-700! font-black! h-11! px-7! text-xs! hover:bg-rose-50! shadow-lg! transition-all!"
            @click="goToBillHistory"
          >
            LIHAT RIWAYAT
            <font-awesome-icon icon="arrow-right" class="ml-2! text-[10px]!" />
          </BaseButton>
        </div>
      </div>

      <!-- Info belum lunas tapi masih dalam tempo -->
      <div
        v-else-if="arrears.unpaid_count > 0"
        class="mb-8! rounded-3xl! bg-amber-50! border! border-amber-200! p-5! lg:p-6! flex! flex-col! md:flex-row! md:items-center! justify-between! gap-4!"
      >
        <div class="flex! items-start! gap-4!">
          <div
            class="w-11! h-11! rounded-2xl! bg-amber-100! text-amber-600! flex! items-center! justify-center! flex-shrink-0!"
          >
            <font-awesome-icon icon="clock" />
          </div>
          <div>
            <h2 class="text-sm! lg:text-base! font-black! text-amber-900!">
              {{ arrears.unpaid_count }} tagihan menunggu pembayaran
            </h2>
            <p class="text-[11px]! lg:text-sm! font-medium! text-amber-700! mt-0.5!">
              Total
              <span class="font-black!">Rp. {{ formatNumber(arrears.total_unpaid_amount) }}</span>
              • belum melewati batas waktu pembayaran
            </p>
          </div>
        </div>
        <BaseButton
          variant="ghost"
          block
          class="md:flex-shrink-0! md:w-auto! rounded-full! bg-amber-500! text-white! font-black! h-11! px-7! text-xs! hover:bg-amber-600! shadow-lg! transition-all!"
          @click="goToBillHistory"
        >
          LIHAT RIWAYAT
          <font-awesome-icon icon="arrow-right" class="ml-2! text-[10px]!" />
        </BaseButton>
      </div>

      <div class="grid! grid-cols-1! lg:grid-cols-12! gap-10! mb-12!">
        <div class="lg:col-span-4!">
          <ContentCard
            variant="elevated"
            padding="none"
            class="h-full! border-0! shadow-[0_25px_50px_-12px_rgba(0,0,0,0.15)]! rounded-3xl! overflow-hidden! bg-white! relative!"
          >
            <div
              class="absolute! top-0! left-0! right-0! h-2! bg-gradient-to-r! from-indigo-500! to-blue-400!"
            ></div>

            <div class="p-5! lg:p-8!">
              <div class="flex! items-center! justify-between! mb-6! lg:mb-8!">
                <div
                  class="w-14! h-14! rounded-full! bg-indigo-50! flex! items-center! justify-center! text-indigo-600! shadow-inner!"
                >
                  <font-awesome-icon icon="receipt" size="lg" />
                </div>
                <div class="flex! items-center!">
                  <span
                    v-if="arrears.unpaid_count > 0"
                    class="px-4! py-1.5! bg-red-50! text-red-600! text-[10px]! font-black! rounded-full! border! border-red-100! tracking-widest!"
                    >BELUM LUNAS</span
                  >
                  <span
                    v-else-if="dashboardData.latest_bill"
                    class="px-4! py-1.5! bg-emerald-50! text-emerald-600! text-[10px]! font-black! rounded-full! border! border-emerald-100! tracking-widest!"
                    >LUNAS</span
                  >
                  <span
                    v-else
                    class="px-4! py-1.5! bg-slate-50! text-slate-500! text-[10px]! font-black! rounded-full! border! border-slate-200! tracking-widest!"
                    >BELUM ADA TAGIHAN</span
                  >
                </div>
              </div>

              <div class="mb-8!">
                <h3
                  class="text-slate-400! text-[10px]! font-black! uppercase! tracking-widest! mb-2!"
                >
                  {{ isOverdue ? 'Total Tunggakan' : 'Total Tagihan' }}
                </h3>
                <div class="flex! items-baseline! justify-end! gap-1!">
                  <span class="text-base! lg:text-lg! font-black! text-slate-400!">Rp.</span>
                  <span
                    :class="`text-3xl! lg:text-4xl! font-black! tracking-tighter! ${isOverdue ? 'text-rose-600!' : 'text-slate-800!'}`"
                    >{{ formatNumber(currentBillAmount) }}</span
                  >
                </div>
              </div>

              <div
                class="space-y-4! mb-10! bg-slate-50! p-5! rounded-3xl! border! border-slate-100!"
              >
                <div class="flex! justify-between! items-center!">
                  <span class="text-sm! font-bold! text-slate-500!">Periode Tagihan</span>
                  <span class="text-sm! font-black! text-slate-800!">
                    {{ formatPeriod(dashboardData.latest_bill) }}
                  </span>
                </div>
                <div class="w-full! h-px! bg-slate-200!"></div>
                <div class="flex! justify-between! items-center!">
                  <span class="text-sm! font-bold! text-slate-500!">Pemakaian Air</span>
                  <span class="text-sm! font-black! text-slate-800!">
                    {{ formatMeter(dashboardData.latest_bill?.usage_m3) }} m³
                  </span>
                </div>
                <div class="w-full! h-px! bg-slate-200!"></div>
                <div class="flex! justify-between! items-center!">
                  <span class="text-sm! font-bold! text-slate-500!">Jatuh Tempo</span>
                  <span
                    :class="`text-sm! font-black! ${isOverdue ? 'text-rose-600!' : 'text-slate-800!'}`"
                    >
                    {{ formatDate(dashboardData.latest_bill?.due_date) }}
                  </span>
                </div>
                <template v-if="arrears.unpaid_count > 1">
                  <div class="w-full! h-px! bg-slate-200!"></div>
                  <div class="flex! justify-between! items-center!">
                    <span class="text-sm! font-bold! text-slate-500!">Total Belum Lunas</span>
                    <span class="text-sm! font-black! text-indigo-600!">
                      {{ arrears.unpaid_count }} tagihan
                    </span>
                  </div>
                </template>
              </div>

              <BaseButton
                variant="primary-gradient"
                block
                class="rounded-full! font-black! h-12! text-sm! shadow-xl! shadow-indigo-200! hover:-translate-y-1! transition-all!"
                @click="goToBillDetail"
              >
                CEK DETAIL
                <font-awesome-icon icon="chevron-right" class="ml-2! text-[10px]!" />
              </BaseButton>
            </div>
          </ContentCard>
        </div>
        <div class="lg:col-span-8!">
          <ContentCard
            variant="elevated"
            padding="none"
            class="h-full! border-0! shadow-[0_25px_50px_-12px_rgba(0,0,0,0.1)]! rounded-3xl! bg-white!"
          >
            <div class="p-5! lg:p-8! flex! items-start! justify-between! mb-3! gap-4!">
              <div>
                <h2 class="text-lg! lg:text-xl! font-black! text-slate-800! tracking-tight!">
                  Distribusi Penggunaan
                </h2>
                <p class="text-slate-400! text-[10px]! lg:text-xs! font-medium! mt-1!">
                  Pemakaian air 12 bulan terakhir •
                  <span class="font-black! text-indigo-500!">{{ recordedCount }}/12</span>
                  bulan tercatat
                </p>
              </div>
              <div class="flex! bg-slate-50! p-1.5! rounded-2xl! border! border-slate-100!">
                <button
                  @click="viewType = 'line'"
                  :class="`text-[10px]! font-black! px-4! py-2! rounded-xl! transition-all! ${viewType === 'line' ? 'bg-white! shadow-md! text-indigo-600!' : 'text-slate-400! hover:text-slate-600!'}`"
                >
                  Garis
                </button>
                <button
                  @click="viewType = 'bar'"
                  :class="`text-[10px]! font-black! px-4! py-2! rounded-xl! transition-all! ${viewType === 'bar' ? 'bg-white! shadow-md! text-indigo-600!' : 'text-slate-400! hover:text-slate-600!'}`"
                >
                  Batang
                </button>
              </div>
            </div>

            <!-- Summary metrics -->
            <div class="px-5! lg:px-8! grid! grid-cols-2! md:grid-cols-4! gap-3! mb-4!">
              <div class="p-3! rounded-2xl! bg-indigo-50! border! border-indigo-100!">
                <div class="text-[9px]! font-black! text-indigo-400! uppercase! tracking-widest!">
                  Rata-rata
                </div>
                <div class="text-base! font-black! text-indigo-700! mt-1!">
                  {{ formatDecimal(avgUsage) }} <span class="text-[10px]!">m³</span>
                </div>
              </div>
              <div class="p-3! rounded-2xl! bg-emerald-50! border! border-emerald-100!">
                <div class="text-[9px]! font-black! text-emerald-500! uppercase! tracking-widest!">
                  Minimum
                </div>
                <div class="text-base! font-black! text-emerald-700! mt-1!">
                  {{ formatDecimal(summary.min_m3) }} <span class="text-[10px]!">m³</span>
                </div>
              </div>
              <div class="p-3! rounded-2xl! bg-rose-50! border! border-rose-100!">
                <div class="text-[9px]! font-black! text-rose-500! uppercase! tracking-widest!">
                  Maksimum
                </div>
                <div class="text-base! font-black! text-rose-700! mt-1!">
                  {{ formatDecimal(summary.max_m3) }} <span class="text-[10px]!">m³</span>
                </div>
              </div>
              <div
                :class="`p-3! rounded-2xl! border! ${trend.bg}! ${trend.color.replace('text-rose-600', 'border-rose-100').replace('text-emerald-600', 'border-emerald-100').replace('text-slate-500', 'border-slate-200')}!`"
              >
                <div
                  :class="`text-[9px]! font-black! uppercase! tracking-widest! ${trend.color}! opacity-70!`"
                >
                  Tren 3 bln
                </div>
                <div :class="`flex! items-center! gap-1! mt-1! ${trend.color}!`">
                  <font-awesome-icon :icon="trend.icon" class="text-xs!" />
                  <span class="text-base! font-black!">{{ trend.label }}</span>
                </div>
              </div>
            </div>

            <div
              class="px-5! lg:px-8! pb-8! lg:pb-10! flex! flex-col! items-center! justify-center! min-h-[260px]! lg:min-h-[280px]!"
            >
              <div
                v-if="usageValues.length === 0"
                class="flex-1! flex! flex-col! items-center! justify-center! gap-3!"
              >
                <div
                  class="w-16! h-16! bg-slate-50! rounded-full! flex! items-center! justify-center! text-slate-200!"
                >
                  <font-awesome-icon icon="chart-bar" size="2x" />
                </div>
                <p class="text-slate-400! text-xs! font-medium!">Belum ada riwayat pemakaian</p>
              </div>

              <div
                v-else-if="viewType === 'bar'"
                class="w-full! relative!"
              >
                <!--
                  `preserveAspectRatio="none"` WAJIB di sini.

                  Tanpa itu, viewBox 100x40 dipaksa skalakan dengan mode
                  default `xMidYMid meet`: saat elemennya jauh lebih lebar
                  dari rasio 100:40 (mis. 800x256), seluruh SVG mengecil dan
                  batangnya setipis rambut — praktis tidak terlihat.

                  Konsekuensi samping: viewBox lalu dipanjangkan mengikuti
                  lebar elemen, teks di dalam SVG ikut gepar (forge). Karena
                  itu label angka TIDAK lagi digambar di dalam SVG, tapi
                  dipindah ke bawah sebagai HTML supaya font & posisinya
                  konsisten di semua ukuran layar.
                -->
                <svg viewBox="0 0 100 40" preserveAspectRatio="none" class="w-full! h-56! lg:h-64!">
                  <defs>
                    <linearGradient id="barGradUp" x1="0%" y1="0%" x2="0%" y2="100%">
                      <stop offset="0%" style="stop-color: #34d399; stop-opacity: 1" />
                      <stop offset="100%" style="stop-color: #059669; stop-opacity: 0.9" />
                    </linearGradient>
                    <linearGradient id="barGradDown" x1="0%" y1="0%" x2="0%" y2="100%">
                      <stop offset="0%" style="stop-color: #fb7185; stop-opacity: 1" />
                      <stop offset="100%" style="stop-color: #e11d48; stop-opacity: 0.9" />
                    </linearGradient>
                    <linearGradient id="barGradSame" x1="0%" y1="0%" x2="0%" y2="100%">
                      <stop offset="0%" style="stop-color: #94a3b8; stop-opacity: 0.95" />
                      <stop offset="100%" style="stop-color: #64748b; stop-opacity: 0.85" />
                    </linearGradient>
                  </defs>
                  <g stroke="#f1f5f9" stroke-width="1" vector-effect="non-scaling-stroke">
                    <line x1="4" :y1="getPointY(maxUsage * 0.75)" x2="96" :y2="getPointY(maxUsage * 0.75)" />
                    <line x1="4" :y1="getPointY(maxUsage * 0.5)" x2="96" :y2="getPointY(maxUsage * 0.5)" />
                    <line x1="4" :y1="getPointY(maxUsage * 0.25)" x2="96" :y2="getPointY(maxUsage * 0.25)" />
                    <line x1="4" :y1="34" x2="96" y2="34" stroke="#cbd5e1" stroke-width="1.5" />
                  </g>
                  <g v-if="avgLineY !== null">
                    <line
                      x1="4"
                      :y1="avgLineY"
                      x2="96"
                      :y2="avgLineY"
                      stroke="#f59e0b"
                      stroke-width="1"
                      vector-effect="non-scaling-stroke"
                      stroke-dasharray="4,4"
                      opacity="0.8"
                    />
                  </g>
                  <g v-for="b in barChartData" :key="'bar-' + b.idx">
                    <rect
                      :x="b.barX"
                      :y="b.barY"
                      :width="b.barWidth"
                      :height="b.barHeight"
                      :fill="b.fill"
                      :opacity="b.hasData ? 1 : 0.25"
                      rx="0.4"
                    >
                      <title>
                        {{ b.label }}: {{ formatDecimal(b.value) }} m³{{ b.changeSuffix }}
                      </title>
                    </rect>
                  </g>
                </svg>
                <div class="flex! flex-wrap! items-center! justify-between! gap-3! mt-2! px-1!">
                  <span class="text-[9px]! text-slate-400!">
                    Warna = perubahan dibanding bulan sebelumnya
                  </span>
                  <span class="flex! items-center! gap-3!">
                    <span class="flex! items-center! gap-1! text-[9px]! font-black! text-slate-400!">
                      <span class="w-2.5! h-2.5! rounded-[3px]! bg-emerald-500!"></span>
                      Naik
                    </span>
                    <span class="flex! items-center! gap-1! text-[9px]! font-black! text-slate-400!">
                      <span class="w-2.5! h-2.5! rounded-[3px]! bg-rose-500!"></span>
                      Turun
                    </span>
                    <span
                      v-if="avgLineY !== null"
                      class="flex! items-center! gap-1! text-[9px]! font-black! text-amber-600!"
                    >
                      <span class="w-3! h-px! bg-amber-400!"></span>
                      Rata²
                    </span>
                  </span>
                </div>
                <div class="flex! justify-between! mt-2! px-1!">
                  <span
                    v-for="(label, idx) in usageLabelsCompact"
                    :key="'lbl-' + idx"
                    :class="`text-[9px]! font-black! uppercase! ${distributionSeries[idx].isCurrent ? 'text-indigo-600!' : 'text-slate-400!'}`"
                  >
                    {{ label }}
                  </span>
                </div>
              </div>

              <div
                v-else-if="viewType === 'line'"
                class="w-full! relative!"
              >
                <!-- Sama seperti chart batang: `preserveAspectRatio="none"` supaya
                     garis memenuhi lebar kartu. `vector-effect` menjaga
                     ketebalan garis tetap normal meskipun viewBox dipanjangkan. -->
                <svg viewBox="0 0 100 40" preserveAspectRatio="none" class="w-full! h-56! lg:h-64!">
                  <defs>
                    <linearGradient id="areaGradient" x1="0%" y1="0%" x2="0%" y2="100%">
                      <stop offset="0%" style="stop-color: #6366f1; stop-opacity: 0.35" />
                      <stop offset="100%" style="stop-color: #6366f1; stop-opacity: 0" />
                    </linearGradient>
                  </defs>
                  <path :d="generateAreaPath" fill="url(#areaGradient)" />
                  <path
                    :d="generateLinePath"
                    fill="none"
                    stroke="#6366f1"
                    stroke-width="2"
                    vector-effect="non-scaling-stroke"
                    stroke-linecap="round"
                    stroke-linejoin="round"
                  />
                  <g v-if="avgLineY !== null">
                    <line
                      x1="4"
                      :y1="avgLineY"
                      x2="96"
                      :y2="avgLineY"
                      stroke="#f59e0b"
                      stroke-width="1"
                      vector-effect="non-scaling-stroke"
                      stroke-dasharray="4,4"
                      opacity="0.8"
                    />
                  </g>
                  <g v-for="(p, idx) in distributionSeries" :key="'pt-' + idx">
                    <circle
                      :cx="getPointX(idx)"
                      :cy="getPointY(p.usage_m3)"
                      :r="p.is_current ? 1.6 : 1.1"
                      :fill="p.is_current ? '#4f46e5' : 'white'"
                      :stroke="p.is_current ? '#4f46e5' : '#6366f1'"
                      stroke-width="1.5"
                      vector-effect="non-scaling-stroke"
                    >
                      <title>{{ p.label }}: {{ formatDecimal(p.usage_m3) }} m³</title>
                    </circle>
                  </g>
                </svg>
                <div
                  v-if="avgLineY !== null"
                  class="flex! justify-end! items-center! gap-1! mt-1! -mb-1!"
                >
                  <span class="w-3! h-px! bg-amber-400!"></span>
                  <span class="text-[9px]! font-black! text-amber-600! uppercase!">Rata²</span>
                </div>
                <div class="flex! justify-between! mt-3! px-1!">
                  <span
                    v-for="(label, idx) in usageLabelsCompact"
                    :key="'ll-' + idx"
                    :class="`text-[9px]! font-black! uppercase! ${distributionSeries[idx].is_current ? 'text-indigo-600!' : 'text-slate-400!'}`"
                  >
                    {{ label }}
                  </span>
                </div>
              </div>
            </div>

            <!-- Top 3 bulan tertinggi -->
            <div
              v-if="usageValues.length > 0 && recordedCount > 0"
              class="px-5! lg:px-8! pb-8! lg:pb-10! border-t! border-slate-100! pt-5!"
            >
              <div class="flex! items-center! justify-between! mb-3!">
                <h4 class="text-xs! font-black! text-slate-700! uppercase! tracking-widest!">
                  3 Bulan Pemakaian Tertinggi
                </h4>
                <span class="text-[10px]! text-slate-400! font-medium!">
                  Total {{ formatDecimal(totalUsage) }} m³ / {{ recordedCount }} bulan
                </span>
              </div>
              <div class="grid! grid-cols-1! sm:grid-cols-3! gap-3!">
                <div
                  v-for="(top, idx) in topMonths"
                  :key="'top-' + idx"
                  class="flex! items-center! gap-3! p-3! rounded-2xl! bg-slate-50! border! border-slate-100!"
                >
                  <div
                    :class="`w-9! h-9! rounded-xl! flex! items-center! justify-center! text-xs! font-black! ${idx === 0 ? 'bg-rose-100! text-rose-600!' : idx === 1 ? 'bg-amber-100! text-amber-600!' : 'bg-slate-200! text-slate-600!'}`"
                  >
                    #{{ idx + 1 }}
                  </div>
                  <div class="flex-1! min-w-0!">
                    <div class="text-[10px]! font-black! text-slate-400! uppercase! tracking-wide!">
                      {{ top.label }}
                    </div>
                    <div class="text-sm! font-black! text-slate-800!">
                      {{ formatDecimal(top.value) }} m³
                    </div>
                  </div>
                  <span
                    v-if="top.bill_status === 'paid'"
                    class="text-[9px]! font-black! text-emerald-600! bg-emerald-50! px-2! py-1! rounded-full! border! border-emerald-100!"
                    >LUNAS</span
                  >
                  <span
                    v-else-if="top.bill_status === 'unpaid'"
                    class="text-[9px]! font-black! text-rose-600! bg-rose-50! px-2! py-1! rounded-full! border! border-rose-100!"
                    >BELUM</span
                  >
                  <span
                    v-else
                    class="text-[9px]! font-black! text-slate-500! bg-white! px-2! py-1! rounded-full! border! border-slate-100!"
                    >—</span
                  >
                </div>
              </div>
            </div>
          </ContentCard>
        </div>
      </div>

      <div class="grid! grid-cols-1! md:grid-cols-3! gap-8!">
        <ContentCard
          v-for="(action, idx) in actions"
          :key="idx"
          variant="elevated"
          padding="none"
          clickable
          class="border-0! shadow-[0_15px_30px_-10px_rgba(0,0,0,0.12)]! group! rounded-2xl! lg:rounded-3xl! overflow-hidden! bg-white! hover:-translate-y-2! hover:shadow-[0_25px_50px_-12px_rgba(0,0,0,0.2)]! transition-all!"
          @click="action.path ? $router.push(action.path) : null"
        >
          <div class="p-4! lg:p-5! flex! items-center! gap-4!">
            <div
              :class="`w-12! h-12! rounded-full! ${action.bg}! ${action.color}! flex! items-center! justify-center! text-lg! flex-shrink-0! shadow-inner! group-hover:scale-110! transition-transform!`"
            >
              <font-awesome-icon :icon="action.icon" />
            </div>
            <div>
              <h4 class="text-base! font-black! text-slate-800! leading-tight!">
                {{ action.title }}
              </h4>
              <p class="text-[10px]! text-slate-400! font-bold! mt-1! uppercase! tracking-wider!">
                {{ action.desc }}
              </p>
            </div>
            <div class="ml-auto! text-slate-300! group-hover:text-indigo-500! transition-colors!">
              <font-awesome-icon icon="chevron-right" />
            </div>
          </div>
        </ContentCard>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted, onUnmounted, computed } from 'vue'
import { useRouter } from 'vue-router'
import ContentCard from '@/presentations/components/ui/ContentCard.vue'
import BaseButton from '@/presentations/components/ui/BaseButton.vue'
import pelangganService from '@/services/pelanggan.service'
import Swal from 'sweetalert2'

const router = useRouter()

const goToBillDetail = () => {
  const billId = dashboardData.value.latest_bill?.id
  if (!billId) {
    Swal.fire({
      icon: 'info',
      title: 'Belum Ada Tagihan',
      text: 'Saat ini belum ada tagihan yang tersedia untuk ditampilkan.',
      confirmButtonColor: '#4f46e5',
    })
    return
  }
  router.push({ path: '/app/pelanggan/tagihan-detail', query: { id: billId } })
}

const goToBillHistory = () => {
  router.push({ path: '/app/pelanggan/riwayat-tagihan', query: { from: 'dashboard' } })
}

/**
 * Tunggakan aktual dari backend. Backend sudah memakai definisi yang sama
 * dengan admin (`status = 'unpaid'` + `due_date` lewat dari hari ini), jadi
 * angka di sini tidak boleh dihitung ulang dengan aturan sendiri.
 */
const arrears = computed(() => dashboardData.value.arrears || {
  unpaid_count: 0,
  total_unpaid_amount: 0,
  overdue_count: 0,
  overdue_amount: 0,
  max_overdue_days: 0,
})

/** True bila tagihan yang tampil sudah lewat jatuh tempo. */
const isOverdue = computed(() => arrears.value.overdue_count > 0)

/**
 * Nominal yang ditampilkan di kartu tagihan.
 *
 * Kalau ada tagihan lewat jatuh tempo, angka yang relevan bagi pelanggan
 * adalah total tunggakan — bukan hanya satu tagihan yang kebetulan paling
 * baru. Kalau tidak, tampilkan nominal tagihan terbaru seperti biasa.
 */
const currentBillAmount = computed(() =>
  isOverdue.value ? arrears.value.overdue_amount : Number(dashboardData.value.latest_bill?.total_amount || 0),
)

/** Periode tagihan, mis. "Agustus 2026". */
const formatPeriod = (bill) => {
  if (!bill?.billing_period_month || !bill?.billing_period_year) return '-'
  const months = [
    'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni',
    'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember',
  ]
  return `${months[bill.billing_period_month - 1]} ${bill.billing_period_year}`
}

const MONTH_SHORT = [
  'Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun',
  'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des',
]

const viewType = ref('bar')
const loadError = ref('')

const dashboardData = ref({
  user: { name: '', customer_code: '' },
  latest_bill: null,
  usage_history: [],
  arrears: {
    unpaid_count: 0,
    total_unpaid_amount: 0,
    overdue_count: 0,
    overdue_amount: 0,
    max_overdue_days: 0,
  },
  distribution: {
    series: [],
    months_count: 12,
    summary: {
      total_m3: 0,
      avg_m3: 0,
      max_m3: 0,
      min_m3: 0,
      recorded_months: 0,
      trend_direction: 'flat',
      trend_percent: 0,
      recent_avg_m3: 0,
      previous_avg_m3: 0,
    },
  },
  balance: 0,
})

const distributionSeries = computed(() => {
  const series = dashboardData.value.distribution?.series || []
  return series.map((p) => ({
    ...p,
    label: `${MONTH_SHORT[p.month - 1]} ${String(p.year).slice(-2)}`,
    shortLabel: MONTH_SHORT[p.month - 1],
  }))
})

const usageValues = computed(() => distributionSeries.value.map((p) => Number(p.usage_m3 || 0)))

const usageLabelsCompact = computed(() => distributionSeries.value.map((p) => p.shortLabel))

const recordedCount = computed(() =>
  distributionSeries.value.filter((p) => p.has_reading || p.has_bill).length,
)

const summary = computed(() => dashboardData.value.distribution?.summary || {})

const totalUsage = computed(() => summary.value.total_m3 || 0)
const avgUsage = computed(() => summary.value.avg_m3 || 0)
const maxUsage = computed(() => Math.max(...usageValues.value, 1))

const trend = computed(() => {
  const dir = summary.value.trend_direction || 'flat'
  const pct = Math.abs(Number(summary.value.trend_percent || 0))
  if (dir === 'up') return { icon: 'arrow-up', color: 'text-rose-600', bg: 'bg-rose-50', label: `+${pct}%` }
  if (dir === 'down') return { icon: 'arrow-down', color: 'text-emerald-600', bg: 'bg-emerald-50', label: `-${pct}%` }
  return { icon: 'equals', color: 'text-slate-500', bg: 'bg-slate-100', label: 'Stabil' }
})

// SVG chart geometry (viewBox 100x40)
const CHART_LEFT = 4
const CHART_RIGHT = 96
const CHART_TOP = 4
const CHART_BOTTOM = 34

const getPointX = (idx) => {
  const len = usageValues.value.length
  if (len <= 1) return (CHART_LEFT + CHART_RIGHT) / 2
  return CHART_LEFT + (idx / (len - 1)) * (CHART_RIGHT - CHART_LEFT)
}

const getPointY = (val) => {
  const safeMax = Math.max(maxUsage.value, 1)
  return CHART_BOTTOM - (Number(val) / safeMax) * (CHART_BOTTOM - CHART_TOP)
}

const generateLinePath = computed(() => {
  if (usageValues.value.length === 0) return ''
  let d = `M ${getPointX(0)} ${getPointY(usageValues.value[0])}`
  for (let i = 1; i < usageValues.value.length; i++) {
    const x = getPointX(i)
    const y = getPointY(usageValues.value[i])
    const prevX = getPointX(i - 1)
    const prevY = getPointY(usageValues.value[i - 1])
    const cp1x = prevX + (x - prevX) / 2
    d += ` C ${cp1x} ${prevY}, ${cp1x} ${y}, ${x} ${y}`
  }
  return d
})

const generateAreaPath = computed(() => {
  const line = generateLinePath.value
  if (!line) return ''
  const last = usageValues.value.length - 1
  return `${line} L ${getPointX(last)} ${CHART_BOTTOM} L ${getPointX(0)} ${CHART_BOTTOM} Z`
})

const avgLineY = computed(() => {
  if (avgUsage.value <= 0) return null
  return getPointY(avgUsage.value)
})

/**
 * Geometri batang, gaya crypto/candlestick.
 *
 * Warnanya bukan dekorasi: hijau = pemakaian naik dari bulan sebelumnya,
 * merah = turun, abu-abu = sama. Persis seperti grafik saham, supaya
 * pelanggan bisa langsung membaca tren tanpa memeriksa angka satu per satu.
 *
 * `barWidth` dibatasi 62% dari lebar slot supaya batang bersebelahan
 * tidak menyentuh — kalau penuh, chart terbaca sebagai blok padat dan
 * perbandingan antar bulan hilang.
 */
const barChartData = computed(() => {
  const series = distributionSeries.value
  const len = series.length
  const slotWidth = (CHART_RIGHT - CHART_LEFT) / Math.max(len, 1)
  const barWidth = Math.min(slotWidth * 0.62, 6)

  return series.map((p, idx) => {
    const cx = CHART_LEFT + slotWidth * idx + slotWidth / 2
    const baseY = CHART_BOTTOM
    const topY = getPointY(p.usage_m3)

    // Bulan pertama tidak punya pembanding, jadi netral (abu-abu).
    const prev = idx > 0 ? Number(series[idx - 1].usage_m3 || 0) : null
    const curr = Number(p.usage_m3 || 0)
    let dir = 'same'
    if (prev !== null) {
      if (curr > prev) dir = 'up'
      else if (curr < prev) dir = 'down'
    }

    // Bulan tanpa data (tidak ada meter reading & belum ada tagihan) tidak
    // punya pembanding yang sah, jadi jangan diwarnai seolah-olah ada
    // penurunan pemakaian.
    if (!p.has_reading && !p.has_bill) dir = 'empty'

    const fill =
      dir === 'up' ? 'url(#barGradUp)' : dir === 'down' ? 'url(#barGradDown)' : 'url(#barGradSame)'

    const diff = prev === null ? 0 : curr - prev

    return {
      idx,
      label: p.shortLabel,
      value: p.usage_m3,
      cx,
      dir,
      fill,
      barX: cx - barWidth / 2,
      barWidth,
      barY: Math.min(topY, baseY),
      // Batang tanpa data tetap harus punya tinggi kecil supaya kelihatan
      // sebagai "slot kosong" — bukan hilang, yang bikin sumbu-x terlihat
      // tidak nyambung dengan batang.
      barHeight: Math.max(Math.abs(baseY - topY), 0.6),
      hasData: p.has_reading || p.has_bill,
      isCurrent: p.is_current,
      changeSuffix:
        prev === null || diff === 0
          ? ''
          : ` (${diff > 0 ? '+' : ''}${formatDecimal(diff)} m³ vs bulan sebelumnya)`,
    }
  })
})

const topMonths = computed(() => {
  return [...distributionSeries.value]
    .filter((p) => p.usage_m3 > 0)
    .sort((a, b) => b.usage_m3 - a.usage_m3)
    .slice(0, 3)
})

/**
 * Format angka dengan pembulatan ke rupiah penuh, sama seperti admin.
 * Kolom `monthly_bills` bertipe `decimal`, jadi backend mengirim
 * "15000.00"; tanpa `maximumFractionDigits: 0` tampilnya "15.000,00".
 */
const formatNumber = (num) =>
  new Intl.NumberFormat('id-ID', {
    minimumFractionDigits: 0,
    maximumFractionDigits: 0,
  }).format(Number(num) || 0)

/** Volume meter boleh berdesimal, dibulatkan ke 1 angka desimal. */
const formatMeter = (num) =>
  new Intl.NumberFormat('id-ID', {
    minimumFractionDigits: 0,
    maximumFractionDigits: 1,
  }).format(Number(num) || 0)

const formatDecimal = (num) =>
  new Intl.NumberFormat('id-ID', { maximumFractionDigits: 0, minimumFractionDigits: 0 }).format(
    Number(num) || 0,
  )

const formatDate = (dateString) => {
  if (!dateString) return '-'
  const date = new Date(dateString)
  return new Intl.DateTimeFormat('id-ID', {
    day: 'numeric',
    month: 'long',
    year: 'numeric',
  }).format(date)
}

const fetchDashboardData = async () => {
  try {
    const response = await pelangganService.getDashboardData()
    if (response.success) {
      dashboardData.value = {
        ...response.data,
        distribution: response.data.distribution || dashboardData.value.distribution,
        arrears: response.data.arrears || dashboardData.value.arrears,
      }
      loadError.value = ''
    }
  } catch (error) {
    // Jangan biarkan `catch` kosong. Kalau request gagal, semua angka
    // tunggakan akan tampil sebagai "Rp 0 / LUNAS" karena default state-nya
    // nol — itu menyatakan pelanggan punya utang padahal sistem tidak tahu.
    // Ini misrepresentation, jadi kegagalan harus terlihat.
    loadError.value =
      error.response?.data?.message || 'Gagal memuat data dashboard. Silakan coba lagi.'
  }
}

const isMobile = ref(false)
const checkMobile = () => {
  isMobile.value = window.innerWidth < 1024
}

onMounted(() => {
  checkMobile()
  window.addEventListener('resize', checkMobile)
  fetchDashboardData()
})

onUnmounted(() => {
  window.removeEventListener('resize', checkMobile)
})

const actions = ref([
  {
    title: 'Lapor Gangguan',
    desc: 'Air mati atau pipa bocor?',
    icon: 'headset',
    color: 'text-red-600',
    bg: 'bg-red-50',
    path: '/app/pelanggan/lapor-gangguan?from=dashboard',
  },
  {
    title: 'Riwayat Tagihan',
    desc: 'Lihat pembayaran terdahulu',
    icon: 'history',
    color: 'text-indigo-600',
    bg: 'bg-indigo-50',
    path: '/app/pelanggan/riwayat-tagihan?from=dashboard',
  },
  {
    title: 'Info Pamsimas',
    desc: 'Berita & pengumuman terbaru',
    icon: 'bullhorn',
    color: 'text-amber-600',
    bg: 'bg-amber-50',
  },
])
</script>

<style scoped>
.pelanggan-dashboard {
  animation: fadeIn 1s cubic-bezier(0.16, 1, 0.3, 1);
}

.ease-out-expo {
  transition-timing-function: cubic-bezier(0.19, 1, 0.22, 1);
}

@keyframes fadeIn {
  from {
    opacity: 0;
    transform: translateY(20px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}
</style>
