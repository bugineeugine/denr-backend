<?php

namespace App\Repositories;

interface UserRepositoryInterface
{
    public function findByEmail(string $email);
    public function create(array $data);
    public function getAllUsers();
    public function findAndUpdateUserById(string $userId,array $data);
    public function findAndDeleteUserById(string $userId);
    public function findEmailById(string $email,string $userId);

}
