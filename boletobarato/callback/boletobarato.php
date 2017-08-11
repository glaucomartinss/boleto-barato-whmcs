<?php
    /*
        Boleto Barato
        Nosso objetivo é facilitar e economizar tempo e dinheiro na administração da sua empresa!
        Website :: http://boletobarato.com.br
        Copyright (c) Boleto Barato
    */

ini_set('display_errors',1);
ini_set('display_startup_erros',1);
error_reporting(E_ALL);

require_once __DIR__ . '/../../../init.php';
require_once __DIR__ . '/../../../includes/gatewayfunctions.php';
require_once __DIR__ . '/../../../includes/invoicefunctions.php';

$gatewayModuleName = basename(__FILE__, '.php');
$gatewayParams = getGatewayVariables($gatewayModuleName);

    if (!$gatewayParams['type']) {
        die("Module Not Activated");
    }


    function moeda_unmask($valor){
        $valor = str_replace('.', '', $valor) ;
        $valor = str_replace(',', '.', $valor) ;
        $valor = str_replace('%', '', $valor) ;
        return trim($valor);
    }


    $jd = $_POST;

    if($jd['tipo'] == 'boleto.status') {
        $jd = $jd['dados'];
        $success = true;
        $invoiceId = $jd['meucodigo'];
        $transactionId = '';
        $paymentReal = moeda_unmask($jd['valor']);
        $paymentAmount = moeda_unmask($jd['valorpago']);
        $paymentFee = '0.00';

        //Busca o ID userid ligado a fatura
        $sql_tblinvoices = 'select id, userid from tblinvoices 
                        where id = "'.$invoiceId.'" limit 1';
        $tblinvoices_campo = consultaDB($sql_tblinvoices);
        $userid = $tblinvoices_campo[0]['userid'];

        $type = 'Mora e Multa';

        //Gerar relid
        $sql_relid = 'select invoiceid, relid from tblinvoiceitems order by id desc limit 1';
        $verifica_relid = consultaDB($sql_relid);
        $relid = $verifica_relid[0]['relid'] + 1;

        $sub = $paymentAmount - $paymentReal;
        $amount = number_format($sub, 2, '.', ',');
        $taxed = '0';
        $duedate = date('Y-m-d');
        $paymentmethod = 'boletobarato';
        $description = 'Mora e Multa';


        $notes = 'Este pedido foi pago com atraso, valor pago de mora e multa R$'.$amount.'';



    //Adiciona Mora e Multa   
    if($paymentAmount > $paymentReal){

        full_query("UPDATE tblinvoices SET subtotal='$paymentAmount', total='$paymentAmount' WHERE id=$invoiceId");

        full_query("UPDATE tblorders SET notes='$notes', amount='$paymentAmount' WHERE invoiceid=$invoiceId");

        full_query("INSERT INTO tblinvoiceitems 
                    (invoiceid, userid, type, relid, description, amount, taxed, duedate, paymentmethod) 
                    VALUES 
                    ('$invoiceId', '$userid', '$type', '$relid', '$description', '$amount', '$taxed', '$duedate', '$paymentmethod')");

    }else{

    }

    }else{
        echo 'Nenhum Status Identificado';
        exit;       
    }

$transactionStatus = $success ? 'Success' : 'Failure';

$invoiceId = checkCbInvoiceID($invoiceId, $gatewayParams['name']);

logTransaction($gatewayParams['name'], $_POST, $transactionStatus);

if ($success) {
    addInvoicePayment(
        $invoiceId,
        $transactionId,
        $paymentAmount,
        $paymentFee,
        $gatewayModuleName
    );

}