<?php

// app/Models/ShippingSetting.php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ShippingSetting extends Model
{
    use HasFactory;

    // Allow mass assignment for these fields
    protected $fillable = ['shipping_name', 'shipping_price'];
}
