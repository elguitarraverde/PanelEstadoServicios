<?php
/*
 * Copyright (c) 2025 Antonio José Palma Silva <desarrolloweb@antoniojosepalma.es>
 */

namespace FacturaScripts\Plugins\PanelEstadoServicios\Controller;

use FacturaScripts\Core\Base\Controller;
use FacturaScripts\Core\KernelException;
use FacturaScripts\Core\Tools;
use FacturaScripts\Core\Where;
use FacturaScripts\Dinamic\Model\MaquinaAT;
use FacturaScripts\Dinamic\Model\ServicioAT;
use FacturaScripts\Plugins\Servicios\Model\EstadoAT;

class PanelEstadoServicios extends Controller
{
    public int $interval;

    public function getPageData(): array
    {
        $data = parent::getPageData();
        $data['menu'] = 'sales';
        $data['title'] = 'services-panel';
        $data['icon'] = 'fa-solid fa-headset';
        return $data;
    }

    /**
     * @throws KernelException
     */
    public function privateCore(&$response, $user, $permissions): void
    {
        parent::privateCore($response, $user, $permissions);

        $minutos = Tools::settings('panelestadoservicios', 'tiemporefrescopagina', 1);
        $this->interval = $minutos * 60 * 1000;

        $action = $this->request->request->get('action');
        if ($this->request->method() === 'POST' && $this->isAjax() && $action === 'get-servicios') {
            $this->setTemplate(false);

            $maquinasIndexadas = $this->getMaquinasIndexadas();
            $estadosIndexados = $this->getEstadosIndexados();
            $servicios = $this->getServiciosArrayResponse($maquinasIndexadas, $estadosIndexados);

            $this->response->json([
                'success' => true,
                'servicios' => $servicios,
            ]);
        }
    }

    private function isAjax(): bool
    {
        return 'XMLHttpRequest' === $this->request->headers->get('X-Requested-With');
    }

    /**
     * @return MaquinaAT[]
     */
    private function getMaquinasIndexadas(): array
    {
        $maquinas = MaquinaAT::all();

        $maquinasIndexadas = [];

        foreach ($maquinas as $maquina) {
            $maquinasIndexadas[$maquina->idmaquina]['id'] = $maquina->id();
            $maquinasIndexadas[$maquina->idmaquina]['nombre'] = $maquina->nombre;
            $maquinasIndexadas[$maquina->idmaquina]['numserie'] = $maquina->numserie;
        }

        return $maquinasIndexadas;
    }

    /**
     * @return EstadoAT[]
     */
    private function getEstadosIndexados(): array
    {
        $estados = EstadoAT::all();

        $estadosIndexados = [];

        foreach ($estados as $estado) {
            $estadosIndexados[$estado->id]['nombre'] = $estado->nombre;
            $estadosIndexados[$estado->id]['color'] = $estado->color;
        }

        return $estadosIndexados;
    }

    /**
     * @param $maquinasIndexadas
     * @param $estadosIndexados
     *
     * @return array{id: int, codigo: string, maquina: array|null, estado: array}
     */
    private function getServiciosArrayResponse($maquinasIndexadas, $estadosIndexados): array
    {
        $where = [];

        $idsEstados = Tools::settings('panelestadoservicios', 'estadosmostrarpanel');
        if (!empty($idsEstados)) {
            $idsEstados = explode(',', $idsEstados);
            $where[] = Where::in('idestado', $idsEstados);
        }

        $servicios = ServicioAT::all($where);

        return array_map(function ($servicio) use ($maquinasIndexadas, $estadosIndexados) {
            $maquina = $maquinasIndexadas[$servicio->idmaquina] ?? null;
            $estado = $estadosIndexados[$servicio->idestado] ?? null;

            return [
                'id' => $servicio->idservicio,
                'codigo' => $servicio->codigo,
                'maquina' => $maquina,
                'estado' => $estado,
            ];
        }, $servicios);
    }
}
