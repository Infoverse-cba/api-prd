<?php

class Usuario_model extends CI_Model
{
    public function __construct()
    {
        parent::__construct();
        $this->load->database();
    }

    /**
     * Insere dados do cliente na tabela 'auth_user_cliente'.
     *
     * @param array $dados - Array contendo os dados do cliente a serem inseridos.
     * @return mixed - Retorna o ID do registro inserido se a inserção for bem-sucedida, ou false em caso de falha.
     */
    public function setCliente($dados)
    {
        // Insere os dados na tabela 'auth_user_cliente'
        $this->db->insert('auth_user_cliente', $dados);

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
     * Verifica se existe uma associação entre um usuário e um cliente na tabela 'auth_user_cliente'.
     *
     * @param int|null $userId - ID do usuário.
     * @param int|null $clientId - ID do cliente.
     * @return bool - Retorna true se a associação existir, ou false caso contrário.
     */
    public function userInClient($userId = null, $clientId = null)
    {
        // Verifica se o ID do usuário foi fornecido
        if ($userId == null) {
            return false;
        }

        // Verifica se o ID do cliente foi fornecido
        if ($clientId == null) {
            return false;
        }

        // Constrói a consulta utilizando Active Record
        $this->db->select('id_user');
        $this->db->from('auth_user_cliente');
        $this->db->where('id_user', $userId);
        $this->db->where('id_cliente', $clientId);

        // Executa a consulta
        $query = $this->db->get();

        // Verifica se há registros correspondentes
        if ($query->num_rows() > 0) {
            // Associação encontrada
            return true;
        } else {
            // Associação não encontrada
            return false;
        }
    }

    /**
     * Obtém informações sobre usuários associados a um cliente específico.
     *
     * @param int|null $clientId - ID do cliente.
     * @return mixed - Retorna um array de objetos com informações dos usuários, ou false se nenhum resultado for encontrado.
     */
    public function getUsers($clientId = null)
    {
        // Verifica se o ID do cliente foi fornecido
        if ($clientId == null) {
            return false;
        }

        // Constrói a consulta utilizando Active Record
        $this->db->select('au.id, au.ip_address, au.username, au.email, au.remember_selector, au.remember_code, au.created_on, au.last_login, au.active, au.first_name, au.last_name, au.company, au.phone');
        $this->db->from('auth_users au');
        $this->db->join('auth_user_cliente auc', 'auc.id_user = au.id');
        $this->db->where('auc.id_cliente', $clientId);

        // Executa a consulta
        $query = $this->db->get();

        // Verifica se há resultados
        if ($query->num_rows() > 0) {
            // Resultados encontrados, retorna um array de objetos
            return $query->result();
        } else {
            // Nenhum resultado encontrado, retorna false
            return false;
        }
    }

    /**
     * Obtém informações sobre usuários ativos associados a um cliente específico.
     *
     * @param int|null $clientId - ID do cliente.
     * @return mixed - Retorna um array de objetos com informações dos usuários ativos, ou false se nenhum resultado for encontrado.
     */
    public function getUsersActive($clientId = null)
    {
        // Verifica se o ID do cliente foi fornecido
        if ($clientId == null) {
            return false;
        }

        // Constrói a consulta utilizando Active Record
        $this->db->select('au.id, au.ip_address, au.username, au.email, au.remember_selector, au.remember_code, au.created_on, au.last_login, au.active, au.first_name, au.last_name, au.company, au.phone');
        $this->db->from('auth_users au');
        $this->db->join('auth_user_cliente auc', 'auc.id_user = au.id');
        $this->db->where('auc.id_cliente', $clientId);
        $this->db->where('au.active', 1);

        // Executa a consulta
        $query = $this->db->get();

        // Verifica se há resultados
        if ($query->num_rows() > 0) {
            // Resultados encontrados, retorna um array de objetos
            return $query->result();
        } else {
            // Nenhum resultado encontrado, retorna false
            return false;
        }
    }

    /**
     * Obtém informações sobre usuários inativos associados a um cliente específico.
     *
     * @param int|null $clientId - ID do cliente.
     * @return mixed - Retorna um array de objetos com informações dos usuários inativos, ou false se nenhum resultado for encontrado.
     */
    public function getUsersDeactive($clientId = null)
    {
        // Verifica se o ID do cliente foi fornecido
        if ($clientId == null) {
            return false;
        }

        // Constrói a consulta utilizando Active Record
        $this->db->select('au.id, au.ip_address, au.username, au.email, au.remember_selector, au.remember_code, au.created_on, au.last_login, au.active, au.first_name, au.last_name, au.company, au.phone');
        $this->db->from('auth_users au');
        $this->db->join('auth_user_cliente auc', 'auc.id_user = au.id');
        $this->db->where('auc.id_cliente', $clientId);
        $this->db->where('au.active', 0);

        // Executa a consulta
        $query = $this->db->get();

        // Verifica se há resultados
        if ($query->num_rows() > 0) {
            // Resultados encontrados, retorna um array de objetos
            return $query->result();
        } else {
            // Nenhum resultado encontrado, retorna false
            return false;
        }
    }

    /**
     * Obtém informações detalhadas de um usuário associado a um cliente específico.
     *
     * @param int|null $userId - ID do usuário.
     * @param int|null $clientId - ID do cliente.
     * @return mixed - Retorna um objeto com informações detalhadas do usuário, ou false se nenhum resultado for encontrado.
     */
    public function getUser($userId = null, $clientId = null)
    {
        // Verifica se o ID do cliente e do usuário foram fornecidos
        if ($clientId == null || $userId == null) {
            return false;
        }

        // Constrói a consulta utilizando Active Record
        $this->db->select('au.id, au.ip_address, au.username, au.email, au.remember_selector, au.remember_code, au.created_on, au.last_login, au.active, au.first_name, au.last_name, au.company, au.phone');
        $this->db->from('auth_users au');
        $this->db->join('auth_user_cliente auc', 'auc.id_user = au.id');
        $this->db->where('auc.id_cliente', $clientId);
        $this->db->where('auc.id_user', $userId);
        $this->db->limit(1);

        // Executa a consulta
        $query = $this->db->get();

        // Verifica se há resultados
        if ($query->num_rows() > 0) {
            // Resultado encontrado, retorna a primeira linha como objeto
            return $query->row();
        } else {
            // Nenhum resultado encontrado, retorna false
            return false;
        }
    }

    /**
     * Atualiza os dados de um usuário na tabela 'auth_users'.
     *
     * @param array $dados - Novos dados a serem atualizados.
     * @param int $id - ID do usuário a ser atualizado.
     * @return bool - Retorna true se a atualização for bem-sucedida, false se falhar.
     */
    public function updateUser($dados, $id)
    {
        // Atualiza os dados na tabela 'auth_users' onde o ID corresponde ao fornecido
        $this->db->update('auth_users', $dados, ['id' => $id]);

        // Verifica se a atualização teve êxito
        if ($this->db->affected_rows() > 0) {
            // Retorna true se a atualização foi bem-sucedida
            return true;
        } else {
            // Retorna false se a atualização falhou
            return false;
        }
    }

}
