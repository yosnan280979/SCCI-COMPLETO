<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class DashboardController extends Controller
{
    public function index()
    {
        try {
            // Totales para las tarjetas - usando DB::table directamente
            $totales = [
                'solicitudes' => $this->getCount('Solicitudes'),
                'contratos' => $this->getCount('Contratos'),
                'proveedores' => $this->getCount('Nomenclador Proveedores'),
                'usuarios' => $this->getCount('Nomenclador Usuarios'),
                'asignaciones' => $this->getCount('Asig por solic'),
                'logistica' => $this->getCount('Embarques'),
                'ditec' => $this->getCount('DITEC'),
                'clientes' => $this->getCount('Nomenclador Clientes'),
                'finanzas' => $this->getCount('Pagos por el Cliente'),
                'operaciones' => $this->getCount('Salidas al Mercado'),
            ];

            // Solicitudes recientes (últimas 5)
            $solicitudesRecientes = $this->getRecentSolicitudes();
            
            // Contratos recientes (últimos 5)
            $contratosRecientes = $this->getRecentContratos();

            Log::info('Dashboard cargado correctamente', [
                'solicitudes' => $totales['solicitudes'],
                'contratos' => $totales['contratos']
            ]);

            return view('dashboard', compact(
                'totales',
                'solicitudesRecientes',
                'contratosRecientes'
            ));

        } catch (\Exception $e) {
            Log::error('Error en DashboardController: ' . $e->getMessage());
            Log::error('Stack trace: ' . $e->getTraceAsString());
            
            $totales = array_fill_keys([
                'solicitudes', 'contratos', 'proveedores', 'usuarios',
                'asignaciones', 'logistica', 'ditec', 'clientes',
                'finanzas', 'operaciones'
            ], 0);

            return view('dashboard', [
                'totales' => $totales,
                'solicitudesRecientes' => collect(),
                'contratosRecientes' => collect()
            ]);
        }
    }

    /**
     * Obtener conteo seguro de una tabla
     */
    private function getCount($tableName)
    {
        try {
            // Usar backticks para tablas con espacios
            if (str_contains($tableName, ' ')) {
                $tableName = "`{$tableName}`";
            }
            
            return DB::table(DB::raw($tableName))->count();
        } catch (\Exception $e) {
            Log::warning("Error contando tabla {$tableName}: " . $e->getMessage());
            return 0;
        }
    }

    /**
     * Obtener solicitudes recientes
     */
    private function getRecentSolicitudes()
    {
        try {
            return DB::table('Solicitudes')
                ->leftJoin('Nomenclador Especialistas', 'Solicitudes.`Id Especialista`', '=', 'Nomenclador Especialistas.`Id especialista`')
                ->select(
                    'Solicitudes.`Id Solicitud`',
                    'Solicitudes.`No Solicitud`',
                    'Solicitudes.`Fecha Solicitud`',
                    'Nomenclador Especialistas.`Especialista`'
                )
                ->orderBy('Solicitudes.`Fecha Solicitud`', 'desc')
                ->take(5)
                ->get();
        } catch (\Exception $e) {
            Log::warning("Error obteniendo solicitudes recientes: " . $e->getMessage());
            return collect();
        }
    }

    /**
     * Obtener contratos recientes
     */
    private function getRecentContratos()
    {
        try {
            return DB::table('Contratos')
                ->leftJoin('Nomenclador Proveedores', 'Contratos.`Id Proveedor`', '=', 'Nomenclador Proveedores.`Id Proveedor`')
                ->select(
                    'Contratos.`Id Ctto`',
                    'Contratos.`No Ctto`',
                    'Contratos.`Id Proveedor`',
                    'Contratos.`Valor Ctto CUC`',
                    'Nomenclador Proveedores.`Proveedor`'
                )
                ->orderBy('Contratos.`Id Ctto`', 'desc')
                ->take(5)
                ->get();
        } catch (\Exception $e) {
            Log::warning("Error obteniendo contratos recientes: " . $e->getMessage());
            return collect();
        }
    }
}