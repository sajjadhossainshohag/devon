<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\ValetService;
use Illuminate\Http\JsonResponse;

class ValetController extends Controller
{
    public function __construct(protected ValetService $valetService)
    {
        $this->valetService = $valetService;
    }

    public function dashboard()
    {
        $sites = $this->valetService->getSites();
        $status = $this->valetService->getValetStatus();
        $phpVersion = $this->valetService->getPhpVersion();

        return view('dashboard', compact('sites', 'status', 'phpVersion'));
    }

    public function getSites(): JsonResponse
    {
        try {
            $sites = $this->valetService->getSites();
            return response()->json(['success' => true, 'sites' => $sites]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'error' => $e->getMessage()]);
        }
    }

    public function linkSite(Request $request): JsonResponse
    {
        $request->validate([
            'name' => 'required|string',
            'path' => 'required|string'
        ]);

        try {
            $success = $this->valetService->linkSite($request->name, $request->path);
            return response()->json(['success' => $success]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'error' => $e->getMessage()]);
        }
    }

    public function unlinkSite(Request $request): JsonResponse
    {
        $request->validate([
            'name' => 'required|string'
        ]);

        try {
            $success = $this->valetService->unlinkSite($request->name);
            return response()->json(['success' => $success]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'error' => $e->getMessage()]);
        }
    }

    public function secureSite(Request $request): JsonResponse
    {
        $request->validate([
            'name' => 'required|string'
        ]);

        try {
            $success = $this->valetService->secureSite($request->name);
            return response()->json(['success' => $success]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'error' => $e->getMessage()]);
        }
    }

    public function restartValet(): JsonResponse
    {
        try {
            $success = $this->valetService->restartValet();
            return response()->json(['success' => $success]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'error' => $e->getMessage()]);
        }
    }

    public function switchPhp(Request $request): JsonResponse
    {
        $request->validate([
            'version' => 'required|string'
        ]);

        try {
            $success = $this->valetService->switchPhpVersion($request->version);
            return response()->json(['success' => $success]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'error' => $e->getMessage()]);
        }
    }
}
