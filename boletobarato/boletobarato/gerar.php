<?php

    use WHMCS\Database\Capsule;

    require_once __DIR__ . '/../../../init.php';
    require_once __DIR__ . '/../../../includes/gatewayfunctions.php';
    require_once __DIR__ . '/../../../includes/invoicefunctions.php';

    function consultaDB ($sql){
        $pdo = Capsule::connection()->getPdo();

        $id_campo_cc = $pdo->prepare($sql); 
        $id_campo_cc->execute();

        return $id_campo_cc->fetchAll(PDO::FETCH_ASSOC);    
    }

    function curl_boleto_token($token, $dados){
        
        $ch = curl_init();    
          
        curl_setopt($ch, CURLOPT_URL, 'https://cobrancaporboleto.com.br/api/v1/' );        

        if ($dados){
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($dados) );
        }

        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);

        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1); 
        curl_setopt($ch, CURLOPT_USERPWD, $token);
        curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
        
        $output = curl_exec($ch);

        curl_close($ch);
        
        $resposta = json_decode($output, 1);

        return $resposta;
    }


    $invoice = $_GET['idinvoice'];

    $sql_verifica_invoice = 'select * from mod_boletobarato 
                                where invoice = "'.$invoice.'"';
    $verifica_campo_invoice = consultaDB($sql_verifica_invoice);
    $id_campo_invoice = $verifica_campo_invoice[0]['invoice'];

    if ( $invoice == $id_campo_invoice ){
        $url = $verifica_campo_invoice[0]['link'];
        //echo $url;
        $code = header("Location: $url");
        exit;
    
    }else{

    $dados['dados'] = array();
    $dados["tipo"] = "boleto.remessa";

    $boleto = unserialize(base64_decode($_POST['dadosboleto']));
    $token = $_POST['token'];
    array_push($dados['dados'], $boleto); 
    
    $resposta = curl_boleto_token($token, $dados);

    $url = $resposta['dados'][0][urlboleto];

        full_query('INSERT INTO `mod_boletobarato` (invoice, link) VALUES ("'.$invoice.'", "'.$url.'")');
        header("Location: $url");

    }
?>

