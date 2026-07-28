<?php

namespace App\Http\Controllers;

class FaviconController extends Controller
{
    /** The brand accent (DB-driven), validated to a hex color. */
    private function accent(): string
    {
        $a = (string) config('brand.accent', '#f59e0b');

        return preg_match('/^#[0-9a-fA-F]{6}$/', $a) ? $a : '#f59e0b';
    }


    /**
     * SVG path for the brand glyph.
     *
     * Reads App\Support\Icons, the same registry the <x-icon> component uses. This
     * controller used to carry its own short copy of the map, and because that copy
     * had no entry for this product's glyph the favicon fell through to the
     * scaffold's shield: the browser tab showed another product's mark.
     */
    private function iconPath(): string
    {
        $name = (string) config('brand.icon', 'shield');
        $markup = \App\Support\Icons::path($name, 'shield') ?? '';

        // The registry stores full <path> markup; the favicon needs the `d` attribute.
        preg_match('/ d="([^"]+)"/', $markup, $m);

        return $m[1] ?? '';
    }

    /** Scalable favicon: the brand glyph in the brand accent on dark chrome. */
    public function svg()
    {
        $accent = $this->accent();
        $svg = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 32 32" width="32" height="32">'
            . '<rect width="32" height="32" rx="7" fill="#0b1220"/>'
            . '<g transform="translate(4 4)" fill="none" stroke="' . $accent . '" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round">'
            . '<path d="' . $this->iconPath() . '"/>'
            . '</g></svg>';

        return response($svg, 200, [
            'Content-Type' => 'image/svg+xml',
            'Cache-Control' => 'public, max-age=3600',
        ]);
    }

    public function faviconPng()
    {
        return $this->png(64);
    }

    public function appleIcon()
    {
        return $this->png(180);
    }

    /**
     * Normalised polygon for the PNG mark, keyed by brand glyph.
     *
     * GD cannot render an SVG path, so the raster favicon needs its own simple shape.
     * This used to be a hardcoded shield-and-tick no matter what the product was, which
     * meant every product shipped the scaffold's mark in a different colour. Anything
     * without an entry gets a neutral rounded mark, never another product's logo.
     */
    private const PNG_SHAPES = [
        // Funnel: mail narrowed down to what gets through.
        'filter' => [[0.18, 0.20], [0.82, 0.20], [0.57, 0.52], [0.57, 0.85], [0.43, 0.75], [0.43, 0.52]],
        'shield' => [[0.25, 0.24], [0.50, 0.16], [0.75, 0.24], [0.75, 0.55], [0.50, 0.82], [0.25, 0.55]],
        'envelope' => [[0.16, 0.30], [0.84, 0.30], [0.84, 0.70], [0.16, 0.70]],
        'inbox' => [[0.16, 0.30], [0.84, 0.30], [0.84, 0.70], [0.16, 0.70]],
    ];

    /** PNG fallback: dark rounded square with the brand glyph in the accent. */
    private function png(int $size)
    {
        [$r, $g, $b] = sscanf($this->accent(), '#%02x%02x%02x');

        $im = imagecreatetruecolor($size, $size);
        imagesavealpha($im, true);
        imagealphablending($im, false);
        imagefill($im, 0, 0, imagecolorallocatealpha($im, 0, 0, 0, 127));
        imagealphablending($im, true);

        $bg = imagecolorallocate($im, 0x0b, 0x12, 0x20);
        $accent = imagecolorallocate($im, $r, $g, $b);

        $rad = (int) round($size * 0.22);
        imagefilledrectangle($im, $rad, 0, $size - $rad, $size, $bg);
        imagefilledrectangle($im, 0, $rad, $size, $size - $rad, $bg);
        foreach ([[$rad, $rad], [$size - $rad, $rad], [$rad, $size - $rad], [$size - $rad, $size - $rad]] as [$cx, $cy]) {
            imagefilledellipse($im, $cx, $cy, $rad * 2, $rad * 2, $bg);
        }

        $shape = self::PNG_SHAPES[(string) config('brand.icon')] ?? null;

        if ($shape === null) {
            // Neutral fallback: a plain accent disc. Deliberately not a shield.
            imagefilledellipse($im, (int) ($size / 2), (int) ($size / 2), (int) ($size * 0.5), (int) ($size * 0.5), $accent);
        } else {
            $pts = [];
            foreach ($shape as [$x, $y]) {
                $pts[] = (int) round($x * $size);
                $pts[] = (int) round($y * $size);
            }
            imagefilledpolygon($im, $pts, $accent);
        }

        ob_start();
        imagepng($im);
        $data = ob_get_clean();
        imagedestroy($im);

        return response($data, 200, [
            'Content-Type' => 'image/png',
            'Cache-Control' => 'public, max-age=3600',
        ]);
    }
}
