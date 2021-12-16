<?php

namespace App\Http\Controllers;

use App\Environment;
use App\Http\Requests\SaveRequest;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

class EnvironmentController extends Controller
{
    public Environment $environment;

    public function __construct()
    {
        $this->environment = new Environment();
    }

    /**
     * @throws Throwable
     */
    public function show(Request $request, string $project, string $branch): string
    {
        return $this->environment->show($request->ajax(), $project, $branch);
    }

    /**
     * @throws Throwable
     */
    public function save(SaveRequest $request, string $project, string $branch): Response
    {

        if (!$request->ajax()) {
            return response()->json(['error' => 'access denied'], Response::HTTP_FORBIDDEN);
        }

        $this->environment->save($request->validated()['values'], $project, $branch);

        return response()->json(['message' => 'successfully saved'], Response::HTTP_OK);
    }
}
