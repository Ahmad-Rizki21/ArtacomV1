import './bootstrap';
import Echo from 'laravel-echo';

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

        window.Echo.private(`App.Models.User.${userId}`)
            .notification((notification) => {
                // Tampilkan notifikasi popup
                Filament.Notification.show(notification.title, notification.body);

                // Buat objek audio dan mainkan suara notifikasi
                const audio = new Audio('/sounds/bill.mp3'); // pastikan path audio benar
                audio.volume = 0.5;
                audio.preload = 'auto';

                // Putar audio saat sudah siap
                audio.oncanplaythrough = () => {
                    audio.play().catch(error => {
                        // Handle error autoplay (misal browser blokir)
                        console.log('Error memutar audio:', error);
                    });
                };

                audio.onerror = () => {
                    console.error('Gagal memuat file audio, periksa path atau server.');
                };

                // Buka panel notifikasi dengan klik simulasi (opsional)
                setTimeout(() => {
                    const notificationBell = document.querySelector('.filament-notifications-bell');
                    if (notificationBell) {
                        notificationBell.click();
                    }
                }, 100);
            });

    }
});

// Polling fallback (optional)
setInterval(() => {
    axios.get('/api/check-notifications')
        .then(response => {
            if (response.data.unread > 0) {
                Filament.Notification.show('Notifikasi Baru', 'Anda memiliki notifikasi yang belum dibaca.');
                const audio = new Audio('/sounds/bill.mp3');
                audio.volume = 0.5;
                audio.preload = 'auto';
                audio.oncanplaythrough = () => {
                    audio.play().catch(() => {});
                };
                setTimeout(() => {
                    const bell = document.querySelector('.filament-notifications-bell');
                    if (bell) bell.click();
                }, 100);
            }
        })
        .catch(console.error);
}, 5000);
