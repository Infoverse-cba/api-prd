<?php

class Cliente_model extends CI_Model
{



    public function __construct()
    {

        parent::__construct();

        $this->load->database();
    }



    public function getLicenseKey($hash)
    {

        $this->db->select('id, id_cliente, hash, qtd_usuario, dt_criacao, dt_alteracao, dt_validade');

        $this->db->from('cliente_licenca');

        $this->db->where('hash', $hash);

        $query = $this->db->get();



        if ($query->num_rows() > 0) {

            return $query->row();
        } else {

            return false;
        }
    }



    public function getLicenseKeyClientId($id)
    {

        $this->db->select('id, id_cliente, hash, qtd_usuario, dt_criacao, dt_alteracao, dt_validade');

        $this->db->from('cliente_licenca');

        $this->db->where('id_cliente', $id);

        $query = $this->db->get();



        if ($query->num_rows() > 0) {

            return $query->row();
        } else {

            return false;
        }
    }

    public function getQtdUsuariosRestantes($id_cliente)
    {
        $this->db->select('cl.qtd_usuario - COUNT(auc.id) AS qtd_usuario_restante');
        $this->db->from('cliente_licenca cl');
        $this->db->join('auth_user_cliente auc', 'auc.id_cliente = cl.id_cliente', 'left');
        $this->db->where('cl.id_cliente', $id_cliente);
        $this->db->where('cl.dt_validade >', 'CURRENT_DATE', false);
        $this->db->group_by('cl.id_cliente, cl.qtd_usuario');

        $query = $this->db->get();

        // Verificar se há pelo menos uma linha no resultado
        if ($query->num_rows() > 0) {
            $qtd_usuario_restante = $query->row()->qtd_usuario_restante;
            // Verificar se é menor ou igual a 0
            return max(0, $qtd_usuario_restante);
        } else {
            return 0;
        }
    }

    public function licencaValida($id_cliente)
    {
        $this->db->select('id_cliente');
        $this->db->from('cliente_licenca');
        $this->db->where('dt_validade >', 'CURRENT_DATE', false);
        $this->db->where('id_cliente', $id_cliente);

        $query = $this->db->get();

        if ($query->num_rows() > 0) {
            return $query->row()->id_cliente;
        } else {
            return false;
        }
    }

    public function createLicenseKey($dados)
    {

        $this->db->insert('cliente_licenca', $dados);



        if ($this->db->affected_rows() > 0) {

            return true;
        } else {

            return false;
        }
    }



    public function updateLicenseKey($dados, $hash)
    {

        $this->db->update('cliente_licenca', $dados, ['hash' => $hash]);



        if ($this->db->affected_rows() > 0) {

            return true;
        } else {

            return false;
        }
    }



    public function updateLicenseKeyClienteId($dados, $id)
    {

        $this->db->update('cliente_licenca', $dados, ['id_cliente' => $id]);



        if ($this->db->affected_rows() > 0) {

            return true;
        } else {

            return false;
        }
    }



    public function getClientes()
    {

        $this->db->select('id, cnpj, razao_social, nome_fantasia, dt_criacao, dt_alteracao, status');

        $this->db->from('cliente');

        $query = $this->db->get();



        if ($query->num_rows() > 0) {

            return $query->result();
        } else {

            return false;
        }
    }



    public function getCliente($id)
    {

        $this->db->select('id, cnpj, razao_social, nome_fantasia, dt_criacao, dt_alteracao, status');

        $this->db->from('cliente');

        $this->db->where('id', $id);

        $query = $this->db->get();



        if ($query->num_rows() > 0) {

            return $query->row();
        } else {

            return false;
        }
    }



    public function getClienteCnpj($cnpj)
    {

        $this->db->select('id, cnpj, razao_social, nome_fantasia, dt_criacao, dt_alteracao, status');

        $this->db->from('cliente');

        $this->db->where('cnpj', $cnpj);

        $query = $this->db->get();



        if ($query->num_rows() > 0) {

            return $query->row();
        } else {

            return false;
        }
    }



    public function createCliente($dados)
    {

        $this->db->insert('cliente', $dados);



        if ($this->db->affected_rows() > 0) {

            return $this->db->insert_id();
        } else {

            return false;
        }
    }



    public function updateCliente($dados, $id)
    {

        $this->db->update('cliente', $dados, ['id' => $id]);



        if ($this->db->affected_rows() > 0) {

            return true;
        } else {

            return false;
        }
    }



    public function getClienteEndereco($id_cliente)
    {

        $this->db->select('id, id_cliente, cep, referencia, complemento, numero, logradouro, bairro, cidade, estado, dt_criacao, dt_alteracao');

        $this->db->from('cliente_endereco');

        $this->db->where('id_cliente', $id_cliente);

        $query = $this->db->get();



        if ($query->num_rows() > 0) {

            return $query->row();
        } else {

            return false;
        }
    }



    public function createClienteEndereco($dados)
    {

        $this->db->insert('cliente_endereco', $dados);



        if ($this->db->affected_rows() > 0) {

            return $this->db->insert_id();
        } else {

            return false;
        }
    }



    public function updateClienteEndereco($dados, $id_cliente)
    {

        $this->db->update('cliente_endereco', $dados, ['id_cliente' => $id_cliente]);

        if ($this->db->affected_rows() > 0) {

            return true;
        } else {

            return false;
        }
    }



    public function getClienteResponsavel($id_cliente)
    {

        $this->db->select('id, id_cliente, cpf, nome, email, telefone, celular, cargo, setor, dt_criacao, dt_alteracao');

        $this->db->from('cliente_responsavel');

        $this->db->where('id_cliente', $id_cliente);

        $query = $this->db->get();



        if ($query->num_rows() > 0) {

            return $query->row();
        } else {

            return false;
        }
    }



    public function createClienteResponsavel($dados)
    {

        $this->db->insert('cliente_responsavel', $dados);

        if ($this->db->affected_rows() > 0) {

            return $this->db->insert_id();
        } else {

            return false;
        }
    }



    public function updateClienteResponsavel($dados, $id_cliente)
    {

        $this->db->update('cliente_responsavel', $dados, ['id_cliente' => $id_cliente]);

        if ($this->db->affected_rows() > 0) {

            return true;
        } else {

            return false;
        }
    }



    public function getUserClienteId($id_user)
    {

        $this->db->select('id_cliente');

        $this->db->from('auth_user_cliente');

        $this->db->where('id_user', $id_user);

        $query = $this->db->get();



        if ($query->num_rows() > 0) {

            return $query->row();
        } else {

            return false;
        }
    }
}
