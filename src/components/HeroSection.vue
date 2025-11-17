<template>
  <section class="relative w-full h-screen overflow-hidden">

    <!-- VIDEO BACKGROUND -->
    <video
      autoplay
      loop
      muted
      playsinline
      class="absolute top-0 left-0 w-full h-full object-cover"
    >
      <source src="/video/bg-driving.mp4" type="video/mp4" />
    </video>

    <!-- OVERLAY GELAP -->
    <div class="absolute inset-0 bg-black/50"></div>

    <!-- TEXT DI ATAS VIDEO -->
    <div class="relative z-10 h-full flex flex-col justify-center items-center text-center px-4">
      <h1 class="text-4xl md:text-6xl font-extrabold text-white drop-shadow-lg">
        Kuasai Jalanan Bersama Kami
      </h1>

      <p class="text-lg md:text-xl text-gray-200 mt-4 max-w-2xl">
        Dari pemula hingga mahir, instruktur profesional kami siap membimbing Anda
        menjadi pengemudi yang andal, aman, dan percaya diri.
      </p>

      <p class="text-gray-300 mt-3 max-w-xl italic">
        Belajar nyaman, mobil terbaru, jadwal fleksibel—semua untuk pengalaman belajar terbaik.
      </p>

      <!-- CTA BUTTON -->
      <div class="mt-6 flex gap-4">
        <a
          href="#"
          class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-3 rounded-lg font-semibold shadow-lg"
        >
          Daftar Sekarang
        </a>

        <a
          href="#"
          class="bg-white/20 hover:bg-white/30 text-white px-6 py-3 rounded-lg font-semibold border border-white/40 shadow-lg"
        >
          Lihat Paket Kursus
        </a>
      </div>
    </div>
  </section>
</template>

<script setup>
// tidak perlu script
</script>
