<?php

namespace App\Utils;

use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\String\Slugger\SluggerInterface;

class FileManager
{
    private string $profilePicturesDirectory;
    private SluggerInterface $slugger;

    public function __construct(
        string $profilePicturesDirectory,
        SluggerInterface $slugger
    ) {
        $this->profilePicturesDirectory = $profilePicturesDirectory;
        $this->slugger = $slugger;
    }

    /**
     * Upload une photo de profil
     *
     * @param UploadedFile $file Le fichier uploadé
     * @param string $username Le nom d'utilisateur (pour le nom du fichier)
     * @param string|null $oldFilename L'ancien nom de fichier à supprimer (optionnel)
     * @return string Le nom du nouveau fichier
     * @throws FileException
     */
    public function uploadProfilePicture(UploadedFile $file, string $username, ?string $oldFilename = null): string
    {
        // Supprimer l'ancienne photo si elle existe
        if ($oldFilename) {
            $this->deleteProfilePicture($oldFilename);
        }

        // Sécuriser le nom d'utilisateur pour le nom de fichier
        $safeUsername = $this->slugger->slug($username);

        // Générer un nom de fichier unique
        $originalFilename = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
        $extension = $file->guessExtension();

        // Valider l'extension
        $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
        if (!in_array(strtolower($extension), $allowedExtensions)) {
            throw new FileException('Format de fichier non autorisé. Utilisez JPG, PNG, GIF ou WebP.');
        }

        $newFilename = sprintf(
            '%s-%s.%s',
            $safeUsername,
            uniqid(),
            $extension
        );

        // Déplacer le fichier dans le répertoire de destination
        try {
            $file->move($this->profilePicturesDirectory, $newFilename);
        } catch (FileException $e) {
            throw new FileException('Erreur lors de l\'upload du fichier : ' . $e->getMessage());
        }

        return $newFilename;
    }

    /**
     * Supprime une photo de profil
     *
     * @param string $filename Le nom du fichier à supprimer
     * @return bool True si le fichier a été supprimé, false sinon
     */
    public function deleteProfilePicture(string $filename): bool
    {
        $filePath = $this->profilePicturesDirectory . '/' . $filename;

        if (file_exists($filePath)) {
            return unlink($filePath);
        }

        return false;
    }

    /**
     * Vérifie si un fichier de profil existe
     *
     * @param string $filename Le nom du fichier
     * @return bool
     */
    public function profilePictureExists(string $filename): bool
    {
        $filePath = $this->profilePicturesDirectory . '/' . $filename;
        return file_exists($filePath);
    }

    /**
     * Obtient le chemin complet d'une photo de profil
     *
     * @param string $filename Le nom du fichier
     * @return string
     */
    public function getProfilePicturePath(string $filename): string
    {
        return $this->profilePicturesDirectory . '/' . $filename;
    }

    /**
     * Valide un fichier image avant upload
     *
     * @param UploadedFile $file
     * @param int $maxSizeInMb Taille maximale en MB (par défaut 5MB)
     * @throws FileException
     */
    public function validateImageFile(UploadedFile $file, int $maxSizeInMb = 5): void
    {
        // Vérifier la taille
        $maxSizeInBytes = $maxSizeInMb * 1024 * 1024;
        if ($file->getSize() > $maxSizeInBytes) {
            throw new FileException(
                sprintf('Le fichier est trop volumineux. Taille maximale : %dMB', $maxSizeInMb)
            );
        }

        // Vérifier le type MIME
        $allowedMimeTypes = [
            'image/jpeg',
            'image/jpg',
            'image/png',
            'image/gif',
            'image/webp'
        ];

        if (!in_array($file->getMimeType(), $allowedMimeTypes)) {
            throw new FileException('Format de fichier non autorisé. Utilisez JPG, PNG, GIF ou WebP.');
        }

        // Vérifier que c'est vraiment une image (sécurité supplémentaire)
        $imageInfo = @getimagesize($file->getPathname());
        if ($imageInfo === false) {
            throw new FileException('Le fichier n\'est pas une image valide.');
        }
    }
}