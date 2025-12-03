<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\BranchController;
use App\Http\Controllers\Api\BranchAvailabilityController;
use App\Http\Controllers\Api\MachineController;
use App\Http\Controllers\Api\ChargeController;
use App\Http\Controllers\Api\CategoryController;
use App\Http\Controllers\Api\MovementController;
use App\Http\Controllers\Api\ExerciseController;
use App\Http\Controllers\Api\ProgrammeController;
use App\Http\Controllers\Api\UserProgressController;
use App\Http\Controllers\Api\WorkoutController;
use App\Http\Controllers\Api\CoachController;
use App\Http\Controllers\Api\SpecialityController;
use App\Http\Controllers\Api\CourseController;
use App\Http\Controllers\Api\CoachAvailabilityController;
use App\Http\Controllers\Api\GroupTrainingSessionController;
use App\Http\Controllers\Api\VideoController;
use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\Api\AdminBookingController;
use App\Http\Controllers\ParametreController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

// Public routes
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

// Branch routes (public if needed)
Route::apiResource('branches', BranchController::class);
Route::get('/branches/{branch}/coaches', [BranchController::class, 'getCoaches']);

// Branch availability routes (public)
Route::prefix('branches/{branchId}/availabilities')->group(function () {
    Route::get('/', [BranchAvailabilityController::class, 'index']);
    Route::get('/{id}', [BranchAvailabilityController::class, 'show']);
});
Route::get('branches/{branchId}/availability/{dayOfWeek}', [BranchAvailabilityController::class, 'getAvailabilityForDay']);
Route::delete('/user-progresses/{id}', [UserProgressController::class, 'destroy']);

// Machine routes (public if needed)
Route::apiResource('machines', MachineController::class)->only(['index', 'show']);
Route::get('branches/{branchId}/machines', [MachineController::class, 'getByBranch']);

// Category routes (public if needed)
Route::apiResource('categories', CategoryController::class)->only(['index', 'show']);

// Movement routes (public read, protected write)
Route::get('/movements', [MovementController::class, 'index']);
Route::get('/movements/{movement}', [MovementController::class, 'show']);

// Exercise routes (public read)
Route::get('/exercises', [ExerciseController::class, 'index']);
Route::get('/exercises/{exercise}', [ExerciseController::class, 'show']);
Route::get('machines/{machine}/charges', [ExerciseController::class, 'getChargesForMachine']);

// Coach and related routes (public read)
Route::apiResource('coaches', CoachController::class)->only(['index', 'show']);
Route::apiResource('specialities', SpecialityController::class)->only(['index', 'show']);
Route::get('coaches/{coachId}/specialities', function($coachId) {
    $coach = \App\Models\Coach::with('specialities')->find($coachId);
    if (!$coach) {
        return response()->json(['success' => false, 'message' => 'Coach not found'], 404);
    }
    return response()->json(['success' => true, 'data' => $coach->specialities]);
});

Route::get('coaches/{coachId}/availabilities', [CoachAvailabilityController::class, 'getByCoach']);

// Group Training Session routes (public read)
Route::get('group-training-sessions', [GroupTrainingSessionController::class, 'index']);
Route::get('group-training-sessions/{id}', [GroupTrainingSessionController::class, 'show']);
Route::get('branches/{branchId}/sessions', [GroupTrainingSessionController::class, 'getByBranch']);
Route::get('coaches/{coachId}/sessions', [GroupTrainingSessionController::class, 'getByCoach']);
Route::get('sessions/upcoming', [GroupTrainingSessionController::class, 'getUpcoming']);
Route::get('sessions/filter', [GroupTrainingSessionController::class, 'getSessionsByFilters']);

// Paramètres routes publiques
Route::get('/parametres/public', [ParametreController::class, 'public']);

// Protected routes
Route::middleware('auth:sanctum')->group(function () {
    // Auth routes
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/user', [AuthController::class, 'user']);
    Route::delete('/movements/{movement}', [MovementController::class, 'destroy']);
    Route::apiResource('users', UserController::class);

    // User Progress routes
    Route::apiResource('user-progresses', UserProgressController::class);
    Route::get('user-progresses/history', [UserProgressController::class, 'history']);

    // Workout routes
    Route::apiResource('workouts', WorkoutController::class);
    Route::get('workouts/{workout}/exercises', [WorkoutController::class, 'getExercises']);
    Route::post('/workouts/{workout}/exercises', [WorkoutController::class, 'addExercise']);
    Route::delete('workouts/{workout}/exercises/{exercise}', [WorkoutController::class, 'removeExercise']);
    Route::patch('workouts/{workout}/exercises/{exercise}/progress', [WorkoutController::class, 'updateExerciseProgress']);
    Route::patch('/workouts/{workout}/exercises/{exercise}', [WorkoutController::class, 'updateExercisePivot']);

    // Branch availability management routes (protected)
    Route::prefix('branches/{branchId}/availabilities')->group(function () {
        Route::post('/', [BranchAvailabilityController::class, 'store']);
        Route::put('/{id}', [BranchAvailabilityController::class, 'update']);
        Route::delete('/{id}', [BranchAvailabilityController::class, 'destroy']);
    });

    // Machine management routes
    Route::apiResource('machines', MachineController::class)->except(['index', 'show']);
    
    // Machine charge management routes
    Route::post('machines/{machine}/charges/{charge}', [MachineController::class, 'attachCharge']);
    Route::delete('machines/{machine}/charges/{charge}', [MachineController::class, 'detachCharge']);
    Route::put('machines/{machine}/charges', [MachineController::class, 'syncCharges']);

    // Charge routes
    Route::apiResource('charges', ChargeController::class);

    // Category management routes
    Route::apiResource('categories', CategoryController::class)->except(['index', 'show']);

    // Programme routes
    Route::apiResource('programmes', ProgrammeController::class);
    Route::patch('programmes/{programme}/activate', [ProgrammeController::class, 'activate']);

    // Exercise management routes
    Route::post('/exercises', [ExerciseController::class, 'store']);
    Route::put('/exercises/{exercise}', [ExerciseController::class, 'update']);
    Route::patch('/exercises/{exercise}', [ExerciseController::class, 'update']);
    Route::delete('/exercises/{exercise}', [ExerciseController::class, 'destroy']);

    // Coach management routes
    Route::apiResource('coaches', CoachController::class)->except(['index', 'show']);
    Route::post('coaches/{coachId}/specialities', function(Request $request, $coachId) {
        $coach = \App\Models\Coach::find($coachId);
        if (!$coach) {
            return response()->json(['success' => false, 'message' => 'Coach not found'], 404);
        }

        $validator = \Illuminate\Support\Facades\Validator::make($request->all(), [
            'speciality_ids' => 'required|array',
            'speciality_ids.*' => 'exists:specialities,id'
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        $coach->specialities()->sync($request->speciality_ids);
        return response()->json(['success' => true, 'message' => 'Specialities updated successfully']);
    });

    // Speciality management routes
    Route::apiResource('specialities', SpecialityController::class)->except(['index', 'show']);

    // Course routes
    Route::apiResource('courses', CourseController::class);

    // Coach Availability routes
    Route::apiResource('coach-availabilities', CoachAvailabilityController::class);

   

    // Group Training Session management and booking routes
    Route::apiResource('group-training-sessions', GroupTrainingSessionController::class)->except(['index', 'show']);
    Route::post('sessions/{id}/join', [GroupTrainingSessionController::class, 'joinSession']);
    Route::post('sessions/{id}/leave', [GroupTrainingSessionController::class, 'leaveSession']);
    Route::post('/sessions/{id}/book', [GroupTrainingSessionController::class, 'bookSession']);
    Route::post('/sessions/{id}/cancel', [GroupTrainingSessionController::class, 'cancelBooking']);
    Route::get('/user/bookings', [GroupTrainingSessionController::class, 'getUserBookings']);

    // Additional group session routes
    Route::get('/group-sessions', [GroupTrainingSessionController::class, 'index']);
    Route::post('/group-sessions', [GroupTrainingSessionController::class, 'store']);
    Route::get('/group-sessions/{id}', [GroupTrainingSessionController::class, 'show']);
    Route::get('/group-sessions/upcoming', [GroupTrainingSessionController::class, 'getUpcoming']);
    Route::get('/group-sessions/available', [GroupTrainingSessionController::class, 'getAvailable']);
    Route::get('/branches/{branchId}/sessions', [GroupTrainingSessionController::class, 'getByBranch']);
    Route::post('/group-sessions/{sessionId}/book', [GroupTrainingSessionController::class, 'bookSession']);
    Route::delete('/group-sessions/{sessionId}/book', [GroupTrainingSessionController::class, 'cancelBooking']);
    Route::get('/group-sessions/{sessionId}/booking-status', [GroupTrainingSessionController::class, 'checkBookingStatus']);
    Route::get('/group-sessions/{sessionId}/bookings', [GroupTrainingSessionController::class, 'getSessionBookings']);
    Route::get('/user/bookings', [GroupTrainingSessionController::class, 'getUserBookings']);

    // Admin routes
    Route::get('/admin/bookings', [AdminBookingController::class, 'index']);

    // Paramètres routes protégées
    Route::apiResource('parametres', ParametreController::class);
    Route::post('/parametres/bulk-update', [ParametreController::class, 'bulkUpdate']);
});

// Movement management routes
Route::post('/movements', [MovementController::class, 'store']);
Route::put('/movements/{movement}', [MovementController::class, 'update']);
Route::patch('/movements/{movement}', [MovementController::class, 'update']);
// Add this inside Route::middleware('auth:sanctum')->group(function () {
// Video management routes
 // ---------------------- VIDEOS (PROTECTED) ----------------------
    Route::post('/videos', [VideoController::class, 'store']);
    Route::delete('/videos/{id}', [VideoController::class, 'destroy']);// ---------------------- VIDEOS (PUBLIC) ----------------------
Route::get('/videos', [VideoController::class, 'getAllVideos']);
Route::get('/coaches/{coachId}/videos', [VideoController::class, 'index']);
