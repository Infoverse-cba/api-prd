<?php

defined('BASEPATH') or exit('No direct script access allowed');

use chriskacerguis\RestServer\RestController;



class Cliente extends RestController
{
    function __construct()
    {
        // Construct the parent class
        parent::__construct();
        $this->load->helper('license_helper'); // Carrega o helper personalizado
        $this->load->model('Cliente_model', 'cliente');
        $this->load->library(['session', 'ion_auth']);
    }

    public function generate_key()
    {
        $license_key = generate_license_key(); // Chama a função do helper
        $this->response(["Sua chave de licença é:" => $license_key], 200);
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

    public function cliente_get($id = null)
    {
        // Verifica se o usuário está autenticado
        if (!$this->ion_auth->logged_in()) {
            $this->retorno(false, "Usuário não autenticado.", null, 401);
        }

        // Se um ID foi fornecido, busca o cliente correspondente
        if ($id != null) {
            $cliente = $this->cliente->getCliente($id);
            if ($cliente) {
                $cliente->endereco = $this->cliente->getClienteEndereco($id);
                $cliente->responsavel = $this->cliente->getClienteResponsavel($id);
                $cliente->licenca = $this->cliente->getLicenseKeyClientId($id);

                // Retorna o cliente com os dados adicionais
                $this->retorno(true, "Cliente encontrado com sucesso.", $cliente, 200);
            } else {
                // Se o cliente não foi encontrado, retorna uma resposta com status 404
                $this->retorno(true, "Cliente não encontrado em nossa base de dados.", $cliente, 404);
            }
        }

        // Se nenhum ID foi fornecido, obtém todos os clientes com dados adicionais
        $clientes = $this->cliente->getClientes();
        $numeroClientes = count($clientes);

        // Adiciona dados adicionais para cada cliente
        foreach ($clientes as &$cliente) {
            $cliente->endereco = $this->cliente->getClienteEndereco($cliente->id);
            $cliente->responsavel = $this->cliente->getClienteResponsavel($cliente->id);
            $cliente->licenca = $this->cliente->getLicenseKeyClientId($cliente->id);
        }

        // Determina a mensagem com base na quantidade de clientes encontrados
        if ($numeroClientes === 1) {
            $mensagem = "Foi encontrado " . $numeroClientes . " cliente.";
        } else {
            $mensagem = "Foram encontrados " . $numeroClientes . " clientes.";
        }

        // Verifica se existem clientes e retorna uma resposta com os dados ou status 404
        if ($clientes) {
            $this->retorno(true, $mensagem, $clientes, 200);
        } else {
            $this->retorno(true, "Não foram encontrados clientes.", null, 404);
        }
    }

    public function cliente_post()
    {

        // Verifica se o usuário está autenticado
        if (!$this->ion_auth->logged_in()) {
            $this->retorno(false, "Usuário não autenticado.", null, 401);
        }

        $json = $this->request->body;

        // Dados gerais do cliente
        $cnpj = isset($json['cnpj']) ? preg_replace("/[^0-9]/", "", $json['cnpj']) : null; // Obrigatorio
        $razao_social = isset($json['razao_social']) ? $json['razao_social'] : null; // Obrigatorio
        $nome_fantasia = isset($json['nome_fantasia']) ? $json['nome_fantasia'] : null;

        // Dados do endereço do cliente
        $cep = isset($json['cep']) ? preg_replace("/[^0-9]/", "", $json['cep']) : null; // Obrigatorio
        $referencia = isset($json['referencia']) ? $json['referencia'] : null;
        $complemento = isset($json['complemento']) ? $json['complemento'] : null;
        $numero = isset($json['numero']) ? $json['numero'] : null;
        $logradouro = isset($json['logradouro']) ? $json['logradouro'] : null; // Obrigatorio
        $bairro = isset($json['bairro']) ? $json['bairro'] : null; // Obrigatorio
        $cidade = isset($json['cidade']) ? $json['cidade'] : null; // Obrigatorio
        $estado = isset($json['estado']) ? $json['estado'] : null; // Obrigatorio

        // Dados do responsavel
        $cpf = isset($json['cpf']) ? preg_replace("/[^0-9]/", "", $json['cpf']) : null; // Obrigatorio
        $nome = isset($json['nome']) ? $json['nome'] : null; // Obrigatorio
        $email = isset($json['email']) ? $json['email'] : null; // Obrigatorio
        $telefone = isset($json['telefone']) ? preg_replace("/[^0-9]/", "", $json['telefone']) : null;
        $celular = isset($json['celular']) ? preg_replace("/[^0-9]/", "", $json['celular']) : null;
        $cargo = isset($json['cargo']) ? $json['cargo'] : null;
        $setor = isset($json['setor']) ? $json['setor'] : null; // Obrigatorio

        if ($cnpj == null) {
            $this->retorno(false, "Favor informar o CNPJ do cliente.", null, 403);
        }

        if ($razao_social == null) {
            $this->retorno(false, "Favor informar a razão social do cliente.", null, 403);
        }

        if ($cep == null) {
            $this->retorno(false, "Favor informar o CEP do cliente.", null, 403);
        }

        if ($logradouro == null) {
            $this->retorno(false, "Favor informar o logradouro do cliente.", null, 403);
        }

        if ($bairro == null) {
            $this->retorno(false, "Favor informar o bairro do cliente.", null, 403);
        }

        if ($cidade == null) {

            $this->retorno(false, "Favor informar a cidade do cliente.", null, 403);
        }

        if ($estado == null) {
            $this->retorno(false, "Favor informar o estado do cliente.", null, 403);
        }

        if ($cpf == null) {
            $this->retorno(false, "Favor informar o CPF do responsavel.", null, 403);
        }

        if ($nome == null) {
            $this->retorno(false, "Favor informar o nome do responsavel.", null, 403);
        }

        if ($email == null) {
            $this->retorno(false, "Favor informar o e-mail do responsavel.", null, 403);
        }

        if ($setor == null) {
            $this->retorno(false, "Favor informar o setor do responsavel.", null, 403);
        }

        if ($telefone == null && $celular == null) {
            $this->retorno(false, "Favor informar pelo menos um telefone ou celular do responsavel.", null, 403);
        }

        if ($this->cliente->getClienteCnpj($cnpj)) {
            $this->retorno(false, "Clienta já existe em nossa base de dados.", null, 403);
        }

        $dados_gerais = [
            'cnpj' => $cnpj,
            'razao_social' => $razao_social,
            'nome_fantasia' => $nome_fantasia
        ];

        $cliente_id = $this->cliente->createCliente($dados_gerais);

        if ($cliente_id) {
            $dados_endereco = [
                'id_cliente' => $cliente_id,
                'cep' => $cep,
                'referencia' => $referencia,
                'complemento' => $complemento,
                'numero' => $numero,
                'logradouro' => $logradouro,
                'bairro' => $bairro,
                'cidade' => $cidade,
                'estado' => $estado
            ];

            $enderecoCliente_id = $this->cliente->createClienteEndereco($dados_endereco);

            if ($enderecoCliente_id) {
                $dados_responsavel = [
                    'id_cliente' => $cliente_id,
                    'cpf' => $cpf,
                    'nome' => $nome,
                    'email' => $email,
                    'telefone' => $telefone,
                    'celular' => $celular,
                    'cargo' => $cargo,
                    'setor' => $setor,
                ];

                $responsavelCliente_id = $this->cliente->createClienteResponsavel($dados_responsavel);

                if ($responsavelCliente_id) {
                    $this->retorno(true, "Cliente cadastrado com sucesso.", null, 200);
                } else {
                    $this->retorno(false, "Falha ao cadastrar o responsavel.", null, 403);
                }
            } else {
                $this->retorno(false, "Falha ao cadastrar o endereço.", null, 403);
            }
        } else {
            $this->retorno(false, "Falha ao cadastrar cliente.", null, 403);
        }
    }

    public function cliente_patch($id = null)
    {
        // Verifica se o usuário está autenticado
        if (!$this->ion_auth->logged_in()) {
            $this->retorno(false, "Usuário não autenticado.", null, 401);
        }

        if ($id == null) {
            $this->retorno(false, "Informe o ID do cliente.", null, 403);
        }

        $json = $this->request->body;

        if (!isset($json['cnpj']) && !isset($json['razao_social']) && !isset($json['nome_fantasia']) && !isset($json['status'])) {
            $this->retorno(false, "Por favor, forneça os parâmetros obrigatórios.", null, 403);
        }

        if (isset($json['status'])) {
            if (!is_bool($json['status'])) {
                $this->retorno(false, "O parâmetro 'status' aceita apenas true ou false.", null, 403);
            }
        }

        $json['dt_alteracao'] = date('Y-m-d');

        if ($this->cliente->updateCliente($json, $id)) {
            $this->retorno(true, "Os dados do cliente foram atualizados com sucesso.", null, 200);
        } else {
            $this->retorno(false, "Ocorreu um erro ao atualizar os dados do cliente.", null, 403);
        }
    }

    public function endereco_patch($id = null)
    {
        // Verifica se o usuário está autenticado
        if (!$this->ion_auth->logged_in()) {
            $this->retorno(false, "Usuário não autenticado.", null, 401);
        }

        if ($id == null) {
            $this->retorno(false, "Informe o ID do cliente.", null, 403);
        }

        $json = $this->request->body;

        if (!isset($json['cep']) && !isset($json['referencia']) && !isset($json['complemento']) && !isset($json['numero']) && !isset($json['logradouro']) && !isset($json['bairro']) && !isset($json['cidade']) && !isset($json['estado'])) {
            $this->retorno(false, "Por favor, forneça os parâmetros obrigatórios.", null, 403);
        }

        $json['dt_alteracao'] = date('Y-m-d');

        if ($this->cliente->updateClienteEndereco($json, $id)) {
            $this->retorno(true, "O endereço do cliente foi atualizado com sucesso.", null, 200);
        } else {
            $this->retorno(false, "Ocorreu um erro ao atualizar o endereço do cliente.", null, 403);
        }
    }

    public function responsavel_patch($id = null)
    {
        // Verifica se o usuário está autenticado
        if (!$this->ion_auth->logged_in()) {
            $this->retorno(false, "Usuário não autenticado.", null, 401);
        }

        if ($id == null) {
            $this->retorno(false, "Informe o ID do cliente.", null, 403);
        }

        $json = $this->request->body;

        if (!isset($json['cpf']) && !isset($json['nome']) && !isset($json['email']) && !isset($json['telefone']) && !isset($json['celular']) && !isset($json['cargo']) && !isset($json['setor'])) {
            $this->retorno(false, "Por favor, forneça os parâmetros obrigatórios.", null, 403);
        }

        $json['dt_alteracao'] = date('Y-m-d');

        if ($this->cliente->updateClienteResponsavel($json, $id)) {
            $this->retorno(true, "Os dados do responsavel foram atualizado com sucesso.", null, 200);
        } else {
            $this->retorno(false, "Ocorreu um erro ao atualizar os dados do responsavel.", null, 403);
        }
    }

    public function licenca_post($id = null)
    {
        // Verifica se o usuário está autenticado
        if (!$this->ion_auth->logged_in()) {
            $this->retorno(false, "Usuário não autenticado.", null, 401);
        }

        if ($id == null) {
            $this->retorno(false, "Informe o ID do cliente.", null, 403);
        }

        if ($this->cliente->getLicenseKeyClientId($id)) {
            $this->retorno(false, "O clienta já possui uma licença.", null, 403);
        }

        $json = $this->request->body;

        if (!isset($json['qtd_usuario']) && !isset($json['dt_validade'])) {
            $this->retorno(false, "Por favor, forneça os parâmetros obrigatórios.", null, 403);
        }

        while (true) {
            $license_key = generate_license_key(); // Chama a função do helper

            if (!$this->cliente->getLicenseKey($license_key)) {
                $dados = [
                    'id_cliente' => $id,
                    'hash' => $license_key,
                    'qtd_usuario' => $json['qtd_usuario'],
                    'dt_validade' => $json['dt_validade']
                ];

                if ($this->cliente->createLicenseKey($dados)) {
                    $this->retorno(true, "A licença foi criada com sucesso.", null, 200);
                    break;
                } else {
                    $this->retorno(false, "Ocorreu um erro ao criar uma licença.", null, 403);
                    break;
                }
            }
        }
    }

    public function licenca_patch($id = null)
    {
        // Verifica se o usuário está autenticado
        if (!$this->ion_auth->logged_in()) {
            $this->retorno(false, "Usuário não autenticado.", null, 401);
        }

        if ($id == null) {
            $this->retorno(false, "Informe o ID do cliente.", null, 403);
        }

        $json = $this->request->body;

        if (!isset($json['qtd_usuario']) && !isset($json['dt_validade'])) {
            $this->retorno(false, "Por favor, forneça os parâmetros obrigatórios.", null, 403);
        }

        $json['dt_alteracao'] = date('Y-m-d');

        if ($this->cliente->updateLicenseKeyClienteId($json, $id)) {
            $this->retorno(true, "A licença foi atualizada com sucesso.", null, 200);
        } else {
            $this->retorno(false, "Ocorreu um erro ao atualizar a licença.", null, 403);
        }
    }
}
