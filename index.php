<?php
require 'vendor/autoload.php';

use Aws\S3\S3Client;
use Aws\S3\Exception\S3Exception;

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();

$accessKeyId = $_ENV['AWS_ACCESS_KEY_ID'];
$secretAccessKey = $_ENV['AWS_SECRET_ACCESS_KEY'];
$region = $_ENV['AWS_REGION'];
$bucketName = $_ENV['AWS_BUCKET'];

$host = $_ENV['DB_HOST'];;
$dbname = $_ENV['DATABASE'];
$username = $_ENV['DB_USER'];
$password = $_ENV['DB_PASSWORD'];

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    echo "Erro na conexão com o banco de dados: " . $e->getMessage();
    exit();
}

if (isset($_FILES['video'])) {
    $video = $_FILES['video'];
    $videoName = $video['name'];
    $videoTmpPath = $video['tmp_name'];

    try {
        $s3 = new S3Client([
            'version' => 'latest',
            'region' => $region,
            'credentials' => [
                'key' => $accessKeyId,
                'secret' => $secretAccessKey,
            ],
        ]);

        $result = $s3->putObject([
            'Bucket' => $bucketName,
            'Key' => $videoName,
            'SourceFile' => $videoTmpPath,
        ]);

        $videoUrl = $result['ObjectURL'];

        $stmt = $pdo->prepare("INSERT INTO videos (video_url) VALUES (:video_url)");
        $stmt->bindParam(':video_url', $videoUrl);
        $stmt->execute();

        echo "Upload do vídeo realizado com sucesso.";
    } catch (S3Exception $e) {
        echo "Erro ao realizar o upload do vídeo: " . $e->getMessage();
    }
} else {
    echo "Nenhum vídeo foi enviado.";
}
?>