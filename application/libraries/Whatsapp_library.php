<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Whatsapp_library
{

    protected $CI;
    protected $ApiSsl;
    protected $ApiUrl;
    protected $ApiPort;
    protected $Token;
    protected $Webhook;
    protected $Protocol;

    public function __construct()
    {
        $this->CI = &get_instance();
        $this->CI->load->config('whatsapp', true); // Carrega o arquivo de configuração

        // Carregar configurações
        $this->ApiSsl = $this->CI->config->item('whatsapp_api_ssl', 'whatsapp');
        $this->ApiUrl = $this->CI->config->item('whatsapp_api_url', 'whatsapp');
        $this->ApiPort = $this->CI->config->item('whatsapp_api_port', 'whatsapp');
        $this->Token = $this->CI->config->item('whatsapp_api_token', 'whatsapp');
        $this->Webhook = $this->CI->config->item('whatsapp_api_webhook', 'whatsapp');

        // Validar e configurar protocolo
        $this->Protocol = ($this->ApiSsl === true) ? 'https://' : 'http://';

        // Validar e configurar porta
        $this->ApiPort = is_bool($this->ApiPort) ? '/' : (is_int($this->ApiPort) ? ':' . $this->ApiPort . '/' : die('Erro: o Parametro "whatsapp_api_port" dever ser int.'));
    }

    protected function executeCurlRequest($endpoint, $instance_key, $postData = null)
    {
        // Configurar a solicitação cURL
        $curl = curl_init();
        curl_setopt_array($curl, array(
            CURLOPT_URL => $this->Protocol . $this->ApiUrl . $this->ApiPort . $endpoint,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => $postData ? 'POST' : 'GET',
            CURLOPT_POSTFIELDS => $postData,
            CURLOPT_HTTPHEADER => array(
                'Content-Type: application/json',
                'apitoken: ' . $this->Token,
                'sessionkey: ' . $instance_key
            ),
        ));

        // Executar a solicitação e capturar a resposta
        $response = curl_exec($curl);
        $err = curl_error($curl);

        curl_close($curl);

        // Lidar com erros
        if ($err) {
            return "cURL Error #:" . $err;
        } else {
            return json_decode($response); // Retorna o objeto decodificado do JSON
        }
    }

    public function SessionStatus($instance_key)
    {
        return $this->executeCurlRequest('getConnectionStatus', $instance_key, json_encode(array("session" => $instance_key)));
    }

    public function SessionState($instance_key)
    {
        return $this->executeCurlRequest('getConnectionState', $instance_key, json_encode(array("session" => $instance_key)));
    }

    public function SessionQRCode($instance_key)
    {
        return $this->executeCurlRequest('getQrCode?session=' . $instance_key . '&sessionkey=' . $instance_key, $instance_key);
    }

    public function SessionLogout($instance_key)
    {
        return $this->executeCurlRequest('logout', $instance_key, json_encode(array("session" => $instance_key)));
    }

    public function SessionClose($instance_key)
    {
        return $this->executeCurlRequest('close', $instance_key, json_encode(array("session" => $instance_key)));
    }

    public function SessionDelete($instance_key)
    {
        return $this->executeCurlRequest('deleteSession', $instance_key, json_encode(array("session" => $instance_key)));
    }

    public function AllGroups($instance_key)
    {
        return $this->executeCurlRequest('getAllGroups', $instance_key, json_encode(array("session" => $instance_key)));
    }

    public function GroupMembers($instance_key, $group_id)
    {
        return $this->executeCurlRequest('getGroupMembers', $instance_key, json_encode(array("session" => $instance_key, "groupid" => $group_id)));
    }

    public function GroupAdmins($instance_key, $group_id)
    {
        return $this->executeCurlRequest('getGroupAdmins', $instance_key, json_encode(array("session" => $instance_key, "groupid" => $group_id)));
    }

    public function GroupInviteLink($instance_key, $group_id)
    {
        return $this->executeCurlRequest('getGroupInviteLink', $instance_key, json_encode(array("session" => $instance_key, "groupid" => $group_id)));
    }

    public function SessionStart($instance_key)
    {
        // Construir dados para a solicitação POST
        $postData = json_encode(array(
            "session" => $instance_key,
            "poweredBy" => $instance_key,
            "deviceName" => $instance_key,
            "useChrome" => false,
            "autoClose" => 90000,
            "autorejectcall" => false,
            "wh_connect" => $this->Webhook,
            "wh_qrcode" => $this->Webhook,
            "wh_status" => $this->Webhook,
            "wh_message" => $this->Webhook,
            "answermissedcall" => "Olá, desculpa não posso atender chamadas, por favor mande mensagem de texto."
        ));

        return $this->executeCurlRequest('start', $instance_key, $postData);
    }
}
