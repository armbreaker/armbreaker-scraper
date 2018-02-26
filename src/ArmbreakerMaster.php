<?php

/*
 * Copyright (c) 2018 Armbreaker Developers.
 * Use of this source code is governed by the MIT license, which
 * can be found in the LICENSE file.
 */

namespace Armbreaker;

use Slim\Http\Request as Request;
use Slim\Http\Response as Response;

/**
 * This is our public view i.e., the website and API. It'll queue up stuff for
 * the scrapers as well.
 *
 * @author sylae and skyyrunner
 */
class ArmbreakerMaster extends ArmbreakerEntity
{

    /**
     * Mostly this is setting up Slim.
     */
    public function __construct()
    {
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

    /**
     * @todo move the callables to their own functions.
     */
    protected function setupEndpoints()
    {
        $this->slim->get('/', function (Request $request, Response $response, $args) {
            return $this->view->render($response, 'index.twig', []);
        });
        $this->slim->get('/viewer', function (Request $request, Response $response) {
            return $this->view->render($response, 'browsefics.twig', []);
        });
        $this->slim->get('/viewer/{id}', function (Request $request, Response $response) {
            $id = $request->getAttribute('id'); // unused
            return $this->view->render($response, 'dist/main.html', []);
        });
        $this->slim->get('/api', function (Request $request, Response $response) {
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

        $this->slim->get('/api/fics', function (Request $request, Response $response) {
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


        $this->slim->get('/api/fics/{id}', function (Request $request, Response $response) {
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

        // @TODO split this off to its own class or something, tbh.
        $sqs = $this->sqs;
        $this->slim->get('/api/scrape/{id}', function (Request $request, Response $response) use ($sqs) {
            try {
                $x     = new FicScraper($request->getAttribute('id'));
                $x->scrapePostInfo();
                $fic   = new Fic($x->id, $x->name);
                $queue = [];
                foreach ($x->posts as $post) {
                    $isolated = new Post($post->id, $fic, $post->title, $post->time);
                    $queue[]  = [
                        'Id'                => $post->id * time(),
                        'MessageBody'       => json_encode([
                            'post' => $isolated,
                            'fic'  => $fic,
                        ]),
                        'MessageAttributes' => [
                            "command" => [
                                'DataType'    => "String",
                                'StringValue' => 'scrapePost',
                            ],
                            "from"    => [
                                'DataType'    => "Number",
                                'StringValue' => ConfigFactory::get()['id'],
                            ]
                        ],
                    ];
                }
                foreach (array_chunk($queue, 10) as $payload) {
                    $sqs->sendMessageBatch([
                        'QueueUrl' => ConfigFactory::get()['sqsURL'],
                        'Entries'  => $payload,
                    ]);
                }
                return $response->withJson([
                            'request' => true,
                ]);
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
