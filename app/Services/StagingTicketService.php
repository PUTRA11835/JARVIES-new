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
    public function createFromWeb(array $data, int $customerId, ?string $customerEmail = null, ?string $senderName = null): StagingTicket
    {
        $staging = StagingTicket::create([
            'customer_id'        => $customerId,
            'description'        => $data['description'],
            'body'               => $data['body'] ?? null,
            'cc_emails'          => $data['cc_emails'] ?? null,
            'name'               => $data['name'] ?? null,
            'no_hp'              => $data['no_hp'] ?? null,
            'module'             => $data['module'] ?? null,
            'client'             => $data['client'] ?? null,
            'ticket_priority'    => $data['ticket_priority'] ?? 'Medium',
            'status'             => 'unvalidated',
            'channel'            => 'web',
            'submitted_by_email' => $customerEmail,
            'sender_name'        => $senderName,
            'email_thread_id'    => $data['email_thread_id'] ?? null,
            'email_message_id'   => $data['email_message_id'] ?? null,
            'customer_thread_id' => $data['email_thread_id'] ?? null,
        ]);

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
    public function getByCustomer(int $customerId)
    {
        return StagingTicket::where('customer_id', $customerId)
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
