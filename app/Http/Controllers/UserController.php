<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Repositories\UserRepositoryInterface;
use Illuminate\Support\Facades\Hash;
class UserController extends Controller
{
    protected $users;

    public function __construct(UserRepositoryInterface $users){
        $this->users = $users;
    }

    public function index(){
        try{
            $users = $this->users->getAllUsers();
              return response()->json([
                'message' => 'Retrieve successfully!',
                'data' =>$users
            ], 200);
        }catch(\Exception $e){
            return response()->json([
                'error' => 'Could not retrieve users.',
                'message' => $e->getMessage(),
                'status' => 500
            ], 500);
        }
    }

      public function create(Request $request)
    {
        try{
            $validated = $request->validate([
                'email' => 'required|string|email|unique:users,email',
                'password' => 'required|string|min:8',
                'name' => 'required|string',
                'role' => 'required|string',
                'position' => 'nullable|string|required_if:role,officer',
            ],[
                'email.unique' => 'Email already used. Please choose another one.',
                'position.required_if' => 'Position is required when role is officer.',
            ]);


            $validated['password'] = Hash::make($validated['password']);
            $user = $this->users->create($validated);
            return response()->json([
                'message' => 'Created successfully!',
                'data' => $user
            ], 201);
        }catch(\Exception $e){
            return response()->json([
                'error' => 'Something went wrong.',
                'message' => $e->getMessage(),
                'status'=>500
            ], 500);
        }

    }

    public function findAndDeleteUserById(string $userId){
        try{

            $user = $this->users->findAndDeleteUserById($userId);
            if (!$user) {
                return response()->json([
                    'message' => 'User not found'
                ], 404);
            }
            return response()->json([
                'message' => 'Deleted successfully!',
                'data' => null
            ], 200);
        }  catch (\Exception $e) {
            return response()->json([
                'error' => 'Something went wrong',
                'message' => $e->getMessage()
            ], 500);
        }

    }


    public function findAndUpdateUserById(Request $request,string $userId){
        try{
            $validated = $request->validate([
                'name' => 'sometimes|string',
                'email' => 'sometimes|string|min:6|unique:users,email,' . $userId,
                'role' => 'required|string',
                'password' => 'sometimes|string|min:8',
                'position' => 'nullable|string|required_if:role,officer',
            ]);

            if (isset($validated['password'])) {
                $validated['password'] = Hash::make($validated['password']);
            }

            $user = $this->users->findAndUpdateUserById($userId, $validated);

            if (!$user) {
                return response()->json([
                    'message' => 'User not found'
                ], 404);
            }
            return response()->json([
                'message' => 'Updated successfully!',
                'data' => $user
            ], 200);
        }  catch (\Exception $e) {
            return response()->json([
                'error' => 'Something went wrong',
                'message' => $e->getMessage()
            ], 500);
        }

    }

}
