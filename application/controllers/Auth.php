<?php
defined('BASEPATH') or exit('No direct script access allowed');

use chriskacerguis\RestServer\RestController;

class Auth extends RestController
{
    function __construct()
    {
        // Construct the parent class
        parent::__construct();

        parent::__construct();
        $this->load->database();
        $this->load->library(['ion_auth', 'form_validation', 'email']);
        $this->load->helper(['url', 'language']);
        $this->load->model('Usuario_model', 'usuario');
        $this->load->model('Cliente_model', 'cliente');

        $this->form_validation->set_error_delimiters($this->config->item('error_start_delimiter', 'ion_auth'), $this->config->item('error_end_delimiter', 'ion_auth'));

        $this->lang->load('auth');
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
     * Processa a autenticação do usuário com base nas credenciais fornecidas.
     *
     * Verifica os parâmetros de login, autentica o usuário usando o Ion Auth, verifica a licença do cliente
     * e retorna uma resposta com o status de autenticação, detalhes do usuário e grupos associados.
     * 
     * @return void - A função envia uma resposta HTTP com o resultado da autenticação, informações do usuário ou mensagens de erro.
     */
    public function login_post()
    {
        // Obtém dados do corpo da requisição
        $json = $this->request->body;
        $identity = isset($json['username']) ? $json['username'] : null;
        $password = isset($json['password']) ? $json['password'] : null;
        $remember = false;

        // Verifica se os parâmetros obrigatórios foram fornecidos
        if (!isset($json['username']) && !isset($json['password'])) {
            $this->retorno(false, "Por favor, forneça os parâmetros obrigatórios.", null, 403);
        }

        // Verifica e define o parâmetro 'remember' se presente e válido
        if (isset($json['remember']) && !is_bool($json['remember'])) {
            $remember = $json['remember'];
        }

        // Verifica se o parâmetro 'remember' é válido (true ou false)
        if (!is_bool($remember)) {
            $this->retorno(false, "O parâmetro 'remember' aceita apenas true ou false.", null, 403);
        }

        // Verifica se o usuário já está autenticado
        if ($this->ion_auth->logged_in()) {
            // Verifica a validade da licença do cliente
            if (!$this->cliente->licencaValida($this->session->userdata('cliente_id'))) {
                $this->ion_auth->logout();
                $this->retorno(false, "Este cliente não possui uma licença válida.", null, 403);
            }

            // Obtém informações do usuário e grupos
            $user = $this->ion_auth->user()->row();
            $groups = $this->ion_auth->get_users_groups($user->user_id)->result_array();

            // Verifica se o usuário está ativo
            if ((bool) $user->active) {
                // Retorna uma resposta com informações do usuário e grupos
                $this->response([
                    'status' => true,
                    'logged_in' => true,
                    'is_admin' => $this->ion_auth->is_admin(),
                    'message' => 'Usuário autenticado com sucesso.',
                    'user' => [
                        'id' => $user->user_id,
                        'cliente_id' => $this->session->userdata('cliente_id'),
                        'ip_address' => $user->ip_address,
                        'username' => $user->username,
                        'email' => $user->email,
                        'remember_selector' => $user->remember_selector,
                        'remember_code' => $user->remember_code,
                        'created_on' => $user->created_on,
                        'last_login' => $user->last_login,
                        'active' => (bool) $user->active,
                        'is_admin' => $this->ion_auth->is_admin(),
                        'first_name' => $user->first_name,
                        'last_name' => $user->last_name,
                        'company' => $user->company,
                        'phone' => $user->phone
                    ],
                    'groups' => $groups
                ], 200);
            } else {
                $this->ion_auth->logout();
                $this->retorno(false, "Usuário desativado.", null, 401);
            }
        }

        // Tenta realizar o login usando as credenciais fornecidas
        if ($this->ion_auth->login($identity, $password, $remember)) {
            // Verifica a validade da licença do cliente
            if (!$this->cliente->licencaValida($this->session->userdata('cliente_id'))) {
                $this->ion_auth->logout();
                $this->retorno(false, "Este cliente não possui uma licença válida.", null, 403);
            }

            // Obtém informações do usuário e grupos
            $user = $this->ion_auth->user()->row();
            $groups = $this->ion_auth->get_users_groups($user->user_id)->result_array();

            // Verifica se o usuário está ativo
            if ((bool) $user->active) {
                // Retorna uma resposta com informações do usuário e grupos
                $this->response([
                    'status' => true,
                    'logged_in' => true,
                    'is_admin' => $this->ion_auth->is_admin(),
                    'message' => 'Usuário autenticado com sucesso.',
                    'user' => [
                        'id' => $user->user_id,
                        'cliente_id' => $this->session->userdata('cliente_id'),
                        'ip_address' => $user->ip_address,
                        'username' => $user->username,
                        'email' => $user->email,
                        'remember_selector' => $user->remember_selector,
                        'remember_code' => $user->remember_code,
                        'created_on' => $user->created_on,
                        'last_login' => $user->last_login,
                        'active' => (bool) $user->active,
                        'is_admin' => $this->ion_auth->is_admin(),
                        'first_name' => $user->first_name,
                        'last_name' => $user->last_name,
                        'company' => $user->company,
                        'phone' => $user->phone
                    ],
                    'groups' => $groups
                ], 200);
            } else {
                $this->ion_auth->logout();
                $this->retorno(false, "Usuário desativado.", null, 401);
            }
        } else {
            // Se o login falhar, retorna uma mensagem de erro
            $this->retorno(false, "Não foi possível realizar o login.", null, 401);
        }
    }

    /**
     * Realiza o logout do usuário atual.
     *
     * Desconecta o usuário, encerrando a sessão e retornando uma mensagem de sucesso ou falha.
     * 
     * @return void - A função envia uma resposta HTTP indicando o sucesso ou falha do logout.
     */
    public function logout_get()
    {
        // Realiza o logout do usuário
        if ($this->ion_auth->logout()) {
            //$this->retorno(false, "Logout realizado com sucesso.", null, 200);
            $this->response([
                'status' => true, // Define o status da resposta
                'logged_in' => false, // Verifica se o usuário está autenticado
                'is_admin' => $this->ion_auth->is_admin() ? true : false,
                'message' => 'Logout realizado com sucesso.'
            ], 200);
        } else {
            $this->retorno(false, "Falha ao realizar o logout.", null, 403);
        }
    }

    /**
     * Altera a senha do usuário autenticado.
     *
     * Recebe e valida os parâmetros necessários para alterar a senha do usuário, realiza as verificações
     * de segurança e, se bem-sucedida, efetua a mudança de senha, encerrando a sessão do usuário.
     * 
     * @return void - A função envia uma resposta HTTP indicando o sucesso ou falha da alteração de senha.
     */
    public function password_patch()
    {
        // Verifica se o usuário está autenticado
        if (!$this->ion_auth->logged_in()) {
            $this->retorno(false, "Usuário não autenticado.", null, 401);
        }

        // Obtém e valida os parâmetros da requisição
        $json = $this->request->body;
        $id = isset($json['userId']) ? $json['userId'] : false;
        $oldPassword = isset($json['oldPassword']) ? $json['oldPassword'] : false;
        $newPassword = isset($json['newPassword']) ? $json['newPassword'] : false;
        $confirmNewPassword = isset($json['confirmNewPassword']) ? $json['confirmNewPassword'] : false;
        $matcheNewPassword = ($newPassword == $confirmNewPassword) ? true : false;

        // Validação dos parâmetros
        if (!$id) {
            $this->retorno(false, "É preciso informar o id do usuário que será desativado.", null, 400);
        }

        if (!$this->usuario->userInClient($id, $this->session->userdata('cliente_id'))) {
            $this->retorno(false, "Usuário não pertence ao cliente.", null, 401);
        }

        if (!$oldPassword || !$newPassword || !$confirmNewPassword || !$matcheNewPassword) {
            $this->retorno(false, "Parâmetros inválidos ou ausentes.", null, 400);
        }

        // Obtém informações do usuário
        $user = $this->usuario->getUser($id, $this->session->userdata('cliente_id'));
        $identity = $this->session->userdata('identity');

        // Tenta alterar a senha
        $change = $this->ion_auth->change_password($user->email, $oldPassword, $newPassword);

        // Verifica o resultado da operação e envia a resposta adequada
        if ($change) {
            $this->session->set_flashdata('message', $this->ion_auth->messages());
            $this->ion_auth->logout();
            $this->retorno(true, "Senha alterada com sucesso.", null, 200);
        } else {
            $this->session->set_flashdata('message', $this->ion_auth->errors());
            $this->retorno(false, "Não foi possível alterar a senha.", null, 400);
        }
    }

    /**
     * Ativa um usuário específico.
     *
     * Verifica se o usuário autenticado é um administrador, valida os parâmetros da requisição
     * e ativa o usuário correspondente. Retorna uma resposta indicando o sucesso ou falha da ativação.
     * 
     * @return void - A função envia uma resposta HTTP indicando o sucesso ou falha da ativação do usuário.
     */
    public function activate_patch()
    {
        // Obtém e valida os parâmetros da requisição
        $json = $this->request->body;
        $id = isset($json['userId']) ? $json['userId'] : false;

        // Verifica se o usuário está autenticado
        if (!$this->ion_auth->logged_in()) {
            $this->retorno(false, "Usuário não autenticado.", null, 401);
        }

        // Verifica se o usuário autenticado é um administrador
        if (!$this->ion_auth->is_admin()) {
            $this->retorno(false, "Este usuário não é administrador.", null, 401);
        }

        // Validação dos parâmetros
        if (!$id) {
            $this->retorno(false, "É preciso informar o id do usuário que será ativado.", null, 400);
        }

        // Verifica se o usuário pertence ao cliente autenticado
        if (!$this->usuario->userInClient($id, $this->session->userdata('cliente_id'))) {
            $this->retorno(false, "Usuário não pertence ao cliente.", null, 401);
        }

        // Ativa o usuário e envia a resposta adequada
        if ($this->ion_auth->activate($id)) {
            $this->retorno(true, "Usuário ativado com sucesso.", null, 200);
        } else {
            $this->retorno(false, "Houve uma falha ao ativar o usuário.", null, 400);
        }
    }

    /**
     * Desativa um usuário específico.
     *
     * Verifica se o usuário autenticado é um administrador, valida os parâmetros da requisição
     * e desativa o usuário correspondente. Retorna uma resposta indicando o sucesso ou falha da desativação.
     * 
     * @return void - A função envia uma resposta HTTP indicando o sucesso ou falha da desativação do usuário.
     */
    public function deactivate_patch()
    {
        // Obtém e valida os parâmetros da requisição
        $json = $this->request->body;
        $id = isset($json['userId']) ? $json['userId'] : false;

        // Verifica se o usuário está autenticado
        if (!$this->ion_auth->logged_in()) {
            $this->retorno(false, "Usuário não autenticado.", null, 401);
        }

        // Verifica se o usuário autenticado é um administrador
        if (!$this->ion_auth->is_admin()) {
            $this->retorno(false, "Este usuário não é administrador.", null, 401);
        }

        // Validação dos parâmetros
        if (!$id) {
            $this->retorno(false, "É preciso informar o id do usuário que será desativado.", null, 401);
        }

        // Verifica se o usuário pertence ao cliente autenticado
        if (!$this->usuario->userInClient($id, $this->session->userdata('cliente_id'))) {
            $this->retorno(false, "Usuário não pertence ao cliente.", null, 401);
        }

        // Desativa o usuário e envia a resposta adequada
        if ($this->ion_auth->deactivate($id)) {
            $this->retorno(true, "Usuário desativado com sucesso.", null, 200);
        } else {
            $this->retorno(false, "Houve uma falha ao desativar o usuário.", null, 400);
        }
    }

    /**
     * Obtém uma lista de usuários com base nos parâmetros fornecidos.
     *
     * Verifica se o usuário autenticado é um administrador, e recupera uma lista de usuários
     * de acordo com os parâmetros da requisição. Retorna uma resposta HTTP contendo a lista de usuários
     * ou uma mensagem de erro em caso de falha.
     * 
     * @return void - A função envia uma resposta HTTP contendo a lista de usuários ou uma mensagem de erro.
     */
    public function users_get()
    {
        // Verifica se o usuário está autenticado
        if (!$this->ion_auth->logged_in()) {
            $this->retorno(false, "Usuário não autenticado.", null, 401);
        }

        // Verifica se o usuário autenticado é um administrador
        if (!$this->ion_auth->is_admin()) {
            $this->retorno(false, "Este usuário não é administrador.", null, 401);
        }

        // Inicializa a variável $data['users'] como nula
        $data['users'] = null;

        // Verifica os parâmetros da requisição e recupera a lista de usuários correspondente
        if (strtolower($this->uri->segment(3)) == 'active') {
            $data['users'] = $this->usuario->getUsersActive($this->session->userdata('cliente_id'));
        } elseif (strtolower($this->uri->segment(3)) == 'deactive') {
            $data['users'] = $this->usuario->getUsersDeactive($this->session->userdata('cliente_id'));
        } else {
            $data['users'] = $this->usuario->getUsers($this->session->userdata('cliente_id'));
        }

        // Verifica se a lista de usuários foi recuperada com sucesso
        if ($data['users']) {
            // Itera sobre os usuários, realiza ajustes necessários e adiciona informações adicionais
            foreach ($data['users'] as $k => $user) {
                $data['users'][$k]->active = (bool) $user->active;
                $data['users'][$k]->groups = $this->ion_auth->get_users_groups($user->id)->result();
            }

            // Envia a resposta HTTP contendo a lista de usuários e uma mensagem de sucesso
            $this->retorno(true, "Usuários recuperados com sucesso.", $data['users'], 200);
        } else {
            // Envia uma mensagem de erro em caso de falha ao listar os usuários
            $this->retorno(false, "Ocorreu um erro ao listar os usuários.", null, 403);
        }
    }

    /**
     * Obtém informações detalhadas de um usuário com base no ID fornecido.
     *
     * Verifica se o usuário autenticado está logado, se um ID de usuário foi fornecido
     * e se o usuário com o ID especificado pertence ao cliente. Recupera informações detalhadas
     * do usuário e suas associações de grupos. Retorna uma resposta HTTP contendo as informações
     * do usuário ou uma mensagem de erro em caso de falha.
     * 
     * @param int $id - O ID do usuário a ser recuperado.
     * @return void - A função envia uma resposta HTTP contendo as informações do usuário ou uma mensagem de erro.
     */
    public function user_get($id = false)
    {
        // Verifica se o usuário está autenticado
        if (!$this->ion_auth->logged_in()) {
            $this->retorno(false, "Usuário não autenticado.", null, 401);
        }

        // Verifica se foi fornecido um ID de usuário
        if (!$id) {
            $this->retorno(false, "É preciso informar o ID do usuário.", null, 401);
        }

        // Verifica se o usuário com o ID especificado pertence ao cliente
        if (!$this->usuario->userInClient($id, $this->session->userdata('cliente_id'))) {
            $this->retorno(false, "Usuário não pertence ao cliente.", null, 401);
        }

        // Recupera informações detalhadas do usuário
        $data['user'] = $this->usuario->getUser($id, $this->session->userdata('cliente_id'));

        // Verifica se as informações do usuário foram recuperadas com sucesso
        if ($data['user']) {
            // Realiza ajustes necessários nas informações do usuário
            $data['user']->active = (bool) $data['user']->active;
            $data['user']->groups = $this->ion_auth->get_users_groups($data['user']->id)->result();

            // Envia a resposta HTTP contendo as informações do usuário e uma mensagem de sucesso
            $this->retorno(true, "Usuário recuperado com sucesso.", $data['user'], 200);
        } else {
            // Envia uma mensagem de erro em caso de falha ao recuperar as informações do usuário
            $this->retorno(false, "Ocorreu um erro ao recuperar usuário.", null, 403);
        }
    }

    /**
     * Cria um novo usuário com base nos dados fornecidos no corpo da requisição.
     *
     * Verifica se o usuário autenticado é um administrador, se a quantidade de usuários restantes
     * na licença do cliente é suficiente, e se os parâmetros obrigatórios estão presentes no JSON.
     * Em seguida, registra o novo usuário usando a biblioteca Ion Auth. Retorna uma resposta HTTP
     * indicando o sucesso ou a falha da operação.
     * 
     * @return void - A função envia uma resposta HTTP indicando o resultado da criação do usuário.
     */
    public function user_post()
    {
        // Obtém os dados do corpo da requisição
        $json = $this->request->body;

        // Verifica se o usuário está autenticado
        if (!$this->ion_auth->logged_in()) {
            $this->retorno(false, "Usuário não autenticado.", null, 401);
        }

        // Verifica se o usuário autenticado é um administrador
        if (!$this->ion_auth->is_admin()) {
            $this->retorno(false, "Este usuário não é administrador.", null, 401);
        }

        // Obtém a quantidade de usuários restantes na licença do cliente
        $qtdUsuariosRestantes = $this->cliente->getQtdUsuariosRestantes($this->session->userdata('cliente_id'));

        // Verifica se a quantidade de usuários restantes é suficiente
        if (is_bool($qtdUsuariosRestantes) && !$qtdUsuariosRestantes) {
            $this->ion_auth->logout();
            $this->retorno(false, "Este cliente não possui uma licença válida.", null, 403);
        }

        // Verifica se o cliente atingiu o limite de usuários
        if (is_numeric($qtdUsuariosRestantes) && $qtdUsuariosRestantes == 0) {
            $this->retorno(false, "Este cliente atingiu o limite de usuários.", null, 403);
        }

        // Verifica se os parâmetros obrigatórios estão presentes no JSON
        if (
            !isset($json['firstName']) || !isset($json['lastName']) || !isset($json['email']) ||
            !isset($json['phone']) || !isset($json['password']) || !isset($json['passwordConfirm'])
        ) {
            $this->retorno(false, "Parâmetros obrigatórios ausentes no JSON.", null, 401);
        }

        // Verifica se a senha e a confirmação de senha são iguais
        if ($json['passwordConfirm'] !== $json['password']) {
            $this->retorno(false, "A senha e a confirmação de senha devem ser iguais.", null, 401);
        }

        // Obtém o tipo de identificação (email ou outro)
        $identity_column = $this->config->item('identity', 'ion_auth');

        $email = isset($json['email']) ? strtolower($json['email']) : '';
        $identity = ($identity_column === 'email') ? $email : isset($json['identity']);
        $password = isset($json['password']) ? $json['password'] : '';

        // Configura informações adicionais do usuário
        $additional_data = [
            'first_name' => isset($json['firstName']) ? $json['firstName'] : '',
            'last_name' => isset($json['lastName']) ? $json['lastName'] : '',
            'company' => isset($json['company']) ? $json['company'] : '',
            'phone' => isset($json['phone']) ? $json['phone'] : '',
        ];

        // Registra o novo usuário usando a biblioteca Ion Auth
        if ($this->ion_auth->register($identity, $password, $email, $additional_data)) {
            $this->retorno(true, "Usuário criado com sucesso.", null, 200);
        } else {
            $this->retorno(false, "Houve uma falha ao criar o usuário.", null, 403);
        }
    }

    /**
     * Atualiza os dados de um usuário com base no ID fornecido e nos parâmetros do corpo da requisição.
     *
     * Verifica se o usuário autenticado é um administrador e se o ID do usuário e pelo menos um parâmetro
     * válido foram fornecidos. Realiza as devidas conversões e ajustes nos parâmetros do corpo da requisição,
     * como alterar 'firstName' para 'first_name' e 'status' para 'active'. Em seguida, chama o método para
     * atualizar os dados do usuário no modelo. Retorna uma resposta HTTP indicando o sucesso ou a falha da operação.
     * 
     * @param int|null $id - O ID do usuário a ser atualizado.
     * 
     * @return void - A função envia uma resposta HTTP indicando o resultado da atualização dos dados do usuário.
     */
    public function user_patch($id = null)
    {
        // Verifica se o usuário está autenticado
        if (!$this->ion_auth->logged_in()) {
            $this->retorno(false, "Usuário não autenticado.", null, 401);
        }

        // Verifica se o usuário autenticado é um administrador
        if (!$this->ion_auth->is_admin()) {
            $this->retorno(false, "Este usuário não é administrador.", null, 401);
        }

        // Verifica se o ID do usuário foi fornecido
        if ($id == null) {
            $this->retorno(false, "Informe o ID do cliente.", null, 403);
        }

        // Verifica se o usuário pertence ao cliente
        if (!$this->usuario->userInClient($id, $this->session->userdata('cliente_id'))) {
            $this->retorno(false, "Usuário não pertence ao cliente.", null, 401);
        }

        // Obtém os dados do corpo da requisição
        $json = $this->request->body;

        // Verifica se pelo menos um parâmetro válido foi fornecido
        if (
            !isset($json['firstName']) && !isset($json['lastName']) && !isset($json['email']) &&
            !isset($json['phone']) && !isset($json['status'])
        ) {
            $this->retorno(false, "Por favor, forneça pelo menos um parâmetro.", null, 403);
        }

        // Realiza conversões e ajustes nos parâmetros do corpo da requisição
        if (isset($json['firstName'])) {
            $json['first_name'] = $json['firstName'];
            unset($json['firstName']);
        }

        if (isset($json['lastName'])) {
            $json['last_name'] = $json['lastName'];
            unset($json['lastName']);
        }

        if (isset($json['status'])) {
            // Verifica se o parâmetro 'status' é booleano
            if (!is_bool($json['status'])) {
                $this->retorno(false, "O parâmetro 'status' aceita apenas true ou false.", null, 403);
            }

            $json['active'] = (int) $json['status'];
            unset($json['status']);
        }

        // Chama o método para atualizar os dados do usuário no modelo
        if ($this->usuario->updateUser($json, $id)) {
            $this->retorno(true, "Os dados do usuário foram atualizados com sucesso.", null, 200);
        } else {
            $this->retorno(false, "Ocorreu um erro ao atualizar os dados do usuário.", null, 403);
        }
    }

    /**
     * Inicia o processo de recuperação de senha para o usuário com base no e-mail fornecido.
     *
     * Verifica se o e-mail do usuário foi fornecido. Em seguida, utiliza o Identity (por exemplo, e-mail)
     * fornecido para localizar o usuário. Se o usuário for encontrado, o método `forgotten_password`
     * do Ion Auth é chamado para enviar um código de ativação por e-mail. Retorna uma resposta HTTP indicando
     * o sucesso ou a falha do processo de recuperação de senha.
     * 
     * @return void - A função envia uma resposta HTTP indicando o resultado da recuperação de senha.
     */
    public function recuperar_senha_post()
    {
        // Obtém os dados do corpo da requisição
        $json = $this->request->body;

        // Verifica se o e-mail do usuário foi fornecido
        if (!isset($json['email'])) {
            $this->retorno(false, "Informe o e-mail do usuário.", null, 403);
        }

        // Obtém o nome da coluna de identidade (por exemplo, e-mail)
        $identity_column = $this->config->item('identity', 'ion_auth');

        // Localiza o usuário com base no e-mail fornecido
        $identity = $this->ion_auth->where($identity_column, $json['email'])->users()->row();

        // Verifica se o usuário foi encontrado
        if (empty($identity)) {
            $this->retorno(false, "Usuário não encontrado.", null, 403);
        }

        // Inicia o processo de recuperação de senha
        $forgotten = $this->ion_auth->forgotten_password($identity->{$identity_column});

        // Verifica se não houve erros durante o processo
        if ($forgotten) {
            // Se não houver erros, envia uma resposta de sucesso
            $this->retorno(false, "E-mail de recuperação de senha enviado com sucesso.", null, 200);
        } else {
            // Se houver erros, envia uma resposta de falha
            $this->retorno(false, "Ocorreu um erro ao enviar e-mail de recuperação de senha.", null, 403);
        }
    }

    /**
     * Completa o processo de recuperação de senha após o usuário receber um código de resete.
     *
     * Verifica se o código de resete e as senhas fornecidas estão presentes no corpo da requisição.
     * Confirma se as senhas fornecidas são iguais. Em seguida, verifica se o código de resete é válido
     * usando o método `forgotten_password_check` do Ion Auth. Se o código for válido, limpa o código
     * antigo de resete, redefine a senha do usuário e retorna uma resposta HTTP indicando o sucesso ou a falha
     * do processo.
     * 
     * @return void - A função envia uma resposta HTTP indicando o resultado da recuperação de senha.
     */
    public function recuperar_senha_patch()
    {
        // Obtém os dados do corpo da requisição
        $json = $this->request->body;

        // Verifica se o código de resete está presente
        if (!isset($json['code'])) {
            $this->retorno(false, "Informe o código de resete de senha.", null, 403);
        }

        // Verifica se a senha está presente
        if (!isset($json['password'])) {
            $this->retorno(false, "Informe uma senha.", null, 403);
        }

        // Verifica se a confirmação de senha está presente
        if (!isset($json['confirmPassword'])) {
            $this->retorno(false, "Confirme a senha.", null, 403);
        }

        // Verifica se as senhas fornecidas são iguais
        if ($json['password'] != $json['confirmPassword']) {
            $this->retorno(false, "As senhas precisam ser iguais.", null, 403);
        }

        // Verifica se o código de resete é válido
        $user = $this->ion_auth->forgotten_password_check($json['code']);

        // Se o código for válido, continua com a redefinição da senha
        if ($user) {
            $identity = $user->{$this->config->item('identity', 'ion_auth')};

            // Limpa o código antigo de resete
            $this->ion_auth->clear_forgotten_password_code($identity);

            // Redefine a senha do usuário
            $change = $this->ion_auth->reset_password($identity, $json['password']);

            // Verifica se a redefinição de senha foi bem-sucedida
            if ($change) {
                // Se a senha foi alterada com sucesso, envia uma resposta de sucesso
                $this->retorno(false, "Senha alterada com sucesso.", null, 200);
            } else {
                // Se houver erros durante a redefinição da senha, envia uma resposta de falha
                $this->retorno(false, "Ocorreu um erro ao alterar a senha.", null, 403);
            }
        }

        // Se o código de resete não for válido, envia uma resposta de falha
        $this->retorno(false, "O código de resete de senha informado não é válido.", null, 403);
    }
}
