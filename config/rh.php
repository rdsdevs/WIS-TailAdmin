<?php

declare(strict_types=1);

return [

    /*
    |--------------------------------------------------------------------------
    | Notificaciones de contratos próximos a vencer
    |--------------------------------------------------------------------------
    |
    | "expiring_thresholds" define los hitos en días previos al vencimiento
    | en los que se enviará una notificación a los gestores. Cada combinación
    | (contrato, threshold) se notifica una sola vez (idempotencia).
    |
    | "notify_expiring_via_mail" controla si además del canal "database"
    | (campana en el dashboard) se envía correo electrónico. Por defecto solo
    | usa la campana para evitar saturar bandejas.
    |
    */

    'expiring_thresholds' => [7, 15, 30],

    'notify_expiring_via_mail' => env('RH_NOTIFY_EXPIRING_MAIL', false),

];
