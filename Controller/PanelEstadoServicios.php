<?php

/*
 * Copyright (c) 2025 Antonio José Palma Silva <desarrolloweb@antoniojosepalma.es>
 */

namespace FacturaScripts\Plugins\PanelEstadoServicios\Controller;

use FacturaScripts\Core\Base\Controller;
use FacturaScripts\Core\KernelException;
use FacturaScripts\Core\Request;
use FacturaScripts\Core\Tools;
use FacturaScripts\Core\Where;
use FacturaScripts\Dinamic\Model\EstadoAT;
use FacturaScripts\Dinamic\Model\MaquinaAT;
use FacturaScripts\Dinamic\Model\ServicioAT;

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

        $minutos = (int) Tools::settings('panelestadoservicios', 'tiemporefrescopagina', 1);
        $this->interval = $minutos * 60 * 1000;

        $action = $this->request->request->get('action');
        if (Request::METHOD_POST === $this->request->method() && $this->isAjax() && $action === 'get-servicios') {
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
     * @return array<int|string, array{id: mixed, nombre: string, numserie: string}>
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
     * @return array<int, array{nombre: string, color: string}>
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
     * @param array<int|string, array{id: mixed, nombre: string, numserie: string}> $maquinasIndexadas
     * @param array<int, array{nombre: string, color: string}> $estadosIndexados
     *
     * @return list<array{id: mixed, codigo: mixed, maquina: array{id: mixed, nombre: string, numserie: string}|null, estado: array{nombre: string, color: string}}>
     */
    private function getServiciosArrayResponse($maquinasIndexadas, $estadosIndexados): array
    {
        $where = [];

        $idsEstados = (string) Tools::settings('panelestadoservicios', 'estadosmostrarpanel');
        if ('' !== $idsEstados) {
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
