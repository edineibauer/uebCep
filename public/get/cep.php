<?php

use \Helpers\Helper;

$cep = $link->getVariaveis()[0];

/**
 * Cria Cidade, Cep e Coordenadas
 * retorna id da Cidade Criada ou existente
 *
 * @param string $cep
 * @param string $bairro
 * @param string $rua
 * @param string $cidade
 * @param string $estado
 * @param string $pais
 * @param string $latitude
 * @param string $longitude
 * @param string $ddd
 * @param string $ibge
 * @param string $altitude
 * @return int
 */
function createCidadeAndCep(string $cep, string $bairro, string $rua, string $cidade, string $estado, string $pais, string $latitude, string $longitude, string $ddd, string $ibge, string $altitude)
{
    $idCidade = 0;
    $read = new \Conn\Read();
    $create = new \Conn\Create();

    if (!empty($cidade)) {
        $read->exeRead("cidades", "WHERE nome = '{$cidade}' && ddd = :ddd", "ddd={$ddd}");
        if ($read->getResult()) {
            $idCidade = $read->getResult()[0]['id'];
        } else {
            $create->exeCreate("cidades", [
                "nome" => $cidade,
                "ddd" => $ddd,
                "ibge" => $ibge,
                "estado" => $estado,
                "pais" => $pais,
                "altitude" => $altitude
            ]);

            if (empty($create->getErro()) && $create->getResult() && !empty($cep)) {
                $idCidade = $create->getResult();
            }
        }

        if ($idCidade !== 0) {
            $create->exeCreate("cep", [
                "cep" => $cep,
                "cidade" => $idCidade,
                "bairro" => $bairro,
                "rua" => $rua,
                "latitude" => $latitude,
                "longitude" => $longitude
            ]);
        }
    }

    return $idCidade;
}

/**
 * Busca em CEP aberto
 */
if (defined('CEPABERTO') && !empty(CEPABERTO)) {
    $retorno = Helper::cepAberto($cep);
    if (!empty($retorno) && !empty($retorno['cidade']['nome'])) {
        /**
         * Teve retorno, faz o cadastro na base
         */
        $cidadeId = createCidadeAndCep($retorno['cep'] ?? "", $retorno['bairro'] ?? "", $retorno['logradouro'] ?? "", $retorno['cidade']['nome'] ?? "", $retorno['estado']['sigla'] ?? "", "BR", $retorno['latitude'] ?? "", $retorno['longitude'] ?? "", $retorno['cidade']['ddd'] ?? "", $retorno['cidade']['ibge'] ?? "", $retorno['altitude'] ?? "");

        $data['data'] = ['cidade' => $cidadeId, "bairro" => $retorno['bairro'] ?? "", "rua" => $retorno['logradouro'] ?? ""];
    }
}

/**
 * Busca em GeoCode
 */
if (empty($data['data']) && defined('GEOCODE') && !empty(GEOCODE)) {
    $retorno = Helper::cepGeoCode($cep);

    if (!empty($retorno)) {
        /**
         * Teve retorno faz o cadastro na base
         */

        //default parameters
        $param = [
            "cep" => "",
            "cidade" => "",
            "estado" => "",
            "pais" => "",
            "bairro" => "",
            "rua" => "",
            "latitude" => $retorno['geometry']['location']['lat'],
            "longitude" => $retorno['geometry']['location']['lng'],
            "ddd" => "",
            "ibge" => "",
            "altitude" => "",
        ];

        //find values in Google Geo Code $retorno content
        foreach ($retorno['address_components'] as $component) {
            if (in_array("postal_code", $component['types']))
                $param['cep'] = $component['long_name'];
            elseif (in_array("sublocality_level_1", $component['types']))
                $param['bairro'] = $component['long_name'];
            elseif (in_array("administrative_area_level_2", $component['types']))
                $param['cidade'] = $component['long_name'];
            elseif (in_array("administrative_area_level_1", $component['types']))
                $param['estado'] = $component['short_name'];
            elseif (in_array("country", $component['types']))
                $param['pais'] = $component['short_name'];
        }

        $cidadeId = createCidadeAndCep($param['cep'], $param['bairro'], $param['rua'], $param['cidade'], $param['estado'], $param['pais'], $param['latitude'], $param['longitude'], $param['ddd'], $param['ibge'], $param['altitude']);

        $data['data'] = ['cidade' => $cidadeId, "bairro" => $param['bairro'], "rua" => $param['rua']];
    }
}