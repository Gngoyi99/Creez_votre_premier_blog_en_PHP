<?php
namespace Blog\Twig\Controller\User;

use Blog\Twig\Controller\CoreController;
use Blog\Twig\Utils\Validator;
use Blog\Twig\Utils\Notification;
use PDO;

class UserController extends CoreController {

    public function userLogin() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $username = $_POST['username'];
            $password = $_POST['password'];

            // Vérification de l'utilisateur en db
            $stmt = $this->db->prepare('SELECT * FROM User WHERE username = :username');
            $stmt->execute(['username' => $username]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            // Vérification du mot de passe
            if ($user && password_verify($password, $user['password'])) {
                // Démarrage de la session
                session_start();
                $_SESSION['id_user'] = $user['id_user'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['isAdmin'] = $user['isAdmin']; // Stocker le statut d'admin

                // Redirection vers la page d'accueil après la connexion réussie
                header('Location: /BlogPHP/home');
                exit;
            } else {
                // Affichage du formulaire de connexion avec un message d'erreur
                echo $this->twig->render('User/userLogin.html.twig', [
                    'error' => 'Nom d\'utilisateur ou mot de passe incorrect'
                ]);
            }
        } else {
            // Affichage du formulaire de connexion avec les notifications
        $notifications = Notification::getMessages();
    
        echo $this->twig->render('User/userLogin.html.twig', [
            'notifications' => $notifications
        ]);
        }
    }    

    public function userRegister() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            // Récupération des données du formulaire
            $name = $_POST['name'];
            $surname = $_POST['surname'];
            $username = $_POST['username'];
            $email = $_POST['email'];
            $password = $_POST['password'];
    
            // Validation des données côté serveur
            $errors = [];
    
            if (!Validator::validateNameAndSurname($name)) {
                $errors['name'] = 'Le prénom ne peut contenir que des lettres, des espaces, des apostrophes et des tirets';
            }
    
            if (!Validator::validateNameAndSurname($surname)) {
                $errors['surname'] = 'Le nom ne peut contenir que des lettres, des espaces, des apostrophes et des tirets';
            }
    
            if (!Validator::validateUsername($username)) {
                $errors['username'] = 'Le nom d\'utilisateur ne peut contenir que des lettres, des chiffres et underscores (_)';
            }
    
            if (!Validator::validateEmail($email)) {
                $errors['email'] = 'Veuillez entrer une adresse e-mail valide';
            }
    
            if (!Validator::validatePassword($password)) {
                $errors['password'] = 'Le mot de passe doit contenir au moins 8 caractères avec au moins une majuscule, un chiffre et un caractère spécial';
            }

            // Vérification de l'unicité du nom d'utilisateur
            $stmt = $this->db->prepare('SELECT COUNT(*) FROM User WHERE username = :username');
            $stmt->execute(['username' => $username]);
            if ($stmt->fetchColumn() > 0) {
                $errors['username'] = 'Ce nom d\'utilisateur est déjà utilisé. Veuillez en choisir un autre.';
            }

            // Vérification de l'unicité de l'email
            $stmt = $this->db->prepare('SELECT COUNT(*) FROM User WHERE email = :email');
            $stmt->execute(['email' => $email]);
            if ($stmt->fetchColumn() > 0) {
                $errors['email'] = 'Cette adresse e-mail est déjà utilisée. Veuillez en choisir une autre.';
            }
    
            if (!empty($errors)) {
                // Affichage du formulaire d'inscription avec les erreurs
                foreach ($errors as $error) {
                    Notification::addMessage(Notification::ERROR, $error);
                }
    
                echo $this->twig->render('User/userRegister.html.twig', [
                    'dbStatus' => $this->dbStatus,
                    'errors' => $errors
                ]);
                return;
            }
    
            // Hachage du mot de passe
            $passwordHash = password_hash($password, PASSWORD_BCRYPT);
    
            // Insertion de l'utilisateur dans la db
            $stmt = $this->db->prepare('INSERT INTO User (name, surname, username, email, password) VALUES (:name, :surname, :username, :email, :password)');
            $stmt->execute([
                'name' => $name,
                'surname' => strtoupper($surname),
                'username' => $username,
                'email' => $email,
                'password' => $passwordHash
            ]);
    
            // Ajout du message de succès
            Notification::addMessage(Notification::SUCCESS, 'Inscription réussie ! Vous pouvez maintenant vous connecter.');
    
            // Redirection vers la page de connexion après l'inscription réussie
            header('Location: /BlogPHP/userLogin');
            exit;
        } else {
            // Affichage du formulaire d'inscription
            echo $this->twig->render('User/userRegister.html.twig', ['dbStatus' => $this->dbStatus]);
        }
    }
    
    public function logout() {
        // Détruire la session et rediriger vers la page d'accueil
        session_start();
        session_destroy();
        header('Location: /BlogPHP/');
        exit;
    }

    public function editProfile() {
        // Vérifiez si l'utilisateur est connecté 
        if (!isset($_SESSION['id_user'])) {
            header('Location: /BlogPHP/userLogin');
            exit;
        }
    
        $userId = $_SESSION['id_user'];
    
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            // Récupération des données du formulaire
            $name = $_POST['name'];
            $surname = $_POST['surname'];
            $message = $_POST['message'];
            $photo = $_FILES['photo'];
            $cvFile = $_FILES['cv_file'];
    
            // Récupération des informations actuelles de l'utilisateur
            $stmt = $this->db->prepare('SELECT * FROM User WHERE id_user = :id_user');
            $stmt->execute(['id_user' => $userId]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
            // Validation des données côté serveur
            $nameValid = Validator::validateNameAndSurname($name, 'prénom');
            $surnameValid = Validator::validateNameAndSurname($surname, 'Nom');
            $errors = [];
    
            if (!$nameValid) $errors['name'] = "Le prénom n'est pas valide.";
            if (!$surnameValid) $errors['surname'] = "Le nom n'est pas valide.";
    
            $targetFile = $user['photo'];
            if (!empty($photo['name'])) {
                $allowedTypes = ['image/jpeg', 'image/png', 'image/gif'];
                if (!in_array($photo['type'], $allowedTypes)) {
                    $errors['photo'] = "Le format de l'image n'est pas valide.";
                } else {
                    $targetDir = "uploads/photos/";
                    $targetFile = $targetDir . basename($photo['name']);
                    if (!move_uploaded_file($photo['tmp_name'], $targetFile)) {
                        $errors['photo'] = "L'image n'a pas pu être téléchargée.";
                    }
                }
            }
    
            $cvTargetFile = $user['cv'];
            if (!empty($cvFile['name'])) {
                $allowedTypes = ['application/pdf'];
                if (!in_array($cvFile['type'], $allowedTypes)) {
                    $errors['cv'] = "Le format du fichier CV n'est pas valide. Seul le format PDF est accepté.";
                } else {
                    $cvTargetDir = "uploads/cv_files/";
                    $cvTargetFile = $cvTargetDir . basename($cvFile['name']);
                    if (!move_uploaded_file($cvFile['tmp_name'], $cvTargetFile)) {
                        $errors['cv'] = "Le fichier CV n'a pas pu être téléchargé.";
                    }
                }
            }
    
            if (!empty($errors)) {
                // Affichage du formulaire avec les erreurs
                echo $this->twig->render('User/editProfile.html.twig', [
                    'user' => [
                        'name' => $name,
                        'surname' => $surname,
                        'message' => $message,
                        'cv' => $cvTargetFile,
                        'photo' => $targetFile
                    ],
                    'errors' => $errors
                ]);
                return;
            }
    
            // Mise à jour de l'utilisateur dans la db
            $stmt = $this->db->prepare('UPDATE User SET name = :name, surname = :surname, message = :message, cv = :cv, photo = :photo WHERE id_user = :id_user');
            $stmt->execute([
                'name' => $name,
                'surname' => strtoupper($surname),
                'message' => $message,
                'cv' => $cvTargetFile,
                'photo' => $targetFile,
                'id_user' => $userId
            ]);
    
            // Ajout du message de succès
            Notification::addMessage(Notification::SUCCESS, 'Profil mis à jour avec succès.');
    
            // Redirection vers la page d'édition du profil
            header('Location: /BlogPHP/home');
            exit;
        } else {
            // Récupération des informations de l'utilisateur depuis la base de données
            $stmt = $this->db->prepare('SELECT * FROM User WHERE id_user = :id_user');
            $stmt->execute(['id_user' => $userId]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
            // Affichage du formulaire avec les informations de l'utilisateur
            echo $this->twig->render('User/editProfile.html.twig', [
                'user' => $user,
                'errors' => []
            ]);
        }
    }  

}
