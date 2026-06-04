# Jarvies — Tipe Pesan Baru: Meeting

## Latar Belakang

EcoSystem menambahkan tipe pesan khusus **Meeting** — digunakan helpdesk untuk menjadwalkan sesi meeting dengan customer sebelum proses solusi dimulai. Pesan ini tersimpan di tabel `ticket_message` sebagai JSON di kolom `message`, bukan teks biasa.

Tanpa penanganan khusus, Jarvies akan menampilkan JSON mentah seperti:

```
{"_type":"meeting","title":"wqert","scheduled_at":"2026-05-16 15:57","duration_minutes":30,"link":"https:\/\/www.youtube.com\/watch?v=...","agenda":"..."}
```

Panduan ini menjelaskan cara Jarvies mendeteksi dan merender pesan meeting dengan benar.

---

## Cara Mendeteksi Pesan Meeting

Saat Jarvies fetch pesan tiket via:

```
GET /api/tickets/{ticketId}/messages
```

Setiap pesan di array `data` memiliki field `message_type`. Pesan meeting memiliki:

```json
{
  "message_type": "meeting"
}
```

Cara deteksi di kode:

```js
if (message.message_type === 'meeting') {
  // render sebagai meeting card
}
```

---

## Struktur JSON Pesan Meeting

Field `message_body` (atau `message` tergantung mapping Jarvies) berisi JSON string dengan struktur:

```json
{
  "_type": "meeting",
  "title": "Diskusi Solusi",
  "scheduled_at": "2026-05-16 15:57",
  "duration_minutes": 60,
  "link": "https://meet.google.com/abc-defg-hij",
  "agenda": "Pembahasan error modul purchasing",
  "ended_at": "2026-05-16 17:02"
}
```

### Penjelasan Field

| Field | Tipe | Keterangan |
|---|---|---|
| `_type` | string | Selalu `"meeting"` — penanda tipe pesan |
| `title` | string | Judul meeting |
| `scheduled_at` | string | Waktu mulai yang dijadwalkan (`YYYY-MM-DD HH:mm`, WIB) |
| `duration_minutes` | number | Durasi rencana dalam menit |
| `link` | string \| null | URL meeting (Google Meet, Zoom, Teams, dll.) — bisa kosong |
| `agenda` | string | Agenda / deskripsi meeting — bisa kosong |
| `ended_at` | string \| null | Waktu meeting diakhiri (`YYYY-MM-DD HH:mm`, WIB). `null` = meeting masih berlangsung |

> **Catatan:** `link` dan `agenda` bersifat opsional — cek null/empty sebelum ditampilkan.

---

## Status Meeting

| Kondisi | Status | Keterangan |
|---|---|---|
| `ended_at` = `null` | **Ongoing** | Meeting sedang berlangsung |
| `ended_at` terisi | **Ended** | Meeting sudah selesai |

---

## Contoh Render di Jarvies

### Tampilan yang Disarankan

```
┌─────────────────────────────────────────────┐
│  📅  Meeting — Diskusi Solusi                │
│                                             │
│  🕐  16 Mei 2026, 15:57 WIB  ·  1 jam       │
│  🔗  meet.google.com/abc-defg-hij  [klik]   │
│  📝  Pembahasan error modul purchasing       │
│                                             │
│  Status:  🟠 Ongoing  /  ✅ Selesai (1j 5m) │
└─────────────────────────────────────────────┘
```

### Implementasi (Contoh JavaScript/React)

```js
function parseMeeting(messageBody) {
  try {
    const d = JSON.parse(messageBody);
    if (d && d._type === 'meeting') return d;
  } catch (_) {}
  return null;
}

function formatDuration(minutes) {
  const h = Math.floor(minutes / 60);
  const m = minutes % 60;
  if (h && m) return `${h}j ${m}m`;
  if (h)      return `${h} jam`;
  return `${m} menit`;
}

function renderMeetingCard(message) {
  const d = parseMeeting(message.message_body);
  if (!d) return null;

  const scheduledAt = new Date(d.scheduled_at);
  const isEnded     = !!d.ended_at;

  // Hitung durasi aktual jika sudah selesai
  let durationLabel = formatDuration(d.duration_minutes);
  if (isEnded && d.ended_at) {
    const endedAt  = new Date(d.ended_at);
    const diffMin  = Math.round((endedAt - scheduledAt) / 60000);
    durationLabel  = formatDuration(diffMin);
  }

  const timeLabel = scheduledAt.toLocaleString('id-ID', {
    timeZone: 'Asia/Jakarta',
    day: '2-digit', month: 'short', year: 'numeric',
    hour: '2-digit', minute: '2-digit', hour12: false
  }) + ' WIB';

  return {
    title:        d.title ?? 'Meeting',
    time:         timeLabel,
    duration:     durationLabel,
    link:         d.link   || null,
    agenda:       d.agenda || null,
    status:       isEnded ? 'ended' : 'ongoing',
  };
}
```

### Render Kondisional

```js
function MeetingCard({ message }) {
  const meeting = renderMeetingCard(message);
  if (!meeting) return null;

  return (
    <div className="meeting-card">
      <div className="meeting-header">
        <span>📅</span>
        <strong>{meeting.title}</strong>
      </div>

      <div className="meeting-time">
        🕐 {meeting.time} · {meeting.duration}
      </div>

      {meeting.link && (
        <a href={meeting.link} target="_blank" rel="noopener noreferrer">
          🔗 {meeting.link}
        </a>
      )}

      {meeting.agenda && (
        <div className="meeting-agenda">📝 {meeting.agenda}</div>
      )}

      <div className={`meeting-status ${meeting.status}`}>
        {meeting.status === 'ended'
          ? '✅ Meeting Selesai'
          : '🟠 Meeting Berlangsung'}
      </div>
    </div>
  );
}
```

---

## Integrasi dengan Daftar Pesan

Pesan meeting **tidak perlu dibalas** oleh customer — ini adalah notifikasi jadwal dari helpdesk. Jarvies cukup menampilkannya sebagai kartu informasi (non-interactive), kecuali ingin menambahkan tombol "Tambah ke Kalender".

### Contoh Filter di Loop Pesan

```js
messages.forEach(message => {
  if (message.message_type === 'meeting') {
    renderMeetingCard(message);  // render sebagai kartu
  } else if (message.message_type === 'internal_note') {
    // internal note tidak ditampilkan ke customer
  } else {
    renderChatBubble(message);   // render bubble biasa
  }
});
```

---

## Alur Lengkap di EcoSystem

```
Helpdesk klik tombol "Meeting" di EcoSystem
        ↓
Isi form: Judul, Tanggal, Waktu, Durasi, Link, Agenda
        ↓
POST /api/tickets/{id}/messages
  { message_type: "meeting", meeting_title: "...", ... }
        ↓
EcoSystem simpan ke ticket_message:
  message      = JSON string
  message_type = "meeting"  (via resolveMessageType())
  sender_type  = "employee"
        ↓
Helpdesk klik "End Meeting" saat meeting selesai
        ↓
PATCH /api/tickets/{id}/messages/{msgId}/end-meeting
  → JSON diupdate: ended_at = "YYYY-MM-DD HH:mm"
        ↓
GET /api/tickets/{id}/messages
  → message_type: "meeting"
  → message_body: { ..., "ended_at": "..." }
```

---

## Checklist Implementasi Jarvies

- [ ] Deteksi `message_type === 'meeting'` saat render daftar pesan
- [ ] Parse `message_body` sebagai JSON jika tipe meeting
- [ ] Tampilkan title, waktu, durasi dalam format yang rapi
- [ ] Tampilkan `link` sebagai URL yang bisa diklik (jika ada)
- [ ] Tampilkan `agenda` sebagai teks (jika ada)
- [ ] Bedakan status: **Ongoing** (`ended_at = null`) vs **Ended** (`ended_at` terisi)
- [ ] Jika Ended: hitung durasi aktual dari `scheduled_at` ke `ended_at`
- [ ] Pesan meeting **tidak perlu** input balas dari customer
- [ ] Internal note (`is_internal_note = true`) tetap disembunyikan dari customer

---

## Catatan Teknis

- Semua timestamp (`scheduled_at`, `ended_at`) dalam format **WIB (Asia/Jakarta)**, string `YYYY-MM-DD HH:mm`
- Field `link` disimpan tanpa encoding — langsung bisa dipakai sebagai `href`
- `message_html` untuk pesan meeting bernilai `null` — jangan gunakan field ini untuk meeting
- `sender_type` selalu `"employee"` — meeting hanya dibuat oleh helpdesk

---

*Dibuat oleh: EcoSystem Team*  
*Tanggal: 2026-05-16*
