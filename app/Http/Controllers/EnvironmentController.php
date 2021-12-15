<?php

namespace App\Http\Controllers;

use App\Environment;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnvironmentController extends Controller
{
    public Environment $environment;

    public function __construct()
    {
        $this->environment = new Environment();
    }

    public function show(Request $request, string $project, string $branch): string
    {
        return $this->environment->show($request->ajax(), $project, $branch);
    }

    public function save(Request $request, string $project, string $branch)
    {
        if (!$request->ajax()) {
            return response()->json(['error' => 'access denied'], Response::HTTP_FORBIDDEN);
        }
        $this->environment->save($request->get('values'), $project, $branch);;
    }
}
