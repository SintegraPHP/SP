<?php

require_once '../vendor/autoload.php';

use SintegraPHP\SP\SintegraSP;

if(isset($_POST['captcha']) && isset($_POST['paramBot']) && isset($_POST['cookie']) && isset($_POST['cnpj'])){

    $result = SintegraSP::consulta($_POST['cnpj'], $_POST['paramBot'], $_POST['captcha'], $_POST['cookie']);

    var_dump($result);
    die;

}else
    $params = SintegraSP::getParams();
?>

<img src="<?php echo $params['captchaBase64'] ?>" />

<form method="POST">
    <input type="hidden" name="cookie" value="<?php echo $params['cookie'] ?>" />
    <input type="hidden" name="paramBot" value="<?php echo $params['paramBot'] ?>" />

    <input type="text" name="captcha" placeholder="Captcha" />
    <input type="text" name="cnpj" placeholder="CNPJ" value="60990751000124" />

    <button type="submit">Consultar</button>
</form>