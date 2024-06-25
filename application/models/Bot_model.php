<?php
class Bot_model extends CI_Model
{

    public function __construct()
    {
        parent::__construct();
        $this->load->database();
    }

    /**
     * Obtém a lista de bots a partir do banco de dados.
     * 
     * @return mixed Retorna um array de objetos contendo os detalhes dos bots ou FALSE em caso de falha.
     */
    public function getBots()
    {
        $this->db->select('id, descricao, dt_criacao, dt_alteracao, status');
        $this->db->from('bot');

        // Executa a consulta no banco de dados
        $query = $this->db->get();

        // Verifica se a consulta foi bem-sucedida
        if ($query) {
            $result = $query->result();
            return $result;
        } else {
            return FALSE;
        }
    }

    /**
     * Obtém as credenciais do bot a partir do banco de dados.
     * 
     * @param int|null $id_cliente Opcional. ID do cliente para filtrar as credenciais.
     * @return mixed Retorna um array de objetos contendo as credenciais do bot ou FALSE em caso de falha.
     */
    public function getCredenciais($id_cliente = null)
    {
        $this->db->select('bc.id, bc.id_cliente, bc.id_bot, b.descricao AS descricao_bot, bc.descricao, bc.username, bc.password, bc.email, bc.dt_criacao, bc.dt_alteracao, bc.status');
        $this->db->from('bot_credencial bc');
        $this->db->join('bot b', 'bc.id_bot = b.id');

        // Adiciona condição de filtro pelo ID do cliente, se fornecido
        if ($id_cliente != null) {
            $this->db->where('bc.id_cliente', $id_cliente);
        }

        // Executa a consulta no banco de dados
        $query = $this->db->get();

        // Verifica se a consulta foi bem-sucedida
        if ($query) {
            $result = $query->result();
            return $result;
        } else {
            return FALSE;
        }
    }

    public function getCredenciaisBot($id, $status = null, $id_cliente = null)
    {
        $this->db->select('id, id_cliente, id_bot, descricao, username, "password", email, dt_criacao, dt_alteracao, status');
        $this->db->from('bot_credencial');
        $this->db->where('id_bot', $id);

        if (is_bool($status)) {
            $this->db->where('status', $status);
        }

        // Adiciona condição de filtro pelo ID do cliente, se fornecido
        if ($id_cliente != null) {
            $this->db->where('id_cliente', $id_cliente);
        }

        // Executa a consulta no banco de dados
        $query = $this->db->get();

        // Verifica se a consulta foi bem-sucedida
        if ($query) {
            $result = $query->result();
            return $result;
        } else {
            return FALSE;
        }
    }

    /**
     * Obtém uma credencial específica do bot a partir do banco de dados.
     * 
     * @param int $id ID da credencial a ser obtida.
     * @param int|null $id_cliente Opcional. ID do cliente para verificar a associação da credencial.
     * @return mixed Retorna um objeto contendo os detalhes da credencial ou FALSE em caso de falha.
     */
    public function getCredencial($id, $id_cliente = null)
    {
        $this->db->select('id, id_cliente, id_bot, descricao, username, "password", email, dt_criacao, dt_alteracao, status');
        $this->db->from('bot_credencial');
        $this->db->where('id', $id);

        // Adiciona condição de filtro pelo ID do cliente, se fornecido
        if ($id_cliente != null) {
            $this->db->where('id_cliente', $id_cliente);
        }

        // Executa a consulta no banco de dados
        $query = $this->db->get();

        // Verifica se a consulta foi bem-sucedida
        if ($query) {
            $result = $query->row();
            return $result;
        } else {
            return FALSE;
        }
    }

    /**
     * Cria uma nova credencial no banco de dados.
     * 
     * @param array $dados Array contendo os dados da credencial a serem inseridos.
     * @return bool Retorna true se a operação for bem-sucedida, false caso contrário.
     */
    public function createCredencial($dados)
    {
        // Insere os dados na tabela 'bot_credencial'
        $this->db->insert('bot_credencial', $dados);

        // Verifica se a operação afetou alguma linha no banco de dados
        if ($this->db->affected_rows() > 0) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * Atualiza os dados de uma credencial no banco de dados.
     * 
     * @param array $dados Array contendo os novos dados da credencial.
     * @param int $id ID da credencial a ser atualizada.
     * @return bool Retorna true se a operação for bem-sucedida, false caso contrário.
     */
    public function updateCredencial($dados, $id, $id_cliente = null)
    {
        $where['id'] = $id;

        if ($id_cliente != null) {
            $where['id_cliente'] = $id_cliente;
        }

        // Atualiza os dados na tabela 'bot_credencial' onde o ID corresponde ao fornecido
        $this->db->update('bot_credencial', $dados, ['id' => $id]);

        // Verifica se a operação afetou alguma linha no banco de dados
        if ($this->db->affected_rows() > 0) {
            return true;
        } else {
            return false;
        }
    }


    public function getPesquisasAvulsas($id_usuario = null, $id_cliente = null)
    {
        $this->db->distinct();
        $this->db->select(
            'bpa.id,
            bpa.id_bot,
            bpa.id_cliente,
            bpa.id_credencial,
            bpa."id_usuário",
            bpa.investigacao_id,
            bpa.executado,
            bpa.agendado,
            bpa.erro,
            bpa.executando,
            b.descricao AS descricao_bot,
            bc.descricao AS descricao_credencial,
            i.descricao AS descricao_investigacao,
            bpa.descricao AS descricao_pesquisa,
            c.razao_social AS cliente_razao_social,
            c.nome_fantasia AS cliente_nome_fantasia,
            au.first_name AS user_first_name,
            au.last_name AS user_last_name,
            bpa.palavra_chave,
            bpa.filtrar_por,
            bpa.dt_pesquisa,
            bpa.dt_executado'
        );
        $this->db->from('bot_pesquisa_avulsa bpa');
        $this->db->join('bot b', 'b.id = bpa.id_bot');
        $this->db->join('bot_credencial bc', 'bc.id = bpa.id_credencial');
        $this->db->join('investigacao i', 'i.id = bpa.investigacao_id');
        $this->db->join('cliente c', 'c.id = bpa.id_cliente');
        $this->db->join('auth_users au', 'au.id = bpa.id_usuário');
        $this->db->where('bpa.id_usuário', $id_usuario);
        $this->db->where('bpa.id_cliente', $id_cliente);
        $this->db->where("(EXISTS (SELECT 1 FROM investigacao_coordenador ic WHERE ic.user_id = bpa.id_usuário) OR EXISTS (SELECT 1 FROM investigacao_user iu WHERE iu.user_id = bpa.id_usuário))");

        // Executa a consulta no banco de dados
        $query = $this->db->get();

        // Verifica se a consulta foi bem-sucedida
        if ($query) {
            $result = $query->result();
            return $result;
        } else {
            return FALSE;
        }
    }


    public function getPesquisasAvulsasRetornoBot($id_pesquisa_avulsa, $id_cliente = null)
    {
        $this->db->select('bp.id,
                        bp.id_bot,
                        bp.id_cliente,
                        bp.id_usuario,
                        bp.id_credencial,
                        bp.investigacao_id,
                        bp.id_pesquisa_avulsa,
                        b.descricao as descricao_bot,
                        bc.descricao as descricao_credencial,
                        i.nome as nome_investigacao,
                        au.first_name as user_first_name,
                        au.last_name as user_last_name,
                        bp.palavra_chave,
                        bp.filtrar_por,
                        bp.header,
                        bp.usuario_publicacao,
                        bp.link_usuario,
                        bp.link_publicacao,
                        bp.body,
                        bp.dt_pesquisa');
        $this->db->from('bot_pesquisa bp');
        $this->db->join('bot b', 'b.id = bp.id_bot');
        $this->db->join('auth_users au', 'au.id = bp.id_usuario');
        $this->db->join('bot_credencial bc', 'bc.id = bp.id_credencial');
        $this->db->join('investigacao i', 'i.id = bp.investigacao_id');
        $this->db->where('bp.id_pesquisa_avulsa', $id_pesquisa_avulsa);

        // Adiciona condição de filtro pelo ID do cliente, se fornecido
        if ($id_cliente != null) {
            $this->db->where('bp.id_cliente', $id_cliente);
        }

        // Executa a consulta no banco de dados
        $query = $this->db->get();

        // Verifica se a consulta foi bem-sucedida
        if ($query) {
            $result = $query->result();
            return $result;
        } else {
            return FALSE;
        }
    }

    public function getPesquisasAvulsasCredencial($id_credencial = null, $id_cliente = null)
    {
        $this->db->select('id, id_bot, id_cliente, id_usuario, id_credencial, investigacao_id, id_pesquisa_avulsa, palavra_chave, filtrar_por, header, usuario_publicacao, link_usuario, link_publicacao, body, dt_pesquisa');
        $this->db->from('bot_pesquisa');

        // Adiciona condição de filtro pelo ID do cliente, se fornecido
        if ($id_credencial != null) {
            $this->db->where('id_credencial', $id_credencial);
        }

        // Adiciona condição de filtro pelo ID do cliente, se fornecido
        if ($id_cliente != null) {
            $this->db->where('id_cliente', $id_cliente);
        }

        // Executa a consulta no banco de dados
        $query = $this->db->get();

        // Verifica se a consulta foi bem-sucedida
        if ($query) {
            $result = $query->result();
            return $result;
        } else {
            return FALSE;
        }
    }

    public function getPesquisasAvulsasBot($id_bot = null, $id_cliente = null)
    {
        $this->db->select('id, id_bot, id_cliente, id_usuario, id_credencial, investigacao_id, id_pesquisa_avulsa, palavra_chave, filtrar_por, header, usuario_publicacao, link_usuario, link_publicacao, body, dt_pesquisa');
        $this->db->from('bot_pesquisa');

        // Adiciona condição de filtro pelo ID do cliente, se fornecido
        if ($id_bot != null) {
            $this->db->where('id_bot', $id_bot);
        }

        // Adiciona condição de filtro pelo ID do cliente, se fornecido
        if ($id_cliente != null) {
            $this->db->where('id_cliente', $id_cliente);
        }

        // Executa a consulta no banco de dados
        $query = $this->db->get();

        // Verifica se a consulta foi bem-sucedida
        if ($query) {
            $result = $query->result();
            return $result;
        } else {
            return FALSE;
        }
    }

    public function getPesquisaAvulsa($id, $id_cliente = null)
    {
        $this->db->select('id, id_bot, id_cliente, id_usuario, id_credencial, investigacao_id, id_pesquisa_avulsa, palavra_chave, filtrar_por, header, usuario_publicacao, link_usuario, link_publicacao, body, dt_pesquisa');
        $this->db->from('bot_pesquisa');
        $this->db->where('id', $id);

        // Adiciona condição de filtro pelo ID do cliente, se fornecido
        if ($id_cliente != null) {
            $this->db->where('id_cliente', $id_cliente);
        }

        // Executa a consulta no banco de dados
        $query = $this->db->get();

        // Verifica se a consulta foi bem-sucedida
        if ($query) {
            $result = $query->row();
            return $result;
        } else {
            return FALSE;
        }
    }

    public function createPesquisaAvulsa($dados)
    {
        // Insere os dados na tabela 'bot_credencial'
        $this->db->insert('bot_pesquisa_avulsa', $dados);

        // Verifica se a operação afetou alguma linha no banco de dados
        if ($this->db->affected_rows() > 0) {
            return true;
        } else {
            return false;
        }
    }

    public function updatePesquisaAvulsa($dados, $id, $id_cliente = null)
    {
        $where['id'] = $id;

        if ($id_cliente != null) {
            $where['id_cliente'] = $id_cliente;
        }

        // Atualiza os dados na tabela 'bot_credencial' onde o ID corresponde ao fornecido
        $this->db->update('bot_pesquisa_avulsa', $dados, $where);

        // Verifica se a operação afetou alguma linha no banco de dados
        if ($this->db->affected_rows() > 0) {
            return true;
        } else {
            return false;
        }
    }


    public function createRotina($dados)
    {
        // Insere os dados na tabela 'bot_credencial'
        $this->db->insert('bot_rotina', $dados);

        // Verifica se a operação afetou alguma linha no banco de dados
        if ($this->db->affected_rows() > 0) {
            return true;
        } else {
            return false;
        }
    }

    public function updateRotina($dados, $id, $id_cliente = null)
    {
        $where['id'] = $id;

        if ($id_cliente != null) {
            $where['id_cliente'] = $id_cliente;
        }

        // Atualiza os dados na tabela 'bot_credencial' onde o ID corresponde ao fornecido
        $this->db->update('bot_rotina', $dados, $where);

        // Verifica se a operação afetou alguma linha no banco de dados
        if ($this->db->affected_rows() > 0) {
            return true;
        } else {
            return false;
        }
    }

    public function getRotinas($id_cliente = null, $status = null)
    {
        $this->db->select('br.id, br.id_bot, br.id_cliente, br.id_credencial, br.investigacao_id, c.razao_social as cliente_razao_social, c.nome_fantasia as cliente_nome_fantasia, b.descricao as bot_descricao, bc.descricao as credencial_descricao, i.nome as investigacao_nome, i.descricao as investigacao_descricao, br.descricao as rotina_descricao, br.palavra_chave, br.filtrar_por, br.dt_criacao, br.dt_alteracao, TO_CHAR(br.horario, \'HH24:MI\') AS horario, br.dt_inicio, br.dt_fim, br.status');
        $this->db->from('bot_rotina br');
        $this->db->join('bot b', 'b.id = br.id_bot');
        $this->db->join('cliente c', 'c.id = br.id_cliente');
        $this->db->join('bot_credencial bc', 'bc.id = br.id_credencial');
        $this->db->join('investigacao i', 'i.id = br.investigacao_id');

        if (!is_null($id_cliente)) {
            $this->db->where('br.id_cliente', $id_cliente);
        }

        if (!is_null($status)) {
            $this->db->where('br.status', $status);
        }

        $query = $this->db->get();

        if ($query->num_rows() > 0) {
            return $query->result();
        } else {
            return false;
        }
    }

    public function getRotinasCredencial($id_credencial = null, $id_cliente = null)
    {
        $this->db->select('br.id, br.id_bot, br.id_cliente, br.id_credencial, br.investigacao_id, c.razao_social as cliente_razao_social, c.nome_fantasia as cliente_nome_fantasia, b.descricao as bot_descricao, bc.descricao as credencial_descricao, i.nome as investigacao_nome, i.descricao as investigacao_descricao, br.descricao as rotina_descricao, br.palavra_chave, br.filtrar_por, br.dt_criacao, br.dt_alteracao, TO_CHAR(br.horario, \'HH24:MI\') AS horario, br.dt_inicio, br.dt_fim, br.status');
        $this->db->from('bot_rotina br');
        $this->db->join('bot b', 'b.id = br.id_bot');
        $this->db->join('cliente c', 'c.id = br.id_cliente');
        $this->db->join('bot_credencial bc', 'bc.id = br.id_credencial');
        $this->db->join('investigacao i', 'i.id = br.investigacao_id');

        // Adiciona condição de filtro pelo ID do cliente, se fornecido
        if ($id_credencial != null) {
            $this->db->where('br.id_credencial', $id_credencial);
        }

        // Adiciona condição de filtro pelo ID do cliente, se fornecido
        if ($id_cliente != null) {
            $this->db->where('br.id_cliente', $id_cliente);
        }

        // Executa a consulta no banco de dados
        $query = $this->db->get();

        // Verifica se a consulta foi bem-sucedida
        if ($query) {
            $result = $query->result();
            return $result;
        } else {
            return FALSE;
        }
    }

    public function getRotinasBot($id_bot = null, $id_cliente = null)
    {
        $this->db->select('br.id, br.id_bot, br.id_cliente, br.id_credencial, br.investigacao_id, c.razao_social as cliente_razao_social, c.nome_fantasia as cliente_nome_fantasia, b.descricao as bot_descricao, bc.descricao as credencial_descricao, i.nome as investigacao_nome, i.descricao as investigacao_descricao, br.descricao as rotina_descricao, br.palavra_chave, br.filtrar_por, br.dt_criacao, br.dt_alteracao, TO_CHAR(br.horario, \'HH24:MI\') AS horario, br.dt_inicio, br.dt_fim, br.status');
        $this->db->from('bot_rotina br');
        $this->db->join('bot b', 'b.id = br.id_bot');
        $this->db->join('cliente c', 'c.id = br.id_cliente');
        $this->db->join('bot_credencial bc', 'bc.id = br.id_credencial');
        $this->db->join('investigacao i', 'i.id = br.investigacao_id');

        // Adiciona condição de filtro pelo ID do cliente, se fornecido
        if ($id_bot != null) {
            $this->db->where('br.id_bot', $id_bot);
        }

        // Adiciona condição de filtro pelo ID do cliente, se fornecido
        if ($id_cliente != null) {
            $this->db->where('br.id_cliente', $id_cliente);
        }

        // Executa a consulta no banco de dados
        $query = $this->db->get();

        // Verifica se a consulta foi bem-sucedida
        if ($query) {
            $result = $query->result();
            return $result;
        } else {
            return FALSE;
        }
    }

    public function getRotina($id, $id_cliente = null)
    {
        $this->db->select('br.id, br.id_bot, br.id_cliente, br.id_credencial, br.investigacao_id, c.razao_social as cliente_razao_social, c.nome_fantasia as cliente_nome_fantasia, b.descricao as bot_descricao, bc.descricao as credencial_descricao, i.nome as investigacao_nome, i.descricao as investigacao_descricao, br.descricao as rotina_descricao, br.palavra_chave, br.filtrar_por, br.dt_criacao, br.dt_alteracao, TO_CHAR(br.horario, \'HH24:MI\') AS horario, br.dt_inicio, br.dt_fim, br.status');
        $this->db->from('bot_rotina br');
        $this->db->join('bot b', 'b.id = br.id_bot');
        $this->db->join('cliente c', 'c.id = br.id_cliente');
        $this->db->join('bot_credencial bc', 'bc.id = br.id_credencial');
        $this->db->join('investigacao i', 'i.id = br.investigacao_id');
        $this->db->where('br.id', $id);

        // Adiciona condição de filtro pelo ID do cliente, se fornecido
        if ($id_cliente != null) {
            $this->db->where('br.id_cliente', $id_cliente);
        }

        // Executa a consulta no banco de dados
        $query = $this->db->get();

        // Verifica se a consulta foi bem-sucedida
        if ($query) {
            $result = $query->row();
            return $result;
        } else {
            return FALSE;
        }
    }
}
