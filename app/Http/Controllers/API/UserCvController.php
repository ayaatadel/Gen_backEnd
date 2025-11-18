<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\UserCv;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class UserCvController extends Controller
{
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'cv_json' => 'required|array',
            'title' => 'nullable|string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        $user = $request->user();

        $cv = UserCv::create([
            'user_id' => $user->id,
            'title' => $request->input('title'),
            'cv_json' => $request->input('cv_json'),
        ]);

        return response()->json([
            'message' => 'CV saved successfully',
            'cv' => $cv,
        ], 201);
    }
}
