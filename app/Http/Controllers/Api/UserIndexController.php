<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\UserResource;
use App\Models\User;
use Illuminate\Http\Resources\Json\ResourceCollection;

class UserIndexController extends Controller
{
    public function __invoke(): ResourceCollection
    {
        $users = User::itStaff()
            ->with('skills')
            ->orderBy('surname')
            ->paginate();

        return UserResource::collection($users);
    }
}
