<?php

namespace ForumPay\PaymentGateway\OpenCartExtension\Model;

/**
 * Encapsulates request parameter
 */
class Request
{
    /**
     * Return expected parameter for Request, throw \InvalidArgumentException otherwise.
     *
     * @param $param
     * @return mixed
     */
    public function getRequired($param)
    {
        $requiredParameter = $this->get($param, null);
        if ($requiredParameter === null) {
            throw new \InvalidArgumentException(sprintf('Missing required parameter %s', $param));
        }

        return $requiredParameter;
    }

    /**
     * Return parameter for Request or default one if request one is not found
     *
     * @param string $param
     * @param string|null $default
     * @return mixed|string|null
     */
    public function get(string $param, ?string $default = null)
    {
        $value = $_GET[$param] ?? null;

        if ($value !== null) {
            return $value;
        }

        // try body parameters
        $bodyParameters = $this->getBodyParameters();
        if (array_key_exists($param, $bodyParameters)) {
            return $bodyParameters[$param];
        }

        return $default;
    }

    /**
     * Returns response body data.
     *
     * @return array|mixed
     */
    public function getBodyParameters() {
        $bodyContent = file_get_contents('php://input');
        return json_decode($bodyContent, true) ?? [];
    }
}
