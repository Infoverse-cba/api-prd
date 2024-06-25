<?php
defined('BASEPATH') or exit('No direct script access allowed');

use chriskacerguis\RestServer\RestController;

class Investigacao extends RestController
{

    function __construct()
    {
        // Construct the parent class
        parent::__construct();
        $this->load->helper('license_helper'); // Carrega o helper personalizado
        $this->load->model('Investigacao_model', 'investigacao');
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

    /**
     * Obtém informações sobre uma ou todas as investigações associadas ao cliente.
     *
     * Se um ID de investigação for fornecido, busca e retorna informações detalhadas sobre essa investigação.
     * Caso contrário, retorna informações sobre todas as investigações associadas ao cliente.
     *
     * @param int|null $id - O ID da investigação a ser recuperado (opcional).
     * @return void - A função envia uma resposta HTTP com os resultados ou uma mensagem de erro.
     */
    public function investigacao_get($id = null)
    {
        // Verifica se o usuário está autenticado
        if (!$this->ion_auth->logged_in()) {
            $this->retorno(false, "Usuário não autenticado.", null, 401);
        }

        // Se um ID foi fornecido, busca a investigação correspondente
        if ($id !== null) {
            $investigacao = $this->investigacao->getInvestigacao($id, $this->session->userdata('cliente_id'));

            if ($investigacao) {
                $this->retorno(true, "Executado com sucesso.", $investigacao, 200);
            } else {
                $this->retorno(true, "Nenhuma investigação foi encontrada para o ID fornecido.", null, 404);
            }
        }

        // Se nenhum ID foi fornecido, obtém todas as investigações associadas ao cliente
        $investigacoes = $this->investigacao->getInvestigacoes($this->session->userdata('cliente_id'));

        // Verifica se existem investigações e retorna uma resposta com os dados ou status 404
        if ($investigacoes) {
            $this->retorno(true, "Executado com sucesso.", $investigacoes, 200);
        } else {
            $this->retorno(true, "Nenhuma investigação foi encontrada para o cliente atual.", null, 404);
        }
    }


    /**
     * Cria uma nova investigação com base nos dados fornecidos via requisição POST.
     *
     * Verifica se o usuário está autenticado, obtém os parâmetros obrigatórios do corpo da requisição JSON,
     * verifica se os parâmetros obrigatórios foram fornecidos e chama o método do modelo para criar a investigação.
     * Retorna uma resposta com o resultado da operação.
     *
     * @return void - A função envia uma resposta HTTP com a mensagem de sucesso ou falha.
     */
    public function investigacao_post()
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
        $nome = isset($json['nome']) ? $json['nome'] : null;

        // Verifica se os parâmetros obrigatórios foram fornecidos
        if ($clienteId == null) {
            $this->retorno(false, "Favor informar o ID do cliente.", null, 403);
        }

        if ($nome == null) {
            $this->retorno(false, "Favor informar um nome para a investigação.", null, 403);
        }

        // Cria um array com os dados da investigação
        $dados = [
            'cliente_id' => $clienteId,
            'nome' => $nome,
            'descricao' => isset($json['descricao']) ? $json['descricao'] : null
        ];

        // Chama o método para criar a investigação no modelo
        $investigacao = $this->investigacao->createInvestigacao($dados);

        // Verifica o resultado da operação e retorna a resposta adequada
        if ($investigacao) {
            $this->retorno(true, "Investigação cadastrada com sucesso.", null, 200);
        } else {
            $this->retorno(false, "Falha ao cadastrar investigação.", null, 403);
        }
    }


    /**
     * Atualiza parcialmente os dados de uma investigação com base no ID fornecido.
     *
     * Verifica se o usuário está autenticado e se o ID da investigação foi fornecido.
     * Atualiza os campos específicos da investigação com os dados fornecidos no corpo da requisição.
     * Verifica a validade do parâmetro 'status' e adiciona a data de alteração.
     * Retorna uma resposta com o resultado da operação.
     *
     * @param int $id - O ID da investigação a ser atualizada.
     * @return void - A função envia uma resposta HTTP com a mensagem de sucesso ou falha.
     */
    public function investigacao_patch($id)
    {
        // Verifica se o usuário está autenticado
        if (!$this->ion_auth->logged_in()) {
            $this->retorno(false, "Usuário não autenticado.", null, 401);
        }

        // Verifica se o usuário é um administrador
        if (!$this->ion_auth->is_admin()) {
            $this->retorno(false, "Este usuário não é administrador.", null, 401);
        }

        // Verifica se foi fornecido o ID da investigação
        if ($id === null) {
            $this->retorno(false, "Informe o ID da investigação.", null, 403);
        }

        // Obtém dados do corpo da requisição
        $json = $this->request->body;
        $dados = [];

        if (!isset($json['nome']) && !isset($json['descricao']) && !isset($json['status'])) {
            $this->retorno(false, "Por favor, forneça pelo menos um parâmetro.", null, 403);
        }

        // Adiciona a data de alteração se algum campo a ser atualizado estiver presente
        $dados['dt_alteracao'] = date('Y-m-d');

        // Atualiza os dados apenas se os campos específicos estiverem presentes
        $camposAtualizar = ['nome', 'descricao', 'status'];
        foreach ($camposAtualizar as $campo) {
            if (isset($json[$campo])) {
                $dados[$campo] = $json[$campo];
            }
        }

        // Verifica se o parâmetro 'status' é válido (true ou false)
        if (isset($dados['status'])) {
            if (!is_bool($dados['status'])) {
                $this->retorno(false, "O parâmetro 'status' aceita apenas true ou false.", null, 403);
            }
        }

        // Atualiza os dados da investigação no modelo
        if (!empty($dados)) {
            if ($this->investigacao->updateInvestigacao($dados, $id, $this->session->userdata('cliente_id'))) {
                $this->retorno(true, "Os dados da investigação foram atualizados com sucesso.", null, 200);
            } else {
                $this->retorno(false, "Ocorreu um erro ao atualizar os dados da investigação.", null, 403);
            }
        } else {
            $this->retorno(false, "Por favor, forneça os parâmetros obrigatórios para a atualização.", null, 403);
        }
    }

    /**
     * Obtém informações sobre investigações associadas a coordenadores.
     *
     * Verifica se o usuário está autenticado e processa a requisição com base nos segmentos da URI.
     * Se um ID de investigação for fornecido, busca e retorna informações sobre investigações associadas a coordenadores.
     * Se 'user' for fornecido como um segmento, busca e retorna informações sobre investigações associadas a um coordenador específico.
     * Retorna uma resposta com o resultado da operação.
     *
     * @return void - A função envia uma resposta HTTP com a mensagem de sucesso ou falha.
     */
    public function coordenador_get()
    {
        // Verifica se o usuário está autenticado
        if (!$this->ion_auth->logged_in()) {
            $this->retorno(false, "Usuário não autenticado.", null, 401);
        }

        // Processa a requisição com base nos segmentos da URI
        if (is_numeric($this->uri->segment(3))) {
            // Busca investigações associadas a coordenadores com o ID fornecido
            $investigacao = $this->investigacao->getinvestigacoesCoordenadores($this->uri->segment(3), $this->session->userdata('cliente_id'));

            // Verifica se há resultados
            if ($investigacao) {
                $this->retorno(true, "Executado com sucesso.", $investigacao, 200);
            } else {
                $this->retorno(false, "Nenhuma investigação foi encontrada.", null, 404);
            }
        }

        if (strtolower($this->uri->segment(3)) == 'user') {
            // Processa a requisição para investigações associadas a um coordenador específico
            if ($this->uri->segment(4) !== null) {
                if (!is_numeric($this->uri->segment(4))) {
                    $this->retorno(false, "O ID do coordenador precisa ser um inteiro.", null, 404);
                }
            } else {
                $this->retorno(false, "Informe o ID do coordenador.", null, 404);
            }

            // Busca investigações associadas a um coordenador com o ID fornecido
            $investigacao = $this->investigacao->getinvestigacaoCoordenador($this->uri->segment(4), $this->session->userdata('cliente_id'));

            // Verifica se há resultados
            if ($investigacao) {
                $this->retorno(true, "Executado com sucesso.", $investigacao, 200);
            } else {
                $this->retorno(false, "Nenhuma investigação foi encontrada.", null, 404);
            }
        }

        $this->retorno(false, "Informe um parâmetro válido.", null, 404);
    }

    /**
     * Associa um coordenador a uma investigação com base nos dados fornecidos via requisição POST.
     *
     * Verifica se o usuário está autenticado, obtém os parâmetros obrigatórios do corpo da requisição JSON,
     * verifica se os parâmetros obrigatórios foram fornecidos e chama o método do modelo para criar a associação.
     * Retorna uma resposta com o resultado da operação.
     *
     * @return void - A função envia uma resposta HTTP com a mensagem de sucesso ou falha.
     */
    public function coordenador_post()
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
        $user_id = isset($json['user_id']) ? $json['user_id'] : null;
        $investigacao_id = isset($json['investigacao_id']) ? $json['investigacao_id'] : null;

        // Verifica se os parâmetros obrigatórios foram fornecidos
        if ($clienteId == null) {
            $this->retorno(false, "Favor informar o ID do cliente.", null, 403);
        }

        if ($user_id == null) {
            $this->retorno(false, "Favor informar um ID de usuário.", null, 403);
        }

        if ($investigacao_id == null) {
            $this->retorno(false, "Favor informar um ID de investigação.", null, 403);
        }

        // Cria um array com os dados da investigação
        $dados = [
            'cliente_id' => $clienteId,
            'user_id' => $user_id,
            'investigacao_id' => $investigacao_id
        ];

        // Chama o método para criar a investigação no modelo
        $investigacao = $this->investigacao->createInvestigacaoCoordenador($dados);

        // Verifica o resultado da operação e retorna a resposta adequada
        if ($investigacao) {
            $this->retorno(true, "Coordenador cadastrado com sucesso.", null, 200);
        } else {
            $this->retorno(false, "Falha ao cadastrar coordenador.", null, 403);
        }
    }

    /**
     * Remove a associação de um coordenador a uma investigação com base nos parâmetros da URI.
     *
     * Verifica se o usuário está autenticado e se os parâmetros na URI estão corretos.
     * Chama o método do modelo para remover a associação de um coordenador a uma investigação.
     * Retorna uma resposta com o resultado da operação.
     *
     * @return void - A função envia uma resposta HTTP com a mensagem de sucesso ou falha.
     */
    public function coordenador_delete()
    {
        // Verifica se o usuário está autenticado
        if (!$this->ion_auth->logged_in()) {
            $this->retorno(false, "Usuário não autenticado.", null, 401);
        }

        // Verifica se o usuário é um administrador
        if (!$this->ion_auth->is_admin()) {
            $this->retorno(false, "Este usuário não é administrador.", null, 401);
        }

        // Verifica se os parâmetros na URI estão corretos
        if (!is_numeric($this->uri->segment(3)) || strtolower($this->uri->segment(4)) != 'investigacao' || !is_numeric($this->uri->segment(5))) {
            $this->retorno(false, "Os parâmetros informados não estão corretos.", null, 403);
        }

        // Chama o método do modelo para remover a associação de um coordenador a uma investigação
        if ($this->investigacao->deleteInvestigacaoCoordenador($this->uri->segment(3), $this->uri->segment(5), $this->session->userdata('cliente_id'))) {
            $this->retorno(true, "Coordenador removido com sucesso.", null, 200);
        } else {
            $this->retorno(false, "Falha ao remover coordenador.", null, 403);
        }
    }

    /**
     * Obtém informações sobre investigações associadas a usuários.
     *
     * Verifica se o usuário está autenticado e processa a requisição com base nos segmentos da URI.
     * Se um ID de usuário for fornecido, busca e retorna informações sobre investigações associadas ao usuário.
     * Se 'investigacao' for fornecido como um segmento, busca e retorna informações sobre usuários associados a uma investigação específica.
     * Retorna uma resposta com o resultado da operação.
     *
     * @return void - A função envia uma resposta HTTP com a mensagem de sucesso ou falha.
     */
    public function user_get()
    {
        // Verifica se o usuário está autenticado
        if (!$this->ion_auth->logged_in()) {
            $this->retorno(false, "Usuário não autenticado.", null, 401);
        }

        // Processa a requisição com base nos segmentos da URI
        if (is_numeric($this->uri->segment(3))) {
            // Busca investigações associadas ao usuário com o ID fornecido
            $investigacao = $this->investigacao->getinvestigacaoUser($this->uri->segment(3), $this->session->userdata('cliente_id'));

            // Verifica se há resultados
            if ($investigacao) {
                $this->retorno(true, "Executado com sucesso.", $investigacao, 200);
            } else {
                $this->retorno(false, "Nenhuma investigação foi encontrada.", null, 404);
            }
        }

        if (strtolower($this->uri->segment(3)) == 'investigacao') {
            // Processa a requisição para usuários associados a uma investigação específica
            if ($this->uri->segment(4) !== null) {
                if (!is_numeric($this->uri->segment(4))) {
                    $this->retorno(false, "O ID do coordenador precisa ser um inteiro.", null, 404);
                }
            } else {
                $this->retorno(false, "Informe o ID do usuário.", null, 404);
            }

            // Busca usuários associados a uma investigação com o ID fornecido
            $investigacao = $this->investigacao->getinvestigacaoUsers($this->uri->segment(4), $this->session->userdata('cliente_id'));

            // Verifica se há resultados
            if ($investigacao) {
                $this->retorno(true, "Executado com sucesso.", $investigacao, 200);
            } else {
                $this->retorno(false, "Nenhuma investigação foi encontrada.", null, 404);
            }
        }

        $this->retorno(false, "Informe um parâmetro válido.", null, 404);
    }

    /**
     * Associa um usuário a uma investigação com base nos dados fornecidos via requisição POST.
     *
     * Verifica se o usuário está autenticado, obtém os parâmetros obrigatórios do corpo da requisição JSON,
     * verifica se os parâmetros obrigatórios foram fornecidos e chama o método do modelo para criar a associação.
     * Retorna uma resposta com o resultado da operação.
     *
     * @return void - A função envia uma resposta HTTP com a mensagem de sucesso ou falha.
     */
    public function user_post()
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
        $user_id = isset($json['user_id']) ? $json['user_id'] : null;
        $investigacao_id = isset($json['investigacao_id']) ? $json['investigacao_id'] : null;

        // Verifica se os parâmetros obrigatórios foram fornecidos
        if ($clienteId == null) {
            $this->retorno(false, "Favor informar o ID do cliente.", null, 403);
        }

        if ($user_id == null) {
            $this->retorno(false, "Favor informar um ID de usuário.", null, 403);
        }

        if ($investigacao_id == null) {
            $this->retorno(false, "Favor informar um ID de investigação.", null, 403);
        }

        // Cria um array com os dados da investigação
        $dados = [
            'cliente_id' => $clienteId,
            'user_id' => $user_id,
            'investigacao_id' => $investigacao_id
        ];

        // Chama o método para criar a investigação no modelo
        $investigacao = $this->investigacao->createInvestigacaoUser($dados);

        // Verifica o resultado da operação e retorna a resposta adequada
        if ($investigacao) {
            $this->retorno(true, "Usuário cadastrado com sucesso.", null, 200);
        } else {
            $this->retorno(false, "Falha ao cadastrar usuário.", null, 403);
        }
    }

    /**
     * Remove a associação de um usuário a uma investigação com base nos parâmetros da URI.
     *
     * Verifica se o usuário está autenticado e se os parâmetros na URI estão corretos.
     * Chama o método do modelo para remover a associação de um usuário a uma investigação.
     * Retorna uma resposta com o resultado da operação.
     *
     * @return void - A função envia uma resposta HTTP com a mensagem de sucesso ou falha.
     */
    public function user_delete()
    {
        // Verifica se o usuário está autenticado
        if (!$this->ion_auth->logged_in()) {
            $this->retorno(false, "Usuário não autenticado.", null, 401);
        }

        // Verifica se o usuário é um administrador
        if (!$this->ion_auth->is_admin()) {
            $this->retorno(false, "Este usuário não é administrador.", null, 401);
        }

        // Verifica se os parâmetros na URI estão corretos
        if (!is_numeric($this->uri->segment(3)) || strtolower($this->uri->segment(4)) != 'investigacao' || !is_numeric($this->uri->segment(5))) {
            $this->retorno(false, "Os parâmetros informados não estão corretos.", null, 403);
        }

        // Chama o método do modelo para remover a associação de um usuário a uma investigação
        if ($this->investigacao->deleteInvestigacaoUser($this->uri->segment(3), $this->uri->segment(5), $this->session->userdata('cliente_id'))) {
            $this->retorno(true, "Usuário removido com sucesso.", null, 200);
        } else {
            $this->retorno(false, "Falha ao remover Usuário.", null, 403);
        }
    }

    /**
     * Obtém todas as investigações associadas a um usuário com base no ID do usuário e ID do cliente.
     *
     * Verifica se o usuário está autenticado, chama o método do modelo para obter todas as investigações
     * associadas a um usuário e retorna uma resposta com o resultado da operação.
     *
     * @return void - A função envia uma resposta HTTP com a mensagem de sucesso, as investigações ou status 404.
     */
    public function investigacoes_user_get($status = null)
    {
        // Verifica se o usuário está autenticado
        if (!$this->ion_auth->logged_in()) {
            $this->retorno(false, "Usuário não autenticado.", null, 401);
        }

        // Verifica se o usuário é um administrador
        if (!$this->ion_auth->is_admin()) {
            $this->retorno(false, "Este usuário não é administrador.", null, 401);
        }

        if(!is_null($status)){
            if(strtolower($status) == 'active'){
                $status = true;
            }

            if(strtolower($status) == 'deactive'){
                $status = false;
            }
        }

        // Obtém todas as investigações associadas a um usuário
        $investigacoes = $this->investigacao->getAllInvestigacoesInUser($this->session->userdata('user_id'), $this->session->userdata('cliente_id'), $status);

        // Verifica se há resultados
        if ($investigacoes) {
            $this->retorno(true, "Executado com sucesso.", $investigacoes, 200);
        } else {
            $this->retorno(false, "Nenhuma investigação foi encontrada.", null, 404);
        }
    }

}
