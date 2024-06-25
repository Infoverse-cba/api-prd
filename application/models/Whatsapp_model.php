<?php

class Whatsapp_model extends CI_Model
{
    public function __construct()
    {
        parent::__construct();
        $this->load->database();
    }

    /**
     * Função para inserir uma nova instância no banco de dados.
     *
     * Esta função insere uma nova instância no banco de dados usando os dados fornecidos. Utiliza o método insert do CodeIgniter para realizar a inserção na tabela 'whats_instance'. Após a inserção, verifica se a operação teve êxito. Se a inserção for bem-sucedida, retorna o ID do registro inserido. Caso contrário, retorna false.
     *
     * @param array $dados - Os dados da instância a serem inseridos no banco de dados.
     * @return mixed - Retorna o ID do registro inserido se a inserção for bem-sucedida, caso contrário, retorna false.
     */
    public function setInstance($dados)
    {
        $this->db->insert('whats_instance', $dados);

        // Verifica se a inserção teve êxito
        if ($this->db->affected_rows() > 0) {
            // Retorna o ID do registro inserido
            return $this->db->insert_id();
        } else {
            // Retorna false se a inserção falhou
            return false;
        }
    }

    /**
     * Função para verificar se uma instância já existe no banco de dados.
     *
     * Esta função verifica se uma instância com a chave e o cliente fornecidos já existe no banco de dados. Seleciona a ID da instância na tabela 'whats_instance' com base na chave da instância e no ID do cliente. Se a consulta retornar uma ou mais linhas, significa que a instância já existe no banco de dados e retorna true. Caso contrário, retorna false.
     *
     * @param string $instance_key - A chave da instância a ser verificada.
     * @param int $cliente_id - O ID do cliente associado à instância.
     * @return bool - Retorna true se a instância já existe no banco de dados, caso contrário, retorna false.
     */
    public function instanceInDb($instance_key, $cliente_id)
    {
        // Selecione a ID da instância baseada na chave fornecida
        $this->db->select('id');
        $this->db->from('whats_instance');
        $this->db->where('instance_key', $instance_key);
        $this->db->where('cliente_id', $cliente_id);
        $query = $this->db->get();

        // Verifique se a consulta retornou alguma linha
        if ($query->num_rows() > 0) {
            return true; // Se houver uma linha, retorne true
        } else {
            return false; // Caso contrário, retorne false
        }
    }

    /**
     * Função para obter informações sobre as instâncias associadas a um cliente.
     *
     * Esta função retorna informações detalhadas sobre as instâncias do WhatsApp associadas a um cliente específico. Seleciona os campos desejados da tabela 'whats_instance' e junta-os com os dados dos usuários da tabela 'auth_users' usando um join. Pode opcionalmente filtrar as instâncias pelo ID do cliente fornecido. Se houver resultados encontrados, retorna um array de objetos contendo as informações das instâncias. Caso contrário, retorna false.
     *
     * @param int|null $cliente_id - O ID do cliente para o qual obter as instâncias associadas (opcional).
     * @return mixed - Retorna um array de objetos contendo as informações das instâncias se houver resultados, caso contrário, retorna false.
     */
    public function getInstances($cliente_id = null)
    {
        // Selecionar os campos desejados
        $this->db->select('wi.id, wi.cliente_id, wi.created_user_id, wi.phone_connected, wi.phone_number, wi.descricao, au.first_name as user_first_name, au.last_name as user_last_name, wi.instance_key, wi.dt_criacao, wi.dt_verificado');

        // Definir a tabela principal e a junção com a tabela de usuários
        $this->db->from('whats_instance wi');
        $this->db->join('auth_users au', 'au.id = wi.created_user_id');

        // Condição WHERE para filtrar pelo cliente_id fornecido
        if (!is_null($cliente_id)) {
            $this->db->where('wi.cliente_id', $cliente_id);
        }

        // Executar a consulta
        $query = $this->db->get();

        // Verificar se há resultados
        if ($query->num_rows() > 0) {
            return $query->result(); // Retornar resultados se encontrados
        } else {
            return false; // Retornar false se nenhum resultado for encontrado
        }
    }

    /**
     * Função para obter informações detalhadas sobre uma instância do WhatsApp.
     *
     * Esta função retorna informações detalhadas sobre uma instância específica do WhatsApp. Seleciona os campos desejados da tabela 'whats_instance' e junta-os com os dados dos usuários da tabela 'auth_users' usando um join. Pode opcionalmente filtrar a instância pela chave da instância e/ou pelo ID do cliente fornecidos. Se houver uma instância correspondente encontrada, retorna um objeto contendo as informações da instância. Caso contrário, retorna false.
     *
     * @param string|null $instance_key - A chave da instância do WhatsApp para a qual obter informações (opcional).
     * @param int|null $cliente_id - O ID do cliente associado à instância (opcional).
     * @return mixed - Retorna um objeto contendo as informações detalhadas da instância se encontrada, caso contrário, retorna false.
     */
    public function getInstance($instance_key = null, $cliente_id = null)
    {
        // Selecionar os campos desejados
        $this->db->select('wi.id, wi.cliente_id, wi.created_user_id, wi.phone_connected, wi.phone_number, wi.descricao, au.first_name as user_first_name, au.last_name as user_last_name, wi.instance_key, wi.dt_criacao, wi.dt_verificado');

        // Definir a tabela principal e a junção com a tabela de usuários
        $this->db->from('whats_instance wi');
        $this->db->join('auth_users au', 'au.id = wi.created_user_id');

        // Condição WHERE para filtrar pela chave da instância, se fornecida
        if (!is_null($instance_key)) {
            $this->db->where('wi.instance_key', $instance_key);
        }

        // Condição WHERE para filtrar pelo ID do cliente, se fornecido
        if (!is_null($cliente_id)) {
            $this->db->where('wi.cliente_id', $cliente_id);
        }

        // Executar a consulta
        $query = $this->db->get();

        // Verificar se há resultados
        if ($query->num_rows() > 0) {
            return $query->row(); // Retornar resultados se encontrados
        } else {
            return false; // Retornar false se nenhum resultado for encontrado
        }
    }

    /**
     * Função para atualizar os dados de uma instância do WhatsApp no banco de dados.
     *
     * Esta função atualiza os dados de uma instância específica do WhatsApp na tabela 'whats_instance' com base no ID fornecido. Utiliza o método update do CodeIgniter para realizar a atualização. Se a atualização for bem-sucedida, retorna true. Caso contrário, retorna false.
     *
     * @param array $dados - Os novos dados da instância a serem atualizados no banco de dados.
     * @param int $id - O ID da instância do WhatsApp a ser atualizada.
     * @return bool - Retorna true se a atualização for bem-sucedida, caso contrário, retorna false.
     */
    public function updateInstance($dados, $instance_key)
    {
        // Atualiza os dados na tabela 'whats_instance' onde o ID corresponde ao fornecido
        $this->db->update('whats_instance', $dados, ['instance_key' => $instance_key]);

        // Verifica se a atualização teve êxito
        if ($this->db->affected_rows() > 0) {
            // Retorna true se a atualização foi bem-sucedida
            return true;
        } else {
            // Retorna false se a atualização falhou
            return false;
        }
    }

    /**
     * Função para excluir uma instância do WhatsApp do banco de dados.
     *
     * Esta função recebe o ID da instância e o ID do cliente como parâmetros. Verifica se ambos os valores não são nulos. Se algum deles for nulo, retorna false. Caso contrário, define as condições para a exclusão e executa a exclusão na tabela 'whats_instance'. Se a exclusão for bem-sucedida, retorna true. Caso contrário, retorna false.
     *
     * @param int|null $id - O ID da instância do WhatsApp a ser excluída (opcional).
     * @param int|null $cliente_id - O ID do cliente associado à instância (opcional).
     * @return bool - Retorna true se a exclusão for bem-sucedida, caso contrário, retorna false.
     */
    public function deleteInstance($id = null, $cliente_id = null)
    {
        // Verificar se o ID e o ID do cliente não são nulos
        if ($id === null || $cliente_id === null) {
            return false; // Se algum dos valores for nulo, retorna false
        }

        // Condições para a exclusão
        $condicoes = array(
            'id' => $id,
            'cliente_id' => $cliente_id,
        );

        // Executa a exclusão
        $this->db->where($condicoes);
        $delete_result = $this->db->delete('whats_instance');

        // Verificar se o delete ocorreu com sucesso
        if ($delete_result) {
            return true; // Se o delete ocorreu com sucesso, retorna true
        } else {
            return false; // Se o delete falhou, retorna false
        }
    }

    public function insertWebhookQrcode($dados)
    {
        $this->db->insert('whats_webhook_qrcode', $dados);

        // Verifica se a inserção teve êxito
        if ($this->db->affected_rows() > 0) {
            // Retorna o ID do registro inserido
            return $this->db->insert_id();
        } else {
            // Retorna false se a inserção falhou
            return false;
        }
    }

    public function getQrCode($instance_key = null)
    {
        $this->db->select('dt_received, attempts, "session", qrcode');
        $this->db->from('whats_webhook_qrcode wwq');

        // Condição WHERE para filtrar pela chave da instância, se fornecida
        if (!is_null($instance_key)) {
            $this->db->where('"session"', $instance_key);;
        }

        $this->db->where('state', 'QRCODE_RECEIVED');
        $this->db->where('status', 'awaitReadQrCode');
        $this->db->order_by('dt_received', 'desc');
        $this->db->limit(1);

        // Executar a consulta
        $query = $this->db->get();

        // Verificar se há resultados
        if ($query->num_rows() > 0) {
            return $query->row(); // Retornar resultados se encontrados
        } else {
            return false; // Retornar false se nenhum resultado for encontrado
        }
    }

    public function insertGruposmonitorado($dados)
    {
        $this->db->insert('whats_monitored_group', $dados);

        // Verifica se a inserção teve êxito
        if ($this->db->affected_rows() > 0) {
            // Retorna o ID do registro inserido
            return $this->db->insert_id();
        } else {
            // Retorna false se a inserção falhou
            return false;
        }
    }

    public function getGruposmonitorado($instance_key = null, $cliente_id = null)
    {
        $this->db->select('wmg.id, wmg.status_id, wmg.cliente_id, wmg.investigacao_id,  wmg.created_user_id, wmg.group_id, wmg.session as instance_key, i.descricao as investigacao_descricao, s.descricao as status_descricao, wi.descricao as session_descricao, c.razao_social as cliente_razao_social, c.nome_fantasia as cliente_nome_fantasia ,au.first_name as user_first_name, au.last_name as user_last_name, wmg.group_name, wmg.dt_created');
        $this->db->from('whats_monitored_group wmg');
        $this->db->join('whats_instance wi', 'wi.instance_key = wmg.session');
        $this->db->join('cliente c', 'c.id = wmg.cliente_id');
        $this->db->join('auth_users au', 'au.id = wmg.created_user_id');
        $this->db->join('status s', 's.id = wmg.status_id');
        $this->db->join('investigacao i', 'i.id = wmg.investigacao_id');

        if (!is_null($instance_key)) {
            $this->db->where('"wmg.session"', $instance_key);;
        }

        if (!is_null($cliente_id)) {
            $this->db->where('"wmg.cliente_id"', $cliente_id);;
        }

        // Executar a consulta
        $query = $this->db->get();

        // Verificar se há resultados
        if ($query->num_rows() > 0) {
            return $query->result(); // Retornar resultados se encontrados
        } else {
            return false; // Retornar false se nenhum resultado for encontrado
        }
    }

    public function instanceInCliente($instance_key, $cliente_id)
    {
        // Selecione a ID da instância baseada na chave fornecida
        $this->db->select('id');
        $this->db->from('whats_instance');
        $this->db->where('instance_key', $instance_key);
        $this->db->where('cliente_id', $cliente_id);
        $query = $this->db->get();

        // Verifique se a consulta retornou alguma linha
        if ($query->num_rows() > 0) {
            return true; // Se houver uma linha, retorne true
        } else {
            return false; // Caso contrário, retorne false
        }
    }

    public function waitingQrcode($instance_key, $cliente_id)
    {
        // Selecione a ID da instância baseada na chave fornecida
        $this->db->select('id');
        $this->db->from('whats_instance');
        $this->db->where('instance_key', $instance_key);
        $this->db->where('cliente_id', $cliente_id);
        $this->db->where('waiting_qrcode', true);
        $query = $this->db->get();

        // Verifique se a consulta retornou alguma linha
        if ($query->num_rows() > 0) {
            return true; // Se houver uma linha, retorne true
        } else {
            return false; // Caso contrário, retorne false
        }
    }

    public function readQrcodeError($instance_key, $cliente_id)
    {
        // Selecione a ID da instância baseada na chave fornecida
        $this->db->select('id');
        $this->db->from('whats_instance');
        $this->db->where('instance_key', $instance_key);
        $this->db->where('cliente_id', $cliente_id);
        $this->db->where('read_qrcode_error', true);
        $query = $this->db->get();

        // Verifique se a consulta retornou alguma linha
        if ($query->num_rows() > 0) {
            return true; // Se houver uma linha, retorne true
        } else {
            return false; // Caso contrário, retorne false
        }
    }

    public function insertItemMonitorado($dados)
    {
        $this->db->insert('whats_monitored_item', $dados);

        // Verifica se a inserção teve êxito
        if ($this->db->affected_rows() > 0) {
            // Retorna o ID do registro inserido
            return $this->db->insert_id();
        } else {
            // Retorna false se a inserção falhou
            return false;
        }
    }

    public function getItemMonitorado($is_member = null, $monitored_group_id = null, $status_id = null, $cliente_id = null)
    {
        $this->db->select('wmi.id, wmi.cliente_id, wmi.created_user_id, wmi.status_id, wmg."session" as instance_key, wmi.monitored_group_id, wmi.is_member, s.descricao as status_descricao, au.first_name as user_first_name, au.last_name as user_last_name, wmg.group_id as monitored_group_group_id, wmg.group_name as monitored_group_group_name, wmi.value, wmi.dt_created');
        $this->db->from('whats_monitored_item wmi');
        $this->db->join('status s', 's.id = wmi.status_id');
        $this->db->join('whats_monitored_group wmg', 'wmg.id = wmi.monitored_group_id');
        $this->db->join('auth_users au', 'au.id = wmi.created_user_id');

        if (!is_null($is_member)) {
            $this->db->where('"wmi.is_member"', $is_member);;
        }

        if (!is_null($monitored_group_id)) {
            $this->db->where('"wmi.monitored_group_id"', $monitored_group_id);;
        }

        if (!is_null($status_id)) {
            $this->db->where('"wmi.status_id"', $status_id);;
        }

        if (!is_null($cliente_id)) {
            $this->db->where('"wmi.cliente_id"', $cliente_id);;
        }

        // Executar a consulta
        $query = $this->db->get();

        // Verificar se há resultados
        if ($query->num_rows() > 0) {
            return $query->result(); // Retornar resultados se encontrados
        } else {
            return false; // Retornar false se nenhum resultado for encontrado
        }
    }

    public function grupoMonitoradoInCliente($id, $cliente_id)
    {
        // Selecione a ID da instância baseada na chave fornecida
        $this->db->select('id');
        $this->db->from('whats_monitored_group');
        $this->db->where('id', $id);
        $this->db->where('cliente_id', $cliente_id);
        $query = $this->db->get();

        // Verifique se a consulta retornou alguma linha
        if ($query->num_rows() > 0) {
            return true; // Se houver uma linha, retorne true
        } else {
            return false; // Caso contrário, retorne false
        }
    }

    public function insertWhatsMessage($dados)
    {
        $this->db->insert('whats_message', $dados);

        // Verifica se a inserção teve êxito
        if ($this->db->affected_rows() > 0) {
            // Retorna o ID do registro inserido
            return $this->db->insert_id();
        } else {
            // Retorna false se a inserção falhou
            return false;
        }
    }

    public function updateWhatsMessage($dados, $id_whats)
    {
        // Atualiza os dados na tabela 'whats_instance' onde o ID corresponde ao fornecido
        $this->db->update('whats_message', $dados, ['id_whats' => $id_whats]);

        // Verifica se a atualização teve êxito
        if ($this->db->affected_rows() > 0) {
            // Retorna true se a atualização foi bem-sucedida
            return true;
        } else {
            // Retorna false se a atualização falhou
            return false;
        }
    }

    public function isGruposmonitorado($group_id = null, $cliente_id = null)
    {
        $this->db->select('id, cliente_id');
        $this->db->from('whats_monitored_group');

        if (!is_null($group_id)) {
            $this->db->where('"group_id"', $group_id);
        }

        if (!is_null($cliente_id)) {
            $this->db->where('"wmg.cliente_id"', $cliente_id);
        }

        // Executar a consulta
        $query = $this->db->get();

        // Verificar se há resultados
        if ($query->num_rows() > 0) {
            return $query->row(); // Retornar resultados se encontrados
        } else {
            return false; // Retornar false se nenhum resultado for encontrado
        }
    }

    public function isItemMonitorado($value = null, $cliente_id = null)
    {
        $this->db->select('id, status_id, monitored_group_id, is_member, value, cliente_id, created_user_id, dt_created');
        $this->db->from('whats_monitored_item');
        $this->db->where('"status_id"', 61);

        if (!is_null($cliente_id)) {
            $this->db->where('"cliente_id"', $cliente_id);
        }

        if (!is_null($value)) {
            $this->db->where('"value"', $value);
        }

        // Executar a consulta
        $query = $this->db->get();

        // Verificar se há resultados
        if ($query->num_rows() > 0) {
            return $query->result(); // Retornar resultados se encontrados
        } else {
            return false; // Retornar false se nenhum resultado for encontrado
        }
    }

    public function getMessage($instance_key = null, $group_id = null, $message_id = null, $dt_inicio = null, $dt_fim = null, $order_by = 'DESC', $cliente_id = null)
    {
        $this->db->select('wm.id, wm.id_whats, wm.type, wm.mimetype, wm.isGroupMsg, wm.fromMe, wm.session, wm.status, wm.to, wm.from, wm.timestamp, wm.datetime, wm.caption, wm.base64, wm.content, wm.quotedMsg, wm.quotedMsgId, wm.data_deprecatedMms3Url, wm.data_directPath, wm.data_filehash, wm.data_encFilehash, wm.data_mediaKey, wm.data_mediaKeyTimestamp, wm.data_chatId, wm.sender_id, wm.sender_name, wm.sender_shortName, wm.sender_pushname, wm.sender_verifiedName, wm.sender_type, wm.sender_isBusiness, wm.sender_isEnterprise, wm.sender_isSmb, wm.mediaData_type, wm.mediaData_mediaStage, wm.mediaData_animationDuration, wm.mediaData_animatedAsNewMsg, wm.mediaData_isViewOnce, wm.mediaData_swStreamingSupported, wm.mediaData_listeningToSwSupport, wm.mediaData_isVcardOverMmsDocument, wm.cron_checked');
        $this->db->from('whats_message wm');
        $this->db->join('whats_instance wi', 'wi.instance_key = wm.session');
        $this->db->join('whats_monitored_group wmg', 'wmg.session = wm.session');

        if (!is_null($cliente_id)) {
            $this->db->where('"wi.cliente_id"', $cliente_id);
        }

        if (!is_null($instance_key)) {
            $this->db->where('"wm.session"', $instance_key);
        }

        if (!is_null($group_id)) {
            $this->db->like('wm.data_chatId', $group_id);
        }

        if (!is_null($message_id)) {
            $this->db->where('"wm.id"', $message_id);
        }

        if (!is_null($dt_inicio)) {
            $this->db->where('wm.datetime >=', $dt_inicio . ' 00:00:00');
        }

        if (!is_null($dt_fim)) {
            $this->db->where('wm.datetime <=', $dt_fim . ' 23:59:59');
        }

        $this->db->group_by('wm.id, wm.id_whats, wm.type, wm.mimetype, wm.isGroupMsg, wm.fromMe, wm.session, wm.status, wm.to, wm.from, wm.timestamp, wm.datetime, wm.caption, wm.base64, wm.content, wm.quotedMsg, wm.quotedMsgId, wm.data_deprecatedMms3Url, wm.data_directPath, wm.data_filehash, wm.data_encFilehash, wm.data_mediaKey, wm.data_mediaKeyTimestamp, wm.data_chatId, wm.sender_id, wm.sender_name, wm.sender_shortName, wm.sender_pushname, wm.sender_verifiedName, wm.sender_type, wm.sender_isBusiness, wm.sender_isEnterprise, wm.sender_isSmb, wm.mediaData_type, wm.mediaData_mediaStage, wm.mediaData_animationDuration, wm.mediaData_animatedAsNewMsg, wm.mediaData_isViewOnce, wm.mediaData_swStreamingSupported, wm.mediaData_listeningToSwSupport, wm.mediaData_isVcardOverMmsDocument, wm.cron_checked');
        $this->db->order_by('wm.id', $order_by);

        // Executar a consulta
        $query = $this->db->get();

        // Verificar se há resultados
        if ($query->num_rows() > 0) {
            return $query->result(); // Retornar resultados se encontrados
        } else {
            return false; // Retornar false se nenhum resultado for encontrado
        }
    }

    public function getHistoryMessages($instance_key = null, $group_id = null, $message_id = null, $dt_inicio = null, $dt_fim = null, $qtd_message = null, $page = 1, $order_by = 'DESC', $type = null, $cliente_id = null)
    {

        $id_filter = null;
        $orderby_subquery = null;

        // Definir os parâmetros
        $limit = $qtd_message;
        $offset = ($page - 1) * $qtd_message;

        switch ($type) {
            case 'next':
                $id_filter = '>';
                $orderby_subquery = 'ASC';
                break;
            case 'previous':
                $id_filter = '<';
                $$orderby_subquery = 'DESC';
                break;
            default:
                $id_filter = '';
                $$orderby_subquery = $order_by;
        }

        // Subconsulta
        $this->db->select('wm.id, wm.id_whats, wm.type, wm.mimetype, wm.isGroupMsg, wm.fromMe, wm.session, wm.status, wm.to, wm.from, wm.timestamp, wm.datetime, wm.caption, wm.base64, wm.content, wm.url_title, wm.url_description, wm.quotedMsg, wm.quotedMsgId, wm.data_deprecatedMms3Url, wm.data_directPath, wm.data_filehash, wm.data_encFilehash, wm.data_mediaKey, wm.data_mediaKeyTimestamp, wm.data_chatId, wm.sender_id, wm.sender_name, wm.sender_shortName, wm.sender_pushname, wm.sender_verifiedName, wm.sender_type, wm.sender_isBusiness, wm.sender_isEnterprise, wm.sender_isSmb, wm.mediaData_type, wm.mediaData_mediaStage, wm.mediaData_animationDuration, wm.mediaData_animatedAsNewMsg, wm.mediaData_isViewOnce, wm.mediaData_swStreamingSupported, wm.mediaData_listeningToSwSupport, wm.mediaData_isVcardOverMmsDocument, wm.cron_checked');
        $this->db->from('whats_message wm');
        $this->db->join('whats_instance wi', 'wi.instance_key = wm.session');
        $this->db->join('whats_monitored_group wmg', 'wmg.session = wm.session');

        if (!is_null($message_id)) {
            $this->db->where("wm.id $id_filter", $message_id);
        }

        if (!is_null($cliente_id)) {
            $this->db->where('wi.cliente_id', $cliente_id);
        }

        if (!is_null($instance_key)) {
            $this->db->where('wm.session', $instance_key);
        }

        if (!is_null($dt_inicio)) {
            $this->db->where('wm.datetime >=', $dt_inicio . ' 00:00:00');
        }

        if (!is_null($dt_fim)) {
            $this->db->where('wm.datetime <=', $dt_fim . ' 23:59:59');
        }

        if (!is_null($group_id)) {
            $this->db->like('wm.data_chatId', $group_id);
        }

        $this->db->group_by('wm.id, wm.id_whats, wm.type, wm.mimetype, wm.isGroupMsg, wm.fromMe, wm.session, wm.status, wm.to, wm.from, wm.timestamp, wm.datetime, wm.caption, wm.base64, wm.content, wm.quotedMsg, wm.quotedMsgId, wm.data_deprecatedMms3Url, wm.data_directPath, wm.data_filehash, wm.data_encFilehash, wm.data_mediaKey, wm.data_mediaKeyTimestamp, wm.data_chatId, wm.sender_id, wm.sender_name, wm.sender_shortName, wm.sender_pushname, wm.sender_verifiedName, wm.sender_type, wm.sender_isBusiness, wm.sender_isEnterprise, wm.sender_isSmb, wm.mediaData_type, wm.mediaData_mediaStage, wm.mediaData_animationDuration, wm.mediaData_animatedAsNewMsg, wm.mediaData_isViewOnce, wm.mediaData_swStreamingSupported, wm.mediaData_listeningToSwSupport, wm.mediaData_isVcardOverMmsDocument, wm.cron_checked');
        $this->db->order_by('wm.datetime', $order_by);

        if (!is_null($qtd_message)) {
            $this->db->limit($limit, $offset);
        }

        /*$subquery = $this->db->get_compiled_select();

        // Consulta principal usando a subconsulta
        $query = $this->db->query("SELECT * FROM ($subquery) AS mensagens ORDER BY datetime $order_by");*/

        $query = $this->db->get();

        // Verificar se há resultados e retornar false se não houver
        if ($query->num_rows() > 0) {
            return $query->result();
        } else {
            return false;
        }
    }

    public function getQtdMessages($instance_key = null, $group_id = null, $message_id = null, $dt_inicio = null, $dt_fim = null, $type = null, $cliente_id = null)
    {
        switch ($type) {
            case 'next':
                $type = '>';
                break;
            case 'previous':
                $type = '<';
                break;
            default:
                $type = '';
        }

        // Subconsulta
        $this->db->select('wm.id');
        $this->db->from('whats_message wm');
        $this->db->join('whats_instance wi', 'wi.instance_key = wm.session');
        $this->db->join('whats_monitored_group wmg', 'wmg.session = wm.session');

        if (!is_null($message_id)) {
            $this->db->where("wm.id $type", $message_id);
        }

        if (!is_null($cliente_id)) {
            $this->db->where('wi.cliente_id', $cliente_id);
        }

        if (!is_null($instance_key)) {
            $this->db->where('wm.session', $instance_key);
        }

        if (!is_null($dt_inicio)) {
            $this->db->where('wm.datetime >=', $dt_inicio . ' 00:00:00');
        }

        if (!is_null($dt_fim)) {
            $this->db->where('wm.datetime <=', $dt_fim . ' 23:59:59');
        }

        if (!is_null($group_id)) {
            $this->db->like('wm.data_chatId', $group_id);
        }

        $this->db->group_by('wm.id');

        $subquery = $this->db->get_compiled_select();

        // Consulta principal usando a subconsulta
        $query = $this->db->query("SELECT count(id) as qtd_message FROM ($subquery) AS mensagens");

        // Verificar se há resultados e retornar false se não houver
        if ($query->num_rows() > 0) {
            return $query->row();
        } else {
            return false;
        }
    }

    public function getWhatsAlert($instance_id = null, $investigacao_id = null, $monitored_group_id = null, $monitored_item_id = null, $message_id = null, $dt_inicio = null, $dt_fim = null, $cliente_id = null)
    {
        $this->db->select('wa.id, wa.cliente_id, i.id as investigacao_id, wa.instance_id, wa.monitored_group_id, wa.monitored_item_id, wa.message_id, wmg.group_id as whatsapp_group_id, wmi.is_member as is_member, c.razao_social as cliente_razao_social, c.nome_fantasia as cliente_nome_fantasia, wi.descricao as instance_descricao, i.nome as investigacao_nome, i.descricao as investigacao_descricao, wmg.group_name as whatsapp_group_name, wmi.value as item_value, wm.type as message_type, wm.content as message_content, wm.caption as message_caption, wm.base64 as message_base64, wa.dt_criacao');
        $this->db->from('whats_alert wa');
        $this->db->join('cliente c', 'c.id = wa.cliente_id');
        $this->db->join('whats_instance wi', 'wi.id = wa.instance_id');
        $this->db->join('whats_monitored_group wmg', 'wmg.id = wa.monitored_group_id');
        $this->db->join('investigacao i', 'i.id = wmg.investigacao_id');
        $this->db->join('whats_monitored_item wmi', 'wmi.id = wa.monitored_item_id');
        $this->db->join('whats_message wm', 'wm.id = wa.message_id');

        if (!is_null($cliente_id)) {
            $this->db->where('"wa.cliente_id"', $cliente_id);
        }

        if (!is_null($instance_id)) {
            $this->db->where('"wa.instance_id"', $instance_id);
        }

        if (!is_null($monitored_group_id)) {
            $this->db->where('"wa.monitored_group_id"', $monitored_group_id);
        }

        if (!is_null($monitored_item_id)) {
            $this->db->where('"wa.monitored_item_id"', $monitored_item_id);
        }

        if (!is_null($message_id)) {
            $this->db->where('"wa.message_id"', $message_id);
        }

        if (!is_null($investigacao_id)) {
            $this->db->where('"i.id"', $investigacao_id);
        }

        if (!is_null($dt_inicio)) {
            $this->db->where('wa.dt_criacao >=', $dt_inicio . ' 00:00:00');
        }

        if (!is_null($dt_fim)) {
            $this->db->where('wa.dt_criacao <=', $dt_fim . ' 23:59:59');
        }

        $this->db->order_by('wa.dt_criacao', 'DESC'); // Adicionando a cláusula ORDER BY$this->db->order_by('wa.dt_criacao', 'DESC'); // Adicionando a cláusula ORDER BY

        // Executar a consulta
        $query = $this->db->get();

        // Verificar se há resultados
        if ($query->num_rows() > 0) {
            return $query->result(); // Retornar resultados se encontrados
        } else {
            return false; // Retornar false se nenhum resultado for encontrado
        }
    }
}
