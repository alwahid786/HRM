<?php
require 'vendor/autoload.php';

use Dompdf\Dompdf;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['html_content'])) {
    $html_content = $_POST['html_content'];
    $css = "
        <style>
           
        .payslip-container {
            width: 700px;
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
            font-size: 12px;
            margin-bottom: 20px;
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
            display: flex !important;
            justify-content: center !important;
            margin-top: 30px !important;
        }
        .signature {
            text-align: center !important;
            width: 40% !important;
        }

        .footer-slip {
            text-align: center;
            margin-top: 50px;
            font-style: italic;
            font-size: 12px;
        }

        .width-25{
             width: 25%;
        }
        
        .width-65{
            width: 65%;
        }

        .boldtext{
            font-weight: 700;
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
