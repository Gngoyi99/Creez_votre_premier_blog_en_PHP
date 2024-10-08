<?php
require_once 'vendor/autoload.php';

use Blog\Twig\Controller\Main\MainController;
use Blog\Twig\Controller\User\UserController;
use Blog\Twig\Controller\Post\PostController;
use Blog\Twig\Controller\Comment\CommentController;
use Blog\Twig\Controller\Error\ErrorController;
use Twig\Loader\FilesystemLoader;
use Twig\Environment;

// Définir le chemin racine du projet
define('BASE_PATH', __DIR__);

// Démarrer la session
session_start();

// Configuration de Twig
$loader = new FilesystemLoader(BASE_PATH . '/src/Template/');
$twig = new Environment($loader);

// Charger la configuration de la db
$configPath = BASE_PATH . '/config/db.php';
if (!file_exists($configPath)) {
    throw new Exception("Configuration file not found: $configPath");
}
$config = require $configPath;

// Initialiser la connexion à la db
try {
    $dsn = "mysql:host={$config['host']};dbname={$config['dbname']};charset={$config['charset']}";
    $db = new PDO($dsn, $config['user'], $config['password']);
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Erreur de connexion à la base de données : " . $e->getMessage());
}

// Initialisation des contrôleurs
$mainController = new MainController($twig, $db);
$userController = new UserController($twig, $db);
$postController = new PostController($twig, $db);
$commentController = new CommentController($twig, $db);
$errorController = new ErrorController($twig, $db);

// Fonction pour gérer les routes
function handleRoute($mainController, $userController, $postController, $commentController, $errorController) {
    $uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

    // Si l'URI commence par /BlogPHP, on enlève cette partie le routage
    if (strpos($uri, '/BlogPHP') === 0) {
        $uri = substr($uri, strlen('/BlogPHP'));
    }

    $segments = explode('/', trim($uri, '/'));

    switch ($segments[0]) {
        case '':
        case 'index.php':
            // Rediriger vers la page d'accueil
            header('Location: /BlogPHP/home');
            break;
        case 'home':
            $mainController->home();
            break;
        case 'userLogin':
            $userController->userLogin();
            break;
        case 'userRegister':
            $userController->userRegister();
            break;
        case 'editProfile':
            $userController->editProfile();
            break;
        case 'listPost':
            $postController->listPost();
            break;
        case 'showPost':
            if (isset($segments[1]) && is_numeric($segments[1])) {
                $postController->showPost($segments[1]);
            } else {
                $errorController->notFound($uri);
            }
            break;
        case 'addPost':
            $postController->addPost();
            break;
        case 'editPost':
            if (isset($segments[1]) && is_numeric($segments[1])) {
                $postController->editPost($segments[1]);
            } else {
                $errorController->notFound($uri);
            }
            break;
        case 'deletePost':
            if (isset($segments[1]) && is_numeric($segments[1])) {
                $postController->deletePost($segments[1]);
            } else {
                $errorController->notFound($uri);
            }
            break;
        case 'editComment':
            if (isset($segments[1]) && is_numeric($segments[1])) {
                $commentController->editComment($segments[1]);
            } else {
                $errorController->notFound($uri);
            }
            break;
        case 'deleteComment':
            if (isset($segments[1]) && is_numeric($segments[1])) {
                $commentController->deleteComment($segments[1]);
            } else {
                $errorController->notFound($uri);
            }
            break;
        case 'addComment':
            if (isset($segments[1]) && is_numeric($segments[1])) {
                $commentController->addComment($segments[1]);
            } else {
                $errorController->notFound($uri);
            }
            break;
        case 'approveComment':
            if (isset($segments[1]) && is_numeric($segments[1])) {
                $commentController->approveComment($segments[1]);
            } else {
                $errorController->notFound($uri);
            }
            break;
        case 'logout':
            $userController->logout();
            break;
        default:
            $errorController->notFound($uri);
            break;
    }
}

// Passer les informations de l'utilisateur à Twig
$twig->addGlobal('username', $_SESSION['username'] ?? null);
$twig->addGlobal('isAdmin', $_SESSION['isAdmin'] ?? null);

// Appel de la fonction de routage
handleRoute($mainController, $userController, $postController, $commentController, $errorController);
