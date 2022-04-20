<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ChecklistTemplate extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'user_id',
    ];

    public function template_tasks()
    {
        return $this->hasMany(TaskTemplate::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
