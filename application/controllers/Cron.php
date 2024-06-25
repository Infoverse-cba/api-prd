<?php defined('BASEPATH') or exit('No direct script access allowed');

class Cron extends CI_Controller
{
	public $data = [];

	public function __construct()
	{
		parent::__construct();
		//$this->load->database();

		$this->load->model('Cron_model', 'cron');
	}

	private function respond($message, $statusCode = 200)
	{
		http_response_code($statusCode);
		echo $message;
	}

	/*public function whats_alert()
	{
		// Carregar a biblioteca TextAnalyzer
        $this->load->library('textanalyzer');

		$messages = $this->cron->getMessages();

		// Verifica se $clientes é um array válido antes de iterar sobre ele
		if (!is_array($messages) || empty($messages)) {
			$this->respond("Nenhuma nova mensagem encontrada.");
			return;
		}

		// Loop pelas mensagens
		foreach ($messages as $message) {

			$clienteId = explode("-", $message->session);
			$clienteId = $clienteId[0];

			$gruposMonitorados = $this->cron->getGruposMonitorados($clienteId, $message->session);

			// Verifica se $gruposMonitorados é um array válido antes de iterar sobre ele
			if (!is_array($gruposMonitorados) || empty($gruposMonitorados)) {
				continue; // Pula para a próxima mensagem se não houver grupos monitorados
			}

			// Loop pelos grupos monitorados
			foreach ($gruposMonitorados as $grupoMonitorado) {

				$groupId = strstr($message->data_chatId, '@', true);

				if ($groupId == $grupoMonitorado->group_id) {

					$itensMonitorados = $this->cron->getItensMonitorados($clienteId, $grupoMonitorado->instance_id, $grupoMonitorado->id);

					// Verifica se $instances é um array válido antes de iterar sobre ele
					if (!is_array($itensMonitorados) || empty($itensMonitorados)) {
						continue; // Pula para o próximo cliente se não houver instancias
					}

					// Loop pelos clientes
					foreach ($itensMonitorados as $itemMonitorado) {

						// Verificar se o remetente é um membro do grupo
						if ($itemMonitorado->is_member == 't') {
							$senderId = strstr($message->sender_id, '@', true); // Remove tudo depois do '@'

							// Verificar se o remetente corresponde ao valor monitorado
							if ($itemMonitorado->value == $senderId) {
								$dados = [
									"cliente_id" => $clienteId,
									"instance_id" => $grupoMonitorado->instance_id,
									"monitored_group_id" => $grupoMonitorado->id,
									"monitored_item_id" => $itemMonitorado->id,
									"message_id" => $message->id
								];

								$alerts = $this->cron->insertWhatsAlert($dados);

								if ($alerts) {
									echo "Cliente ID: " . $clienteId . ", Instance ID: " . $grupoMonitorado->instance_id . ", GrupoMonitorado ID: " . $grupoMonitorado->id . ", ItemMonitorado ID: " . $itemMonitorado->id .  ", Message ID: " . $message->id . "<br>";
								}
							}
						}
						// Verificar se o remetente não é um membro do grupo
						elseif ($itemMonitorado->is_member == 'f') {
							$texto = $message->content;
							$palavra = $itemMonitorado->value;

							// Remover acentos da palavra a ser buscada
							$palavra_sem_acentos = preg_replace('/[^\p{L}]/u', '', $palavra);

							// Utilizar expressão regular com o modificador 'iu' para busca case insensitive e unicode
							if (preg_match("/\b$palavra_sem_acentos\b/iu", $texto)) {
								$dados = [
									"cliente_id" => $clienteId,
									"instance_id" => $grupoMonitorado->instance_id,
									"monitored_group_id" => $grupoMonitorado->id,
									"monitored_item_id" => $itemMonitorado->id,
									"message_id" => $message->id
								];

								$alerts = $this->cron->insertWhatsAlert($dados);

								if ($alerts) {
									echo "Cliente ID: " . $clienteId . ", Instance ID: " . $grupoMonitorado->instance_id . ", GrupoMonitorado ID: " . $grupoMonitorado->id . ", ItemMonitorado ID: " . $itemMonitorado->id .  ", Message ID: " . $message->id . "<br>";
								}
							}
						}
					}
				}
			}

			$this->cron->updateWhatsMessage(["cron_checked" => true], $message->id);
		}
	}*/

	public function whats_alert()
	{
		// Carregar a biblioteca TextAnalyzer
		$this->load->library('TextAnalyzer');

		$messages = $this->cron->getMessages();

		// Verifica se $messages é um array válido antes de iterar sobre ele
		if (!is_array($messages) || empty($messages)) {
			$this->respond("Nenhuma nova mensagem encontrada.");
			return;
		}

		// Loop pelas mensagens
		foreach ($messages as $message) {
			$clienteId = explode("-", $message->session);
			$clienteId = $clienteId[0];

			$gruposMonitorados = $this->cron->getGruposMonitorados($clienteId, $message->session);

			// Verifica se $gruposMonitorados é um array válido antes de iterar sobre ele
			if (!is_array($gruposMonitorados) || empty($gruposMonitorados)) {
				continue; // Pula para a próxima mensagem se não houver grupos monitorados
			}

			// Loop pelos grupos monitorados
			foreach ($gruposMonitorados as $grupoMonitorado) {
				$groupId = strstr($message->data_chatId, '@', true);

				if ($groupId == $grupoMonitorado->group_id) {
					$itensMonitorados = $this->cron->getItensMonitorados($clienteId, $grupoMonitorado->instance_id, $grupoMonitorado->id);

					// Verifica se $itensMonitorados é um array válido antes de iterar sobre ele
					if (!is_array($itensMonitorados) || empty($itensMonitorados)) {
						continue; // Pula para o próximo cliente se não houver instancias
					}

					// Loop pelos itens monitorados
					foreach ($itensMonitorados as $itemMonitorado) {
						// Verificar se o remetente é um membro do grupo
						if ($itemMonitorado->is_member == 't') {
							$senderId = strstr($message->sender_id, '@', true); // Remove tudo depois do '@'

							// Verificar se o remetente corresponde ao valor monitorado
							if ($itemMonitorado->value == $senderId) {
								$dados = [
									"cliente_id" => $clienteId,
									"instance_id" => $grupoMonitorado->instance_id,
									"monitored_group_id" => $grupoMonitorado->id,
									"monitored_item_id" => $itemMonitorado->id,
									"message_id" => $message->id
								];

								$alerts = $this->cron->insertWhatsAlert($dados);

								if ($alerts) {
									echo "Cliente ID: " . $clienteId . ", Instance ID: " . $grupoMonitorado->instance_id . ", GrupoMonitorado ID: " . $grupoMonitorado->id . ", ItemMonitorado ID: " . $itemMonitorado->id .  ", Message ID: " . $message->id . "<br>";
								}
							}
						}
						// Verificar se o remetente não é um membro do grupo
						elseif ($itemMonitorado->is_member == 'f') {
							$texto = $message->content;
							$url_title = $message->url_title;
							$url_description = $message->url_description;
							$palavra = $itemMonitorado->value;

							// Utilizar os métodos da biblioteca para todas as verificações
							$palavraEncontrada =
								$this->textanalyzer->findWordInText($texto, $palavra) ||
								$this->textanalyzer->findPhraseInText($texto, $palavra) ||
								$this->textanalyzer->findWordInLinks($texto, $palavra) ||
								$this->textanalyzer->findPhraseInLinks($texto, $palavra) ||
								$this->textanalyzer->findWordInText($url_title, $palavra) ||
								$this->textanalyzer->findPhraseInText($url_title, $palavra) ||
								$this->textanalyzer->findWordInText($url_description, $palavra) ||
								$this->textanalyzer->findPhraseInText($url_description, $palavra);

							if ($palavraEncontrada) {
								$dados = [
									"cliente_id" => $clienteId,
									"instance_id" => $grupoMonitorado->instance_id,
									"monitored_group_id" => $grupoMonitorado->id,
									"monitored_item_id" => $itemMonitorado->id,
									"message_id" => $message->id
								];

								$alerts = $this->cron->insertWhatsAlert($dados);

								if ($alerts) {
									echo "Cliente ID: " . $clienteId . ", Instance ID: " . $grupoMonitorado->instance_id . ", GrupoMonitorado ID: " . $grupoMonitorado->id . ", ItemMonitorado ID: " . $itemMonitorado->id .  ", Message ID: " . $message->id . "<br>";
								}
							}
						}
					}
				}
			}

			$this->cron->updateWhatsMessage(["cron_checked" => true], $message->id);
		}
	}
}
