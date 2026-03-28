<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Institution;
use App\Models\RH\Collaborator;
use App\Models\RH\Contract;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(): View|RedirectResponse
    {
        $user = auth()->user();
        $isSuperAdmin = $user->hasRole('super-admin');
        $role = $user->roles->first()->name ?? 'viewer';
        $institutionId = $user->institution_id;

        if (! $isSuperAdmin && $institutionId === null) {
            return redirect()->route('dashboard')
                ->withErrors(['error' => 'El usuario no tiene una institución asignada.']);
        }

        $metrics = match ($role) {
            'super-admin' => $this->metricsForSuperAdmin(),
            'admin' => $this->metricsForAdmin($institutionId),
            'rh-manager',
            'employee-manager' => $this->metricsForRhManager($institutionId),
            'contractor-manager' => $this->metricsForContractorManager($institutionId),
            'rh-viewer' => $this->metricsForRhViewer($institutionId),
            default => $this->metricsForRhViewer($institutionId),
        };

        return view('pages.dashboard', compact('metrics', 'role', 'isSuperAdmin'));
    }

    private function metricsForSuperAdmin(): array
    {
        return [
            'institutions' => Institution::count(),
            'users' => User::count(),
            'collaborators' => Collaborator::count(),
            'contracts' => Contract::count(),
            'vigentes' => Contract::where('status', 'Vigente')->count(),
            'por_vencer' => Contract::where('status', 'Vigente')
                ->whereNotNull('end_date')
                ->where('end_date', '<=', now()->addDays(30))
                ->where('end_date', '>=', now())
                ->count(),
            'terminados' => Contract::whereIn('status', ['Terminado', 'Liquidado', 'Vencido'])->count(),
            'empleados' => Collaborator::where('type', 'Empleado')->count(),
            'contratistas' => Collaborator::where('type', 'Contratista')->count(),
        ];
    }

    private function metricsForAdmin(string $institutionId): array
    {
        return [
            'users' => User::where('institution_id', $institutionId)->count(),
            'collaborators' => Collaborator::where('institution_id', $institutionId)->count(),
            'contracts' => Contract::where('institution_id', $institutionId)->count(),
            'vigentes' => Contract::where('institution_id', $institutionId)
                ->where('status', 'Vigente')->count(),
            'por_vencer' => Contract::where('institution_id', $institutionId)
                ->where('status', 'Vigente')
                ->whereNotNull('end_date')
                ->where('end_date', '<=', now()->addDays(30))
                ->where('end_date', '>=', now())
                ->count(),
            'terminados' => Contract::where('institution_id', $institutionId)
                ->whereIn('status', ['Terminado', 'Liquidado', 'Vencido'])->count(),
            'empleados' => Collaborator::where('institution_id', $institutionId)
                ->where('type', 'Empleado')->count(),
            'contratistas' => Collaborator::where('institution_id', $institutionId)
                ->where('type', 'Contratista')->count(),
        ];
    }

    private function metricsForRhManager(string $institutionId): array
    {
        return [
            'collaborators' => Collaborator::where('institution_id', $institutionId)->count(),
            'contracts' => Contract::where('institution_id', $institutionId)->count(),
            'vigentes' => Contract::where('institution_id', $institutionId)
                ->where('status', 'Vigente')->count(),
            'por_vencer' => Contract::where('institution_id', $institutionId)
                ->where('status', 'Vigente')
                ->whereNotNull('end_date')
                ->where('end_date', '<=', now()->addDays(30))
                ->where('end_date', '>=', now())
                ->count(),
            'terminados' => Contract::where('institution_id', $institutionId)
                ->whereIn('status', ['Terminado', 'Liquidado', 'Vencido'])->count(),
            'empleados' => Collaborator::where('institution_id', $institutionId)
                ->where('type', 'Empleado')->count(),
            'contratistas' => Collaborator::where('institution_id', $institutionId)
                ->where('type', 'Contratista')->count(),
        ];
    }

    private function metricsForContractorManager(string $institutionId): array
    {
        return [
            'contratistas' => Collaborator::where('institution_id', $institutionId)
                ->where('type', 'Contratista')->count(),
            'vigentes' => Contract::where('institution_id', $institutionId)
                ->whereHas('collaborator', fn ($q) => $q->where('type', 'Contratista'))
                ->where('status', 'Vigente')->count(),
            'por_vencer' => Contract::where('institution_id', $institutionId)
                ->whereHas('collaborator', fn ($q) => $q->where('type', 'Contratista'))
                ->where('status', 'Vigente')
                ->whereNotNull('end_date')
                ->where('end_date', '<=', now()->addDays(30))
                ->where('end_date', '>=', now())->count(),
            'terminados' => Contract::where('institution_id', $institutionId)
                ->whereHas('collaborator', fn ($q) => $q->where('type', 'Contratista'))
                ->whereIn('status', ['Terminado', 'Liquidado', 'Vencido'])->count(),
        ];
    }

    private function metricsForRhViewer(string $institutionId): array
    {
        return [
            'collaborators' => Collaborator::where('institution_id', $institutionId)->count(),
            'contracts' => Contract::where('institution_id', $institutionId)->count(),
            'vigentes' => Contract::where('institution_id', $institutionId)
                ->where('status', 'Vigente')->count(),
            'por_vencer' => Contract::where('institution_id', $institutionId)
                ->where('status', 'Vigente')
                ->whereNotNull('end_date')
                ->where('end_date', '<=', now()->addDays(30))
                ->where('end_date', '>=', now())
                ->count(),
            'terminados' => Contract::where('institution_id', $institutionId)
                ->whereIn('status', ['Terminado', 'Liquidado', 'Vencido'])->count(),
            'empleados' => Collaborator::where('institution_id', $institutionId)
                ->where('type', 'Empleado')->count(),
            'contratistas' => Collaborator::where('institution_id', $institutionId)
                ->where('type', 'Contratista')->count(),
        ];
    }
}
