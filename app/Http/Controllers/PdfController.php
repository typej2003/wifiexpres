<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\TicketLog;
use App\Models\Router;
use Illuminate\Support\Facades\Auth;
use Barryvdh\DomPDF\Facade\Pdf;

class PdfController extends Controller
{
    public function exportHistory(Request $request)
    {
        $user = Auth::user();
        $routerIds = Router::where('user_id', $user->id)->pluck('id');

        $logs = TicketLog::whereIn('router_id', $routerIds)
            ->with('router')
            ->when($request->search, function($q) use ($request) {
                $q->where('username', 'like', '%' . $request->search . '%')
                  ->orWhere('mac_address', 'like', '%' . $request->search . '%');
            })
            ->when($request->router, function($q) use ($request) {
                $q->where('router_id', $request->router);
            })
            ->when($request->from, function($q) use ($request) {
                $q->whereDate('created_at', '>=', $request->from);
            })
            ->when($request->to, function($q) use ($request) {
                $q->whereDate('created_at', '<=', $request->to);
            })
            ->latest()
            ->get();

        $pdf = Pdf::loadView('pdf.user-history', [
            'logs' => $logs,
            'user' => $user,
            'from' => $request->from,
            'to' => $request->to
        ]);

        return $pdf->download('Historial_Conexiones_'.now()->format('d-m-Y').'.pdf');
    }
}