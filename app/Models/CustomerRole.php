<?php

namespace App\Models;

use Database\Factories\CustomerRoleFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class CustomerRole extends Model
{
    /** @use HasFactory<CustomerRoleFactory> */
    use HasFactory, SoftDeletes;

    protected $fillable = ['name'];

    public function customers(): HasMany
    {
        return $this->hasMany(Customer::class);
    }
}
