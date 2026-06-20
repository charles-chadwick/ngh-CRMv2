<?php

namespace App\Models;

    use Illuminate\Database\Eloquent\Factories\HasFactory;
    use Illuminate\Database\Eloquent\Model;
    use Illuminate\Database\Eloquent\SoftDeletes;
    
    class Contact extends Model {
        use HasFactory, SoftDeletes;
        
        protected $fillable = [
        'type',
        'street_address',
        'city',
        'state',
        'zip',
        'work_phone',
        'mobile_phone',
        ];
    }
