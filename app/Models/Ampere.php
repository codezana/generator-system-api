<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Ampere extends Model
{

    protected $guarded = [];
    protected $table = 'ampere';

    //Relations
    public function generator()
    {
        return $this->belongsTo(Generator::class);
    }
    // Define the relationship between Ampere and Debt (One-to-Many)
    public function debts()
    {
        return $this->hasMany(Debt::class, 'ampere_id');
    }
}
