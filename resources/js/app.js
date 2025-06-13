import './bootstrap';
import Echo from 'laravel-echo';

// Fungsi untuk unlock audio setelah interaksi pengguna
function unlockAudio() {
    const audio = new Audio('/sounds/bill.mp3');
    audio.play().catch(() => {});
    document.removeEventListener('click', unlockAudio);
    document.removeEventListener('keydown', unlockAudio);
}

// Pasang event listener untuk unlock audio
document.addEventListener('click', unlockAudio);
document.addEventListener('keydown', unlockAudio);

// Inisialisasi Echo dengan broadcaster Reverb
window.Echo = new Echo({
    broadcaster: 'reverb',
    key: import.meta.env.VITE_REVERB_APP_KEY,
    wsHost: import.meta.env.VITE_REVERB_HOST || 'localhost',
    wsPort: import.meta.env.VITE_REVERB_PORT || 8080,
    wssPort: import.meta.env.VITE_REVERB_PORT || 8080,
    forceTLS: false,
    enabledTransports: ['ws'],
});

document.addEventListener('DOMContentLoaded', () => {
    if (typeof Filament !== 'undefined' && Filament.auth && Filament.auth.user) {
        const userId = Filament.auth.user.id;

        // Dengarkan notifikasi realtime pada channel private user
        window.Echo.private(`App.Models.User.${userId}`)
            .notification((notification) => {
                // Tampilkan notifikasi popup
                Filament.Notification.show(notification.title, notification.body);

                // Buat objek audio dan mainkan suara notifikasi
                const audio = new Audio('/sounds/bill.mp3');
                audio.volume = 0.5;
                audio.preload = 'auto';

                audio.oncanplaythrough = () => {
                    audio.play().catch(error => {
                        console.warn('Audio autoplay diblokir, butuh interaksi pengguna.', error);
                    });
                };

                audio.onerror = () => {
                    console.error('Gagal memuat file audio, periksa path atau server.');
                };

                // Simulasikan klik pada ikon notifikasi untuk membuka panel
                setTimeout(() => {
                    const notificationBell = document.querySelector('.filament-notifications-bell');
                    if (notificationBell) {
                        notificationBell.click();
                    }
                }, 100);
            });
    } else {
        console.warn('Filament.auth tidak tersedia, periksa autentikasi.');
    }
});

// Polling fallback sebagai cadangan (opsional)
setInterval(() => {
    axios.get('/api/check-notifications')
        .then(response => {
            if (response.data.unread > 0) {
                Filament.Notification.show('Notifikasi Baru', 'Anda memiliki notifikasi yang belum dibaca.');

                const audio = new Audio('/sounds/bill.mp3');
                audio.volume = 0.5;
                audio.preload = 'auto';

                audio.oncanplaythrough = () => {
                    audio.play().catch(error => {
                        console.warn('Audio autoplay diblokir, butuh interaksi pengguna.', error);
                    });
                };

                audio.onerror = () => {
                    console.error('Gagal memuat file audio, periksa path atau server.');
                };

                setTimeout(() => {
                    const notificationBell = document.querySelector('.filament-notifications-bell');
                    if (notificationBell) {
                        notificationBell.click();
                    }
                }, 100);
            }
        })
        .catch(error => console.log('Error polling notifikasi:', error));
}, 5000);
