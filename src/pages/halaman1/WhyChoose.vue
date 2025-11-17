<template>
  <section class="relative py-20 bg-white">
    <div class="max-w-7xl mx-auto px-6">
      
      <!-- HEADER SECTION -->
      <div class="text-center mb-16">
        <h2 class="text-gray-900 tracking-tighter text-3xl font-black leading-tight max-w-2xl mx-auto md:text-5xl">
          Kenapa Memilih Krishna?
        </h2>
        <p class="text-gray-600 text-lg font-normal leading-normal max-w-[720px] mx-auto mt-4">
          Kami memberikan lebih dari sekadar kursus mengemudi, kami membangun fondasi keamanan dan kepercayaan diri Anda di jalan.
        </p>
      </div>

      <!-- FEATURES GRID -->
      <div class="grid grid-cols-1 md:grid-cols-3 gap-8 p-0 mt-8">
        
        <!-- FEATURE 1 - INSTRUKTUR -->
        <div class="group flex flex-1 gap-4 rounded-xl border-2 border-primary/30 bg-white p-6 flex-col items-center text-center shadow-lg hover:shadow-2xl hover:border-primary/60 transition-all duration-300">
          <div class="flex h-20 w-20 items-center justify-center rounded-full bg-primary/10 text-primary relative z-10">
            <span class="material-symbols-outlined !text-4xl">verified_user</span>
          </div>
          <div class="flex flex-col gap-1 mt-4">
            <h3 class="text-gray-900 text-lg font-bold leading-tight">Instruktur Profesional</h3>
            <p class="text-gray-600 text-base font-normal leading-normal">
              Instruktur kami bersertifikat, sabar, dan berpengalaman dalam mengajar pengemudi pemula.
            </p>
          </div>
        </div>

        <!-- FEATURE 2 - JADWAL -->
        <div class="group flex flex-1 gap-4 rounded-xl border-2 border-primary bg-white p-6 flex-col items-center text-center shadow-2xl hover:shadow-primary/25 transition-all duration-300 transform hover:scale-105">
          <div class="flex h-20 w-20 items-center justify-center rounded-full bg-primary/10 text-primary relative z-10">
            <span class="material-symbols-outlined !text-4xl">event_available</span>
          </div>
          <div class="flex flex-col gap-1 mt-4">
            <h3 class="text-primary text-lg font-bold leading-tight">Jadwal Fleksibel</h3>
            <p class="text-gray-600 text-base font-normal leading-normal">
              Pilih jadwal latihan yang paling sesuai dengan kesibukan Anda, tersedia di akhir pekan.
            </p>
          </div>
        </div>

        <!-- FEATURE 3 - ARMADA -->
        <div class="group flex flex-1 gap-4 rounded-xl border-2 border-primary/30 bg-white p-6 flex-col items-center text-center shadow-lg hover:shadow-2xl hover:border-primary/60 transition-all duration-300">
          <div class="flex h-20 w-20 items-center justify-center rounded-full bg-primary/10 text-primary relative z-10">
            <span class="material-symbols-outlined !text-4xl">directions_car</span>
          </div>
          <div class="flex flex-col gap-1 mt-4">
            <h3 class="text-gray-900 text-lg font-bold leading-tight">Armada Modern</h3>
            <p class="text-gray-600 text-base font-normal leading-normal">
              Belajar nyaman menggunakan mobil modern yang terawat dan dilengkapi fitur keamanan terbaru.
            </p>
          </div>
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

/* Primary color matching PaketSection */
.primary { color: #60a5fa; }
.bg-primary { background-color: #60a5fa; }
.bg-primary\/10 { background-color: rgba(96, 165, 250, 0.1); }
.border-primary { border-color: #60a5fa; }
.border-primary\/30 { border-color: rgba(96, 165, 250, 0.3); }
.border-primary\/60 { border-color: rgba(96, 165, 250, 0.6); }
.shadow-primary\/25 { box-shadow: 0 25px 50px -12px rgba(96, 165, 250, 0.25); }
</style>