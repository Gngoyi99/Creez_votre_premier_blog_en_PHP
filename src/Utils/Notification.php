<?php

namespace Blog\Twig\Utils;

class Notification {
    const SUCCESS = 'success';
    const ERROR = 'error';

    public static function addMessage($type, $message) {
        if (!isset($_SESSION['notifications'])) {
            $_SESSION['notifications'] = [];
        }

        $_SESSION['notifications'][] = ['type' => $type, 'message' => $message];
    }

    public static function getMessages() {
        if (!isset($_SESSION['notifications'])) {
            return [];
        }

        $messages = $_SESSION['notifications'];
        unset($_SESSION['notifications']);
        return $messages;
    }
}
