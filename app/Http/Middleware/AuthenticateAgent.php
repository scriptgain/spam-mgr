<?php

namespace App\Http\Middleware;

use App\Models\Node;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Per-node bearer auth. Each node holds its own key, so one can be revoked
 * without touching the others.
 */
class AuthenticateAgent
{
    public function handle(Request $request, Closure $next): Response
    {
        $bearer = $request->bearerToken();
        if (! $bearer) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        $node = Node::findByApiKey($bearer);
        if (! $node) {
            return response()->json(['message' => 'Invalid node key.'], 401);
        }

        $request->attributes->set('agent_node', $node);

        return $next($request);
    }
}
