<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class MediaController extends Controller
{
    public function __invoke(string $path): StreamedResponse
    {
        $path = ltrim(str_replace('\\', '/', $path), '/');

        abort_if($path === '' || str_contains($path, '..'), 404);
        abort_unless(Storage::disk('public')->exists($path), 404);

        return Storage::disk('public')->response($path);
    }
}
