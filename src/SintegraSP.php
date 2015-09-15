<?php

namespace SintegraPHP\SP;

use Exception;
use Goutte\Client;
use JansenFelipe\Utils\Utils;
use Symfony\Component\DomCrawler\Crawler;

/**
 * SintegraSP
 *
 * @author Flávio H. Ferreira <flaviometalvale@gmail.com>
 * @author Jansen Felipe <jansen.felipe@gmail.com>
 */
class SintegraSP
{

    /**
     * Metodo para capturar o captcha e viewstate para enviar no metodo
     * de consulta
     *
     * @param  string $cnpj CNPJ
     * @throws Exception
     * @return array Link para ver o Captcha e Cookie
     */
    public static function getParams()
    {
        $client = new Client();
        $crawler = $client->request('GET', 'http://pfeserv1.fazenda.sp.gov.br/sintegrapfe/consultaSintegraServlet');
        $response = $client->getResponse();

        $input = $crawler->filter('input[name="paramBot"]');
        $paramBot = trim($input->attr('value'));

        $headers = $response->getHeaders();
        $cookie = $headers['Set-Cookie'][0];

        $paramBotURL = urlencode($paramBot);

        $ch = curl_init("http://pfeserv1.fazenda.sp.gov.br/sintegrapfe/imageGenerator?keycheck=" . $paramBotURL);
        $options = array(
            CURLOPT_COOKIEJAR => 'cookiejar',
            CURLOPT_HTTPHEADER => array(
                "Pragma: no-cache",
                "Origin: http://pfeserv1.fazenda.sp.gov.br",
                "Host: pfeserv1.fazenda.sp.gov.br",
                "User-Agent: Mozilla/5.0 (Windows NT 6.1; rv:32.0) Gecko/20100101 Firefox/32.0",
                "Accept: text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8",
                "Accept-Language: pt-BR,pt;q=0.8,en-US;q=0.5,en;q=0.3",
                "Accept-Encoding: gzip, deflate",
                "Referer: http://pfeserv1.fazenda.sp.gov.br/sintegrapfe/consultaSintegraServlet",
                "Cookie: flag=1; $cookie",
                "Connection: keep-alive"
            ),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => 1,
            CURLOPT_BINARYTRANSFER => true
        );

        curl_setopt_array($ch, $options);
        $img = curl_exec($ch);
        curl_close($ch);

        if (@imagecreatefromstring($img) == false) {
            throw new Exception('Não foi possível capturar o captcha');
        }

        return array(
            'cookie' => $cookie,
            'captchaBase64' => 'data:image/png;base64,' . base64_encode($img),
            'paramBot' => $paramBot
        );
    }

    /**
     * Metodo para realizar a consulta
     *
     * @param  string $cnpj CNPJ
     * @param  string $paramBot ParamBot parametro enviado para validação do captcha
     * @param  string $captcha CAPTCHA
     * @param  string $stringCookie COOKIE
     * @throws Exception
     * @return array  Dados da empresa
     */
    public static function consulta($cnpj, $paramBot, $captcha, $stringCookie)
    {
        $arrayCookie = explode(';', $stringCookie);

        if (!Utils::isCnpj($cnpj)) {
            throw new Exception('O CNPJ informado não é válido.');
        }

        $client = new Client();

        $client->getClient()->setDefaultOption('config/curl/'.CURLOPT_TIMEOUT, 0);
        $client->getClient()->setDefaultOption('config/curl/'.CURLOPT_TIMEOUT_MS, 0);
        $client->getClient()->setDefaultOption('config/curl/'.CURLOPT_CONNECTTIMEOUT, 0);
        $client->getClient()->setDefaultOption('config/curl/'.CURLOPT_RETURNTRANSFER, true);

        $client->setHeader('Host', 'pfeserv1.fazenda.sp.gov.br');
        $client->setHeader('User-Agent', 'Mozilla/5.0 (Windows NT 6.1; rv:32.0) Gecko/20100101 Firefox/32.0');
        $client->setHeader('Accept', 'text/html,application/xhtml+xml,application/xml;q=0.9, */* ;q=0.8');
        $client->setHeader('Accept-Language', 'pt-BR,pt;q=0.8,en-US;q=0.5,en;q=0.3');
        $client->setHeader('Accept-Encoding', 'gzip, deflate');
        $client->setHeader('Referer', 'http://www.sintegra.gov.br/new_bv.html');
        $client->setHeader('Cookie', $arrayCookie[0]);
        $client->setHeader('Connection', 'keep-alive');

        $param = array(
            'hidFlag' => '0',
            'cnpj' => Utils::unmask($cnpj),
            'ie' => '',
            'paramBot' => $paramBot,
            'Key' => $captcha,
            'servico' => 'cnpj',
            'botao' => 'Consulta por CNPJ'
        );

        $crawler = $client->request('POST', 'http://pfeserv1.fazenda.sp.gov.br/sintegrapfe/sintegra', $param);

        $imageError = 'O valor da imagem esta incorreto ou expirou. Verifique novamente a imagem e digite exatamente os 5 caracteres exibidos.';
        $checkError = $crawler->filter('body > center')->eq(1)->count();

        if ($checkError && $imageError == trim($crawler->filter('body > center')->eq(1)->text())) {
            throw new Exception($imageError, 99);
        }

        $center_ = $crawler->filter('body > center');

        if (count($center_) == 0) {
            throw new Exception('Serviço indisponível!. Tente novamente.', 99);
        }

        file_put_contents('resposta.html', $crawler);
    }

    /**
     * Metodo para efetuar o parser
     *
     * @param  Crawler $html HTML
     * @return array  Dados da empresa
     */
    public static function parser(Crawler $crawler){
        return [
            'cnpj' => (string) $crawler->filter("input[name='cnpj.identificacaoFormatada']")->attr('value'),
            'inscricao_estadual' => (string) $crawler->filter("input[name='inscricaoEstadual.identificacaoFormatada']")->attr('value'),
            'razao_social' => (string) $crawler->filter("input[name='nomeEmpresarial']")->attr('value'),
            'cnae_principal' => (string) $crawler->filter("input[name='cnaefPrincipal.descricao']")->attr('value'),
            'data_inscricao' => (string) $crawler->filter("input[name='dataInicioInscricao']")->attr('value'),
            'situacao' => (string) $crawler->filter("input[name='situacaoContribuinte.descricao']")->attr('value'),
            'situacao_data' => (string) $crawler->filter("input[name='dataSituacao']")->attr('value'),
            'regime_recolhimento' => (string) $crawler->filter("input[name='regimeRecolhimento.descricao']")->attr('value'),
            'motivo_suspensao' => (string) $crawler->filter("input[name='motivoSuspensao.descricao']")->attr('value'),
            'telefone' => (string) $crawler->filter("input[name='comunicacao.telefone']")->attr('value'),
            'endereco' => [
                'cep' => (string) $crawler->filter("input[name='enderecoEstabelecimento.cep']")->attr('value'),
                'logradouro' => (string) $crawler->filter("input[name='enderecoEstabelecimento.nomeTipoLogradouro']")->attr('value') . ' ' .(string) $crawler->filter("input[name='enderecoEstabelecimento.nomeLogradouro']")->attr('value'),
                'numero' => (string) $crawler->filter("input[name='enderecoEstabelecimento.numero']")->attr('value'),
                'complemento' => (string) $crawler->filter("input[name='txtComplemento']")->attr('value'),
                'bairro' => (string) $crawler->filter("input[name='enderecoEstabelecimento.nomeBairro']")->attr('value'),
                'cidade' => (string) $crawler->filter("input[name='enderecoEstabelecimento.nomeMunicipio']")->attr('value'),
                'distrito' => (string) $crawler->filter("input[name='enderecoEstabelecimento.nomePovoadoDistrito']")->attr('value'),
                'uf' => (string) $crawler->filter("input[name='enderecoEstabelecimento.sgUf_']")->attr('value'),
            ]
        ];
    }

}