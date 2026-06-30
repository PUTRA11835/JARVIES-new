<?php

namespace App\Services;

use App\Models\StagingTicket;
use Illuminate\Support\Facades\Log;

/**
 * StagingTicketService (JARVIES — Customer Side)
 *
 * Tanggung jawab:
 * - createFromWeb(): simpan submission dari form JARVIES ke tabel staging_tickets
 *
 * Approve / reject dilakukan di EcoSystem (Employee side) via StagingTicketController.
 * JARVIES hanya READ staging untuk menampilkan status ke customer.
 */
class StagingTicketService
{
    /**
     * Simpan tiket baru dari form web customer ke tabel staging.
     *
     * @param  array  $data            Validated data dari request (description, ticket_priority, body, cc_emails)
     * @param  int    $customerId      ID customer dari session
     * @param  string|null $customerEmail  Email customer (untuk referensi)
     * @return StagingTicket
     */
    /**
     * Ekstrak base64 inline image dari HTML, simpan sebagai file di storage,
     * dan ganti src dengan URL yang bisa diakses via route.
     * Ini menghindari menyimpan data besar di DB (mencegah max_allowed_packet).
     *
     * @return array{html: string, saved_images: string[]}
     */
    private function extractAndSaveImages(?string $html, int $stagingId): array
    {
        if (!$html) return ['html' => $html, 'saved_images' => []];

        $savedImages = [];
        $dir = storage_path('app/staging-images/' . $stagingId);

        $processedHtml = preg_replace_callback(
            '/src="data:([^;]+);base64,([^"]+)"/i',
            function ($matches) use ($dir, $stagingId, &$savedImages) {
                $mimeType  = $matches[1]; // e.g. image/png
                $base64    = $matches[2];
                $ext       = explode('/', $mimeType)[1] ?? 'png';
                $ext       = preg_replace('/[^a-z0-9]/i', '', $ext); // sanitize
                $uuid      = \Illuminate\Support\Str::uuid()->toString();
                $filename  = $uuid . '.' . $ext;

                if (!is_dir($dir)) mkdir($dir, 0755, true);
                file_put_contents($dir . '/' . $filename, base64_decode($base64));
                $savedImages[] = $filename;

                $url = route('tickets.staging.image.serve', ['id' => $stagingId, 'filename' => $uuid . '.' . $ext]);
                return 'src="' . $url . '"';
            },
            $html
        );

        return ['html' => $processedHtml, 'saved_images' => $savedImages];
    }

    public function createFromWeb(array $data, int $customerId, ?string $customerEmail = null, ?string $senderName = null, array $fileNames = []): StagingTicket
    {
        // Simpan dulu tanpa body agar dapat ID staging
        $staging = StagingTicket::create([
            'customer_id'        => $customerId,
            'end_customer_id'    => isset($data['end_customer_id']) ? (int) $data['end_customer_id'] : null,
            'description'        => $data['description'],
            'body'               => null, // akan diupdate setelah proses gambar
            'cc_emails'          => isset($data['cc_emails'])
                                       ? (is_array($data['cc_emails']) ? json_encode($data['cc_emails']) : $data['cc_emails'])
                                       : null,
            'name'               => $data['name'] ?? null,
            'no_hp'              => $data['no_hp'] ?? null,
            'module'             => $data['module'] ?? null,
            'client'             => $data['client'] ?? null,
            'ticket_priority'    => $data['ticket_priority'] ?? 'Medium',
            'ticket_type'        => $data['ticket_type'] ?? null,
            'scale'              => $data['scale'] ?? null,
            'status'             => 'unvalidated',
            'channel'            => 'web',
            'submitted_by_email' => $customerEmail,
            'sender_name'        => $senderName,
            'email_thread_id'    => $data['email_thread_id'] ?? null,
            'email_message_id'   => $data['email_message_id'] ?? null,
            'customer_thread_id' => $data['email_thread_id'] ?? null,
            'attachment_names'   => !empty($fileNames) ? json_encode($fileNames) : null,
        ]);

        // Proses gambar setelah dapat staging ID, lalu update body
        $rawBody = $data['body_html'] ?? $data['body'] ?? null;
        if ($rawBody) {
            $result = $this->extractAndSaveImages($rawBody, $staging->id);
            $staging->body = $result['html'];
            $staging->save();
        }

        Log::info('StagingTicketService: new staging ticket created', [
            'staging_id'  => $staging->id,
            'customer_id' => $customerId,
            'sender_name' => $senderName,
            'priority'    => $staging->ticket_priority,
        ]);

        return $staging;
    }

    /**
     * Ambil semua staging ticket milik satu customer,
     * terurut dari terbaru.
     *
     * @param  int  $customerId
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getByCustomer(int $customerId, ?string $submittedByEmail = null)
    {
        return StagingTicket::where('customer_id', $customerId)
            ->when($submittedByEmail, fn ($q) => $q->where('submitted_by_email', $submittedByEmail))
            ->orderBy('created_at', 'desc')
            ->get();
    }

    /**
     * Ambil satu staging ticket milik customer (keamanan: pastikan customer_id cocok).
     *
     * @param  int  $stagingId
     * @param  int  $customerId
     * @return StagingTicket|null
     */
    public function findForCustomer(int $stagingId, int $customerId): ?StagingTicket
    {
        return StagingTicket::where('id', $stagingId)
            ->where('customer_id', $customerId)
            ->with('ticket')
            ->first();
    }
}
