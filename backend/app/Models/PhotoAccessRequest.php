<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PhotoAccessRequest extends Model
{
    protected $fillable = ['requester_id', 'owner_id', 'status', 'responded_at'];

    protected function casts(): array
    {
        return ['responded_at' => 'datetime'];
    }

    public function requester()
    {
        return $this->belongsTo(User::class, 'requester_id');
    }

    public function owner()
    {
        return $this->belongsTo(User::class, 'owner_id');
    }
}
