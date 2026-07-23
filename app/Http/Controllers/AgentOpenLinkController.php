<?php

namespace App\Http\Controllers;

use App\Models\AgentOpenLink;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class AgentOpenLinkController extends Controller
{
    public function show(string $token): RedirectResponse
    {
        $tokenHash = hash('sha256', $token);

        $openLink = DB::transaction(function () use ($tokenHash) {
            $link = AgentOpenLink::with('agentDevice')
                ->where('token_hash', $tokenHash)
                ->lockForUpdate()
                ->firstOrFail();

            abort_if($link->used_at !== null, 410, 'Este enlace ya fue utilizado.');
            abort_if($link->expires_at->isPast(), 410, 'Este enlace ya expiro.');
            abort_if(!$link->agentDevice || $link->agentDevice->revoked_at !== null, 403, 'Este equipo ya no esta autorizado.');

            $link->forceFill(['used_at' => now()])->save();

            return $link;
        });

        $user = User::findOrFail($openLink->user_id);
        Auth::login($user, (bool) $openLink->agentDevice->remember_web_session);

        if ($openLink->notification_id) {
            $user->notifications()
                ->where('id', $openLink->notification_id)
                ->first()
                ?->markAsRead();
        }

        return redirect()->to($openLink->target_url);
    }
}
