<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Machine extends Model
{
    use HasFactory;

    protected $fillable = [
        'branch_id',
        'name', 
        'type',
        'description',
        'image_url',
        'video_url'
    ];

    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }

    public function charges()
    {
        return $this->belongsToMany(Charge::class, 'machine_charges')
                    ->withTimestamps();
    }

    public function categories()
    {
        return $this->belongsToMany(Category::class, 'machine_categories')
                    ->withTimestamps();
    }
}