<?php
namespace Blog\Twig\Controller\Post;

use Blog\Twig\Controller\CoreController;
use Blog\Twig\Model\Post;
use Blog\Twig\Repository\PostRepository;
use Blog\Twig\Utils\Validator;
use Blog\Twig\Utils\Notification;
use PDO;

class PostController extends CoreController
{
    private $postRepository;

    public function __construct($twig, PDO $db)
    {
        parent::__construct($twig, $db);
        $this->postRepository = new PostRepository($db);
    }

    public function listPost()
    {
        $posts = $this->postRepository->findAll();
        $notifications = Notification::getMessages();

        // Nettoyer les titres et chapôs des posts pour l'affichage
        foreach ($posts as $post) {
            $post->setTitle(Validator::decodeString($post->getTitle()));
            $post->setChapo(Validator::decodeString($post->getChapo()));
        }

        echo $this->twig->render('Post/listPost.html.twig', [
            'posts' => $posts,
            'notifications' => $notifications
        ]);
    }

    public function addPost()
    {
        if (!isset($_SESSION['username']) || !isset($_SESSION['isAdmin']) || $_SESSION['isAdmin'] != 1) {
            header('Location: /BlogPHP/home');
            exit();
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $title = $_POST['title'] ?? '';
            $chapo = $_POST['chapo'] ?? '';
            $content = $_POST['content'] ?? '';
            $userId = $_SESSION['id_user'];

            $titleValid = Validator::validateMaxLength($title, 255);
            $chapoValid = Validator::validateMaxLength($chapo, 255);
            $contentValid = Validator::validateContent($content);

            if (!$titleValid || !$chapoValid || !$contentValid) {
                Notification::addMessage(Notification::ERROR, "Le titre, le chapô, ou le contenu du post ne sont pas valides.");
                header('Location: /BlogPHP/addPost');
                exit();
            }

            // Nettoyer le titre, le chapô, et le contenu
            $title = Validator::sanitizeString($title);
            $chapo = Validator::sanitizeString($chapo);
            $content = Validator::sanitizeString($content);

            $post = new Post(null, $title, $chapo, $content, $userId, date('Y-m-d H:i:s'));
            $this->postRepository->save($post);

            Notification::addMessage(Notification::SUCCESS, 'Le post a été ajouté avec succès.');
            header('Location: /BlogPHP/listPost');
            exit();
        }

        $notifications = Notification::getMessages();
        echo $this->twig->render('Post/addPost.html.twig', [
            'notifications' => $notifications
        ]);
    }

    public function editPost($postId)
    {
        if (!isset($_SESSION['username']) || !isset($_SESSION['isAdmin']) || $_SESSION['isAdmin'] != 1) {
            header('Location: /BlogPHP/userLogin');
            exit();
        }

        $post = $this->postRepository->findById($postId);

        if (!$post) {
            Notification::addMessage(Notification::ERROR, "Article introuvable.");
            header('Location: /BlogPHP/listPost');
            exit();
        }

        // Décoder le titre, le chapô, et le contenu pour l'affichage
        $post->setTitle(Validator::decodeString($post->getTitle()));
        $post->setChapo(Validator::decodeString($post->getChapo()));
        $post->setContent(Validator::decodeString($post->getContent()));

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $title = $_POST['title'];
            $chapo = $_POST['chapo'];
            $content = $_POST['content'];

            $titleValid = Validator::validateMaxLength($title, 255);
            $chapoValid = Validator::validateMaxLength($chapo, 255);
            $contentValid = Validator::validateContent($content);

            if (!$titleValid || !$chapoValid || !$contentValid) {
                Notification::addMessage(Notification::ERROR, "Le titre, le chapô, ou le contenu du post ne sont pas valides.");
                header("Location: /BlogPHP/editPost/{$postId}");
                exit();
            }

            // Nettoyer le titre, le chapô, et le contenu
            $title = Validator::sanitizeString($title);
            $chapo = Validator::sanitizeString($chapo);
            $content = Validator::sanitizeString($content);

            $post->setTitle($title);
            $post->setChapo($chapo);
            $post->setContent($content);
            $post->setUpdatedAt(date('Y-m-d H:i:s'));

            $this->postRepository->save($post);

            Notification::addMessage(Notification::SUCCESS, 'Le post a été modifié avec succès.');
            header("Location: /BlogPHP/showPost/{$postId}");
            exit();
        }

        $notifications = Notification::getMessages();
        echo $this->twig->render('Post/editPost.html.twig', [
            'post' => $post,
            'notifications' => $notifications
        ]);
    }

    public function deletePost($postId)
    {
        if (!isset($_SESSION['username']) || !isset($_SESSION['isAdmin']) || $_SESSION['isAdmin'] != 1) {
            header('Location: /BlogPHP/userLogin');
            exit();
        }

        $post = $this->postRepository->findById($postId);

        if (!$post) {
            Notification::addMessage(Notification::ERROR, "Article introuvable.");
            header('Location: /BlogPHP/listPost');
            exit();
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->postRepository->delete($postId);

            Notification::addMessage(Notification::SUCCESS, 'Le post a été supprimé avec succès.');
            header('Location: /BlogPHP/listPost');
            exit();
        }

        $notifications = Notification::getMessages();
        echo $this->twig->render('Post/deletePost.html.twig', [
            'post' => $post,
            'notifications' => $notifications
        ]);
    }

    public function showPost($postId)
    {
        $post = $this->postRepository->findById($postId);

        if (!$post) {
            Notification::addMessage(Notification::ERROR, "Post introuvable.");
            header('Location: /BlogPHP/listPost');
            exit();
        }

        // Décoder le titre, le chapô, et le contenu pour l'affichage
        $post->setTitle(Validator::decodeString($post->getTitle()));
        $post->setChapo(Validator::decodeString($post->getChapo()));
        $post->setContent(Validator::decodeString($post->getContent()));

        $comments = $this->postRepository->findCommentsByPostId($postId);
        $notifications = Notification::getMessages();

        echo $this->twig->render('Post/showPost.html.twig', [
            'post' => $post,
            'comments' => $comments,
            'notifications' => $notifications
        ]);
    }
}
