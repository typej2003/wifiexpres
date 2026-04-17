<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class ApkController extends Controller
{
    public function download(): BinaryFileResponse
    {
        $path = 'apks/wifiexpres_v1.apk'; // Ruta en storage/app/public/

        if (!Storage::disk('public')->exists($path)) {
            abort(404, 'Archivo no encontrado');
        }

        $fullPath = storage_path('app/public/' . $path);
        $fileName = 'wifiexpres_v1.apk';

        $headers = [
            'Content-Type' => 'application/vnd.android.package-archive',
            'Content-Disposition' => 'attachment; filename="' . $fileName . '"',
        ];

        return response()->download($fullPath, $fileName, $headers);
    }
}