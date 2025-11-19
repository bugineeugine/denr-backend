<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Repositories\UserRepositoryInterface;
use Illuminate\Support\Facades\Hash;
use Tymon\JWTAuth\Facades\JWTAuth;

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
         $permissions = [];
            if($user['role'] == 'admin'){
                array_push($permissions, "canViewUsers", "canDeletePermit");
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
                false,
                true
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
                array_push($permissions, "canViewUsers", "canDeletePermit", "canViewPermits","canEditPermit","canViewDashboard");
            };
              if($user['role'] == 'validator'){
                array_push($permissions, "canViewPermits","canViewDashboard");
            };
            if($user['role'] == 'applicant'){
                array_push($permissions, "canViewHome","canApplicationForm");
            };
            $user['permissions'] = $permissions;
            return response()->json([
            'message' => 'Authenticated user',
            'user' => $user
        ]);

    }

    public function register(Request $request){
        try{

            $validated = $request->validate([
                'email' => 'required|string|unique:users,email',
                'password' => 'required|string|min:8',
                'name' => 'required|string',
            ],[
                'email.unique' => 'Email already used. Please choose another one.',
            ]);
            $validated['role'] = 'applicant';
            $validated['password'] = Hash::make($validated['password']);
            $this->users->create($validated);
            return response()->json([
                'message' => 'Register successfully!',

            ], 201);
        }catch(\Exception $e){
            return response()->json([
                'error' => 'Something went wrong.',
                'message' => $e->getMessage(),
                'status'=>500
            ], 500);
        }
    }

}
