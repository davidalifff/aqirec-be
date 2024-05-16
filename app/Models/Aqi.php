<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Aqi extends Model
{
    use HasFactory;

    protected $table = 'aqi';

    protected $primaryKey = 'id';

    public $timestamps = true;

    protected $fillable = [
        'id_aqi_stations',
        'index_1',
        'index_2'
    ];
}
