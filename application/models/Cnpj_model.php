<?php
class Cnpj_model extends CI_Model
{
    private $db; // Corrigido: Removido o tipo de variável db

    public function __construct()
    {
        parent::__construct();
        $this->db = $this->load->database('cnpj', TRUE);
    }

    public function getEstabelecimentos($cnpj_basico, $onlycnpj = false)
    {

        if ($onlycnpj) {
            $this->db->select("UPPER(CONCAT(cnpj_basico, '/', cnpj_ordem, '-', cnpj_dv)) AS cnpj");
        } else {
            $this->db->select("UPPER(CONCAT(cnpj_basico, '/', cnpj_ordem, '-', cnpj_dv)) AS cnpj, 
            CASE 
                WHEN identificador_matriz_filial = '1' THEN 'MATRIZ'
                WHEN identificador_matriz_filial = '2' THEN 'FILIAL'
                ELSE 'INDEFINIDO'
            END AS tipo,
            UPPER(nome_fantasia) AS nome_fantasia,
            CASE
                WHEN situacao_cadastral = '01' THEN 'NULA'
                WHEN situacao_cadastral = '02' THEN 'ATIVA'
                WHEN situacao_cadastral = '03' THEN 'SUSPENSA'
                WHEN situacao_cadastral = '04' THEN 'INAPTA'
                WHEN situacao_cadastral = '08' THEN 'BAIXADA'
                ELSE 'DESCONHECIDA'
            END AS situacao_cadastral,
            TO_CHAR(TO_DATE(data_inicio_atividade, 'YYYYMMDD'), 'DD/MM/YYYY') AS data_inicio_atividade");
        }

        $this->db->from("estabelecimento e");
        $this->db->where("cnpj_basico", $cnpj_basico);

        if ($onlycnpj) {
            $this->db->where("identificador_matriz_filial", '1');
        }

        $query = $this->db->get();

        if ($query->num_rows() > 0) {

            if ($onlycnpj) {
                return $query->row();
            } else {
                return $query->result(); // Você pode retornar diretamente o resultado
            }
        } else {
            return false;
        }
    }

    public function getEstabelecimento($cnpj_basico, $cnpj_ordem, $cnpj_dv)
    {
        $this->db->select("UPPER(CONCAT(cnpj_basico, '/', cnpj_ordem, '-', cnpj_dv)) AS cnpj");
        $this->db->select("CASE 
                        WHEN identificador_matriz_filial = '1' THEN 'MATRIZ'
                        WHEN identificador_matriz_filial = '2' THEN 'FILIAL'
                        ELSE 'INDEFINIDO'
                    END AS tipo");
        $this->db->select("UPPER(nome_fantasia) AS nome_fantasia");
        $this->db->select("CASE 
                        WHEN situacao_cadastral = '01' THEN 'NULA'
                        WHEN situacao_cadastral = '02' THEN 'ATIVA'
                        WHEN situacao_cadastral = '03' THEN 'SUSPENSA'
                        WHEN situacao_cadastral = '04' THEN 'INAPTA'
                        WHEN situacao_cadastral = '08' THEN 'BAIXADA'
                        ELSE 'DESCONHECIDA'
                    END AS situacao_cadastral");
        $this->db->select("TO_CHAR(TO_DATE(data_situacao_cadastral, 'YYYYMMDD'), 'DD/MM/YYYY') AS data_situacao_cadastral");
        $this->db->select("UPPER(m.descricao) AS motivo_situacao_cadastral");
        $this->db->select("UPPER(nome_cidade_exterior) AS nome_cidade_exterior");
        $this->db->select("UPPER(pais) AS pais");
        $this->db->select("TO_CHAR(TO_DATE(data_inicio_atividade, 'YYYYMMDD'), 'DD/MM/YYYY') AS data_inicio_atividade");
        $this->db->select("cnae_fiscal_principal");

        // Correção da concatenação para cnae_fiscal_secundaria
        $this->db->select("cnae_fiscal_secundaria");

        $this->db->select("UPPER(e.tipo_logradouro) as tipo_logradouro");
        $this->db->select("UPPER(logradouro) as logradouro");
        $this->db->select("UPPER(numero) as numero");
        $this->db->select("UPPER(complemento) as complemento");
        $this->db->select("UPPER(bairro) as bairro");
        $this->db->select("UPPER(cep) as cep");
        $this->db->select("UPPER(munic.descricao) as municipio");
        $this->db->select("UPPER(uf) as uf");
        $this->db->select("CASE WHEN ddd_1 IS NULL AND telefone_1 IS NULL THEN NULL ELSE CONCAT('(', COALESCE(ddd_1, '0'), ') ', COALESCE(telefone_1, '0')) END AS telefone_1");
        $this->db->select("CASE WHEN ddd_2 IS NULL AND telefone_2 IS NULL THEN NULL ELSE CONCAT('(', COALESCE(ddd_2, '0'), ') ', COALESCE(telefone_2, '0')) END AS telefone_2");
        $this->db->select("CASE WHEN ddd_fax IS NULL AND fax IS NULL THEN NULL ELSE CONCAT('(', COALESCE(ddd_fax, '0'), ') ', COALESCE(fax, '0')) END AS fax");
        $this->db->select("correio_eletronico");
        $this->db->select("situacao_especial");
        $this->db->select("TO_CHAR(TO_DATE(data_situacao_especial, 'YYYYMMDD'), 'DD/MM/YYYY') AS data_situacao_especial");

        $this->db->from("estabelecimento e");
        $this->db->join("moti m", "e.motivo_situacao_cadastral = m.codigo");
        $this->db->join("munic", "e.municipio = munic.codigo");
        $this->db->where('cnpj_basico', $cnpj_basico);
        $this->db->where('cnpj_ordem', $cnpj_ordem);
        $this->db->where('cnpj_dv', $cnpj_dv);
        $query = $this->db->get();

        if ($query->num_rows() > 0) {
            return $query->row(); // Você pode retornar diretamente o resultado
        } else {
            return false;
        }
    }

    public function getCnae($codigo)
    {
        $this->db->select('codigo, descricao');
        $this->db->from('cnae');

        // Verifica se há vírgula na variável $codigo
        if (strpos($codigo, ',') !== false) {
            $codigos = explode(',', $codigo);
            $this->db->where_in('codigo', $codigos);
        } else {
            $this->db->where('codigo', $codigo);
        }

        $query = $this->db->get();

        if ($query->num_rows() > 0) {
            if (strpos($codigo, ',') !== false) {
                // Se houver vírgula, retorna um array de resultados formatado
                $result = array();
                foreach ($query->result() as $row) {
                    $result[] = $row->codigo . " - " . strtoupper($row->descricao);
                }
                $result = (object) $result;
                return $result;
            } else {
                // Se não houver vírgula, retorna uma linha formatada
                $row = $query->row();
                $result = $row->codigo . " - " . strtoupper($row->descricao);
                return $result;
            }
        } else {
            return false;
        }
    }

    public function getEmpresasRazao($razao_social)
    {
        $razao_social = strtoupper($razao_social);

        $this->db->select("
            e.cnpj_basico,
            '' as cnpj_completo,
            e.razao_social,
            UPPER(n.descricao) as natureza_juridica,
            UPPER(q.descricao) as qualificacao_responsavel,
            TRIM(BOTH ' ' FROM REPLACE(TO_CHAR(e.capital_social, 'R\$ 999G999G999D99'), '.', ',')) as capital_social,
            CASE 
                WHEN e.porte_empresa = '00' THEN 'NÃO INFORMADO' 
                WHEN e.porte_empresa = '01' THEN 'MICRO EMPRESA' 
                WHEN e.porte_empresa = '03' THEN 'EMPRESA DE PEQUENO PORTE' 
                WHEN e.porte_empresa = '05' THEN 'DEMAIS' 
                ELSE e.porte_empresa 
            END as porte_empresa,
            e.ente_federativo_responsavel
        ");

        $this->db->from('empresa e');
        $this->db->join('natju n', 'n.codigo = e.natureza_juridica');
        $this->db->join('quals q', 'q.codigo = e.qualificacao_responsavel');
        $this->db->like('e.razao_social', $razao_social);

        $query = $this->db->get();

        if ($query->num_rows() > 0) {
            return $query->result(); // Você pode retornar diretamente o resultado
        } else {
            return false;
        }
    }

    public function getEmpresa($cnpj_basico)
    {
        $this->db->select("UPPER(e.cnpj_basico) as cnpj_basico, UPPER(e.razao_social) as razao_social, UPPER(n.descricao) as natureza_juridica, UPPER(q.descricao) as qualificacao_responsavel, TRIM(BOTH ' ' FROM REPLACE(TO_CHAR(e.capital_social, 'R\$ 999G999G999D99'), '.', ',')) as capital_social, CASE WHEN e.porte_empresa = '00' THEN 'NÃO INFORMADO' WHEN e.porte_empresa = '01' THEN 'MICRO EMPRESA' WHEN e.porte_empresa = '03' THEN 'EMPRESA DE PEQUENO PORTE' WHEN e.porte_empresa = '05' THEN 'DEMAIS' ELSE e.porte_empresa END as porte_empresa, UPPER(e.ente_federativo_responsavel) as ente_federativo_responsavel");
        $this->db->from("empresa e");
        $this->db->join("natju n", "e.natureza_juridica = n.codigo");
        $this->db->join("quals q", "e.qualificacao_responsavel = q.codigo");
        $this->db->where("cnpj_basico", $cnpj_basico);
        $query = $this->db->get();

        if ($query->num_rows() > 0) {
            return $query->row(); // Você pode retornar diretamente o resultado
        } else {
            return false;
        }
    }

    public function getSimples($cnpj_basico)
    {
        $this->db->select('
            cnpj_basico,
            CASE WHEN opcao_pelo_simples = \'S\' THEN \'SIM\' 
                WHEN opcao_pelo_simples = \'N\' THEN \'NÃO\' 
                ELSE \'OUTROS\' 
            END AS opcao_pelo_simples,
            NULLIF(TO_CHAR(CASE WHEN data_opcao_simples = \'00000000\' THEN NULL ELSE TO_DATE(data_opcao_simples, \'YYYYMMDD\') END, \'DD/MM/YYYY\'), \'00/00/0000\') AS data_opcao_simples,
            NULLIF(TO_CHAR(CASE WHEN data_exclusao_simples = \'00000000\' THEN NULL ELSE TO_DATE(data_exclusao_simples, \'YYYYMMDD\') END, \'DD/MM/YYYY\'), \'00/00/0000\') AS data_exclusao_simples,
            CASE WHEN opcao_mei = \'S\' THEN \'SIM\' 
                WHEN opcao_mei = \'N\' THEN \'NÃO\' 
                ELSE \'OUTROS\' 
            END AS opcao_mei,
            NULLIF(TO_CHAR(CASE WHEN data_opcao_mei = \'00000000\' THEN NULL ELSE TO_DATE(data_opcao_mei, \'YYYYMMDD\') END, \'DD/MM/YYYY\'), \'00/00/0000\') AS data_opcao_mei,
            NULLIF(TO_CHAR(CASE WHEN data_exclusao_mei = \'00000000\' THEN NULL ELSE TO_DATE(data_exclusao_mei, \'YYYYMMDD\') END, \'DD/MM/YYYY\'), \'00/00/0000\') AS data_exclusao_mei
        ');
        $this->db->from('simples s');
        $this->db->where('cnpj_basico', $cnpj_basico);
        $query = $this->db->get();

        if ($query->num_rows() > 0) {
            return $query->row(); // Você pode retornar diretamente o resultado
        } else {
            return false;
        }
    }

    public function getSocios($cnpj_basico)
    {
        $this->db->select("UPPER(s.cnpj_basico) AS cnpj_basico");
        $this->db->select("CASE WHEN s.identificador_socio = '1' THEN 'PESSOA JURÍDICA'
                        WHEN s.identificador_socio = '2' THEN 'PESSOA FÍSICA'
                        WHEN s.identificador_socio = '3' THEN 'ESTRANGEIRO'
                        ELSE 'OUTROS' END AS identificador_socio");
        $this->db->select("UPPER(s.nome_socio_razao_social) AS nome_socio_razao_social");
        $this->db->select("s.cpf_cnpj_socio");
        $this->db->select("UPPER(q.descricao) AS qualificacao_socio");
        $this->db->select("TO_CHAR(TO_DATE(s.data_entrada_sociedade, 'YYYYMMDD'), 'DD/MM/YYYY') AS data_entrada_sociedade");
        $this->db->select("UPPER(s.pais) AS pais");
        $this->db->select("CASE WHEN s.faixa_etaria = '1' THEN '0 A 12 ANOS'
                        WHEN s.faixa_etaria = '2' THEN '13 A 20 ANOS'
                        WHEN s.faixa_etaria = '3' THEN '21 A 30 ANOS'
                        WHEN s.faixa_etaria = '4' THEN '31 A 40 ANOS'
                        WHEN s.faixa_etaria = '5' THEN '41 A 50 ANOS'
                        WHEN s.faixa_etaria = '6' THEN '51 A 60 ANOS'
                        WHEN s.faixa_etaria = '7' THEN '61 A 70 ANOS'
                        WHEN s.faixa_etaria = '8' THEN '71 A 80 ANOS'
                        WHEN s.faixa_etaria = '9' THEN '81 ANOS OU MAIS'
                        WHEN s.faixa_etaria = '0' THEN 'NÃO SE APLICA'
                        ELSE 'OUTROS' END AS faixa_etaria");
        $this->db->from("socios s");
        $this->db->join("quals q", "s.qualificacao_socio = q.codigo");
        $this->db->where("s.cnpj_basico", $cnpj_basico);
        $query = $this->db->get();

        if ($query->num_rows() > 0) {
            return $query->result(); // Você pode retornar diretamente o resultado
        } else {
            return false;
        }
    }

    public function getSocioCpf($cpf, $nome = false)
    {
        $this->db->select("s.cpf_cnpj_socio AS cpf_cnpj_socio");
        $this->db->select("UPPER(s.nome_socio_razao_social) AS nome_socio_razao_social");
        $this->db->select("CASE WHEN s.identificador_socio = '1' THEN 'PESSOA JURÍDICA' WHEN s.identificador_socio = '2' THEN 'PESSOA FÍSICA' WHEN s.identificador_socio = '3' THEN 'ESTRANGEIRO' ELSE 'OUTRO' END AS identificador_socio", FALSE);
        $this->db->select("UPPER(q.descricao) AS qualificacao_socio");
        $this->db->select("TO_CHAR(TO_DATE(s.data_entrada_sociedade::text, 'YYYYMMDD'), 'DD/MM/YYYY') AS data_entrada_sociedade", FALSE);
        $this->db->select("CASE WHEN s.faixa_etaria = '1' THEN '0 A 12 ANOS' WHEN s.faixa_etaria = '2' THEN '13 A 20 ANOS' WHEN s.faixa_etaria = '3' THEN '21 A 30 ANOS' WHEN s.faixa_etaria = '4' THEN '31 A 40 ANOS' WHEN s.faixa_etaria = '5' THEN '41 A 50 ANOS' WHEN s.faixa_etaria = '6' THEN '51 A 60 ANOS' WHEN s.faixa_etaria = '7' THEN '61 A 70 ANOS' WHEN s.faixa_etaria = '8' THEN '71 A 80 ANOS' WHEN s.faixa_etaria = '9' THEN '80 ANOS OU MAIS' WHEN s.faixa_etaria = '0' THEN 'NÃO SE APLICA' ELSE 'NÃO INFORMADA' END AS faixa_etaria", FALSE);
        $this->db->select("s.cnpj_basico AS cnpj_basico");
        $this->db->select("e.razao_social");

        $this->db->from("socios s");
        $this->db->join("quals q", "s.qualificacao_socio = q.codigo");
        $this->db->join("empresa e", "s.cnpj_basico = e.cnpj_basico");

        $cpf_cnpj_socio = '***' . substr($cpf, 3, 6) . '**';
        $this->db->where("s.cpf_cnpj_socio", $cpf_cnpj_socio);

        if ($nome) {
            $this->db->like("UPPER(s.nome_socio_razao_social)", strtoupper($nome));
        }

        $query = $this->db->get();

        return $query->num_rows() > 0 ? $query->result() : false;
    }
}
