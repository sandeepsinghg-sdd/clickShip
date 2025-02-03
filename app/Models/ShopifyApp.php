<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ShopifyApp extends Model
{
    use HasFactory;

    protected $fillable = ['shop', 'access_token'];
}