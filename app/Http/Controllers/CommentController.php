<?php

namespace App\Http\Controllers;

use App\Repositories\CommentRepositoryInterface;
use Illuminate\Http\Request;

class CommentController extends Controller
{

    protected $comments;

    public function __construct(CommentRepositoryInterface $comments){
        $this->comments = $comments;
    }


    public function userComments($userId)
    {
        $comments = $this->comments->findByUserId($userId);

        return response()->json([
            'success' => true,
            'data' => $comments
        ]);
    }

    public function getCommentByPermitId($permitId)
    {
        $comments = $this->comments->findByPermitId($permitId);

        return response()->json([
            'success' => true,
            'data' => $comments
        ]);
    }

     public function create(Request $request)
    {
        $user = auth()->user();

        $data = $request->only(['permit_id', 'comment']);
        $data['user_id'] = $user['id'];

        $comments = $this->comments->create($data);

        return response()->json([
            'success' => true,
            'data' => $comments
        ]);
    }
}
