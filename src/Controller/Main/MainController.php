<?php

namespace Blog\Twig\Controller\Main;

use Blog\Twig\Controller\CoreController;
use Blog\Twig\Utils\Validator;
use Blog\Twig\Utils\Notification;
use Blog\Twig\Repository\CommentRepository;
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use PDO;


class MainController extends CoreController
{
    private $commentRepository;

    public function __construct($twig, PDO $db)
    {
        parent::__construct($twig, $db);
        $this->commentRepository = new CommentRepository($db);
    }

    public function index()
    {
        if (isset($_SESSION['username'])) {
            $this->home();
        } else {
            echo $this->twig->render('index.html.twig');
        }
    }

    public function home()
    {
        $notifications = Notification::getMessages();

        //Mail
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $surname = htmlspecialchars(trim($_POST['surname']));
            $name = htmlspecialchars(trim($_POST['name']));
            $email = filter_var(trim($_POST['email']), FILTER_SANITIZE_EMAIL);
            $message = htmlspecialchars(trim($_POST['message']));

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
                foreach ($errors as $field => $error) {
                    Notification::addMessage(Notification::ERROR, $error);
                }

                echo $this->twig->render('home.html.twig', [
                    'notifications' => $notifications,
                    'errors' => $errors
                ]);
                return;
            }

            $mail = new PHPMailer(true);

            try {
                $mail->isSMTP();
                $mail->Host = '127.0.0.1';
                $mail->SMTPAuth = false;
                $mail->Port = 1025;

                $mail->setFrom($email, $name);
                $mail->addAddress('gngoyi58@gmail.com');

                $mail->isHTML(true);
                $mail->Subject = 'Nouveau message de contact';
                $mail->Body    = "Nom: $surname<br>Prénom: $name<br>E-mail: $email<br>Message: $message";
                $mail->AltBody = "Nom: $surname\nPrénom: $name\nE-mail: $email\nMessage: $message";

                $mail->send();
                Notification::addMessage(Notification::SUCCESS, 'Votre message a été envoyé avec succès.');
            } catch (Exception $e) {
                Notification::addMessage(Notification::ERROR, "Une erreur est survenue lors de l'envoi du message. Veuillez réessayer plus tard. Mailer Error: {$mail->ErrorInfo}");
            }
        }

        //Admin 
        if (isset($_SESSION['id_user'])) {
            $userId = $_SESSION['id_user'];

            $user = $this->commentRepository->getUserById($userId);

            $isAdmin = isset($_SESSION['isAdmin']) && $_SESSION['isAdmin'] == 1;

            $comments = [];
            if ($isAdmin) {
                $comments = $this->commentRepository->getPendingComments();
            }

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
