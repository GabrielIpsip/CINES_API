<?php


namespace App\Common\Classes;


abstract class DatabaseExportLocker
{
    private const LOCKER_FILE_NAME = 'database_export.lock';

    /**
     * Lock database export to prevent future export to execute in parallel.
     * @return bool True if is locked, else false.
     */
    public static function isLocked(): bool {
        $content = false;

        if (file_exists(self::LOCKER_FILE_NAME)) {
            $sizeFile = filesize(self::LOCKER_FILE_NAME);
            if ($sizeFile > 0) {
                $lockerFile = fopen(self::LOCKER_FILE_NAME, 'r');
                $content = fread($lockerFile, $sizeFile);
                fclose($lockerFile);
            }
        } else {
            file_put_contents(self::LOCKER_FILE_NAME, false);
        }

        return $content;
    }

    /**
     * Lock database export to prevent parallel execution.
     */
    public static function lock() {
        file_put_contents(self::LOCKER_FILE_NAME, true);
    }

    /**
     * Unlock database export, database export will can be launched.
     */
    public static function unlock() {
        file_put_contents(self::LOCKER_FILE_NAME, false);
    }
}