<?php

class Bot_adm_model extends CI_Model
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

    public function getClientesAtivos()
    {
        $this->db->select('c.id, c.cnpj, c.razao_social, c.nome_fantasia, c.dt_criacao, c.dt_alteracao, c.status');
        $this->db->from('cliente c');
        $this->db->join('cliente_licenca cl', 'cl.id_cliente = c.id');
        $this->db->where('cl.dt_validade >', 'NOW()', false);
        $this->db->where('c.status', true);

        $query = $this->db->get();

        if ($query->num_rows() > 0) {
            return $query->result();
        } else {
            return false;
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

        $this->db->select('id, id_cliente, id_bot, descricao, username, "password", email, dt_criacao, dt_alteracao, status');

        $this->db->from('bot_credencial');



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



    public function getPesquisasAvulsas($id_cliente = null)
    {
        $this->db->select('id, id_bot, id_cliente, id_credencial, agendado, executando, executado, erro, descricao, palavra_chave, filtrar_por, dt_pesquisa, dt_executado, investigacao_id');
        $this->db->from('bot_pesquisa_avulsa');
        $this->db->where('agendado', true);
        $this->db->where('executado', false);
        $this->db->where('erro', false);
        $this->db->where('executando', false);

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



    public function getPesquisasAvulsasCredencial($id_credencial = null, $id_cliente = null)
    {

        $this->db->select('id, id_bot, id_cliente, id_credencial, agendado, executado, erro, descricao, palavra_chave, filtrar_por, dt_pesquisa, dt_executado, investigacao_id');

        $this->db->from('bot_pesquisa_avulsa');



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

        $this->db->select('id, id_bot, id_cliente, id_credencial, agendado, executado, erro, descricao, palavra_chave, filtrar_por, dt_pesquisa, dt_executado, investigacao_id');

        $this->db->from('bot_pesquisa_avulsa');



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

        $this->db->select('id, id_bot, id_cliente, id_credencial, agendado, executado, erro, descricao, palavra_chave, filtrar_por, dt_pesquisa, dt_executado, investigacao_id');

        $this->db->from('bot_pesquisa_avulsa');

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



        // Atualiza os dados na tabela 'bot_credencial' onde o ID corresponde ao fornecido

        $this->db->update('bot_pesquisa_avulsa', $dados, ['id' => $id]);



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



    public function getRotinas($id_cliente = null)
    {

        $this->db->select("id, id_bot, id_cliente, id_credencial, investigacao_id, status, descricao");

        $this->db->select("TO_CHAR(horario, 'HH24:MI') AS horario");

        $this->db->select("palavra_chave, filtrar_por, dt_inicio, dt_fim, dt_criacao, dt_alteracao");

        $this->db->from('bot_rotina');



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



    public function getRotinasCredencial($id_credencial = null, $id_cliente = null)
    {

        $this->db->select("id, id_bot, id_cliente, id_credencial, investigacao_id, status, descricao");

        $this->db->select("TO_CHAR(horario, 'HH24:MI') AS horario");

        $this->db->select("palavra_chave, filtrar_por, dt_inicio, dt_fim, dt_criacao, dt_alteracao");

        $this->db->from('bot_rotina');



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



    public function getRotinasBot($id_bot = null, $id_cliente = null)
    {

        $this->db->select("id, id_bot, id_cliente, id_credencial, investigacao_id, status, descricao");

        $this->db->select("TO_CHAR(horario, 'HH24:MI') AS horario");

        $this->db->select("palavra_chave, filtrar_por, dt_inicio, dt_fim, dt_criacao, dt_alteracao");

        $this->db->from('bot_rotina');



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



    public function getRotina($id, $id_cliente = null)
    {

        $this->db->select("id, id_bot, id_cliente, id_credencial, investigacao_id, status, descricao");

        $this->db->select("TO_CHAR(horario, 'HH24:MI') AS horario");

        $this->db->select("palavra_chave, filtrar_por, dt_inicio, dt_fim, dt_criacao, dt_alteracao");

        $this->db->from('bot_rotina');

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



}

