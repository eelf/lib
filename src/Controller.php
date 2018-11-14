<?php
/**
 * @author Evgeniy Makhrov <emakhrov@gmail.com>
 */

namespace lib;

abstract class Controller {
    /** @var Request */
    protected $request;
    /** @var Response */
    protected $response;
    /** @var Router */
    protected $router;

    public function __construct($request, $response, $router) {
        $this->request = $request;
        $this->response = $response;
        $this->router = $router;
    }
    public function init(Request $request, Response $response) {}
    public function finish() {}

    protected function redirect($action, $params = [], $query = []) {
        return $this->response->redirect($this->router->getUrl($action, $params, $query));
    }
}
