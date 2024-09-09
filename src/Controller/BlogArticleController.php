<?php

namespace App\Controller;

use App\Entity\BlogArticle;
use OpenApi\Annotations as OA;
use Doctrine\ORM\EntityManagerInterface;
use Nelmio\ApiDocBundle\Annotation\Model;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\Exception\FileException;

#[Route('/api')]
class BlogArticleController extends AbstractController
{
    /**
     * @OA\Get(
     *     path="/api/blog/article/list",
     *     summary="Get list of blog articles",
     *     @OA\Response(
     *         response=200,
     *         description="Returns list of blog articles",
     *         @OA\JsonContent(type="array", @OA\Items(ref=@Model(type=BlogArticle::class)))
     *     )
     * )
     */
    #[Route('/blog/article/list', name: 'list_blog_article', methods: ['GET'])]
    public function list_blog_article(Request $request, EntityManagerInterface $em)
    {
        $articles = $em->getRepository(BlogArticle::class)->findAll();
        return $this->json($articles);
    }

    /**
     * @OA\Post(
     *     path="/api/blog/article/add",
     *     summary="Add a new blog article",
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(ref=@Model(type=BlogArticle::class))
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Blog article created successfully"
     *     )
     * )
     */
    #[Route('/blog/article/add', name: 'add_new_blog_article', methods: ['POST'])]
    public function add_new_blog_article(Request $request, EntityManagerInterface $em)
    {
        $bannedWords = ['banned1', 'banned2']; // Define your banned words here
        $data = json_decode($request->getContent(), true);

        $file = $request->files->get('coverPicture');

        // Validate content
        if (!validateContent($data['content'], $bannedWords)) {
            return $this->json(['message' => 'Content contains banned words'], Response::HTTP_BAD_REQUEST);
        }

        // Create a new BlogArticle entity
        $article = new BlogArticle();
        $article->setAuthorId($data['authorId']);
        $article->setTitle($data['title']);
        $article->setPublicationDate(new \DateTime($data['publicationDate']));
        $article->setCreationDate(new \DateTime());
        $article->setContent($data['content']);
        $article->setStatus($data['status']);
        $article->setSlug($data['slug']);

        // Find the top 3 keywords from the content and set them
        $topKeywords = getTopThreeWords($data['content'], $bannedWords);
        $article->setKeywords($topKeywords);

        if ($file instanceof UploadedFile) {
            // Handle the file upload
            $newFilename = $this->uploadCoverPicture($file);
            $article->setCoverPictureRef($newFilename); // Save the filename to coverPictureRef
        }

        $em->persist($article);
        $em->flush();

        return $this->json($article, Response::HTTP_CREATED);
    }

    private function uploadCoverPicture(UploadedFile $file): string
    {
        $uploadDir = $this->getParameter('kernel.project_dir') . '/public/uploaded_pictures';

        // Generate a unique name for the file before saving it
        $newFilename = uniqid() . '.' . $file->guessExtension();

        try {
            // Move the file to the target directory
            $file->move($uploadDir, $newFilename);
        } catch (FileException $e) {
            throw new \Exception('Failed to upload file');
        }

        return $newFilename; // Return the new file name
    }

    /**
     * @OA\Get(
     *     path="/api/blog/article/find/{id}",
     *     summary="Find a blog article by ID",
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="Blog article ID",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Returns the found blog article",
     *         @OA\JsonContent(ref=@Model(type=BlogArticle::class))
     *     )
     * )
     */
    #[Route('/blog/article/find/{id}', name: 'find_blog_article', methods: ['GET'])]
    public function getArticle(int $id, EntityManagerInterface $em): Response
    {
        $article = $em->getRepository(BlogArticle::class)->find($id);

        if (!$article) {
            return $this->json(['message' => 'Article not found'], Response::HTTP_NOT_FOUND);
        }

        return $this->json($article);
    }

    /**
     * @OA\Patch(
     *     path="/api/blog/article/update/{id}",
     *     summary="Update a blog article",
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(ref=@Model(type=BlogArticle::class))
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Updated blog article"
     *     )
     * )
     */
    #[Route('/blog/article/update/{id}', methods: ['PATCH'])]
    public function update(BlogArticle $article, Request $request, EntityManagerInterface $em): Response
    {
        $bannedWords = ['banned1', 'banned2']; // Define your banned words here
        $data = json_decode($request->getContent(), true);

        if (isset($data['content'])) {
            // Validate content
            if (!validateContent($data['content'], $bannedWords)) {
                return $this->json(['message' => 'Content contains banned words'], Response::HTTP_BAD_REQUEST);
            }

            $article->setContent($data['content']);

            // Update keywords based on the new content
            $topKeywords = getTopThreeWords($data['content'], $bannedWords);
            $article->setKeywords($topKeywords);
        }

        // Other updates...
        if (isset($data['title'])) {
            $article->setTitle($data['title']);
        }
        if (isset($data['publicationDate'])) {
            $article->setPublicationDate(new \DateTime($data['publicationDate']));
        }

        $em->flush();

        return $this->json($article);
    }

    /**
     * @OA\Delete(
     *     path="/api/blog/article/delete/{id}",
     *     summary="Soft delete a blog article",
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="Blog article ID",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Article soft deleted"
     *     )
     * )
     */
    #[Route('/blog/article/delete/{id}', methods: ['DELETE'])]
    public function softDelete(BlogArticle $article, EntityManagerInterface $em): Response
    {
        if (!$article) {
            return $this->json(['message' => 'Article not found'], Response::HTTP_NOT_FOUND);
        }

        // Soft delete by changing the status to 'deleted'
        $article->setStatus('deleted');
        $em->flush();

        return $this->json(['message' => 'Article soft deleted']);
    }
}


function getTopThreeWords($text, $banned) {
    // Convert text to lowercase and split into words, removing punctuation
    $words = preg_split('/\W+/', strtolower($text), -1, PREG_SPLIT_NO_EMPTY);

    // Word frequency count
    $wordCount = array();
    foreach ($words as $word) {
        if (!in_array($word, $banned)) {
            if (isset($wordCount[$word])) {
                $wordCount[$word]++;
            } else {
                $wordCount[$word] = 1;
            }
        }
    }

    // Sort words by frequency in descending order
    arsort($wordCount);

    // Get the top 3 words
    return array_slice(array_keys($wordCount), 0, 3);
}

function validateContent($content, $banned) {
    // Convert text to lowercase and split into words, removing punctuation
    $words = preg_split('/\W+/', strtolower($content), -1, PREG_SPLIT_NO_EMPTY);

    // Check for banned words in the content
    foreach ($words as $word) {
        if (in_array($word, $banned)) {
            return false;  // Content contains a banned word
        }
    }
    return true;  // No banned words found
}
