<?php

namespace App\Http\Controllers;

use App\Models\StagingTicket;
use App\Models\Ticket;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class EcosystemWebhookController extends Controller
{
    /**
     * POST /api/ecosystem/staging-approved
     *
     * Dipanggil Ecosystem setelah staging ticket divalidasi.
     * Menyinkronkan status staging dan membuat record ticket di JARVIES DB.
     */
    public function stagingApproved(Request $request)
    {
        $request->validate([
            'customer_id'        => 'required|integer',
            'staging_description'=> 'required|string',
            'ticket_id'          => 'required|integer',
            'ticket_number'      => 'required|string',
            'description'        => 'required|string',
            'status'             => 'required|string',
            'ticket_priority'    => 'nullable|string',
            'ticket_type'        => 'nullable|string',
            'channel'            => 'nullable|string',
            'submitted_by_email' => 'nullable|email',
            'submitted_by_name'  => 'nullable|string',
            'start_date'         => 'nullable|string',
        ]);

        $customerId  = (int) $request->customer_id;
        $ticketId    = (int) $request->ticket_id;
        $stagingDesc = $request->staging_description;

        // 1. Update staging ticket JARVIES yang cocok (customer + description + belum divalidasi)
        $staging = StagingTicket::where('customer_id', $customerId)
            ->whereRaw('LOWER(description) = LOWER(?)', [$stagingDesc])
            ->where('status', 'unvalidated')
            ->whereNull('ticket_id')
            ->first();

        if ($staging) {
            $staging->update([
                'status'       => 'approved',
                'ticket_id'    => $ticketId,
                'validated_at' => now(),
            ]);
            Log::info('EcosystemWebhook@stagingApproved: staging updated', [
                'staging_id' => $staging->id,
                'ticket_id'  => $ticketId,
            ]);
        } else {
            Log::warning('EcosystemWebhook@stagingApproved: matching staging not found', [
                'customer_id' => $customerId,
                'description' => $stagingDesc,
            ]);
        }

        // 2. Upsert ticket record di JARVIES DB
        $ticket = Ticket::updateOrCreate(
            ['ticket_id' => $ticketId],
            [
                'ticket_number'      => $request->ticket_number,
                'customer_id'        => $customerId,
                'description'        => $request->description,
                'status'             => $request->status,
                'ticket_priority'    => $request->ticket_priority,
                'ticket_type'        => $request->ticket_type,
                'channel'            => $request->channel ?? 'web',
                'submitted_by_email' => $request->submitted_by_email,
                'submitted_by_name'  => $request->submitted_by_name,
                'start_date'         => $request->start_date,
            ]
        );

        Log::info('EcosystemWebhook@stagingApproved: ticket upserted', [
            'ticket_id'     => $ticket->ticket_id,
            'ticket_number' => $ticket->ticket_number,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Ticket synced successfully',
            'data'    => ['ticket_id' => $ticket->ticket_id],
        ]);
    }

    /**
     * POST /api/ecosystem/staging-rejected
     *
     * Dipanggil Ecosystem setelah staging ticket ditolak.
     */
    public function stagingRejected(Request $request)
    {
        $request->validate([
            'customer_id'        => 'required|integer',
            'staging_description'=> 'required|string',
            'rejection_reason'   => 'required|string',
        ]);

        $staging = StagingTicket::where('customer_id', (int) $request->customer_id)
            ->whereRaw('LOWER(description) = LOWER(?)', [$request->staging_description])
            ->where('status', 'unvalidated')
            ->whereNull('ticket_id')
            ->first();

        if ($staging) {
            $staging->update([
                'status'           => 'rejected',
                'rejection_reason' => $request->rejection_reason,
                'validated_at'     => now(),
            ]);
            Log::info('EcosystemWebhook@stagingRejected: staging updated', ['staging_id' => $staging->id]);
        }

        return response()->json(['success' => true, 'message' => 'Rejection synced']);
    }
}
