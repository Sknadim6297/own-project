<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\ActiveSession;
use App\Models\OtpVerification;
use App\Services\OtpService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Session;
use Jenssegers\Agent\Agent;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

class LoginController extends Controller
{
    protected $otpService;
    
    public function __construct(OtpService $otpService)
    {
        $this->otpService = $otpService;
    }
    
    // Show login form
    public function showLoginForm()
    {
        if (Auth::guard('user')->check()) {
            return redirect()->route('home');
        }

        return view('frontend.login.login');
    }

    // Handle login request with device check
    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required|min:6',
        ]);

        try {
            $credentials = $request->only('email', 'password');
            $remember = $request->filled('remember');

            if (Auth::guard('user')->validate($credentials)) {
                $user = User::where('email', $request->email)->first();

                if (!$user->registration_completed && $user->password) {
                    $user->update([
                        'registration_completed' => true,
                        'password_set_at' => $user->password_set_at ?? now(),
                    ]);
                }

                if (!$user->password) {
                    return response()->json([
                        'status' => 'error',
                        'message' => 'Please complete your registration by setting a password first.',
                    ], 401);
                }

                $activeSessions = ActiveSession::where('user_id', $user->id)->count();
                if ($activeSessions > 0 && !$request->has('force_login')) {
                    return response()->json([
                        'status' => 'confirm_logout',
                        'message' => 'You are currently logged in on another device. Would you like to log out from all other devices?',
                    ]);
                }

                $bookingData = session('booking_data');
                $selectedServiceName = session('selected_service_name');

                Auth::guard('user')->login($user, $remember);
                $request->session()->regenerate();

                if ($bookingData) {
                    session(['booking_data' => $bookingData]);
                }
                if ($selectedServiceName) {
                    session(['selected_service_name' => $selectedServiceName]);
                }

                $newSessionId = Session::getId();
                if ($request->has('force_login')) {
                    $this->invalidateOtherSessions($user->id, $newSessionId);
                }

                $this->registerUserSession($user->id, $request);

                $redirectUrl = $this->resolvePostLoginRedirect($request);

                session()->flash('success', 'Login successful — Welcome back to Yuktione!');

                return response()->json([
                    'status' => 'success',
                    'message' => 'You are successfully logged in!',
                    'redirect_url' => $redirectUrl,
                ]);
            }

            return response()->json([
                'status' => 'error',
                'message' => 'Incorrect email or password. Please check your details and try again.',
            ], 401);
        } catch (Throwable $exception) {
            Log::error('Customer login failed unexpectedly.', [
                'exception_class' => get_class($exception),
                'exception_code' => $exception->getCode(),
                'exception_file' => $exception->getFile(),
                'exception_line' => $exception->getLine(),
            ]);

            return response()->json([
                'status' => 'error',
                'message' => "We couldn't complete your request right now. Please try again later.",
            ], 500);
        }
    }
    
    /**
     * Resolve where a customer should land after a successful login.
     * Prefer an intended/protected destination, then booking resume, else public Home.
     */
    private function resolvePostLoginRedirect(Request $request): string
    {
        foreach ([$request->input('redirect'), session()->pull('url.intended')] as $candidate) {
            $safe = $this->safeInternalRedirect(is_string($candidate) ? $candidate : null);
            if ($safe !== null) {
                return $safe;
            }
        }

        if (session()->has('booking_data')) {
            return route('user.booking');
        }

        return route('home');
    }

    /**
     * Allow only same-origin relative or absolute paths; block auth loops and externals.
     */
    private function safeInternalRedirect(?string $url): ?string
    {
        if ($url === null || $url === '') {
            return null;
        }

        $appUrl = rtrim((string) config('app.url'), '/');
        $path = $url;

        if (preg_match('#^https?://#i', $url) === 1) {
            $isSameOrigin = $appUrl !== ''
                && ($url === $appUrl || str_starts_with($url, $appUrl.'/'));

            if (! $isSameOrigin) {
                return null;
            }

            $path = substr($url, strlen($appUrl)) ?: '/';
        }

        if (! str_starts_with($path, '/')) {
            return null;
        }

        $pathOnly = parse_url($path, PHP_URL_PATH) ?: $path;
        $blockedPrefixes = ['/login', '/register', '/force-login', '/admin/login', '/professional/login'];

        foreach ($blockedPrefixes as $blocked) {
            if ($pathOnly === $blocked || str_starts_with($pathOnly, $blocked.'/')) {
                return null;
            }
        }

        return url($path);
    }

    /**
     * Register the user's current session
     */
    private function registerUserSession($userId, Request $request)
    {
        // Get current session ID
        $sessionId = Session::getId();
        
        // Get device information
        $agent = new Agent();
        $browser = $agent->browser();
        $platform = $agent->platform();
        $deviceInfo = "$browser on $platform";
        
        // Create a new session record (don't use updateOrCreate here)
        $session = ActiveSession::create([
            'user_id' => $userId,
            'session_id' => $sessionId,
            'device_info' => $deviceInfo,
            'ip_address' => $request->ip(),
            'last_active_at' => now(),
        ]);
        return $session;
    }

    private function invalidateOtherSessions($userId, $currentSessionId)
    {
        // Use a transaction for consistency
        DB::beginTransaction();
        try {
            // 1. Find all sessions other than the current one
            $otherSessions = ActiveSession::where('user_id', $userId)
                ->where('session_id', '!=', $currentSessionId)
                ->get();
            
            foreach ($otherSessions as $session) {

                DB::table('sessions')
                    ->where('id', $session->session_id)
                    ->delete();
                
                // Delete the active session record
                $session->delete();
            }
            
            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
        }
    }
    
    /**
     * Force login endpoint - handles the confirmation from SweetAlert
     */
    public function forceLogin(Request $request)
    {
        $request->merge(['force_login' => true]);
        return $this->login($request);
    }

    public function showRegisterForm()
    {
        return view('frontend.login.register');
    }

    /**
     * Save customer lead immediately when they click "Continue"
     * This captures all potential customers even if they don't complete registration
     */
    public function saveCustomerLead(Request $request)
    {
        $request->validate([
            'first_name' => 'required|string|max:255',  
            'last_name' => 'required|string|max:255',
            'phone' => 'required|string|max:20',
            'email' => 'required|email|max:255',
        ]);

        try {
            // Check if email already exists with completed registration
            $existingUser = User::where('email', $request->email)->first();
            if ($existingUser && $existingUser->registration_completed) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'This email address is already registered.'
                ], 422);
            }
            
            // If user exists but registration is incomplete, update the existing record
            if ($existingUser && !$existingUser->registration_completed) {
                $existingUser->update([
                    'name' => $request->first_name . ' ' . $request->last_name,
                    'phone' => $request->phone,
                    'registration_status' => 'started',
                    'updated_at' => now(),
                ]);
                
                $user = $existingUser;
            } else {
                // Create new user record with "started" status
                $user = User::create([
                    'name' => $request->first_name . ' ' . $request->last_name,
                    'email' => $request->email,
                    'phone' => $request->phone,
                    'password' => null, // No password yet
                    'email_verified' => false,
                    'registration_completed' => false,
                    'registration_status' => 'started',
                ]);
            }

            // Log this lead capture for admin tracking
            Log::info('Customer lead captured', [
                'user_id' => $user->id,
                'email' => $user->email,
                'name' => $user->name,
                'phone' => $user->phone,
                'status' => 'started'
            ]);

            return response()->json([
                'status' => 'success',
                'message' => 'Information saved. Proceeding to verification.',
                'user_id' => $user->id
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to save customer lead: ' . $e->getMessage(), [
                'email' => $request->email,
                'first_name' => $request->first_name,
                'last_name' => $request->last_name,
                'phone' => $request->phone
            ]);
            
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to save information. Please try again.'
            ], 500);
        }
    }

    /**
     * Send OTP verification code
     */
    public function sendOtp(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
        ]);

        // Find the user that should have been created in saveCustomerLead
        $user = User::where('email', $request->email)->first();
        if (!$user) {
            return response()->json([
                'status' => 'error',
                'message' => 'User record not found. Please try again.'
            ], 422);
        }

        // Check if email already exists with completed registration
        if ($user->registration_completed) {
            return response()->json([
                'status' => 'error',
                'message' => 'This email address is already registered.'
            ], 422);
        }

        try {
            // Update status to pending_otp
            $user->update(['registration_status' => 'pending_otp']);
            
            // Generate and send OTP
            $otp = $this->otpService->generate($request->email);
            
            return response()->json([
                'status' => 'success',
                'message' => 'OTP has been sent to your email address.'
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to send OTP: ' . $e->getMessage(), [
                'email' => $request->email
            ]);
            
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to send verification code. Please try again later.'
            ], 500);
        }
    }

    /**
     * Verify OTP code
     */
    public function verifyOtp(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'otp' => 'required|string|size:6', // Changed to string|size:6 to handle leading zeros
        ]);

        $result = $this->otpService->verify($request->email, $request->otp);

        if ($result['success']) {
            // Update user status to otp_verified
            $user = User::where('email', $request->email)->first();
            if ($user) {
                $user->update([
                    'email_verified' => true,
                    'registration_status' => 'otp_verified'
                ]);
            }
            
            return response()->json([
                'status' => 'success',
                'message' => $result['message']
            ]);
        }

        return response()->json([
            'status' => 'error',
            'message' => $result['message'],
            'error_type' => $result['error_type'] ?? 'unknown'
        ], 422);
    }
    
    /**
     * Register a new user after OTP verification
     */
    public function register(Request $request)
    {
        // Validation - allow email to exist if registration is incomplete
        $validated = $request->validate([
            'first_name' => 'required|string|max:255',
            'last_name'  => 'required|string|max:255',
            'phone'      => 'required|string|min:10|max:15',
            'email'      => 'required|email',
            'password'   => 'required|min:6|confirmed',
            'otp'        => 'required|string|size:6', // Changed to string|size:6
        ]);
        
        // Check if email already exists with completed registration
        $existingUser = User::where('email', $request->email)->first();
        if ($existingUser && $existingUser->registration_completed) {
            return response()->json([
                'status' => 'error',
                'message' => 'This email address is already registered.'
            ], 422);
        }

        // Verify OTP code
        $verification = $this->otpService->verify($request->email, $request->otp);
        if (!$verification['success']) {
            return response()->json([
                'status' => 'error',
                'message' => $verification['message'],
                'error_type' => $verification['error_type'] ?? 'unknown'
            ], 422);
        }

        try {
            // If incomplete user exists, delete it first
            if ($existingUser && !$existingUser->registration_completed) {
                $existingUser->delete();
            }
            
            // Create user
            $user = User::create([
                'name'                   => $request->first_name . ' ' . $request->last_name,
                'email'                  => $request->email,
                'phone'                  => $request->phone,
                'password'               => $request->password, // Will be hashed by the mutator
                'email_verified'         => true,
                'registration_completed' => true,
                'password_set_at'        => now(),
                'email_verified_at'      => now(),
            ]);

            // Return success response with redirect to login
            return response()->json([
                'status'  => 'success',
                'message' => 'Your account has been created successfully. Please login.',
                'redirect_url' => route('login'),
                'registered_email' => $request->email
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to create user: ' . $e->getMessage(), [
                'email' => $request->email
            ]);
            
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to create your account. Please try again later.'
            ], 500);
        }
    }

    public function logout(Request $request)
    {
        $user = Auth::guard('user')->user();
        
        // Remove this session from active_sessions if the user is logged in
        if ($user) {
            ActiveSession::where('user_id', $user->id)
                ->where('session_id', Session::getId())
                ->delete();
        }
        
        Auth::guard('user')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('home');
    }
    
    /**
     * Create user after email verification but before password setup
     */
    public function createIncompleteUser(Request $request)
    {
        $request->validate([
            'first_name' => 'required|string|max:255',
            'last_name'  => 'required|string|max:255',
            'phone'      => 'required|string|min:10|max:15',
            'email'      => 'required|email',
            'otp'        => 'required|string|size:6',
        ]);
        
        // Check if email already exists with completed registration
        $existingUser = User::where('email', $request->email)->first();
        if ($existingUser && $existingUser->registration_completed) {
            return response()->json([
                'status' => 'error',
                'message' => 'This email address is already registered.'
            ], 422);
        }

        // Verify OTP code
        $verification = $this->otpService->verify($request->email, $request->otp);
        if (!$verification['success']) {
            return response()->json([
                'status' => 'error',
                'message' => $verification['message'],
                'error_type' => $verification['error_type'] ?? 'unknown'
            ], 422);
        }

        try {
            // If user already exists, update their status instead of creating new one
            if ($existingUser) {
                $existingUser->update([
                    'name'                   => $request->first_name . ' ' . $request->last_name,
                    'phone'                  => $request->phone,
                    'email_verified'         => true,
                    'registration_status'    => 'otp_verified',
                    'registration_completed' => false,
                    'email_verified_at'      => now(),
                ]);
                $user = $existingUser;
            } else {
                // Create new user (should not happen with new flow, but keeping as fallback)
                $user = User::create([
                    'name'                   => $request->first_name . ' ' . $request->last_name,
                    'email'                  => $request->email,
                    'phone'                  => $request->phone,
                    'email_verified'         => true,
                    'registration_status'    => 'otp_verified',
                    'registration_completed' => false,
                    'email_verified_at'      => now(),
                ]);
            }

            return response()->json([
                'status'  => 'success',
                'message' => 'Email verified successfully. Please set your password.',
                'user_id' => $user->id
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to create incomplete user: ' . $e->getMessage(), [
                'email' => $request->email
            ]);
            
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to verify email. Please try again later.'
            ], 500);
        }
    }

    /**
     * Complete registration by setting password
     */
    public function completeRegistration(Request $request)
    {
        $request->validate([
            'user_id' => 'required|exists:users,id',
            'password' => 'required|min:6|confirmed',
        ]);

        try {
            $user = User::findOrFail($request->user_id);
            
            // Update user with password
            $user->update([
                'password'               => $request->password, // Will be hashed by the mutator
                'registration_status'    => 'completed',
                'registration_completed' => true,
                'password_set_at'        => now(),
            ]);

            return response()->json([
                'status'  => 'success',
                'message' => 'Registration completed successfully. Please login.',
                'redirect_url' => route('login'),
                'registered_email' => $user->email
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to complete registration: ' . $e->getMessage(), [
                'user_id' => $request->user_id
            ]);
            
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to complete registration. Please try again later.'
            ], 500);
        }
    }
}
