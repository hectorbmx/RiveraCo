<?php

namespace App\Http\Controllers;

use App\Models\Cliente;
use App\Models\ClientePortal;
use Illuminate\Http\Request;

class ClientePortalController extends Controller
{
    public function store(Request $request, Cliente $cliente)
    {
        $data = $request->validate([
            'link_acceso' => ['required', 'url', 'max:2048'],
            'usuario' => ['nullable', 'string', 'max:255'],
            'password' => ['required', 'string', 'max:2000'],
        ]);

        $cliente->portales()->create($data);

        return redirect()
            ->route('clientes.edit', ['cliente' => $cliente, 'tab' => 'portales'])
            ->with('success', 'Portal del cliente guardado correctamente.');
    }

    public function update(Request $request, Cliente $cliente, ClientePortal $portal)
    {
        $this->ensurePortalBelongsToCliente($cliente, $portal);

        $data = $request->validate([
            'link_acceso' => ['required', 'url', 'max:2048'],
            'usuario' => ['nullable', 'string', 'max:255'],
            'password' => ['nullable', 'string', 'max:2000'],
        ]);

        if (($data['password'] ?? '') === '') {
            unset($data['password']);
        }

        $portal->update($data);

        return redirect()
            ->route('clientes.edit', ['cliente' => $cliente, 'tab' => 'portales'])
            ->with('success', 'Portal del cliente actualizado correctamente.');
    }

    public function destroy(Cliente $cliente, ClientePortal $portal)
    {
        $this->ensurePortalBelongsToCliente($cliente, $portal);

        $portal->delete();

        return redirect()
            ->route('clientes.edit', ['cliente' => $cliente, 'tab' => 'portales'])
            ->with('success', 'Portal del cliente eliminado correctamente.');
    }

    private function ensurePortalBelongsToCliente(Cliente $cliente, ClientePortal $portal): void
    {
        abort_unless((int) $portal->cliente_id === (int) $cliente->id, 404);
    }
}