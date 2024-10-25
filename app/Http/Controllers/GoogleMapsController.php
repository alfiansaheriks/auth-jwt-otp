<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class GoogleMapsController extends Controller
{
    public function getDirections(Request $request)
    {
        // Validasi input dari request
        $request->validate([
            'origin' => 'required|string',
            'destination' => 'required|string',
        ]);

        // Ambil API Key dari environment
        $apiKey = env('API_KEY', 'API_KEY_NOT_SET');

        // dd($apiKey);

        // Ambil origin (titik awal) dan destination (titik tujuan) dari request
        $origin = $request->input('origin'); // format: "latitude,longitude"
        $destination = $request->input('destination'); // format: "latitude,longitude"

        // URL untuk Google Directions API
        $url = "https://maps.googleapis.com/maps/api/directions/json?origin={$origin}&destination={$destination}&key={$apiKey}";

        // Kirim request ke API
        $response = Http::get($url);

        // Parsing hasil response
        $data = $response->json();

        // Jika status dari API adalah 'OK', return rute
        if ($data['status'] == 'OK') {
            return response()->json([
                'routes' => $data['routes'],
                'status' => $data['status'],
            ], 200);
        } else {
            return response()->json([
                'message' => 'Directions not found',
                'status' => $data['status'],
            ], 404);
        }
    }
}
