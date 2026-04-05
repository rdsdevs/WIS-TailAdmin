# Plan de Acción Técnico: Refactorización y Evolución Módulo RH (Final)

## 1. Hito 1: Infraestructura de Datos (Backend/DB)
- Refactorizar modelos `RH/*` para asegurar UUID + Auditing + SoftDeletes.
- Migraciones: `employee_profiles`, `payroll_contract_details`, `position_emails`.
- Índices de rendimiento para búsquedas por documento y vigencia.

## 2. Hito 2: Capa de Servicios (Backend)
- Crear `CollaboratorService`, `ContractService` y `PositionService`.
- Extraer lógica de persistencia y cálculos de los controladores.
- Implementar `ContractObserver` para historial automático.

## 3. Hito 3: Seguridad y Validación (QA/Backend)
- Implementar `FormRequests` para toda entrada de usuario.
- Aplicar `Policies` en todos los métodos de los controladores y componentes Volt.
- Blindaje contra XSS y SQLi siguiendo la guía `qa.md`.

## 4. Hito 4: UI/UX Premium (Frontend)
- Refactorizar `collaborator-form` y `contract-form` con secciones opcionales y reactividad Livewire.
- Crear `position-manager` con estándar Akademi y Tailwind v4.
- Dashboard especializado para el rol `employee-manager`.
- Localización completa: Moneda COP y fechas d/m/Y.
