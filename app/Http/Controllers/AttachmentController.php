<?php

namespace App\Http\Controllers;

use App\Models\Ticket;
use App\Models\TicketAttachment;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * AttachmentController
 *
 * Proxy route untuk mengambil attachment dari Microsoft Graph on-demand.
 * File tidak pernah disimpan lokal — metadata (graph_message_id, graph_attachment_id)
 * disimpan di ticket_attachment oleh EcoSystem processInbox/sendTicketReply.
 *
 * Route: GET /attachments/{id}  (middleware: jarvies.auth)
 */
class AttachmentController extends Controller
{
    public function show($id)
    {
        $sessionUser = session('user');
        if (!$sessionUser) {
            abort(401, 'Unauthorized');
        }

        $attachment = TicketAttachment::findOrFail($id);

        // Customer hanya bisa akses attachment dari tiket miliknya
        if (($sessionUser['role']['id'] ?? 0) == 3) {
            $ticket = Ticket::find($attachment->ticket_id);
            if (!$ticket || $ticket->customer_id != $sessionUser['id']) {
                abort(403, 'Forbidden');
            }
        }

        // Jika ada graph_message_id + graph_attachment_id → fetch dari Graph
        if ($attachment->graph_message_id && $attachment->graph_attachment_id) {
            return $this->fetchFromGraph($attachment);
        }

        // Fallback: file lokal lama
        if ($attachment->file_path) {
            return redirect('/storage/' . $attachment->file_path);
        }

        // Fallback: link langsung
        if ($attachment->link_url) {
            return redirect($attachment->link_url);
        }

        abort(404, 'Attachment not found');
    }

    /**
     * Fetch file dari Microsoft Graph menggunakan app credentials (client_credentials),
     * lalu stream ke browser.
     */
    private function fetchFromGraph(TicketAttachment $attachment)
    {
        $tenantId     = env('MS_TENANT_ID');
        $clientId     = env('MS_CLIENT_ID');
        $clientSecret = env('MS_CLIENT_SECRET');
        $sender       = env('MS_SENDER_EMAIL');

        if (!$tenantId || !$clientId || !$clientSecret || !$sender) {
            Log::error('AttachmentController: MS credentials not configured');
            abort(503, 'Attachment service not configured');
        }

        // Ambil app access token via client_credentials
        $tokenRes = Http::asForm()->post(
            "https://login.microsoftonline.com/{$tenantId}/oauth2/v2.0/token",
            [
                'grant_type'    => 'client_credentials',
                'client_id'     => $clientId,
                'client_secret' => $clientSecret,
                'scope'         => 'https://graph.microsoft.com/.default',
            ]
        );

        if (!$tokenRes->successful()) {
            Log::error('AttachmentController: failed to get app token', [
                'status' => $tokenRes->status(),
            ]);
            abort(502, 'Failed to authenticate with Microsoft');
        }

        $accessToken = $tokenRes->json('access_token');
        $baseUrl     = rtrim(env('GRAPH_BASE_URL', 'https://graph.microsoft.com/v1.0'), '/');

        $res = Http::withToken($accessToken)
            ->get("{$baseUrl}/users/{$sender}/messages/{$attachment->graph_message_id}/attachments/{$attachment->graph_attachment_id}");

        if (!$res->successful()) {
            Log::error('AttachmentController: attachment fetch failed', [
                'id'     => $attachment->id,
                'status' => $res->status(),
                'body'   => $res->body(),
            ]);
            abort(404, 'Attachment not found in Microsoft');
        }

        $data         = $res->json();
        $contentBytes = base64_decode($data['contentBytes'] ?? '');
        $contentType  = $data['contentType'] ?? ($attachment->mime_type ?? 'application/octet-stream');
        $fileName     = $data['name'] ?? ($attachment->file_name ?? 'attachment');
        $isInline     = $attachment->is_inline ?? false;
        $disposition  = $isInline ? 'inline' : 'attachment';

        return response($contentBytes, 200)
            ->header('Content-Type', $contentType)
            ->header('Content-Disposition', "{$disposition}; filename=\"{$fileName}\"")
            ->header('Cache-Control', 'private, max-age=3600')
            ->header('Content-Length', strlen($contentBytes));
    }
}
