<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class ImageUploadController extends Controller
{
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'image' => 'required|image|mimes:jpeg,png,jpg,gif,webp|max:5120',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        // Store in storage/app/public/uploads to keep files behind the symlink.
        $path = $request->file('image')->store('uploads', 'public');
        $url = asset('storage/' . $path);

        return response()->json([
            'success' => true,
            'message' => 'Image uploaded successfully',
            'path' => $path,
            'url' => $url,
        ]);
    }
}
