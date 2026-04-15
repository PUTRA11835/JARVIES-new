# Toast Notification — Panduan Implementasi untuk Jarvies

Dokumen ini berisi spesifikasi lengkap sistem toast notification EcoSystem agar Jarvies dapat mengimplementasikan tampilan yang identik.

---

## Hasil Akhir (Preview)

Toast muncul di sudut kanan atas, slide-in dari kanan, dan otomatis menghilang setelah beberapa detik. Terdapat 4 tipe:

| Tipe      | Warna Background | Warna Border | Label       |
|-----------|------------------|--------------|-------------|
| `success` | Hijau muda       | Hijau        | Success     |
| `error`   | Merah muda       | Merah        | Error       |
| `warning` | Kuning muda      | Kuning       | Warning     |
| `info`    | Biru muda        | Biru         | Information |

---

## 1. Struktur HTML

Letakkan container ini di dalam `<body>`, sebelum tag penutup `</body>`:

```html
<div id="toast-container"></div>
```

Toast individual akan di-inject ke dalam container ini oleh JavaScript secara otomatis.

---

## 2. CSS

Salin seluruh CSS berikut ke stylesheet global (misalnya `app.css` atau `<style>` di layout utama):

```css
#toast-container {
    position: fixed; top: 1.5rem; right: 1.5rem; z-index: 9999;
    display: flex; flex-direction: column; gap: 0.75rem;
    max-width: 22rem; width: 100%; pointer-events: none;
}
.toast {
    pointer-events: all; border-radius: 0.875rem;
    padding: 1rem 1rem 0 1rem; display: flex; flex-direction: column;
    box-shadow: 0 10px 30px rgba(0,0,0,0.15); overflow: hidden;
    transform: translateX(110%); opacity: 0;
    transition: transform 0.4s cubic-bezier(0.34,1.56,0.64,1), opacity 0.3s ease;
}
.toast.show { transform: translateX(0); opacity: 1; }
.toast.hide { transform: translateX(110%); opacity: 0; transition: transform 0.35s ease-in, opacity 0.3s ease-in; }
.toast-body { display: flex; align-items: flex-start; gap: 0.75rem; padding-bottom: 0.875rem; }
.toast-icon { flex-shrink: 0; width: 2rem; height: 2rem; border-radius: 50%; display: flex; align-items: center; justify-content: center; }
.toast-content { flex: 1; min-width: 0; }
.toast-title { font-size: 0.8125rem; font-weight: 700; line-height: 1.2; }
.toast-message { font-size: 0.8125rem; margin-top: 0.2rem; line-height: 1.4; }
.toast-close { flex-shrink: 0; background: none; border: none; cursor: pointer; padding: 0.1rem; border-radius: 0.375rem; opacity: 0.5; transition: opacity 0.2s; line-height: 1; }
.toast-close:hover { opacity: 1; }
.toast-progress { height: 3px; border-radius: 0 0 0.875rem 0.875rem; margin: 0 -1rem; transform-origin: left; animation: toast-progress-shrink linear forwards; }
@keyframes toast-progress-shrink { from { transform: scaleX(1); } to { transform: scaleX(0); } }

/* ── Tipe: success ── */
.toast-success { background: #f0fdf4; border: 1.5px solid #86efac; }
.toast-success .toast-icon { background: #dcfce7; }
.toast-success .toast-icon svg { color: #16a34a; }
.toast-success .toast-title { color: #14532d; }
.toast-success .toast-message { color: #15803d; }
.toast-success .toast-close { color: #14532d; }
.toast-success .toast-progress { background: #22c55e; }

/* ── Tipe: error ── */
.toast-error { background: #fff1f1; border: 1.5px solid #fca5a5; }
.toast-error .toast-icon { background: #fee2e2; }
.toast-error .toast-icon svg { color: #dc2626; }
.toast-error .toast-title { color: #991b1b; }
.toast-error .toast-message { color: #b91c1c; }
.toast-error .toast-close { color: #991b1b; }
.toast-error .toast-progress { background: #ef4444; }

/* ── Tipe: warning ── */
.toast-warning { background: #fffbeb; border: 1.5px solid #fcd34d; }
.toast-warning .toast-icon { background: #fef9c3; }
.toast-warning .toast-icon svg { color: #d97706; }
.toast-warning .toast-title { color: #78350f; }
.toast-warning .toast-message { color: #92400e; }
.toast-warning .toast-close { color: #78350f; }
.toast-warning .toast-progress { background: #f59e0b; }

/* ── Tipe: info ── */
.toast-info { background: #eff6ff; border: 1.5px solid #93c5fd; }
.toast-info .toast-icon { background: #dbeafe; }
.toast-info .toast-icon svg { color: #2563eb; }
.toast-info .toast-title { color: #1e3a8a; }
.toast-info .toast-message { color: #1d4ed8; }
.toast-info .toast-close { color: #1e3a8a; }
.toast-info .toast-progress { background: #3b82f6; }
```

---

## 3. JavaScript

Salin seluruh blok JS berikut ke file JS global (misalnya `app.js` atau `<script>` di layout utama):

```javascript
// ── Icon SVG per tipe ──
const _toastIcons = {
    success: `<svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg>`,
    error:   `<svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/></svg>`,
    warning: `<svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/></svg>`,
    info:    `<svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"/></svg>`,
};

// ── Label judul per tipe ──
const _toastLabels = {
    success: 'Success',
    error:   'Error',
    warning: 'Warning',
    info:    'Information',
};

/**
 * Tampilkan toast notification.
 *
 * @param {string} message  - Teks pesan yang ditampilkan
 * @param {string} type     - Tipe: 'success' | 'error' | 'warning' | 'info'
 * @param {number} duration - Durasi tampil dalam ms (default: 4000)
 */
function showToast(message, type, duration = 4000) {
    type = type ?? 'info';
    const container = document.getElementById('toast-container');
    const toast = document.createElement('div');
    toast.className = `toast toast-${type}`;
    toast.innerHTML = `
        <div class="toast-body">
            <div class="toast-icon">${_toastIcons[type] ?? _toastIcons.info}</div>
            <div class="toast-content">
                <p class="toast-title">${_toastLabels[type] ?? 'Info'}</p>
                <p class="toast-message">${message}</p>
            </div>
            <button class="toast-close" aria-label="Close">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </button>
        </div>
        <div class="toast-progress" style="animation-duration: ${duration}ms;"></div>
    `;
    container.appendChild(toast);

    // Double rAF agar transisi CSS berjalan (elemen harus sudah di-render dulu)
    requestAnimationFrame(() => requestAnimationFrame(() => toast.classList.add('show')));

    function dismiss() {
        toast.classList.remove('show');
        toast.classList.add('hide');
        toast.addEventListener('transitionend', () => toast.remove(), { once: true });
    }

    const timer = setTimeout(dismiss, duration);
    toast.querySelector('.toast-close').addEventListener('click', () => { clearTimeout(timer); dismiss(); });
}

// Alias singkat (opsional)
function showNotification(message, type = 'info') {
    showToast(message, type);
}
```

---

## 4. Cara Penggunaan

### Panggil dari mana saja di halaman:

```javascript
// Berhasil submit form
showToast('Ticket berhasil dibuat!', 'success');

// Gagal / error dari API
showToast('Terjadi kesalahan, coba lagi.', 'error');

// Peringatan
showToast('Sesi Anda akan berakhir dalam 5 menit.', 'warning');

// Informasi umum
showToast('Data berhasil dimuat.', 'info');

// Dengan durasi kustom (7 detik)
showToast('Upload sedang diproses...', 'info', 7000);
```

### Contoh setelah AJAX / `fetch`:

```javascript
fetch('/api/tickets', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken },
    body: JSON.stringify(payload),
})
.then(res => res.json())
.then(data => {
    if (data.success) {
        showToast('Ticket berhasil dikirim!', 'success');
    } else {
        showToast(data.message ?? 'Gagal mengirim ticket.', 'error');
    }
})
.catch(() => showToast('Koneksi bermasalah.', 'error'));
```

---

## 5. Catatan Penting

### Urutan inject
Pastikan `#toast-container` sudah ada di DOM **sebelum** JS di-load. Letakkan `<div id="toast-container"></div>` di awal `<body>` atau di dalam komponen layout utama.

### Multiple toast
Sistem ini mendukung multiple toast sekaligus — setiap `showToast()` menambahkan elemen baru ke container, dan masing-masing memiliki timer sendiri.

### Tailwind CSS
CSS di atas **tidak memerlukan Tailwind** — semua ditulis inline tanpa class utility. Tailwind hanya digunakan pada class `w-4 h-4` di dalam SVG (icon ukuran). Jika Jarvies tidak menggunakan Tailwind, ganti:
- `w-4 h-4` → `width: 1rem; height: 1rem;` (tambahkan ke CSS `.toast-icon svg` dan `.toast-close svg`)

### Keamanan (XSS)
Fungsi `showToast` menyisipkan `message` sebagai innerHTML tanpa sanitasi. Jika `message` berasal dari input user atau response API yang tidak terpercaya, **escape HTML-nya terlebih dahulu**:

```javascript
function escapeHtml(str) {
    return str.replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
}
showToast(escapeHtml(apiResponse.message), 'error');
```

### Integrasi Vue / React
Untuk framework berbasis komponen, buat helper file global (`toast.js`) yang mengekspor `showToast`, lalu panggil dari komponen manapun:

```javascript
// utils/toast.js
export function showToast(message, type = 'info', duration = 4000) {
    // ... (paste implementasi di atas)
}
```

```javascript
// Di dalam komponen Vue / React
import { showToast } from '@/utils/toast';
showToast('Berhasil!', 'success');
```

Pastikan `#toast-container` tetap ada di template utama (`App.vue` / root `index.html`).
