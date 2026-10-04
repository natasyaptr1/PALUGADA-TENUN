<?php
namespace App\Http\Middleware;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
class PastikanPeran {
    public function handle(Request $request, Closure $next, string ...$daftarPeran): Response {
        $pengguna=$request->user();
        if (!$pengguna || !in_array($pengguna->peran,$daftarPeran,true)) {
            abort(403,'Anda tidak memiliki hak akses untuk halaman ini.');
        }
        return $next($request);
    }
}
