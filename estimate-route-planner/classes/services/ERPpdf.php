<?php
    
    class ERPpdf {
    
        public function createPDF($pdf_content) {
            $dompdf = new DOMPDF();
            $dompdf->load_html($pdf_content);
            $dompdf->render();
            echo $dompdf->output();
        }
    }