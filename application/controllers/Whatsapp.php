<?php

defined('BASEPATH') or exit('No direct script access allowed');



use chriskacerguis\RestServer\RestController;



class Whatsapp extends RestController
{

    function __construct()
    {
        // Construct the parent class
        parent::__construct();

        $this->load->library(['whatsapp_library', 'ion_auth', 'session']); // Carrega a biblioteca Whatsapp_library
        $this->load->model('Whatsapp_model', 'whatsapp');
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
    public function retorno($status, $message, $data = null, $code = 200)
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
     * Função para obter informações sobre as instâncias associadas a um cliente.
     *
     * Esta função verifica se o usuário está autenticado antes de prosseguir. Em seguida, chama o método getInfo da biblioteca Whatsapp_library para obter informações sobre as instâncias associadas ao cliente atualmente logado. Se houver sucesso na obtenção das informações, retorna uma mensagem indicando que as instâncias foram encontradas junto com os dados das instâncias. Caso contrário, retorna uma mensagem indicando que nenhuma instância foi encontrada.
     *
     * @param void
     * @return void
     */
    public function instances_get()
    {
        // Verifica se o usuário está autenticado
        if (!$this->ion_auth->logged_in()) {
            $this->retorno(false, "Usuário não autenticado.", null, 401);
        }

        // Chama o método getInfo da biblioteca Whatsapp_library para obter informações sobre a instância
        $info = $this->whatsapp->getInstances($this->session->userdata('cliente_id'));

        // Verifica se houve um erro durante a solicitação
        if (!$info) {
            $this->retorno(false, "Nenhuma instância foi encontrada.", null, 403);
        } else {
            $this->retorno(true, "Instâncias encontradas.", $info, 200);
        }
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
    public function instance_post()
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

        // Parametros obrigatorios a serem enviados por json por post
        $phone_number = isset($json['phone_number']) ? $json['phone_number'] : null;
        $descricao = isset($json['descricao']) ? $json['descricao'] : null;

        $cliente_id = $this->session->userdata('cliente_id');
        $user_id = $this->session->userdata('user_id');
        $instance_key = $cliente_id . "-" . $phone_number;

        if ($phone_number == null) {
            $this->retorno(false, "O parâmetro 'phone_number' é obrigatório.", null, 401);
        }

        if ($descricao == null) {
            $this->retorno(false, "O parâmetro 'descricao' é obrigatório.", null, 401);
        }

        $instanceInsert = true;

        if (!$this->whatsapp->instanceInDb($instance_key, $cliente_id)) {
            $dados = [
                "cliente_id" => $cliente_id,
                "created_user_id" => $user_id,
                "phone_number" => $phone_number,
                "descricao" => $descricao,
                "instance_key" => $instance_key
            ];

            $instanceInsert = $this->whatsapp->setInstance($dados);
        }

        if (!$instanceInsert) {
            $this->retorno(false, "Ocorreu um erro ao cadastrar a instância. Por favor, tente novamente mais tarde.", null, 401);
        }

        // Chama o método getInfo da biblioteca Whatsapp_library para obter informações sobre a instância
        $info = $this->whatsapp_library->SessionState($instance_key);

        if (isset($info->isConnected) && is_bool($info->isConnected) && $info->isConnected) {
            $this->retorno(false, "Já existe um telefone conectado nesta instância.", null, 401);
        }

        $info = $this->whatsapp_library->SessionStart($instance_key);

        if (!$info->status) {
            $this->retorno(false, "Ocorreu um erro ao gerar o QRCode. Por favor, tente novamente mais tarde.", null, 401);
        }

        $this->retorno(true, "Instancia registrada com sucesso.", (object)['instance_key' => $instance_key], 200);
    }

    /**
     * Função para realizar o logout de uma instância do WhatsApp.
     *
     * Esta função é responsável por efetuar o logout de uma instância do WhatsApp. Primeiro, verifica se o usuário está autenticado e se é um administrador. Em seguida, verifica se foi fornecida uma chave de instância. Se não for fornecida, retorna uma mensagem indicando que o parâmetro 'instance_key' é obrigatório. Então, chama o método InstanceLogout da biblioteca Whatsapp_library para realizar o logout da instância. Se ocorrer um erro durante o logout, retorna uma mensagem indicando que o telefone não está conectado. Caso contrário, atualiza o status da instância no banco de dados como desconectado e retorna uma mensagem indicando que o logout foi realizado com sucesso.
     *
     * @param string|null $instance_key - A chave da instância do WhatsApp a ser desconectada.
     * @return void
     */
    public function logout_delete($instance_key = null)
    {
        // Verifica se o usuário está autenticado
        if (!$this->ion_auth->logged_in()) {
            $this->retorno(false, "Usuário não autenticado.", null, 401);
        }

        // Verifica se o usuário é um administrador
        if (!$this->ion_auth->is_admin()) {
            $this->retorno(false, "Este usuário não é administrador.", null, 401);
        }

        if (is_null($instance_key)) {
            $this->retorno(false, "O parâmetro 'instance_key' é obrigatório.", null, 401);
        }

        // Verifica se a instancia pertence ao cliente
        if (!$this->whatsapp->instanceInCliente($instance_key, $this->session->userdata('cliente_id'))) {
            $this->retorno(false, "A instancia informada não pertence a este cliente.", null, 401);
        }

        $session = $this->whatsapp_library->SessionDelete($instance_key);

        //$this->retorno(true, "Logout realizado com sucesso", $session, 200);

        /*if (isset($session->result) && is_string($session->result) && $session->result === 'error') {
            $this->retorno(false, "Sessão não encontrada.", null, 404);
        }*/

        if (isset($session->close) && is_bool($session->close) && !$session->close) {
            $this->retorno(false, "Erro ao realizar o logout, o telefone não foi desconectadoo.", null, 401);
        }

        $instancia = $this->whatsapp->getInstance($instance_key, $this->session->userdata('cliente_id'));

        if(isset($instancia->id) && !$instancia->id){
            $this->retorno(false, "A instancia $instance_key não foi encontrada no banco de dados.", null, 404);
        }

        $update = $this->whatsapp->updateInstance(["phone_connected" => false, "dt_verificado" => date('Y-m-d H:i:s')], $instance_key);

        $this->retorno(true, "Logout realizado com sucesso", $update, 200);

        $this->retorno(true, "Logout realizado com sucesso", null, 200);
    }

    /**
     * Função para excluir uma instância do WhatsApp.
     *
     * Esta função verifica se o usuário está autenticado e se é um administrador. Em seguida, verifica se a chave da instância foi fornecida. Se não, retorna uma mensagem de erro. Caso contrário, chama a função InstanceDelete da biblioteca whatsapp_library para excluir a instância. Se a exclusão for bem-sucedida, remove a instância do banco de dados usando a função deleteInstance e retorna uma mensagem de sucesso. Se ocorrer um erro durante a exclusão, retorna uma mensagem indicando que o telefone não está conectado.
     *
     * @param string|null $instance_key - A chave da instância do WhatsApp a ser excluída (opcional).
     * @return void
     */
    public function instance_delete($instance_key = null)
    {
        // Verifica se o usuário está autenticado
        if (!$this->ion_auth->logged_in()) {
            $this->retorno(false, "Usuário não autenticado.", null, 401);
        }

        // Verifica se o usuário é um administrador
        if (!$this->ion_auth->is_admin()) {
            $this->retorno(false, "Este usuário não é administrador.", null, 401);
        }

        if (is_null($instance_key)) {
            $this->retorno(false, "O parâmetro 'instance_key' é obrigatório.", null, 401);
        }

        // Verifica se a instancia pertence ao cliente
        if (!$this->whatsapp->instanceInCliente($instance_key, $this->session->userdata('cliente_id'))) {
            $this->retorno(false, "A instancia informada não pertence a este cliente.", null, 401);
        }

        //$logout = $this->whatsapp_library->InstanceLogout($instance_key);

        $instancia = $this->whatsapp->getInstance($instance_key, $this->session->userdata('cliente_id'));

        if ($instancia) {
            $this->whatsapp->SessionDelete($instancia->id, $this->session->userdata('cliente_id'));
        }

        $this->retorno(true, "Instância excluída com sucesso.", null, 200);
    }

    public function qrcode_get($instance_key = null)
    {
        // Verifica se o usuário está autenticado
        if (!$this->ion_auth->logged_in()) {
            $this->retorno(false, "Usuário não autenticado.", null, 401);
        }

        if (is_null($instance_key)) {
            $this->retorno(false, "O parâmetro 'instance_key' é obrigatório.", null, 401);
        }

        // Verifica se a instancia pertence ao cliente
        if (!$this->whatsapp->instanceInCliente($instance_key, $this->session->userdata('cliente_id'))) {
            $this->retorno(false, "A instancia informada não pertence a este cliente.", null, 401);
        }

        // Chama o método getInfo da biblioteca Whatsapp_library para obter informações sobre a instância
        $info = $this->whatsapp_library->SessionState($instance_key);
        $phone_connected = isset($info->isConnected) && is_bool($info->isConnected) ? $info->isConnected : false;

        //$this->retorno(true, "Aguardando leitura do QR Code.", $info, 200);

        if ((isset($info->response) && is_bool($info->response) && !$info->response) && (isset($info->status) && is_string($info->status) && $info->status === 'NOT FOUND')) {
            $info = $this->whatsapp_library->SessionStart($instance_key);
        }

        if (isset($info->isConnected) && is_bool($info->isConnected) && $info->isConnected) {
            $this->retorno(true, "Já existe um telefone conectado nesta instância.", (object)['session' => $instance_key, 'phone_connected' => $phone_connected], 200);
        }

        if ($this->whatsapp->waitingQrcode($instance_key, $this->session->userdata('cliente_id'))) {
            $this->retorno(true, "Aguardando QR Code.", (object)['session' => $instance_key, 'phone_connected' => $phone_connected], 200);
        }

        if ($this->whatsapp->readQrcodeError($instance_key, $this->session->userdata('cliente_id'))) {
            $info = $this->whatsapp_library->SessionStart($instance_key);

            if (!$info->status) {
                $this->retorno(true, "Ocorreu um erro ao solicitar um novo QR Code.", (object)['session' => $instance_key, 'phone_connected' => $phone_connected], 401);
            }

            $dados = [
                'phone_connected' => false,
                'read_qrcode_error' => false,
                'waiting_qrcode' => true
            ];

            $this->whatsapp->updateInstance($dados, $instance_key);

            $this->retorno(true, "Um novo QR Code foi solicitado.", (object)['session' => $instance_key, 'phone_connected' => $phone_connected], 200);
        }

        // Chama o método getInfo da biblioteca Whatsapp_library para obter informações sobre a instância
        $info = $this->whatsapp->getQrCode($instance_key);

        // Verifica se houve um erro durante a solicitação
        if (!$info) {
            $this->retorno(true, "Nenhum QR Code foi encontrado.", (object)['session' => $instance_key, 'phone_connected' => $phone_connected], 403);
        } else {

            $resul = (object)[
                'session' => $info->session,
                'phone_connected' => $phone_connected,
                'attempts' => $info->attempts,
                'qrcode' => $info->qrcode
            ];

            $this->retorno(true, "Aguardando leitura do QR Code.", $resul, 200);
        }
    }

    public function groups_get($instance_key = null)
    {
        // Verifica se o usuário está autenticado
        if (!$this->ion_auth->logged_in()) {
            $this->retorno(false, "Usuário não autenticado.", null, 401);
        }

        if (is_null($instance_key)) {
            $this->retorno(false, "O parâmetro 'instance_key' é obrigatório.", null, 401);
        }

        // Verifica se a instancia pertence ao cliente
        if (!$this->whatsapp->instanceInCliente($instance_key, $this->session->userdata('cliente_id'))) {
            $this->retorno(false, "A instancia informada não pertence a este cliente.", null, 401);
        }

        // Chama o método getInfo da biblioteca Whatsapp_library para obter informações sobre a instância
        $info = $this->whatsapp_library->AllGroups($instance_key);

        // Verifica se houve um erro durante a solicitação
        if (count($info->groups) == 0) {
            $this->retorno(false, "Nenhum grupo encontrado.", null, 403);
        } else {
            $this->retorno(true, "Grupo(s) encontrado(s).", $info->groups, 200);
        }
    }

    public function groupmembers_get($instance_key = null, $group_id = null)
    {
        // Verifica se o usuário está autenticado
        if (!$this->ion_auth->logged_in()) {
            $this->retorno(false, "Usuário não autenticado.", null, 401);
        }

        if (is_null($instance_key)) {
            $this->retorno(false, "O parâmetro 'instance_key' é obrigatório.", null, 401);
        }

        // Verifica se a instancia pertence ao cliente
        if (!$this->whatsapp->instanceInCliente($instance_key, $this->session->userdata('cliente_id'))) {
            $this->retorno(false, "A instancia informada não pertence a este cliente.", null, 401);
        }

        // Chama o método getInfo da biblioteca Whatsapp_library para obter informações sobre a instância
        $info = $this->whatsapp_library->GroupMembers($instance_key, $group_id);

        // Verifica se houve um erro durante a solicitação
        if (count($info->participants) == 0) {
            $this->retorno(false, "Nenhum membro encontrado.", null, 403);
        } else {
            $this->retorno(true, "Membros(s) encontrado(s).", $info->participants, 200);
        }
    }

    public function groupadmins_get($instance_key = null, $group_id = null)
    {
        // Verifica se o usuário está autenticado
        if (!$this->ion_auth->logged_in()) {
            $this->retorno(false, "Usuário não autenticado.", null, 401);
        }

        if (is_null($instance_key)) {
            $this->retorno(false, "O parâmetro 'instance_key' é obrigatório.", null, 401);
        }

        // Verifica se a instancia pertence ao cliente
        if (!$this->whatsapp->instanceInCliente($instance_key, $this->session->userdata('cliente_id'))) {
            $this->retorno(false, "A instancia informada não pertence a este cliente.", null, 401);
        }

        // Chama o método getInfo da biblioteca Whatsapp_library para obter informações sobre a instância
        $info = $this->whatsapp_library->GroupAdmins($instance_key, $group_id);

        // Verifica se houve um erro durante a solicitação
        if (count($info->admins) == 0) {
            $this->retorno(false, "Nenhum admin encontrado.", null, 403);
        } else {
            $this->retorno(true, "Admin(s) encontrado(s).", $info->admins, 200);
        }
    }

    public function grupomonitorado_post()
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

        // Parametros obrigatorios a serem enviados por json por post
        $instance_key = isset($json['instance_key']) ? $json['instance_key'] : null;
        $group_id = isset($json['group_id']) ? $json['group_id'] : null;
        $investigacao_id = isset($json['investigacao_id']) ? $json['investigacao_id'] : null;
        $group_name = isset($json['group_name']) ? $json['group_name'] : null;

        $cliente_id = $this->session->userdata('cliente_id');
        $user_id = $this->session->userdata('user_id');

        if ($instance_key == null) {
            $this->retorno(false, "O parâmetro 'instance_key' é obrigatório.", null, 401);
        }

        if ($group_id == null) {
            $this->retorno(false, "O parâmetro 'group_id' é obrigatório.", null, 401);
        }

        if ($investigacao_id == null) {
            $this->retorno(false, "O parâmetro 'investigacao_id' é obrigatório.", null, 401);
        }

        if ($group_name == null) {
            $this->retorno(false, "O parâmetro 'group_name' é obrigatório.", null, 401);
        }

        $dados = [
            "session" => $instance_key,
            "group_id" => $group_id,
            "investigacao_id" => $investigacao_id,
            "group_name" => $group_name,
            "cliente_id" => $cliente_id,
            "created_user_id" => $user_id
        ];

        $insertGruposmonitorado = $this->whatsapp->insertGruposmonitorado($dados);

        if ($insertGruposmonitorado) {
            $this->retorno(true, "Grupo cadastrado com sucesso.", null, 200);
        } else {
            $this->retorno(false, "Falha ao acadastrar grupo.", null, 401);
        }
    }

    public function grupomonitorado_get($instance_key = null)
    {
        // Verifica se o usuário está autenticado
        if (!$this->ion_auth->logged_in()) {
            $this->retorno(false, "Usuário não autenticado.", null, 401);
        }

        // Verifica se a instancia pertence ao cliente
        if (!$this->whatsapp->instanceInCliente($instance_key, $this->session->userdata('cliente_id'))) {
            $this->retorno(false, "A instancia informada não pertence a este cliente.", null, 401);
        }

        // Chama o método getInfo da biblioteca Whatsapp_library para obter informações sobre a instância
        $info = $this->whatsapp->getGruposmonitorado($instance_key, $this->session->userdata('cliente_id'));

        // Verifica se houve um erro durante a solicitação
        if (!$info) {
            $this->retorno(false, "Não foi encontrado nenhum grupo a ser monitorado.", null, 403);
        } else {
            $this->retorno(true, "Foram encontrados grupos a serem monitorados.", $info, 200);
        }
    }

    public function itemmonitorado_post()
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

        // Parametros obrigatorios a serem enviados por json por post
        $monitored_group_id = isset($json['monitored_group_id']) ? $json['monitored_group_id'] : null;
        $is_member = isset($json['is_member']) && is_bool($json['is_member']) ? $json['is_member'] : null;
        $value = isset($json['value']) ? $json['value'] : null;

        $cliente_id = $this->session->userdata('cliente_id');
        $user_id = $this->session->userdata('user_id');

        if ($monitored_group_id == null) {
            $this->retorno(false, "O parâmetro 'monitored_group_id' é obrigatório.", null, 401);
        }

        if (is_null($is_member)) {
            $this->retorno(false, "O parâmetro 'is_member' é obrigatório.", null, 401);
        }

        if (!is_bool($is_member)) {
            $this->retorno(false, "O parâmetro 'is_member' deve ser um valor boolean.", null, 401);
        }

        if ($value == null) {
            $this->retorno(false, "O parâmetro 'value' é obrigatório.", null, 401);
        }

        $dados = [
            "monitored_group_id" => $monitored_group_id,
            "is_member" => $is_member,
            "value" => $value,
            "cliente_id" => $cliente_id,
            "created_user_id" => $user_id
        ];

        $result = $this->whatsapp->insertItemMonitorado($dados);

        if ($result) {
            $this->retorno(true, "Item cadastrado com sucesso.", null, 200);
        } else {
            $this->retorno(false, "Falha ao acadastrar item.", null, 401);
        }
    }

    public function itemmonitorado_get()
    {
        // Verifica se o usuário está autenticado
        if (!$this->ion_auth->logged_in()) {
            $this->retorno(false, "Usuário não autenticado.", null, 401);
        }

        $is_member = null;
        $monitored_group_id = null;

        if (isset($_GET['is_member'])) {
            $is_member = strtolower($_GET['is_member']) == 'true' ? true : (strtolower($_GET['is_member']) == 'false' ? false : null);
        }

        if (isset($_GET['monitored_group_id'])) {
            $monitored_group_id = $_GET['monitored_group_id'];
        }

        // Chama o método getInfo da biblioteca Whatsapp_library para obter informações sobre a instância
        $result = $this->whatsapp->getItemMonitorado($is_member, $monitored_group_id, null, $this->session->userdata('cliente_id'));

        // Verifica se houve um erro durante a solicitação
        if (!$result) {
            $this->retorno(false, "Não foi encontrado nenhum item a ser monitorado.", null, 403);
        } else {
            $this->retorno(true, "Foram encontrados iten a serem monitorados.", $result, 200);
        }
    }

    public function getmessage_post()
    {
        // Verifica se o usuário está autenticado
        if (!$this->ion_auth->logged_in()) {
            $this->retorno(false, "Usuário não autenticado.", null, 401);
        }

        // Obtém dados do corpo da requisição
        $json = $this->request->body;

        // Parametros obrigatorios a serem enviados por json por post
        $instance_key = (isset($json['instance_key']) && !empty($json['instance_key'])) ? $json['instance_key'] : null;
        $group_id = (isset($json['group_id']) && !empty($json['group_id'])) ? $json['group_id'] : null;
        $message_id = (isset($json['message_id']) && !empty($json['message_id'])) ? $json['message_id'] : null;
        $order_by = (isset($json['order_by']) && !empty($json['order_by'])) ? $json['order_by'] : 'DESC';
        $qtd_message = (isset($json['qtd_message']) && !empty($json['qtd_message'])) ? (int)$json['qtd_message'] : null;
        $dt_inicio = (isset($json['dt_inicio']) && !empty($json['dt_inicio'])) ? $json['dt_inicio'] : null;
        $dt_fim = (isset($json['dt_fim']) && !empty($json['dt_fim'])) ? $json['dt_fim'] : null;
        $page = (isset($json['page']) && !empty($json['page'])) ? $json['page'] : 1;
        $return_current = (isset($json['return_current']) && is_bool($json['return_current'])) ? $json['return_current'] : true;
        $return_previous = (isset($json['return_previous']) && is_bool($json['return_previous'])) ? $json['return_previous'] : false;
        $return_next = (isset($json['return_next']) && is_bool($json['return_next'])) ? $json['return_next'] : false;


        if ($return_next && (is_null($qtd_message) || is_null($message_id))) {
            $this->retorno(false, "Para retornar as proximas mensagens, é necessário especificar a quantidade de mensagens por página e o ID da mensagem atual.", null, 401);
        }

        if ($return_previous && (is_null($qtd_message) || is_null($message_id))) {
            $this->retorno(false, "Para retornar mensagens anteriores, é necessário especificar a quantidade de mensagens por página e o ID da mensagem atual.", null, 401);
        }

        // Verifica se a instancia pertence ao cliente
        if (!$this->whatsapp->instanceInCliente($instance_key, $this->session->userdata('cliente_id'))) {
            $this->retorno(false, "A instancia informada não pertence a este cliente.", null, 401);
        }

        $result_return = false;
        $pagination_return = false;
        $result_all = false;

        if (isset($return_current) && $return_current) {
            //$message = $this->whatsapp->getMessage($instance_key, $group_id, $message_id, $dt_inicio, $dt_fim, $order_by, $this->session->userdata('cliente_id'));
            $message = $this->whatsapp->getHistoryMessages($instance_key, $group_id, $message_id,  $dt_inicio, $dt_fim, $qtd_message, $page, $order_by, null, $this->session->userdata('cliente_id'));
            if ($message && count($message) == 1) {
                $result['message']['current_menssage'] = $message;
                $result_return = true;
                $result_all = false;
            } else if ($message && count($message) > 1) {
                $result['message'] = $message;
                $result_return = true;
                $pagination_return = !is_null($qtd_message) ? true : false;
                $result_all = true;
            } else {
                $result_return = false;
                $pagination_return = false;
                $result_all = false;
            }
        }

        if (isset($return_next) && $return_next) {

            $next_menssages = $this->whatsapp->getHistoryMessages($instance_key, $group_id, $message_id,  $dt_inicio, $dt_fim, $qtd_message, $page, $order_by, 'next', $this->session->userdata('cliente_id'));

            if ($next_menssages) {
                $result['message']['next_menssages'] = $next_menssages;
                $result_return = true;
                $pagination_return = !is_null($qtd_message) ? true : false;
            } else {
                $result_return = false;
                $pagination_return = false;
            }
        }

        if (isset($return_previous) && $return_previous) {
            $previous_menssages = $this->whatsapp->getHistoryMessages($instance_key, $group_id, $message_id,  $dt_inicio, $dt_fim, $qtd_message, $page, $order_by, 'previous', $this->session->userdata('cliente_id'));

            if ($previous_menssages) {
                $result['message']['previous_menssages'] = $previous_menssages;
                $result_return = true;
                $pagination_return = !is_null($qtd_message) ? true : false;
            } else {
                $result_return = false;
                $pagination_return = false;
            }
        }

        if (isset($pagination_return) && $pagination_return) {
            $result['pagination'] = [
                'total_messages_per_page' => (int)$qtd_message,
                'current_page' => (int)$page
            ];

            if ($result_all) {
                $result['pagination']['total_message'] = (int)$this->whatsapp->getQtdMessages($instance_key, $group_id, $message_id,  $dt_inicio, $dt_fim, null, $this->session->userdata('cliente_id'))->qtd_message;
                $result['pagination']['total_page'] = ceil((int)$this->whatsapp->getQtdMessages($instance_key, $group_id, $message_id,  $dt_inicio, $dt_fim, null, $this->session->userdata('cliente_id'))->qtd_message / $qtd_message);
            } else {
                if (isset($return_next) && $return_next) {
                    $result['pagination']['next_menssages'] = [
                        'total_message' => (int)$this->whatsapp->getQtdMessages($instance_key, $group_id, $message_id,  $dt_inicio, $dt_fim, 'next', $this->session->userdata('cliente_id'))->qtd_message,
                        'total_page' => ceil((int)$this->whatsapp->getQtdMessages($instance_key, $group_id, $message_id,  $dt_inicio, $dt_fim, 'next', $this->session->userdata('cliente_id'))->qtd_message / $qtd_message)
                    ];
                }

                if (isset($previous_menssages) && $previous_menssages) {
                    $result['pagination']['previous_menssages'] = [
                        'total_message' => (int)$this->whatsapp->getQtdMessages($instance_key, $group_id, $message_id,  $dt_inicio, $dt_fim, 'previous', $this->session->userdata('cliente_id'))->qtd_message,
                        'total_page' => ceil((int)$this->whatsapp->getQtdMessages($instance_key, $group_id, $message_id,  $dt_inicio, $dt_fim, 'previous', $this->session->userdata('cliente_id'))->qtd_message / $qtd_message)
                    ];
                }
            }
        }

        // Verifica se houve um erro durante a solicitação
        if (!$result_return) {
            $this->retorno(false, "Nenhuma mensagem foi encontrada.", null, 403);
        } else {
            $this->retorno(true, "Mensagens encontradas.", $result, 200);
        }
    }

    public function getalert_post()
    {
        // Verifica se o usuário está autenticado
        if (!$this->ion_auth->logged_in()) {
            $this->retorno(false, "Usuário não autenticado.", null, 401);
        }

        // Obtém dados do corpo da requisição
        $json = $this->request->body;

        // Parametros obrigatorios a serem enviados por json por post
        $instance_id = (isset($json['instance_id']) && !empty($json['instance_id'])) ? $json['instance_id'] : null;
        $investigacao_id = (isset($json['investigacao_id']) && !empty($json['investigacao_id'])) ? $json['investigacao_id'] : null;
        $monitored_group_id = (isset($json['monitored_group_id']) && !empty($json['monitored_group_id'])) ? $json['monitored_group_id'] : null;
        $monitored_item_id = (isset($json['monitored_item_id']) && !empty($json['monitored_item_id'])) ? $json['monitored_item_id'] : null;
        $message_id = (isset($json['message_id']) && !empty($json['message_id'])) ? $json['message_id'] : null;
        $dt_inicio = (isset($json['dt_inicio']) && !empty($json['dt_inicio'])) ? $json['dt_inicio'] : null;
        $dt_fim = (isset($json['dt_fim']) && !empty($json['dt_fim'])) ? $json['dt_fim'] : null;


        // Chama o método getInfo da biblioteca Whatsapp_library para obter informações sobre a instância
        $info = $this->whatsapp->getWhatsAlert($instance_id, $investigacao_id, $monitored_group_id, $monitored_item_id, $message_id, $dt_inicio, $dt_fim, $this->session->userdata('cliente_id'));

        // Verifica se houve um erro durante a solicitação
        if (!$info) {
            $this->retorno(false, "Nenhum alerta encontrad.", null, 403);
        } else {
            $this->retorno(true, "Alertas encontrados.", $info, 200);
        }
    }
}
