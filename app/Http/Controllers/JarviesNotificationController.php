<?php

namespace App\Http\Controllers;

use App\Models\AuthUser;
use App\Models\Notification;
use App\Models\NotificationSound;
use Illuminate\Http\Request;

class JarviesNotificationController extends Controller
{
    private function customerId(): ?int
    {
        $id = session('user.id');
        return $id ? (int) $id : null;
    }

    private function authUserId(): ?int
    {
        $id = session('user.auth_user_id');
        return $id ? (int) $id : null;
    }

    private function guardCustomer()
    {
        if (!$this->customerId()) {
            abort(response()->json(['success' => false, 'message' => 'Unauthorized'], 401));
        }
    }

    /**
     * GET /api/notifications
     * 20 notifikasi terbaru + unread count untuk bell dropdown.
     */
    public function index()
    {
        $this->guardCustomer();
        $customerId = $this->customerId();

        $notifications = Notification::forCustomer($customerId)
            ->orderBy('created_at', 'desc')
            ->limit(20)
            ->get()
            ->map(fn($n) => [
                'id'         => $n->id,
                'type'       => $n->type,
                'ticket_id'  => $n->ticket_id,
                'from_name'  => $n->from_name,
                'preview'    => $n->preview,
                'link'       => $n->link,
                'is_read'    => $n->is_read,
                'created_at' => $n->created_at?->diffForHumans(),
            ]);

        $unreadCount = Notification::forCustomer($customerId)->unread()->count();

        return response()->json([
            'success'      => true,
            'data'         => $notifications,
            'unread_count' => $unreadCount,
        ]);
    }

    /**
     * GET /api/notifications/unread-count
     * Polling ringan untuk badge bell.
     */
    public function unreadCount()
    {
        $this->guardCustomer();

        $count = Notification::forCustomer($this->customerId())->unread()->count();

        return response()->json(['success' => true, 'count' => $count]);
    }

    /**
     * PUT /api/notifications/{id}/read
     */
    public function markRead(int $id)
    {
        $this->guardCustomer();

        Notification::where('id', $id)
            ->where('customer_id', $this->customerId())
            ->update(['is_read' => true, 'read_at' => now()]);

        return response()->json(['success' => true]);
    }

    /**
     * PUT /api/notifications/read-all
     */
    public function markAllRead()
    {
        $this->guardCustomer();

        Notification::forCustomer($this->customerId())
            ->unread()
            ->update(['is_read' => true, 'read_at' => now()]);

        return response()->json(['success' => true]);
    }

    /**
     * DELETE /api/notifications/bulk-delete
     * Hapus semua notifikasi yang sudah dibaca.
     */
    public function bulkDelete()
    {
        $this->guardCustomer();

        Notification::forCustomer($this->customerId())
            ->where('is_read', true)
            ->delete();

        return response()->json(['success' => true]);
    }

    /**
     * GET /api/notification-sounds
     * Daftar semua sound + sound yang sedang dipilih user.
     */
    public function sounds()
    {
        $this->guardCustomer();

        $sounds = NotificationSound::orderByDesc('is_default')
            ->orderBy('name')
            ->get()
            ->map(fn($s) => [
                'id'         => $s->id,
                'name'       => $s->name,
                'url'        => '/sounds/' . $s->filename,
                'is_default' => $s->is_default,
            ]);

        $authUser        = AuthUser::find($this->authUserId());
        $selectedSoundId = $authUser?->notification_sound_id
            ?? NotificationSound::defaultId();

        return response()->json([
            'success'           => true,
            'data'              => $sounds,
            'selected_sound_id' => $selectedSoundId,
        ]);
    }

    /**
     * PUT /api/notification-sounds/preference
     * Simpan pilihan sound user.
     * Body: { "sound_id": 2 }  atau { "sound_id": null } untuk reset ke default.
     */
    public function saveSound(Request $request)
    {
        $this->guardCustomer();

        $soundId = $request->input('sound_id');

        if ($soundId !== null && !NotificationSound::find($soundId)) {
            return response()->json(['success' => false, 'message' => 'Sound not found.'], 422);
        }

        AuthUser::where('id', $this->authUserId())
            ->update(['notification_sound_id' => $soundId]);

        return response()->json(['success' => true]);
    }
}
