<?php

namespace App\Repositories\Implementations;

use App\Models\Comment;
use App\Repositories\CommentRepositoryInterface;

class CommentRepository implements CommentRepositoryInterface
{

    public function findByUserId($userId)
    {
       return Comment::with('user:id,name,email')
                  ->where('user_id', $userId)
                  ->orderBy('created_at', 'desc')
                  ->get();
    }
    public function findByPermitId($permitId)
    {
          return Comment::with('user:id,name,email')
                 ->where('permit_id', $permitId)
                 ->orderBy('created_at', 'desc')
                 ->get();

    }
    public function create(array $data)
    {
        return Comment::create($data);
    }

}
