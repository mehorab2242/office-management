<?php

namespace App\Services;

use Dompdf\Dompdf;
use Dompdf\Options;

class PdfReportService
{
    public function render(array $data): string
    {
        $options = new Options;
        $options->set('defaultFont', 'DejaVu Sans');
        $dompdf = new Dompdf($options);
        $dompdf->loadHtml(view('reports.report', $data)->render());
        $dompdf->setPaper('A4', 'landscape');
        $dompdf->render();

        return $dompdf->output();
    }
}
