<?php

namespace App\Utils;

use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\String\Slugger\SluggerInterface;

class FileManager
{
    public function __construct(private SluggerInterface $slugger)
    {
    }

    public function upload(UploadedFile $file, string $dir, string $name, ?string $oldRessourceToDelete = null): string
    {
        $name = $this->slugger->slug($name) . '-' . uniqid() . '.' . $file->guessExtension();
        $file->move($dir, $name);

        if ($oldRessourceToDelete) {
            $this->delete($dir, $oldRessourceToDelete);
        }

        return $name;
    }

    public function uploadPoster(UploadedFile $poster, string $dir, string $name, ?string $oldRessourceToDelete = null): string
    {
        $name = $this->slugger->slug($name) . '-' . uniqid() . '.' . $poster->guessExtension();
        $poster->move($dir, $name);

        if ($oldRessourceToDelete) {
            $this->delete($dir, $oldRessourceToDelete);
        }

        return $name;
    }

    public function uploadProfilePicture(UploadedFile $profilePicture, string $name, ?string $oldRessourceToDelete = null): string
    {
        $filename = $this->slugger->slug($name) . '-' . uniqid() . '.' . $profilePicture->guessExtension();

        $dir = __DIR__ . '/../../public/uploads/profile_pictures';

        $profilePicture->move($dir, $filename);

        if ($oldRessourceToDelete) {
            $this->delete($dir, $oldRessourceToDelete);
        }

        return $filename;
    }

    public function delete(string $dir, string $name): bool
    {
        if (\file_exists($dir . '/' . $name)) {
            unlink($dir . '/' . $name);
            return true;
        }

        return false;
    }
}