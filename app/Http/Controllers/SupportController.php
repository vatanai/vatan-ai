<?php

namespace App\Http\Controllers;

use App\Models\SupportTicket;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;

class SupportController extends Controller
{
    public function index(Request $request): View
    {
        $tickets = $request->user()
            ? SupportTicket::where('user_id', $request->user()->id)->latest('last_message_at')->latest()->get()
            : collect();

        return view('support.index', compact('tickets'));
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'subject' => ['required', 'string', 'max:180'],
            'category' => ['required', 'in:image,video,payment,account,other'],
            'body' => ['required', 'string', 'min:5', 'max:5000'],
        ]);

        $ticket = SupportTicket::create([
            'ticket_number' => 'VT-' . now()->format('ymd') . '-' . strtoupper(Str::random(5)),
            'user_id' => $request->user()->id,
            'channel' => 'site',
            'category' => $data['category'],
            'subject' => $data['subject'],
            'status' => 'open',
            'last_message_preview' => Str::limit($data['body'], 180),
            'last_message_at' => now(),
        ]);
        $ticket->messages()->create(['sender_type' => 'user', 'sender_id' => $request->user()->id, 'body' => $data['body']]);

        return redirect()->route('support.tickets.show', $ticket)->with('success', 'درخواست شما ثبت شد؛ تیم وطن در سریع‌ترین زمان پاسخ می‌دهد.');
    }

    public function show(Request $request, SupportTicket $ticket): View
    {
        abort_unless($ticket->user_id === $request->user()->id, 404);
        $ticket->load('messages');
        $ticket->messages()->where('sender_type', 'admin')->whereNull('read_at')->update(['read_at' => now()]);
        return view('support.show', compact('ticket'));
    }

    public function reply(Request $request, SupportTicket $ticket): RedirectResponse
    {
        abort_unless($ticket->user_id === $request->user()->id, 404);
        $data = $request->validate(['body' => ['required', 'string', 'min:2', 'max:5000']]);
        $ticket->messages()->create(['sender_type' => 'user', 'sender_id' => $request->user()->id, 'body' => $data['body']]);
        $ticket->update(['status' => 'open', 'last_message_preview' => Str::limit($data['body'], 180), 'last_message_at' => now(), 'closed_at' => null]);
        return back()->with('success', 'پاسخ شما ثبت شد.');
    }
}
