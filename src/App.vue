<template>
  <div>
    <Navbar /> 
    <HeroSection />
    <WhyChooseSection />
    <PaketSection />
    <Testimoni />
    <CTA />
    <Footer />
  </div>
</template>

<script>
import Navbar from "./components/layout/Navbar.vue";
import HeroSection from "./pages/halaman1/Hero.vue";
import WhyChooseSection from "./pages/halaman1/WhyChoose.vue";
import PaketSection from "./pages/halaman1/PaketSection.vue";
import Testimoni from "./pages/halaman1/Testimoni.vue";
import CTA from "./pages/halaman1/CTA.vue";
import Footer from "./components/layout/Footer.vue";

export default {
  components: {
    Navbar,
    HeroSection,
    WhyChooseSection,
    PaketSection,
    Testimoni,
    CTA,
    Footer
  }
};
</script>
