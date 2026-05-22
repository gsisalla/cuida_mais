<?php
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';
    $remember = $_POST['rememberusername'] ?? '0';

    $loginUrl = 'https://capacitacao.proj.ufsm.br/login/index.php';

    // 1. Inicia o cURL para obter a página inicial de login e capturar o cookie/token obrigatório do Moodle
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $loginUrl);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    // Cria um arquivo de sessão temporária para guardar os cookies do Moodle
    curl_setopt($ch, CURLOPT_COOKIEJAR, tmpfile()); 
    
    $response = curl_exec($ch);

    // Captura o 'logintoken' usando expressão regular dentro do HTML do Moodle
    preg_match('/name="logintoken" value="([^"]+)"/', $response, $matches);
    $token = $matches[1] ?? '';

    // 2. Prepara os dados exatos de autenticação que o servidor da UFSM exige
    $postFields = [
        'username' => $username,
        'password' => $password,
        'logintoken' => $token,
        'rememberusername' => $remember
    ];

    // 3. Faz a requisição POST simulando o envio real dos dados
    curl_setopt($ch, CURLOPT_URL, $loginUrl);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($postFields));
    curl_setopt($ch, CURLOPT_HEADER, true); // Necessário para capturar o redirecionamento de sucesso
    
    $result = curl_exec($ch);
    curl_close($ch);

    // 4. Verifica se as credenciais estão corretas. Se estiverem, o Moodle envia um cabeçalho de sessão válida
    if (strpos($result, 'MoodleSessionCapacitacao') !== false || strpos($result, 'Location:') !== false) {
        // Sucesso! O servidor aceitou o login. 
        // Abaixo, criamos um formulário invisível que faz o navegador do usuário entrar autenticado no portal
        echo '
        <form id="redirectForm" action="'.$loginUrl.'" method="POST">
            <input type="hidden" name="username" value="'.htmlspecialchars($username).'">
            <input type="hidden" name="password" value="'.htmlspecialchars($password).'">
            <input type="hidden" name="logintoken" value="'.htmlspecialchars($token).'">
        </form>
        <script>document.getElementById("redirectForm").submit();</script>';
        exit;
    } else {
        // Se falhar (usuário ou senha errados), avisa e volta para a sua tela inicial customizada
        echo "<script>alert('Falha na autenticação. Verifique seu usuário e senha.'); window.location.href='index.html';</script>";
    }
}
?>