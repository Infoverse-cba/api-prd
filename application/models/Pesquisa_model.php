<?php
class Pesquisa_model extends CI_Model
{

    protected $CI;

    public function __construct()
    {
        parent::__construct();
        $this->load->database();
    }

    public function getPesquisas($id_cliente = null)
    {
        $this->db->select('id, id_bot, id_cliente, id_usuário, id_pesquisa_avulsa, investigacao_id, id_credencial, id_usuario, palavra_chave, filtrar_por, header, usuario_publicacao, link_usuario, link_publicacao, body, dt_pesquisa');
        $this->db->from('bot_pesquisa');

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

    public function getPesquisasCredencial($id_credencial = null, $id_cliente = null)
    {
        $this->db->select('id, id_bot, id_cliente, id_pesquisa_avulsa, investigacao_id, id_credencial, id_usuario, palavra_chave, filtrar_por, header, usuario_publicacao, link_usuario, link_publicacao, body, dt_pesquisa');
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

    public function getPesquisasBot($id_bot = null, $id_cliente = null)
    {
        $this->db->select('id, id_bot, id_cliente, id_pesquisa_avulsa, investigacao_id, id_credencial, id_usuario, palavra_chave, filtrar_por, header, usuario_publicacao, link_usuario, link_publicacao, body, dt_pesquisa');
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

    public function getPesquisasInvestigacao($investigacao_id = null, $id_cliente = null)
    {
        $this->db->select('id, id_bot, id_cliente, id_pesquisa_avulsa, investigacao_id, id_credencial, id_usuario, palavra_chave, filtrar_por, header, usuario_publicacao, link_usuario, link_publicacao, body, dt_pesquisa');
        $this->db->from('bot_pesquisa');

        // Adiciona condição de filtro pelo ID do cliente, se fornecido
        if ($investigacao_id != null) {
            $this->db->where('investigacao_id', $investigacao_id);
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

    public function getPesquisa($id, $id_cliente = null)
    {
        $this->db->select('id, id_bot, id_cliente, id_pesquisa_avulsa, investigacao_id, id_credencial, id_usuario, palavra_chave, filtrar_por, header, usuario_publicacao, link_usuario, link_publicacao, body, dt_pesquisa');
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

    public function createPesquisa($dados)
    {
        // Insere os dados na tabela 'bot_pesquisa'
        $this->db->insert('bot_pesquisa', $dados);

        // Retorna o último ID inserido
        return $this->db->insert_id();
    }


    public function updatePesquisa($dados, $id, $id_cliente = null)
    {
        $where['id'] = $id;

        if ($id_cliente != null) {
            $where['id_cliente'] = $id_cliente;
        }

        // Atualiza os dados na tabela 'bot_credencial' onde o ID corresponde ao fornecido
        $this->db->update('bot_pesquisa', $dados, $where);

        // Verifica se a operação afetou alguma linha no banco de dados
        if ($this->db->affected_rows() > 0) {
            return true;
        } else {
            return false;
        }
    }

    public function getScreenshot($id_pesquisa = null, $id_cliente = null)
    {
        try {
            // Conecte-se ao banco de dados usando PDO
            $pdo = new PDO("pgsql:host={$this->db->hostname};dbname={$this->db->database}", $this->db->username, $this->db->password);

            // Prepare e execute a consulta
            $query = "SELECT bs.id, bs.id_bot, bs.id_cliente, bs.id_pesquisa, bs.id_investigacao, 
                         b.descricao as descricao_bot, i.nome as nome_investigacao,
                         CONCAT('data:image/png;base64,', bs.bytea) as bytea
                  FROM bot_screenshot bs
                  JOIN bot b ON b.id = bs.id_bot
                  JOIN investigacao i ON i.id = bs.id_investigacao 
                  WHERE bs.id_pesquisa = :id_pesquisa AND bs.id_cliente = :id_cliente
                  ORDER BY bs.id DESC
                  LIMIT 1";
            $stmt = $pdo->prepare($query);
            $stmt->bindParam(':id_pesquisa', $id_pesquisa);
            $stmt->bindParam(':id_cliente', $id_cliente);
            $stmt->execute();
            $result = $stmt->fetch(PDO::FETCH_ASSOC);

            // Feche a conexão PDO
            $pdo = null;

            // Verifique se algum resultado foi encontrado
            if ($result) {
                return $result;
            } else {
                return FALSE;
            }
        } catch (PDOException $e) {
            // Em caso de erro, exiba uma mensagem de erro ou faça o tratamento adequado
            return FALSE;
        }
    }



    public function createScreenshot($dados)
    {
        // Insere os dados na tabela 'bot_pesquisa'
        $this->db->insert('bot_screenshot', $dados);

        // Verifica se a inserção foi bem-sucedida
        if ($this->db->affected_rows() > 0) {
            return true;
        } else {
            return false;
        }
    }
}
