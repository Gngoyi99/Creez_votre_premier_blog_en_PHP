<?php
namespace Blog\Twig\Controller\Main;

use Blog\Twig\Controller\CoreController;
use Blog\Twig\Utils\Validator;
use Blog\Twig\Utils\Notification;
use PDO;

class MainController extends CoreController {

    public function index() {
        if (isset($_SESSION['username'])) {
            $this->home();
        } else {
            echo $this->twig->render('index.html.twig');
        }
    }

    public function home() {
        $notifications = Notification::getMessages();
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            // Récupération des données du formulaire
            $surname = $_POST['surname'];
            $name = $_POST['name'];
            $email = $_POST['email'];
            $message = $_POST['message'];
    
            // Validation des données
            $errors = [];
            if (!Validator::validateNameAndSurname($surname, 'surname')) {
                $errors['surname'] = 'Le nom ne peut contenir que des lettres, des espaces, des apostrophes et des tirets';
            }
            if (!Validator::validateNameAndSurname($name, 'name')) {
                $errors['name'] = 'Le prénom ne peut contenir que des lettres, des espaces, des apostrophes et des tirets';
            }
            if (!Validator::validateEmail($email)) {
                $errors['email'] = 'Veuillez entrer une adresse e-mail valide';
            }
            if (empty($message)) {
                $errors['message'] = 'Le message ne peut pas être vide';
            }
    
            if (!empty($errors)) {
                // Ajout des erreurs dans les notifications
                foreach ($errors as $field => $error) {
                    Notification::addMessage(Notification::ERROR, $error);
                }
    
                // Affichage de la page d'accueil avec les erreurs
                echo $this->twig->render('home.html.twig', [
                    'notifications' => $notifications,
                    'errors' => $errors
                ]);
                return;
            }
    
            // Envoi du message par e-mail
            $to = 'gngoyi58@gmail.com'; // Adresse site admin
            $subject = 'Nouveau message de contact';
            $body = "Nom: $surname\nPrénom: $name\nE-mail: $email\nMessage: $message";
            $headers = 'From: ' . $email;
    
            if (mail($to, $subject, $body, $headers)) {
                Notification::addMessage(Notification::SUCCESS, 'Votre message a été envoyé avec succès.');
            } else {
                Notification::addMessage(Notification::ERROR, 'Une erreur est survenue lors de l\'envoi du message. Veuillez réessayer plus tard.');
            }
    
            // Redirection vers la page d'accueil pour éviter la soumission multiple
            header('Location: /BlogPHP/home');
            exit;
        }
        // Partie Admin
        if (isset($_SESSION['id_user'])) {
            $userId = $_SESSION['id_user'];
    
            // Récupération des informations de l'utilisateur dans la db
            $stmt = $this->db->prepare('SELECT * FROM User WHERE id_user = :id_user');
            $stmt->execute(['id_user' => $userId]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
            // Vérification si l'utilisateur est admin
            $isAdmin = isset($_SESSION['isAdmin']) && $_SESSION['isAdmin'] == 1;
    
            // Récupération des commentaires en attente de validation si l'utilisateur connecté est Admin
            $comments = [];
            if ($isAdmin) {
                $stmtComments = $this->db->prepare('
                    SELECT c.*, u.username 
                    FROM CommentPost c
                    JOIN User u ON c.id_user = u.id_user 
                    WHERE c.approved = 0
                ');
                $stmtComments->execute();
                $comments = $stmtComments->fetchAll(PDO::FETCH_ASSOC);
            }
    
            // Affichage de la page d'accueil avec les informations de l'utilisateur et les commentaires si il est Admin
            echo $this->twig->render('home.html.twig', [
                'user' => $user,
                'isAdmin' => $isAdmin,
                'comments' => $comments,
                'notifications' => $notifications
            ]);
        } else {
            echo $this->twig->render('index.html.twig');
        }
    }
    
}
