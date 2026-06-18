<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class CompanyType extends Model
{
    use SoftDeletes;

    protected $fillable = ['name'];

    public function companies()
    {
        return $this->hasMany(Company::class);
    }
}
