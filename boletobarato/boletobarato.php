<style>
.btn_novoboleto {
    -moz-box-shadow: 0px 4px 14px -7px #000000;
    -webkit-box-shadow: 0px 4px 14px -7px #000000;
    box-shadow: 0px 4px 14px -7px #000000;
    background:-webkit-gradient(linear, left top, left bottom, color-stop(0.05, #4a4a4a), color-stop(1, #000000));
    background:-moz-linear-gradient(top, #4a4a4a 5%, #000000 100%);
    background:-webkit-linear-gradient(top, #4a4a4a 5%, #000000 100%);
    background:-o-linear-gradient(top, #4a4a4a 5%, #000000 100%);
    background:-ms-linear-gradient(top, #4a4a4a 5%, #000000 100%);
    background:linear-gradient(to bottom, #4a4a4a 5%, #000000 100%);
    filter:progid:DXImageTransform.Microsoft.gradient(startColorstr='#4a4a4a', endColorstr='#000000',GradientType=0);
    background-color:#4a4a4a;
    -moz-border-radius:3px;
    -webkit-border-radius:3px;
    border-radius:3px;
    display:inline-block;
    cursor:pointer;
    color:#ffffff  !important;
    font-family:Arial;
    font-size:20px  !important;
    font-weight:bold;
    padding:12px 27px;
    text-decoration:none !important;
    text-shadow:0px 1px 0px #3d768a;
}
.btn_novoboleto:hover {
    background:-webkit-gradient(linear, left top, left bottom, color-stop(0.05, #000000), color-stop(1, #4a4a4a));
    background:-moz-linear-gradient(top, #000000 5%, #4a4a4a 100%);
    background:-webkit-linear-gradient(top, #000000 5%, #4a4a4a 100%);
    background:-o-linear-gradient(top, #000000 5%, #4a4a4a 100%);
    background:-ms-linear-gradient(top, #000000 5%, #4a4a4a 100%);
    background:linear-gradient(to bottom, #000000 5%, #4a4a4a 100%);
    filter:progid:DXImageTransform.Microsoft.gradient(startColorstr='#000000', endColorstr='#4a4a4a',GradientType=0);
    background-color:#000000;
}
.btn_novoboleto:active {
    position:relative;
    top:1px;
}

.alert-danger {
    color: #a94442;
    background-color: #f2dede;
    border-color: #ebccd1;
}

.alert {
    padding: 15px;
    margin-bottom: 20px;
    border: 1px solid transparent;
    border-radius: 4px;
}

.alert-danger .alert-link {
    color: #843534;
}

.alert .alert-link {
    font-weight: 700;
}
</style>

<?php
    /*
        Boleto Barato
        Nosso objetivo é facilitar e economizar tempo e dinheiro na administração da sua empresa!
        Website :: http://boletobarato.com.br
        Copyright (c) Boleto Barato
    */

    if (!defined("WHMCS")) {
        die("This file cannot be accessed directly");
    }

    use WHMCS\Database\Capsule;

    function consultaDB ($sql){
        $pdo = Capsule::connection()->getPdo();

        $id_campo_cc = $pdo->prepare($sql); 
        $id_campo_cc->execute();

        return $id_campo_cc->fetchAll(PDO::FETCH_ASSOC);    
    }

    function viacep($cep){

        $cep = preg_replace("/[^0-9]/", "", $cep);
        $url = "https://viacep.com.br/ws/$cep/json/unicode/";

        $ch = curl_init();      

        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1); 

        $output = curl_exec($ch); 

        $resposta = json_decode($output, 1);

        if ( is_numeric( @$resposta['ibge'] ) ){
            return $resposta;
        }else{
            return false;
        }
    }

function boletobarato_MetaData()
{
    return array(
        'DisplayName' => 'Boleto Barato',
        'APIVersion' => '1.1', // Use API Version 1.1
        'DisableLocalCredtCardInput' => true,
        'TokenisedStorage' => false,
    );
}

function boletobarato_config() {

    // cria tabela de controle caso ela nao exista
    full_query("CREATE TABLE IF NOT 
                EXISTS `mod_boletobarato` (`id` int(11) unsigned NOT NULL AUTO_INCREMENT, 
                `invoice` int(11) DEFAULT NULL,  
                `link` varchar(255) DEFAULT NULL, PRIMARY KEY (`id`), 
                KEY `IX_INVOICE` (`invoice`)) ENGINE=InnoDB AUTO_INCREMENT=100 DEFAULT CHARSET=utf8;");

    // verificar se existe o campo personalizado CPF/CNPJ
    $sql_verifica_campo_cc = 'select id, fieldname, fieldtype, required from tblcustomfields 
                                where fieldname = "CPF/CNPJ" and fieldtype = "text" and 
                                required = "on" and type = "client"';
    $verifica_campo_cc = consultaDB($sql_verifica_campo_cc);

    if ( count($verifica_campo_cc) == 0 ){
        echo '  <center>
                    <div class="alert alert-danger">
                        Campo CPF/CNPJ deve ser preenchido! 
                        <a href="./configcustomfields.php" class="alert-link">
                            Criar Campo "CPF/CNPJ"
                        </a>.
                            <br><br>
                        <img src="/modules/gateways/boletobarato/cpf_cnpj.jpeg" alt="Mountain View" style="width:50%;">
                    </div>
                </center>';
        exit;
    }

    return array(
        'FriendlyName' => array(
            'Type' => 'System',
            'Value' => 'Boleto Barato',
        ),
        'token' => array(
            'FriendlyName' => 'Token da API',
            'Type' => 'text',
            'Size' => '32',
            'Default' => '',
            'Description' => 'Entre com o Tokem do <a href="http://boletobarato.com.br" target="_blank"> Boleto Barato </a>',
        ),
        'mora' => array(
            'FriendlyName' => 'Mora (ao Mês)',
            'Type' => 'text',
            'Size' => '4',
            'Default' => '2,00',
            'Description' => '(X,XX% am) de Mora',
        ),
        'multa' => array(
            'FriendlyName' => 'Multa',
            'Type' => 'text',
            'Size' => '4',
            'Default' => '1,00',
            'Description' => '(X,XX%) de Multa ',
        ),
        'bb_dias' => array(
            'FriendlyName' => 'Vencimento com',
            'Type' => 'dropdown',
            'Options' => array(
                '0' => '00','1' => '01','2' => '02','3' => '03','4' => '04','5' => '05','6' => '06','7' => '07','8' => '08','9' => '09','10' => '10','11' => '11','12' => '12','13' => '13','14' => '14','15' => '15'),
            'Description' => 'Dia(as)',
        ),
    );
}

function boletobarato_link($params) {


    // verificar se existe o campo personalizado CPF/CNPJ
    $sql_verifica_campo_cc = 'select id, fieldname, fieldtype, required from tblcustomfields 
                                where fieldname = "CPF/CNPJ" and fieldtype = "text" and 
                                required = "on" and type = "client"';
    $verifica_campo_cc = consultaDB($sql_verifica_campo_cc);

    if ( count($verifica_campo_cc) == 0 ){
        echo '<center><div class="alert alert-danger">Campo CPF/CNPJ deve ser preenchido! <a href="./clientarea.php?action=details#footer" class="alert-link">Alterar Dados</a>.
                            </div></center>';
        exit;
    }

    $id_campo_cpfcnpj = $verifica_campo_cc[0]['id'];
    $id_cliente = $params['clientdetails']['userid'];

    // Retornar o valor do campo personalizado CPF/CNPJ
    $sql_cpfcnpj = "select value from tblcustomfieldsvalues 
                    where relid = $id_cliente and fieldid = $id_campo_cpfcnpj ";
    $campo_cpfcnpj = consultaDB($sql_cpfcnpj);

    $cpfcnpj = $campo_cpfcnpj[0]['value'];

    if ( $cpfcnpj == '' ){
        echo '<center><div class="alert alert-danger">Campo CPF/CNPJ não pode estar em branco! <a href="./clientarea.php?action=details#footer" class="alert-link">Alterar Dados</a>.
                            </div></center>';
        exit;
    }

    // Verifica se a data de vencimento é diferente
    $invoice = $params['invoiceid'];
    $sql_venci = 'select * from tblinvoices where id="'.$invoice.'" ';
    $campo_venci = consultaDB($sql_venci);

    $duedate = $campo_venci[0]['duedate'];
    $date = $campo_venci[0]['date'];

    if($duedate == $date){

    $dia_venc = $params['bb_dias'];
    $dias = strtotime('+'.$dia_venc.' days');
    $vencimento = date('d/m/Y', $dias);

    }else{

    $dias = strtotime($duedate);
    $vencimento = date('d/m/Y', $dias);

    }

    $token = $params['token'];
    $invoiceId = $params['invoiceid'];
    $description = $params["description"];
    $amount = $params['amount'];
    $amount_brl = number_format($amount, 2, ',', '.');
    $currencyCode = $params['currency'];
    $firstname = $params['clientdetails']['firstname'];
    $lastname = $params['clientdetails']['lastname'];
    $email = $params['clientdetails']['email'];
    $postcode = $params['clientdetails']['postcode'];
    $phone = $params['clientdetails']['phonenumber'];
    $userid = $params['clientdetails']['userid'];
    $companyName = $params['companyname'];
    $systemUrl = $params['systemurl'];
    $returnUrl = $params['returnurl'];
    $langPayNow = $params['langpaynow'];
    $moduleDisplayName = $params['name'];
    $moduleName = $params['paymentmethod'];
    $whmcsVersion = $params['whmcsVersion'];    

    $boleto = array();

    $boleto["config.urlretorno"] = $systemUrl.'/callback/boletobarato.php';

    $boleto["config.enviaemail"] = 0;
    $boleto["config.enviasms"] = 0;
    $boleto["config.recobrar"] = 0;

    $boleto["cliente.atualizadados"] = "1"; 
    $boleto["cliente.nome"] = $firstname." ".$lastname;
    $boleto["cliente.cpfcnpj"] = $cpfcnpj;
    $boleto["cliente.celular"] = $phone;
    $boleto["cliente.email"] = $email;
    $boleto["cliente.fixo"] = "";

    $viaCEP = viacep($postcode);

    if($viaCEP != false){
        $boleto["cliente.logradouro"] = @$viaCEP['logradouro'];
        $boleto["cliente.numero"] = "";
        $boleto["cliente.complemento"] = @$viaCEP['complemento'];
        $boleto["cliente.bairro"] = @$viaCEP['bairro'];
        $boleto["cliente.cep"] = $postcode;
        $boleto["cliente.cidade"] = @$viaCEP['ibge'];
        $boleto["cliente.uf"] = @$viaCEP['uf'];
    }else{
        echo '<center>CEP não encontrado</center>';
        exit;
    }

    // esses dados só são obrigatórios para clientes CNPJ
    $boleto["cliente.nomeresponsavel"] = "";
    $boleto["cliente.cpfresponsavel"] = "";

    // se o formato do boleto vai ser carnê
    $boleto["boleto.carne"] = "0";

    // codigo numerico de indentificação do boleto no seu sistema (geralmente PK)
    $boleto["boleto.meucodigo"] = $invoiceId;

    // formato português mesmo
    $boleto["boleto.valor"] = $amount_brl;



    $boleto["boleto.datavencimento"] = $vencimento;
    $boleto["boleto.numerodoc"] = date('Y').'/'.$invoiceId;
    
    // Assunto/Título do E-mail
    $boleto["boleto.assunto"] = "";

    // 1 é porcentagem, 2 é valor fixo
    $boleto["boleto.tipmora"] = "2";
    $boleto["boleto.mora"] = $params['mora'];
    $boleto["boleto.tipmulta"] = "2";
    $boleto["boleto.multa"] = $params['multa'];

    // ÍTENS
    $sql_itens = "select description, amount  from tblinvoiceitems where invoiceid = $invoiceId order by id";
    $itens = consultaDB($sql_itens);

    $itens_parse = array();

    $i = 1;
    foreach ($itens as $key => $v) {
        
        array_push($itens_parse, array(
                                'id' => $i,
                                'tip' => 'C',
                                'descricao' => $v['description'],
                                'quantidade' => '1',
                                'valor' => number_format($v['amount'], 2, ',', '.')
                            )
        );
    
        $i++;
    
    }
    $itens_parse = serialize($itens_parse);
    
    
    // descritivo em tabela
    $boleto["boleto.descritivo_itens"] = $itens_parse;

    // descritivo que vai na parte de baixo do boleto, para o caixa
    $boleto["boleto.corpoboleto"] = "";

    // especie do Boleto, a maioria é Duplicata Mercantil (DM)
    $boleto["boleto.especie"] = "DM";   

    // mostra o array completo da resposta
    $url = './modules/gateways/boletobarato/gerar.php?idinvoice='.$invoiceId;

    $postfields = array();
    $postfields['callback_url'] = $url;
    $postfields['return_url'] = $returnUrl;
    $postfields['token'] = $token;

    $htmlOutput = '<form method="post" action="' . $url . '">';
    foreach ($postfields as $k => $v) {
        $htmlOutput .= '<input type="hidden" name="' . $k . '" value="' . urlencode($v) . '" />';
    }


    $htmlOutput .= '<input type="hidden" name="dadosboleto" value="' . base64_encode(serialize($boleto)) . '" />';


    $htmlOutput .= '<input class="btn_novoboleto" type="submit" value="' . $langPayNow . '" />';
    $htmlOutput .= '</form>';

    return $htmlOutput;

    return $url;
}