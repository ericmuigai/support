<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class HandleSubdomain
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Extract subdomain from Host header or X-Subdomain header
        $subdomain = $this->extractSubdomain($request);
        
        // Add subdomain to request for easy access
        $request->merge(['subdomain' => $subdomain]);
        
        // Add CORS headers for API requests
        $response = $next($request);
        
        if ($request->isMethod('OPTIONS')) {
            $response->headers->set('Access-Control-Allow-Origin', '*');
            $response->headers->set('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, OPTIONS');
            $response->headers->set('Access-Control-Allow-Headers', 'Content-Type, Authorization, X-Requested-With, X-Subdomain');
            $response->headers->set('Access-Control-Max-Age', '3600');
        } else {
            $response->headers->set('Access-Control-Allow-Origin', '*');
            $response->headers->set('Access-Control-Allow-Credentials', 'false');
        }
        
        return $response;
    }
    
    /**
     * Extract subdomain from request
     */
    private function extractSubdomain(Request $request): ?string
    {
        // First check for X-Subdomain header (preferred for API usage)
        if ($request->hasHeader('X-Subdomain')) {
            return $request->header('X-Subdomain');
        }
        
        // Then check query parameter
        if ($request->has('subdomain')) {
            return $request->get('subdomain');
        }
        
        // Finally, try to extract from Host header
        $host = $request->getHost();
        $parts = explode('.', $host);
        
        // If we have more than 2 parts (subdomain.domain.tld), return the first part
        if (count($parts) > 2) {
            return $parts[0];
        }
        
        return null;
    }
}
