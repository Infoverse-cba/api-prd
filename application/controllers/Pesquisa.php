<?php

defined('BASEPATH') or exit('No direct script access allowed');

use chriskacerguis\RestServer\RestController;



class Pesquisa extends RestController
{
    function __construct()
    {
        // Construct the parent class
        parent::__construct();
        $this->load->helper('license_helper'); // Carrega o helper personalizado
        $this->load->model('Pesquisa_model', 'pesquisa');
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

    public function pesquisa_get($opcao = null, $id = null)
    {
        // Verifica se o usuário está autenticado
        if (!$this->ion_auth->logged_in()) {
            $this->retorno(false, "Usuário não autenticado.", null, 401);
        }

        // Se um ID foi fornecido, busca o grupo correspondente
        if (strtolower($opcao) == 'id' && $id) {
            $avulsa = $this->pesquisa->getPesquisa($id, $this->session->userdata('cliente_id'));

            if ($avulsa) {
                // Retorna o grupo com os dados adicionais
                $this->retorno(true, "Pesquisa encontrada com sucesso.", (object) $avulsa, 200);
            } else {
                // Se o grupo não foi encontrado, retorna uma resposta com status 404
                $this->retorno(true, "Nenhuma pesquisa encontrada com o ID fornecido.", null, 404);
            }
        }

        if (strtolower($opcao) == 'bot' && $id != null) {
            // Se nenhum ID foi fornecido, busca todos os grupos
            $avulsa = $this->pesquisa->getPesquisasBot($id, $this->session->userdata('cliente_id'));

            if ($avulsa) {
                // Retorna o grupo com os dados adicionais
                $this->retorno(true, "Pesquisa encontrada com sucesso.", (object) $avulsa, 200);
            } else {
                // Se o grupo não foi encontrado, retorna uma resposta com status 404
                $this->retorno(true, "Nenhuma pesquisa encontrada com o ID fornecido.", null, 404);
            }
        }

        if (strtolower($opcao) == 'credencial' && $id != null) {
            // Se nenhum ID foi fornecido, busca todos os grupos
            $avulsa = $this->pesquisa->getPesquisasCredencial($id, $this->session->userdata('cliente_id'));

            if ($avulsa) {
                // Retorna o grupo com os dados adicionais
                $this->retorno(true, "Pesquisa encontrada com sucesso.", (object) $avulsa, 200);
            } else {
                // Se o grupo não foi encontrado, retorna uma resposta com status 404
                $this->retorno(true, "Nenhuma pesquisa encontrada com o ID fornecido.", null, 404);
            }
        }

        if (strtolower($opcao) == 'investigacao' && $id != null) {
            // Se nenhum ID foi fornecido, busca todos os grupos
            $avulsa = $this->pesquisa->getPesquisasInvestigacao($id, $this->session->userdata('cliente_id'));

            if ($avulsa) {
                // Retorna o grupo com os dados adicionais
                $this->retorno(true, "Pesquisa encontrada com sucesso.", (object) $avulsa, 200);
            } else {
                // Se o grupo não foi encontrado, retorna uma resposta com status 404
                $this->retorno(true, "Nenhuma pesquisa encontrada com o ID fornecido.", null, 404);
            }
        }

        $avulsa = $this->pesquisa->getPesquisas($this->session->userdata('cliente_id'));

        if ($avulsa) {
            // Retorna o grupo com os dados adicionais
            $this->retorno(true, "Pesquisa encontrada com sucesso.", (object) $avulsa, 200);
        } else {
            // Se o grupo não foi encontrado, retorna uma resposta com status 404
            $this->retorno(true, "Nenhuma pesquisa encontrada.", null, 404);
        }
    }

    public function pesquisa_post()
    {
        // Verifica se o usuário está autenticado
        if (!$this->ion_auth->logged_in()) {
            $this->retorno(false, "Usuário não autenticado.", null, 401);
        }

        // Verifica se o usuário é um administrador
        if (!$this->ion_auth->is_admin()) {
            $this->retorno(false, "Este usuário não é administrador.", null, 401);
        }

        // Obtém dados do corpo da requisição
        $json = $this->request->body;

        // Extrai parâmetros obrigatórios do JSON
        $clienteId = $this->session->userdata('cliente_id');
        $id_bot = isset($json['id_bot']) ? $json['id_bot'] : null; // Obrigatorio
        $id_pesquisa_avulsa = isset($json['id_pesquisa_avulsa']) ? $json['id_pesquisa_avulsa'] : 0;
        $investigacao_id = isset($json['investigacao_id']) ? $json['investigacao_id'] : 0; // Obrigatorio
        $id_credencial = isset($json['id_credencial']) ? $json['id_credencial'] : null; // Obrigatorio
        $id_usuario = isset($json['id_usuario']) ? $json['id_usuario'] : 0; // Obrigatorio
        $palavra_chave = isset($json['palavra_chave']) ? $json['palavra_chave'] : null; // Obrigatorio
        $filtrar_por = isset($json['filtrar_por']) ? $json['filtrar_por'] : null;
        $header = isset($json['header']) ? $json['header'] : null;
        $usuario_publicacao = isset($json['usuario_publicacao']) ? $json['usuario_publicacao'] : null;
        $link_usuario = isset($json['link_usuario']) ? $json['link_usuario'] : null;
        $link_publicacao = isset($json['link_publicacao']) ? $json['link_publicacao'] : null;
        $body = isset($json['body']) ? $json['body'] : null;
        $dt_pesquisa = isset($json['dt_pesquisa']) ? $json['dt_pesquisa'] : null;
        $bytea = isset($json['bytea']) ? $json['bytea'] : null;

        // Verifica se os parâmetros obrigatórios foram fornecidos
        if ($clienteId == null) {
            $this->retorno(false, "Favor informar o ID do cliente.", null, 403);
        }

        if ($id_bot == null) {
            $this->retorno(false, "Favor informar o ID do bot.", null, 403);
        }

        if ($id_credencial == null) {
            $this->retorno(false, "Favor informar o ID da credencial.", null, 403);
        }

        if ($investigacao_id == null) {
            $this->retorno(false, "Favor informar o ID da investigação.", null, 403);
        }

        if ($id_usuario == null) {
            $this->retorno(false, "Favor informar o ID do usuário.", null, 403);
        }

        if ($palavra_chave == null) {
            $this->retorno(false, "Favor informar uma palavra-chave.", null, 403);
        }

        if ($bytea == null) {
            $this->retorno(false, "Favor informar uma screenshot.", null, 403);
        }

        // Cria um array com os dados da credencial, criptografando os campos sensíveis
        $dados = [
            'id_bot' => $id_bot,
            'id_cliente' => $clienteId,
            'id_credencial' => $id_credencial,
            'id_usuario' => $id_usuario,
            'investigacao_id' => $investigacao_id,
            'id_pesquisa_avulsa' => $id_pesquisa_avulsa,
            'palavra_chave' => $palavra_chave,
            'filtrar_por' => $filtrar_por,
            'header' => $header,
            'usuario_publicacao' => $usuario_publicacao,
            'link_usuario' => $link_usuario,
            'link_publicacao' => $link_publicacao,
            'body' => $body,
            'dt_pesquisa' => $dt_pesquisa,
        ];

        // Chama o método para criar a credencial no modelo
        $avulsa_id = $this->pesquisa->createPesquisa($dados);

        if ($avulsa_id) {
            $dados = [
                'id_bot' => $id_bot,
                'id_cliente' => $clienteId,
                'id_pesquisa' => $avulsa_id,
                'id_investigacao' => $investigacao_id,
                'bytea' => $bytea
            ];
            $screenshot = $this->pesquisa->createScreenshot($dados);

            // Verifica o resultado da operação e retorna a resposta adequada
            if ($screenshot) {
                $this->retorno(true, "Pesquisa cadastrada com sucesso.", null, 200);
            } else {
                $this->retorno(false, "Falha ao cadastrar pesquisa. #02", null, 403);
            }
        } else {
            $this->retorno(false, "Falha ao cadastrar pesquisa. #01", null, 403);
        }


    }

    public function screenshot_get($id = null)
    {
        // Verifica se o usuário está autenticado
        if (!$this->ion_auth->logged_in()) {
            $this->retorno(false, "Usuário não autenticado.", null, 401);
        }

        // Verifica se os parâmetros obrigatórios foram fornecidos
        if ($id == null) {
            $this->retorno(false, "Favor informar o ID da pesquisa.", null, 403);
        }

        // Se nenhum ID foi fornecido, busca todos os grupos
        $avulsa = $this->pesquisa->getScreenshot($id, $this->session->userdata('cliente_id'));

        if ($avulsa) {
            // Retorna o grupo com os dados adicionais
            $this->retorno(true, "Pesquisa encontrada com sucesso.", (object) $avulsa, 200);
        } else {
            // Se o grupo não foi encontrado, retorna uma resposta com status 404
            $this->retorno(true, "Nenhuma pesquisa encontrada com o ID fornecido.", null, 404);
        }
    }
}
