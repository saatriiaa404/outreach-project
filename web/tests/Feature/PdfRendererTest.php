<?php

namespace Tests\Feature;

use App\Models\StoredFile;
use App\Support\PdfRenderer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class PdfRendererTest extends TestCase
{
    use RefreshDatabase;

    public function test_html_is_rendered_to_a_pdf_with_indonesian_text()
    {
        $pdf = (new PdfRenderer)->html('<h1>LAPORAN HASIL OUTREACH</h1><p>Kelurahan Manukan Kulon — Rp 1.500.000</p>');

        $this->assertStringStartsWith('%PDF-', $pdf);
        $this->assertStringContainsString('DejaVu', $pdf, 'The bundled DejaVu Sans font is embedded.');
    }

    public function test_remote_php_and_javascript_are_disabled_and_files_are_confined()
    {
        $options = (new PdfRenderer)->dompdf()->getOptions();

        $this->assertFalse($options->getIsRemoteEnabled());
        $this->assertFalse($options->getIsPhpEnabled());
        $this->assertFalse($options->getIsJavascriptEnabled());
        $this->assertSame([realpath(resource_path('views/pdf'))], array_map('realpath', $options->getChroot()));
    }

    public function test_remote_and_out_of_folder_images_are_not_loaded()
    {
        $dompdf = (new PdfRenderer)->dompdf();
        $dompdf->loadHtml('<img src="http://127.0.0.1:9/x.png"><img src="file://'.base_path('composer.json').'">');
        $dompdf->render();

        $warnings = implode("\n", $GLOBALS['_dompdf_warnings'] ?? []);
        $this->assertStringNotContainsString('/Subtype /Image', (string) $dompdf->output());
        $this->assertNotSame('', $warnings, 'Dompdf reports the blocked images instead of fetching them.');
    }

    public function test_private_images_are_embedded_as_data_uris()
    {
        Storage::fake('local');
        $image = imagecreatetruecolor(20, 10);
        ob_start();
        imagepng($image);
        Storage::disk('local')->put('signatures/demo.png', (string) ob_get_clean());

        $file = new StoredFile;
        $file->forceFill([
            'storage_disk' => 'local', 'storage_key' => 'signatures/demo.png', 'original_name' => 'demo.png',
            'mime_type' => 'image/png', 'extension' => 'png', 'size_bytes' => 1, 'sha256_hash' => str_repeat('a', 64),
        ])->save();

        $uri = PdfRenderer::dataUri($file);
        $this->assertStringStartsWith('data:image/png;base64,', $uri);

        $pdf = (new PdfRenderer)->html('<img src="'.$uri.'" style="width:40px">');
        $this->assertStringContainsString('/Subtype /Image', $pdf);
    }
}
