<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Repositories\UserRepositoryInterface;
use App\Providers\PHPMailerService;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Tymon\JWTAuth\Facades\JWTAuth;
use Carbon\Carbon;

class AuthController extends Controller
{
    protected $users;

    public function __construct(UserRepositoryInterface $users){
        $this->users = $users;
    }

    public function login(Request $request)
    {
        try{
        $validated = $request->validate([
            'email' => 'required|string',
            'password' => 'required|string'
        ]);

        $user = $this->users->findByEmail($validated['email']);

         if (!$user || !Hash::check($validated['password'], $user->password)) {
            return response()->json([
                'message' => 'Invalid credentials'
            ], 401);
        }

        // Block unverified applicants from logging in
        if ($user['role'] === 'applicant' && empty($user->email_verified_at)) {
            return response()->json([
                'message' => 'Email not verified. Please check your inbox for the verification code.',
                'requires_verification' => true,
                'email' => $user->email,
            ], 403);
        }

         $permissions = [];
            if($user['role'] == 'admin'){
                array_push($permissions, "canViewUsers", "canDeletePermit", "canViewArchive", "canViewViolations", "canViewSystem");
            };
            $user['permissions'] = $permissions;

        $token = JWTAuth::fromUser($user);
            return response()->json([
                'message' => 'Login successful',
                'token' => $token,
                'user' => $user
            ])->cookie(
                'accessToken',
                $token,
                60*24,
                '/',
                null,
                false,   // secure
                true,    // httpOnly
                false,   // raw
                null     // samesite (null = no SameSite attribute set)
            );
        }catch(\Exception $e){
            return response()->json([
                'error' => 'Something went wrong',
                'message' => $e->getMessage()
            ], 500);
        }
    }


    public function updateSettings(Request $request)
    {
        try{
            $data = $request->all();
            $email = $data['email'];
            $auth = auth()->user();
            $checkExistEmail = $this->users->findEmailById($email,$auth['id'] );
            if($checkExistEmail){
                return response()->json([
                    'message' => 'Email already exist'
                ], 400);
            }
            if (isset($data['password'])) {
                $data['password'] = Hash::make($data['password']);
            }

            $user = $this->users->findAndUpdateUserById($auth['id'], $data);
              return response()->json([
                'message' => 'User updated successfully.',
                'data' => $user
            ]);
        }catch(\Exception $e){
            return response()->json([
                'error' => 'Something went wrong',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function userdata(Request $request){
            $auth = auth()->user();
            logger()->info('AUTH USERS', ['user' => $auth]);
            $user = $this->users->findByEmail($auth['email']);
            if (!$user) {
                return response()->json(['message' => 'Unauthorized'], 401);
            }
            $permissions = [];
            if($user['role'] == 'admin'){
                array_push($permissions, "canViewUsers", "canDeletePermit", "canViewPermits","canEditPermit","canViewDashboard","canViewCitizen","canViewArchive","canViewViolations","canViewSystem");
            };
              if($user['role'] == 'validator'){
                array_push($permissions, "canViewPermits","canViewDashboard","canViewViolations");
            };
            if($user['role'] == 'applicant'){
                array_push($permissions, "canViewHome","canApplicationForm","canCitizenCharter");
            };
              if($user['role'] == 'officer'){
               array_push($permissions,"canViewDashboard","canViewCitizen","canViewForApproval","canViewHistoryApprove");
            };
            $user['permissions'] = $permissions;
            return response()->json([
            'message' => 'Authenticated user',
            'user' => $user
        ]);

    }

    public function register(Request $request, PHPMailerService $mailer){
        try{

            $validated = $request->validate([
                'email' => 'required|string|unique:users,email',
                'password' => 'required|string|min:8',
                'name' => 'required|string',
            ],[
                'email.unique' => 'Email already used. Please choose another one.',
            ]);

            // Generate 6-digit verification code (expires in 30 mins)
            $code = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);

            $validated['role'] = 'applicant';
            $validated['password'] = Hash::make($validated['password']);
            $validated['email_verification_code'] = $code;
            $validated['email_verification_expires_at'] = Carbon::now()->addMinutes(30);
            $validated['email_verified_at'] = null;

            $this->users->create($validated);

            $this->sendVerificationEmail($mailer, $validated['email'], $validated['name'], $code);

            return response()->json([
                'message' => 'Registered. Please check your email for the verification code.',
                'email' => $validated['email'],
                'requires_verification' => true,
            ], 201);
        }catch(\Exception $e){
            return response()->json([
                'error' => 'Something went wrong.',
                'message' => $e->getMessage(),
                'status'=>500
            ], 500);
        }
    }

    public function verifyEmail(Request $request){
        try {
            $validated = $request->validate([
                'email' => 'required|string',
                'code' => 'required|string|size:6',
            ]);

            $user = User::where('email', $validated['email'])->first();
            if (!$user) {
                return response()->json(['message' => 'Account not found.'], 404);
            }
            if ($user->email_verified_at) {
                return response()->json(['message' => 'Email already verified. You may now log in.'], 200);
            }
            if (!$user->email_verification_code || $user->email_verification_code !== $validated['code']) {
                return response()->json(['message' => 'Invalid verification code.'], 422);
            }
            if ($user->email_verification_expires_at && Carbon::parse($user->email_verification_expires_at)->isPast()) {
                return response()->json(['message' => 'Verification code expired. Please request a new code.'], 422);
            }

            $user->update([
                'email_verified_at' => Carbon::now(),
                'email_verification_code' => null,
                'email_verification_expires_at' => null,
            ]);

            return response()->json(['message' => 'Email verified successfully. You may now log in.'], 200);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Something went wrong',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    public function resendVerification(Request $request, PHPMailerService $mailer){
        try {
            $validated = $request->validate(['email' => 'required|string']);

            $user = User::where('email', $validated['email'])->first();
            if (!$user) {
                return response()->json(['message' => 'Account not found.'], 404);
            }
            if ($user->email_verified_at) {
                return response()->json(['message' => 'Email already verified.'], 200);
            }

            $code = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
            $user->update([
                'email_verification_code' => $code,
                'email_verification_expires_at' => Carbon::now()->addMinutes(30),
            ]);

            $this->sendVerificationEmail($mailer, $user->email, $user->name, $code);

            return response()->json(['message' => 'A new verification code has been sent to your email.'], 200);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Something went wrong',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    private function sendVerificationEmail(PHPMailerService $mailer, string $email, string $name, string $code): void
    {
        $subject = "Email Verification – DENR-CENRO";
        $body = "
            <p>Dear <strong>{$name}</strong>,</p>
            <p>Thank you for registering with the DENR-CENRO permit system.</p>
            <p>Please use the verification code below to activate your account. This code will expire in 30 minutes.</p>
            <div style='margin: 24px 0; padding: 18px; background: #f0fdf4; border: 2px dashed #15803d; border-radius: 12px; text-align: center;'>
                <p style='margin: 0; font-size: 11px; letter-spacing: 0.2em; color: #166534; font-weight: bold;'>VERIFICATION CODE</p>
                <p style='margin: 8px 0 0 0; font-size: 32px; font-weight: bold; color: #14532d; letter-spacing: 0.4em;'>{$code}</p>
            </div>
            <p>If you did not request this account, please ignore this email.</p>
            <br>
            <p>Thank you,</p>
            <p>
                <strong>
                    Department of Environment and Natural Resources (DENR)<br>
                    Community Environment and Natural Resources Office (CENRO)<br>
                    Brgy. Duhat, Santa Cruz, Laguna
                </strong>
            </p>
        ";
        $mailer->send($email, $subject, $body);
    }
}
