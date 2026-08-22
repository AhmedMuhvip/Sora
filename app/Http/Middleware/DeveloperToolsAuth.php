<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class DeveloperToolsAuth
{
    public function handle(
        Request $request,
        Closure $next,
    ): Response {
        $username = config('developer-tools.username');
        $password = config('developer-tools.password');

        $valid =
            $username &&
            $password &&
            hash_equals(
                $username,
                (string) $request->getUser(),
            ) &&
            hash_equals(
                $password,
                (string) $request->getPassword(),
            );

        if (! $valid) {
            return response(
                'Authentication required.',
                Response::HTTP_UNAUTHORIZED,
                [
                    'WWW-Authenticate' => 'Basic realm="Sora Developer Tools"',
                ],
            );
        }

        return $next($request);
    }
}
