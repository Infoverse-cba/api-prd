<?php

defined('BASEPATH') or exit('No direct script access allowed');

use chriskacerguis\RestServer\RestController;

class Cnpj extends RestController
{

    function __construct()
    {
        // Construct the parent class
        parent::__construct();
        $this->load->model('Cnpj_model', 'cnpj');
        $this->load->library(['session', 'ion_auth']);
    }

    /**
     * Função para retornar uma resposta formatada.
     *
     * Esta função gera uma resposta com um formato específico que inclui status, autenticação,
     * mensagem e, opcionalmente, dados. É útil para padronizar as respostas da API.
     *
     * @param bool $status - Define o status da resposta.
     * @param string $message - Define a mensagem a ser retornada.
     * @param mixed $data - Dados a serem incluídos na resposta (opcional).
     * @param int $code - Código de status HTTP da resposta.
     * @return void
     */
    public function retorno($status, $message, $data = null, $code)
    {
        $retorno = [
            'status' => $status, // Define o status da resposta
            'logged_in' => $this->ion_auth->logged_in() ? true : false, // Verifica se o usuário está autenticado
            'is_admin' => $this->ion_auth->is_admin() ? true : false,
            'message' => $message, // Define a mensagem a ser retornada
        ];

        if ($data !== null) {
            $retorno['data'] = $data; // Inclui os dados a serem retornados
        }

        // Cria uma resposta com os dados fornecidos
        $this->response($retorno, $code); // Define o código de status HTTP da resposta
    }

    public function cnpj_post()
    {
        // Obtém dados do corpo da requisição
        $json = $this->request->body;

        $cnpj = isset($json['cnpj']) ? $json['cnpj'] : false;
        $estabelecimentos = isset($json['estabelecimentos']) ? $json['estabelecimentos'] : false;
        if ($cnpj === false) {
            $this->response([
                'status' => false,
                'message' => 'Informe um CNPJ.'
            ], 400);
            return;
        }

        //$cnpj = substr(str_replace(['.', '-', '/'], '', $cnpj), 0, 8);
        $cnpj = str_replace(['.', '-', '/'], '', $cnpj);
        if (strlen($cnpj) !== 14) {
            $this->response([
                'status' => false,
                'message' => 'Informe um CNPJ válido.'
            ], 400);
            return;
        }

        // Verifica e valida o parâmetro 'cnpj'
        if (isset($cnpj)) {
            if (!is_numeric($cnpj)) {
                $this->retorno(false, "O parâmetro 'cnpj' aceita apenas números.", null, 403);
            }
        }

        // Verifica e valida o parâmetro 'executado'
        if (isset($estabelecimentos)) {
            if (!is_bool($estabelecimentos)) {
                $this->retorno(false, "O parâmetro 'estabelecimentos' aceita apenas true ou false.", null, 403);
            }
        }

        $cnpj_basico = substr($cnpj, 0, 8); // Pega os primeiros 8 digitos
        $cnpj_ordem = substr($cnpj, 8, 4); // Pega do 9 ao 12 digito
        $cnpj_dv = substr($cnpj, 12, 2); // Pega do 13 ao 14 digito

        $data = [];

        $cnpj_result = $this->cnpj->getEstabelecimento($cnpj_basico, $cnpj_ordem, $cnpj_dv);
        if ($cnpj_result) {

            $cnpj_result->cnae_fiscal_principal = $this->cnpj->getCnae($cnpj_result->cnae_fiscal_principal);
            $cnpj_result->cnae_fiscal_secundaria = $this->cnpj->getCnae($cnpj_result->cnae_fiscal_secundaria);

            $empresa_result = $this->cnpj->getEmpresa($cnpj_basico);

            $data['empresa'] = $empresa_result;
            $data['estabelecimento'] = $cnpj_result;

            if ($estabelecimentos) {
                $estabelecimentos_result = $this->cnpj->getEstabelecimentos($cnpj_basico);

                if ($estabelecimentos_result) {
                    $data['estabelecimentos'] = (object) $estabelecimentos_result;
                }
            }

            $simples_result = $this->cnpj->getSimples($cnpj_basico);
            if ($simples_result) {
                $data['simples'] = $simples_result;
            }

            $socios_result = $this->cnpj->getSocios($cnpj_basico);
            if ($socios_result) {
                $data['socios'] = (object) $socios_result;
            }

            $this->retorno(true, "Socio encontrado com sucesso!", (object) $data, 200);
        } else {
            /*$this->response([
                'status' => false,
                'message' => 'Socio não encontrado.'
            ], 404);*/
            $this->retorno(false, "Nenhuma empresa foi encontrada.", null, 400);
        }
    }

    public function socio_post()
    {
        // Obtém dados do corpo da requisição
        $json = $this->request->body;

        $cpf = isset($json['cpf']) ? $json['cpf'] : false;
        $nome = isset($json['nome']) ? $json['nome'] : false;
        if ($cpf === false) {
            $this->response([
                'status' => false,
                'message' => 'Informe um CPF.'
            ], 400);
            return;
        }

        //$cnpj = substr(str_replace(['.', '-', '/'], '', $cnpj), 0, 8);
        $cpf = str_replace(['.', '-', '/'], '', $cpf);
        if (strlen($cpf) !== 11) {
            $this->response([
                'status' => false,
                'message' => 'Informe um CPF válido.'
            ], 400);
            return;
        }

        // Verifica e valida o parâmetro 'cpf'
        if (isset($cpf)) {
            if (!is_numeric($cpf)) {
                $this->retorno(false, "O parâmetro 'cpf' aceita apenas números.", null, 403);
            }
        }

        $nome = iconv('UTF-8', 'ASCII//TRANSLIT', $nome);

        $cpf_result = $this->cnpj->getSocioCpf($cpf, $nome);

        if ($cpf_result) {
            // Supondo que $cpf_result seja o array que você deseja percorrer e adicionar o índice 'cnpj_completo'
            foreach ($cpf_result as &$item) {
                // Aqui você pode adicionar a lógica para criar o 'cnpj_completo'
                // Por exemplo, concatenando duas chaves existentes para formar o cnpj_completo:
                $item->cnpj_completo = $this->cnpj->getEstabelecimentos($item->cnpj_basico, true)->cnpj;
            }
        }

        if ($cpf_result) {
            $this->retorno(true, "Socio encontrado com sucesso!", (object) $cpf_result, 200);
        } else {
            $this->retorno(false, "Socio não encontrado.", null, 400);
        }
    }

    public function razao_post()
    {
        // Obtém dados do corpo da requisição
        $json = $this->request->body;

        $razao_social = isset($json['razao_social']) ? $json['razao_social'] : false;

        if (!$razao_social) {
            $this->retorno(false, "Informe os parâmetros nescessários.", null, 403);
        }

        $razao_social = iconv('UTF-8', 'ASCII//TRANSLIT', $razao_social);
        $razao_social = strtoupper($razao_social);

        $empresa_result = $this->cnpj->getEmpresasRazao($razao_social);

        foreach ($empresa_result as &$item) {
            $item->cnpj_completo = $this->cnpj->getEstabelecimentos($item->cnpj_basico, true)->cnpj;
        }


        if ($empresa_result) {
            $this->retorno(true, "Empresa encontrada com sucesso!", (object) $empresa_result, 200);
        } else {
            /*$this->response([
                'status' => false,
                'message' => 'Nenhuma empresa foi encontrada.'
            ], 404);*/
            $this->retorno(false, "Nenhuma empresa foi encontrada.", null, 400);
        }
    }

    public function estabelecimentos_post()
    {
        // Obtém dados do corpo da requisição
        $json = $this->request->body;

        $cnpj_basico = isset($json['cnpj_basico']) ? $json['cnpj_basico'] : false;

        if (!$cnpj_basico) {
            $this->retorno(false, "Informe os parâmetros nescessários.", null, 403);
        }


        $estabelecimentos_result = $this->cnpj->getEstabelecimentos($cnpj_basico);
        if ($estabelecimentos_result) {
            $this->retorno(true, "Empresa encontrada com sucesso!", (object) $estabelecimentos_result, 200);
        } else {
            /*$this->response([
                'status' => false,
                'message' => 'Nenhuma empresa foi encontrada.'
            ], 404);*/

            $this->retorno(false, "Nenhuma empresa foi encontrada.", null, 400);
        }
    }
}
