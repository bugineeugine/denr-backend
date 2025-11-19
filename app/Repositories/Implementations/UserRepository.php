<?php

namespace App\Repositories\Implementations;

use App\Models\User;
use App\Repositories\UserRepositoryInterface;

class UserRepository implements UserRepositoryInterface
{
    public function findByEmail(string $email)
    {
        return User::where('email', $email)->first();
    }

    public function getAllUsers()
    {
        return User::orderBy('created_at', 'desc')->get();
    }

    public function findAndUpdateUserById(string $userId , array $data){
        $user = User::find($userId);

        if (!$user) {
            return null;
        }

        $user->update($data);
        return $user;
    }
     public function findAndDeleteUserById(string $userId){
         $user = User::find($userId);
        if (!$user) {
            return null;
        }

      return $user->delete();
    }


    public function create(array $data)
    {
        return User::create($data);
    }
     public function findEmailById(string $email,string $userId)
    {
        return User::where('email', $email)
        ->where('id', '!=', $userId)
        ->first();
    }


}
