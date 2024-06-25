<?php defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Class Auth
 * @property Ion_auth|Ion_auth_model $ion_auth        The ION Auth spark
 * @property CI_Form_validation      $form_validation The form validation library
 */
class Webhook extends CI_Controller
{

	public function __construct()
	{
		parent::__construct();
		$this->load->model('Whatsapp_model');

		date_default_timezone_set('America/Cuiaba');
	}

	private function respond($message, $statusCode = 200)
	{
		http_response_code($statusCode);
		echo $message;
	}

	function saveWebhookData($dataJson, $filename = "webhook.json")
	{
		// Verificar se o arquivo já existe
		$mode = file_exists($filename) ? "a+" : "w";

		// Tentar abrir o arquivo
		if ($file = fopen($filename, $mode)) {
			// Converter os dados recebidos para JSON e adicionar uma quebra de linha
			$jsonData = json_encode($dataJson) . "\n";

			// Escrever os dados JSON no arquivo
			fwrite($file, $jsonData);

			// Fechar o arquivo
			fclose($file);

			// Mensagem de sucesso
			echo "Dados do webhook foram salvos com sucesso.";
		} else {
			// Mensagem de erro ao abrir o arquivo
			echo "Não foi possível abrir o arquivo para escrita.";
		}
	}

	private function handleQRCodeWebhook($dataJson)
	{
		if (
			$dataJson !== null &&
			isset($dataJson["attempts"]) &&
			isset($dataJson["result"]) &&
			isset($dataJson["session"]) &&
			isset($dataJson["state"]) &&
			isset($dataJson["status"]) &&
			isset($dataJson["qrcode"]) &&
			isset($dataJson["urlCode"])
		) {
			$dados = [
				"attempts" => $dataJson["attempts"],
				"result" => $dataJson["result"],
				"session" => $dataJson["session"],
				"state" => $dataJson["state"],
				"status" => $dataJson["status"],
				"qrcode" => $dataJson["qrcode"],
				"urlCode" => $dataJson["urlCode"]
			];

			$insert = $this->Whatsapp_model->insertWebhookQrcode($dados);

			$dados = [
				'phone_connected' => false,
				'read_qrcode_error' => false,
				'waiting_qrcode' => false
			];

			$update = $this->Whatsapp_model->updateInstance($dados, $dataJson["session"]);

			if (!$insert) {
				$this->respond("Error insert db.", 400);
				return;
			} else {
				$this->respond("Webhook received successfully.");
				return;
			}
		} else {
			$this->respond("Missing or invalid JSON data.", 400);
			return;
		}
	}

	private function handleConnectionStatusWebhook($dataJson)
	{
		$statusMapping = [
			'CONNECTED' => ['phone_connected' => true, 'read_qrcode_error' => false, 'waiting_qrcode' => false],
			'autocloseCalled' => ['phone_connected' => false, 'read_qrcode_error' => true, 'waiting_qrcode' => false],
			'browserClose' => ['phone_connected' => false, 'read_qrcode_error' => true, 'waiting_qrcode' => false],
			'qrReadError' => ['phone_connected' => false, 'read_qrcode_error' => true, 'waiting_qrcode' => false]
		];

		if (!isset($dataJson["state"]) || !isset($statusMapping[$dataJson["state"]])) {
			$this->respond("Invalid state in JSON data for 'STATUS_CONNECT' webhook.", 400);
			return;
		}

		$update = $this->Whatsapp_model->updateInstance($statusMapping[$dataJson["state"]], $dataJson["session"]);

		if (!$update) {
			$this->respond("Error updating instance for 'STATUS_CONNECT' webhook.", 400);
			return;
		}

		$this->respond("Instance updated successfully for 'STATUS_CONNECT' webhook.");
	}

	private function handleMessageWebhook($dataJson)
	{
		try {
			if (!isset($dataJson["isGroupMsg"]) || !$dataJson["isGroupMsg"]) {
				$this->respond("Missing 'isGroupMsg' key in JSON data for 'SEND_MESSAGE' or 'RECEIVE_MESSAGE' webhook.", 400);
				return;
			}

			$chatId = $dataJson["data"]["chatId"];
			$chatId = strstr($chatId, '@', true); // Remove tudo depois do '@'

			$result = $this->Whatsapp_model->isGruposmonitorado($chatId);

			if (!$result) {
				$this->respond("Group not monitored.", 400);
				return;
			}

			$result = $this->Whatsapp_model->isItemMonitorado($result->cliente_id);
			$monitored = !empty($result);

			// salva os retorno das mensagens em um arquivo
			$this->saveWebhookData($dataJson, 'whats_message.json');

			// Tipos a serem verificados
			$tiposPermitidos = array('sticker', 'text', 'image', 'link'/*, 'audio', 'ptt'*/);

			// Verifica se "type" está definido e se seu valor não está no array de tipos permitidos
			if (isset($dataJson["type"]) && !in_array($dataJson["type"], $tiposPermitidos)) {
				$filename = "SEND_MESSAGE_RECEIVE_MESSAGE.json";
				$jsonData = json_encode($dataJson) . PHP_EOL;

				if (file_put_contents($filename, $jsonData, FILE_APPEND) === false) {
					$this->respond("Error writing to file for 'SEND_MESSAGE' or 'RECEIVE_MESSAGE' webhook.", 400);
					return;
				}

				$this->respond("Webhook data saved successfully for 'SEND_MESSAGE' or 'RECEIVE_MESSAGE' webhook.");
				return;
			}

			$dados = [
				'id_whats' => isset($dataJson["id"]) ? $dataJson["id"] : null,
				'type' => isset($dataJson["type"]) ? $dataJson["type"] : null,
				'mimetype' => isset($dataJson["mimetype"]) ? $dataJson["mimetype"] : null,
				'isGroupMsg' => isset($dataJson["isGroupMsg"]) ? $dataJson["isGroupMsg"] : null,
				'fromMe' => isset($dataJson["fromMe"]) ? $dataJson["fromMe"] : null,
				'session' => isset($dataJson["session"]) ? $dataJson["session"] : null,
				'status' => isset($dataJson["status"]) ? $dataJson["status"] : null,
				'to' => isset($dataJson["to"]) ? $dataJson["to"] : null,
				'from' => isset($dataJson["from"]) ? $dataJson["from"] : null,
				'timestamp' => $dataJson["timestamp"] ?? ($dataJson['data']["t"] ?? null),
				'datetime' => isset($dataJson["datetime"]) ? date('Y-m-d H:i:s', strtotime($dataJson["datetime"])) : null,
				'caption' => isset($dataJson["caption"]) ? $dataJson["caption"] : null,
				'base64' => isset($dataJson["base64"]) ? $dataJson["base64"] : null,
				'content' => isset($dataJson["content"]) ? $dataJson["content"] : (isset($dataJson["url"]) ? $dataJson["url"] : null),
				'url_title' => isset($dataJson["title"]) ? $dataJson["title"] : null,
				'url_description' => isset($dataJson["description"]) ? $dataJson["description"] : null,
				'quotedMsg' => isset($dataJson["quotedMsg"]) ? $dataJson["quotedMsg"] : null,
				'quotedMsgId' => isset($dataJson["quotedMsgId"]) ? $dataJson["quotedMsgId"] : null,
				'data_deprecatedMms3Url' => isset($dataJson['data']["deprecatedMms3Url"]) ? $dataJson['data']["deprecatedMms3Url"] : null,
				'data_directPath' => isset($dataJson['data']["directPath"]) ? $dataJson['data']["directPath"] : null,
				'data_filehash' => isset($dataJson['data']["filehash"]) ? $dataJson['data']["filehash"] : null,
				'data_encFilehash' => isset($dataJson['data']["encFilehash"]) ? $dataJson['data']["encFilehash"] : null,
				'data_mediaKey' => isset($dataJson['data']["mediaKey"]) ? $dataJson['data']["mediaKey"] : null,
				'data_mediaKeyTimestamp' => isset($dataJson['data']["mediaKeyTimestamp"]) ? $dataJson['data']["mediaKeyTimestamp"] : null,
				'data_chatId' => isset($dataJson['data']["chatId"]) ? $dataJson['data']["chatId"] : null,
				'sender_id' => isset($dataJson['data']['sender']["id"]) ? $dataJson['data']['sender']["id"] : null,
				'sender_name' => isset($dataJson['data']['sender']["name"]) ? $dataJson['data']['sender']["name"] : null,
				'sender_shortName' => isset($dataJson['data']['sender']["shortName"]) ? $dataJson['data']['sender']["shortName"] : null,
				'sender_pushname' => isset($dataJson['data']['sender']["pushname"]) ? $dataJson['data']['sender']["pushname"] : null,
				'sender_verifiedName' => isset($dataJson['data']['sender']["verifiedName"]) ? $dataJson['data']['sender']["verifiedName"] : null,
				'sender_type' => isset($dataJson['data']['sender']["type"]) ? $dataJson['data']['sender']["type"] : null,
				'sender_isBusiness' => isset($dataJson['data']['sender']["isBusiness"]) ? $dataJson['data']['sender']["isBusiness"] : null,
				'sender_isEnterprise' => isset($dataJson['data']['sender']["isEnterprise"]) ? $dataJson['data']['sender']["isEnterprise"] : null,
				'sender_isSmb' => isset($dataJson['data']['sender']["isSmb"]) ? $dataJson['data']['sender']["isSmb"] : null,
				'mediaData_type' => isset($dataJson['data']['mediaData']["type"]) ? $dataJson['data']['mediaData']["type"] : null,
				'mediaData_mediaStage' => isset($dataJson['data']['mediaData']["mediaStage"]) ? $dataJson['data']['mediaData']["mediaStage"] : null,
				'mediaData_animationDuration' => isset($dataJson['data']['mediaData']["animationDuration"]) ? $dataJson['data']['mediaData']["animationDuration"] : null,
				'mediaData_animatedAsNewMsg' => isset($dataJson['data']['mediaData']["animatedAsNewMsg"]) ? $dataJson['data']['mediaData']["animatedAsNewMsg"] : null,
				'mediaData_isViewOnce' => isset($dataJson['data']['mediaData']["isViewOnce"]) ? $dataJson['data']['mediaData']["isViewOnce"] : null,
				'mediaData_swStreamingSupported' => isset($dataJson['data']['mediaData']["_swStreamingSupported"]) ? $dataJson['data']['mediaData']["_swStreamingSupported"] : null,
				'mediaData_listeningToSwSupport' => isset($dataJson['data']['mediaData']["_listeningToSwSupport"]) ? $dataJson['data']['mediaData']["_listeningToSwSupport"] : null,
				'mediaData_isVcardOverMmsDocument' => isset($dataJson['data']['mediaData']["isVcardOverMmsDocument"]) ? $dataJson['data']['mediaData']["isVcardOverMmsDocument"] : null,
			];

			$result = $this->Whatsapp_model->insertWhatsMessage($dados);

			if (!$result) {
				$this->respond("Failed to save webhook data for 'SEND_MESSAGE' or 'RECEIVE_MESSAGE' webhook.", 400);
			}

			$this->respond("Webhook data saved successfully for 'SEND_MESSAGE' or 'RECEIVE_MESSAGE' webhook.");

			/*$filename = "SEND_MESSAGE_RECEIVE_MESSAGE.json";
			$jsonData = json_encode($dataJson) . PHP_EOL;
	
			if (file_put_contents($filename, $jsonData, FILE_APPEND) === false) {
				$this->respond("Error writing to file for 'SEND_MESSAGE' or 'RECEIVE_MESSAGE' webhook.", 400);
				return;
			}
	
			$this->respond("Webhook data saved successfully for 'SEND_MESSAGE' or 'RECEIVE_MESSAGE' webhook.");*/
		} catch (Exception $e) {
			// Trata a exceção capturada
			$errorMessage = "Error: " . $e->getMessage() . PHP_EOL;
			$errorFile = "error.txt";
			file_put_contents($errorFile, $errorMessage, FILE_APPEND);
		}
	}

	public function whatsapp()
	{

		if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
			$this->respond("Method not allowed.", 405);
			return;
		}

		$payload = file_get_contents('php://input');
		$dataJson = json_decode($payload, true);

		if (!$dataJson) {
			$this->respond("Error decoding JSON data.", 400);
			return;
		}

		if (!isset($dataJson["wook"])) {
			$this->respond("Missing 'wook' key in JSON data.", 400);
			return;
		}

		switch ($dataJson["wook"]) {
			case 'QRCODE':
				$this->handleQRCodeWebhook($dataJson);
				break;
			case 'STATUS_CONNECT':
				$this->handleConnectionStatusWebhook($dataJson);
				break;
			case 'SEND_MESSAGE':
			case 'RECEIVE_MESSAGE':
				$this->handleMessageWebhook($dataJson);
				break;
			default:
				// Verificar se o arquivo webhook.json já existe
				if (file_exists("webhook.json")) {
					// Abrir o arquivo webhook.json para leitura e escrita (append)
					$file = fopen("webhook.json", "a+");
				} else {
					// Se o arquivo não existir, criar e abrir para escrita
					$file = fopen("webhook.json", "w");
				}

				// Verificar se o arquivo foi aberto corretamente
				if ($file !== false) {
					// Converter os dados recebidos de volta para JSON
					$jsonData = json_encode($dataJson);

					// Adicionar uma quebra de linha para separar os dados
					$jsonData .= "\n";

					// Escrever os dados JSON no arquivo
					fwrite($file, $jsonData);

					// Fechar o arquivo
					fclose($file);

					echo "Dados do webhook foram salvos com sucesso.";
				} else {
					echo "Não foi possível abrir o arquivo para escrita.";
				}
				$this->respond("Invalid webhook type.", 400);
		}
	}
}
