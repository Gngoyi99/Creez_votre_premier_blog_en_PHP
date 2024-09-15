<?php

namespace Blog\Twig\Repository;

use Blog\Twig\Model\Comment;
use PDO;

class CommentRepository
{
    private $db;

    public function __construct(PDO $db)
    {
        $this->db = $db;
    }

    public function findAllByPostId($postId)
    {
        $stmt = $this->db->prepare('SELECT * FROM CommentPost WHERE postId = :postId AND approved = 1 ORDER BY created_at DESC');
        $stmt->execute(['postId' => $postId]);
        $comments = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $result = [];
        foreach ($comments as $comment) {
            $result[] = new Comment(
                $comment['commentId'],
                $comment['content'],
                $comment['id_user'],
                $comment['postId'],
                $comment['created_at'],
                $comment['approved']
            );
        }

        return $result;
    }

    public function findById($commentId)
    {
        $stmt = $this->db->prepare('SELECT * FROM CommentPost WHERE commentId = :commentId');
        $stmt->execute(['commentId' => $commentId]);
        $comment = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($comment) {
            return new Comment(
                $comment['commentId'],
                $comment['content'],
                $comment['id_user'],
                $comment['postId'],
                $comment['created_at'],
                $comment['approved']
            );
        }

        return null;
    }

    public function save(Comment $comment)
    {
        if ($comment->getCommentId()) {
            // Update existing comment
            $stmt = $this->db->prepare('UPDATE CommentPost SET content = :content, approved = :approved WHERE commentId = :commentId');
            $stmt->execute([
                'content' => $comment->getContent(),
                'approved' => $comment->getApproved(),
                'commentId' => $comment->getCommentId()
            ]);
        } else {
            // Insert new comment
            $stmt = $this->db->prepare('INSERT INTO CommentPost (content, id_user, postId, created_at, approved) VALUES (:content, :userId, :postId, NOW(), :approved)');
            $stmt->execute([
                'content' => $comment->getContent(),
                'userId' => $comment->getUserId(),
                'postId' => $comment->getPostId(),
                'approved' => $comment->getApproved()
            ]);

            // Set the ID of the newly inserted comment
            $commentId = $this->db->lastInsertId();
            $commentReflection = new \ReflectionClass($comment);
            $commentIdProperty = $commentReflection->getProperty('commentId');
            $commentIdProperty->setAccessible(true);
            $commentIdProperty->setValue($comment, $commentId);
        }
    }

    public function delete($commentId)
    {
        $stmt = $this->db->prepare('DELETE FROM CommentPost WHERE commentId = :commentId');
        $stmt->execute(['commentId' => $commentId]);
    }

    public function approve($commentId)
    {
        $stmt = $this->db->prepare('UPDATE CommentPost SET approved = 1 WHERE commentId = :commentId');
        $stmt->execute(['commentId' => $commentId]);
    }

    public function getUserById($userId)
    {
        $stmt = $this->db->prepare('SELECT * FROM User WHERE id_user = :id_user');
        $stmt->execute(['id_user' => $userId]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function getPendingComments()
    {
        $stmt = $this->db->prepare('
            SELECT c.*, u.username 
            FROM CommentPost c
            JOIN User u ON c.id_user = u.id_user 
            WHERE c.approved = 0
        ');
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
