<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AqiStation extends Model
{
    use HasFactory;

    protected $table = 'aqi_stations';

    protected $primaryKey = 'id';

    public $timestamps = true;

    protected $fillable = [
        'nama',
        'lat',
        'long',
        'quality'
    ];

    public function getAll(array $filter) {
        $data = $this->query()
            ->select('id', 'nama', 'lat', 'long', 'url_1', 'url_2', 'created_at', 'updated_at');

        if (!empty($filter['nama'])) {
            $data->where("nama", "LIKE", "%" . $filter['nama'] . "%");
        }

        return $data->get();
    }

    public function getById(int $id) {
        return $this->find($id);
    }
}
