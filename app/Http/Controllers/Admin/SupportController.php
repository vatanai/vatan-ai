<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SupportTicket;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;

class SupportController extends Controller
{
    public function index(Request $request): View
    {
        $query = SupportTicket::with('user')->latest('last_message_at')->latest();
        if ($request->filled('status')) $query->where('status', $request->string('status'));
        if ($request->filled('search')) {
            $search = '%' . $request->string('search') . '%';
            $query->where(function ($builder) use ($search) {
                $builder->where('ticket_number', 'like', $search)->orWhere('subject', 'like', $search)->orWhereHas('user', fn ($user) => $user->where('name', 'like', $search)->orWhere('phone', 'like', $search)->orWhere('email', 'like', $search));
            });
        }
        $tickets = $query->paginate(25)->withQueryString();
        $stats = ['open' => SupportTicket::whereIn('status', ['open', 'pending'])->count(), 'answered' => SupportTicket::where('status', 'answered')->count(), 'closed' => SupportTicket::where('status', 'closed')->count()];
        return view('admin.support.index', compact('tickets', 'stats'));
    }

    public function show(SupportTicket $ticket): View
    {
        $ticket->load(['user', 'messages']);
        $admins = \App\Models\Admin::query()->where('is_active', true)->orderBy('name')->get();
        return view('admin.support.show', compact('ticket', 'admins'));
    }

    public function reply(Request $request, SupportTicket $ticket): RedirectResponse
    {
        $data = $request->validate(['body' => ['required', 'string', 'min:2', 'max:5000']]);
        $ticket->messages()->create(['sender_type' => 'admin', 'sender_id' => auth('admin')->id(), 'body' => $data['body']]);
        $ticket->update(['status' => 'answered', 'assigned_admin_id' => auth('admin')->id(), 'last_message_preview' => Str::limit($data['body'], 180), 'last_message_at' => now()]);
        return back()->with('success', 'پاسخ برای کاربر ارسال شد.');
    }

    public function update(Request $request, SupportTicket $ticket): RedirectResponse
    {
        $data = $request->validate(['status' => ['required', 'in:open,pending,answered,closed'], 'priority' => ['required', 'in:low,normal,high,urgent'], 'assigned_admin_id' => ['nullable', 'exists:admins,id']]);
        $ticket->update($data + ['closed_at' => $data['status'] === 'closed' ? now() : null]);
        return back()->with('success', 'وضعیت تیکت به‌روزرسانی شد.');
    }
}
