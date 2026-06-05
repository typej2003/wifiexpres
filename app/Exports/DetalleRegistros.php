<?php

namespace App\Exports;

use App\Models\TicketLog;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class DetalleRegistros implements FromCollection, WithHeadings, WithMapping, ShouldAutoSize, WithStyles
{
    /**
     * Retorna la colección de datos que queremos exportar.
     * * @return \Illuminate\Support\Collection
     */
    public function collection()
    {
        // Traemos los usuarios de la base de datos
        return TicketLog::all();
    }

    /**
     * Define los encabezados de las columnas del archivo Excel.
     * * @return array
     */
    public function headings(): array
    {
        return [
            'ID',
            'Nombre Completo',
            'Dirección MAC',
            'Fecha de Registro',
        ];
    }

    /**
     * Mapea los datos de cada fila para controlar exactamente qué se escribe.
     * Esto evita exportar contraseñas o campos innecesarios.
     * * @param mixed $user
     * @return array
     */
    public function map($user): array
    {
        return [
            $user->id,
            $user->username,
            $user->mac_address,
            $user->created_at->format('d/m/Y H:i'),
        ];
    }

    /**
     * Aplica estilos a la hoja de cálculo (por ejemplo, poner la fila 1 en negrita).
     * * @param Worksheet $sheet
     * @return array
     */
    public function styles(Worksheet $sheet)
    {
        return [
            // Estilo para la primera fila (los encabezados)
            1 => [
                'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
                'fill' => [
                    'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                    'startColor' => ['rgb' => '4F46E5'] // Color índigo
                ]
            ],
        ];
    }
}