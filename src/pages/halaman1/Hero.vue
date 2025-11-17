<template>
  <section id="beranda" class="relative w-full h-screen overflow-hidden bg-gradient-to-br from-navy-900 via-black to-navy-950">

    <!-- VIDEO BACKGROUND -->
    <video
      autoplay
      loop
      muted
      playsinline
      class="absolute inset-0 w-full h-full object-cover opacity-40">
      <source src="@/assets/beranda.mp4" type="video/mp4">
    </video>

    <!-- GRADIENT OVERLAY -->
    <div class="absolute inset-0 bg-gradient-to-br from-navy-900/80 via-black/90 to-navy-950/70"></div>
    <div class="absolute inset-0 bg-gradient-to-t from-black/60 via-transparent to-black/30"></div>

    <!-- CONTENT -->
    <div class="relative z-10 h-full flex items-center justify-center text-center">
      <div class="max-w-4xl px-6">
        
        <!-- BADGE -->
        <div class="inline-flex items-center gap-2 bg-white/10 backdrop-blur-md border border-white/20 rounded-full px-4 py-2 mb-8">
          <div class="w-2 h-2 bg-white rounded-full animate-pulse"></div>
          <span class="text-sm text-white font-medium">Kursus Mengemudi Premium</span>
        </div>

        <!-- MAIN HEADLINE - DIPERBESAR -->
        <h1 class="text-6xl md:text-8xl lg:text-9xl font-black text-white mb-6 leading-none tracking-tighter bg-gradient-to-r from-white to-primary/80 bg-clip-text text-transparent">
          KRISHNA
        </h1>

        <!-- SUB HEADLINE -->
        <div class="mb-12">
          <p class="text-xl md:text-2xl text-gray-200 font-light">
            Mengemudi dengan <span class="text-primary font-normal">Percaya Diri</span>
          </p>
        </div>

        <!-- CTA BUTTON - BIRU YANG TERLIHAT -->
        <a href="#daftar" class="inline-block bg-primary/40 backdrop-blur-md border border-primary/50 text-white px-12 py-4 rounded-full text-lg font-semibold transition-all duration-300 transform hover:scale-105 hover:bg-primary/50 hover:border-primary/70 shadow-2xl shadow-primary/30 cursor-pointer">
          Daftar Sekarang - Mulai dari Rp 350.000
        </a>

        <!-- FITUR DENGAN CENTANG -->
        <div class="flex flex-wrap justify-center gap-6 mt-8">
          <div class="flex items-center gap-2 text-white/80 text-sm">
            <span class="material-symbols-outlined text-green-400 !text-lg">check_circle</span>
            <span>Instruktur Profesional</span>
          </div>
          <div class="flex items-center gap-2 text-white/80 text-sm">
            <span class="material-symbols-outlined text-green-400 !text-lg">check_circle</span>
            <span>Mobil Terawat</span>
          </div>
          <div class="flex items-center gap-2 text-white/80 text-sm">
            <span class="material-symbols-outlined text-green-400 !text-lg">check_circle</span>
            <span>Garansi Sampai Bisa</span>
          </div>
        </div>

      </div>
    </div>

    <!-- SCROLL INDICATOR -->
    <a href="#paket" class="absolute bottom-8 left-1/2 transform -translate-x-1/2 animate-bounce cursor-pointer">
      <div class="w-6 h-10 border-2 border-primary/50 rounded-full flex justify-center">
        <div class="w-1 h-3 bg-primary rounded-full mt-2"></div>
      </div>
    </a>

  </section>
</template>

<script setup>
// Tidak perlu fungsi scrollToSection lagi karena sudah pakai anchor links
</script>

<style>
/* Import Material Icons - HARUS DI ATAS */
@import url('https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@20..48,100..700,0..1,-50..200');

.material-symbols-outlined {
  font-variation-settings:
  'FILL' 0,
  'wght' 400,
  'GRAD' 0,
  'opsz' 24
}

/* Warna Navy Blue custom */
.navy-900 { background-color: #0f172a; }
.navy-950 { background-color: #020617; }

/* Primary color matching PaketSection */
.primary { color: #60a5fa; }
.bg-primary { background-color: #60a5fa; }
.bg-primary\/40 { background-color: rgba(96, 165, 250, 0.4); }
.bg-primary\/50 { background-color: rgba(96, 165, 250, 0.5); }
.border-primary { border-color: #60a5fa; }
.shadow-primary\/30 { box-shadow: 0 25px 50px -12px rgba(96, 165, 250, 0.3); }

/* Video zoom effect */
video {
  animation: subtleZoom 20s ease-in-out infinite;
}

@keyframes subtleZoom {
  0%, 100% {
    transform: scale(1);
  }
  50% {
    transform: scale(1.05);
  }
}
</style>  