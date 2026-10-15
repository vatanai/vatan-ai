<?php

namespace Vatan\Content\Http\Controllers;

use Illuminate\Http\Request;
use Vatan\Content\Models\MusicTrack;

class MusicController extends Controller
{
    public function index()
    {
        return view('cf::music.index', [
            'cfTitle' => 'کتابخانه‌ی موسیقی', 'cfIcon' => 'fa-music', 'cfHelp' => 'music',
            'cfSubtitle' => 'فقط موسیقی با مجوز وارد تولید می‌شود. کلاد موسیقی نمی‌سازد؛ فقط از همین کتابخانه انتخاب می‌کند.',
            'tracks' => MusicTrack::where('workspace_id', $this->ws())->orderByDesc('license_status')->orderBy('id')->get(),
        ]);
    }

    public function update(Request $request, MusicTrack $track)
    {
        abort_unless($track->workspace_id === $this->ws(), 404);
        $d = $request->validate([
            'title' => 'nullable|string|max:255', 'mood' => 'nullable|string|max:255', 'genre' => 'nullable|string|max:255',
            'license_status' => 'nullable|in:approved,unknown,forbidden', 'license_note' => 'nullable|string|max:1000',
        ]);
        if (isset($d['license_status'])) {
            $d['commercial_ok'] = $d['license_status'] === 'approved';
        }
        $track->update(array_filter($d, fn ($v) => $v !== null));

        return back()->with('success', 'موسیقی به‌روز شد.');
    }
}
