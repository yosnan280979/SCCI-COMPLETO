<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Area;
use App\Models\Specialist;
use App\Models\BalanceCenter;
use App\Models\OSDE;
use App\Models\Classification;
use App\Models\OperationType;
use App\Models\CreditLine;
use App\Models\FinancingSource;
use App\Models\Provider;
use App\Models\Currency;
use App\Models\LoadType;
use App\Models\UserType;
use App\Models\Cliente;
use App\Models\Destino;
use App\Models\TipoProductoGeneral;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\NomencladorExport;
use PDF;

class NomencladorController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }
    
    // ... [Aquí van todos tus métodos existentes: areasIndex, areasCreate, etc.]
    // Solo voy a agregar los métodos SHOW al final
    
    // =================================================================
    // MÉTODOS SHOW PARA NOMENCLADORES
    // =================================================================
    
    public function areasShow($id)
    {
        $area = Area::findOrFail($id);
        return view('nomencladores.areas.show', compact('area'));
    }
    
    public function specialistsShow($id)
    {
        $specialist = Specialist::findOrFail($id);
        return view('nomencladores.specialists.show', compact('specialist'));
    }
    
    public function balanceCentersShow($id)
    {
        $balanceCenter = BalanceCenter::with('osde')->findOrFail($id);
        return view('nomencladores.balance_centers.show', compact('balanceCenter'));
    }
    
    public function osdesShow($id)
    {
        $osde = OSDE::findOrFail($id);
        return view('nomencladores.osdes.show', compact('osde'));
    }
    
    public function classificationsShow($id)
    {
        $classification = Classification::findOrFail($id);
        return view('nomencladores.classifications.show', compact('classification'));
    }
    
    public function operationTypesShow($id)
    {
        $operationType = OperationType::findOrFail($id);
        return view('nomencladores.operation_types.show', compact('operationType'));
    }
    
    public function creditLinesShow($id)
    {
        $creditLine = CreditLine::findOrFail($id);
        return view('nomencladores.credit_lines.show', compact('creditLine'));
    }
    
    public function financingSourcesShow($id)
    {
        $financingSource = FinancingSource::findOrFail($id);
        return view('nomencladores.financing_sources.show', compact('financingSource'));
    }
    
    public function providersShow($id)
    {
        $provider = Provider::findOrFail($id);
        return view('nomencladores.providers.show', compact('provider'));
    }
    
    public function currenciesShow($id)
    {
        $currency = Currency::findOrFail($id);
        return view('nomencladores.currencies.show', compact('currency'));
    }
    
    public function loadTypesShow($id)
    {
        $loadType = LoadType::findOrFail($id);
        return view('nomencladores.load_types.show', compact('loadType'));
    }
    
    public function userTypesShow($id)
    {
        $userType = UserType::findOrFail($id);
        return view('nomencladores.user_types.show', compact('userType'));
    }
    
    // Métodos para clientes, destinos y tipo_productos_general
    public function clientesIndex()
    {
        $clientes = Cliente::all();
        return view('nomencladores.clientes.index', compact('clientes'));
    }
    
    public function clientesShow($id)
    {
        $cliente = Cliente::findOrFail($id);
        return view('nomencladores.clientes.show', compact('cliente'));
    }
    
    public function clientesCreate()
    {
        if (auth()->user()->userType->{'Id Tipo Usuario'} == 3) {
            return redirect()->route('nomencladores.clientes.index')->with('error', 'No tienes permisos para crear clientes.');
        }
        
        return view('nomencladores.clientes.create');
    }
    
    public function clientesStore(Request $request)
    {
        if (auth()->user()->userType->{'Id Tipo Usuario'} == 3) {
            return redirect()->route('nomencladores.clientes.index')->with('error', 'No tienes permisos para crear clientes.');
        }
        
        $request->validate([
            'Cliente' => 'required|string|max:50',
            'Bases Presentadas' => 'required|boolean',
            'Fecha Bases' => 'nullable|date',
        ]);
        
        Cliente::create($request->all());
        
        return redirect()->route('nomencladores.clientes.index')
            ->with('success', 'Cliente creado exitosamente.');
    }
    
    public function clientesEdit($id)
    {
        if (auth()->user()->userType->{'Id Tipo Usuario'} == 3) {
            return redirect()->route('nomencladores.clientes.index')->with('error', 'No tienes permisos para editar clientes.');
        }
        
        $cliente = Cliente::findOrFail($id);
        
        return view('nomencladores.clientes.edit', compact('cliente'));
    }
    
    public function clientesUpdate(Request $request, $id)
    {
        if (auth()->user()->userType->{'Id Tipo Usuario'} == 3) {
            return redirect()->route('nomencladores.clientes.index')->with('error', 'No tienes permisos para editar clientes.');
        }
        
        $request->validate([
            'Cliente' => 'required|string|max:50',
            'Bases Presentadas' => 'required|boolean',
            'Fecha Bases' => 'nullable|date',
        ]);
        
        $cliente = Cliente::findOrFail($id);
        $cliente->update($request->all());
        
        return redirect()->route('nomencladores.clientes.index')
            ->with('success', 'Cliente actualizado exitosamente.');
    }
    
    public function clientesDestroy($id)
    {
        if (auth()->user()->userType->{'Id Tipo Usuario'} != 1) {
            return redirect()->route('nomencladores.clientes.index')->with('error', 'No tienes permisos para eliminar clientes.');
        }
        
        $cliente = Cliente::findOrFail($id);
        $cliente->delete();
        
        return redirect()->route('nomencladores.clientes.index')
            ->with('success', 'Cliente eliminado exitosamente.');
    }
    
    public function clientesExportExcel()
    {
        $clientes = Cliente::all();
        
        return Excel::download(new NomencladorExport('clientes'), 'clientes.xlsx');
    }
    
    public function clientesExportPdf()
    {
        $clientes = Cliente::all();
        
        $pdf = PDF::loadView('nomencladores.clientes.pdf', compact('clientes'));
        return $pdf->download('clientes.pdf');
    }
    
    public function destinosIndex()
    {
        $destinos = Destino::all();
        return view('nomencladores.destinos.index', compact('destinos'));
    }
    
    public function destinosShow($id)
    {
        $destino = Destino::findOrFail($id);
        return view('nomencladores.destinos.show', compact('destino'));
    }
    
    public function destinosCreate()
    {
        if (auth()->user()->userType->{'Id Tipo Usuario'} == 3) {
            return redirect()->route('nomencladores.destinos.index')->with('error', 'No tienes permisos para crear destinos.');
        }
        
        return view('nomencladores.destinos.create');
    }
    
    public function destinosStore(Request $request)
    {
        if (auth()->user()->userType->{'Id Tipo Usuario'} == 3) {
            return redirect()->route('nomencladores.destinos.index')->with('error', 'No tienes permisos para crear destinos.');
        }
        
        $request->validate([
            'destino' => 'required|string|max:100',
        ]);
        
        Destino::create($request->all());
        
        return redirect()->route('nomencladores.destinos.index')
            ->with('success', 'Destino creado exitosamente.');
    }
    
    public function destinosEdit($id)
    {
        if (auth()->user()->userType->{'Id Tipo Usuario'} == 3) {
            return redirect()->route('nomencladores.destinos.index')->with('error', 'No tienes permisos para editar destinos.');
        }
        
        $destino = Destino::findOrFail($id);
        
        return view('nomencladores.destinos.edit', compact('destino'));
    }
    
    public function destinosUpdate(Request $request, $id)
    {
        if (auth()->user()->userType->{'Id Tipo Usuario'} == 3) {
            return redirect()->route('nomencladores.destinos.index')->with('error', 'No tienes permisos para editar destinos.');
        }
        
        $request->validate([
            'destino' => 'required|string|max:100',
        ]);
        
        $destino = Destino::findOrFail($id);
        $destino->update($request->all());
        
        return redirect()->route('nomencladores.destinos.index')
            ->with('success', 'Destino actualizado exitosamente.');
    }
    
    public function destinosDestroy($id)
    {
        if (auth()->user()->userType->{'Id Tipo Usuario'} != 1) {
            return redirect()->route('nomencladores.destinos.index')->with('error', 'No tienes permisos para eliminar destinos.');
        }
        
        $destino = Destino::findOrFail($id);
        $destino->delete();
        
        return redirect()->route('nomencladores.destinos.index')
            ->with('success', 'Destino eliminado exitosamente.');
    }
    
    public function destinosExportExcel()
    {
        $destinos = Destino::all();
        
        return Excel::download(new NomencladorExport('destinos'), 'destinos.xlsx');
    }
    
    public function destinosExportPdf()
    {
        $destinos = Destino::all();
        
        $pdf = PDF::loadView('nomencladores.destinos.pdf', compact('destinos'));
        return $pdf->download('destinos.pdf');
    }
    
    public function tipoProductosGeneralIndex()
    {
        $tipoProductosGenerales = TipoProductoGeneral::all();
        return view('nomencladores.tipo_productos_general.index', compact('tipoProductosGenerales'));
    }
    
    public function tipoProductosGeneralShow($id)
    {
        $tipoProductoGeneral = TipoProductoGeneral::findOrFail($id);
        return view('nomencladores.tipo_productos_general.show', compact('tipoProductoGeneral'));
    }
    
    public function tipoProductosGeneralCreate()
    {
        if (auth()->user()->userType->{'Id Tipo Usuario'} == 3) {
            return redirect()->route('nomencladores.tipo_productos_general.index')->with('error', 'No tienes permisos para crear tipos de producto general.');
        }
        
        return view('nomencladores.tipo_productos_general.create');
    }
    
    public function tipoProductosGeneralStore(Request $request)
    {
        if (auth()->user()->userType->{'Id Tipo Usuario'} == 3) {
            return redirect()->route('nomencladores.tipo_productos_general.index')->with('error', 'No tienes permisos para crear tipos de producto general.');
        }
        
        $request->validate([
            'Tipo Prod general' => 'required|string|max:50',
            'Grupo' => 'nullable|string|max:10',
            'Arancel CUC' => 'nullable|numeric',
            'Arancel CUP' => 'nullable|numeric',
        ]);
        
        TipoProductoGeneral::create($request->all());
        
        return redirect()->route('nomencladores.tipo_productos_general.index')
            ->with('success', 'Tipo de Producto General creado exitosamente.');
    }
    
    public function tipoProductosGeneralEdit($id)
    {
        if (auth()->user()->userType->{'Id Tipo Usuario'} == 3) {
            return redirect()->route('nomencladores.tipo_productos_general.index')->with('error', 'No tienes permisos para editar tipos de producto general.');
        }
        
        $tipoProductoGeneral = TipoProductoGeneral::findOrFail($id);
        
        return view('nomencladores.tipo_productos_general.edit', compact('tipoProductoGeneral'));
    }
    
    public function tipoProductosGeneralUpdate(Request $request, $id)
    {
        if (auth()->user()->userType->{'Id Tipo Usuario'} == 3) {
            return redirect()->route('nomencladores.tipo_productos_general.index')->with('error', 'No tienes permisos para editar tipos de producto general.');
        }
        
        $request->validate([
            'Tipo Prod general' => 'required|string|max:50',
            'Grupo' => 'nullable|string|max:10',
            'Arancel CUC' => 'nullable|numeric',
            'Arancel CUP' => 'nullable|numeric',
        ]);
        
        $tipoProductoGeneral = TipoProductoGeneral::findOrFail($id);
        $tipoProductoGeneral->update($request->all());
        
        return redirect()->route('nomencladores.tipo_productos_general.index')
            ->with('success', 'Tipo de Producto General actualizado exitosamente.');
    }
    
    public function tipoProductosGeneralDestroy($id)
    {
        if (auth()->user()->userType->{'Id Tipo Usuario'} != 1) {
            return redirect()->route('nomencladores.tipo_productos_general.index')->with('error', 'No tienes permisos para eliminar tipos de producto general.');
        }
        
        $tipoProductoGeneral = TipoProductoGeneral::findOrFail($id);
        $tipoProductoGeneral->delete();
        
        return redirect()->route('nomencladores.tipo_productos_general.index')
            ->with('success', 'Tipo de Producto General eliminado exitosamente.');
    }
    
    public function tipoProductosGeneralExportExcel()
    {
        $tipoProductosGenerales = TipoProductoGeneral::all();
        
        return Excel::download(new NomencladorExport('tipo_productos_general'), 'tipo_productos_general.xlsx');
    }
    
    public function tipoProductosGeneralExportPdf()
    {
        $tipoProductosGenerales = TipoProductoGeneral::all();
        
        $pdf = PDF::loadView('nomencladores.tipo_productos_general.pdf', compact('tipoProductosGenerales'));
        return $pdf->download('tipo_productos_general.pdf');
    }
}