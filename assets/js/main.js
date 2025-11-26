// Mobile Menu Toggle
document.addEventListener('DOMContentLoaded', function() {
    const mobileMenuButton = document.getElementById('mobile-menu-button');
    const mobileMenu = document.getElementById('mobile-menu');
    
    if (mobileMenuButton && mobileMenu) {
        mobileMenuButton.addEventListener('click', function() {
            mobileMenu.classList.toggle('hidden');
        });
    }
    
    // Close mobile menu when clicking outside
    document.addEventListener('click', function(event) {
        if (mobileMenu && !mobileMenu.contains(event.target) && 
            mobileMenuButton && !mobileMenuButton.contains(event.target) &&
            !mobileMenu.classList.contains('hidden')) {
            mobileMenu.classList.add('hidden');
        }
    });
    
    // Smooth scrolling for navigation links
    document.querySelectorAll('a[href^="#"]').forEach(anchor => {
        anchor.addEventListener('click', function (e) {
            e.preventDefault();
            const target = document.querySelector(this.getAttribute('href'));
            if (target) {
                target.scrollIntoView({
                    behavior: 'smooth',
                    block: 'start'
                });
                
                // Close mobile menu after clicking
                if (mobileMenu) {
                    mobileMenu.classList.add('hidden');
                }
            }
        });
    });
});

// Form Pendaftaran Handling
document.getElementById('formPendaftaran')?.addEventListener('submit', function(e) {
    e.preventDefault();
    
    if (!validateForm()) {
        return;
    }
    
    const formData = new FormData(this);
    const data = Object.fromEntries(formData);
    
    // Show loading state
    const submitBtn = this.querySelector('button[type="submit"]');
    const originalText = submitBtn.innerHTML;
    submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Mengirim...';
    submitBtn.disabled = true;
    
    // Kirim data ke server
    fetch('process/submit_pendaftaran.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: new URLSearchParams(data)
    })
    .then(response => response.json())
    .then(result => {
        if (result.success) {
            showSuccessMessage(result.nomor_pendaftaran);
            this.reset();
        } else {
            showErrorMessage(result.message);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showErrorMessage('Terjadi kesalahan saat mengirim data.');
    })
    .finally(() => {
        submitBtn.innerHTML = originalText;
        submitBtn.disabled = false;
    });
});

function validateForm() {
    const form = document.getElementById('formPendaftaran');
    const requiredFields = form.querySelectorAll('[required]');
    let isValid = true;
    
    requiredFields.forEach(field => {
        if (!field.value.trim()) {
            isValid = false;
            field.classList.add('border-red-500');
        } else {
            field.classList.remove('border-red-500');
        }
    });
    
    // Validasi email
    const email = document.getElementById('email');
    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    if (email.value && !emailRegex.test(email.value)) {
        isValid = false;
        email.classList.add('border-red-500');
    }
    
    // Validasi telepon
    const telepon = document.getElementById('telepon');
    const teleponRegex = /^[0-9+\-\s()]{10,}$/;
    if (telepon.value && !teleponRegex.test(telepon.value)) {
        isValid = false;
        telepon.classList.add('border-red-500');
    }
    
    if (!isValid) {
        showErrorMessage('Harap lengkapi semua field yang wajib diisi dengan format yang benar.');
    }
    
    return isValid;
}

function showSuccessMessage(nomorPendaftaran) {
    const alertHTML = `
        <div class="fixed top-4 right-4 bg-green-100 border border-green-400 text-green-700 px-6 py-4 rounded-lg shadow-lg z-50 max-w-md">
            <div class="flex items-center">
                <i class="fas fa-check-circle text-green-500 text-xl mr-3"></i>
                <div>
                    <h4 class="font-bold">Pendaftaran Berhasil!</h4>
                    <p class="text-sm mt-1">Nomor Pendaftaran: <strong>${nomorPendaftaran}</strong></p>
                    <p class="text-sm">Simpan nomor ini untuk mengecek status pendaftaran.</p>
                </div>
            </div>
        </div>
    `;
    
    document.body.insertAdjacentHTML('beforeend', alertHTML);
    
    // Remove alert after 5 seconds
    setTimeout(() => {
        const alert = document.querySelector('.fixed.top-4.right-4');
        if (alert) alert.remove();
    }, 5000);
}

function showErrorMessage(message) {
    const alertHTML = `
        <div class="fixed top-4 right-4 bg-red-100 border border-red-400 text-red-700 px-6 py-4 rounded-lg shadow-lg z-50 max-w-md">
            <div class="flex items-center">
                <i class="fas fa-exclamation-circle text-red-500 text-xl mr-3"></i>
                <div>
                    <h4 class="font-bold">Terjadi Kesalahan</h4>
                    <p class="text-sm mt-1">${message}</p>
                </div>
            </div>
        </div>
    `;
    
    document.body.insertAdjacentHTML('beforeend', alertHTML);
    
    // Remove alert after 5 seconds
    setTimeout(() => {
        const alert = document.querySelector('.fixed.top-4.right-4');
        if (alert) alert.remove();
    }, 5000);
}

function pilihPaket(paketId) {
    document.getElementById('paket_kursus_id').value = paketId;
    document.getElementById('pendaftaran').scrollIntoView({ behavior: 'smooth' });
}

// Real-time validation
document.querySelectorAll('#formPendaftaran input, #formPendaftaran select').forEach(field => {
    field.addEventListener('blur', function() {
        if (this.hasAttribute('required') && !this.value.trim()) {
            this.classList.add('border-red-500');
        } else {
            this.classList.remove('border-red-500');
        }
    });
});