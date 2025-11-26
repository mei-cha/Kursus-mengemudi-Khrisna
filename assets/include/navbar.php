<?php
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Navbar Krishna</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        blue: {
                            600: '#2563eb',
                        }
                    }
                }
            }
        }
    </script>
</head>
<body class="bg-gray-50 min-h-screen">
    <!-- Navbar -->
    <nav class="fixed top-0 left-0 w-full z-50 bg-navy-900/80 backdrop-blur-md border-b border-primary/30">
        <div class="max-w-7xl mx-auto px-6 py-4 flex justify-between items-center">
            <!-- LOGO -->
            <div class="flex items-center gap-3 cursor-pointer">
                <div class="w-12 h-12 rounded-full bg-primary/20 flex items-center justify-center text-white font-bold">
                    K
                </div>
                <h1 class="text-white font-black text-xl tracking-wide drop-shadow-lg">
                    <span class="text-transparent bg-clip-text bg-gradient-to-r from-white to-primary">KRISHNA</span>
                    <span class="text-white/80 text-lg font-semibold ml-1">Mengemudi</span>
                </h1>
            </div>

            <!-- MENU -->
            <ul class="hidden md:flex gap-2 text-white font-medium items-center">
                <li>
                    <a href="#" class="hover:text-primary duration-200 cursor-pointer py-2 px-4 rounded-lg hover:bg-white/5 transition-all">Beranda</a>
                </li>
                <li>
                    <a href="#" class="text-primary duration-200 cursor-pointer py-2 px-4 rounded-lg bg-white/5 transition-all">Paket</a>
                </li>
                <li>
                    <a href="#" class="hover:text-primary duration-200 cursor-pointer py-2 px-4 rounded-lg hover:bg-white/5 transition-all">Testimoni</a>
                </li>
                <li>
                    <a href="#" class="hover:text-primary duration-200 cursor-pointer py-2 px-4 rounded-lg hover:bg-white/5 transition-all">Gallery</a>
                </li>
                <li>
                    <a href="#" class="hover:text-primary duration-200 cursor-pointer py-2 px-4 rounded-lg hover:bg-white/5 transition-all">Kontak</a>
                </li>
                
                <!-- TOMBOL DAFTAR SEKARANG -->
                <li class="ml-4">
                    <a href="#" class="group btn-primary text-white px-8 py-3 rounded-full font-bold shadow-lg transition-all duration-300 transform hover:scale-105 flex items-center gap-2 cursor-pointer">
                        <span>Daftar Sekarang</span>
                        <svg class="w-4 h-4 group-hover:translate-x-1 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6"></path>
                        </svg>
                    </a>
                </li>
            </ul>

            <!-- Mobile Menu Button -->
            <button class="md:hidden text-white">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path>
                </svg>
            </button>
        </div>
    </nav>

    <script>
        // Fungsi untuk toggle mobile menu
        document.getElementById('mobileMenuButton').addEventListener('click', function() {
            const mobileMenu = document.getElementById('mobileMenu');
            mobileMenu.classList.toggle('hidden');
        });

        // Fungsi untuk menutup mobile menu ketika link diklik
        document.querySelectorAll('.mobile-menu-link').forEach(link => {
            link.addEventListener('click', function() {
                document.getElementById('mobileMenu').classList.add('hidden');
            });
        });
    </script>
</body>
</html>