<template>
  <div class="registrasi-root! px-3! sm:px-0!">
    <div class="mb-3! sm:mb-2!">
      <h1 class="text-xl! sm:text-2xl! font-bold! text-slate-800! tracking-tight!">Register Instalasi</h1>
      <p class="text-xs! sm:text-sm! text-slate-500! mt-1!">
        Buat koneksi layanan baru untuk pelanggan yang telah divalidasi.
      </p>
    </div>

    <div class="mb-4! sm:mb-5!">
      <div class="relative!" ref="customerSelectRef">
        <ContentCard variant="bordered" padding="none" rounded="xl" class="overflow-hidden!">
          <div
            class="flex! items-center! gap-2! sm:gap-3! w-full! bg-white! rounded-xl! pl-3! sm:pl-4! py-0! transition-all! duration-200!"
            :class="[
              isCustomerDropdownOpen
                ? 'border-blue-400! ring-2! ring-blue-100! shadow-xl! shadow-blue-500/10!'
                : 'border-slate-100! hover:border-slate-200!',
            ]"
          >
            <font-awesome-icon icon="search" class="w-4! h-4! text-slate-400! shrink-0!" />

            <input
              v-model="customerSearch"
              type="text"
              placeholder="Cari nama / NIK..."
              class="flex-1! min-w-0! bg-transparent! border-none! text-sm! text-slate-700! placeholder-slate-400! focus:outline-none! py-3.5! sm:py-4!"
              @focus="isCustomerDropdownOpen = true"
              @input="isCustomerDropdownOpen = true"
            />

            <div class="flex! items-center! gap-0! shrink-0! h-full!">
              <BaseButton
                v-if="selectedCustomer || customerSearch"
                variant="ghost"
                size="sm"
                class="w-9! h-9! sm:w-10! sm:h-10! p-0! rounded-full! mr-1! sm:mr-2!"
                @click.stop="clearCustomer"
                icon="times"
              />
              <BaseButton
                variant="primary"
                class="h-[48px]! sm:h-[52px]! px-3! sm:px-5! rounded-l-none! rounded-r-xl! border-none! bg-linear-to-r! from-indigo-600! via-blue-600! to-cyan-500! shadow-lg! shadow-indigo-500/20! hover:shadow-indigo-500/40! text-white! ring-offset-0! focus:ring-0!"
                icon="user-plus"
                @click.stop="handleNewCustomerRegistration"
              >
                <span class="hidden! sm:inline-block! text-xs! font-bold!"
                  >Registrasi Pelanggan</span
                >
              </BaseButton>
            </div>
          </div>
        </ContentCard>

        <Transition
          enter-active-class="transition! duration-150! ease-out!"
          enter-from-class="opacity-0! translate-y-1! scale-95!"
          enter-to-class="opacity-100! translate-y-0! scale-100!"
          leave-active-class="transition! duration-100! ease-in!"
          leave-from-class="opacity-100! translate-y-0! scale-100!"
          leave-to-class="opacity-0! translate-y-1! scale-95!"
        >
          <div
            v-if="isCustomerDropdownOpen"
            class="absolute! top-full! left-0! right-0! mt-2! bg-white! border! border-slate-200! rounded-xl! shadow-xl! z-50! overflow-hidden!"
          >
            <div class="max-h-60! overflow-y-auto!">
              <div v-if="filteredCustomerOptions.length === 0" class="py-12! px-6! text-center!">
                <div
                  class="w-16! h-16! bg-slate-50! rounded-full! flex! items-center! justify-center! mx-auto! mb-4! border-2! border-dashed! border-slate-200!"
                >
                  <font-awesome-icon icon="users" class="w-6! h-6! text-slate-300!" />
                </div>
                <p class="text-sm! font-bold! text-slate-600! mb-1!">No customers found</p>
                <p class="text-xs! text-slate-400! leading-relaxed!">
                  We couldn't find any customers matching your search query or filter.
                </p>
              </div>
              <BaseButton
                v-for="customer in filteredCustomerOptions"
                :key="customer.id"
                @click.stop="selectCustomer(customer)"
                variant="ghost"
                class="w-full! px-4! py-1.5! hover:bg-slate-50! transition-all! duration-200! text-left! border-b! border-slate-50! last:border-0! rounded-none! shadow-none!"
              >
                <div class="flex! items-center! gap-3! w-full!">
                  <div
                    class="w-8! h-8! rounded-full! shrink-0! flex! items-center! justify-center! text-white! text-xs! font-extrabold! shadow-sm!"
                    :style="{ backgroundColor: '#3b82f6' }"
                  >
                    {{ customer.name ? customer.name.charAt(0).toUpperCase() : 'C' }}
                  </div>
                  <div class="flex-1! min-w-0!">
                    <div class="text-[13px]! font-bold! text-slate-700! truncate!">
                      {{ customer.name }}
                    </div>
                    <div class="text-[9px]! text-slate-400! font-mono! font-bold! leading-none!">
                      NIK: {{ customer.nik }}
                    </div>
                  </div>
                  <div
                    :class="[
                      'text-[9px]! font-black! px-2! py-0.5! rounded-md! border! shrink-0! uppercase! tracking-wider! flex! items-center! gap-1!',
                      customer.status === 'Aktif'
                        ? 'bg-emerald-50! border-emerald-200! text-emerald-700!'
                        : customer.status === 'Draft'
                          ? 'bg-amber-50! border-amber-200! text-amber-700!'
                          : customer.status === 'Suspended'
                            ? 'bg-orange-50! border-orange-200! text-orange-700!'
                            : customer.status === 'Terminated'
                              ? 'bg-rose-50! border-rose-200! text-rose-700!'
                              : 'bg-slate-50! border-slate-200! text-slate-600!',
                    ]"
                    :title="customer.statusLabel"
                  >
                    <font-awesome-icon
                      :icon="
                        customer.status === 'Aktif'
                          ? 'check-circle'
                          : customer.status === 'Draft'
                            ? 'edit'
                            : customer.status === 'Suspended'
                              ? 'lock'
                              : customer.status === 'Terminated'
                                ? 'ban'
                                : 'circle-xmark'
                      "
                      class="text-[9px]!"
                    />
                    {{
                      customer.status === 'Aktif'
                        ? 'Terdaftar'
                        : customer.status === 'Draft'
                          ? 'Belum Daftar'
                          : customer.status === 'Suspended'
                            ? 'Blokir'
                            : customer.status === 'Terminated'
                              ? 'Cabut'
                              : 'Tidak Aktif'
                    }}
                  </div>
                </div>
              </BaseButton>
            </div>
            <div class="px-4! py-2! border-t! border-slate-100! bg-slate-50/50!">
              <p class="text-[11px]! text-slate-400!">
                {{ filteredCustomerOptions.length }} customer ditemukan
              </p>
            </div>
          </div>
        </Transition>
      </div>
    </div>

    <ContentCard variant="bordered" padding="none" hoverable class="overflow-hidden!">
      <Transition
        enter-active-class="transition! duration-300! ease-out!"
        enter-from-class="opacity-0! -translate-y-4!"
        enter-to-class="opacity-100! translate-y-0!"
      >
        <div
          v-if="selectedCustomer"
          class="bg-linear-to-br! from-indigo-600! via-blue-600! to-cyan-500! p-3! sm:p-5! text-white! flex! flex-wrap! items-center! gap-3! sm:gap-5! border-b! border-white/10!"
        >
          <div
            class="w-10! h-10! sm:w-14! sm:h-14! rounded-full! bg-white/20! backdrop-blur-md! flex! items-center! justify-center! text-white! font-bold! text-base! sm:text-xl! border-2! border-white/40! shrink-0!"
          >
            {{ selectedCustomer.name.charAt(0).toUpperCase() }}
          </div>
          <div class="flex-1! min-w-0!">
            <div class="font-bold! text-white! text-sm! sm:text-lg! truncate! tracking-tight!">
              {{ selectedCustomer.name }}
            </div>
            <div
              class="flex! flex-wrap! items-center! gap-x-2! gap-y-0.5! text-blue-50! text-[10px]! sm:text-xs! mt-0.5! opacity-90!"
            >
              <span class="font-mono! font-bold!">NIK: {{ selectedCustomer.nik }}</span>
              <span class="hidden! sm:inline!">·</span>
              <span class="truncate!">
                Status:
                <span class="font-bold! text-white!">{{ selectedCustomer.statusLabel || '-' }}</span>
              </span>
            </div>
          </div>
          <div
            class="backdrop-blur-md! text-white! border! text-[9px]! sm:text-[10px]! font-bold! px-2! sm:px-3! py-1! sm:py-1.5! rounded-full! shrink-0! uppercase! tracking-wider! bg-white/20! border-white/30! flex! items-center! gap-1!"
            :title="selectedCustomer.statusLabel"
          >
            <font-awesome-icon
              :icon="
                selectedCustomer.status === 'Aktif'
                  ? 'check-circle'
                  : selectedCustomer.status === 'Draft'
                    ? 'edit'
                    : selectedCustomer.status === 'Suspended'
                      ? 'lock'
                      : selectedCustomer.status === 'Terminated'
                        ? 'ban'
                        : 'circle-xmark'
              "
            />
            <span class="whitespace-nowrap!">
              {{
                selectedCustomer.status === 'Aktif'
                  ? 'Terdaftar'
                  : selectedCustomer.status === 'Draft'
                    ? 'Belum Daftar'
                    : selectedCustomer.status === 'Suspended'
                      ? 'Blokir'
                      : selectedCustomer.status === 'Terminated'
                        ? 'Cabut'
                        : 'Tidak Aktif'
              }}
            </span>
          </div>
        </div>
      </Transition>

      <div
        v-if="selectedCustomer && selectedCustomer.isBlocked"
        :class="[
          'flex! flex-col! items-center! justify-center! text-center! py-8! sm:py-12! px-4! sm:px-6! gap-3! border-b!',
          selectedCustomer.status === 'Suspended'
            ? 'bg-orange-50! border-orange-200!'
            : 'bg-rose-50! border-rose-200!',
        ]"
      >
        <div
          :class="[
            'w-16! h-16! rounded-full! flex! items-center! justify-center! shadow-sm!',
            selectedCustomer.status === 'Suspended'
              ? 'bg-orange-100! text-orange-600!'
              : 'bg-rose-100! text-rose-600!',
          ]"
        >
          <font-awesome-icon
            :icon="selectedCustomer.status === 'Suspended' ? 'lock' : 'ban'"
            class="text-2xl!"
          />
        </div>
        <h3
          :class="[
            'text-base! font-bold!',
            selectedCustomer.status === 'Suspended' ? 'text-orange-900!' : 'text-rose-900!',
          ]"
        >
          {{
            selectedCustomer.status === 'Suspended'
              ? 'Pendaftaran Ditolak — Pelanggan Dalam Status Blokir'
              : 'Pendaftaran Ditolak — Pelanggan Sudah Dicabut'
          }}
        </h3>
        <p
          :class="[
            'text-xs! max-w-md! leading-relaxed!',
            selectedCustomer.status === 'Suspended' ? 'text-orange-800!' : 'text-rose-800!',
          ]"
        >
          <template v-if="selectedCustomer.status === 'Suspended'">
            Pelanggan <strong>{{ selectedCustomer.name }}</strong> masih memiliki
            <strong>tagihan tertunggak</strong>. Formulir pendaftaran instalasi baru
            dikunci. Mohon <strong>lunasi tagihan lama</strong> terlebih dahulu, kemudian
            status akan dibuka kembali.
          </template>
          <template v-else>
            Pelanggan <strong>{{ selectedCustomer.name }}</strong> sudah dalam status
            <strong>cabut (terminated)</strong>. Koneksi air telah dicabut permanen dan
            <strong>tidak dapat didaftarkan kembali</strong>.
          </template>
        </p>
        <button
          type="button"
          @click="clearBlockedCustomer"
          :class="[
            'mt-2! px-4! py-2! rounded-lg! text-xs! font-bold! uppercase! tracking-wider! transition-all!',
            selectedCustomer.status === 'Suspended'
              ? 'bg-orange-500! hover:bg-orange-600! text-white!'
              : 'bg-rose-500! hover:bg-rose-600! text-white!',
          ]"
        >
          Pilih Pelanggan Lain
        </button>
      </div>

      <div
        v-else
        class="grid! grid-cols-1! lg:grid-cols-2! gap-2! divide-y! lg:divide-y-0! lg:divide-x! divide-slate-100!"
      >
        <div class="p-4! sm:p-5! lg:p-6! bg-gradient-to-br! from-slate-50/60! to-white!">
          <div class="flex! items-center! gap-2.5! mb-4! sm:mb-5!">
            <div class="w-8! h-8! bg-blue-100! rounded-lg! flex! items-center! justify-center!">
              <font-awesome-icon icon="file-alt" class="w-4! h-4! text-blue-600!" />
            </div>
            <h2 class="text-base! font-bold! text-slate-800!">Detail Layanan</h2>
          </div>

          <AppDatePicker
            v-model="form.tanggalOrder"
            label="Tanggal Order"
            placeholder="Pilih tanggal order"
          />

          <div class="space-y-4!">
            <div class="grid! grid-cols-1! sm:grid-cols-2! gap-3!">
              <SelectSearch
                v-model="form.user_id"
                :options="caterUsersOptionsFormatted"
                label="Nama Cater"
                placeholder="Pilih Petugas Cater"
                searchable
              />

              <SelectSearch
                v-model="form.package_id"
                :options="packagesOptionsFormatted"
                label="Paket/Kelas"
                placeholder="Pilih Paket"
                searchable
              />
            </div>

            <Transition
              enter-active-class="transition! duration-200! ease-out!"
              enter-from-class="opacity-0! -translate-y-2!"
              enter-to-class="opacity-100! translate-y-0!"
            >
              <div v-if="form.package_id && selectedPackageDetails" class="space-y-4!">
                <div
                  v-if="
                    selectedPackageDetails &&
                    (selectedPackageDetails.water_tariff_blocks ||
                      selectedPackageDetails.tariffBlocks)
                  "
                  class="bg-white! border! border-slate-200! rounded-xl! divide-y! divide-slate-100! shadow-xs! overflow-hidden!"
                >
                  <div
                    class="px-2.5! sm:px-3.5! py-2! grid! grid-cols-3! gap-2! sm:gap-4! bg-slate-50/70! text-[9px]! sm:text-[10px]! font-bold! text-slate-400! uppercase! tracking-wider!"
                  >
                    <div>Blok</div>
                    <div class="text-center!">Volume</div>
                    <div class="text-right!">Harga / m³</div>
                  </div>

                  <div
                    v-for="(block, index) in selectedPackageDetails.water_tariff_blocks ||
                    selectedPackageDetails.tariffBlocks"
                    :key="block.id || index"
                    class="p-2.5! sm:p-3.5! grid! grid-cols-3! items-center! gap-2! sm:gap-4! hover:bg-slate-50/50! transition-colors!"
                  >
                    <div class="flex! items-center! gap-3!">
                      <div
                        class="w-6! h-6! bg-blue-50! text-blue-600! rounded-full! flex! items-center! justify-center! text-[10px]! font-black!"
                      >
                        {{ index + 1 }}
                      </div>
                      <div
                        class="text-[11px]! font-bold! text-slate-700! uppercase! tracking-tight!"
                      >
                        Blok {{ index + 1 }}
                      </div>
                    </div>

                    <div class="text-center! text-xs! font-bold! text-slate-600!">
                      {{ parseFloat(block.usage_min_m3).toFixed(2) }} -
                      {{ block.usage_max_m3 ? parseFloat(block.usage_max_m3).toFixed(2) : '∞' }}
                      <span class="text-[10px]! text-slate-400! font-normal!">m³</span>
                    </div>

                    <div class="text-right! text-xs! font-extrabold! text-blue-600!">
                      Rp {{ formatRupiah(block.price_per_m3) }}
                    </div>
                  </div>
                </div>

                <div class="space-y-3!">
                  <div class="grid! grid-cols-1! sm:grid-cols-2! gap-3!">
                    <div
                      class="bg-slate-50! border! border-slate-200! rounded-xl! px-4! py-2.5! flex! justify-between! items-center! h-[46px]!"
                    >
                      <div
                        class="text-[11px]! font-semibold! text-slate-500! uppercase! tracking-wider!"
                      >
                        Abodemen
                      </div>
                      <div class="text-sm! font-extrabold! text-emerald-600!">
                        Rp {{ formatRupiah(selectedPackageDetails.monthly_abodemen) }}
                      </div>
                    </div>

                    <div
                      class="bg-slate-50! border! border-slate-200! rounded-xl! px-4! py-2.5! flex! justify-between! items-center! h-[46px]!"
                    >
                      <div
                        class="text-[11px]! font-semibold! text-slate-500! uppercase! tracking-wider!"
                      >
                        Denda
                      </div>
                      <div class="text-sm! font-extrabold! text-rose-600!">
                        Rp {{ formatRupiah(selectedPackageDetails.late_penalty) }}
                      </div>
                    </div>
                  </div>

                  <div class="grid! grid-cols-1! gap-3!">
                    <div>
                      <div class="flex! items-center! justify-between! mb-1.5!">
                        <label class="text-xs! font-semibold! text-slate-500!">
                          Nominal Pasang Baru
                        </label>
                        <span
                          v-if="isMustFullyPaid"
                          class="text-[9px]! font-bold! uppercase! tracking-wider! px-1.5! py-0.5! rounded! bg-rose-100! text-rose-700! flex! items-center! gap-1!"
                          title="Wajib Lunas: nominal dikunci sesuai paket"
                        >
                          <font-awesome-icon icon="lock" />
                          Wajib Lunas
                        </span>
                        <span
                          v-else
                          class="text-[9px]! font-bold! uppercase! tracking-wider! px-1.5! py-0.5! rounded! bg-emerald-100! text-emerald-700! flex! items-center! gap-1!"
                          title="Boleh dicicil: nominal bisa disesuaikan"
                        >
                          <font-awesome-icon icon="pen-to-square" />
                          Bisa Dicicil
                        </span>
                      </div>
                      <div
                        class="flex! items-center! rounded-xl! border! transition-all!"
                        :class="
                          isMustFullyPaid
                            ? 'bg-slate-50! border-slate-200!'
                            : 'bg-white! border-slate-200! focus-within:border-blue-400! focus-within:ring-2! focus-within:ring-blue-100!'
                        "
                      >
                        <span
                          class="pl-3! text-xs! font-bold! text-slate-400! uppercase! tracking-wider!"
                          >Rp</span
                        >
                        <input
                          v-model="nominalInput"
                          @blur="onNominalBlur"
                          :readonly="isMustFullyPaid"
                          :disabled="isMustFullyPaid"
                          type="text"
                          inputmode="numeric"
                          placeholder="0,00"
                          class="w-full! px-3! py-2.5! text-sm! font-extrabold! text-right! border-none! focus:outline-none! bg-transparent!"
                          :class="isMustFullyPaid ? 'text-slate-500! cursor-not-allowed!' : 'text-blue-600!'"
                        />
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            </Transition>
          </div>
        </div>

        <div class="p-4! sm:p-5! lg:p-6! bg-gradient-to-br! from-cyan-50/40! to-white!">
          <div class="flex! items-center! gap-2.5! mb-4! sm:mb-5!">
            <div class="w-8! h-8! bg-cyan-100! rounded-lg! flex! items-center! justify-center!">
              <font-awesome-icon icon="map-marker-alt" class="w-4! h-4! text-cyan-600!" />
            </div>
            <h2 class="text-base! font-bold! text-slate-800!">Lokasi Penyebaran</h2>
          </div>

          <SelectSearch
            v-model="form.village_id"
            :options="villageOptionsFormatted"
            label="Nama Desa"
            placeholder="Pilih Desa"
            searchable
            @change="handleVillageChange"
          />

          <BaseInput
            v-model="form.jalan"
            label="Jalan"
            disabled
            placeholder="Alamat Penyebaran"
            class="bg-slate-50!"
          />

          <div>
            <div class="flex! items-center! justify-between! mb-1.5!">
              <label class="text-xs! font-semibold! text-slate-500!">
                Koordinat <span class="text-rose-500!">*</span>
              </label>
              <button
                type="button"
                @click="getCurrentLocation"
                class="text-[10px]! font-bold! text-cyan-600! hover:text-cyan-700! flex! items-center! gap-1! uppercase! tracking-wider!"
                title="Gunakan lokasi saat ini"
              >
                <font-awesome-icon icon="location-dot" />
                Lokasi Saya
              </button>
            </div>
            <div class="grid! grid-cols-1! sm:grid-cols-2! gap-2!">
              <div
                class="flex! items-center! rounded-xl! border! border-slate-200! bg-white! transition-all! focus-within:border-blue-400! focus-within:ring-2! focus-within:ring-blue-100!"
              >
                <span
                  class="pl-3! text-xs! font-bold! text-slate-400! uppercase! tracking-wider! shrink-0!"
                  >Lat</span
                >
                <input
                  v-model="form.lat"
                  @input="onLatLngInput"
                  type="text"
                  inputmode="decimal"
                  placeholder="contoh: -7.4591"
                  class="w-full! min-w-0! px-3! py-2.5! text-xs! sm:text-sm! font-mono! text-slate-700! border-none! focus:outline-none! bg-transparent!"
                />
              </div>
              <div
                class="flex! items-center! rounded-xl! border! border-slate-200! bg-white! transition-all! focus-within:border-blue-400! focus-within:ring-2! focus-within:ring-blue-100!"
              >
                <span
                  class="pl-3! text-xs! font-bold! text-slate-400! uppercase! tracking-wider! shrink-0!"
                  >Lng</span
                >
                <input
                  v-model="form.lng"
                  @input="onLatLngInput"
                  type="text"
                  inputmode="decimal"
                  placeholder="contoh: 110.2589"
                  class="w-full! min-w-0! px-3! py-2.5! text-xs! sm:text-sm! font-mono! text-slate-700! border-none! focus:outline-none! bg-transparent!"
                />
              </div>
            </div>
            <p class="text-[10px]! text-slate-400! mt-1.5!">
              <font-awesome-icon icon="info-circle" class="mr-1!" />
              Masukkan nilai desimal, contoh: -7.4591, 110.2589
            </p>
          </div>

          <div>
            <label class="block! text-xs! font-semibold! text-slate-500! mb-1.5!"
              >Preview Lokasi</label
            >
            <div
              class="relative! w-full! h-40! sm:h-44! bg-linear-to-br! from-slate-100! to-slate-200! rounded-xl! overflow-hidden! border! border-slate-200!"
            >
              <div
                class="absolute! inset-0!"
                style="
                  background-image:
                    linear-gradient(rgba(148, 163, 184, 0.15) 1px, transparent 1px),
                    linear-gradient(90deg, rgba(148, 163, 184, 0.15) 1px, transparent 1px);
                  background-size: 20px 20px;
                "
              ></div>

              <div
                class="absolute! inset-0! flex! items-center! justify-center! pointer-events-none! z-0!"
              >
                <div v-if="!hasCoordinates" class="text-center! px-3!">
                  <div
                    class="w-10! h-10! sm:w-12! sm:h-12! rounded-full! bg-white/80! border-2! border-dashed! border-slate-300! flex! items-center! justify-center! mx-auto! mb-2!"
                  >
                    <font-awesome-icon icon="map-marker-alt" class="w-4! h-4! sm:w-5! sm:h-5! text-slate-400!" />
                  </div>
                  <p class="text-[11px]! sm:text-xs! text-slate-400! font-medium!">
                    Masukkan koordinat untuk preview
                  </p>
                </div>

                <div v-else class="relative!">
                  <div
                    class="w-8! h-8! rounded-full! bg-blue-600! border-3! border-white! shadow-lg! flex! items-center! justify-center! animate-bounce!"
                  >
                    <font-awesome-icon icon="map-marker-alt" class="w-4! h-4! text-white!" />
                  </div>
                  <div
                    class="absolute! -inset-2! rounded-full! bg-blue-400/20! animate-ping!"
                  ></div>
                </div>
              </div>

              <div class="hidden! sm:block! absolute! bottom-3! left-1/2! -translate-x-1/2! z-30!">
                <button
                  type="button"
                  @click="openMapPreview"
                  class="flex! items-center! justify-center! px-4! py-2! bg-white/90! hover:bg-white! text-slate-700! text-xs! font-bold! rounded-lg! border! border-slate-200! shadow-sm! hover:shadow-md! transition-all! cursor-pointer!"
                >
                  <font-awesome-icon icon="eye" class="mr-2! text-blue-500!" />
                  PREVIEW LOCATION
                </button>
              </div>
            </div>
            <button
              type="button"
              @click="openMapPreview"
              class="sm:hidden! mt-2! w-full! flex! items-center! justify-center! px-4! py-2! bg-white! text-slate-700! text-xs! font-bold! rounded-lg! border! border-slate-200! shadow-sm! active:bg-slate-50! transition-all! cursor-pointer!"
            >
              <font-awesome-icon icon="eye" class="mr-2! text-blue-500!" />
              PREVIEW LOCATION
            </button>
          </div>
        </div>
      </div>
    </ContentCard>

    <div class="mt-6! sm:mt-10! flex! flex-col! sm:flex-row! items-stretch! sm:items-center! justify-between! gap-3! sm:gap-5!">
      <div class="flex! items-start! sm:items-center! gap-2! text-xs! text-slate-400! opacity-80! order-2! sm:order-1!">
        <div
          class="w-4! h-4! rounded-full! bg-blue-50! flex! items-center! justify-center! shrink-0! mt-0.5! sm:mt-0!"
        >
          <font-awesome-icon icon="info-circle" class="w-2.5! h-2.5! text-blue-500!" />
        </div>
        <span class="leading-relaxed!">
          Pastikan semua data terisi dengan benar sebelum menyimpan transaksi.
        </span>
      </div>

      <div class="w-full! sm:w-auto! order-1! sm:order-2!">
        <BaseButton
          variant="primary-gradient"
          class="w-full! sm:w-auto! px-6! sm:px-10! py-3! sm:py-3.5! rounded-xl! shadow-lg! text-sm! font-bold! uppercase! tracking-wide!"
          @click="handleSubmit"
          :disabled="!isFormValid"
        >
          Daftar & Simpan
          <font-awesome-icon icon="check-circle" class="ml-2!" />
        </BaseButton>
      </div>
    </div>

    <div
      v-if="isCustomerDropdownOpen"
      class="fixed! inset-0! z-40!"
      @click="isCustomerDropdownOpen = false"
    ></div>
  </div>
</template>

<script setup>
import api from '@/utils/axios'
import { ref, computed, onMounted, onUnmounted, watch } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import ContentCard from '@/presentations/components/ui/ContentCard.vue'
import BaseButton from '@/presentations/components/ui/BaseButton.vue'
import BaseInput from '@/presentations/components/ui/BaseInput.vue'
import SelectSearch from '@/presentations/components/SelectSearch.vue'
import AppDatePicker from '@/presentations/components/AppDatePicker.vue'
import ticketService from '@/services/ticket.service.js'
import sopService from '@/services/sop.service.js'
import Swal from 'sweetalert2'

const isCustomerDropdownOpen = ref(false)
const customerSearch = ref('')
const selectedCustomer = ref(null)
const villageOptions = ref([])
const router = useRouter()
const route = useRoute()

// true = wajib lunas (locked), false = boleh dicicil (editable)
const isMustFullyPaid = ref(true)

const form = ref({
  tanggalOrder: new Date(),
  user_id: '',
  package_id: '',
  nominal: 0,
  village_id: '',
  namaDesa: '',
  jalan: '',
  lat: '',
  lng: '',
})

const customerOptions = ref([])
const packages = ref([])
const caterUsers = ref([])

// 1. FETCH CUSTOMERS
const fetchCustomers = async () => {
  try {
    const res = await ticketService.getTickets()
    const list = res.data?.data || res.data || []
    const grouped = {}

    list.forEach((item) => {
      const nik = item.nik
      if (!grouped[nik]) {
        grouped[nik] = {
          nik: nik,
          name: item.applicant_name || '',
          phone: item.phone || '',
          gender: item.gender || '',
          birth_place: item.birth_place || '',
          birth_date: item.birth_date || '',
          tickets: [],
        }
      }

      if (!grouped[nik].phone && item.phone) grouped[nik].phone = item.phone
      if (!grouped[nik].gender && item.gender) grouped[nik].gender = item.gender
      if (!grouped[nik].birth_place && item.birth_place) grouped[nik].birth_place = item.birth_place
      if (!grouped[nik].birth_date && item.birth_date) grouped[nik].birth_date = item.birth_date

      grouped[nik].tickets.push({
        id: item.id,
        village_id: item.village_id || '',
        village_name: item.village?.village_name || '',
        village_address: item.village?.address || '',
        lat: item.lat || '',
        lng: item.lng || '',
        package_id: item.package_id || '',
        package: item.package || null,
        user_id: item.user_id || '',
        order_date: item.order_date || '',
        nominal: item.package?.installation_fee || 0,
        status: item.status || 'draft',
      })
    })

    Object.values(grouped).forEach((c) => {
      const activeStatuses = ['pending', 'surveyed', 'unpaid', 'processing', 'completed']
      const hasActive = c.tickets.some((t) => activeStatuses.includes(t.status))
      const hasDraft = c.tickets.some((t) => t.status === 'draft')
      const hasSuspended = c.tickets.some((t) => t.status === 'suspended')
      const hasTerminated = c.tickets.some((t) => t.status === 'terminated')
      const latestTicket = [...c.tickets].sort((a, b) => (b.id || 0) - (a.id || 0))[0]

      if (hasTerminated) {
        c.status = 'Terminated'
        c.statusLabel = 'cabut'
        c.isBlocked = true
      } else if (hasSuspended) {
        c.status = 'Suspended'
        c.statusLabel = 'blokir'
        c.isBlocked = true
      } else if (hasActive) {
        c.status = 'Aktif'
        c.statusLabel = latestTicket?.status || 'aktif'
      } else if (hasDraft) {
        c.status = 'Draft'
        c.statusLabel = 'draft'
      } else {
        c.status = 'Tidak Aktif'
        c.statusLabel = 'tidak aktif'
      }
    })

    customerOptions.value = Object.values(grouped)
  } catch (err) {
    console.error('Gagal ambil customer:', err)
  }
}

// 2. FETCH DATA PAKET
const fetchPackages = async () => {
  try {
    const res = await api.get('/installation-packages')
    packages.value = res.data?.data || res.data || []
  } catch (err) {
    console.error('Gagal mengambil paket data:', err)
  }
}

// 3. FETCH USERS
const fetchCaterUsers = async () => {
  try {
    const res = await api.get('/users?role=teknisi')
    caterUsers.value = res.data?.data || res.data || []
  } catch (err) {
    console.error('Gagal memuat pengguna cater:', err)
    caterUsers.value = []
  }
}

// 4. FETCH VILLAGES
const villageOptionsFormatted = computed(() => {
  return villageOptions.value.map((village) => ({
    id: village.id,
    text: village.village_name,
  }))
})

const packagesOptionsFormatted = computed(() => {
  return packages.value.map((pkg) => ({
    id: pkg.id,
    text: pkg.name,
  }))
})

const caterUsersOptionsFormatted = computed(() => {
  return caterUsers.value.map((user) => ({
    id: user.id,
    text: user.name,
  }))
})

const fetchVillages = async () => {
  try {
    const res = await api.get('/villages')
    villageOptions.value = res.data?.data || res.data || []
  } catch (err) {
    console.error('Gagal mengambil data desa dari database:', err)
  }
}

const fetchPaymentMode = async () => {
  try {
    const res = await api.get('/settings/payment-mode', {
      params: { _t: Date.now() },
      headers: { 'Cache-Control': 'no-cache', Pragma: 'no-cache' },
    })
    if (res.data?.success) {
      const raw = res.data.data?.status_pembayaran
      isMustFullyPaid.value = raw === true || raw === 1 || raw === '1'
      console.log('[Registrasi] payment mode loaded:', {
        status_pembayaran: raw,
        type: typeof raw,
        must_be_fully_paid: isMustFullyPaid.value,
      })
    }
  } catch (err) {
    console.error('Gagal membaca payment mode dari settings, default bisa dicicil.', err)
    isMustFullyPaid.value = false
  }
}

const fetchPasangBaruMode = async () => {
  try {
    const res = await sopService.getAll()
    const pb = res?.data?.pasangBaru?.statusPembayaran
    if (pb === undefined || pb === null) return
    isMustFullyPaid.value = pb === true || pb === 1 || pb === '1'
  } catch (err) {
    console.error('Gagal membaca mode pasang baru dari SOP:', err)
  }
}

// FILTER SEARCH CUSTOMER
const filteredCustomerOptions = computed(() => {
  if (!customerSearch.value) return customerOptions.value
  const q = customerSearch.value.toLowerCase()
  return customerOptions.value.filter((c) => {
    return c.name.toLowerCase().includes(q) || c.nik.toLowerCase().includes(q)
  })
})

// VALIDASI TAMPILAN MAP PREVIEW
const hasCoordinates = computed(() => {
  const lat = parseFloat(form.value.lat)
  const lng = parseFloat(form.value.lng)
  return !isNaN(lat) && !isNaN(lng) && !(lat === 0 && lng === 0)
})

// KETIKA PILIH CUSTOMER
const selectCustomer = (customer) => {
  selectedCustomer.value = customer
  customerSearch.value = customer.name
  isCustomerDropdownOpen.value = false

  if (customer.isBlocked) {
    return
  }

  const lastTicket = customer.tickets[customer.tickets.length - 1]
  if (!lastTicket) return

  form.value.village_id = lastTicket.village_id
  form.value.namaDesa = lastTicket.village_name
  form.value.jalan = lastTicket.village_address

  if (lastTicket.lat && lastTicket.lng && !(lastTicket.lat == 0 && lastTicket.lng == 0)) {
    form.value.lat = lastTicket.lat
    form.value.lng = lastTicket.lng
  } else {
    form.value.lat = ''
    form.value.lng = ''
  }

  form.value.package_id = ''

  const userExist = caterUsers.value.find((u) => u.id == lastTicket.user_id)

  form.value.user_id = userExist ? lastTicket.user_id : ''
  form.value.tanggalOrder = lastTicket.order_date ? new Date(lastTicket.order_date) : new Date()

  if (lastTicket.package && lastTicket.package.tariff_blocks) {
    const existingPkg = packages.value.find((p) => p.id == lastTicket.package_id)
    if (existingPkg) {
      existingPkg.tariffBlocks = lastTicket.package.tariff_blocks
    }
  }
}

const clearCustomer = () => {
  selectedCustomer.value = null
  customerSearch.value = ''
  form.value.tanggalOrder = new Date()
  form.value.user_id = ''
  form.value.package_id = ''
  form.value.nominal = 0
  form.value.village_id = ''
  form.value.namaDesa = ''
  form.value.jalan = ''
  form.value.lat = ''
  form.value.lng = ''
}

const clearBlockedCustomer = () => {
  clearCustomer()
}

// WATCHER PAKET
watch(
  () => form.value.package_id,
  (newPackageId) => {
    const selectedPkg = packages.value.find((p) => p.id == newPackageId)
    if (selectedPkg) {
      form.value.nominal = selectedPkg.installation_fee
      if (
        (!selectedPkg.tariffBlocks || selectedPkg.tariffBlocks.length === 0) &&
        selectedCustomer.value
      ) {
        const matchedTicket = selectedCustomer.value.tickets.find(
          (t) => t.package_id == newPackageId,
        )
        if (matchedTicket && matchedTicket.package && matchedTicket.package.tariff_blocks) {
          selectedPkg.tariffBlocks = matchedTicket.package.tariff_blocks
        }
      }
    } else {
      form.value.nominal = 0
    }
  },
)

const handleVillageChange = () => {
  const selectedVillage = villageOptions.value.find((v) => v.id == form.value.village_id)
  if (selectedVillage) {
    form.value.namaDesa = selectedVillage.village_name
    form.value.jalan = selectedVillage.address
  } else {
    form.value.namaDesa = ''
    form.value.jalan = ''
  }
}

const getCurrentLocation = () => {
  if (navigator.geolocation) {
    navigator.geolocation.getCurrentPosition(
      (pos) => {
        form.value.lat = pos.coords.latitude.toString()
        form.value.lng = pos.coords.longitude.toString()
      },
      () => {
        form.value.lat = '-7.459139746409505'
        form.value.lng = '110.25898006208358'
      },
    )
  }
}

const openMapPreview = () => {
  const lat = parseFloat(form.value.lat)
  const lng = parseFloat(form.value.lng)
  if (isNaN(lat) || isNaN(lng) || lat === 0 || lng === 0) {
    alert('Koordinat belum diisi atau tidak valid')
    return
  }
  window.open(`https://www.google.com/maps/search/?api=1&query=${lat},${lng}`, '_blank')
}

const isFormValid = computed(() => {
  return (
    selectedCustomer.value !== null &&
    !selectedCustomer.value.isBlocked &&
    form.value.package_id !== '' &&
    form.value.user_id !== '' &&
    form.value.tanggalOrder !== null &&
    form.value.tanggalOrder !== '' &&
    hasCoordinates.value
  )
})

const handleSubmit = async () => {
  if (!isFormValid.value) {
    if (selectedCustomer.value?.isBlocked) {
      Swal.fire({
        icon: 'warning',
        title: 'Tidak Dapat Mendaftar',
        text:
          selectedCustomer.value.status === 'Suspended'
            ? 'Pelanggan masih dalam status blokir. Mohon lunasi tagihan tertunggak terlebih dahulu.'
            : 'Pelanggan sudah dalam status cabut dan tidak dapat didaftarkan kembali.',
      })
    }
    return
  }
  try {
    const targetDate = new Date(form.value.tanggalOrder)
    const yyyy = targetDate.getFullYear()
    const mm = String(targetDate.getMonth() + 1).padStart(2, '0')
    const dd = String(targetDate.getDate()).padStart(2, '0')
    const formattedDate = `${yyyy}-${mm}-${dd}`

    const payload = {
      package_id: form.value.package_id,
      user_id: form.value.user_id,
      order_date: formattedDate,
      village_id: form.value.village_id,
      lat: parseFloat(form.value.lat),
      lng: parseFloat(form.value.lng),
      nominal: Number(String(form.value.nominal ?? 0).replace(',', '.')) || 0,
    }

    const lastTicket = selectedCustomer.value.tickets[selectedCustomer.value.tickets.length - 1]
    const response = await api.put(`/installation-tickets/${lastTicket.id}/register`, payload)

    const ticketId = response.data?.data?.id || lastTicket.id

    let customerCode = response.data?.data?.customer?.[0]?.customer_code
    if (!customerCode) {
      try {
        const detail = await api.get(`/installation-tickets/${ticketId}`)
        customerCode = detail.data?.data?.customer?.[0]?.customer_code
      } catch {
        // ignore, fallback handled below
      }
    }
    if (!customerCode) {
      customerCode = `#INS-${ticketId.toString().padStart(4, '0')}`
    }

    const result = await Swal.fire({
      title: 'Berhasil!',
      text: 'Instalasi berhasil didaftarkan. Status berubah menjadi pending.',
      icon: 'success',
      showCancelButton: true,
      confirmButtonColor: '#3b82f6',
      cancelButtonColor: '#64748b',
      confirmButtonText: 'Lihat Detail',
      cancelButtonText: 'Tambah Registrasi Baru',
      reverseButtons: true,
    })

    if (result.isConfirmed) {
      router.push({
        name: 'Detail Permohonan',
        params: { id: encodeURIComponent(customerCode) },
      })
    } else if (result.dismiss === Swal.DismissReason.cancel) {
      clearCustomer()
      router.push('/app/instalasi/register').catch(() => {
        selectedCustomer.value = null
        customerSearch.value = ''
        form.value.tanggalOrder = new Date()
        form.value.user_id = ''
        form.value.package_id = ''
        form.value.nominal = 0
        form.value.village_id = ''
        form.value.namaDesa = ''
        form.value.jalan = ''
        form.value.lat = ''
        form.value.lng = ''
      })
    }
  } catch (err) {
    console.error('Gagal mengirim registrasi instalasi:', err.response?.data || err)

    const errorMessage =
      err.response?.data?.message || 'Gagal mendaftarkan instalasi. Silakan coba lagi.'

    await Swal.fire({
      title: 'Gagal!',
      text: errorMessage,
      icon: 'error',
      confirmButtonColor: '#ef4444',
      confirmButtonText: 'OK',
    })
  }
}

const handleNewCustomerRegistration = () => {
  router.push('/app/instalasi/register')
}

const selectedPackageDetails = computed(() => {
  if (!form.value.package_id) return null
  return packages.value.find((p) => p.id == form.value.package_id) || null
})

const formatRupiah = (angka) => {
  if (!angka) return '0'
  const val = Math.floor(parseFloat(angka))
  return val.toString().replace(/\B(?=(\d{3})+(?!\d))/g, '.')
}

const formatRupiahDecimal = (angka) => {
  if (angka === null || angka === undefined || angka === '') return '0,00'
  const num = parseFloat(angka)
  if (isNaN(num)) return '0,00'
  const fixed = num.toFixed(2)
  const [intPart, decPart] = fixed.split('.')
  const intFormatted = intPart.replace(/\B(?=(\d{3})+(?!\d))/g, '.')
  return `${intFormatted},${decPart}`
}

const nominalInput = ref('')

watch(
  () => form.value.nominal,
  (val) => {
    nominalInput.value = formatRupiahDecimal(val)
  },
  { immediate: true },
)

const onNominalBlur = () => {
  if (isMustFullyPaid.value) {
    nominalInput.value = formatRupiahDecimal(form.value.nominal)
    return
  }
  const digits = String(nominalInput.value ?? '').replace(/[^\d]/g, '')
  form.value.nominal = digits === '' ? 0 : parseInt(digits, 10)
  nominalInput.value = formatRupiahDecimal(form.value.nominal)
}

const handleKeydown = (e) => {
  if (e.key === 'Escape') isCustomerDropdownOpen.value = false
}

onMounted(() => {
  fetchCustomers()
  fetchPackages()
  fetchCaterUsers()
  fetchVillages()
  fetchPaymentMode()
  fetchPasangBaruMode()
  document.addEventListener('keydown', handleKeydown)
})

watch(
  () => route.fullPath,
  () => {
    fetchPaymentMode()
    fetchPasangBaruMode()
  },
)

onUnmounted(() => {
  document.removeEventListener('keydown', handleKeydown)
})
</script>
