<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GeneratorExpense extends Model
{

    protected $guarded = [];

    public function generator() {
        return $this->belongsTo( Generator::class );
    }

    public function types() {
        return $this->belongsTo( Types::class , 'type_id' );
    }


        // Define the relationship between Ampere and Debt (One-to-Many)
        public function debts()
        {
            return $this->hasMany(Debt::class, 'geexpense_id');
        }
}
