<template>
  <section id="paket" class="py-16 bg-gradient-to-br from-navy-900 via-black to-navy-950 text-white">
    <div class="max-w-7xl mx-auto px-4 sm:px-6">
      
      <!-- HEADER -->
      <div class="text-center mb-12">
        <h2 class="text-2xl sm:text-3xl font-black leading-tight tracking-tighter md:text-4xl">Pilihan Paket Kursus</h2>
        <p class="text-text-light/80 text-base sm:text-lg font-normal leading-normal max-w-[720px] mx-auto mt-3">
          Temukan paket yang paling sesuai untuk kebutuhan belajar mengemudi Anda.
        </p>
      </div>

      <!-- GRID PAKET -->
      <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 sm:gap-6">
        
        <!-- Paket Reguler -->
        <div class="flex flex-col rounded-xl border border-gray-700 bg-neutral-dark/50 p-4 sm:p-6 transform hover:-translate-y-1 sm:hover:-translate-y-2 transition-transform duration-300">
          <h3 class="text-lg sm:text-xl font-bold text-white">Paket Reguler</h3>
          <p class="text-xs sm:text-sm text-text-light/70 mt-1">Manual & Automatic</p>
          
          <!-- HARGA TERMURAH -->
          <div class="mt-4 mb-3">
            <div class="text-2xl sm:text-3xl font-bold text-primary">Rp 550rb</div>
            <p class="text-xs text-text-light/70 mt-1">Mulai dari</p>
          </div>
          
          <!-- FITUR DENGAN CENTANG -->
          <div class="space-y-2 sm:space-y-3 flex-grow">
            <div class="flex items-center gap-2 text-text-light/90 text-xs sm:text-sm">
              <span class="material-symbols-outlined text-green-400 !text-base sm:!text-lg">check_circle</span>
              <span>Instruktur Profesional</span>
            </div>
            <div class="flex items-center gap-2 text-text-light/90 text-xs sm:text-sm">
              <span class="material-symbols-outlined text-green-400 !text-base sm:!text-lg">check_circle</span>
              <span>Mobil Terawat</span>
            </div>
            <div class="flex items-center gap-2 text-text-light/90 text-xs sm:text-sm">
              <span class="material-symbols-outlined text-green-400 !text-base sm:!text-lg">check_circle</span>
              <span>Bimbingan Full</span>
            </div>
            <div class="flex items-center gap-2 text-text-light/90 text-xs sm:text-sm">
              <span class="material-symbols-outlined text-green-400 !text-base sm:!text-lg">check_circle</span>
              <span>Free Konsultasi</span>
            </div>
          </div>

          <button class="mt-4 sm:mt-6 w-full flex cursor-pointer items-center justify-center rounded-lg h-10 px-4 bg-primary/20 text-primary text-sm font-bold hover:bg-primary/30 transition-colors">
            <span>Lihat Detail</span>
          </button>
        </div>

        <!-- Paket Campuran (POPULER) -->
        <div class="flex flex-col rounded-xl border-2 border-primary bg-neutral-dark p-4 sm:p-6 relative shadow-xl sm:shadow-2xl shadow-primary/20 scale-100 sm:scale-105">
          <div class="absolute top-0 -translate-y-1/2 left-1/2 -translate-x-1/2 bg-primary text-secondary text-xs font-bold px-3 py-1 rounded-full uppercase tracking-wider">
            Populer
          </div>
          
          <h3 class="text-lg sm:text-xl font-bold text-primary">Paket Campuran</h3>
          <p class="text-xs sm:text-sm text-text-light/70 mt-1">Manual & Matic</p>
          
          <!-- HARGA TERMURAH -->
          <div class="mt-4 mb-3">
            <div class="text-2xl sm:text-3xl font-bold text-white">Rp 650rb</div>
            <p class="text-xs text-text-light/70 mt-1">Mulai dari</p>
          </div>
          
          <!-- FITUR DENGAN CENTANG -->
          <div class="space-y-2 sm:space-y-3 flex-grow">
            <div class="flex items-center gap-2 text-text-light/90 text-xs sm:text-sm">
              <span class="material-symbols-outlined text-green-400 !text-base sm:!text-lg">check_circle</span>
              <span>Belajar Manual & Matic</span>
            </div>
            <div class="flex items-center gap-2 text-text-light/90 text-xs sm:text-sm">
              <span class="material-symbols-outlined text-green-400 !text-base sm:!text-lg">check_circle</span>
              <span>Instruktur Berpengalaman</span>
            </div>
            <div class="flex items-center gap-2 text-text-light/90 text-xs sm:text-sm">
              <span class="material-symbols-outlined text-green-400 !text-base sm:!text-lg">check_circle</span>
              <span>Garansi Sampai Bisa</span>
            </div>
            <div class="flex items-center gap-2 text-text-light/90 text-xs sm:text-sm">
              <span class="material-symbols-outlined text-green-400 !text-base sm:!text-lg">check_circle</span>
              <span>Free Konsultasi</span>
            </div>
          </div>

          <button class="mt-4 sm:mt-6 w-full flex cursor-pointer items-center justify-center rounded-lg h-10 px-4 bg-primary text-secondary text-sm font-bold hover:bg-primary/90 transition-colors">
            <span>Pilih Paket Ini</span>
          </button>
        </div>

        <!-- Paket Extra -->
        <div class="flex flex-col rounded-xl border border-gray-700 bg-neutral-dark/50 p-4 sm:p-6 transform hover:-translate-y-1 sm:hover:-translate-y-2 transition-transform duration-300">
          <h3 class="text-lg sm:text-xl font-bold text-white">Paket Extra</h3>
          <p class="text-xs sm:text-sm text-text-light/70 mt-1">Malam / Hari Libur</p>
          
          <!-- HARGA TERMURAH -->
          <div class="mt-4 mb-3">
            <div class="text-2xl sm:text-3xl font-bold text-primary">Rp 650rb</div>
            <p class="text-xs text-text-light/70 mt-1">Mulai dari</p>
          </div>
          
          <!-- FITUR DENGAN CENTANG -->
          <div class="space-y-2 sm:space-y-3 flex-grow">
            <div class="flex items-center gap-2 text-text-light/90 text-xs sm:text-sm">
              <span class="material-symbols-outlined text-green-400 !text-base sm:!text-lg">check_circle</span>
              <span>Waktu Fleksibel</span>
            </div>
            <div class="flex items-center gap-2 text-text-light/90 text-xs sm:text-sm">
              <span class="material-symbols-outlined text-green-400 !text-base sm:!text-lg">check_circle</span>
              <span>Malam & Weekend</span>
            </div>
            <div class="flex items-center gap-2 text-text-light/90 text-xs sm:text-sm">
              <span class="material-symbols-outlined text-green-400 !text-base sm:!text-lg">check_circle</span>
              <span>Instruktur Sabar</span>
            </div>
            <div class="flex items-center gap-2 text-text-light/90 text-xs sm:text-sm">
              <span class="material-symbols-outlined text-green-400 !text-base sm:!text-lg">check_circle</span>
              <span>Bimbingan Penuh</span>
            </div>
          </div>

          <button class="mt-4 sm:mt-6 w-full flex cursor-pointer items-center justify-center rounded-lg h-10 px-4 bg-primary/20 text-primary text-sm font-bold hover:bg-primary/30 transition-colors">
            <span>Lihat Detail</span>
          </button>
        </div>

        <!-- Paket Pelancaran -->
        <div class="flex flex-col rounded-xl border border-gray-700 bg-neutral-dark/50 p-4 sm:p-6 transform hover:-translate-y-1 sm:hover:-translate-y-2 transition-transform duration-300">
          <h3 class="text-lg sm:text-xl font-bold text-white">Paket Pelancaran</h3>
          <p class="text-xs sm:text-sm text-text-light/70 mt-1">Sudah punya SIM</p>
          
          <!-- HARGA TERMURAH -->
          <div class="mt-4 mb-3">
            <div class="text-2xl sm:text-3xl font-bold text-primary">Rp 350rb</div>
            <p class="text-xs text-text-light/70 mt-1">Mulai dari</p>
          </div>
          
          <!-- FITUR DENGAN CENTANG -->
          <div class="space-y-2 sm:space-y-3 flex-grow">
            <div class="flex items-center gap-2 text-text-light/90 text-xs sm:text-sm">
              <span class="material-symbols-outlined text-green-400 !text-base sm:!text-lg">check_circle</span>
              <span>Refresh Skill</span>
            </div>
            <div class="flex items-center gap-2 text-text-light/90 text-xs sm:text-sm">
              <span class="material-symbols-outlined text-green-400 !text-base sm:!text-lg">check_circle</span>
              <span>Perbaikan Teknik</span>
            </div>
            <div class="flex items-center gap-2 text-text-light/90 text-xs sm:text-sm">
              <span class="material-symbols-outlined text-green-400 !text-base sm:!text-lg">check_circle</span>
              <span>Konsultasi Gratis</span>
            </div>
            <div class="flex items-center gap-2 text-text-light/90 text-xs sm:text-sm">
              <span class="material-symbols-outlined text-green-400 !text-base sm:!text-lg">check_circle</span>
              <span>Fleksibel Waktu</span>
            </div>
          </div>

          <button class="mt-4 sm:mt-6 w-full flex cursor-pointer items-center justify-center rounded-lg h-10 px-4 bg-primary/20 text-primary text-sm font-bold hover:bg-primary/30 transition-colors">
            <span>Lihat Detail</span>
          </button>
        </div>

      </div>
    </div>
  </section>
</template>

<script setup>
</script>

<style>
/* Import Material Icons */
@import url('https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@20..48,100..700,0..1,-50..200');

.material-symbols-outlined {
  font-variation-settings:
  'FILL' 0,
  'wght' 400,
  'GRAD' 0,
  'opsz' 24
}

/* Warna Custom */
.navy-900 { background-color: #0f172a; }
.primary { color: #60a5fa; }
.bg-primary { background-color: #60a5fa; }
.bg-primary\/20 { background-color: rgba(96, 165, 250, 0.2); }
.bg-primary\/30 { background-color: rgba(96, 165, 250, 0.3); }
.bg-neutral-dark { background-color: #1e293b; }
.bg-neutral-dark\/50 { background-color: rgba(30, 41, 59, 0.5); }
.secondary { color: #ffffff; }
.text-text-light { color: #94a3b8; }
.border-primary { border-color: #60a5fa; }
.shadow-primary\/20 { box-shadow: 0 25px 50px -12px rgba(96, 165, 250, 0.2); }
</style>