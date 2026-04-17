<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class ApkController extends Controller
{
    public function download(): BinaryFileResponse
    {
        // Nombre del archivo dentro de storage/app/public/
        $path = 'apks/mi-aplicacion.apk';

        if (!Storage::disk('public')->exists($path)) {
            abort(404, 'El archivo APK no existe en el servidor.');
        }

        // Definimos un nombre amigable para el usuario que descarga
        $nombreDescarga = 'WifiExpres-v1.apk';

        return response()->download(storage_path('app/public/' . $path), $nombreDescarga, [
            'Content-Type' => 'application/vnd.android.package-archive',
        ]);
    }
}