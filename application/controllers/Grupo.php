<?php

defined('BASEPATH') or exit('No direct script access allowed');

use chriskacerguis\RestServer\RestController;



class Grupo extends RestController
{
    function __construct()
    {
        // Construct the parent class
        parent::__construct();
        $this->load->helper('license_helper'); // Carrega o helper personalizado
        $this->load->library(['session', 'ion_auth']);
        $this->load->model('Usuario_model', 'usuario');
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
     * Summary: Retorna informações sobre um grupo específico ou uma lista de todos os grupos.
     *
     * @param int|null $id - O ID do grupo a ser recuperado. Se não fornecido, retorna todos os grupos.
     *
     * @return void - A função envia uma resposta JSON com informações sobre o grupo encontrado ou uma lista de todos os grupos.
     *   - Se o usuário não estiver autenticado, retorna uma resposta de não autenticação (401).
     *   - Se um ID foi fornecido, busca o grupo correspondente e retorna com sucesso (200) ou informa que nenhum grupo foi encontrado com o ID fornecido (404).
     *   - Se nenhum ID foi fornecido, retorna todos os grupos com sucesso (200) ou informa que nenhum grupo foi encontrado (404).
     */
    public function grupo_get($id = null)
    {
        // Verifica se o usuário está autenticado
        if (!$this->ion_auth->logged_in()) {
            $this->retorno(false, "Usuário não autenticado.", null, 401);
        }

        // Se um ID foi fornecido, busca o grupo correspondente
        if ($id != null) {
            $grupo = $this->ion_auth->group($id)->result();
            if ($grupo) {
                // Retorna o grupo com os dados adicionais
                $this->retorno(true, "Grupo encontrado com sucesso.", $grupo, 200);
            } else {
                // Se o grupo não foi encontrado, retorna uma resposta com status 404
                $this->retorno(true, "Nenhum grupo encontrado com o ID fornecido.", null, 404);
            }
        } else {
            // Se nenhum ID foi fornecido, busca todos os grupos
            $grupos = $this->ion_auth->groups()->result();
            if ($grupos) {
                // Retorna todos os grupos com os dados adicionais
                $this->retorno(true, "Grupos encontrados com sucesso.", $grupos, 200);
            } else {
                // Se nenhum grupo foi encontrado, retorna uma resposta com status 404
                $this->retorno(true, "Nenhum grupo foi encontrado.", null, 404);
            }
        }
    }

    /**
     * Summary: Retorna informações sobre os grupos de um usuário específico.
     *
     * @param int|null $id - O ID do usuário para o qual os grupos serão recuperados.
     *
     * @return void - A função envia uma resposta JSON com informações sobre os grupos do usuário ou mensagens de erro.
     *   - Se o usuário não estiver autenticado, retorna uma resposta de não autenticação (401).
     *   - Se um ID foi fornecido, busca os grupos do usuário correspondente e retorna com sucesso (200) ou informa que nenhum grupo foi encontrado (404).
     *   - Se nenhum ID foi fornecido, retorna uma resposta de requisição inválida (400) solicitando o fornecimento do ID do usuário.
     */
    public function user_get($id = null)
    {
        // Verifica se o usuário está autenticado
        if (!$this->ion_auth->logged_in()) {
            $this->retorno(false, "Usuário não autenticado.", null, 401);
        }

        // Se um ID foi fornecido, busca os grupos do usuário correspondente
        if ($id != null) {
            $grupos = $this->ion_auth->get_users_groups($id)->result();
            if ($grupos) {
                // Retorna os grupos com os dados adicionais
                $this->retorno(true, "Grupos encontrados com sucesso.", $grupos, 200);
            } else {
                // Se nenhum grupo foi encontrado, retorna uma resposta com status 404
                $this->retorno(true, "Nenhum grupo encontrado.", null, 404);
            }
        } else {
            // Se nenhum ID foi fornecido, retorna uma resposta de requisição inválida
            $this->retorno(true, "Informe o ID do usuário.", null, 400);
        }
    }

    /**
     * Summary: Cadastra um novo grupo.
     *
     * @return void - A função envia uma resposta JSON indicando se o grupo foi cadastrado com sucesso ou mensagens de erro.
     *   - Verifica se o usuário está autenticado e se possui permissões de administrador.
     *   - Obtém dados do corpo da requisição em formato JSON.
     *   - Extrai parâmetros obrigatórios do JSON, como nome e descrição do grupo.
     *   - Verifica se os parâmetros obrigatórios foram fornecidos e retorna uma resposta de requisição inválida (403) se algum estiver ausente.
     *   - Utiliza a função `create_group` do `ion_auth` para criar o novo grupo no banco de dados.
     *   - Retorna uma resposta indicando sucesso (200) ou falha (403) no cadastro do grupo.
     */
    public function grupo_post()
    {
        // Verifica se o usuário está autenticado
        if (!$this->ion_auth->logged_in()) {
            $this->retorno(false, "Usuário não autenticado.", null, 401);
        }

        // Verifica se o usuário é administrador
        if (!$this->ion_auth->is_admin()) {
            $this->retorno(false, "Este usuário não é administrador.", null, 401);
        }

        // Obtém dados do corpo da requisição em formato JSON
        $json = $this->request->body;

        // Extrai parâmetros obrigatórios do JSON
        $name = isset($json['nome']) ? $json['nome'] : null;
        $description = isset($json['descricao']) ? $json['descricao'] : null;

        // Verifica se os parâmetros obrigatórios foram fornecidos
        if ($name == null) {
            $this->retorno(false, "Favor informar o nome do grupo.", null, 403);
        }

        // Utiliza a função create_group do ion_auth para criar o novo grupo no banco de dados
        $new_group_id = $this->ion_auth->create_group($name, $description);

        // Verifica o resultado da operação e retorna a resposta adequada
        if ($new_group_id) {
            $this->retorno(true, "Grupo cadastrado com sucesso.", null, 200);
        } else {
            $this->retorno(false, "Falha ao cadastrar grupo.", null, 403);
        }
    }

    /**
     * Summary: Atualiza informações de um grupo existente.
     *
     * @return void - A função envia uma resposta JSON indicando se o grupo foi atualizado com sucesso ou mensagens de erro.
     *   - Verifica se o usuário está autenticado e se possui permissões de administrador.
     *   - Obtém dados do corpo da requisição em formato JSON.
     *   - Extrai parâmetros obrigatórios do JSON, como o ID do grupo.
     *   - Verifica se os parâmetros obrigatórios foram fornecidos e retorna uma resposta de requisição inválida (403) se algum estiver ausente.
     *   - Obtém informações do grupo existente usando o ID fornecido.
     *   - Verifica o comprimento do novo nome do grupo e retorna uma resposta de requisição inválida (403) se exceder 40 caracteres.
     *   - Utiliza a função `update_group` do `ion_auth` para atualizar o grupo no banco de dados.
     *   - Retorna uma resposta indicando sucesso (200) ou falha (403) na atualização do grupo.
     */
    public function grupo_patch()
    {
        // Verifica se o usuário está autenticado
        if (!$this->ion_auth->logged_in()) {
            $this->retorno(false, "Usuário não autenticado.", null, 401);
        }

        // Verifica se o usuário é administrador
        if (!$this->ion_auth->is_admin()) {
            $this->retorno(false, "Este usuário não é administrador.", null, 401);
        }

        // Obtém dados do corpo da requisição em formato JSON
        $json = $this->request->body;

        // Extrai parâmetros obrigatórios do JSON
        $id = isset($json['id']) ? $json['id'] : null;

        if ($id == 1 || $id == 2) {
            $this->retorno(false, "O grupo de ID {$id} não pode ser alterado por se tratar de um grupo padrão do sistema.", null, 403);
        }

        // Verifica se os parâmetros obrigatórios foram fornecidos
        if ($id == null) {
            $this->retorno(false, "Favor informar o ID do grupo.", null, 403);
        }

        // Obtém informações do grupo existente usando o ID fornecido
        $group = $this->ion_auth->group($id)->row();

        // Verifica o comprimento do novo nome do grupo
        if (isset($json['nome']) && strlen($json['nome']) > 40) {
            $this->retorno(false, "O nome do grupo pode ter no máximo 40 caracteres.", null, 403);
        }

        // Define os parâmetros para atualização, utilizando os valores fornecidos ou os existentes
        $name = isset($json['nome']) ? $json['nome'] : $group->name;
        $description = isset($json['descricao']) ? $json['descricao'] : $group->description;

        // Utiliza a função update_group do ion_auth para atualizar o grupo no banco de dados
        $group_update = $this->ion_auth->update_group($id, $name, array('description' => $description));

        // Verifica o resultado da operação e retorna a resposta adequada
        if ($group_update) {
            $this->retorno(true, "Grupo atualizado com sucesso.", null, 200);
        } else {
            $this->retorno(false, "Falha ao atualizar grupo.", null, 403);
        }
    }


    /**
     * Summary: Verifica se um usuário pertence a um grupo específico.
     *
     * @param int|null $idgroupId - O ID do grupo a ser verificado.
     * @param int|null $userId - O ID do usuário a ser verificado.
     *
     * @return void - A função envia uma resposta JSON indicando se o usuário pertence ao grupo ou mensagens de erro.
     *   - Se o usuário não estiver autenticado, retorna uma resposta de não autenticação (401).
     *   - Se ambos os IDs foram fornecidos, verifica se o usuário pertence ao grupo especificado e retorna com sucesso (200) ou informa que o usuário não pertence ao grupo (404).
     *   - Se algum dos IDs não foi fornecido, retorna uma resposta de requisição inválida (400) solicitando o fornecimento do ID do usuário e do grupo.
     */
    public function in_group_get($idgroupId = null, $userId = null)
    {
        // Verifica se o usuário está autenticado
        if (!$this->ion_auth->logged_in()) {
            $this->retorno(false, "Usuário não autenticado.", null, 401);
        }

        // Se ambos os IDs foram fornecidos, verifica se o usuário pertence ao grupo especificado
        if ($idgroupId != null && $userId != null) {
            $pertenceAoGrupo = $this->ion_auth->in_group($idgroupId, $userId);
            if ($pertenceAoGrupo) {
                // Retorna uma resposta indicando que o usuário pertence ao grupo
                $this->retorno(true, "Usuário pertence ao grupo.", (object) ['status' => $pertenceAoGrupo], 200);
            } else {
                // Se o usuário não pertence ao grupo, retorna uma resposta com status 404
                $this->retorno(true, "Usuário não pertence ao grupo.", null, 404);
            }
        } else {
            // Se algum dos IDs não foi fornecido, retorna uma resposta de requisição inválida
            $this->retorno(true, "Informe o ID do usuário e do grupo.", null, 400);
        }
    }

    public function user_group_post($id = null)
    {
        // Verifica se o usuário está autenticado
        if (!$this->ion_auth->logged_in()) {
            $this->retorno(false, "Usuário não autenticado.", null, 401);
        }

        if (!$this->ion_auth->is_admin()) {
            $this->retorno(false, "Este usuário não é administrador.", null, 401);
        }

        if ($id == null) {
            $this->retorno(false, "Informe o ID do usuário.", null, 403);
        }

        if (!$this->usuario->userInClient($id, $this->session->userdata('cliente_id'))) {
            $this->retorno(false, "Usuário não pertence ao cliente.", null, 401);
        }

        $json = $this->request->body;

        if (!isset($json['group_ids'])) {
            $this->retorno(false, "Por favor, forneçe pelo menos um parâmetro.", null, 403);
        }

        if (isset($json['group_ids'])) {
            if (!is_array($json['group_ids'])) {
                $this->retorno(false, "O parâmetro 'group_ids' deve ser um array.", null, 403);
            }
        }

        if (empty($json['group_ids'])) {
            $this->retorno(false, "O parâmetro 'group_ids' não pode ser um array vazio.", null, 403);
        }

        $grp_ids = array();

        foreach ($json['group_ids'] as $grp_id) {
            $pertenceAoGrupo = $this->ion_auth->in_group($grp_id, $id);
            if (!$pertenceAoGrupo) {
                $grp_ids[] = $grp_id; // Adiciona o ID do grupo ao array
            }
        }

        // Remove duplicatas do array
        $grp_ids = array_unique($grp_ids);

        if (!empty($grp_ids)) {
            if ($this->ion_auth->add_to_group($grp_ids, $id)) {
                $this->retorno(true, "Grupo(s) adicionado(s) com sucesso.", null, 200);
            } else {
                $this->retorno(false, "Ocorreu um erro ao adicionar o(s) grupo(s).", null, 403);
            }
        } else {
            $this->retorno(false, "O usuário já pertence a todos os grupos fornecidos.", null, 403);
        }
    }

    public function user_group_patch($id = null)
    {
        // Verifica se o usuário está autenticado
        if (!$this->ion_auth->logged_in()) {
            $this->retorno(false, "Usuário não autenticado.", null, 401);
        }

        if (!$this->ion_auth->is_admin()) {
            $this->retorno(false, "Este usuário não é administrador.", null, 401);
        }

        if ($id == null) {
            $this->retorno(false, "Informe o ID do usuário.", null, 403);
        }

        if (!$this->usuario->userInClient($id, $this->session->userdata('cliente_id'))) {
            $this->retorno(false, "Usuário não pertence ao cliente.", null, 401);
        }

        $json = $this->request->body;

        if (!isset($json['group_ids'])) {
            $this->retorno(false, "Por favor, forneçe pelo menos um parâmetro.", null, 403);
        }

        if (isset($json['group_ids'])) {
            if (!is_array($json['group_ids'])) {
                $this->retorno(false, "O parâmetro 'group_ids' deve ser um array.", null, 403);
            }
        }

        if (empty($json['group_ids'])) {
            $this->retorno(false, "O parâmetro 'group_ids' não pode ser um array vazio.", null, 403);
        }

        if ($this->ion_auth->remove_from_group($json['group_ids'], $id)) {
            $this->retorno(true, "Grupo(s) removido(s) com sucesso.", null, 200);
        } else {
            $this->retorno(false, "Ocorreu um erro ao remover o(s) grupo(s).", null, 403);
        }
    }
}
