<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Institution;
use App\Models\RH\Collaborator;
use App\Models\RH\Contract;
use App\Models\User;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(): View
    {
        $user          = auth()->user();
        $isSuperAdmin  = $user->hasRole('super-admin');
        $institutionId = $user->institution_id;

        if ($isSuperAdmin) {
            // Métricas globales para super-admin
            $metrics = [
                'institutions'  => Institution::count(),
                'users'         => User::count(),
                'collaborators' => Collaborator::count(),
                'contracts'     => Contract::count(),
                'vigentes'      => Contract::where('status', 'Vigente')->count(),
                'por_vencer'    => Contract::where('status', 'Vigente')
                    ->whereNotNull('end_date')
                    ->where('end_date', '<=', now()->addDays(30))
                    ->where('end_date', '>=', now())
                    ->count(),
                'terminados'    => Contract::where('status', 'Terminado')->count(),
                'empleados'     => Collaborator::where('type', 'Empleado')->count(),
                'contratistas'  => Collaborator::where('type', 'Contratista')->count(),
            ];
        } else {
            // Métricas por institución
            $metrics = [
                'institutions'  => 1,
                'users'         => User::where('institution_id', $institutionId)->count(),
                'collaborators' => Collaborator::where('institution_id', $institutionId)->count(),
                'contracts'     => Contract::where('institution_id', $institutionId)->count(),
                'vigentes'      => Contract::where('institution_id', $institutionId)->where('status', 'Vigente')->count(),
                'por_vencer'    => Contract::where('institution_id', $institutionId)
                    ->where('status', 'Vigente')
                    ->whereNotNull('end_date')
                    ->where('end_date', '<=', now()->addDays(30))
                    ->where('end_date', '>=', now())
                    ->count(),
                'terminados'    => Contract::where('institution_id', $institutionId)->where('status', 'Terminado')->count(),
                'empleados'     => Collaborator::where('institution_id', $institutionId)->where('type', 'Empleado')->count(),
                'contratistas'  => Collaborator::where('institution_id', $institutionId)->where('type', 'Contratista')->count(),
            ];
        }

        return view('pages.dashboard', compact('metrics', 'isSuperAdmin'));
    }
}
