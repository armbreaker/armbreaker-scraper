<?php

/*
 * The MIT License
 *
 * Copyright 2017 sylae and skyyrunner.
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in
 * all copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
 * THE SOFTWARE.
 */

namespace Armbreaker;

use Slim\Http\Request as Request;
use Slim\Http\Response as Response;

/**
 * Description of ArmbreakerMaster
 *
 * @author sylae and skyyrunner
 */
class ArmbreakerMaster extends ArmbreakerEntity {

  public function __construct() {
    parent::__construct();

    // Get container
    $container = $this->slim->getContainer();

    $container['view'] = function ($c) {
      $view = new \Slim\Views\Twig('client', [
          'cache' => false,
      ]);

      // Instantiate and add Slim specific extension
      $basePath = rtrim(str_ireplace('index.php', '', $c['request']->getUri()->getBasePath()), '/');
      $view->addExtension(new \Slim\Views\TwigExtension($c['router'], $basePath));

      return $view;
    };
    $container['notFoundHandler'] = function ($c) {
      return function (Request $request, Response $response) use ($c) {
        if (substr($request->getUri()->getPath(), 0, 5) == "/api/") {
          $err = [
              'error'         => true,
              'error_message' => "Endpoint not found. GET /api for help.",
          ];
          return $c['response']->withJson($err, 404);
        } else {
          return $c['view']->render($response, '404.twig', [
                      'path' => $request->getUri()->getPath()
          ]);
        }
      };
    };
    $this->setupEndpoints();
    $this->slim->run();
  }

  protected function setupEndpoints() {
    $this->slim->get('/', function (Request $request, Response $response, $args) {
      return $this->view->render($response, 'index.twig', []);
    });
    $this->slim->get('/viewer', function(Request $request, Response $response) {
      return $this->view->render($response, 'browsefics.twig', []);
    });
    $this->slim->get('/viewer/{id}', function(Request $request, Response $response) {
      $id = $request->getAttribute('id'); // unused
      return $this->view->render($response, 'dist/main.html', []);
    });
    $this->slim->get('/api', function(Request $request, Response $response) {
      $methods = [];

      $methods['GET /api']           = [
          'description' => 'Get help',
          'args'        => null,
      ];
      $methods['GET /api/fics']      = [
          'description' => 'Get a list of fics',
          'args'        => null,
      ];
      $methods['GET /api/fics/{id}'] = [
          'description' => 'Get a dump of a fic\'s info',
          'args'        => [
              'id' => [
                  'type'        => 'int',
                  'description' => 'Spacebattles topic ID.',
              ],
          ],
      ];

      return $response->withJson($methods);
    });

    $this->slim->get('/api/fics', function(Request $request, Response $response) {
      try {
        $fics = FicFactory::getAllFics();
        foreach ($fics as $fic) {
          // $fic->loadPosts();
        }
        return $response->withJson($fics);
      } catch (\Throwable $e) {
        $err = [
            'error'         => true,
            'error_message' => $e->getMessage(),
            'error_trace'   => $e->getTraceAsString(),
        ];
        return $response->withJson($err, 500);
      }
    });


    $this->slim->get('/api/fics/{id}', function(Request $request, Response $response) {
      try {
        $fic = FicFactory::getFic($request->getAttribute('id'));
        $fic->loadPosts(true);
        $fic->setPrintMode(true);
        return $response->withJson($fic);
      } catch (NotFoundError $e) {
        $err = [
            'error'         => true,
            'error_message' => $e->getMessage(),
        ];
        return $response->withJson($err, 404);
      } catch (\Throwable $e) {
        $err = [
            'error'         => true,
            'error_message' => $e->getMessage(),
            'error_trace'   => $e->getTraceAsString(),
        ];
        return $response->withJson($err, 500);
      }
    });
  }

}
