<?php

require '../vendor/autoload.php';
mb_internal_encoding('UTF-8');
try { //Otra opción con un manejador de excepciones: https://stackoverflow.com/questions/15245184/log-caught-exception-with-stack-trace
    $dotenv = Dotenv\Dotenv::createImmutable('../');
    $dotenv->load();
    Com\Daw2\Core\FrontController::main();
} catch (\Exception $e) {
    http_response_code(500);
    error_log($e);
    if ($_ENV['app.debug']) {
        echo json_encode(
            [
                'message' => $e->getMessage(),
                'trace'   => $e->getTraceAsString()
            ]);
    }
}
