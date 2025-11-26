    <!-- Footer -->
    <footer class="bg-gray-800 text-white py-12 mt-12">
        <div class="max-w-7xl mx-auto px-4">
            <div class="grid grid-cols-1 md:grid-cols-4 gap-8">
                <!-- Company Info -->
                <div>
                    <div class="flex items-center mb-4">
                        <img src="./assets/images/logo1.png" alt="logo" class="w-10 h-10 mr-3 rounded-full object-cover">
                        <span class="text-xl font-bold">Krishna Kursus</span>
                    </div>
                    <p class="text-gray-400 text-sm leading-relaxed mb-4">
                        Kursus mengemudi mobil profesional dengan instruktur berpengalaman dan metode terbaik. 
                        Garansi sampai bisa mengemudi dengan percaya diri.
                    </p>
                    <div class="flex space-x-3">
                        <a href="#" class="text-gray-400 hover:text-white transition duration-300">
                            <i class="fab fa-facebook text-lg"></i>
                        </a>
                        <a href="#" class="text-gray-400 hover:text-white transition duration-300">
                            <i class="fab fa-instagram text-lg"></i>
                        </a>
                        <a href="#" class="text-gray-400 hover:text-white transition duration-300">
                            <i class="fab fa-youtube text-lg"></i>
                        </a>
                        <a href="#" class="text-gray-400 hover:text-white transition duration-300">
                            <i class="fab fa-tiktok text-lg"></i>
                        </a>
                    </div>
                </div>
                
                <!-- Quick Links -->
                <div>
                    <h4 class="font-bold mb-4 text-lg">Menu Cepat</h4>
                    <div class="space-y-2 text-sm">
                        <a href="index.php" class="block text-gray-400 hover:text-white transition duration-300">Beranda</a>
                        <a href="paket-kursus.php" class="block text-gray-400 hover:text-white transition duration-300">Paket Kursus</a>
                        <a href="index.php#instruktur" class="block text-gray-400 hover:text-white transition duration-300">Instruktur</a>
                        <a href="index.php#testimoni" class="block text-gray-400 hover:text-white transition duration-300">Testimoni</a>
                        <a href="cek-status.php" class="block text-gray-400 hover:text-white transition duration-300">Cek Status</a>
                    </div>
                </div>
                
                <!-- Contact Info -->
                <div>
                    <h4 class="font-bold mb-4 text-lg">Kontak Kami</h4>
                    <div class="space-y-2 text-sm text-gray-400">
                        <p class="flex items-center">
                            <i class="fas fa-map-marker-alt mr-3 text-blue-400"></i>
                            Jl. Raya Contoh No. 123, Jakarta
                        </p>
                        <p class="flex items-center">
                            <i class="fas fa-phone mr-3 text-blue-400"></i>
                            +62 812-3456-7890
                        </p>
                        <p class="flex items-center">
                            <i class="fas fa-envelope mr-3 text-blue-400"></i>
                            info@krishnadriving.com
                        </p>
                        <p class="flex items-center">
                            <i class="fab fa-whatsapp mr-3 text-green-400"></i>
                            +62 812-3456-7890
                        </p>
                    </div>
                </div>
                
                <!-- Business Hours -->
                <div>
                    <h4 class="font-bold mb-4 text-lg">Jam Operasional</h4>
                    <div class="text-sm text-gray-400 space-y-2">
                        <div class="flex justify-between">
                            <span>Senin - Jumat:</span>
                            <span>08:00 - 20:00</span>
                        </div>
                        <div class="flex justify-between">
                            <span>Sabtu:</span>
                            <span>08:00 - 18:00</span>
                        </div>
                        <div class="flex justify-between">
                            <span>Minggu:</span>
                            <span>08:00 - 15:00</span>
                        </div>
                    </div>
                    
                    <!-- WhatsApp CTA -->
                    <div class="mt-4">
                        <a href="https://wa.me/6281234567890?text=Halo,%20saya%20ingin%20bertanya%20tentang%20kursus%20mengemudi%20di%20Krishna%20Driving" 
                           target="_blank"
                           class="inline-flex items-center bg-green-500 text-white px-4 py-2 rounded-lg font-semibold hover:bg-green-600 transition duration-300 text-sm">
                            <i class="fab fa-whatsapp mr-2"></i>Chat WhatsApp
                        </a>
                    </div>
                </div>
            </div>
            
            <!-- Bottom Bar -->
            <div class="border-t border-gray-700 mt-8 pt-8 text-center text-sm text-gray-400">
                <p>&copy; 2024 Krishna Driving Course. All rights reserved. | 
                   <a href="#" class="hover:text-white transition duration-300">Privacy Policy</a> | 
                   <a href="#" class="hover:text-white transition duration-300">Terms of Service</a>
                </p>
            </div>
        </div>
    </footer>

    <!-- Back to Top Button -->
    <button id="backToTop" class="fixed bottom-6 right-6 bg-blue-600 text-white p-3 rounded-full shadow-lg hover:bg-blue-700 transition duration-300 opacity-0 invisible">
        <i class="fas fa-chevron-up"></i>
    </button>

    <script>
        // Back to Top Button
        const backToTop = document.getElementById('backToTop');
        
        window.addEventListener('scroll', function() {
            if (window.pageYOffset > 300) {
                backToTop.classList.remove('opacity-0', 'invisible');
                backToTop.classList.add('opacity-100', 'visible');
            } else {
                backToTop.classList.remove('opacity-100', 'visible');
                backToTop.classList.add('opacity-0', 'invisible');
            }
        });

        backToTop.addEventListener('click', function() {
            window.scrollTo({
                top: 0,
                behavior: 'smooth'
            });
        });

        // Smooth scrolling for anchor links
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function (e) {
                e.preventDefault();
                const target = document.querySelector(this.getAttribute('href'));
                if (target) {
                    target.scrollIntoView({
                        behavior: 'smooth',
                        block: 'start'
                    });
                }
            });
        });
    </script>
</body>
</html>