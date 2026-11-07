<?php
namespace App\Controllers;

class ReceiptController extends Controller
{
    public function print(): void
    {
        require APP_ROOT . '/app/shared/views/receipt_print.php';
    }
}