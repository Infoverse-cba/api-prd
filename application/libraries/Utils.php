<?php
defined('BASEPATH') or exit('No direct script access allowed');

use chriskacerguis\RestServer\RestController;

class Utils extends RestController
{
    protected $CI;

    public function __construct()
    {
        parent::__construct();
        $this->CI = &get_instance();

        // Carrega a biblioteca ion_auth apenas dentro da função retorno, se não estiver carregada
        if (!class_exists('Ion_auth')) {
            $this->CI->load->library('ion_auth');
        }
    }

    public function retorno($status, $message, $data, $code)
    {
        $retorno = new stdClass();
        $retorno->status = $status;

        // Verifica se a biblioteca ion_auth já está carregada
        if (class_exists('Ion_auth')) {
            $retorno->logged_in = $this->CI->ion_auth->logged_in() ? true : false;
            $retorno->is_admin = $this->CI->ion_auth->is_admin();
        } else {
            // Se a biblioteca não estiver carregada, define as propriedades como nulas
            $retorno->logged_in = null;
            $retorno->is_admin = null;
        }

        $retorno->message = $message;

        if ($data !== null) {
            $retorno->data = $this->arrayToObject($data);
        }

        $this->response($retorno, $code);
    }

    private function arrayToObject($array)
    {
        if (is_array($array)) {
            $obj = new stdClass();
            foreach ($array as $key => $value) {
                $obj->$key = $this->arrayToObject($value);
            }
            return $obj;
        } else {
            return $array;
        }
    }
}
