<template>
  <nav class="fixed top-0 left-0 w-full z-50 bg-navy-900/80 backdrop-blur-md border-b border-primary/30">
    <div class="max-w-7xl mx-auto px-6 py-4 flex justify-between items-center">

      <!-- LOGO -->
      <div class="flex items-center gap-3 cursor-pointer" @click="scrollToSection('beranda')">
        <img src="@/assets/logo.jpg"
             class="w-12 h-12 rounded-full object-cover shadow-lg border border-primary/40" />

        <h1 class="text-white font-black text-xl tracking-wide drop-shadow-lg">
          <span class="text-transparent bg-clip-text bg-gradient-to-r from-white to-primary">KRISHNA</span>
          <span class="text-white/80 text-lg font-semibold ml-1">Mengemudi</span>
        </h1>
      </div>

      <!-- MENU -->
      <ul class="flex gap-2 text-white font-medium items-center">
        <li>
          <a @click="scrollToSection('beranda')" class="hover:text-primary duration-200 cursor-pointer py-2 px-4 rounded-lg hover:bg-white/5 transition-all">Beranda</a>
        </li>
        <li>
          <a @click="scrollToSection('paket')" class="hover:text-primary duration-200 cursor-pointer py-2 px-4 rounded-lg hover:bg-white/5 transition-all">Paket</a>
        </li>
        <li>
          <a @click="scrollToSection('testimoni')" class="hover:text-primary duration-200 cursor-pointer py-2 px-4 rounded-lg hover:bg-white/5 transition-all">Testimoni</a>
        </li>
        <li>
          <a @click="scrollToSection('gallery')" class="hover:text-primary duration-200 cursor-pointer py-2 px-4 rounded-lg hover:bg-white/5 transition-all">Gallery</a>
        </li>
        <li>
          <a @click="scrollToSection('kontak')" class="hover:text-primary duration-200 cursor-pointer py-2 px-4 rounded-lg hover:bg-white/5 transition-all">Kontak</a>
        </li>
        
        <!-- TOMBOL DAFTAR SEKARANG -->
        <li class="ml-4">
          <a @click="scrollToSection('daftar')" class="group bg-gradient-to-r from-primary/80 to-primary/60 hover:from-primary hover:to-primary/80 text-white px-8 py-3 rounded-full font-bold shadow-lg transition-all duration-300 transform hover:scale-105 hover:shadow-primary/25 flex items-center gap-2 cursor-pointer">
            <span>Daftar Sekarang</span>
            <svg class="w-4 h-4 group-hover:translate-x-1 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6"></path>
            </svg>
          </a>
        </li>
      </ul>

    </div>
  </nav>
</template>

<script setup>
const scrollToSection = (sectionId) => {
  const element = document.getElementById(sectionId);
  if (element) {
    element.scrollIntoView({ 
      behavior: 'smooth',
      block: 'start'
    });
  }
};
</script>

<style scoped>
/* Warna Navy Blue untuk navbar */
.bg-navy-900 { background-color: #0f172a; }

/* Primary color matching dengan Hero & PaketSection */
.primary { color: #60a5fa; }
.bg-primary { background-color: #60a5fa; }
.border-primary { border-color: #60a5fa; }
.from-primary { --tw-gradient-from: #60a5fa; }
.to-primary { --tw-gradient-to: #60a5fa; }
.hover\:shadow-primary\/25:hover { box-shadow: 0 10px 25px rgba(96, 165, 250, 0.25); }
</style>