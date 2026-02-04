<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Ticket;
use App\Models\TicketReply;
use App\Http\Requests\StoreTicketRequest; // 🔥 Custom Request
use App\Http\Requests\ReplyTicketRequest; // 🔥 Custom Request
use App\Http\Resources\TicketResource;    // 🔥 Custom Resource
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use App\Notifications\TicketReplyNotification;


class TicketController extends Controller
{
    /**
     * 🟢 1. List Tickets
     */
    public function index()
    {
        $user = Auth::user();
        $query = Ticket::with('user')->latest();

        // Admin sees all, Customer sees own
        if (!$user->hasRole(['employee', 'super-admin'])) {
            $query->where('user_id', $user->id);
        }

        return TicketResource::collection($query->paginate(10));
    }

    /**
     * 🟢 2. Create Ticket
     */
    public function store(StoreTicketRequest $request)
    {
        // 1. Create Ticket
        $ticket = Ticket::create([
            'user_id'       => Auth::id(),
            'ticket_number' => 'TKT-' . strtoupper(Str::random(8)),
            'subject'       => $request->subject,
            'priority'      => $request->priority,
            'status'        => 'open',
        ]);

        // 2. Handle Attachment
        $attachmentPath = null;
        if ($request->hasFile('attachment')) {
            $attachmentPath = $request->file('attachment')->store('tickets', 'public');
        }

        // 3. Create First Message
        TicketReply::create([
            'ticket_id'  => $ticket->id,
            'user_id'    => Auth::id(),
            'message'    => $request->message,
            'attachment' => $attachmentPath
        ]);

        return response()->json([
            'message' => 'Ticket created successfully',
            'data'    => new TicketResource($ticket)
        ], 201);
    }

    /**
     * 🟢 3. Show Single Ticket (With Replies)
     */
    public function show($id)
    {
        // Load relationships: User (owner) and Replies (chat history)
        $ticket = Ticket::with(['replies.user', 'user'])->findOrFail($id);

        // Security Check: Prevent seeing other's tickets
        if (!Auth::user()->hasRole(['employee', 'super-admin']) && $ticket->user_id !== Auth::id()) {
            return response()->json(['message' => 'Unauthorized access to this ticket'], 403);
        }

        return new TicketResource($ticket);
    }

    /**
     * 🟢 4. Reply to Ticket
     */
    public function reply(ReplyTicketRequest $request, $id)
    {
        $ticket = Ticket::findOrFail($id);

        // Security Check
        if (!Auth::user()->hasRole(['employee', 'super-admin']) && $ticket->user_id !== Auth::id()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        // Handle Attachment
        $attachmentPath = null;
        if ($request->hasFile('attachment')) {
            $attachmentPath = $request->file('attachment')->store('tickets', 'public');
        }

        // Save Reply
        $reply = TicketReply::create([
            'ticket_id'  => $ticket->id,
            'user_id'    => Auth::id(),
            'message'    => $request->message,
            'attachment' => $attachmentPath
        ]);

        // Update Ticket Status
        if (Auth::user()->hasRole(['employee', 'super-admin'])) {
            $ticket->update(['status' => 'replied']);
            $ticket->user->notify(new TicketReplyNotification($ticket));
        } else {
            $ticket->update(['status' => 'open']);
        }

        return response()->json([
            'message' => 'Reply sent successfully',
            'data'    => new \App\Http\Resources\TicketReplyResource($reply)
        ]);
    }

    /**
     * 🔴 5. Close Ticket
     */
    public function close($id)
    {
         $ticket = Ticket::findOrFail($id);

         // Security Check
         if (!Auth::user()->hasRole(['employee', 'super-admin']) && $ticket->user_id !== Auth::id()) {
            return response()->json(['message' => 'Unauthorized'], 403);
         }

         $ticket->update(['status' => 'closed']);

         return response()->json(['message' => 'Ticket closed successfully']);
    }
}
