<?php
defined('BASEPATH') or exit('No direct script access allowed');

use chriskacerguis\RestServer\RestController;

class Bot extends RestController
{
    function __construct()
    {
        // Construct the parent class
        parent::__construct();
        $this->load->helper('license_helper'); // Carrega o helper personalizado
        $this->load->model('Bot_model', 'bot');
        $this->load->model('Investigacao_model', 'investigacao');
        $this->load->library(['session', 'ion_auth', 'encryption']);

        $this->encryption->initialize(
            array(
                'driver' => 'openssl',
                'cipher' => 'aes-256',
                'mode' => 'ctr'
            )
        );
    }

    function is_valid_date($date_string)
    {
        $format = 'Y-m-d';
        $date = DateTime::createFromFormat($format, $date_string);

        return $date && $date->format($format) === $date_string;
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

    public function bot_get()
    {
        // Verifica se o usuário está autenticado
        if (!$this->ion_auth->logged_in()) {
            $this->retorno(false, "Usuário não autenticado.", null, 401);
        }

        // Se nenhum ID foi fornecido, busca todos os grupos
        $bots = $this->bot->getBots();

        if ($bots) {

            // Retorna todos os grupos com os dados adicionais
            $this->retorno(true, "Bots encontrados com sucesso.", (object) $bots, 200);
        } else {
            // Se nenhum grupo foi encontrado, retorna uma resposta com status 404
            $this->retorno(true, "Nenhuma bot foi encontrado.", null, 404);
        }
    }

    /**
     * Sumário da função credencial_get
     * 
     * Verifica se o usuário está autenticado. Se fornecido um ID, busca e retorna os detalhes da credencial correspondente,
     * descriptografando os campos sensíveis. Se nenhum ID é fornecido, busca e retorna todas as credenciais do usuário atual,
     * descriptografando os campos sensíveis para cada credencial.
     * 
     * @param int|null $id Opcional. ID da credencial a ser buscada.
     * @return void A função não retorna um valor diretamente, mas utiliza $this->retorno para enviar respostas HTTP JSON.
     */
    public function credencial_get($id = null)
    {
        // Verifica se o usuário está autenticado
        if (!$this->ion_auth->logged_in()) {
            $this->retorno(false, "Usuário não autenticado.", null, 401);
        }

        // Se um ID foi fornecido, busca o grupo correspondente
        if ($id != null) {
            $credencial = $this->bot->getCredencial($id, $this->session->userdata('cliente_id'));

            if ($credencial) {
                // Descriptografa os campos
                $credencial->username = $this->encryption->decrypt($credencial->username);
                $credencial->password = $this->encryption->decrypt($credencial->password);
                $credencial->email = $this->encryption->decrypt($credencial->email);

                // Retorna o grupo com os dados adicionais
                $this->retorno(true, "Credencial encontrado com sucesso.", (object) $credencial, 200);
            } else {
                // Se o grupo não foi encontrado, retorna uma resposta com status 404
                $this->retorno(true, "Nenhuma credencial encontrada com o ID fornecido.", null, 404);
            }
        }

        // Se nenhum ID foi fornecido, busca todos os grupos
        $credenciais = $this->bot->getCredenciais($this->session->userdata('cliente_id'));

        if ($credenciais) {
            // Descriptografa os campos para cada credencial
            foreach ($credenciais as &$credencial) {
                $credencial->username = $this->encryption->decrypt($credencial->username);
                $credencial->password = $this->encryption->decrypt($credencial->password);
                $credencial->email = $this->encryption->decrypt($credencial->email);
            }

            // Retorna todos os grupos com os dados adicionais
            $this->retorno(true, "Credenciais encontradas com sucesso.", (object) $credenciais, 200);
        } else {
            // Se nenhum grupo foi encontrado, retorna uma resposta com status 404
            $this->retorno(true, "Nenhuma credencial foi encontrada.", null, 404);
        }
    }

    public function credencial_bot_get($id = null)
    {
        // Verifica se o usuário está autenticado
        if (!$this->ion_auth->logged_in()) {
            $this->retorno(false, "Usuário não autenticado.", null, 401);
        }

        if ($id == null) {
            $this->retorno(true, "Irmorme o ID do BOT.", null, 404);
        }

        $status = null;

        if (strtolower($this->uri->segment(4)) == "status") {
            $status = strtolower($this->uri->segment(5)) == "true" ? true : (strtolower($this->uri->segment(5)) == "false" ? false : null);
        }

        // Se um ID foi fornecido, busca o grupo correspondente
        if ($id != null) {
            $credenciais = $this->bot->getCredenciaisBot($id, $status, $this->session->userdata('cliente_id'));

            if ($credenciais) {
                // Descriptografa os campos para cada credencial
                foreach ($credenciais as &$credencial) {
                    $credencial->username = $this->encryption->decrypt($credencial->username);
                    $credencial->password = $this->encryption->decrypt($credencial->password);
                    $credencial->email = $this->encryption->decrypt($credencial->email);
                }

                // Retorna todos os grupos com os dados adicionais
                $this->retorno(true, "Credenciais encontradas com sucesso.", (object) $credenciais, 200);
            } else {
                // Se nenhum grupo foi encontrado, retorna uma resposta com status 404
                $this->retorno(true, "Nenhuma credencial foi encontrada.", null, 404);
            }
        }
    }

    /**
     * Sumário da função credencial_post
     * 
     * Verifica se o usuário está autenticado e é um administrador. Obtém dados do corpo da requisição,
     * valida e cria uma nova credencial, criptografando campos sensíveis.
     * 
     * @return void A função não retorna um valor diretamente, mas utiliza $this->retorno para enviar respostas HTTP JSON.
     */
    public function credencial_post()
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
        $id_bot = isset($json['id_bot']) ? $json['id_bot'] : null;
        $descricao = isset($json['descricao']) ? $json['descricao'] : null;
        $username = isset($json['username']) ? $json['username'] : null;
        $password = isset($json['password']) ? $json['password'] : null;
        $email = isset($json['email']) ? $json['email'] : null;

        // Verifica se os parâmetros obrigatórios foram fornecidos
        if ($clienteId == null) {
            $this->retorno(false, "Favor informar o ID do cliente.", null, 403);
        }

        if ($id_bot == null) {
            $this->retorno(false, "Favor informar o ID do bot.", null, 403);
        }

        if ($descricao == null) {
            $this->retorno(false, "Favor informar uma descrição para a credencial.", null, 403);
        }

        if ($password == null) {
            $this->retorno(false, "Favor informar uma senha para a credencial.", null, 403);
        }

        if ($username == null && $email == null) {
            $this->retorno(false, "Favor informar um e-mail ou um usuário para a credencial.", null, 403);
        }

        // Cria um array com os dados da credencial, criptografando os campos sensíveis
        $dados = [
            'id_cliente' => $clienteId,
            'id_bot' => $id_bot,
            'descricao' => $descricao,
            'username' => $this->encryption->encrypt($username),
            'password' => $this->encryption->encrypt($password),
            'email' => $this->encryption->encrypt($email)
        ];

        // Chama o método para criar a credencial no modelo
        $credencial = $this->bot->createCredencial($dados);

        // Verifica o resultado da operação e retorna a resposta adequada
        if ($credencial) {
            $this->retorno(true, "Credencial cadastrada com sucesso.", null, 200);
        } else {
            $this->retorno(false, "Falha ao cadastrar credencial.", null, 403);
        }
    }

    /**
     * Sumário da função credencial_patch
     * 
     * Verifica se o usuário está autenticado e é um administrador. Atualiza os dados de uma credencial
     * específica com base nos parâmetros fornecidos no corpo da requisição, criptografando campos sensíveis quando necessário.
     * 
     * @param int|null $id ID da credencial a ser atualizada.
     * @return void A função não retorna um valor diretamente, mas utiliza $this->retorno para enviar respostas HTTP JSON.
     */
    public function credencial_patch($id = null)
    {
        // Verifica se o usuário está autenticado
        if (!$this->ion_auth->logged_in()) {
            $this->retorno(false, "Usuário não autenticado.", null, 401);
        }

        // Verifica se o usuário é um administrador
        if (!$this->ion_auth->is_admin()) {
            $this->retorno(false, "Este usuário não é administrador.", null, 401);
        }

        // Verifica se o ID da credencial foi fornecido
        if ($id == null) {
            $this->retorno(false, "Informe o ID da credencial.", null, 401);
        }

        // Obtém dados do corpo da requisição
        $json = $this->request->body;

        // Verifica se foram fornecidos parâmetros obrigatórios
        if (!isset($json['id_bot']) && !isset($json['descricao']) && !isset($json['username']) && !isset($json['password']) && !isset($json['email']) && !isset($json['status'])) {
            $this->retorno(false, "Por favor, forneça os parâmetros obrigatórios.", null, 403);
        }

        // Criptografa os campos sensíveis, se fornecidos
        if (isset($json['username'])) {
            $json['username'] = $this->encryption->encrypt($json['username']);
        }

        if (isset($json['password'])) {
            $json['password'] = $this->encryption->encrypt($json['password']);
        }

        if (isset($json['email'])) {
            $json['email'] = $this->encryption->encrypt($json['email']);
        }

        // Verifica e valida o parâmetro 'status'
        if (isset($json['status'])) {
            if (!is_bool($json['status'])) {
                $this->retorno(false, "O parâmetro 'status' aceita apenas true ou false.", null, 403);
            }
        }

        // Adiciona informações adicionais ao array de dados
        $json['id_cliente'] = $this->session->userdata('cliente_id');
        $json['dt_alteracao'] = date('Y-m-d');

        // Chama o método para atualizar a credencial no modelo
        $credencial = $this->bot->updateCredencial($json, $id, $this->session->userdata('cliente_id'));

        // Verifica o resultado da operação e retorna a resposta adequada
        if ($credencial) {
            $this->retorno(true, "Credencial atualizada com sucesso.", null, 200);
        } else {
            $this->retorno(false, "Falha ao atualizar credencial.", null, 403);
        }
    }

    public function avulsa_get($opcao = null, $id = null)
    {
        // Verifica se o usuário está autenticado
        if (!$this->ion_auth->logged_in()) {
            $this->retorno(false, "Usuário não autenticado.", null, 401);
        }

        // Se um ID foi fornecido, busca o grupo correspondente
        if (strtolower($opcao) == 'id' && $id) {

            /*if(!$this->investigacao->userInInvestigacao($this->session->userdata('user_id'), $id, $this->session->userdata('cliente_id'))){
                $this->retorno(true, "Este usuário não tem permissão para acessar esta pesquisa.", null, 403);
            }*/

            $avulsa = $this->bot->getPesquisasAvulsasRetornoBot($id, $this->session->userdata('cliente_id'));

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
            $avulsa = $this->bot->getPesquisasAvulsasBot($id, $this->session->userdata('cliente_id'));

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
            $avulsa = $this->bot->getPesquisasAvulsasCredencial($id, $this->session->userdata('cliente_id'));

            if ($avulsa) {
                // Retorna o grupo com os dados adicionais
                $this->retorno(true, "Pesquisa encontrada com sucesso.", (object) $avulsa, 200);
            } else {
                // Se o grupo não foi encontrado, retorna uma resposta com status 404
                $this->retorno(true, "Nenhuma pesquisa encontrada com o ID fornecido.", null, 404);
            }
        }

        $avulsa = $this->bot->getPesquisasAvulsas($this->session->userdata('user_id'), $this->session->userdata('cliente_id'));

        if ($avulsa) {
            // Retorna o grupo com os dados adicionais
            $this->retorno(true, "Pesquisa encontrada com sucesso.", (object) $avulsa, 200);
        } else {
            // Se o grupo não foi encontrado, retorna uma resposta com status 404
            $this->retorno(true, "Nenhuma pesquisa encontrada.", null, 404);
        }
    }

    public function avulsa_post()
    {
        // Verifica se o usuário está autenticado
        if (!$this->ion_auth->logged_in()) {
            $this->retorno(false, "Usuário não autenticado.", null, 401);
        }

        // Obtém dados do corpo da requisição
        $json = $this->request->body;

        $user = $this->ion_auth->user()->row();

        // Extrai parâmetros obrigatórios do JSON
        $clienteId = $this->session->userdata('cliente_id');
        $id_bot = isset($json['id_bot']) ? $json['id_bot'] : null; // Obrigatorio
        $id_credencial = isset($json['id_credencial']) ? $json['id_credencial'] : null; // Obrigatorio
        $id_usuário = isset($json['id_usuário']) ? $json['id_usuário'] : $user->id;
        $descricao = isset($json['descricao']) ? $json['descricao'] : null; // Obrigatorio
        $palavra_chave = isset($json['palavra_chave']) ? $json['palavra_chave'] : null; // Obrigatorio
        $filtrar_por = isset($json['filtrar_por']) ? $json['filtrar_por'] : null;
        $investigacao_id = isset($json['investigacao_id']) ? $json['investigacao_id'] : null;

        // Verifica se os parâmetros obrigatórios foram fornecidos
        if ($clienteId == null) {
            $this->retorno(false, "Favor informar o ID do cliente.", null, 403);
        }

        if ($id_bot == null) {
            $this->retorno(false, "Favor informar o ID do bot.", null, 403);
        }

        if ($descricao == null) {
            $this->retorno(false, "Favor informar uma descrição para a pesquisa.", null, 403);
        }

        if ($id_credencial == null) {
            $this->retorno(false, "Favor informar o ID da credencial.", null, 403);
        }

        if ($palavra_chave == null) {
            $this->retorno(false, "Favor informar uma palavra-chave.", null, 403);
        }

        // Verifica se o usuário é um administrador ou pertence a uma investigação
        if (!$this->ion_auth->is_admin()) {
            if (!$this->investigacao->userInInvestigacao($this->session->userdata('user_id'), $investigacao_id, $this->session->userdata('cliente_id'))) {
                $this->retorno(false, "Este usuário não pertence a investigação selecionada.", null, 401);
            }
        }

        // Cria um array com os dados da credencial, criptografando os campos sensíveis
        $dados = [
            'id_bot' => $id_bot,
            'id_cliente' => $clienteId,
            'id_usuário' => $id_usuário,
            'id_credencial' => $id_credencial,
            'descricao' => $descricao,
            'palavra_chave' => $palavra_chave,
            'filtrar_por' => $filtrar_por,
            'investigacao_id' => $investigacao_id
        ];

        // Chama o método para criar a credencial no modelo
        $avulsa = $this->bot->createPesquisaAvulsa($dados);

        // Verifica o resultado da operação e retorna a resposta adequada
        if ($avulsa) {
            $this->retorno(true, "Pesquisa cadastrada com sucesso.", null, 200);
        } else {
            $this->retorno(false, "Falha ao cadastrar pesquisa.", null, 403);
        }
    }

    public function avulsa_patch($id = null)
    {
        // Verifica se o usuário está autenticado
        if (!$this->ion_auth->logged_in()) {
            $this->retorno(false, "Usuário não autenticado.", null, 401);
        }

        // Verifica se o usuário é um administrador
        if (!$this->ion_auth->is_admin()) {
            $this->retorno(false, "Este usuário não é administrador.", null, 401);
        }

        // Verifica se o ID da credencial foi fornecido
        if ($id == null) {
            $this->retorno(false, "Informe o ID da pesquisa.", null, 401);
        }

        // Obtém dados do corpo da requisição
        $json = $this->request->body;

        // Verifica se foram fornecidos parâmetros obrigatórios
        if (!isset($json['dt_executado']) && !isset($json['executado']) && !isset($json['agendado']) && !isset($json['erro'])) {
            $this->retorno(false, "Por favor, forneça os parâmetros obrigatórios.", null, 403);
        }

        // Verifica e valida o parâmetro 'executado'
        if (isset($json['executado'])) {
            if (!is_bool($json['executado'])) {
                $this->retorno(false, "O parâmetro 'executado' aceita apenas true ou false.", null, 403);
            }
        }

        // Verifica e valida o parâmetro 'agendado'
        if (isset($json['agendado'])) {
            if (!is_bool($json['agendado'])) {
                $this->retorno(false, "O parâmetro 'agendado' aceita apenas true ou false.", null, 403);
            }
        }

        // Verifica e valida o parâmetro 'erro'
        if (isset($json['erro'])) {
            if (!is_bool($json['erro'])) {
                $this->retorno(false, "O parâmetro 'erro' aceita apenas true ou false.", null, 403);
            }
        }

        // Chama o método para atualizar a credencial no modelo
        $credencial = $this->bot->updatePesquisaAvulsa($json, $id, $this->session->userdata('cliente_id'));

        // Verifica o resultado da operação e retorna a resposta adequada
        if ($credencial) {
            $this->retorno(true, "Pesquisa atualizada com sucesso.", null, 200);
        } else {
            $this->retorno(false, "Falha ao atualizar pesquisa.", null, 403);
        }
    }

    public function rotina_post()
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
        $id_credencial = isset($json['id_credencial']) ? $json['id_credencial'] : null; // Obrigatorio
        $descricao = isset($json['descricao']) ? $json['descricao'] : null; // Obrigatorio
        $horario = isset($json['horario']) ? $json['horario'] : null; // Obrigatorio
        $palavra_chave = isset($json['palavra_chave']) ? $json['palavra_chave'] : null; // Obrigatorio
        $filtrar_por = isset($json['filtrar_por']) ? $json['filtrar_por'] : null;
        $investigacao_id = isset($json['investigacao_id']) ? $json['investigacao_id'] : null;
        $dt_inicio = isset($json['dt_inicio']) ? $json['dt_inicio'] : null; // Obrigatorio
        $dt_fim = isset($json['dt_fim']) ? $json['dt_fim'] : null; // Obrigatorio

        // Verifica se os parâmetros obrigatórios foram fornecidos
        if ($clienteId == null) {
            $this->retorno(false, "Favor informar o ID do cliente.", null, 403);
        }

        if ($id_bot == null) {
            $this->retorno(false, "Favor informar o ID do bot.", null, 403);
        }

        if ($descricao == null) {
            $this->retorno(false, "Favor informar uma descrição para a rotina.", null, 403);
        }

        if ($horario == null) {
            $this->retorno(false, "Favor informar um descrição para a rotina.", null, 403);
        }

        if ($id_credencial == null) {
            $this->retorno(false, "Favor informar o ID da credencial.", null, 403);
        }

        if ($palavra_chave == null) {
            $this->retorno(false, "Favor informar uma palavra-chave.", null, 403);
        }

        if ($dt_inicio == null) {
            $this->retorno(false, "Favor informar uma data de inicio.", null, 403);
        }

        if ($dt_fim == null) {
            $this->retorno(false, "Favor informar uma data fim.", null, 403);
        }

        if ($investigacao_id == null) {
            $this->retorno(false, "Favor informar o ID da investigação.", null, 403);
        }

        if (!$this->is_valid_date($dt_inicio)) {
            $this->retorno(false, "Favor informar uma data de inicio valida Y-m-d.", null, 403);
        }

        if (!$this->is_valid_date($dt_fim)) {
            $this->retorno(false, "Favor informar uma data fim valida Y-m-d.", null, 403);
        }

        // Adiciona uma data fictícia para formar uma string de data/hora completa
        $data_atual = date("Y-m-d"); // Obtém a data atual no formato "Y-m-d"
        $data_hora_str = $data_atual . ' ' . $horario;

        // Cria um array com os dados da credencial, criptografando os campos sensíveis
        $dados = [
            'id_bot' => $id_bot,
            'id_cliente' => $clienteId,
            'id_credencial' => $id_credencial,
            'descricao' => $descricao,
            'horario' => $data_hora_str,
            'palavra_chave' => $palavra_chave,
            'filtrar_por' => $filtrar_por,
            'investigacao_id' => $investigacao_id,
            'dt_inicio' => $dt_inicio,
            'dt_fim' => $dt_fim
        ];

        // Chama o método para criar a credencial no modelo
        $avulsa = $this->bot->createRotina($dados);

        // Verifica o resultado da operação e retorna a resposta adequada
        if ($avulsa) {
            $this->retorno(true, "Rotina cadastrada com sucesso.", null, 200);
        } else {
            $this->retorno(false, "Falha ao cadastrar rotina.", null, 403);
        }
    }

    public function rotina_patch($id = null)
    {
        // Verifica se o usuário está autenticado
        if (!$this->ion_auth->logged_in()) {
            $this->retorno(false, "Usuário não autenticado.", null, 401);
        }

        // Verifica se o usuário é um administrador
        if (!$this->ion_auth->is_admin()) {
            $this->retorno(false, "Este usuário não é administrador.", null, 401);
        }

        // Verifica se o ID da credencial foi fornecido
        if ($id == null) {
            $this->retorno(false, "Informe o ID da da rotina.", null, 401);
        }

        // Obtém dados do corpo da requisição
        $json = $this->request->body;

        // Verifica se foram fornecidos parâmetros obrigatórios
        if (!isset($json['id_credencial']) && !isset($json['descricao']) && !isset($json['horario']) && !isset($json['filtrar_por']) && !isset($json['status']) && !isset($json['dt_fim'])) {
            $this->retorno(false, "Por favor, forneça os parâmetros obrigatórios.", null, 403);
        }

        // Verifica e valida o parâmetro 'status'
        if (isset($json['status'])) {
            if (!is_bool($json['status'])) {
                $this->retorno(false, "O parâmetro 'status' aceita apenas true ou false.", null, 403);
            }
        }

        if (isset($json['horario'])) {
            // Adiciona uma data fictícia para formar uma string de data/hora completa
            $data_atual = date("Y-m-d"); // Obtém a data atual no formato "Y-m-d"
            $data_hora_str = $data_atual . ' ' . $json['horario'];
            $json['horario'] = $data_hora_str;
        }

        $json['dt_alteracao'] = date('Y-m-d');

        // Chama o método para atualizar a credencial no modelo
        $credencial = $this->bot->updateRotina($json, $id, $this->session->userdata('cliente_id'));

        // Verifica o resultado da operação e retorna a resposta adequada
        if ($credencial) {
            $this->retorno(true, "Rotina atualizada com sucesso.", null, 200);
        } else {
            $this->retorno(false, "Falha ao atualizar rotina.", null, 403);
        }
    }

    public function rotina_get($opcao = null, $id = null)
    {
        // Verifica se o usuário está autenticado
        if (!$this->ion_auth->logged_in()) {
            $this->retorno(false, "Usuário não autenticado.", null, 401);
        }

        // Se um ID foi fornecido, busca o grupo correspondente
        if (strtolower($opcao) == 'id' && $id) {
            $avulsa = $this->bot->getRotina($id, $this->session->userdata('cliente_id'));

            if ($avulsa) {
                // Retorna o grupo com os dados adicionais
                $this->retorno(true, "Rotina encontrada com sucesso.", (object) $avulsa, 200);
            } else {
                // Se o grupo não foi encontrado, retorna uma resposta com status 404
                $this->retorno(true, "Nenhuma rotina encontrada com o ID fornecido.", null, 404);
            }
        }

        if (strtolower($opcao) == 'bot' && $id != null) {
            // Se nenhum ID foi fornecido, busca todos os grupos
            $avulsa = $this->bot->getRotinasBot($id, $this->session->userdata('cliente_id'));

            if ($avulsa) {
                // Retorna o grupo com os dados adicionais
                $this->retorno(true, "Rotina(s) encontrada(s) com sucesso.", (object) $avulsa, 200);
            } else {
                // Se o grupo não foi encontrado, retorna uma resposta com status 404
                $this->retorno(true, "Nenhuma rotina encontrada com o ID fornecido.", null, 404);
            }
        }

        if (strtolower($opcao) == 'credencial' && $id != null) {
            // Se nenhum ID foi fornecido, busca todos os grupos
            $avulsa = $this->bot->getRotinasCredencial($id, $this->session->userdata('cliente_id'));

            if ($avulsa) {
                // Retorna o grupo com os dados adicionais
                $this->retorno(true, "Rotina(s) encontrada(s) com sucesso.", (object) $avulsa, 200);
            } else {
                // Se o grupo não foi encontrado, retorna uma resposta com status 404
                $this->retorno(true, "Nenhuma rotina encontrada com o ID fornecido.", null, 404);
            }
        }

        $avulsa = $this->bot->getRotinas($this->session->userdata('cliente_id'));

        if ($avulsa) {
            // Retorna o grupo com os dados adicionais
            $this->retorno(true, "Rotina(s) encontrada(s) com sucesso.", (object) $avulsa, 200);
        } else {
            // Se o grupo não foi encontrado, retorna uma resposta com status 404
            $this->retorno(true, "Nenhuma rotina encontrada.", null, 404);
        }
    }
}
