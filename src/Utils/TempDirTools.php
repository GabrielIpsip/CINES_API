<?php


namespace App\Utils;


use Exception;

class TempDirTools
{

    /**
     * Get template dir path.
     * @throws Exception Generation token for directory name not working.
     */
    public static function getTempDir(string $rootDir): ?string
    {
        $rootDir = $rootDir . '/';

        TempDirTools::cleanOldFile($rootDir);

        do {
            $token = StringTools::generateToken(20);
            $directory = $rootDir . $token . '/';
        } while (file_exists($directory));

        mkdir($directory, 0777, true);

        return $directory;
    }

    /**
     * Remove old file and directory. (older than 1 hours)
     * @param string $rootDir Directory path to clean.
     */
    private static function cleanOldFile(string $rootDir)
    {
        $directories = glob($rootDir."/*");
        foreach ($directories as $directory) {
            if(time() - filectime($directory) > 3600) {
                if (!is_dir($directory)) {
                    continue;
                }
                $files = glob($directory."/*");
                foreach ($files as $file) {
                    unlink($file);
                }
                rmdir($directory);
            }
        }
    }
}
