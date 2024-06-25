<?php
class Investigacao_model extends CI_Model
{

    public function __construct()
    {
        parent::__construct();
        $this->load->database();
    }

    /**
     * Obtém informações sobre investigações com base no cliente_id fornecido.
     *
     * Esta função retorna um array contendo informações sobre investigações com base
     * no cliente_id fornecido. Retorna o array de objetos se houver resultados,
     * caso contrário, retorna false.
     *
     * @param int|null $cliente_id ID do cliente relacionado às investigações (opcional).
     *
     * @return array|bool Retorna um array de objetos com informações sobre as investigações se houver
     *                     resultados, caso contrário, retorna false.
     */
    public function getinvestigacoes($cliente_id = null)
    {
        // Seleciona os campos relevantes da tabela 'investigacao'
        $this->db->select('id, cliente_id, nome, descricao, dt_criacao, dt_alteracao, status');
        $this->db->from('investigacao');

        // Filtra por cliente_id, se fornecido
        if ($cliente_id !== null) {
            $this->db->where('cliente_id', $cliente_id);
        }

        // Executa a consulta no banco de dados e retorna o array de objetos ou false
        return $this->db->get()->result() ?: false;
    }

    /**
     * Obtém informações sobre uma investigação específica.
     *
     * Esta função retorna um objeto contendo informações sobre uma investigação específica
     * com base no ID e, opcionalmente, no cliente_id fornecidos. Retorna o objeto se houver
     * resultados, caso contrário, retorna false.
     *
     * @param int $id ID da investigação a ser recuperada.
     * @param int|null $cliente_id ID do cliente relacionado à investigação (opcional).
     *
     * @return object|bool Retorna um objeto com informações sobre a investigação se houver resultados,
     *                     caso contrário, retorna false.
     */
    public function getInvestigacao($id, $cliente_id = null)
    {
        // Seleciona os campos relevantes da tabela 'investigacao'
        $this->db->select('id, cliente_id, nome, descricao, dt_criacao, dt_alteracao, status');
        $this->db->from('investigacao');

        // Filtra por cliente_id, se fornecido
        if ($cliente_id !== null) {
            $this->db->where('cliente_id', $cliente_id);
        }

        // Filtra por ID da investigação
        $this->db->where('id', $id);

        // Executa a consulta no banco de dados e retorna o objeto ou false
        return $this->db->get()->row() ?: false;
    }

    /**
     * Cria uma nova investigação.
     *
     * Esta função insere os dados fornecidos na tabela 'investigacao'. Retorna
     * true se a inserção for bem-sucedida, caso contrário, retorna false.
     *
     * @param array $dados Dados a serem inseridos na tabela.
     *
     * @return bool Retorna true se a inserção for bem-sucedida, caso contrário, retorna false.
     */
    public function createInvestigacao($dados)
    {
        // Insere os dados na tabela 'investigacao' e retorna o resultado
        return $this->db->insert('investigacao', $dados);
    }

    /**
     * Atualiza os dados de uma investigação específica.
     *
     * Esta função atualiza os dados na tabela 'investigacao' com base no ID e,
     * opcionalmente, no cliente_id fornecidos. Retorna true se a atualização for bem-sucedida,
     * caso contrário, retorna false.
     *
     * @param array $dados Novos dados a serem atualizados.
     * @param int $id ID da investigação a ser atualizada.
     * @param int|null $cliente_id ID do cliente relacionado à investigação (opcional).
     *
     * @return bool Retorna true se a atualização for bem-sucedida, caso contrário, retorna false.
     */
    public function updateInvestigacao($dados, $id, $cliente_id = null)
    {
        // Configura as condições de atualização (ID e, opcionalmente, cliente_id)
        $where = ['id' => $id];
        if ($cliente_id !== null) {
            $where['cliente_id'] = $cliente_id;
        }

        // Atualiza os dados na tabela 'investigacao' e retorna o resultado
        return $this->db->update('investigacao', $dados, $where);
    }

    /**
     * Obtém informações sobre os coordenadores associados a uma investigação específica.
     *
     * Esta função retorna um array contendo informações sobre os coordenadores associados
     * a uma investigação específica, incluindo o ID da investigação, o nome da investigação,
     * a descrição da investigação e uma lista de coordenadores associados.
     *
     * @param int $investigacao_id ID da investigação.
     * @param int|null $cliente_id ID do cliente relacionado à investigação (opcional).
     *
     * @return array|bool Retorna um array com informações sobre os coordenadores associados à investigação
     *                     se houver resultados, caso contrário, retorna false.
     */
    public function getinvestigacoesCoordenadores($investigacao_id, $cliente_id = '')
    {
        $this->db->select('ic.investigacao_id, ic.user_id, i.nome as investigacao_nome, i.descricao as investigacao_descricao, au.first_name, au.last_name')
            ->from('investigacao_coordenador ic')
            ->join('investigacao i', 'ic.investigacao_id = i.id', 'inner')
            ->join('auth_users au', 'ic.user_id = au.id', 'inner')
            ->where('ic.investigacao_id', $investigacao_id)
            ->where('ic.cliente_id', $cliente_id)
            ->group_by('ic.investigacao_id, i.nome, i.descricao, au.first_name, au.last_name, ic.user_id');

        $query = $this->db->get();

        if ($query->num_rows() > 0) {
            $result = array();

            foreach ($query->result_array() as $row) {
                $investigacao_id = $row['investigacao_id'];

                // Adiciona à investigação existente ou cria uma nova
                $result[$investigacao_id] = $result[$investigacao_id] ?? array(
                    'investigacao_id' => $investigacao_id,
                    'investigacao_nome' => $row['investigacao_nome'],
                    'investigacao_descricao' => $row['investigacao_descricao'],
                    'coordenadores' => array()
                );

                // Adiciona coordenadores à lista de coordenadores da investigação
                $result[$investigacao_id]['coordenadores'][] = array(
                    'user_id' => $row['user_id'],
                    'user_name' => $row['first_name'] . ' ' . $row['last_name']
                );
            }

            // Retorna o array final
            return array_values($result);  // Reindexa o array numericamente
        }

        // Retorna false se não houver resultados
        return false;
    }

    /**
     * Obtém informações sobre as investigações associadas a um coordenador específico.
     *
     * Esta função retorna um array contendo informações sobre as investigações associadas
     * a um coordenador específico, incluindo o nome do coordenador, o ID da investigação,
     * o nome da investigação e a descrição da investigação.
     *
     * @param int $user_id ID do coordenador.
     * @param int|null $cliente_id ID do cliente relacionado ao coordenador (opcional).
     *
     * @return array|bool Retorna um array com informações sobre as investigações associadas ao coordenador
     *                     se houver resultados, caso contrário, retorna false.
     */
    public function getInvestigacaoCoordenador($user_id, $cliente_id = null)
    {
        $this->db->select('ic.user_id, i.id as investigacao_id, au.first_name, au.last_name, i.nome as investigacao_nome, i.descricao as investigacao_descricao')
            ->from('investigacao_coordenador ic')
            ->join('investigacao i', 'ic.investigacao_id = i.id', 'inner')
            ->join('auth_users au', 'ic.user_id = au.id', 'inner')
            ->where('ic.user_id', $user_id)
            ->where('ic.cliente_id', $cliente_id)
            ->group_by('ic.user_id, i.id, au.first_name, au.last_name, i.nome, i.descricao');

        $query = $this->db->get();

        if ($query->num_rows() > 0) {
            $result = array();

            foreach ($query->result_array() as $row) {
                $user_id = $row['user_id'];

                // Adiciona ao resultado existente ou cria um novo
                $result[$user_id] = $result[$user_id] ?? array(
                    'user_id' => $user_id,
                    'user_name' => $row['first_name'] . ' ' . $row['last_name'],
                    'investigacoes' => array()
                );

                // Adiciona informações da investigação ao usuário
                $result[$user_id]['investigacoes'][] = array(
                    'investigacao_id' => $row['investigacao_id'],
                    'investigacao_nome' => $row['investigacao_nome'],
                    'investigacao_descricao' => $row['investigacao_descricao'],
                );
            }

            // Retorna o array final
            return array_values($result);  // Reindexa o array numericamente
        }

        // Retorna false se não houver resultados
        return false;
    }

    /**
     * Cria um novo registro na tabela 'investigacao_coordenador'.
     *
     * Esta função insere os dados fornecidos na tabela 'investigacao_coordenador'. Retorna
     * true se a inserção for bem-sucedida, caso contrário, retorna false.
     *
     * @param array $dados Dados a serem inseridos na tabela.
     *
     * @return bool Retorna true se a inserção for bem-sucedida, caso contrário, retorna false.
     */
    public function createInvestigacaoCoordenador($dados)
    {
        // Insere os dados na tabela 'investigacao_coordenador' e retorna o resultado
        return $this->db->insert('investigacao_coordenador', $dados);
    }

    /**
     * Atualiza os dados de um coordenador em uma investigação específica.
     *
     * Esta função atualiza os dados na tabela 'investigacao_coordenador' com base no ID e,
     * opcionalmente, no cliente_id fornecidos. Retorna true se a atualização for bem-sucedida,
     * caso contrário, retorna false.
     *
     * @param array $dados Novos dados a serem atualizados.
     * @param int $id ID do registro a ser atualizado.
     * @param int|null $cliente_id ID do cliente relacionado ao registro (opcional).
     *
     * @return bool Retorna true se a atualização for bem-sucedida, caso contrário, retorna false.
     */
    public function updateInvestigacaoCoordenador($dados, $id, $cliente_id = null)
    {
        // Configura as condições de atualização (ID e, opcionalmente, cliente_id)
        $where = ['id' => $id];
        if ($cliente_id !== null) {
            $where['cliente_id'] = $cliente_id;
        }

        // Atualiza os dados na tabela 'investigacao_coordenador' e retorna o resultado
        return $this->db->update('investigacao_coordenador', $dados, $where);
    }

    /**
     * Exclui a associação de um coordenador a uma investigação específica.
     *
     * Esta função exclui uma entrada na tabela 'investigacao_coordenador' com base nos parâmetros
     * fornecidos. Retorna true se a exclusão for bem-sucedida, caso contrário, retorna false.
     *
     * @param int $user_id ID do coordenador a ser removido da investigação.
     * @param int $investigacao_id ID da investigação.
     * @param int|null $cliente_id ID do cliente relacionado à investigação (opcional).
     *
     * @return bool Retorna true se a exclusão for bem-sucedida, caso contrário, retorna false.
     */
    public function deleteInvestigacaoCoordenador($user_id, $investigacao_id, $cliente_id = '')
    {
        // Condições para a exclusão
        $condicoes = array(
            'cliente_id' => $cliente_id,
            'user_id' => $user_id,
            'investigacao_id' => $investigacao_id
        );

        // Executa a exclusão e retorna o resultado
        return $this->db->delete('investigacao_coordenador', $condicoes);
    }

    /**
     * Obtém informações sobre os usuários associados a uma investigação específica.
     *
     * Esta função retorna um array contendo informações sobre os usuários associados
     * a uma investigação específica, incluindo o ID da investigação, o nome da investigação,
     * a descrição da investigação e uma lista de usuários associados.
     *
     * @param int $investigacao_id ID da investigação.
     * @param int|null $cliente_id ID do cliente relacionado à investigação (opcional).
     *
     * @return array|bool Retorna um array com informações sobre os usuários associados à investigação
     *                     se houver resultados, caso contrário, retorna false.
     */
    public function getInvestigacaoUsers($investigacao_id, $cliente_id = null)
    {
        $this->db->select('iu.investigacao_id, au.id as user_id, au.first_name, au.last_name, i.nome as investigacao_nome, i.descricao as investigacao_descricao')
            ->from('investigacao_user iu')
            ->join('auth_users au', 'iu.user_id = au.id', 'inner')
            ->join('investigacao i', 'iu.investigacao_id = i.id', 'inner')
            ->where('iu.investigacao_id', $investigacao_id)
            ->where('iu.cliente_id', $cliente_id)
            ->group_by('iu.investigacao_id, au.id, i.nome, i.descricao');

        $query = $this->db->get();

        if ($query->num_rows() > 0) {
            $result = array();

            foreach ($query->result_array() as $row) {
                $investigacao_id = $row['investigacao_id'];

                // Adiciona à investigação existente ou cria uma nova
                $result[$investigacao_id] = $result[$investigacao_id] ?? array(
                    'investigacao_id' => $investigacao_id,
                    'investigacao_nome' => $row['investigacao_nome'],
                    'investigacao_descricao' => $row['investigacao_descricao'],
                    'usuarios' => array()
                );

                // Adiciona usuário à lista de usuários da investigação
                $result[$investigacao_id]['usuarios'][] = array(
                    'user_id' => $row['user_id'],
                    'user_name' => $row['first_name'] . ' ' . $row['last_name']
                );
            }

            // Retorna o array final
            return array_values($result);  // Reindexa o array numericamente
        }

        // Retorna false se não houver resultados
        return false;
    }

    /**
     * Obtém informações sobre as investigações associadas a um usuário específico.
     *
     * Esta função retorna um array contendo informações sobre as investigações associadas
     * a um usuário específico, incluindo o nome do usuário, o ID da investigação, o nome
     * da investigação e a descrição da investigação.
     *
     * @param int $user_id ID do usuário.
     * @param int|null $cliente_id ID do cliente relacionado ao usuário (opcional).
     *
     * @return array|bool Retorna um array com informações sobre as investigações associadas ao usuário
     *                     se houver resultados, caso contrário, retorna false.
     */
    public function getInvestigacaoUser($user_id, $cliente_id = null)
    {
        $this->db->select('iu.user_id, iu.investigacao_id, au.first_name, au.last_name, i.nome as investigacao_nome, i.descricao as investigacao_descricao')
            ->from('investigacao_user iu')
            ->join('investigacao i', 'iu.investigacao_id = i.id', 'inner')
            ->join('auth_users au', 'iu.user_id = au.id', 'inner')
            ->where('iu.user_id', $user_id)
            ->where('iu.cliente_id', $cliente_id)
            ->group_by('iu.user_id, iu.investigacao_id, au.first_name, au.last_name, i.nome, i.descricao');

        $query = $this->db->get();

        if ($query->num_rows() > 0) {
            $result = array();

            foreach ($query->result_array() as $row) {
                $user_id = $row['user_id'];

                // Adiciona ao usuário existente ou cria um novo
                $result[$user_id] = $result[$user_id] ?? array(
                    'user_id' => $user_id,
                    'user_name' => $row['first_name'] . ' ' . $row['last_name'],
                    'investigacoes' => array()
                );

                // Adiciona investigação à lista de investigações do usuário
                $result[$user_id]['investigacoes'][] = array(
                    'investigacao_id' => $row['investigacao_id'],
                    'investigacao_nome' => $row['investigacao_nome'],
                    'investigacao_descricao' => $row['investigacao_descricao']
                );
            }

            // Retorna o array final
            return array_values($result);  // Reindexa o array numericamente
        }

        // Retorna false se não houver resultados
        return false;
    }

    /**
     * Cria um novo registro na tabela 'investigacao_user'.
     *
     * Esta função insere os dados fornecidos na tabela 'investigacao_user'. Retorna
     * true se a inserção for bem-sucedida, caso contrário, retorna false.
     *
     * @param array $dados Dados a serem inseridos na tabela.
     *
     * @return bool Retorna true se a inserção for bem-sucedida, caso contrário, retorna false.
     */
    public function createInvestigacaoUser($dados)
    {
        // Insere os dados na tabela 'investigacao_user' e retorna o resultado
        return $this->db->insert('investigacao_user', $dados);
    }

    /**
     * Atualiza os dados de um usuário em uma investigação específica.
     *
     * Esta função atualiza os dados na tabela 'investigacao_user' com base no ID e,
     * opcionalmente, no cliente_id fornecidos. Retorna true se a atualização for bem-sucedida,
     * caso contrário, retorna false.
     *
     * @param array $dados Novos dados a serem atualizados.
     * @param int $id ID do registro a ser atualizado.
     * @param int|null $cliente_id ID do cliente relacionado ao registro (opcional).
     *
     * @return bool Retorna true se a atualização for bem-sucedida, caso contrário, retorna false.
     */
    public function updateInvestigacaoUser($dados, $id, $cliente_id = null)
    {
        // Configura as condições de atualização (ID e, opcionalmente, cliente_id)
        $where = ['id' => $id];
        if ($cliente_id !== null) {
            $where['cliente_id'] = $cliente_id;
        }

        // Atualiza os dados na tabela 'investigacao_user' e retorna o resultado
        return $this->db->update('investigacao_user', $dados, $where);
    }

    /**
     * Exclui a associação de um usuário a uma investigação específica.
     *
     * Esta função exclui uma entrada na tabela investigacao_user com base nos parâmetros
     * fornecidos. Retorna true se a exclusão for bem-sucedida, caso contrário, retorna false.
     *
     * @param int $user_id ID do usuário a ser removido da investigação.
     * @param int $investigacao_id ID da investigação.
     * @param int $cliente_id ID do cliente relacionado à investigação (opcional).
     *
     * @return bool Retorna true se a exclusão for bem-sucedida, caso contrário, retorna false.
     */
    public function deleteInvestigacaoUser($user_id, $investigacao_id, $cliente_id = '')
    {
        // Condições para a exclusão
        $condicoes = array(
            'cliente_id' => $cliente_id,
            'user_id' => $user_id,
            'investigacao_id' => $investigacao_id
        );

        // Executa a exclusão e retorna o resultado
        return $this->db->delete('investigacao_user', $condicoes);
    }

    /**
     * Verifica se um usuário está associado a uma investigação específica.
     *
     * Esta função realiza uma consulta para determinar se um usuário está envolvido
     * em uma investigação específica como coordenador ou usuário. Retorna o ID do usuário
     * se ele estiver associado, caso contrário, retorna false.
     *
     * @param int $user_id ID do usuário a ser verificado.
     * @param int $investigacao_id ID da investigação a ser verificada.
     * @param int $cliente_id ID do cliente relacionado à investigação.
     *
     * @return mixed Retorna o ID do usuário se associado à investigação, caso contrário, retorna false.
     */
    public function userInInvestigacao($user_id, $investigacao_id, $cliente_id)
    {
        $this->db->select('COALESCE(ic.user_id, iu.user_id) AS user_id');
        $this->db->from('investigacao_coordenador ic');
        $this->db->join('investigacao_user iu', 'iu.cliente_id = ic.cliente_id AND iu.user_id = ic.user_id', 'FULL OUTER');
        $this->db->where("(ic.cliente_id = {$cliente_id} AND ic.investigacao_id = {$investigacao_id} AND ic.user_id = {$user_id}) OR (iu.cliente_id = {$cliente_id} AND iu.investigacao_id = {$investigacao_id} AND iu.user_id = {$user_id})");

        $query = $this->db->get();

        if ($query->num_rows() > 0) {
            return $query->row()->user_id;
        } else {
            return false;
        }
    }

    /**
     * Obtém todas as investigações associadas a um cliente e usuário específicos.
     *
     * Esta função executa uma consulta SQL personalizada para recuperar informações
     * sobre investigações em que o usuário é coordenador ou usuário, filtradas por
     * cliente_id e user_id. A função retorna um array de objetos contendo detalhes
     * da investigação se houver resultados, caso contrário, retorna false.
     *
     * @param int $cliente_id ID do cliente.
     * @param int $user_id ID do usuário.
     *
     * @return mixed Retorna um array de objetos com detalhes da investigação se houver
     *               resultados, caso contrário, retorna false.
     */
    public function getAllInvestigacoesInUser($user_id, $cliente_id, $status = null)
    {
        $this->db->select('ic.cliente_id, ic.user_id, ic.investigacao_id, i.nome, i.descricao, i.dt_criacao, i.dt_alteracao, i.status');
        $this->db->from('investigacao_coordenador ic');
        $this->db->join('investigacao i', 'ic.investigacao_id = i.id AND ic.cliente_id = i.cliente_id', 'inner');
        $this->db->where('ic.cliente_id', $cliente_id);
        $this->db->where('ic.user_id', $user_id);
        
        if(!is_null($status)){
            $this->db->where('i.status', $status);
        }

        $subquery = $this->db->get_compiled_select(); //Obtendo a subconsulta para uso no UNION

        $this->db->reset_query(); // Resetando a query

        $this->db->select('iu.cliente_id, iu.user_id, iu.investigacao_id, i.nome, i.descricao, i.dt_criacao, i.dt_alteracao, i.status');
        $this->db->from('investigacao_user iu');
        $this->db->join('investigacao i', 'iu.investigacao_id = i.id AND iu.cliente_id = i.cliente_id', 'inner');
        $this->db->where('iu.cliente_id', $cliente_id);
        $this->db->where('iu.user_id', $user_id);

        if(!is_null($status)){
            $this->db->where('i.status', $status);
        }

        $union_query = $this->db->get_compiled_select(); //Obtendo a segunda subconsulta para uso no UNION

        $query = $this->db->query($subquery . ' UNION ' . $union_query);

        // Verifica se a consulta retornou algum resultado
        if ($query->num_rows() > 0) {
            $result = $query->result();
        } else {
            $result = false;
        }

        return $result;
    }
}
