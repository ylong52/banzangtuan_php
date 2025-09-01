<?php

namespace App\Http\Middleware;

use Illuminate\Auth\Middleware\Authenticate as Middleware;

class Authenticate extends Middleware
{
    protected function unauthenticated($request, array $guards)
    {
        if ($request->expectsJson() || $request->is('api/*')) {
            // 直接抛出HttpException，Laravel会自动返回json
            abort(response()->json(['message' => 'Unauthenticated.'], 401));
        }
        parent::unauthenticated($request, $guards);
    }
}
