<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Generator extends Model
{

    protected $table = 'generators';
    protected $guarded = [];

    public function admin() {
        return $this->belongsTo(User::class, 'admin_id');
    }

    public function manager() {
        return $this->belongsTo(User::class, 'manager_id');
    }

    public function Ampere() {
        return $this->hasMany(Ampere::class);
    }

    public function expenses() {
        return $this->hasMany(Expense::class);
    }

    public function generatorExpenses() {
        return $this->hasMany(GeneratorExpense::class);
    }
}
