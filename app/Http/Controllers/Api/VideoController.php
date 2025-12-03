<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Video;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class VideoController extends Controller
{
    // Get videos by specific coach (for public route)
    public function index($coachId)
    {
        $videos = Video::with('coach')->where('coach_id', $coachId)->get();

        return response()->json([
            'success' => true,
            'data' => $videos
        ]);
    }

    // Get all videos (for admin panel)
    public function getAllVideos()
    {
        $videos = Video::with('coach')->get();

        return response()->json([
            'success' => true,
            'data' => $videos
        ]);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'coach_id' => 'required|exists:coaches,id',
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'video_url' => 'required|url' // Changed to url validation
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $video = Video::create($request->all());
        $video->load('coach'); // Load coach relationship

        return response()->json([
            'success' => true,
            'message' => 'Video uploaded successfully',
            'data' => $video
        ]);
    }

    public function destroy($id)
    {
        $video = Video::find($id);

        if (!$video) {
            return response()->json([
                'success' => false,
                'message' => 'Video not found'
            ], 404);
        }

        $video->delete();

        return response()->json([
            'success' => true,
            'message' => 'Video deleted successfully'
        ]);
    }
}