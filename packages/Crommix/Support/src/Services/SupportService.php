<?php

namespace Crommix\Support\Services;

use Crommix\Support\Models\Ticket;
use Crommix\Support\Models\TicketReply;
use Illuminate\Support\Facades\Auth;

class SupportService
{
    /**
     * Create a new support ticket.
     *
     * @param array<string, mixed> $data
     */
    public function createTicket(array $data): Ticket
    {
        return Ticket::create($data);
    }

    /**
     * Add a reply to a ticket.
     */
    public function addReply(Ticket $ticket, string $body, bool $isInternal = false): TicketReply
    {
        $reply = $ticket->replies()->create([
            'user_id'     => Auth::id(),
            'body'        => $body,
            'is_internal' => $isInternal,
        ]);

        // Move ticket to in_progress on first staff reply if still open.
        if ($ticket->status === 'open' && !$isInternal) {
            $ticket->update(['status' => 'in_progress']);
        }

        return $reply;
    }

    /**
     * Assign a ticket to a staff member.
     */
    public function assignTicket(Ticket $ticket, int $userId): Ticket
    {
        $ticket->update(['assigned_to' => $userId]);

        return $ticket->refresh();
    }

    /**
     * Mark a ticket as resolved.
     */
    public function resolveTicket(Ticket $ticket): Ticket
    {
        $ticket->update([
            'status'      => 'resolved',
            'resolved_at' => now(),
        ]);

        return $ticket->refresh();
    }

    /**
     * Close a resolved ticket.
     */
    public function closeTicket(Ticket $ticket): Ticket
    {
        $ticket->update([
            'status'    => 'closed',
            'closed_at' => now(),
        ]);

        return $ticket->refresh();
    }
}
