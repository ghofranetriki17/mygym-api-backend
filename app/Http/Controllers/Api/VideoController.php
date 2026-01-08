<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Video;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
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
            'video_file' => 'required|file|mimes:mp4,mov,avi,wmv,flv,mkv,webm,mpeg,mpg|max:204800',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $path = $request->file('video_file')->store('videos', 'public');
        $videoUrl = Storage::url($path);

        $video = Video::create([
            'coach_id' => $request->coach_id,
            'title' => $request->title,
            'description' => $request->description,
            'video_url' => $videoUrl,
        ]);
        $video->load('coach');

        return response()->json([
            'success' => true,
            'message' => 'Video uploaded successfully',
            'data' => $video
        ], 201);
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
