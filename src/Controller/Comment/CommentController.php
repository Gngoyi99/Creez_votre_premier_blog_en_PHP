<?php
namespace Blog\Twig\Controller\Comment;

use Blog\Twig\Controller\CoreController;
use Blog\Twig\Utils\Validator;
use Blog\Twig\Utils\Notification;


use PDO;

class CommentController extends CoreController {

    public function addComment($postId) {
        // Vérifier si l'utilisateur est connecté
        if (!isset($_SESSION['username']) || !isset($_SESSION['id_user'])) {
            header('Location: /BlogPHP/userLogin');
            exit();
        }
    
        // Récupérer les données du formulaire du commentaire
        $content = $_POST['content'];
        $userId = $_SESSION['id_user'];
        $isAdmin = isset($_SESSION['isAdmin']) && $_SESSION['isAdmin'] == 1;
    
        // Validation des données
        if (!Validator::validateContent($content)) {
            Notification::addMessage(Notification::ERROR, "Le contenu du commentaire ne peut pas être vide.");
            header("Location: /BlogPHP/showPost/$postId");
            exit();
        }
    
        if (!Validator::validateMaxLength($content, 255)) {
            Notification::addMessage(Notification::ERROR, "Le contenu du commentaire ne peut pas dépasser 255 caractères.");
            header("Location: /BlogPHP/showPost/$postId");
            exit();
        }
    
        // Déterminer si le commentaire doit être approuvé ou non
        $approved = $isAdmin ? 1 : 0;
    
        // Mettre le commentaire dans la base de données (table CommentPost)
        $stmt = $this->db->prepare('INSERT INTO CommentPost (content, id_user, postId, created_at, approved) VALUES (:content, :id_user, :postId, NOW(), :approved)');
        $stmt->execute([
            'content' => htmlspecialchars($content),
            'id_user' => $userId,
            'postId' => $postId,
            'approved' => $approved
        ]);
    
        // Ajouter un message de succès
        if ($approved) {
            Notification::addMessage(Notification::SUCCESS, 'Commentaire créé et validé avec succès.');
        } else {
            Notification::addMessage(Notification::SUCCESS, 'Commentaire créé, en attente de validation.');
        }
    
        // Redirection vers le post
        header("Location: /BlogPHP/showPost/$postId");
        exit();
    }
    
    

    public function editComment($commentId) {
        // Vérifier si l'utilisateur est connecté
        if (!isset($_SESSION['username']) || !isset($_SESSION['id_user'])) {
            header('Location: /BlogPHP/userLogin');
            exit();
        }
    
        // Récupérer les informations du commentaire en db
        $stmt = $this->db->prepare('SELECT * FROM CommentPost WHERE commentId = :commentId');
        $stmt->execute(['commentId' => $commentId]);
        $comment = $stmt->fetch(PDO::FETCH_ASSOC);
    
        if (!$comment) {
            Notification::addMessage(Notification::ERROR, "Commentaire introuvable.");
            header('Location: /BlogPHP/home');
            exit();
        }
    
        // Vérifier si l'utilisateur est l'auteur du commentaire ou un admin
        if ($_SESSION['id_user'] !== $comment['id_user'] && $_SESSION['isAdmin'] != 1) {
            Notification::addMessage(Notification::ERROR, "Vous n'avez pas les droits pour modifier ce commentaire.");
            header('Location: /BlogPHP/showPost/' . $comment['postId']);
            exit();
        }
    
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $content = $_POST['content'];
    
            // Valider les données
            if (!Validator::validateContent($content)) {
                Notification::addMessage(Notification::ERROR, "Le contenu du commentaire ne peut pas être vide.");
                header('Location: /BlogPHP/editComment/' . $commentId);
                exit();
            }
    
            // Mettre à jour le commentaire dans la db
            $stmt = $this->db->prepare('UPDATE CommentPost SET content = :content WHERE commentId = :commentId');
            $stmt->execute([
                'content' => $content,
                'commentId' => $commentId
            ]);
    
            // Ajout du message de succès
            Notification::addMessage(Notification::SUCCESS, 'Commentaire modifié avec succès.');
    
            // Redirection vers le post
            header('Location: /BlogPHP/showPost/' . $comment['postId']);
            exit();
        }
    
        // Récupérer les notifications
        $notifications = Notification::getMessages();

        // Afficher le formulaire
        echo $this->twig->render('Comment/editComment.html.twig', [
            'comment' => $comment,
            'notifications' => $notifications
        ]);
    }
    
    

    public function deleteComment($commentId) {
        // Vérifier si l'utilisateur est connecté
        if (!isset($_SESSION['username']) || !isset($_SESSION['id_user'])) {
            header('Location: /BlogPHP/userLogin');
            exit();
        }
    
        // Récupérer les informations du commentaire pour redirection
        $stmt = $this->db->prepare('SELECT postId FROM CommentPost WHERE commentId = :commentId');
        $stmt->execute(['commentId' => $commentId]);
        $comment = $stmt->fetch(PDO::FETCH_ASSOC);
    
        // Supprimer le commentaire de la db
        $stmt = $this->db->prepare('DELETE FROM CommentPost WHERE commentId = :commentId');
        $stmt->execute(['commentId' => $commentId]);
    
        // Ajout du message de succès
        Notification::addMessage(Notification::SUCCESS, 'Commentaire supprimé avec succès.');
    
        // Redirection vers la page précédente
        header('Location: /BlogPHP/showPost/' . $comment['postId']);
        exit();
    }
    

    public function approveComment($commentId) {
        if ($_SESSION['isAdmin'] == 1) {
            $stmt = $this->db->prepare('UPDATE CommentPost SET approved = 1 WHERE commentId = :commentId');
            $stmt->execute(['commentId' => $commentId]);
    
            // Ajout du message de succès
            Notification::addMessage(Notification::SUCCESS, 'Commentaire approuvé avec succès.');
        } else {
            // Ajout du message d'erreur
            Notification::addMessage(Notification::ERROR, "Vous n'avez pas les droits pour approuver ce commentaire.");
        }
    
        header('Location: /BlogPHP/home');
        exit();
    }
    
}
