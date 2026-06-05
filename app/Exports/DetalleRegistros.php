<?php

namespace App\Exports;

use App\Models\TicketLog;
use Maatwebsite\Excel\Concerns\FromCollection;

class DetalleRegistros implements FromCollection
{
    /**
    * @return \Illuminate\Support\Collection
    */
    public function collection()
    {
        return TicketLog::all();
    }
}
