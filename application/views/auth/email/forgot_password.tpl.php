<!DOCTYPE html>
<html lang="en">

<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<title>Recuperação de Senha</title>
</head>

<body>

	<table width="100%" border="0" cellspacing="0" cellpadding="0"
		style="max-width: 600px; margin: 0 auto; background-color: #ffffff; border-radius: 10px; overflow: hidden; box-shadow: 0 0 20px rgba(0, 0, 0, 0.1);">
		<tr>
			<td align="center">
				<!-- Adicionando a imagem do logo acima do título -->
				<img src="https://app.infoverse.com.br/assets/images/LogoCompleta.png" alt="Logo"
					style="max-width: 100%; margin-bottom: 100px;">
				<h2 style="color: #007BFF; margin-bottom: 20px;">Recuperação de Senha</h2>
			</td>
		</tr>
		<tr>
			<td>
				<p style="color: #333; line-height: 1.6; margin: 0 0 20px;">Olá,</p>
				<p style="color: #333; line-height: 1.6; margin: 0 0 20px;">Recebemos uma solicitação para redefinir a
					senha da sua conta. Se você não solicitou isso, ignore este e-mail. Caso contrário, clique no link
					abaixo para redefinir sua senha:</p>
				<p style="text-align: center;">
					<a href="https://app.infoverse.com.br/index.php/auth/reset_password/<?php echo $forgotten_password_code; ?>"
						style="color: #fff; text-decoration: none; font-weight: bold; background-color: #007BFF; padding: 10px 20px; border-radius: 5px; display: inline-block;">Redefinir
						Senha</a>
				</p>
				<p style="color: #333; line-height: 1.6; margin: 0 0 20px;">Ou copie e cole o seguinte link no seu
					navegador:</p>
				<p
					style="font-weight: bold; padding: 10px 20px; border-radius: 5px; display: inline-block;">
					<?php echo 'https://app.infoverse.com.br/index.php/auth/reset_password/' . $forgotten_password_code; ?>
				</p>
				<p style="color: #333; line-height: 1.6; margin: 0 0 20px;">Se você tiver algum problema com o link,
					entre em contato conosco pelo e-mail <a href="mailto:suporte@infoverse.com.br"
						style="font-weight: bold;">suporte@infoverse.com.br</a>.</p>
				<p style="color: #333; line-height: 1.6; margin: 0 0 20px;">Atenciosamente,<br>Equipe de Suporte</p>
			</td>
		</tr>
		<tr>
			<td class="footer" style="text-align: center; padding: 20px 0; background-color: #f4f4f4;">
				<p style="color: #666; margin: 0;">© 2023 -
					<?php echo date('Y') ?> Infoverse. Todos os direitos reservados.
				</p>
			</td>
		</tr>
	</table>

</body>

</html>