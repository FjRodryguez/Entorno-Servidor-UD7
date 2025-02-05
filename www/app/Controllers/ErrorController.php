<?php

declare(strict_types=1);

namespace Com\Daw2\Controllers;

use Com\Daw2\Core\BaseController;
use Com\Daw2\Libraries\Respuesta;

class ErrorController extends BaseController
{
    public function errorWithBody(int $statusCode, array $body): void
    {
        $respuesta = new Respuesta($statusCode, $body);
        $this->view->show('json.view.php', ['respuesta' => $respuesta]);
    }
}
