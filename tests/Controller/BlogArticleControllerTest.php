<?php

namespace App\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;
use App\Entity\BlogArticle;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class BlogArticleControllerTest extends WebTestCase
{
    private $client;
    private $entityManager;
    private $jwtToken;

    protected function setUp(): void
    {
        // Create the client to simulate requests
        $this->client = static::createClient();

        // Get entity manager for database interactions
        $this->entityManager = self::$kernel->getContainer()->get('doctrine')->getManager();

        // Obtain JWT token
        $this->jwtToken = $this->getJwtToken();
    }

    private function getJwtToken(): string
    {
        // Simulate login to obtain JWT token
        $this->client->request('POST', '/login', [], [], 
            ['CONTENT_TYPE' => 'application/json'],
            json_encode([
                'username' => 'boushaba',
                'password' => '123456',
            ])
        );

        $response = $this->client->getResponse();
        $data = json_decode($response->getContent(), true);

        // Check if token is received
        if (isset($data['token'])) {
            return $data['token'];
        }

        throw new \Exception('Failed to obtain JWT token.');
    }

    // Test creating a new blog article with image upload and content keyword analysis
    public function testCreateBlogArticleWithImageUpload(): void
    {
        // Create a temporary file to simulate the image upload
        $tempFile = tempnam(sys_get_temp_dir(), 'test_image');
        imagejpeg(imagecreatetruecolor(10, 10), $tempFile);
        $uploadedFile = new UploadedFile($tempFile, 'test-image.jpg', 'image/jpeg', null, true);

        $this->client->request('POST', '/api/blog/article/add', [], [
            'coverPicture' => $uploadedFile // Simulate file upload
        ], [
            'CONTENT_TYPE' => 'multipart/form-data',
            'HTTP_AUTHORIZATION' => 'Bearer ' . $this->jwtToken,
        ], json_encode([
            'authorId' => 1,
            'title' => 'Test Blog Article with Image',
            'publicationDate' => '2024-09-08 10:00:00',
            'content' => 'This is a test blog article with an image. It also includes keyword testing.',
            'keywords' => ['test', 'blog'],
            'status' => 'draft',
            'slug' => 'test-blog-article-image',
        ]));

        $response = $this->client->getResponse();
        $this->assertEquals(Response::HTTP_CREATED, $response->getStatusCode());

        // Check response content
        $content = json_decode($response->getContent(), true);

        // Assert the article title is correct
        $this->assertArrayHasKey('title', $content);
        $this->assertEquals('Test Blog Article with Image', $content['title']);

        // Assert the image file is uploaded and the file name is stored in coverPictureRef
        $this->assertArrayHasKey('coverPictureRef', $content);
        $this->assertNotNull($content['coverPictureRef']);
        $this->assertStringEndsWith('.jpg', $content['coverPictureRef']);

        // Assert keywords are correctly generated based on content frequency
        $this->assertArrayHasKey('keywords', $content);
        $this->assertEquals(['test', 'article', 'image'], $content['keywords']); // Expected based on logic

        // Ensure no banned words are included
        $bannedWords = ['this', 'is']; // Example of banned words
        foreach ($content['keywords'] as $keyword) {
            $this->assertNotContains($keyword, $bannedWords, "Keyword '$keyword' is banned but was found.");
        }

        // Clean up the temporary file
        unlink($tempFile);
    }

    // Test listing blog articles
    public function testListBlogArticles(): void
    {
        $this->client->request('GET', '/api/blog/article/list', [], [], [
            'HTTP_AUTHORIZATION' => 'Bearer ' . $this->jwtToken,
        ]);
        $response = $this->client->getResponse();

        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());
        $content = json_decode($response->getContent(), true);

        // Assuming there are existing articles
        $this->assertIsArray($content);
        $this->assertGreaterThan(0, count($content)); // Articles exist
    }

    // Test getting an article by ID
    public function testGetArticleById(): void
    {
        // Assuming an article with ID 1 exists
        $this->client->request('GET', '/api/blog/article/find/1', [], [], [
            'HTTP_AUTHORIZATION' => 'Bearer ' . $this->jwtToken,
        ]);
        $response = $this->client->getResponse();

        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());

        // Check the article's data
        $content = json_decode($response->getContent(), true);
        $this->assertArrayHasKey('id', $content);
        $this->assertEquals(1, $content['id']);
    }

    // Test updating an article and verifying keywords update
    public function testUpdateArticle(): void
    {
        $this->client->request('PATCH', '/api/blog/article/update/1', [], [], 
            ['CONTENT_TYPE' => 'application/json', 'HTTP_AUTHORIZATION' => 'Bearer ' . $this->jwtToken],
            json_encode([
                'title' => 'Updated Blog Article',
                'content' => 'This updated content has more words. Words matter when you update content.',
                'status' => 'published'
            ])
        );

        $response = $this->client->getResponse();
        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());

        // Check the updated data
        $content = json_decode($response->getContent(), true);

        // Assert the article title and status are updated correctly
        $this->assertEquals('Updated Blog Article', $content['title']);
        $this->assertEquals('published', $content['status']);

        // Assert the keywords are updated based on the new content
        $this->assertArrayHasKey('keywords', $content);
        $this->assertEquals(['words', 'content', 'update'], $content['keywords']); // Replace based on logic

        // Ensure no banned words are included in keywords
        $bannedWords = ['this', 'has', 'more']; // Example of banned words
        foreach ($content['keywords'] as $keyword) {
            $this->assertNotContains($keyword, $bannedWords, "Keyword '$keyword' is banned but was found.");
        }
    }

    // Test soft deleting an article
    public function testSoftDeleteArticle(): void
    {
        // Assuming an article with ID 1 exists
        $this->client->request('DELETE', '/api/blog/article/delete/1', [], [], [
            'HTTP_AUTHORIZATION' => 'Bearer ' . $this->jwtToken,
        ]);

        $response = $this->client->getResponse();
        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());

        // Check if the article status is set to 'deleted'
        $article = $this->entityManager->getRepository(BlogArticle::class)->find(1);
        $this->assertEquals('deleted', $article->getStatus());
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        $this->entityManager->close();
        $this->entityManager = null; // avoid memory leaks
    }
}
