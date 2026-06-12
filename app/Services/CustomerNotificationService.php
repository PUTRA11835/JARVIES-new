<?php

namespace App\Services;

use App\Models\Notification;
use Illuminate\Support\Facades\Log;

/**
 * Kirim notifikasi ke customer Jarvies.
 *
 * Dipanggil dari mana saja yang bisa akses DB yang sama —
 * baik dari JARVIES sendiri maupun dari EcoSystem (shared DB).
 *
 * Contoh pemakaian dari EcoSystem saat helpdesk reply tiket:
 *
 *   CustomerNotificationService::notify(
 *       customerId : $ticket->customer_id,
 *       type       : 'ticket_reply',
 *       ticketId   : $ticket->id,
 *       fromName   : $senderName,
 *       preview    : Str::limit(strip_tags($messageText), 100),
 *       link       : '/tickets/' . $ticket->id,
 *   );
 */
class CustomerNotificationService
{
    /**
     * Type constants — harus konsisten antara JARVIES dan EcoSystem.
     */
    const TYPE_REPLY          = 'ticket_reply';
    const TYPE_STATUS_CHANGED = 'ticket_status_changed';
    const TYPE_CLOSED         = 'ticket_closed';
    const TYPE_ASSIGNED       = 'ticket_assigned';

    public static function notify(
        int    $customerId,
        string $type,
        int    $ticketId,
        string $fromName,
        string $preview,
        string $link
    ): ?Notification {
        try {
            return Notification::create([
                'customer_id' => $customerId,
                'type'        => $type,
                'ticket_id'   => $ticketId,
                'from_name'   => $fromName,
                'preview'     => $preview,
                'link'        => $link,
                'is_read'     => false,
            ]);
        } catch (\Exception $e) {
            Log::warning('CustomerNotificationService: failed to create notification', [
                'customer_id' => $customerId,
                'ticket_id'   => $ticketId,
                'type'        => $type,
                'error'       => $e->getMessage(),
            ]);
            return null;
        }
    }
}
