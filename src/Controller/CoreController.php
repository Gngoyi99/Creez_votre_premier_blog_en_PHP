<?php
namespace Blog\Twig\Controller;

use PDO;
use PDOException;
use Twig\Environment;

abstract class CoreController {
    protected $twig;
    protected $db;
    public $dbStatus;

    public function __construct(Environment $twig, PDO $db) {
        $this->twig = $twig;
        $this->db = $db;
        $this->dbStatus = "Vous êtes connectés à la base de données.";
    }
}

