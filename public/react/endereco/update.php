<?php

if($dadosOld['rua'] !== $dados['rua'] || $dadosOld['bairro'] !== $dados['bairro'] || $dadosOld['cep'] !== $dados['cep'] || $dadosOld['cidade'] !== $dados['cidade']) {
    $read = new \Conn\Read();
    $read->exeRead("cidades", "WHERE id = :idc", "idc={$dados['cidade']}");
    if($read->getResult()) {
        $cidade = $read->getResult()[0];
        $endereco['logradouro'] = $dados['rua'] . ", " . $dados['bairro'] . ", " . $cidade['nome'] . " - " . $cidade['estado'] . ", " . $dados['cep'] . ", " . $cidade['pais'];

        $up = new \Conn\Update();
        $up->exeUpdate("endereco", $endereco, "WHERE id =:id", "id={$dados['id']}");
    }
}