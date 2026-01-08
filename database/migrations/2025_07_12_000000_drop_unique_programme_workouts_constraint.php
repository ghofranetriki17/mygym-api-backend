<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('programme_workouts', function (Blueprint $table) {
            // Drop foreign keys so the composite index can be removed
            $table->dropForeign(['programme_id']);
            $table->dropForeign(['workout_id']);

            $table->dropUnique('programme_workouts_programme_id_workout_id_unique');

            // Re-add the foreign keys (now using the single-column indexes)
            $table->foreign('programme_id')
                ->references('id')
                ->on('programmes')
                ->onDelete('cascade');

            $table->foreign('workout_id')
                ->references('id')
                ->on('workouts')
                ->onDelete('cascade');
        });
    }

    public function down()
    {
        Schema::table('programme_workouts', function (Blueprint $table) {
            $table->dropForeign(['programme_id']);
            $table->dropForeign(['workout_id']);

            $table->unique(['programme_id', 'workout_id']);

            $table->foreign('programme_id')
                ->references('id')
                ->on('programmes')
                ->onDelete('cascade');

            $table->foreign('workout_id')
                ->references('id')
                ->on('workouts')
                ->onDelete('cascade');
        });
    }
};
