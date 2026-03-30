<?php

declare(strict_types=1);

namespace Database\Seeders\Contabilidad;

use App\Models\Contabilidad\AccountingAccount;
use Illuminate\Database\Seeder;

class AccountingAccountSeeder extends Seeder
{
    public function run(): void
    {
        // Cuentas contables extraídas del sistema legacy (ascunwsi_db_wisascun + sandbox)
        $accounts = [
            // Gastos de personal - honorarios contratistas
            ['code' => '511090', 'name' => 'Honorarios - Otros', 'type' => 'Gasto'],
            ['code' => '511095', 'name' => 'Honorarios - Consultores', 'type' => 'Gasto'],
            ['code' => '512090', 'name' => 'Honorarios - Servicios técnicos', 'type' => 'Gasto'],
            ['code' => '512511', 'name' => 'Honorarios - Asesorías', 'type' => 'Gasto'],
            ['code' => '513515', 'name' => 'Servicios - Mantenimiento', 'type' => 'Gasto'],
            ['code' => '51351604', 'name' => 'Servicios - Vigilancia y seguridad', 'type' => 'Gasto'],
            ['code' => '51351605', 'name' => 'Servicios - Aseo y limpieza', 'type' => 'Gasto'],
            ['code' => '51351608', 'name' => 'Servicios - Mensajería', 'type' => 'Gasto'],
            ['code' => '513536', 'name' => 'Servicios - Arrendamiento equipos', 'type' => 'Gasto'],
            ['code' => '513590', 'name' => 'Servicios - Otros', 'type' => 'Gasto'],
            ['code' => '513596', 'name' => 'Servicios - Comunicaciones', 'type' => 'Gasto'],
            ['code' => '51102501', 'name' => 'Honorarios - Asesoría jurídica', 'type' => 'Gasto'],
            ['code' => '51501501', 'name' => 'Seguros generales', 'type' => 'Gasto'],
            ['code' => '515505', 'name' => 'Seguros de vida', 'type' => 'Gasto'],
            ['code' => '519560', 'name' => 'Gastos generales - Papelería', 'type' => 'Gasto'],
            ['code' => '519595', 'name' => 'Gastos generales - Viáticos', 'type' => 'Gasto'],
            ['code' => '519596', 'name' => 'Gastos generales - Transporte', 'type' => 'Gasto'],
            ['code' => '519597', 'name' => 'Gastos generales - Otros', 'type' => 'Gasto'],
            // Gastos de personal - nómina
            ['code' => '711001', 'name' => 'Sueldos y salarios', 'type' => 'Gasto'],
            ['code' => '711002', 'name' => 'Horas extras y recargos', 'type' => 'Gasto'],
            ['code' => '711003', 'name' => 'Comisiones', 'type' => 'Gasto'],
            ['code' => '711004', 'name' => 'Auxilio de transporte', 'type' => 'Gasto'],
            ['code' => '711005', 'name' => 'Cesantías', 'type' => 'Gasto'],
            ['code' => '711009', 'name' => 'Aportes seguridad social', 'type' => 'Gasto'],
            ['code' => '711010', 'name' => 'Aportes parafiscales', 'type' => 'Gasto'],
            ['code' => '711011', 'name' => 'Vacaciones', 'type' => 'Gasto'],
            ['code' => '713505', 'name' => 'Gastos personal - Dotación', 'type' => 'Gasto'],
            ['code' => '713510', 'name' => 'Gastos personal - Capacitación', 'type' => 'Gasto'],
            ['code' => '713512', 'name' => 'Gastos personal - Bienestar', 'type' => 'Gasto'],
            ['code' => '713513', 'name' => 'Gastos personal - Honorarios coordinadores', 'type' => 'Gasto'],
            ['code' => '713514', 'name' => 'Gastos personal - Honorarios asistentes', 'type' => 'Gasto'],
            ['code' => '713515', 'name' => 'Gastos personal - Honorarios ejecutivos', 'type' => 'Gasto'],
            ['code' => '713517', 'name' => 'Gastos personal - Honorarios directivos', 'type' => 'Gasto'],
            ['code' => '713518', 'name' => 'Gastos personal - Honorarios profesionales', 'type' => 'Gasto'],
            ['code' => '713519', 'name' => 'Gastos personal - Honorarios técnicos', 'type' => 'Gasto'],
            ['code' => '713521', 'name' => 'Gastos personal - Honorarios operativos', 'type' => 'Gasto'],
            ['code' => '713522', 'name' => 'Gastos personal - Honorarios administrativos', 'type' => 'Gasto'],
            ['code' => '713524', 'name' => 'Gastos personal - Honorarios investigación', 'type' => 'Gasto'],
            ['code' => '713527', 'name' => 'Gastos personal - Honorarios logística', 'type' => 'Gasto'],
            ['code' => '713528', 'name' => 'Gastos personal - Honorarios sistemas', 'type' => 'Gasto'],
            ['code' => '713535', 'name' => 'Gastos personal - Honorarios comunicaciones', 'type' => 'Gasto'],
            ['code' => '713537', 'name' => 'Gastos personal - Honorarios financieros', 'type' => 'Gasto'],
            ['code' => '713538', 'name' => 'Gastos personal - Honorarios jurídicos', 'type' => 'Gasto'],
            ['code' => '713540', 'name' => 'Gastos personal - Honorarios contables', 'type' => 'Gasto'],
            ['code' => '713541', 'name' => 'Gastos personal - Honorarios académicos', 'type' => 'Gasto'],
            ['code' => '713542', 'name' => 'Gastos personal - Honorarios biblioteca', 'type' => 'Gasto'],
            ['code' => '713543', 'name' => 'Gastos personal - Honorarios calidad', 'type' => 'Gasto'],
            ['code' => '719510', 'name' => 'Gastos personal - Otros beneficios', 'type' => 'Gasto'],
            ['code' => '719525', 'name' => 'Gastos personal - Indemnizaciones', 'type' => 'Gasto'],
            // Pasivos y provisiones
            ['code' => '269590', 'name' => 'Acreedores - Otros', 'type' => 'Pasivo'],
            ['code' => '269596', 'name' => 'Acreedores - Depósitos recibidos', 'type' => 'Pasivo'],
            ['code' => '269105', 'name' => 'Acreedores - Retención en la fuente', 'type' => 'Pasivo'],
            ['code' => '26900501', 'name' => 'Acreedores - IVA por pagar', 'type' => 'Pasivo'],
            ['code' => '269596', 'name' => 'Acreedores - Garantías recibidas', 'type' => 'Pasivo'],
            ['code' => '334010', 'name' => 'Provisiones - Litigios y demandas', 'type' => 'Pasivo'],
            ['code' => '2815051107', 'name' => 'Obligaciones laborales - Cesantías', 'type' => 'Pasivo'],
            ['code' => '2815051114', 'name' => 'Obligaciones laborales - Intereses cesantías', 'type' => 'Pasivo'],
        ];

        foreach ($accounts as $account) {
            AccountingAccount::firstOrCreate(
                ['code' => $account['code'], 'institution_id' => null],
                array_merge($account, ['institution_id' => null, 'is_active' => true])
            );
        }
    }
}
