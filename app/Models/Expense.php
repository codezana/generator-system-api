<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Expense extends Model
{

    protected $guarded = [];

    public function expenseType() {
        return $this->belongsTo( Types::class );
    }

    public function purchaser() {
        return $this->belongsTo( User::class, 'made' );
    }

    public function debts()
{
    return $this->hasMany(Debt::class); // Assuming there's a Debt model
}

}
