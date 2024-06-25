<?php
class Cron_model extends CI_Model
{

    public function __construct()
    {
        parent::__construct();
        $this->load->database();
    }

    public function getClientes()
    {
        // Selecionar os campos desejados
        $this->db->select('id, status');
        $this->db->from('cliente');
        $this->db->where('status', true);

        // Executar a consulta
        $query = $this->db->get();

        // Verificar se há resultados
        if ($query->num_rows() > 0) {
            return $query->result(); // Retornar resultados se encontrados
        } else {
            return false; // Retornar false se nenhum resultado for encontrado
        }
    }

    public function getInstances($cliente_id = null)
    {
        // Selecionar os campos desejados
        $this->db->select('id, cliente_id, instance_key, status_id');
        $this->db->from('whats_instance');
        $this->db->where('status_id', 61);

        if (!is_null($cliente_id)) {
            $this->db->where('cliente_id', $cliente_id);
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

    public function getGruposMonitorados($cliente_id = null, $instance_key = null)
    {
        // Selecionar os campos desejados
        $this->db->select('wmg.id, wi.id as instance_id, wmg.session, wmg.group_id, wmg.cliente_id, wmg.status_id');
        $this->db->from('whats_monitored_group wmg');
        $this->db->join('whats_instance wi', 'wi.instance_key = wmg.session');
        $this->db->where('wmg.status_id', 61);

        if (!is_null($instance_key)) {
            $this->db->where('wmg.session', $instance_key);
        }

        if (!is_null($cliente_id)) {
            $this->db->where('wmg.cliente_id', $cliente_id);
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

    public function getItensMonitorados($cliente_id = null, $instance_id = null, $monitored_group_id = null)
    {
        $this->db->select('wmi.id, wmi.status_id, wmi.monitored_group_id, wi.id as instance_id, wmi.is_member, wmi.value, wmi.cliente_id');
        $this->db->from('whats_monitored_item wmi');
        $this->db->join('whats_monitored_group wmg', 'wmg.id = wmi.monitored_group_id');
        $this->db->join('whats_instance wi', 'wi.instance_key = wmg.session');
        $this->db->where('wmi.status_id', 61);

        if (!is_null($monitored_group_id)) {
            $this->db->where('wmi.monitored_group_id', $monitored_group_id);
        }

        if (!is_null($instance_id)) {
            $this->db->where('wi.id', $instance_id);
        }

        if (!is_null($cliente_id)) {
            $this->db->where('wmi.cliente_id', $cliente_id);
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

    public function getMessages()
    {
        // Selecionar os campos desejados
        $this->db->select('id, cron_checked, sender_id, session, data_chatId, content');
        $this->db->from('whats_message');
        $this->db->where('cron_checked', false);

        // Executar a consulta
        $query = $this->db->get();

        // Verificar se há resultados
        if ($query->num_rows() > 0) {
            return $query->result(); // Retornar resultados se encontrados
        } else {
            return false; // Retornar false se nenhum resultado for encontrado
        }
    }

    public function updateWhatsMessage($dados, $id)
    {
        // Atualiza os dados na tabela 'whats_instance' onde o ID corresponde ao fornecido
        $this->db->update('whats_message', $dados, ['id' => $id]);

        // Verifica se a atualização teve êxito
        if ($this->db->affected_rows() > 0) {
            // Retorna true se a atualização foi bem-sucedida
            return true;
        } else {
            // Retorna false se a atualização falhou
            return false;
        }
    }

    public function insertWhatsAlert($dados)
    {
        $this->db->insert('whats_alert', $dados);

        // Verifica se a inserção teve êxito
        if ($this->db->affected_rows() > 0) {
            // Retorna o ID do registro inserido
            return $this->db->insert_id();
        } else {
            // Retorna false se a inserção falhou
            return false;
        }
    }
}
