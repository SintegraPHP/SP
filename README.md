# Sintegra SP

[![Travis](https://travis-ci.org/SintegraPHP/SP.svg?branch=1.0)](https://travis-ci.org/SintegraPHP/SP)
[![Latest Stable Version](https://poser.pugx.org/sintegra-php/SP/v/stable)](https://packagist.org/packages/sintegra-php/SP) 
[![Total Downloads](https://poser.pugx.org/sintegra-php/SP/downloads)](https://packagist.org/packages/sintegra-php/SP)
[![Latest Unstable Version](https://poser.pugx.org/sintegra-php/SP/v/unstable)](https://packagist.org/packages/sintegra-php/SP)
[![License](https://poser.pugx.org/sintegra-php/SP/license)](http://opensource.org/licenses/MIT)

Consulte gratuitamente CNPJ no site do Sintegra/SP

### Como utilizar

Adicione a library

```sh
$ composer require sintegra-php/SP
```

Adicione o autoload.php do composer no seu arquivo PHP.

```php
require_once 'vendor/autoload.php';  
```

Primeiro chame o método `getParams()` para retornar os dados necessários para enviar no método `consulta()` 

```php
$params = SintegraPHP\SP\SintegraSP::getParams();
```

Agora basta chamar o método `consulta()`

```php
$dadosEmpresa = SintegraPHP\SP\SintegraSP::consulta(
    '07399636001179',
    'INFORME_AS_LETRAS_DO_CAPTCHA',
    $params['challenge']
);
```

### License

The MIT License (MIT)
