<?php

namespace Blog\Twig\Repository;

use Blog\Twig\Model\Post;
use PDO;

class PostRepository
{
    private $db;

    public function __construct(PDO $db)
    {
        $this->db = $db;
    }

    public function findAll()
    {
        $stmt = $this->db->query('SELECT * FROM Post ORDER BY updated_at DESC');
        $posts = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $result = [];
        foreach ($posts as $post) {
            $result[] = new Post(
                $post['postId'],
                $post['title'],
                $post['chapo'],
                $post['content'],
                $post['user_id'],
                $post['created_at'],
                $post['updated_at']
            );
        }

        return $result;
    }

    public function findById($postId)
    {
        $stmt = $this->db->prepare('SELECT * FROM Post WHERE postId = :postId');
        $stmt->execute(['postId' => $postId]);
        $post = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($post) {
            return new Post(
                $post['postId'],
                $post['title'],
                $post['chapo'],
                $post['content'],
                $post['user_id'],
                $post['created_at'],
                $post['updated_at']
            );
        }

        return null;
    }

    public function save(Post $post)
    {
        if ($post->getPostId()) {
            //Modification d'un post existant
            $stmt = $this->db->prepare('UPDATE Post SET title = :title, chapo = :chapo, content = :content, updated_at = NOW() WHERE postId = :postId');
            $stmt->execute([
                'title' => $post->getTitle(),
                'chapo' => $post->getChapo(),
                'content' => $post->getContent(),
                'postId' => $post->getPostId()
            ]);
        } else {
            //Création d'un nouveau post
            $stmt = $this->db->prepare('INSERT INTO Post (title, chapo, content, user_id, created_at, updated_at) VALUES (:title, :chapo, :content, :user_id, NOW(), NOW())');
            $stmt->execute([
                'title' => $post->getTitle(),
                'chapo' => $post->getChapo(),
                'content' => $post->getContent(),
                'user_id' => $post->getUserId()
            ]);

            //Assigner un nouvel ID
            $postId = $this->db->lastInsertId();
            $postReflection = new \ReflectionClass($post);
            $postIdProperty = $postReflection->getProperty('postId');
            $postIdProperty->setAccessible(true);
            $postIdProperty->setValue($post, $postId);
        }
    }

    public function delete($postId)
    {
        $stmt = $this->db->prepare('DELETE FROM Post WHERE postId = :postId');
        $stmt->execute(['postId' => $postId]);
    }

    public function findCommentsByPostId($postId)
    {
        //Jointure pour trouver utilisateur
        $stmt = $this->db->prepare('
            SELECT c.*, u.username 
            FROM CommentPost c
            JOIN User u ON c.id_user = u.id_user 
            WHERE c.postId = :postId AND c.approved = 1 
            ORDER BY c.created_at DESC
        ');
        $stmt->execute(['postId' => $postId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
