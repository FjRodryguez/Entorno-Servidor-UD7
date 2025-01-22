<?php

declare(strict_types=1);

namespace Com\Daw2\Traits;

trait RestController
{
    private ?array $params = null;

    protected function getParams(): array
    {
        if (is_null($this->params)) {
            $this->params = $this->getBodyData();
        }
        return $this->params;
    }

    /**
     * Parsea el contenido del body de petición. Usualmente con PUT, PATCH.
     * Si recibe como CONTENT_TYPE = 'application/json' parsea el body como un json.
     * Si recibe application/x-www-form-urlencoded asume var1=valor1&var2=valor2
     * Si es multipart lo trata como tal
     * En caso contrario asume formato var1=valor1&var2=valor2
     * Almacena el contenido parseado en atributo privado para tenerlo accesible durante toda la vida de la clase
     * @return array Devuelve un array clave=>valor con los parámetros recibidos en el cuerpo de la petición
     */
    private function getBodyData(): array
    {
        $request = file_get_contents("php://input");
        if (!empty($request)) {
            $contentType = $_SERVER["CONTENT_TYPE"] ?? 'plain/text';
            if ($contentType === 'application/json') {
                $params = json_decode($request, true);
            } elseif ($contentType === 'application/x-www-form-urlencoded') {
                parse_str($request, $params);
            } elseif (str_starts_with($contentType, 'multipart/form-data')) {
                $params = $this->getMultipartData();
            } else {
                parse_str($request, $params);
            }
            return $params;
        } else {
            return array();
        }
    }

    private function getMultipartData(): array
    {
        // Fetch content and determine boundary
        $raw_data = file_get_contents('php://input');
        $boundary = substr($raw_data, 0, strpos($raw_data, "\r\n"));

        // Fetch each part
        $parts = array_slice(explode($boundary, $raw_data), 1);
        $data = array();

        foreach ($parts as $part) {
            // If this is the last part, break
            if ($part == "--\r\n") {
                break;
            }

            // Separate content from headers
            $part = ltrim($part, "\r\n");
            list($raw_headers, $body) = explode("\r\n\r\n", $part, 2);

            // Parse the headers list
            $raw_headers = explode("\r\n", $raw_headers);
            $headers = array();
            foreach ($raw_headers as $header) {
                list($name, $value) = explode(':', $header);
                $headers[strtolower($name)] = ltrim($value, ' ');
            }

            // Parse the Content-Disposition to get the field name, etc.
            if (isset($headers['content-disposition'])) {
                $filename = null;
                preg_match(
                    '/^(.+); *name="([^"]+)"(; *filename="([^"]+)")?/',
                    $headers['content-disposition'],
                    $matches
                );
                list(, $type, $name) = $matches;
                isset($matches[4]) and $filename = $matches[4];

                // handle your fields here
                switch ($name) {
                    // this is a file upload
                    case 'userfile':
                        file_put_contents($filename, $body);
                        break;

                    // default for all other files is to populate $data
                    default:
                        $data[$name] = substr($body, 0, strlen($body) - 2);
                        break;
                }
            }
        }
        return $data;
    }
}
