<?php

namespace App\Support;

use App\Models\StoredFile;
use Dompdf\Dompdf;
use Dompdf\Options;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

/**
 * Renders HTML to PDF with Dompdf (D-013, D-052). Nothing outside the
 * document is ever loaded: remote URLs, PHP, and JavaScript are disabled and
 * local file access is confined to the PDF view folder. Images such as
 * attachments and signatures are embedded as data URIs from private storage.
 */
class PdfRenderer
{
    public function dompdf(): Dompdf
    {
        $cache = storage_path('framework/dompdf');

        if (! is_dir($cache) && ! mkdir($cache, 0775, true) && ! is_dir($cache)) {
            throw new RuntimeException('The Dompdf font cache folder could not be created.');
        }

        $options = new Options;
        $options->setIsRemoteEnabled(false);
        $options->setIsPhpEnabled(false);
        $options->setIsJavascriptEnabled(false);
        $options->setChroot([resource_path('views/pdf')]);
        $options->setFontDir($cache);
        $options->setFontCache($cache);
        $options->setTempDir($cache);
        $options->setDefaultFont('DejaVu Sans');
        $options->setDefaultPaperSize('a4');

        return new Dompdf($options);
    }

    /**
     * @param  view-string  $view
     * @param  array<string, mixed>  $data
     */
    public function view(string $view, array $data = []): string
    {
        return $this->html(view($view, $data)->render());
    }

    public function html(string $html): string
    {
        $dompdf = $this->dompdf();
        $dompdf->loadHtml($html, 'UTF-8');
        $dompdf->setPaper('a4', 'portrait');
        $dompdf->render();

        return (string) $dompdf->output();
    }

    /**
     * A private image as a data URI, so the PDF never needs a URL to it.
     */
    public static function dataUri(StoredFile $file): string
    {
        $bytes = Storage::disk($file->storage_disk)->get($file->storage_key);

        if ($bytes === null) {
            throw new RuntimeException("Stored file {$file->id} is missing.");
        }

        return "data:{$file->mime_type};base64,".base64_encode($bytes);
    }
}
