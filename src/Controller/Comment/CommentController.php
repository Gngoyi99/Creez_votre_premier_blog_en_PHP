<?php
namespace Blog\Twig\Controller\Comment;

use Blog\Twig\Controller\CoreController;
use Blog\Twig\Utils\Validator;
use Blog\Twig\Utils\Notification;
use Blog\Twig\Repository\CommentRepository;
use Blog\Twig\Model\Comment;
use PDO;

class CommentController extends CoreController
{
    private $commentRepository;

    public function __construct($twig, PDO $db)
    {
        parent::__construct($twig, $db);
        $this->commentRepository = new CommentRepository($db);
    }

    public function addComment($postId)
    {
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

        // Nettoyer le contenu
        $content = Validator::sanitizeString($content);

        // Déterminer si le commentaire doit être approuvé ou non
        $approved = $isAdmin ? 1 : 0;

        // Mettre le commentaire dans la base de données
        $comment = new Comment(null, $content, $userId, $postId, date('Y-m-d H:i:s'), $approved);
        $this->commentRepository->save($comment);

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

    public function editComment($commentId)
    {
        // Vérifier si l'utilisateur est connecté
        if (!isset($_SESSION['username']) || !isset($_SESSION['id_user'])) {
            header('Location: /BlogPHP/userLogin');
            exit();
        }

        // Récupérer les informations du commentaire en db
        $comment = $this->commentRepository->findById($commentId);

        if (!$comment) {
            Notification::addMessage(Notification::ERROR, "Commentaire introuvable.");
            header('Location: /BlogPHP/home');
            exit();
        }

        // Vérifier si l'utilisateur est l'auteur du commentaire ou un admin
        if ($_SESSION['id_user'] !== $comment->getUserId() && $_SESSION['isAdmin'] != 1) {
            Notification::addMessage(Notification::ERROR, "Vous n'avez pas les droits pour modifier ce commentaire.");
            header('Location: /BlogPHP/showPost/' . $comment->getPostId());
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

            if (!Validator::validateMaxLength($content, 255)) {
                Notification::addMessage(Notification::ERROR, "Le contenu du commentaire ne peut pas dépasser 255 caractères.");
                header('Location: /BlogPHP/editComment/' . $commentId);
                exit();
            }

            // Nettoyer le contenu
            $content = Validator::sanitizeString($content);

            // Mettre à jour le commentaire dans la db
            $comment->setContent($content);
            $this->commentRepository->save($comment);

            // Ajout du message de succès
            Notification::addMessage(Notification::SUCCESS, 'Commentaire modifié avec succès.');

            // Redirection vers le post
            header('Location: /BlogPHP/showPost/' . $comment->getPostId());
            exit();
        }

        // Récupérer les notifications
        $notifications = Notification::getMessages();

        // Décoder le contenu pour l'affichage
        $comment->setContent(Validator::decodeString($comment->getContent()));

        // Afficher le formulaire
        echo $this->twig->render('Comment/editComment.html.twig', [
            'comment' => $comment,
            'notifications' => $notifications
        ]);
    }

    public function deleteComment($commentId)
    {
        // Vérifier si l'utilisateur est connecté
        if (!isset($_SESSION['username']) || !isset($_SESSION['id_user'])) {
            header('Location: /BlogPHP/userLogin');
            exit();
        }

        // Récupérer les informations du commentaire pour redirection
        $comment = $this->commentRepository->findById($commentId);

        if ($comment) {
            // Supprimer le commentaire de la db
            $this->commentRepository->delete($commentId);

            // Ajout du message de succès
            Notification::addMessage(Notification::SUCCESS, 'Commentaire supprimé avec succès.');

            // Redirection vers la page précédente
            header('Location: /BlogPHP/showPost/' . $comment->getPostId());
        } else {
            Notification::addMessage(Notification::ERROR, "Commentaire introuvable.");
            header('Location: /BlogPHP/home');
        }

        exit();
    }

    public function approveComment($commentId)
    {
        if ($_SESSION['isAdmin'] == 1) {
            $this->commentRepository->approve($commentId);

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

