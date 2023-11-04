<?php
namespace App\Services;
use Illuminate\Support\Facades\Storage;
use Illuminate\Http\UploadedFile;

class ImageUploadService
{
    public static function upload(UploadedFile $file, $directory)
    {
        $path = $file->store($directory, 'public');
        return $path;
    }

    public static function delete($path)
    {
        if ($path) {
            Storage::delete('public'.'/'.$path);
        }
    }
}



?>
