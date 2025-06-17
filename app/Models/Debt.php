<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Debt extends Model
{

    protected $guarded = [];

    //Realationships

    public function expense()
    {
        return $this->belongsTo(Expense::class);
    }

    public function generator_expense()
    {
        return $this->belongsTo(GeneratorExpense::class, 'geexpense_id');
    }

    public function ampere()
    {
        return $this->belongsTo(Ampere::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
