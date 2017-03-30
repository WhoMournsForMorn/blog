<?php
require_once __DIR__.'/../vendor/autoload.php';

$app = new Silex\Application();


$app['debug'] = true;

$app->register(new Silex\Provider\DoctrineServiceProvider(), array(
    'db.options' => array(
        'driver'   => 'pdo_mysql',
        'host'     => 'localhost:3360',
        'dbname'   => 'ppn_blog',
        'user'     => 'root',
        'password' => 'root',
        'charset'  => 'utf8mb4',
    ),
));

$app['blogposts'] = $app['db']->fetchAll('SELECT * FROM blog_posts BP INNER JOIN blog_authors BA ON BP.authorID = BA.authorID WHERE BP.postDate > DATE_SUB(now(), INTERVAL 12 MONTH) ORDER BY BP.postDate DESC');

$app->get('/', function (Silex\Application $app)  {
    return $app['twig']->render(
        'index.html.twig',
        array(
            'blogposts' => $app['blogposts'],
        )
    );
})->bind('index');

$app->register(new Silex\Provider\TwigServiceProvider(), array(
    'twig.path' => __DIR__.'/../templates',
));

$app->get('/post/{id}', function (Silex\Application $app, $id)  {
    if (!array_key_exists($id - 1, $app['blogposts'])) {
        $app->abort(404, 'The blog post could not be found');
    }
    $blogpost = $app['blogposts'][$id - 1];
    return $app['twig']->render(
        'blogpost.html.twig',
        array(
            'blogpost' => $blogpost,
        )
    );
})
    ->assert('id', '\d+')
    ->bind('blogpost');

$app->get('/archive/{year}/{month}/post/{id}', function (Silex\Application $app, $year, $month, $id)  {
    if (!array_key_exists($id - 1, $app['blogposts'])) {
        $app->abort(404, 'The blog post could not be found');
    }
    $blogpost = $app['blogposts'][$id - 1];
    return $app['twig']->render(
        'archiveblogpost.html.twig',
        array(
            'blogpost' => $blogpost,
        )
    );
})
    ->assert('id', '\d+')
    ->bind('archiveblogpost');

$app->get('/archive/{year}/{month}', function (Silex\Application $app, $year, $month) {
    $arr = array();

    foreach ($app['blogposts'] as $blogpost) {

        $postMonth = date_format(new DateTime($blogpost['postDate']), 'F');
        $postYear = date_format(new DateTime($blogpost['postDate']), 'Y');

        if ($postYear == $year && $postMonth == $month) {
           array_push($arr, $blogpost);
        }
    }
    return $app['twig']->render(
        'archive.html.twig',
        array(
            'blogposts' => $arr,
        )
    );
})->assert('year', '\d{4}')
  ->assert('month', '\w{3,9}?')
  ->bind('archive');



$app->register(new Silex\Provider\UrlGeneratorServiceProvider());


$app->run();
?>