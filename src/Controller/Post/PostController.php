<?php
namespace Blog\Twig\Controller\Post;

use Blog\Twig\Controller\CoreController;
use Twig\Environment;
use Blog\Twig\Utils\Validator;
use Blog\Twig\Utils\Notification;

use PDO;

class PostController extends CoreController {

    public function listPost() {
        // Récupération des articles dans la db
        $stmt = $this->db->query('SELECT * FROM Post ORDER BY updated_at DESC');
        $posts = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
        // Récupérer les notifications
        $notifications = Notification::getMessages();
    
        // Affichage de la liste des articles avec les notifications
        echo $this->twig->render('Post/listPost.html.twig', [
            'posts' => $posts,
            'notifications' => $notifications
        ]);
    }
    

    public function addPost() {
        // Vérifier si l'utilisateur est connecté et s'il est admin
        if (!isset($_SESSION['username']) || !isset($_SESSION['isAdmin']) || $_SESSION['isAdmin'] != 1) {
            header('Location: /BlogPHP/home');
            exit();
        }
    
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            // Récupérer les données du formulaire
            $title = $_POST['title'] ?? '';
            $chapo = $_POST['chapo'] ?? '';
            $content = $_POST['content'] ?? '';
            $user_id = $_SESSION['id_user']; //'id_user' est défini dans la session
    
            // Valider les données
            $titleValid = Validator::validateMaxLength($title, 255);
            $chapoValid = Validator::validateMaxLength($chapo, 255);
            $contentValid = Validator::validateContent($content);
    
            if (!$titleValid || !$chapoValid || !$contentValid) {
                Notification::addMessage(Notification::ERROR, "Le titre, le chapô, ou le contenu du post ne sont pas valides.");
                header('Location: /BlogPHP/addPost');
                exit();
            }
    
            // Insérer le post dans la db
            $stmt = $this->db->prepare('INSERT INTO Post (title, chapo, content, user_id, created_at) VALUES (:title, :chapo, :content, :user_id, NOW())');
            $stmt->execute([
                'title' => htmlspecialchars($title),
                'chapo' => htmlspecialchars($chapo),
                'content' => htmlspecialchars($content),
                'user_id' => $user_id
            ]);
    
            // Ajout du message de succès
            Notification::addMessage(Notification::SUCCESS, 'Le post a été ajouté avec succès.');
    
            // Redirection vers la liste des posts
            header('Location: /BlogPHP/listPost');
            exit();
        }
    
        // Afficher le formulaire de création de post
        $notifications = Notification::getMessages();
        echo $this->twig->render('Post/addPost.html.twig', [
            'notifications' => $notifications
        ]);
    }
    
    public function editPost($postId) {
        // Vérifier si l'utilisateur est connecté et admin
        if (!isset($_SESSION['username']) || !isset($_SESSION['isAdmin']) || $_SESSION['isAdmin'] != 1) {
            header('Location: /BlogPHP/userLogin');
            exit();
        }
    
        // Récupérer les informations de l'article
        $stmt = $this->db->prepare('SELECT * FROM Post WHERE postId = :postId');
        $stmt->execute(['postId' => $postId]);
        $post = $stmt->fetch(PDO::FETCH_ASSOC);
    
        if (!$post) {
            Notification::addMessage(Notification::ERROR, "Article introuvable.");
            header('Location: /BlogPHP/listPost');
            exit();
        }
    
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $title = $_POST['title'];
            $chapo = $_POST['chapo'];
            $content = $_POST['content'];
    
            // Valider les données
            $titleValid = Validator::validateMaxLength($title, 255);
            $chapoValid = Validator::validateMaxLength($chapo, 255);
            $contentValid = Validator::validateContent($content);
    
            if (!$titleValid || !$chapoValid || !$contentValid) {
                Notification::addMessage(Notification::ERROR, "Le titre, le chapô, ou le contenu du post ne sont pas valides.");
                header("Location: /BlogPHP/editPost/{$postId}");
                exit();
            }
    
            // Mettre à jour l'article dans la db
            $stmt = $this->db->prepare('UPDATE Post SET title = :title, chapo = :chapo, content = :content WHERE postId = :postId');
            $stmt->execute([
                'title' => htmlspecialchars($title),
                'chapo' => htmlspecialchars($chapo),
                'content' => htmlspecialchars($content),
                'postId' => $postId
            ]);
    
            // Ajout du message de succès
            Notification::addMessage(Notification::SUCCESS, 'Le post a été modifié avec succès..');
    
            // Redirection vers le post mis à jour
            header("Location: /BlogPHP/showPost/{$postId}");
            exit();
        }
    
        // Afficher le formulaire d'édition
        $notifications = Notification::getMessages();
        echo $this->twig->render('Post/editPost.html.twig', [
            'post' => $post,
            'notifications' => $notifications
        ]);
    }
    
    public function deletePost($postId) {
        // Vérifier si l'utilisateur est connecté et admin
        if (!isset($_SESSION['username']) || !isset($_SESSION['isAdmin']) || $_SESSION['isAdmin'] != 1) {
            header('Location: /BlogPHP/userLogin');
            exit();
        }
    
        // Récupérer les informations de l'article
        $stmt = $this->db->prepare('SELECT * FROM Post WHERE postId = :postId');
        $stmt->execute(['postId' => $postId]);
        $post = $stmt->fetch(PDO::FETCH_ASSOC);
    
        if (!$post) {
            Notification::addMessage(Notification::ERROR, "Article introuvable.");
            header('Location: /BlogPHP/listPost');
            exit();
        }
    
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            // Supprimer l'article de la db
            $stmt = $this->db->prepare('DELETE FROM Post WHERE postId = :postId');
            $stmt->execute(['postId' => $postId]);
    
            // Ajout du message de succès
            Notification::addMessage(Notification::SUCCESS, 'Le post a été supprimé avec succès.');
    
            // Rediriger vers la liste des articles
            header('Location: /BlogPHP/listPost');
            exit();
        }
    
        // Afficher la confirmation de suppression
        $notifications = Notification::getMessages();
        echo $this->twig->render('Post/deletePost.html.twig', [
            'post' => $post,
            'notifications' => $notifications
        ]);
    }
    
    
    public function showPost($postId) {
        // Récupérer les informations du post
        $stmt = $this->db->prepare('SELECT * FROM Post WHERE postId = :postId');
        $stmt->execute(['postId' => $postId]);
        $post = $stmt->fetch(PDO::FETCH_ASSOC);
    
        if (!$post) {
            Notification::addMessage(Notification::ERROR, "Post introuvable.");
            header('Location: /BlogPHP/listPost');
            exit();
        }
    
        // Récupérer les commentaires du post
        $stmt = $this->db->prepare('
            SELECT c.*, u.username 
            FROM CommentPost c
            JOIN User u ON c.id_user = u.id_user 
            WHERE c.postId = :postId AND c.approved = 1 
            ORDER BY c.created_at DESC
        ');
        $stmt->execute(['postId' => $postId]);
        $comments = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
        // Récupérer les notifications
        $notifications = Notification::getMessages();
    
        // Passer les informations au template
        echo $this->twig->render('Post/showPost.html.twig', [
            'post' => $post,
            'comments' => $comments,
            'notifications' => $notifications,
            'id_user' => $_SESSION['id_user'] ?? null,
            'isAdmin' => $_SESSION['isAdmin'] ?? null,
            'username' => $_SESSION['username'] ?? null
        ]);
    }
    
    
    

    
}
