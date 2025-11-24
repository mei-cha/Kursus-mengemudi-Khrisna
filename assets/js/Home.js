/**
 * Fungsi untuk memuat konten HTML dari sebuah file dan menyisipkannya
 * ke dalam elemen placeholder yang ditentukan.
 * @param {string} url - Jalur (path) ke file HTML yang akan dimuat.
 * @param {string} elementId - ID dari elemen HTML di mana konten akan disisipkan.
 */
function loadHTML(url, elementId) {
    // Menggunakan Fetch API untuk mengambil konten file
    fetch(url)
        .then(response => {
            if (!response.ok) {
                // Penting: Gagal jika Anda menjalankan ini langsung dari C:/ tanpa server
                throw new Error(`Gagal memuat file: ${url} (Status: ${response.status}). Pastikan Anda menggunakan web server.`);
            }
            return response.text();
        })
        .then(htmlContent => {
            const placeholder = document.getElementById(elementId);
            if (placeholder) {
                // Menyisipkan konten yang dimuat ke dalam placeholder
                placeholder.innerHTML = htmlContent;
            }
        })
        .catch(error => {
            console.error('Error saat memuat komponen:', error);
        });
}


// Tambahkan pemanggilan fungsi ini di dalam event DOMContentLoaded yang sudah ada
document.addEventListener('DOMContentLoaded', function() {
    
    // START: INCLUDE NAVBAR & FOOTER
    // PATH HARUS SAMA DENGAN STRUKTUR FOLDER ANDA
    // assets/include/navbar.html
    loadHTML('assets/include/navbar.html', 'navbar-placeholder');
    // assets/include/footer.html
    loadHTML('assets/include/footer.html', 'footer-placeholder');
    // END: INCLUDE NAVBAR & FOOTER
    
    // ... KODE SMOOTH SCROLLING ANDA YANG SUDAH ADA ...
    
    // Smooth scrolling for anchor links
    const anchorLinks = document.querySelectorAll('a[href^="#"]');

    // ... SISA KODE HOME.JS ANDA YANG LAIN ...
});




    // =======================================================
// FUNGSI UTILITY: LOAD EXTERNAL HTML COMPONENTS
// =======================================================

/**
 * Fungsi untuk memuat konten HTML dari sebuah file dan menyisipkannya
 * ke dalam elemen placeholder yang ditentukan.
 */
function loadHTML(url, elementId) {
    fetch(url)
        .then(response => {
            if (!response.ok) {
                // Warning jika dijalankan tanpa server
                throw new Error(`Gagal memuat file: ${url} (Status: ${response.status}).`);
            }
            return response.text();
        })
        .then(htmlContent => {
            const placeholder = document.getElementById(elementId);
            if (placeholder) {
                placeholder.innerHTML = htmlContent;
            }
        })
        .catch(error => {
            console.error('Error saat memuat komponen:', error);
        });
}

document.addEventListener('DOMContentLoaded', function() {
/**
 * Fungsi untuk menginisiasi pemuatan Navbar dan Footer pada halaman mana pun.
 */
function initIncludes() {
    loadHTML('assets/include/navbar.html', 'navbar-placeholder');
    loadHTML('assets/include/footer.html', 'footer-placeholder');
}
// =======================================================
// AKHIR FUNGSI UTILITY
// =======================================================


document.addEventListener('DOMContentLoaded', function() {
    // Jalankan fungsi include saat halaman dimuat (untuk Home.html dan halaman lain)
    initIncludes();

    // ... SISA KODE SMOOTH SCROLLING ANDA YANG SUDAH ADA ...
    // ...
});

// Anda tidak perlu lagi mendefinisikan window.scrollToSection di sini
initIncludes(); 

    // ... Sisa kode Anda yang lain (smooth scrolling, dll.)
});


// ... SISA KODE HOME.JS ANDA ...
// Home Page JavaScript - Sama persis dengan sebelumnya
document.addEventListener('DOMContentLoaded', function() {
    // Smooth scrolling for anchor links
    const anchorLinks = document.querySelectorAll('a[href^="#"]');
    
    anchorLinks.forEach(link => {
        link.addEventListener('click', function(e) {
            e.preventDefault();
            
            const targetId = this.getAttribute('href');
            if (targetId === '#') return;
            
            const targetElement = document.querySelector(targetId);
            if (targetElement) {
                const offsetTop = targetElement.offsetTop;
                
                window.scrollTo({
                    top: offsetTop,
                    behavior: 'smooth'
                });
            }
        });
    });

    // Package card hover effects
    const packageCards = document.querySelectorAll('.package-card');
    
    packageCards.forEach(card => {
        card.addEventListener('mouseenter', function() {
            if (!this.classList.contains('popular')) {
                this.style.transform = 'translateY(-0.5rem)';
            }
        });
        
        card.addEventListener('mouseleave', function() {
            if (!this.classList.contains('popular')) {
                this.style.transform = 'translateY(0)';
            }
        });
    });

    // Feature card hover effects
    const featureCards = document.querySelectorAll('.feature-card');
    
    featureCards.forEach(card => {
        card.addEventListener('mouseenter', function() {
            if (this.classList.contains('featured')) {
                this.style.transform = 'scale(1.05)';
            }
        });
        
        card.addEventListener('mouseleave', function() {
            if (this.classList.contains('featured')) {
                this.style.transform = 'scale(1)';
            }
        });
    });

    // CTA button animations
    const ctaButtons = document.querySelectorAll('.cta-button, .package-button');
    
    ctaButtons.forEach(button => {
        button.addEventListener('mouseenter', function() {
            this.style.transform = 'scale(1.05)';
        });
        
        button.addEventListener('mouseleave', function() {
            this.style.transform = 'scale(1)';
        });
    });

    // Video background animation
    const videoBg = document.querySelector('.video-bg');
    if (videoBg) {
        videoBg.addEventListener('loadeddata', function() {
            this.style.opacity = '0.4';
        });
    }

    // Scroll indicator click handler
    const scrollIndicator = document.querySelector('.scroll-indicator');
    if (scrollIndicator) {
        scrollIndicator.addEventListener('click', function(e) {
            e.preventDefault();
            const targetElement = document.querySelector('#paket');
            if (targetElement) {
                const offsetTop = targetElement.offsetTop;
                window.scrollTo({
                    top: offsetTop,
                    behavior: 'smooth'
                });
            }
        });
    }

    // Add loading animation for testimonial placeholders
    const testimonialCards = document.querySelectorAll('.testimonial-card');
    
    testimonialCards.forEach(card => {
        const contentLines = card.querySelectorAll('.content-line');
        contentLines.forEach(line => {
            line.style.animation = 'pulse 2s infinite';
        });
    });

    console.log('Krishna Driving School Homepage loaded successfully!');
});

// Utility function untuk scroll ke section
function scrollToSection(sectionId) {
    const section = document.querySelector(sectionId);
    if (section) {
        section.scrollIntoView({ behavior: 'smooth' });
    }
}

// Export functions untuk global use
window.scrollToSection = scrollToSection;
