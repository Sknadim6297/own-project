<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Models\ActiveSession;
use App\Services\Terms\TermsAcceptanceService;
use App\Support\Auth\IntendedUrl;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;
use Illuminate\Validation\ValidationException;

class TermsAcceptanceController extends Controller
{
    public function __construct(
        protected TermsAcceptanceService $terms
    ) {
    }

    /**
     * Display the post-login Terms & Conditions acceptance screen.
     */
    public function show(Request $request)
    {
        $user = Auth::guard('user')->user();

        if (! $user) {
            return redirect()->route('login');
        }

        if ($this->terms->hasAcceptedCurrent($user)) {
            return redirect()->to(IntendedUrl::resolve('user'));
        }

        return view('frontend.terms.accept', [
            'termsVersion' => $this->terms->currentVersion(),
            'user' => $user,
        ]);
    }

    /**
     * Persist Terms acceptance for the authenticated customer only.
     */
    public function accept(Request $request)
    {
        $user = Auth::guard('user')->user();

        if (! $user) {
            if ($request->expectsJson() || $request->ajax()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Authentication required.',
                ], 401);
            }

            return redirect()->route('login');
        }

        $request->validate([
            'accepted' => ['accepted'],
        ], [
            'accepted.accepted' => 'You must confirm that you have read and agree to the Terms & Conditions.',
        ]);

        // Defense in depth: never accept on behalf of another account.
        if ((int) $request->input('user_id', $user->id) !== (int) $user->id) {
            throw ValidationException::withMessages([
                'accepted' => 'Unable to accept Terms for another account.',
            ]);
        }

        $this->terms->accept($user);

        $redirectUrl = IntendedUrl::resolve('user');

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json([
                'status' => 'success',
                'message' => 'Terms & Conditions accepted.',
                'redirect_url' => $redirectUrl,
            ]);
        }

        return redirect()
            ->to($redirectUrl)
            ->with('success', 'Thank you for accepting the Terms & Conditions.');
    }

    /**
     * Decline Terms: do not persist acceptance; sign the customer out safely.
     */
    public function decline(Request $request)
    {
        $user = Auth::guard('user')->user();

        if ($user) {
            ActiveSession::where('user_id', $user->id)
                ->where('session_id', Session::getId())
                ->delete();
        }

        Auth::guard('user')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json([
                'status' => 'declined',
                'message' => 'You must accept the Terms & Conditions to use YuktiOne.',
                'redirect_url' => route('login'),
            ]);
        }

        return redirect()
            ->route('login')
            ->with('info', 'You must accept the Terms & Conditions to use YuktiOne.');
    }
}
