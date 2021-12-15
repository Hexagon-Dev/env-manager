<?php

namespace App\Http\Controllers;

use App\Environment;
use Illuminate\Http\Request;

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

    public function save(Request $request, string $project, string $branch): void
    {
        $this->environment->save($request->get('values'), $project, $branch);;
    }
}
