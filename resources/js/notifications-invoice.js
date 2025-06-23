document.addEventListener('DOMContentLoaded', function () {
    // Inisialisasi audio
    const audio = new Audio('/sounds/payment.mp3');

    // Fungsi untuk polling notifikasi
    function pollNotifications() {
        fetch('/api/invoice-notifications', {
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.status === 'success' && data.notifications.length > 0) {
                data.notifications.forEach(notification => {
                    // Tampilkan alert
                    alert(`Status invoice #${notification.invoice_number} dari ${notification.pelanggan} berubah menjadi ${notification.status}! Total: Rp ${notification.total_harga}`);

                    // Mainkan suara
                    audio.play().catch(error => {
                        console.error('Gagal memutar audio:', error);
                    });
                });
            }
        })
        .catch(error => {
            console.error('Error polling notifications:', error);
        });
    }

    // Jalankan polling setiap 10 detik
    setInterval(pollNotifications, 10000);

    // Jalankan sekali saat halaman dimuat
    pollNotifications();
});