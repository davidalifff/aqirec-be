<?php

namespace App\Http\Controllers;

use App\Http\Resources\StationResource;
use App\Models\AqiStation;
use Illuminate\Http\Request;

class StationController extends Controller
{
    private $stationModel;

    public function __construct() {
        $this->stationModel = new AqiStation();
    }

    public function index(Request $request) {
        $filter = ['nama' => $request->nama ?? ''];

        $station = $this->stationModel->getAll($filter);

        return response()->json(['message' => 'success', 'data' => new StationResource($station)], 200);
    }

    public function show($id) {

       $station = $this->stationModel->getById($id);

       return response()->json(['message' => 'success', 'data' => new StationResource($station)], 200);
    }
}
