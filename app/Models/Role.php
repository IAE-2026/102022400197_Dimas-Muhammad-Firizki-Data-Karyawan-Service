<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Role extends Model
{
    protected $fillable = ['name', 'description'];

    public function federatedUsers(): HasMany
    {
        return $this->hasMany(FederatedUser::class);
    }
}
