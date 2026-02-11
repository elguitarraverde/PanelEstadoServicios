<?php
/*
 * Copyright (c) 2025 Antonio José Palma Silva <desarrolloweb@antoniojosepalma.es>
 */

namespace FacturaScripts\Plugins\PanelEstadoServicios;

use FacturaScripts\Core\Template\InitClass;
use FacturaScripts\Core\Tools;

class Init extends InitClass
{
    public function init(): void
    {
        $minutos = Tools::settings('panelestadoservicios', 'tiemporefrescopagina');
        if(empty($minutos)) {
            Tools::settingsSet('panelestadoservicios', 'tiemporefrescopagina', 1);
            Tools::settingsSave();
        }
    }

    public function uninstall(): void
    {
        //
    }

    public function update(): void
    {
        //
    }
}
