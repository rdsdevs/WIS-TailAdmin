<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RolesAndPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        // Limpiar caché de permisos antes de proceder
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // Crear permisos por módulo
        $permisos = [
            // Colaboradores
            'collaborators.create',
            'collaborators.read',
            'collaborators.update',
            'collaborators.delete',

            // Contratos
            'contracts.create',
            'contracts.read',
            'contracts.update',
            'contracts.delete',

            // Certificados
            'certificates.generate',
            'certificates.read',

            // Reportes
            'reports.financial',
            'reports.hr',

            // Inventario — suministros
            'inventory.products.create',
            'inventory.products.read',
            'inventory.products.update',
            'inventory.products.delete',

            // Inventario — bienes y equipos
            'inventory.elements.create',
            'inventory.elements.read',
            'inventory.elements.update',
            'inventory.elements.delete',
        ];

        foreach ($permisos as $permiso) {
            Permission::firstOrCreate([
                'name' => $permiso,
                'guard_name' => 'web',
            ]);
        }

        // Crear roles del sistema
        $roles = [
            'super-admin',
            'admin',
            'rh-manager',
            'rh-viewer',
            'employee-manager',
            'contractor-manager',
            'accounting-manager',
            'accounting-viewer',
            'inventory-manager',
            'inventory-viewer',
        ];

        foreach ($roles as $rol) {
            Role::firstOrCreate([
                'name' => $rol,
                'guard_name' => 'web',
            ]);
        }

        // Asignar permisos a roles

        // super-admin: todos los permisos
        Role::findByName('super-admin')->givePermissionTo(Permission::all());

        // admin: todos los permisos (gestiona su institución)
        Role::findByName('admin')->givePermissionTo(Permission::all());

        // rh-manager: gestión completa de RH
        Role::findByName('rh-manager')->givePermissionTo([
            'collaborators.create',
            'collaborators.read',
            'collaborators.update',
            'collaborators.delete',
            'contracts.create',
            'contracts.read',
            'contracts.update',
            'contracts.delete',
            'certificates.generate',
            'certificates.read',
            'reports.hr',
        ]);

        // rh-viewer: solo consulta RH
        Role::findByName('rh-viewer')->givePermissionTo([
            'collaborators.read',
            'contracts.read',
            'certificates.read',
            'reports.hr',
        ]);

        // employee-manager: gestión completa de RH (equivalente a rh-manager)
        Role::findByName('employee-manager')->givePermissionTo([
            'collaborators.create',
            'collaborators.read',
            'collaborators.update',
            'collaborators.delete',
            'contracts.create',
            'contracts.read',
            'contracts.update',
            'contracts.delete',
            'certificates.generate',
            'certificates.read',
            'reports.hr',
        ]);

        // contractor-manager: gestión completa de RH (equivalente a rh-manager)
        Role::findByName('contractor-manager')->givePermissionTo([
            'collaborators.create',
            'collaborators.read',
            'collaborators.update',
            'collaborators.delete',
            'contracts.create',
            'contracts.read',
            'contracts.update',
            'contracts.delete',
            'certificates.generate',
            'certificates.read',
            'reports.hr',
        ]);

        // accounting-manager: contabilidad completa
        Role::findByName('accounting-manager')->givePermissionTo([
            'reports.financial',
        ]);

        // accounting-viewer: solo consulta contabilidad
        Role::findByName('accounting-viewer')->givePermissionTo([
            'reports.financial',
        ]);

        // inventory-manager: inventario completo
        Role::findByName('inventory-manager')->givePermissionTo([
            'inventory.products.create',
            'inventory.products.read',
            'inventory.products.update',
            'inventory.products.delete',
            'inventory.elements.create',
            'inventory.elements.read',
            'inventory.elements.update',
            'inventory.elements.delete',
        ]);

        // inventory-viewer: solo consulta inventario
        Role::findByName('inventory-viewer')->givePermissionTo([
            'inventory.products.read',
            'inventory.elements.read',
        ]);
    }
}
