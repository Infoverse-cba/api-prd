<?php



defined('BASEPATH') or exit('No direct script access allowed');



use chriskacerguis\RestServer\RestController;







class Bot_adm extends RestController
{

    function __construct()
    {

        // Construct the parent class

        parent::__construct();

        $this->load->helper('license_helper'); // Carrega o helper personalizado

        $this->load->model('Bot_adm_model', 'bot');

        $this->load->model('Pesquisa_model', 'pesquisa');

        $this->load->library(['session', 'ion_auth', 'encryption']);



        $this->encryption->initialize(

            array(

                'driver' => 'openssl',

                'cipher' => 'aes-256',

                'mode' => 'ctr'

            )

        );

    }



    /**

     * Verifica se uma string de data está no formato 'Y-m-d'.

     *

     * @param string $date_string A string que representa a data.

     * @return bool Retorna true se a data for válida e false caso contrário.

     */

    function is_valid_date($date_string)
    {

        // Define o formato esperado da data

        $format = 'Y-m-d';



        try {

            // Tenta criar um objeto DateTime a partir da string de data

            $date = DateTime::createFromFormat($format, $date_string);



            // Verifica se a data foi criada com sucesso e se corresponde à string original

            return $date && $date->format($format) === $date_string;

        } catch (Exception $e) {

            // Se ocorrer uma exceção, a string de data é inválida

            return false;

        }

    }



    /**

     * Valida se foram fornecidos todos os parâmetros obrigatórios.

     *

     * @param array $json Os dados da requisição.

     * @param array $requiredParams Os parâmetros obrigatórios.

     * @return bool Retorna true se todos os parâmetros obrigatórios estiverem presentes, false caso contrário.

     */

    private function validateRequiredParams($json, $requiredParams)
    {

        foreach ($requiredParams as $param) {

            if (!isset($json[$param])) {

                return false;

            }

        }

        return true;

    }



    /**

     * Valida se um parâmetro booleano tem um valor válido.

     *

     * @param array  $json   Os dados da requisição.

     * @param string $param  O nome do parâmetro a ser validado.

     * @return bool Retorna true se o parâmetro for um booleano válido, false caso contrário.

     */

    private function validateBooleanParam($json, $param)
    {

        return isset($json[$param]) && is_bool($json[$param]);

    }



    /**

     * Gera uma resposta padronizada para APIs.

     *

     * @param bool   $status   O status da resposta (true para sucesso, false para falha).

     * @param string $message  A mensagem associada à resposta.

     * @param mixed  $data     Os dados a serem incluídos na resposta (opcional).

     * @param int    $code     O código de status HTTP da resposta.

     * @return void

     */

    public function retorno($status, $message, $data = null, $code = 200)
    {

        // Monta o array de retorno

        $retorno = [

            'status' => $status,

            'message' => $message,

        ];



        // Inclui os dados na resposta se estiverem presentes

        if ($data !== null) {

            $retorno['data'] = $data;

        }



        // Define o código de status HTTP da resposta

        $this->response($retorno, $code);

    }



    public function bot_get()
    {

        // Se nenhum ID foi fornecido, busca todos os grupos

        $bots = $this->bot->getBots();



        if ($bots) {



            // Retorna todos os grupos com os dados adicionais

            $this->retorno(true, "Bots encontrados com sucesso.", (object) $bots, 200);

        } else {

            // Se nenhum grupo foi encontrado, retorna uma resposta com status 404

            $this->retorno(true, "Nenhuma bot foi encontrado.", null, 404);

        }

    }

    public function clientes_get()
    {
        $clientesAtivos = $this->bot->getClientesAtivos();

        if ($clientesAtivos) {
            // Retorna todos os grupos com os dados adicionais
            $this->retorno(true, "Clientes encontrado com sucesso.", (object) $clientesAtivos, 200);
        } else {
            // Se nenhum grupo foi encontrado, retorna uma resposta com status 404
            $this->retorno(true, "Nenhuma cliente foi encontrado.", null, 404);
        }
    }

    public function credencial_get($clienteId = null, $id = null)
    {

        if ($clienteId == null) {

            $this->retorno(false, "Informe o ID do cliente.", null, 401);

        }



        // Se um ID foi fornecido, busca o grupo correspondente

        if ($id != null) {

            $credencial = $this->bot->getCredencial($id, $clienteId);



            if ($credencial) {

                // Descriptografa os campos

                $credencial->username = $this->encryption->decrypt($credencial->username);

                $credencial->password = $this->encryption->decrypt($credencial->password);

                $credencial->email = $this->encryption->decrypt($credencial->email);



                // Retorna o grupo com os dados adicionais

                $this->retorno(true, "Credencial encontrado com sucesso.", (object) $credencial, 200);

            } else {

                // Se o grupo não foi encontrado, retorna uma resposta com status 404

                $this->retorno(true, "Nenhuma credencial encontrada com o ID fornecido.", null, 404);

            }

        }



        // Se nenhum ID foi fornecido, busca todos os grupos

        $credenciais = $this->bot->getCredenciais($id, $clienteId);



        if ($credenciais) {

            // Descriptografa os campos para cada credencial

            foreach ($credenciais as &$credencial) {

                $credencial->username = $this->encryption->decrypt($credencial->username);

                $credencial->password = $this->encryption->decrypt($credencial->password);

                $credencial->email = $this->encryption->decrypt($credencial->email);

            }



            // Retorna todos os grupos com os dados adicionais

            $this->retorno(true, "Credenciais encontradas com sucesso.", (object) $credenciais, 200);

        } else {

            // Se nenhum grupo foi encontrado, retorna uma resposta com status 404

            $this->retorno(true, "Nenhuma credencial foi encontrada.", null, 404);

        }

    }



    public function avulsa_get($clienteId = null, $opcao = null, $id = null)
    {

        if ($clienteId == null) {

            $this->retorno(false, "Informe o ID do cliente.", null, 401);

        }



        // Se um ID foi fornecido, busca o grupo correspondente

        if (strtolower($opcao) == 'id' && $id) {

            $avulsa = $this->bot->getPesquisaAvulsa($id, $clienteId);



            if ($avulsa) {

                // Retorna o grupo com os dados adicionais

                $this->retorno(true, "Pesquisa encontrada com sucesso.", (object) $avulsa, 200);

            } else {

                // Se o grupo não foi encontrado, retorna uma resposta com status 404

                $this->retorno(true, "Nenhuma pesquisa encontrada com o ID fornecido.", null, 404);

            }

        }



        if (strtolower($opcao) == 'bot' && $id != null) {

            // Se nenhum ID foi fornecido, busca todos os grupos

            $avulsa = $this->bot->getPesquisasAvulsasBot($id, $clienteId);



            if ($avulsa) {

                // Retorna o grupo com os dados adicionais

                $this->retorno(true, "Pesquisa encontrada com sucesso.", (object) $avulsa, 200);

            } else {

                // Se o grupo não foi encontrado, retorna uma resposta com status 404

                $this->retorno(true, "Nenhuma pesquisa encontrada com o ID fornecido.", null, 404);

            }

        }



        if (strtolower($opcao) == 'credencial' && $id != null) {

            // Se nenhum ID foi fornecido, busca todos os grupos

            $avulsa = $this->bot->getPesquisasAvulsasCredencial($id, $clienteId);



            if ($avulsa) {

                // Retorna o grupo com os dados adicionais

                $this->retorno(true, "Pesquisa encontrada com sucesso.", (object) $avulsa, 200);

            } else {

                // Se o grupo não foi encontrado, retorna uma resposta com status 404

                $this->retorno(true, "Nenhuma pesquisa encontrada com o ID fornecido.", null, 404);

            }

        }



        $avulsa = $this->bot->getPesquisasAvulsas($clienteId);



        if ($avulsa) {

            // Retorna o grupo com os dados adicionais

            $this->retorno(true, "Pesquisa encontrada com sucesso.", (object) $avulsa, 200);

        } else {

            // Se o grupo não foi encontrado, retorna uma resposta com status 404

            $this->retorno(true, "Nenhuma pesquisa encontrada.", null, 404);

        }

    }



    public function avulsa_patch($clienteId = null, $id = null)
    {

        if ($clienteId == null) {

            $this->retorno(false, "Informe o ID do cliente.", null, 401);

        }



        // Verifica se o ID da credencial foi fornecido

        if ($id == null) {

            $this->retorno(false, "Informe o ID da pesquisa.", null, 401);

        }



        // Obtém dados do corpo da requisição

        $json = $this->request->body;



        // Verifica se foram fornecidos parâmetros obrigatórios

        if (!isset($json['dt_executado']) && !isset($json['executado']) && !isset($json['executando']) && !isset($json['agendado']) && !isset($json['erro'])) {

            $this->retorno(false, "Por favor, forneça os parâmetros obrigatórios.", null, 403);

        }



        // Verifica e valida o parâmetro 'executado'

        if (isset($json['executado'])) {

            if (!is_bool($json['executado'])) {

                $this->retorno(false, "O parâmetro 'executado' aceita apenas true ou false.", null, 403);

            }

        }



        // Verifica e valida o parâmetro 'agendado'

        if (isset($json['agendado'])) {

            if (!is_bool($json['agendado'])) {

                $this->retorno(false, "O parâmetro 'agendado' aceita apenas true ou false.", null, 403);

            }

        }



        // Verifica e valida o parâmetro 'erro'

        if (isset($json['erro'])) {

            if (!is_bool($json['erro'])) {

                $this->retorno(false, "O parâmetro 'erro' aceita apenas true ou false.", null, 403);

            }

        }



        // Verifica e valida o parâmetro 'executando'

        if (isset($json['executando'])) {

            if (!is_bool($json['executando'])) {

                $this->retorno(false, "O parâmetro 'executando' aceita apenas true ou false.", null, 403);

            }

        }



        // Chama o método para atualizar a credencial no modelo

        $credencial = $this->bot->updatePesquisaAvulsa($json, $id, $clienteId);



        // Verifica o resultado da operação e retorna a resposta adequada

        if ($credencial) {

            $this->retorno(true, "Pesquisa atualizada com sucesso.", null, 200);

        } else {

            $this->retorno(false, "Falha ao atualizar pesquisa.", null, 403);

        }

    }



    public function rotina_get($clienteId = null, $opcao = null, $id = null)
    {

        // Se um ID foi fornecido, busca o grupo correspondente

        if (strtolower($opcao) == 'id' && $id) {

            $avulsa = $this->bot->getRotina($id, $clienteId);



            if ($avulsa) {

                // Retorna o grupo com os dados adicionais

                $this->retorno(true, "Rotina encontrada com sucesso.", (object) $avulsa, 200);

            } else {

                // Se o grupo não foi encontrado, retorna uma resposta com status 404

                $this->retorno(true, "Nenhuma rotina encontrada com o ID fornecido.", null, 404);

            }

        }



        if (strtolower($opcao) == 'bot' && $id != null) {

            // Se nenhum ID foi fornecido, busca todos os grupos

            $avulsa = $this->bot->getRotinasBot($id, $clienteId);



            if ($avulsa) {

                // Retorna o grupo com os dados adicionais

                $this->retorno(true, "Rotina(s) encontrada(s) com sucesso.", (object) $avulsa, 200);

            } else {

                // Se o grupo não foi encontrado, retorna uma resposta com status 404

                $this->retorno(true, "Nenhuma rotina encontrada com o ID fornecido.", null, 404);

            }

        }



        if (strtolower($opcao) == 'credencial' && $id != null) {

            // Se nenhum ID foi fornecido, busca todos os grupos

            $avulsa = $this->bot->getRotinasCredencial($id, $clienteId);



            if ($avulsa) {

                // Retorna o grupo com os dados adicionais

                $this->retorno(true, "Rotina(s) encontrada(s) com sucesso.", (object) $avulsa, 200);

            } else {

                // Se o grupo não foi encontrado, retorna uma resposta com status 404

                $this->retorno(true, "Nenhuma rotina encontrada com o ID fornecido.", null, 404);

            }

        }



        $avulsa = $this->bot->getRotinas($clienteId);



        if ($avulsa) {

            // Retorna o grupo com os dados adicionais

            $this->retorno(true, "Rotina(s) encontrada(s) com sucesso.", (object) $avulsa, 200);

        } else {

            // Se o grupo não foi encontrado, retorna uma resposta com status 404

            $this->retorno(true, "Nenhuma rotina encontrada.", null, 404);

        }

    }



    public function rotina_patch($clienteId = null, $id = null)
    {

        if ($clienteId == null) {

            $this->retorno(false, "Informe o ID do cliente.", null, 401);

        }



        // Verifica se o ID da credencial foi fornecido

        if ($id == null) {

            $this->retorno(false, "Informe o ID da da rotina.", null, 401);

        }



        // Obtém dados do corpo da requisição

        $json = $this->request->body;



        // Verifica se foram fornecidos parâmetros obrigatórios

        if (!isset($json['id_credencial']) && !isset($json['descricao']) && !isset($json['horario']) && !isset($json['filtrar_por']) && !isset($json['status']) && !isset($json['dt_fim'])) {

            $this->retorno(false, "Por favor, forneça os parâmetros obrigatórios.", null, 403);

        }



        // Verifica e valida o parâmetro 'status'

        if (isset($json['status'])) {

            if (!is_bool($json['status'])) {

                $this->retorno(false, "O parâmetro 'status' aceita apenas true ou false.", null, 403);

            }

        }



        if (isset($json['horario'])) {

            // Adiciona uma data fictícia para formar uma string de data/hora completa

            $data_atual = date("Y-m-d"); // Obtém a data atual no formato "Y-m-d"

            $data_hora_str = $data_atual . ' ' . $json['horario'];

            $json['horario'] = $data_hora_str;

        }



        $json['dt_alteracao'] = date('Y-m-d');



        // Chama o método para atualizar a credencial no modelo

        $credencial = $this->bot->updateRotina($json, $id, $clienteId);



        // Verifica o resultado da operação e retorna a resposta adequada

        if ($credencial) {

            $this->retorno(true, "Rotina atualizada com sucesso.", null, 200);

        } else {

            $this->retorno(false, "Falha ao atualizar rotina.", null, 403);

        }

    }



    public function pesquisa_post()
    {

        // Obtém dados do corpo da requisição

        $json = $this->request->body;



        // Extrai parâmetros obrigatórios do JSON

        $clienteId = isset($json['id_cliente']) ? $json['id_cliente'] : null; // Obrigatorio

        $id_bot = isset($json['id_bot']) ? $json['id_bot'] : null; // Obrigatorio

        $id_pesquisa_avulsa = isset($json['id_pesquisa_avulsa']) ? $json['id_pesquisa_avulsa'] : 0;

        $investigacao_id = isset($json['investigacao_id']) ? $json['investigacao_id'] : 0; // Obrigatorio

        $id_credencial = isset($json['id_credencial']) ? $json['id_credencial'] : null; // Obrigatorio

        $id_usuario = isset($json['id_usuario']) ? $json['id_usuario'] : 0; // Obrigatorio

        $palavra_chave = isset($json['palavra_chave']) ? $json['palavra_chave'] : null; // Obrigatorio

        $filtrar_por = isset($json['filtrar_por']) ? $json['filtrar_por'] : null;

        $header = isset($json['header']) ? $json['header'] : null;

        $usuario_publicacao = isset($json['usuario_publicacao']) ? $json['usuario_publicacao'] : null;

        $link_usuario = isset($json['link_usuario']) ? $json['link_usuario'] : null;

        $link_publicacao = isset($json['link_publicacao']) ? $json['link_publicacao'] : null;

        $body = isset($json['body']) ? $json['body'] : null;

        $dt_pesquisa = isset($json['dt_pesquisa']) ? $json['dt_pesquisa'] : null;

        $bytea = isset($json['bytea']) ? $json['bytea'] : null;



        // Verifica se os parâmetros obrigatórios foram fornecidos

        if ($clienteId == null) {

            $this->retorno(false, "Favor informar o ID do cliente.", null, 403);

        }



        if ($id_bot == null) {

            $this->retorno(false, "Favor informar o ID do bot.", null, 403);

        }



        if ($id_credencial == null) {

            $this->retorno(false, "Favor informar o ID da credencial.", null, 403);

        }



        if ($id_usuario == null) {

            $this->retorno(false, "Favor informar o ID do usuário.", null, 403);

        }



        if ($investigacao_id == null) {

            $this->retorno(false, "Favor informar o ID da investigação.", null, 403);

        }



        if ($palavra_chave == null) {

            $this->retorno(false, "Favor informar uma palavra-chave.", null, 403);

        }



        if ($bytea == null) {

            $this->retorno(false, "Favor informar uma screenshot.", null, 403);

        }



        // Cria um array com os dados da credencial, criptografando os campos sensíveis

        $dados = [

            'id_bot' => $id_bot,

            'id_cliente' => $clienteId,

            'id_credencial' => $id_credencial,

            'id_usuario' => $id_usuario,

            'investigacao_id' => $investigacao_id,

            'id_pesquisa_avulsa' => $id_pesquisa_avulsa,

            'palavra_chave' => $palavra_chave,

            'filtrar_por' => $filtrar_por,

            'header' => $header,

            'usuario_publicacao' => $usuario_publicacao,

            'link_usuario' => $link_usuario,

            'link_publicacao' => $link_publicacao,

            'body' => $body,

            'dt_pesquisa' => $dt_pesquisa,

        ];



        // Chama o método para criar a credencial no modelo

        $avulsa_id = $this->pesquisa->createPesquisa($dados);



        if ($avulsa_id) {

            $dados = [

                'id_bot' => $id_bot,

                'id_cliente' => $clienteId,

                'id_pesquisa' => $avulsa_id,

                'id_investigacao' => $investigacao_id,

                'bytea' => $bytea

            ];

            $screenshot = $this->pesquisa->createScreenshot($dados);



            // Verifica o resultado da operação e retorna a resposta adequada

            if ($screenshot) {

                $this->retorno(true, "Pesquisa cadastrada com sucesso.", null, 200);

            } else {

                $this->retorno(false, "Falha ao cadastrar pesquisa. #02", null, 403);

            }

        } else {

            $this->retorno(false, "Falha ao cadastrar pesquisa. #01", null, 403);

        }





    }

}

