<?php defined('BASEPATH') or exit('No direct script access allowed');

class Bot_screenshot_library
{
    protected $pdo;

    public function __construct()
    {
        // Carrega o CodeIgniter
        $CI =& get_instance();

        // Carrega as configurações do banco de dados do CodeIgniter
        $CI->config->load('database');

        // Configurações do banco de dados
        $db_config = $CI->config->item('default');

        // Configurações adicionais para o PDO
        $pdo_options = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            // Adicione outras opções conforme necessário
        ];

        // Conecta ao banco de dados usando o PDO
        $dsn = "{$db_config['dbdriver']}:host={$db_config['hostname']};dbname={$db_config['database']}";
        $this->pdo = new PDO($dsn, $db_config['username'], $db_config['password'], $pdo_options);
    }

    public function getScreenshot($id_pesquisa = null, $id_cliente = null)
    {
        // Adiciona condições de filtro pelo ID da pesquisa e/ou ID do cliente
        $conditions = [];
        if ($id_pesquisa != null) {
            $conditions[] = 'id_pesquisa = :id_pesquisa';
        }

        if ($id_cliente != null) {
            $conditions[] = 'id_cliente = :id_cliente';
        }

        $where_clause = implode(' OR ', $conditions);

        // Prepara a consulta usando o PDO
        $query = "SELECT id, id_bot, id_cliente, id_pesquisa, id_investigacao, 
                         CONCAT('data:image/png;base64,', bytea) as bytea
                  FROM bot_screenshot
                  WHERE {$where_clause}
                  ORDER BY id DESC
                  LIMIT 1";

        $stmt = $this->pdo->prepare($query);

        // Atribui os valores dos parâmetros, se necessário
        if ($id_pesquisa != null) {
            $stmt->bindParam(':id_pesquisa', $id_pesquisa, PDO::PARAM_INT);
        }

        if ($id_cliente != null) {
            $stmt->bindParam(':id_cliente', $id_cliente, PDO::PARAM_INT);
        }

        $stmt->execute();

        // Retorna o resultado
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return($result) ? $result : FALSE;
    }
}
