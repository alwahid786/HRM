<?php
require 'vendor/autoload.php';

use Dompdf\Dompdf;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['html_content'])) {
    $html_content = $_POST['html_content'];
    $css = "
        <style>
            .payslip-container {
                width: 700px;
                margin: 20px auto;
                padding: 20px;
                border: 1px solid #000;
            }
            .payslip-header {
                text-align: center;
                margin-bottom: 20px;
            }
            .payslip-header h1 {
                margin: 0;
                font-size: 24px;
                font-weight: bold;
            }
            .company-info, .employee-info {
                margin-bottom: 20px;
            }
            .info-table {
                width: 100%;
                margin-bottom: 20px;
            }
            .info-table td {
                padding: 8px 5px;
            }
            .info-table td:first-child {
                text-align: left;
            }
            .info-table td:last-child {
                text-align: right;
            }
            table {
                width: 100%;
                border-collapse: collapse;
                margin-bottom: 20px;
                font-size: 12px;
            }
            th, td {
                padding: 10px;
                border: 1px solid black;
            }
            th {
                background-color: #f3f3f3;
            }
            .total-row td {
                font-weight: bold;
            }
            .net-pay {
                text-align: center;
                font-size: 24px;
                font-weight: bold;
                margin: 20px 0;
            }
            .signatures {
                display: flex;
                justify-content: space-between;
                margin-top: 30px;
            }
            .signature {
                text-align: center;
                width: 40%;
            }
            .footer-slip {
                text-align: center;
                margin-top: 50px;
                font-style: italic;
                font-size: 12px;
            }
        </style>
    ";

    $full_html_content = $css . $html_content;
    $dompdf = new Dompdf();
    $dompdf->loadHtml($full_html_content);
    $dompdf->setPaper('A4', 'portrait');
    $dompdf->render();
    $dompdf->stream("salary_slip.pdf", ["Attachment" => 1]); 
}
?>
